import { createCipheriv, createDecipheriv, createHash, randomBytes } from "node:crypto";
import { IntegrationError } from "@/lib/api";

type InstagramConfig = {
  appId: string;
  appSecret: string;
  redirectUri: string;
  encryptionKey: Buffer;
  apiVersion: string;
};

type InstagramToken = {
  accessToken: string;
  expiresIn: number;
};

export type InstagramProfile = {
  instagramUserId: string;
  username: string;
  accountType: string | null;
  followers: number | null;
  mediaCount: number | null;
};

export type InstagramInsights = {
  reach: number | null;
  accountsEngaged: number | null;
  totalInteractions: number | null;
  periodEnd: string | null;
};

const insightMetrics = ["reach", "accounts_engaged", "total_interactions"] as const;

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

function getConfig(): InstagramConfig {
  const appId = process.env.INSTAGRAM_APP_ID?.trim();
  const appSecret = process.env.INSTAGRAM_APP_SECRET;
  const redirectUri = process.env.INSTAGRAM_REDIRECT_URI?.trim();
  const encryptionKeyValue = process.env.INSTAGRAM_TOKEN_ENCRYPTION_KEY?.trim();
  const apiVersion = process.env.INSTAGRAM_API_VERSION?.trim() || "v27.0";

  if (!appId || !appSecret || !redirectUri || !encryptionKeyValue) {
    throw new IntegrationError("Integrasi Instagram belum dikonfigurasi di environment variables server.", 503);
  }

  let redirect: URL;
  try {
    redirect = new URL(redirectUri);
  } catch {
    throw new IntegrationError("INSTAGRAM_REDIRECT_URI harus berupa URL yang valid.", 503);
  }
  if (redirect.pathname !== "/api/integrations/instagram/callback" || redirect.search || redirect.hash) {
    throw new IntegrationError("INSTAGRAM_REDIRECT_URI harus mengarah ke endpoint callback Instagram aplikasi.", 503);
  }
  if (process.env.NODE_ENV === "production" && redirect.protocol !== "https:") {
    throw new IntegrationError("INSTAGRAM_REDIRECT_URI production harus menggunakan HTTPS.", 503);
  }
  if (!/^v\d+\.\d+$/.test(apiVersion)) {
    throw new IntegrationError("INSTAGRAM_API_VERSION tidak valid.", 503);
  }

  const encryptionKey = Buffer.from(encryptionKeyValue, "base64");
  if (encryptionKey.length !== 32) {
    throw new IntegrationError("INSTAGRAM_TOKEN_ENCRYPTION_KEY harus berupa key Base64 sepanjang 32 byte.", 503);
  }

  return { appId, appSecret, redirectUri, encryptionKey, apiVersion };
}

export function isInstagramConfigured(): boolean {
  try {
    getConfig();
    return true;
  } catch (error) {
    if (error instanceof IntegrationError && error.status === 503) return false;
    throw error;
  }
}

export function instagramSettingsUrl(requestUrl: string, result: "connected" | "cancelled" | "failed"): URL {
  let origin = new URL(requestUrl).origin;
  const redirectUri = process.env.INSTAGRAM_REDIRECT_URI;
  if (redirectUri) {
    try {
      origin = new URL(redirectUri).origin;
    } catch {
      origin = new URL(requestUrl).origin;
    }
  }
  const url = new URL("/pengaturan", origin);
  url.searchParams.set("instagram", result);
  return url;
}

export function createInstagramOAuthState() {
  const state = randomBytes(32).toString("base64url");
  return { state, hash: hashInstagramOAuthState(state) };
}

export function hashInstagramOAuthState(state: string): string {
  return createHash("sha256").update(state).digest("hex");
}

export function instagramOAuthUrl(state: string): string {
  const config = getConfig();
  const url = new URL("https://www.instagram.com/oauth/authorize");
  url.searchParams.set("client_id", config.appId);
  url.searchParams.set("redirect_uri", config.redirectUri);
  url.searchParams.set("response_type", "code");
  url.searchParams.set("scope", "instagram_business_basic,instagram_business_manage_insights");
  url.searchParams.set("state", state);
  url.searchParams.set("enable_fb_login", "0");
  url.searchParams.set("force_authentication", "1");
  return url.toString();
}

export function encryptInstagramToken(token: string): string {
  const { encryptionKey } = getConfig();
  const iv = randomBytes(12);
  const cipher = createCipheriv("aes-256-gcm", encryptionKey, iv);
  const encrypted = Buffer.concat([cipher.update(token, "utf8"), cipher.final()]);
  return [
    "v1",
    iv.toString("base64"),
    cipher.getAuthTag().toString("base64"),
    encrypted.toString("base64"),
  ].join(":");
}

export function decryptInstagramToken(value: string): string {
  const { encryptionKey } = getConfig();
  const [version, ivValue, tagValue, encryptedValue] = value.split(":");
  if (version !== "v1" || !ivValue || !tagValue || !encryptedValue) {
    throw new IntegrationError("Token Instagram tersimpan dalam format yang tidak valid. Hubungkan ulang akun.", 409);
  }

  try {
    const decipher = createDecipheriv("aes-256-gcm", encryptionKey, Buffer.from(ivValue, "base64"));
    decipher.setAuthTag(Buffer.from(tagValue, "base64"));
    return Buffer.concat([
      decipher.update(Buffer.from(encryptedValue, "base64")),
      decipher.final(),
    ]).toString("utf8");
  } catch {
    throw new IntegrationError("Token Instagram tidak dapat dibuka. Periksa encryption key atau hubungkan ulang akun.", 503);
  }
}

async function readInstagramJson(response: Response, description: string): Promise<Record<string, unknown>> {
  let payload: unknown;
  try {
    payload = await response.json();
  } catch {
    throw new IntegrationError(`Instagram mengirim respons tidak valid saat ${description}.`, 502);
  }

  if (!isRecord(payload)) {
    throw new IntegrationError(`Instagram mengirim respons tidak valid saat ${description}.`, 502);
  }
  if (!response.ok) {
    const error = isRecord(payload.error) ? payload.error : payload;
    const message = typeof error.message === "string"
      ? error.message
      : typeof error.error_message === "string" ? error.error_message : "Permintaan ditolak.";
    throw new IntegrationError(`Instagram: ${message}`, 502);
  }
  return payload;
}

function tokenFromResponse(payload: Record<string, unknown>, description: string): InstagramToken {
  const data = Array.isArray(payload.data) && isRecord(payload.data[0]) ? payload.data[0] : payload;
  if (!isRecord(data) || typeof data.access_token !== "string" || !data.access_token) {
    throw new IntegrationError(`Instagram tidak memberikan token saat ${description}.`, 502);
  }
  const expiresIn = Number(data.expires_in);
  if (!Number.isFinite(expiresIn) || expiresIn <= 0) {
    throw new IntegrationError(`Masa berlaku token dari Instagram tidak valid saat ${description}.`, 502);
  }
  return { accessToken: data.access_token, expiresIn };
}

function accessTokenFromResponse(payload: Record<string, unknown>, description: string): string {
  const data = Array.isArray(payload.data) && isRecord(payload.data[0]) ? payload.data[0] : payload;
  if (!isRecord(data) || typeof data.access_token !== "string" || !data.access_token) {
    throw new IntegrationError(`Instagram tidak memberikan token saat ${description}.`, 502);
  }
  return data.access_token;
}

export async function exchangeInstagramCode(code: string): Promise<InstagramToken> {
  const config = getConfig();
  const body = new FormData();
  body.set("client_id", config.appId);
  body.set("client_secret", config.appSecret);
  body.set("grant_type", "authorization_code");
  body.set("redirect_uri", config.redirectUri);
  body.set("code", code);

  const response = await fetch("https://api.instagram.com/oauth/access_token", {
    method: "POST",
    body,
    cache: "no-store",
  });
  const shortLivedToken = accessTokenFromResponse(
    await readInstagramJson(response, "menukar authorization code"),
    "menukar authorization code",
  );

  const url = new URL("https://graph.instagram.com/access_token");
  url.searchParams.set("grant_type", "ig_exchange_token");
  url.searchParams.set("client_secret", config.appSecret);
  url.searchParams.set("access_token", shortLivedToken);
  const longLivedResponse = await fetch(url, { cache: "no-store" });
  return tokenFromResponse(
    await readInstagramJson(longLivedResponse, "membuat token jangka panjang"),
    "membuat token jangka panjang",
  );
}

export async function refreshInstagramToken(token: string): Promise<InstagramToken> {
  const url = new URL("https://graph.instagram.com/refresh_access_token");
  url.searchParams.set("grant_type", "ig_refresh_token");
  url.searchParams.set("access_token", token);
  const response = await fetch(url, { cache: "no-store" });
  return tokenFromResponse(
    await readInstagramJson(response, "memperbarui token"),
    "memperbarui token",
  );
}

async function fetchGraph(
  path: string,
  token: string,
  description: string,
  params: Record<string, string> = {},
): Promise<Record<string, unknown>> {
  const { apiVersion } = getConfig();
  const url = new URL(`https://graph.instagram.com/${apiVersion}/${path}`);
  url.searchParams.set("access_token", token);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);

  let response: Response;
  try {
    response = await fetch(url, { cache: "no-store" });
  } catch {
    throw new IntegrationError("Tidak dapat menghubungi Instagram. Periksa koneksi server lalu coba lagi.", 502);
  }
  return readInstagramJson(response, description);
}

function nullableNumber(value: unknown): number | null {
  if (typeof value !== "number" && typeof value !== "string") return null;
  const result = Number(value);
  return Number.isFinite(result) ? result : null;
}

export async function fetchInstagramProfile(token: string): Promise<InstagramProfile> {
  const payload = await fetchGraph(
    "me",
    token,
    "membaca profil akun",
    { fields: "user_id,username,account_type,followers_count,media_count" },
  );
  const rawUserId = payload.user_id ?? payload.id;
  const instagramUserId = typeof rawUserId === "string" || typeof rawUserId === "number" ? String(rawUserId) : "";
  if (!instagramUserId || typeof payload.username !== "string") {
    throw new IntegrationError("Profil Instagram tidak berisi ID akun dan username.", 502);
  }
  const accountType = typeof payload.account_type === "string" ? payload.account_type : null;
  if (accountType && !["business", "media_creator", "creator"].includes(accountType.toLowerCase())) {
    throw new IntegrationError("Insight hanya tersedia untuk akun Instagram Professional (Business atau Creator).", 409);
  }

  return {
    instagramUserId,
    username: payload.username,
    accountType,
    followers: nullableNumber(payload.followers_count),
    mediaCount: nullableNumber(payload.media_count),
  };
}

function latestMetricValue(metric: unknown): { value: number | null; endTime: string | null } {
  if (!isRecord(metric)) return { value: null, endTime: null };
  if (isRecord(metric.total_value)) {
    return {
      value: nullableNumber(metric.total_value.value),
      endTime: typeof metric.total_value.end_time === "string" ? metric.total_value.end_time : null,
    };
  }
  if (!Array.isArray(metric.values)) return { value: null, endTime: null };
  const values = metric.values.filter(isRecord);
  const latest = values.reduce<Record<string, unknown> | null>((current, item) => {
    if (!current) return item;
    const itemTime = typeof item.end_time === "string" ? Date.parse(item.end_time) : 0;
    const currentTime = typeof current.end_time === "string" ? Date.parse(current.end_time) : 0;
    return itemTime > currentTime ? item : current;
  }, null);
  if (!latest) return { value: null, endTime: null };
  return {
    value: nullableNumber(latest.value),
    endTime: typeof latest.end_time === "string" ? latest.end_time : null,
  };
}

export async function fetchInstagramInsights(
  instagramUserId: string,
  token: string,
): Promise<InstagramInsights> {
  const payload = await fetchGraph(
    `${encodeURIComponent(instagramUserId)}/insights`,
    token,
    "membaca insight akun",
    { metric: insightMetrics.join(","), period: "day", metric_type: "total_value" },
  );
  const metrics = Array.isArray(payload.data) ? payload.data : [];
  const byName = new Map(
    metrics.filter(isRecord).filter((metric) => typeof metric.name === "string").map((metric) => [metric.name as string, metric]),
  );
  const reach = latestMetricValue(byName.get("reach"));
  const accountsEngaged = latestMetricValue(byName.get("accounts_engaged"));
  const totalInteractions = latestMetricValue(byName.get("total_interactions"));
  const periodEnd = [reach, accountsEngaged, totalInteractions]
    .map((metric) => metric.endTime)
    .filter((value): value is string => value !== null)
    .sort()
    .at(-1) ?? null;

  return {
    reach: reach.value,
    accountsEngaged: accountsEngaged.value,
    totalInteractions: totalInteractions.value,
    periodEnd,
  };
}
