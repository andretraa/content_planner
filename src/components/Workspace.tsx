"use client";

import Image from "next/image";
import Link from "next/link";
import { ChangeEvent, FormEvent, useCallback, useEffect, useMemo, useRef, useState, useSyncExternalStore } from "react";
import type { ReactNode } from "react";
import AuthGate from "@/components/AuthGate";
import { createSupabaseBrowserClient } from "@/lib/supabase-client";

type Page = "reports" | "planner" | "ideas" | "settings";
type RecordItem = Record<string, string | number | null | undefined> & { id: string | number };
type IdeaColumn = "ideas" | "in_progress" | "ready_to_shoot" | "completed";
type TargetPlatform = "all" | "instagram" | "tiktok" | "threads" | "youtube";
type ModalState = { type: "report" | "schedule" | "idea"; item?: RecordItem; column?: IdeaColumn; date?: string } | null;
type Collection = "reports" | "schedules" | "ideas";
type InstagramConnection = {
  username: string;
  accountType: string | null;
  profile: { followers: number | null; mediaCount: number | null };
  insights: {
    reach: number | null;
    accountsEngaged: number | null;
    totalInteractions: number | null;
    periodEnd: string | null;
  };
  lastSyncedAt: string | null;
  tokenExpiresAt: string | null;
};
type ConfirmationState =
  | { type: "save"; collection: Collection; id: string | number; payload: Record<string, FormDataEntryValue>; title: string }
  | { type: "delete"; collection: Collection; item: RecordItem }
  | null;
const navigation: { id: Page; label: string; href: string; hint: string }[] = [
  { id: "reports", label: "Laporan Sosmed", href: "/laporan-sosmed", hint: "Analitik & performa" },
  { id: "planner", label: "Content Planner", href: "/content-planner", hint: "Kalender publikasi" },
  { id: "ideas", label: "Ide Konten", href: "/ide-konten", hint: "Papan inspirasi" },
  { id: "settings", label: "Pengaturan", href: "/pengaturan", hint: "Preferensi workspace" },
];

const platforms = ["Instagram", "TikTok", "Threads", "YouTube"];
const targetPlatforms: { id: TargetPlatform; label: string }[] = [
  { id: "all", label: "Semua platform" },
  { id: "instagram", label: "Instagram" },
  { id: "tiktok", label: "TikTok" },
  { id: "threads", label: "Threads" },
  { id: "youtube", label: "YouTube" },
];
const reportsPerPage = 7;
const platformLabels: Record<string, string> = {
  instagram: "Instagram",
  tiktok: "TikTok",
  threads: "Threads",
  youtube: "YouTube",
};
const columns: { id: IdeaColumn; label: string; color: string }[] = [
  { id: "ideas", label: "Ide baru", color: "lavender" },
  { id: "in_progress", label: "Sedang dibuat", color: "blue" },
  { id: "ready_to_shoot", label: "Siap tayang", color: "amber" },
  { id: "completed", label: "Selesai", color: "green" },
];
const emptyReport = {
  title: "",
  description: "",
  platform: "Instagram",
  tag: "Reels",
  followers: "",
  tayangan: "",
  jangkauan: "",
  engagement: "",
  date: new Date().toISOString().slice(0, 10),
  period: "mingguan",
};
const emptySchedule = {
  title: "",
  description: "",
  platform: "Instagram",
  tag: "Reels",
  date: new Date().toISOString().slice(0, 10),
  time: "14:00",
  status: "scheduled",
};
const emptyIdea = { title: "", description: "", platform: "Instagram", category: "", column: "ideas" };

function platformLabel(value: unknown) {
  const normalized = String(value ?? "Instagram").trim().toLowerCase();
  return platformLabels[normalized] ?? normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function linkifyDescription(description: string): ReactNode {
  const nodes: ReactNode[] = [];
  const pattern = /https?:\/\/[^\s<>"']+/gi;
  let previousIndex = 0;
  let match: RegExpExecArray | null;

  while ((match = pattern.exec(description)) !== null) {
    const rawUrl = match[0];
    const url = rawUrl.replace(/[.,!?;:)\]}]+$/g, "");
    const start = match.index;
    if (start > previousIndex) nodes.push(description.slice(previousIndex, start));
    if (url) {
      nodes.push(<a key={start} href={url} target="_blank" rel="noopener noreferrer">{url}</a>);
      if (url.length < rawUrl.length) nodes.push(rawUrl.slice(url.length));
    } else {
      nodes.push(rawUrl);
    }
    previousIndex = start + rawUrl.length;
  }

  if (previousIndex < description.length) nodes.push(description.slice(previousIndex));
  return nodes;
}

function formatNumber(value: unknown) {
  const number = Number.parseFloat(String(value ?? "").replace(/[^\d.-]/g, ""));
  return Number.isFinite(number) ? new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(number) : "—";
}

function formatDate(value: unknown, options?: Intl.DateTimeFormatOptions) {
  if (!value) return "Tanpa tanggal";
  const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
  if (Number.isNaN(date.getTime())) return String(value);
  return new Intl.DateTimeFormat("id-ID", options ?? { day: "numeric", month: "short", year: "numeric" }).format(date);
}

function dateKey(date: Date) {
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
  return local.toISOString().slice(0, 10);
}

function currentWeekRange(referenceDate: Date) {
  const start = new Date(referenceDate.getFullYear(), referenceDate.getMonth(), referenceDate.getDate());
  start.setDate(start.getDate() - ((start.getDay() + 6) % 7));
  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  return { start: dateKey(start), end: dateKey(end) };
}

function isReportInPeriod(report: RecordItem, period: string, weekStart: string, weekEnd: string, month: string) {
  if (period === "semua") return true;
  const uploadDate = String(report.date ?? "").slice(0, 10);
  if (period === "bulanan") return uploadDate.slice(0, 7) === month;
  return uploadDate >= weekStart && uploadDate <= weekEnd;
}

async function readApiResponse(response: Response, fallback: string): Promise<Record<string, unknown>> {
  const body = await response.text();
  let result: unknown;

  if (body) {
    try {
      result = JSON.parse(body);
    } catch {
      throw new Error(`Server mengirim respons yang tidak valid (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
    }
  } else {
    throw new Error(`Server tidak mengirim respons (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
  }

  if (!result || typeof result !== "object" || Array.isArray(result)) {
    throw new Error(`Format respons server tidak valid (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
  }

  const payload = result as Record<string, unknown>;
  if (!response.ok) {
    throw new Error(typeof payload.message === "string" ? payload.message : `${fallback} (HTTP ${response.status}).`);
  }

  return payload;
}

function platformClass(value: unknown) {
  return `platform platform-${String(value ?? "instagram").toLowerCase()}`;
}

function subscribeToSettings(callback: () => void, key: string) {
  const listener = (event: StorageEvent) => {
    if (event.key === key || event.key === null) callback();
  };
  window.addEventListener("storage", listener);
  return () => window.removeEventListener("storage", listener);
}

function getSettingsSnapshot(key: string) {
  return window.localStorage.getItem(key) ?? "{}";
}

function parseSettings(snapshot: string): Record<string, string> {
  try {
    const saved: unknown = JSON.parse(snapshot);
    if (saved && typeof saved === "object" && !Array.isArray(saved)) {
      return Object.fromEntries(Object.entries(saved).filter((entry): entry is [string, string] => typeof entry[1] === "string"));
    }
  } catch {
    return {};
  }
  return {};
}

function isObjectRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

function nullableMetric(value: unknown): number | null {
  if (typeof value !== "number" && typeof value !== "string") return null;
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseInstagramConnection(value: unknown): InstagramConnection | null {
  if (!isObjectRecord(value) || typeof value.username !== "string") return null;
  const profile = isObjectRecord(value.profile) ? value.profile : {};
  const insights = isObjectRecord(value.insights) ? value.insights : {};
  return {
    username: value.username,
    accountType: typeof value.accountType === "string" ? value.accountType : null,
    profile: {
      followers: nullableMetric(profile.followers),
      mediaCount: nullableMetric(profile.mediaCount),
    },
    insights: {
      reach: nullableMetric(insights.reach),
      accountsEngaged: nullableMetric(insights.accountsEngaged),
      totalInteractions: nullableMetric(insights.totalInteractions),
      periodEnd: typeof insights.periodEnd === "string" ? insights.periodEnd : null,
    },
    lastSyncedAt: typeof value.lastSyncedAt === "string" ? value.lastSyncedAt : null,
    tokenExpiresAt: typeof value.tokenExpiresAt === "string" ? value.tokenExpiresAt : null,
  };
}

async function getInstagramStatus(accessToken: string) {
  const response = await fetch("/api/integrations/instagram", {
    cache: "no-store",
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  const payload = await readApiResponse(response, "Status Instagram tidak dapat dimuat.");
  return {
    configured: payload.configured === true,
    connected: payload.connected === true,
    connection: parseInstagramConnection(payload.data),
  };
}

async function syncInstagram(accessToken: string): Promise<InstagramConnection> {
  const response = await fetch("/api/integrations/instagram/sync", {
    method: "POST",
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  const payload = await readApiResponse(response, "Insight Instagram tidak dapat disinkronkan.");
  const connection = parseInstagramConnection(payload.data);
  if (!connection) throw new Error("Format insight Instagram dari server tidak valid.");
  return connection;
}

function getProfileImage(value?: string) {
  return value?.startsWith("data:image/jpeg;base64,") ? value : "";
}

function resizeProfileImage(file: File): Promise<string> {
  if (!["image/jpeg", "image/png", "image/webp", "image/gif"].includes(file.type)) {
    return Promise.reject(new Error("Gunakan foto berformat JPEG, PNG, WebP, atau GIF."));
  }
  if (file.size > 10 * 1024 * 1024) return Promise.reject(new Error("Ukuran foto maksimal 10 MB."));

  return new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new window.Image();
    image.onload = () => {
      try {
        const scale = Math.min(1, 512 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement("canvas");
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Foto tidak dapat diproses di browser ini.");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL("image/jpeg", 0.82);
        if (dataUrl.length > 1_500_000) throw new Error("Ukuran foto terlalu besar setelah diproses. Pilih foto lain.");
        resolve(dataUrl);
      } catch (error) {
        reject(error instanceof Error ? error : new Error("Foto tidak dapat diproses."));
      } finally {
        URL.revokeObjectURL(objectUrl);
      }
    };
    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error("Foto tidak dapat dibaca. Pilih file gambar lain."));
    };
    image.src = objectUrl;
  });
}

function WorkspaceAvatar({ image, initial, className }: { image: string; initial: string; className: string }) {
  return (
    <span className={className}>
      {image ? <Image className="avatar-photo" src={image} alt="" width={48} height={48} unoptimized /> : initial}
    </span>
  );
}

function PlatformIcon({ platform }: { platform: string }) {
  const name = platform.toLowerCase();
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" className="platform-icon">
      {name === "instagram" && (
        <>
          <rect x="3.5" y="3.5" width="17" height="17" rx="5" />
          <circle cx="12" cy="12" r="4" />
          <circle cx="17.5" cy="6.8" r=".8" className="platform-icon-dot" />
        </>
      )}
      {name === "tiktok" && (
        <>
          <path d="M14.4 4v10.1a4.2 4.2 0 1 1-3.5-4.1" />
          <path d="M14.4 4c.5 2.5 2.1 4 4.6 4.4" />
        </>
      )}
      {name === "threads" && (
        <>
          <path d="M17.3 8.6c-.8-2.2-2.5-3.4-5.2-3.4-3.7 0-5.9 2.5-5.9 6.8 0 4.2 2.1 6.9 5.7 6.9 3.2 0 5.1-1.7 5.1-4.4 0-2.3-1.4-3.7-3.8-3.7-1.8 0-3 .8-3.4 2.2" />
          <path d="M11 12.6c4.5-1.2 7.4.3 7.4 3.2 0 1.9-1.4 3.1-3.6 3.1" />
        </>
      )}
      {name === "youtube" && (
        <>
          <rect x="3" y="5.5" width="18" height="13" rx="3.5" />
          <path d="m10 9 5.5 3-5.5 3Z" className="platform-icon-play" />
        </>
      )}
    </svg>
  );
}

function Icon({ name }: { name: string }) {
  const paths: Record<string, string> = {
    spark: "M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z",
    plus: "M12 5v14M5 12h14",
    arrow: "M5 12h14m-6-6 6 6-6 6",
    calendar: "M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z",
    chart: "M4 19V5m0 14h16M8 15v-4m4 4V7m4 8v-6",
    search: "m20 20-4.2-4.2M18 10.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z",
    download: "M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3",
    refresh: "M20 7v5h-5M4 17v-5h5m-3.1-3A7 7 0 0 1 18 7l2 5M4 12l2 5a7 7 0 0 0 12.1-2",
    edit: "m15 5 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z",
    trash: "M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3",
    close: "m6 6 12 12M18 6 6 18",
    clock: "M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z",
    reports: "M4 19V5m0 14h16M8 15v-4m4 4V7m4 8v-6",
    planner: "M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z",
    ideas: "M9 18h6m-5 3h4m-5.3-6.1A6 6 0 1 1 15.3 14.9c-.8.6-1.3 1.4-1.3 2.1h-4c0-.7-.5-1.5-1.3-2.1Z",
    settings: "M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm0-5v2m0 13v2m9-8h-2m-14 0H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64",
    layers: "m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5",
    users: "M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.5-6.8a3.5 3.5 0 0 1 0 6.6M17 14.5h.5a4 4 0 0 1 4 4V20",
    eye: "M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Zm9.5-3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z",
    heart: "M20.8 8.8c0 5.2-8.8 10.1-8.8 10.1S3.2 14 3.2 8.8A4.3 4.3 0 0 1 12 6.9a4.3 4.3 0 0 1 8.8 1.9Z",
  };
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="icon">
      <path d={paths[name] ?? paths.spark} />
    </svg>
  );
}

export default function Workspace({ page }: { page: Page }) {
  const [authClient] = useState(createSupabaseBrowserClient);
  const [authLoading, setAuthLoading] = useState(Boolean(authClient));
  const [accessToken, setAccessToken] = useState<string | null>(null);
  const [userId, setUserId] = useState("");
  const [userEmail, setUserEmail] = useState("");
  const activeToken = useRef<string | null>(null);
  const [reports, setReports] = useState<RecordItem[]>([]);
  const [schedules, setSchedules] = useState<RecordItem[]>([]);
  const [ideas, setIdeas] = useState<RecordItem[]>([]);
  const [goalMonth, setGoalMonth] = useState(() => dateKey(new Date()).slice(0, 7));
  const [goalPlatform, setGoalPlatform] = useState<TargetPlatform>("all");
  const [monthlyTarget, setMonthlyTarget] = useState<number | null>(null);
  const [monthlyTargetLoading, setMonthlyTargetLoading] = useState(true);
  const monthlyCountSnapshot = useRef<{ month: string; platform: TargetPlatform; count: number; target: number | null } | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [syncingData, setSyncingData] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [modal, setModal] = useState<ModalState>(null);
  const [confirmation, setConfirmation] = useState<ConfirmationState>(null);
  const monthlyGoalReached = useRef(false);
  const [query, setQuery] = useState("");
  const [platformFilter, setPlatformFilter] = useState("Semua platform");
  const [period, setPeriod] = useState("mingguan");
  const [calendarView, setCalendarView] = useState<"month" | "week" | "list">("month");
  const [calendarDate, setCalendarDate] = useState(() => new Date());
  const settingsKey = `planning-kundrew-settings:${userId || "anonymous"}`;
  const settingsSnapshot = useSyncExternalStore(
    (callback) => subscribeToSettings(callback, settingsKey),
    () => getSettingsSnapshot(settingsKey),
    () => "{}",
  );
  const settings = useMemo(() => parseSettings(settingsSnapshot), [settingsSnapshot]);
  const workspaceName = settings.workspaceName?.trim() || "Kundrew Studio";
  const workspaceInitial = workspaceName.charAt(0).toUpperCase() || "K";
  const profileImage = getProfileImage(settings.profileImage);
  const username = (settings.username?.trim() || userEmail.split("@")[0]).replace(/^@+/, "");

  useEffect(() => {
    if (!authClient) return;

    let active = true;
    const syncSession = (session: Awaited<ReturnType<typeof authClient.auth.getSession>>["data"]["session"]) => {
      const token = session?.access_token ?? null;
      activeToken.current = token;
      setReports([]);
      setSchedules([]);
      setIdeas([]);
      monthlyCountSnapshot.current = null;
      monthlyGoalReached.current = false;
      setMonthlyTarget(null);
      setMonthlyTargetLoading(true);
      setLoading(Boolean(token));
      if (token) setError("");
      setAccessToken(session?.access_token ?? null);
      setUserId(session?.user.id ?? "");
      setUserEmail(session?.user.email ?? "");
      setAuthLoading(false);
    };
    const { data: { subscription } } = authClient.auth.onAuthStateChange((_event, session) => {
      syncSession(session);
    });
    void authClient.auth.getSession().then(({ data, error: sessionError }) => {
      if (!active) return;
      if (sessionError) setError(sessionError.message);
      syncSession(data.session);
    }).catch((sessionError: unknown) => {
      if (!active) return;
      setError(sessionError instanceof Error ? sessionError.message : "Sesi akun tidak dapat diperiksa.");
      setAuthLoading(false);
    });

    return () => {
      active = false;
      subscription.unsubscribe();
    };
  }, [authClient]);

  const loadData = useCallback(async (): Promise<boolean> => {
    if (!accessToken) return false;
    try {
      const endpoints = [
        "/api/reports",
        "/api/schedules",
        "/api/ideas",
        `/api/monthly-target?month=${encodeURIComponent(goalMonth)}&platform=${encodeURIComponent(goalPlatform)}`,
      ];
      const responses = await Promise.all(endpoints.map((endpoint) => fetch(endpoint, {
        cache: "no-store",
        headers: { Authorization: `Bearer ${accessToken}` },
      })));
      const payloads = await Promise.all(responses.map(async (response) => {
          const json = await readApiResponse(response, "Data tidak dapat dimuat.");
          return json;
        }));
      if (activeToken.current !== accessToken) return false;
      const [reportData, scheduleData, ideaData, targetData] = payloads.map((payload) => payload.data);
      if (!Array.isArray(reportData) || !Array.isArray(scheduleData) || !Array.isArray(ideaData)) {
        throw new Error("Format data dari server tidak valid.");
      }
      if (!isObjectRecord(targetData) || targetData.month !== goalMonth || targetData.platform !== goalPlatform) {
        throw new Error("Format target bulanan dari server tidak valid.");
      }
      const targetCount = targetData.targetCount;
      if (targetCount !== null && (typeof targetCount !== "number" || !Number.isSafeInteger(targetCount))) {
        throw new Error("Nilai target bulanan dari server tidak valid.");
      }
      const monthlyReports = (reportData as RecordItem[]).filter((report) =>
        String(report.date ?? "").slice(0, 7) === goalMonth
        && (goalPlatform === "all" || String(report.platform ?? "").toLowerCase() === goalPlatform),
      );
      const previousSnapshot = monthlyCountSnapshot.current;
      if (
        previousSnapshot?.month === goalMonth
        && previousSnapshot.platform === goalPlatform
        && previousSnapshot.target !== null
        && previousSnapshot.target === targetCount
        && previousSnapshot.count < targetCount
        && monthlyReports.length >= targetCount
      ) {
        monthlyGoalReached.current = true;
        const platformLabel = targetPlatforms.find((platform) => platform.id === goalPlatform)?.label ?? "Semua platform";
        const monthLabel = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric" })
          .format(new Date(`${goalMonth}-01T00:00:00`));
        setNotice(`Target ${platformLabel} tercapai! ${monthlyReports.length} konten sudah tercatat untuk ${monthLabel}.`);
      }
      monthlyCountSnapshot.current = { month: goalMonth, platform: goalPlatform, count: monthlyReports.length, target: targetCount };
      setReports(reportData as RecordItem[]);
      setSchedules(scheduleData as RecordItem[]);
      setIdeas(ideaData as RecordItem[]);
      setMonthlyTarget(targetCount);
      return true;
    } catch (loadError) {
      if (activeToken.current !== accessToken) return false;
      setError(loadError instanceof Error ? loadError.message : "Data tidak dapat dimuat.");
      return false;
    } finally {
      if (activeToken.current === accessToken) {
        setLoading(false);
        setMonthlyTargetLoading(false);
      }
    }
  }, [accessToken, goalMonth, goalPlatform]);

  useEffect(() => {
    if (!authLoading && accessToken) void Promise.resolve().then(loadData);
  }, [accessToken, authLoading, loadData]);

  useEffect(() => {
    if (!notice) return;
    const timeout = window.setTimeout(() => setNotice(""), 3200);
    return () => window.clearTimeout(timeout);
  }, [notice]);

  const callApi = async (url: string, method: string, body?: unknown) => {
    const response = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${accessToken}`,
        ...(body === undefined ? {} : { "Content-Type": "application/json" }),
      },
      ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    });
    const result = await readApiResponse(response, "Permintaan gagal.");
    return result;
  };

  const setActionNotice = (message: string) => {
    if (monthlyGoalReached.current) {
      monthlyGoalReached.current = false;
      return;
    }
    setNotice(message);
  };

  const syncData = () => {
    setSyncingData(true);
    void runAction(async () => {
      const loaded = await loadData();
      if (loaded) setActionNotice("Data terbaru berhasil disinkronkan.");
    }).finally(() => setSyncingData(false));
  };

  const saveMonthlyTarget = async (targetCount: number) => {
    await runAction(async () => {
      await callApi("/api/monthly-target", "PUT", { month: goalMonth, platform: goalPlatform, targetCount });
      await loadData();
      setActionNotice("Target bulanan berhasil disimpan.");
    });
  };

  const addScheduleToReports = async (scheduleId: string | number) => {
    await callApi(`/api/reports/from-schedule/${scheduleId}`, "POST", {});
  };

  const exportCsv = () => {
    void runAction(async () => {
      const params = new URLSearchParams();
      if (platformFilter !== "Semua platform") params.set("platform", platformFilter);
      const queryString = params.toString();
      const response = await fetch(`/api/reports/export${queryString ? `?${queryString}` : ""}`, {
        headers: { Authorization: `Bearer ${accessToken}` },
      });
      if (!response.ok) {
        await readApiResponse(response, "Laporan tidak dapat diekspor.");
      }
      const blob = await response.blob();
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = objectUrl;
      const platformSuffix = platformFilter === "Semua platform" ? "" : `-${platformFilter.toLowerCase()}`;
      link.download = `laporan-sosmed${platformSuffix}-${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    });
  };

  const runAction = async (action: () => Promise<void>) => {
    setBusy(true);
    setError("");
    try {
      await action();
    } catch (actionError) {
      setError(actionError instanceof Error ? actionError.message : "Terjadi kesalahan. Silakan coba lagi.");
    } finally {
      setBusy(false);
    }
  };

  const signOut = async () => {
    if (!authClient) return;
    try {
      const { error: signOutError } = await authClient.auth.signOut();
      if (signOutError) setError(signOutError.message);
    } catch (signOutError) {
      setError(signOutError instanceof Error ? signOutError.message : "Tidak dapat keluar dari akun.");
    }
  };

  const saveRecord = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!modal) return;
    const form = new FormData(event.currentTarget);
    const fields = Object.fromEntries(form.entries());
    const collection = modal.type === "report" ? "reports" : modal.type === "schedule" ? "schedules" : "ideas";
    const payload = {
      ...fields,
      ...(modal.type === "idea" ? { column: fields.column || modal.column || "ideas" } : {}),
    };

    if (modal.item) {
      setConfirmation({ type: "save", collection, id: modal.item.id, payload, title: String(modal.item.title ?? "") });
      return;
    }

    await runAction(async () => {
      const result = await callApi(`/api/${collection}`, "POST", payload);
      if (collection === "schedules" && fields.status === "published") {
        const created = result.data;
        if (!created || typeof created !== "object" || Array.isArray(created) || !("id" in created)) {
          throw new Error("Jadwal berhasil dibuat, tetapi data laporan tidak dapat disiapkan.");
        }
        const scheduleId = created.id;
        if (typeof scheduleId !== "string" && typeof scheduleId !== "number") {
          throw new Error("Jadwal berhasil dibuat, tetapi ID jadwal tidak valid untuk laporan.");
        }
        await addScheduleToReports(scheduleId);
      }
      await loadData();
      setModal(null);
      setActionNotice("Perubahan berhasil disimpan.");
    });
  };

  const removeRecord = (collection: Collection, item: RecordItem) => {
    setConfirmation({ type: "delete", collection, item });
  };

  const confirmAction = async () => {
    if (!confirmation) return;
    const action = confirmation;
    await runAction(async () => {
      if (action.type === "delete") {
        await callApi(`/api/${action.collection}/${action.item.id}`, "DELETE");
        await loadData();
        setActionNotice("Data berhasil dihapus.");
        return;
      }

      await callApi(`/api/${action.collection}/${action.id}`, "PUT", action.payload);
      if (action.collection === "schedules" && action.payload.status === "published") {
        await addScheduleToReports(action.id);
      }
      await loadData();
      setModal(null);
      setActionNotice("Perubahan berhasil disimpan.");
    });
    setConfirmation(null);
  };

  const publishSchedule = (schedule: RecordItem) => {
    void runAction(async () => {
      await addScheduleToReports(schedule.id);
      await callApi(`/api/schedules/${schedule.id}`, "PUT", { ...schedule, status: "published" });
      await loadData();
      setActionNotice("Jadwal diterbitkan dan otomatis ditambahkan ke laporan.");
    });
  };

  const syncSchedule = (schedule: RecordItem) => {
    void runAction(async () => {
      await addScheduleToReports(schedule.id);
      await loadData();
      setActionNotice("Jadwal berhasil disinkronkan ke laporan.");
    });
  };

  const moveIdea = (idea: RecordItem, column: IdeaColumn) => {
    void runAction(async () => {
      await callApi(`/api/ideas/${idea.id}`, "PUT", { ...idea, column });
      await loadData();
    });
  };

  const sendIdeaToPlanner = (idea: RecordItem) => {
    void runAction(async () => {
      await callApi(`/api/ideas/${idea.id}/send-to-planner`, "POST", {});
      await loadData();
      setActionNotice("Ide berhasil ditambahkan ke Content Planner.");
    });
  };

  const syncIdea = (idea: RecordItem) => {
    void runAction(async () => {
      await callApi(`/api/reports/from-idea/${idea.id}`, "POST", {});
      await loadData();
      setActionNotice("Ide berhasil disinkronkan ke laporan.");
    });
  };

  const today = new Date();
  const { start: weekStart, end: weekEnd } = currentWeekRange(today);
  const currentMonth = dateKey(today).slice(0, 7);
  const filteredReports = useMemo(() => {
    const normalized = query.toLowerCase();
    return [...reports]
      .sort((a, b) => String(b.date ?? "").localeCompare(String(a.date ?? "")) || Number(b.id) - Number(a.id))
      .filter((report) => (platformFilter === "Semua platform" || platformLabel(report.platform) === platformFilter))
      .filter((report) => `${report.title} ${report.description} ${report.platform} ${report.tag}`.toLowerCase().includes(normalized))
      .filter((report) => isReportInPeriod(report, period, weekStart, weekEnd, currentMonth));
  }, [currentMonth, period, platformFilter, query, reports, weekEnd, weekStart]);

  const active = navigation.find((item) => item.id === page)!;
  const insightReports = platformFilter === "Semua platform"
    ? reports
    : reports.filter((report) => platformLabel(report.platform) === platformFilter);
  const totalFollowers = insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.followers ?? "").replace(/[^\d.-]/g, "")) || 0), 0);
  const totalViews = insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.tayangan ?? "").replace(/[^\d.-]/g, "")) || 0), 0);
  const averageEngagement = insightReports.length
    ? insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.engagement ?? "").replace(/[^\d.-]/g, "")) || 0), 0) / insightReports.length
    : 0;
  const saveSettings = (event: FormEvent<HTMLFormElement>, profileImage: string) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const workspaceName = formData.get("workspaceName");
    const enteredUsername = formData.get("username");
    const normalizedName = typeof workspaceName === "string" ? workspaceName.trim() : "";
    const normalizedUsername = typeof enteredUsername === "string" ? enteredUsername.trim().replace(/^@+/, "") : "";
    if (!normalizedName) {
      setError("Nama workspace wajib diisi.");
      return;
    }
    if (!normalizedUsername) {
      setError("Username wajib diisi.");
      return;
    }
    const snapshot = JSON.stringify({ username: normalizedUsername, workspaceName: normalizedName, profileImage });
    try {
      window.localStorage.setItem(settingsKey, snapshot);
      window.dispatchEvent(new StorageEvent("storage", { key: settingsKey, newValue: snapshot }));
      setNotice("Pengaturan berhasil disimpan di browser ini.");
    } catch {
      setError("Preferensi tidak dapat disimpan di browser ini. Coba pilih foto yang lebih kecil.");
    }
  };

  if (authLoading || !accessToken) return <AuthGate client={authClient} loading={authLoading} />;

  return (
    <div className="workspace">
      <aside className="sidebar">
        <Link className="brand" href="/">
          <span className="brand-mark"><Icon name="spark" /></span>
          <span><span className="brand-kicker">CONTENT</span><strong>PLANNING</strong></span>
        </Link>
        <div className="workspace-switcher">
          <WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="workspace-avatar" />
          <span className="workspace-name"><strong>{workspaceName}</strong><small>Workspace pribadi</small></span>
        </div>
        <p className="nav-caption">WORKSPACE</p>
        <nav className="side-nav" aria-label="Navigasi utama">
          {navigation.map((item) => (
            <Link key={item.id} href={item.href} className={`nav-link ${page === item.id ? "active" : ""}`}>
              <span className="nav-icon"><Icon name={item.id} /></span>
              <span className="nav-copy"><strong>{item.label}</strong><small>{item.hint}</small></span>
              {page === item.id && <span className="nav-indicator" />}
            </Link>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <div className="sidebar-tip">
            <span className="tip-icon"><Icon name="spark" /></span>
            <strong>Ide bagus layak terwujud.</strong>
            <p>Mulai dari inspirasi, lanjutkan dengan rencana, ukur dampaknya.</p>
            <Link href="/ide-konten">Buka papan ide <Icon name="arrow" /></Link>
          </div>
        </div>
      </aside>

      <main className="main-content">
        <header className="topbar">
          <div className="breadcrumb"><span>{workspaceName}</span><span className="crumb-slash">/</span><strong>{active.label}</strong></div>
          <div className="topbar-actions"><span className="today-label">{formatDate(dateKey(new Date()), { weekday: "short", day: "numeric", month: "short" })}</span>{username && <span className="today-label">{username}</span>}<WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="top-avatar" /><button className="button button-quiet" type="button" onClick={() => { void signOut(); }}>Keluar</button></div>
        </header>
        <div className="page-wrap">
          {error && <div className="alert alert-error" role="alert"><span>{error}</span><button type="button"           onClick={() => { setError(""); void loadData(); }}>Coba lagi</button></div>}
          {loading && <div className="loading-banner"><span className="spinner" /> Menghubungkan ke workspace...</div>}
          {!loading && !error && reports.length + schedules.length + ideas.length === 0 && (
            <div className="alert alert-info">Data masih kosong. Tambahkan ide, jadwal, atau laporan untuk mulai menggunakan workspace.</div>
          )}
          {page === "reports" && (
            <ReportsPage
              reports={filteredReports}
              insightReports={insightReports}
              monthlyReportsCount={reports.filter((report) =>
                String(report.date ?? "").slice(0, 7) === goalMonth
                && (goalPlatform === "all" || String(report.platform ?? "").toLowerCase() === goalPlatform),
              ).length}
              goalMonth={goalMonth}
              goalPlatform={goalPlatform}
              monthlyTarget={monthlyTarget}
              monthlyTargetLoading={monthlyTargetLoading}
              setGoalMonth={(month) => { setMonthlyTarget(null); setGoalMonth(month); setMonthlyTargetLoading(true); }}
              setGoalPlatform={(platform) => { setMonthlyTarget(null); setGoalPlatform(platform); setMonthlyTargetLoading(true); }}
              saveMonthlyTarget={saveMonthlyTarget}
              schedules={schedules}
              ideas={ideas}
              totalFollowers={totalFollowers}
              totalViews={totalViews}
              averageEngagement={averageEngagement}
              accessToken={accessToken}
              period={period}
              setPeriod={setPeriod}
              weekStart={weekStart}
              weekEnd={weekEnd}
              currentMonth={currentMonth}
              query={query}
              setQuery={setQuery}
              platformFilter={platformFilter}
              setPlatformFilter={setPlatformFilter}
              busy={busy}
              syncingData={syncingData}
              syncData={syncData}
              exportCsv={exportCsv}
              openModal={(item) => setModal({ type: "report", item })}
              deleteItem={(item) => removeRecord("reports", item)}
              deleteSchedule={(item) => removeRecord("schedules", item)}
            />
          )}
          {page === "planner" && (
            <PlannerPage
              schedules={schedules}
              busy={busy}
              view={calendarView}
              setView={setCalendarView}
              date={calendarDate}
              setDate={setCalendarDate}
              openModal={(item, date) => setModal({ type: "schedule", item, date })}
              deleteItem={(item) => removeRecord("schedules", item)}
              publish={publishSchedule}
              sync={syncSchedule}
            />
          )}
          {page === "ideas" && (
            <IdeasPage
              ideas={ideas}
              busy={busy}
              openModal={(item, column) => setModal({ type: "idea", item, column })}
              deleteItem={(item) => removeRecord("ideas", item)}
              move={moveIdea}
              sendToPlanner={sendIdeaToPlanner}
              sync={syncIdea}
            />
          )}
          {page === "settings" && (
            <SettingsPage
              key={settingsSnapshot}
              settings={settings}
              userEmail={userEmail}
              databaseConnected={!loading && !error}
              accessToken={accessToken}
              onSave={saveSettings}
            />
          )}
        </div>
      </main>

      {modal && (
        <RecordModal
          key={`${modal.type}-${modal.item?.id ?? "new"}`}
          modal={modal}
          busy={busy}
          onClose={() => setModal(null)}
          onSubmit={saveRecord}
        />
      )}
      {confirmation && (
        <ConfirmationDialog
          confirmation={confirmation}
          busy={busy}
          onCancel={() => { if (!busy) setConfirmation(null); }}
          onConfirm={confirmAction}
        />
      )}
      {notice && <div className="toast"><span className="toast-check">✓</span>{notice}</div>}
    </div>
  );
}

function PageHeading({ eyebrow, title, description, action }: { eyebrow: string; title: string; description: string; action?: React.ReactNode }) {
  return (
    <div className="page-heading">
      <div><p className="eyebrow">{eyebrow}<Icon name="spark" /></p><h1>{title}</h1><p className="page-description">{description}</p></div>
      {action}
    </div>
  );
}

function ReportsPage(props: {
  reports: RecordItem[];
  insightReports: RecordItem[];
  monthlyReportsCount: number;
  goalMonth: string;
  goalPlatform: TargetPlatform;
  monthlyTarget: number | null;
  monthlyTargetLoading: boolean;
  setGoalMonth: (month: string) => void;
  setGoalPlatform: (platform: TargetPlatform) => void;
  saveMonthlyTarget: (targetCount: number) => void;
  schedules: RecordItem[];
  ideas: RecordItem[];
  totalFollowers: number;
  totalViews: number;
  averageEngagement: number;
  accessToken: string;
  period: string;
  setPeriod: (period: string) => void;
  weekStart: string;
  weekEnd: string;
  currentMonth: string;
  query: string;
  setQuery: (query: string) => void;
  platformFilter: string;
  setPlatformFilter: (platform: string) => void;
  busy: boolean;
  syncingData: boolean;
  syncData: () => void;
  exportCsv: () => void;
  openModal: (item?: RecordItem) => void;
  deleteItem: (item: RecordItem) => void;
  deleteSchedule: (item: RecordItem) => void;
}) {
  const [page, setPage] = useState(1);
  const visiblePlatforms = props.platformFilter === "Semua platform" ? platforms : [props.platformFilter];
  const platformCounts = visiblePlatforms.map((platform) => ({
    name: platform,
    count: props.insightReports.filter((item) => platformLabel(item.platform) === platform).length,
  }));
  const activityReports = props.insightReports.filter((report) =>
    isReportInPeriod(report, props.period, props.weekStart, props.weekEnd, props.currentMonth),
  );
  const activityPlatformCounts = visiblePlatforms.map((platform) => ({
    name: platform,
    count: activityReports.filter((item) => platformLabel(item.platform) === platform).length,
  }));
  const maxActivityPlatformCount = Math.max(1, ...activityPlatformCounts.map((item) => item.count));
  const upcomingSchedules = props.schedules.filter((schedule) => schedule.status === "draft" || schedule.status === "scheduled");
  const pageCount = Math.max(1, Math.ceil(props.reports.length / reportsPerPage));
  const currentPage = Math.min(page, pageCount);
  const pageReports = props.reports.slice((currentPage - 1) * reportsPerPage, currentPage * reportsPerPage);
  const setQuery = (query: string) => {
    setPage(1);
    props.setQuery(query);
  };
  const setPlatformFilter = (platform: string) => {
    setPage(1);
    props.setPlatformFilter(platform);
  };
  const setPeriod = (period: string) => {
    setPage(1);
    props.setPeriod(period);
  };
  return (
    <>
      <PageHeading eyebrow="RINGKASAN PERFORMA" title="Laporan Sosmed" description="Pahami performa konten dan temukan hal yang paling disukai audiens." action={
        <div className="heading-actions"><button className="button button-quiet" type="button" onClick={props.exportCsv}><Icon name="download" /> Ekspor CSV</button><button className="button button-quiet" type="button" onClick={props.syncData} disabled={props.busy || props.syncingData}><Icon name="refresh" /> {props.syncingData ? "Menyinkronkan..." : "Sinkron data"}</button><button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Tambah laporan</button></div>
      } />
      <div className="welcome-strip"><div className="welcome-orb"><Icon name="spark" /></div><div><strong>Setiap angka punya cerita.</strong><p>Gunakan ringkasan ini untuk melihat konten mana yang membawa dampak paling besar.</p></div><span className="strip-decoration">INSIGHT / 01</span></div>
      <InstagramInsightsPanel accessToken={props.accessToken} />
      <div className="metric-grid">
        <MetricCard label="Total konten" value={formatNumber(props.insightReports.length)} detail="laporan tersimpan" tone="purple" icon="layers" />
        <MetricCard label="Total followers" value={formatNumber(props.totalFollowers)} detail="followers tercatat" tone="blue" icon="users" />
        <MetricCard label="Total tayangan" value={formatNumber(props.totalViews)} detail="tayangan tercatat" tone="peach" icon="eye" />
        <MetricCard label="Rata-rata engagement" value={`${props.averageEngagement.toFixed(1)}%`} detail="interaksi audiens" tone="mint" icon="heart" />
      </div>
      <MonthlyTargetPanel
        key={`${props.goalMonth}-${props.goalPlatform}-${props.monthlyTarget ?? "unset"}`}
        month={props.goalMonth}
        platform={props.goalPlatform}
        target={props.monthlyTarget}
        count={props.monthlyReportsCount}
        loading={props.monthlyTargetLoading}
        busy={props.busy}
        onMonthChange={props.setGoalMonth}
        onPlatformChange={props.setGoalPlatform}
        onSave={props.saveMonthlyTarget}
      />
      <div className="content-grid analytics-grid">
        <section className="panel trend-panel">
          <div className="panel-heading"><div><p className="eyebrow">OVERVIEW</p><h2>Aktivitas konten</h2></div><div className="segmented">
            {[["mingguan", "Minggu ini"], ["bulanan", "Bulan ini"], ["semua", "Semua"]].map(([value, label]) => <button key={value} type="button" className={props.period === value ? "selected" : ""} onClick={() => setPeriod(value)}>{label}</button>)}
          </div></div>
          <div className="chart-area">
            <div className="chart-y-labels"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
            <div className="chart-bars" role="img" aria-label="Jumlah laporan konten per platform">
                {activityPlatformCounts.map((item, index) => <div className="chart-column" key={item.name}><div className={`chart-bar bar-${index}`} style={{ height: `${Math.max(8, item.count / maxActivityPlatformCount * 100)}%` }}><span>{item.count}</span></div><small className="chart-platform-label"><PlatformIcon platform={item.name} />{item.name}</small></div>)}
              <div className="chart-grid-lines"><i /><i /><i /><i /><i /></div>
            </div>
          </div>
          <div className="chart-legend"><span><i className="legend-dot violet" />Laporan konten</span><span className="chart-footnote">Distribusi berdasarkan platform</span></div>
        </section>
        <section className="panel insight-panel">
          <div className="panel-heading insight-heading"><div><p className="eyebrow">INSIGHT PER PLATFORM</p><h2>{props.platformFilter === "Semua platform" ? "Performa platform" : `Performa ${props.platformFilter}`}</h2></div>
            <select className="insight-filter" value={props.platformFilter} onChange={(event) => setPlatformFilter(event.target.value)} aria-label="Filter insight berdasarkan platform">
              <option>Semua platform</option>{platforms.map((platform) => <option key={platform}>{platform}</option>)}
            </select>
          </div>
          <p className="insight-filter-hint">Filter ini juga berlaku untuk performa terbaru.</p>
          <div className="platform-list">{platformCounts.map((item) => <div className="platform-row" key={item.name}><span className={`platform-symbol ${platformClass(item.name)}`}><PlatformIcon platform={item.name} /></span><span className="platform-row-name">{item.name}</span><div className="mini-track"><i style={{ width: `${item.count ? Math.max(5, item.count / Math.max(props.insightReports.length, 1) * 100) : 0}%` }} /></div><strong>{item.count}</strong></div>)}</div>
          <div className="insight-note"><span>↗</span><p><strong>{props.insightReports.length ? "Konsistensi adalah kunci." : "Mulai catat performa."}</strong><br />{props.insightReports.length ? `Lihat ringkasan konten ${props.platformFilter === "Semua platform" ? "di semua platform" : `di ${props.platformFilter}`}.` : "Tambahkan laporan pertama untuk melihat ringkasan."}</p></div>
        </section>
      </div>
      <section className="panel report-panel">
        <div className="panel-heading report-heading"><div><p className="eyebrow">ARSIP KONTEN</p><h2>Performa terbaru <span className="count-pill">{props.reports.length}</span></h2></div><button type="button" className="text-button" onClick={() => props.openModal()}>Tambah laporan <Icon name="arrow" /></button></div>
        <div className="filter-row">
          <label className="search-box"><Icon name="search" /><input value={props.query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari konten..." /></label>
        </div>
        {props.reports.length === 0 ? <EmptyState title="Belum ada laporan" description="Tambahkan laporan secara manual atau sinkronkan dari kalender dan papan ide." /> : (
          <div className="table-scroll"><table className="data-table"><thead><tr><th>No.</th><th>Konten</th><th>Platform</th><th>Tanggal</th><th>Followers</th><th>Tayangan</th><th>Jangkauan</th><th>Engagement</th><th><span className="sr-only">Aksi</span></th></tr></thead><tbody>
            {pageReports.map((report, index) => <tr key={report.id}>
              <td>{(currentPage - 1) * reportsPerPage + index + 1}</td>
              <td><div className="content-cell"><span className={`platform-avatar ${platformClass(report.platform)}`}><PlatformIcon platform={platformLabel(report.platform)} /></span><span><strong>{report.title}</strong><small>{report.tag || "Konten"} · {report.description || "—"}</small></span></div></td>
              <td><span className={platformClass(report.platform)}><PlatformIcon platform={platformLabel(report.platform)} />{platformLabel(report.platform)}</span></td><td>{formatDate(report.date)}</td><td>{formatNumber(report.followers)}</td><td>{formatNumber(report.tayangan)}</td><td>{formatNumber(report.jangkauan)}</td><td><span className="engagement-value">{report.engagement || "—"}</span></td>
              <td><div className="row-actions"><button title="Edit laporan" aria-label={`Edit laporan ${report.title}`} type="button" onClick={() => props.openModal(report)} disabled={props.busy}><Icon name="edit" /></button><button title="Hapus laporan" aria-label={`Hapus laporan ${report.title}`} type="button" onClick={() => props.deleteItem(report)} disabled={props.busy}><Icon name="trash" /></button></div></td>
            </tr>)}
          </tbody></table></div>
        )}
        {props.reports.length > 0 && (
          <div className="report-pagination">
            <span className="pagination-info">Menampilkan {(currentPage - 1) * reportsPerPage + 1}–{Math.min(currentPage * reportsPerPage, props.reports.length)} dari {props.reports.length} konten</span>
            {pageCount > 1 && (
              <nav className="pagination-controls" aria-label="Navigasi halaman performa terbaru">
                <button type="button" onClick={() => setPage(currentPage - 1)} disabled={currentPage === 1}>Sebelumnya</button>
                {Array.from({ length: pageCount }, (_, index) => index + 1).map((pageNumber) => (
                  <button
                    key={pageNumber}
                    type="button"
                    className={currentPage === pageNumber ? "selected" : ""}
                    aria-label={`Halaman ${pageNumber}`}
                    aria-current={currentPage === pageNumber ? "page" : undefined}
                    onClick={() => setPage(pageNumber)}
                  >
                    {pageNumber}
                  </button>
                ))}
                <button type="button" onClick={() => setPage(currentPage + 1)} disabled={currentPage === pageCount}>Berikutnya</button>
              </nav>
            )}
          </div>
        )}
      </section>
      <div className="bottom-grid">
        <section className="panel bottom-panel"><div className="panel-heading"><div><p className="eyebrow">LANGKAH BERIKUTNYA</p><h2>Yang akan tayang</h2></div><Link className="text-button" href="/content-planner">Buka planner <Icon name="arrow" /></Link></div>
          {upcomingSchedules.length === 0 ? <p className="muted-copy">{props.schedules.length === 0 ? "Belum ada jadwal publikasi." : "Semua jadwal sudah terbit."}</p> : upcomingSchedules.slice(0, 3).map((schedule) => <div className="compact-row" key={schedule.id}><span className={`platform-avatar ${platformClass(schedule.platform)}`}><PlatformIcon platform={platformLabel(schedule.platform)} /></span><span className="compact-title"><strong>{schedule.title}</strong><small>{formatDate(schedule.date)} · {schedule.time}</small></span><span className={`status-badge status-${schedule.status}`}>{schedule.status === "scheduled" ? "Terjadwal" : "Draft"}</span><div className="row-actions"><button type="button" title="Hapus jadwal" aria-label={`Hapus jadwal ${schedule.title}`} onClick={() => props.deleteSchedule(schedule)} disabled={props.busy}><Icon name="trash" /></button></div></div>)}
        </section>
        <section className="panel bottom-panel idea-callout"><span className="callout-graphic">✳</span><p className="eyebrow">KUMPULKAN INSPIRASI</p><h2>Punya ide baru?</h2><p>Simpan ide sebelum terlupa, lalu wujudkan jadi konten berikutnya.</p><Link className="button button-dark" href="/ide-konten">Buka papan ide <Icon name="arrow" /></Link></section>
      </div>
    </>
  );
}

function MonthlyTargetPanel({
  month,
  platform,
  target,
  count,
  loading,
  busy,
  onMonthChange,
  onPlatformChange,
  onSave,
}: {
  month: string;
  platform: TargetPlatform;
  target: number | null;
  count: number;
  loading: boolean;
  busy: boolean;
  onMonthChange: (month: string) => void;
  onPlatformChange: (platform: TargetPlatform) => void;
  onSave: (targetCount: number) => void;
}) {
  const [targetValue, setTargetValue] = useState(target === null ? "" : String(target));
  const reached = target !== null && count >= target;
  const progress = target ? Math.min(100, (count / target) * 100) : 0;
  const monthLabel = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric" })
    .format(new Date(`${month}-01T00:00:00`));
  const platformLabel = targetPlatforms.find((item) => item.id === platform)?.label ?? "Semua platform";

  const submitTarget = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const targetCount = Number(targetValue);
    if (!Number.isSafeInteger(targetCount) || targetCount < 1 || targetCount > 2_147_483_647) return;
    onSave(targetCount);
  };

  return (
    <section className={`monthly-target panel monthly-target-platform-${platform} ${reached ? "monthly-target-reached" : ""}`} aria-labelledby="monthly-target-title">
      <div className="monthly-target-summary">
        <span className="monthly-target-icon" aria-hidden="true">{reached ? "✓" : <Icon name="calendar" />}</span>
        <div>
          <p className="eyebrow">TARGET PUBLIKASI BULANAN</p>
          <h2 id="monthly-target-title">{reached ? "Target tercapai!" : `Target ${platformLabel}`}</h2>
          <p className="monthly-target-copy">
            {loading ? "Memuat target..." : target === null
              ? `Sudah ${count} konten ${platform === "all" ? "di semua platform" : `di ${platformLabel}`} tercatat untuk ${monthLabel}. Tentukan targetnya.`
              : `${count} dari ${formatNumber(target)} konten ${platform === "all" ? "di semua platform" : `di ${platformLabel}`} untuk ${monthLabel}.`}
          </p>
        </div>
      </div>
      <div className="monthly-target-platforms" role="group" aria-label="Filter target berdasarkan platform">
        {targetPlatforms.map((item) => (
          <button
            key={item.id}
            className={platform === item.id ? "selected" : ""}
            type="button"
            aria-pressed={platform === item.id}
            onClick={() => onPlatformChange(item.id)}
          >
            {item.id === "all" ? <Icon name="layers" /> : <PlatformIcon platform={item.id} />}
            {item.label}
          </button>
        ))}
      </div>
      <div className="monthly-target-progress" aria-label={`${count} dari ${target ?? 0} konten diterbitkan`}>
        <span style={{ width: `${progress}%` }} />
      </div>
      <form className="monthly-target-form" onSubmit={submitTarget}>
        <label className="monthly-target-field">
          <span>Bulan</span>
          <input type="month" value={month} onChange={(event) => { if (event.target.value) onMonthChange(event.target.value); }} />
        </label>
        <label className="monthly-target-field">
          <span>Target konten</span>
          <input
            type="number"
            min="1"
            max="2147483647"
            step="1"
            required
            value={targetValue}
            onChange={(event) => setTargetValue(event.target.value)}
            placeholder="Contoh: 12"
          />
        </label>
        <button className="button button-primary" type="submit" disabled={loading || busy || !targetValue}>
          {loading ? "Memuat..." : busy ? "Menyimpan..." : "Simpan target"}
        </button>
      </form>
      {reached && <p className="monthly-target-success" role="status">✓ Selamat, target {platformLabel} untuk {monthLabel} sudah tercapai.</p>}
    </section>
  );
}

function MetricCard({ label, value, detail, tone, icon }: { label: string; value: string; detail: string; tone: string; icon: string }) {
  return <article className={`metric-card tone-${tone}`}><div className="metric-top"><span>{label}</span><span className="metric-icon"><Icon name={icon} /></span></div><strong className="metric-value">{value}</strong><span className="metric-detail">{detail}</span><span className="metric-decoration" /></article>;
}

function PlannerPage(props: {
  schedules: RecordItem[];
  busy: boolean;
  view: "month" | "week" | "list";
  setView: (view: "month" | "week" | "list") => void;
  date: Date;
  setDate: (date: Date) => void;
  openModal: (item?: RecordItem, date?: string) => void;
  deleteItem: (item: RecordItem) => void;
  publish: (item: RecordItem) => void;
  sync: (item: RecordItem) => void;
}) {
  const [expandedDates, setExpandedDates] = useState<Set<string>>(() => new Set());
  const [platformFilter, setPlatformFilter] = useState("Semua platform");
  const filteredSchedules = props.schedules.filter((schedule) => platformFilter === "Semua platform" || platformLabel(schedule.platform) === platformFilter);
  const upcomingSchedules = filteredSchedules.filter((schedule) => schedule.status === "scheduled");
  const emptyScheduleState = platformFilter === "Semua platform"
    ? <EmptyState title="Kalendermu masih kosong" description="Tambahkan jadwal publikasi pertama untuk memulai." />
    : <EmptyState title={`Belum ada jadwal ${platformFilter}`} description="Pilih platform lain atau tambahkan jadwal untuk platform ini." />;
  const mondayOffset = (day: number) => (day + 6) % 7;
  const monthStart = new Date(props.date.getFullYear(), props.date.getMonth(), 1);
  const weekStart = new Date(props.date);
  weekStart.setDate(props.date.getDate() - mondayOffset(props.date.getDay()));
  const gridStart = props.view === "week" ? weekStart : new Date(monthStart.getFullYear(), monthStart.getMonth(), 1 - mondayOffset(monthStart.getDay()));
  const dayCount = props.view === "week" ? 7 : 42;
  const calendarDays = Array.from({ length: dayCount }, (_, index) => {
    const day = new Date(gridStart);
    day.setDate(gridStart.getDate() + index);
    return day;
  });
  const monthLabel = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric" }).format(props.date);
  const daysLabel = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];
  const moveDate = (direction: number) => {
    const next = new Date(props.date);
    next.setDate(next.getDate() + (props.view === "week" ? direction * 7 : 0));
    if (props.view !== "week") next.setMonth(next.getMonth() + direction);
    props.setDate(next);
  };
  const schedulesOn = (date: Date) => filteredSchedules.filter((schedule) => String(schedule.date).slice(0, 10) === dateKey(date));
  const changePlatformFilter = (platform: string) => {
    setPlatformFilter(platform);
    setExpandedDates(new Set());
  };
  const toggleDateExpanded = (date: string) => {
    setExpandedDates((expanded) => {
      const next = new Set(expanded);
      if (next.has(date)) next.delete(date);
      else next.add(date);
      return next;
    });
  };
  return (
    <>
      <PageHeading eyebrow="JAGA RITME PUBLIKASI" title="Content Planner" description="Atur ritme kontenmu. Satu kalender, semua platform." action={<button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Buat jadwal</button>} />
      <div className="planner-summary">
        <div className="planner-summary-copy"><span className="summary-icon"><Icon name="calendar" /></span><span><strong>{filteredSchedules.length} konten direncanakan</strong><small>{platformFilter === "Semua platform" ? "Di semua platform sosial media" : `Di ${platformFilter}`}</small></span></div>
        <div className="summary-separator" /><div className="planner-summary-copy"><span className="summary-icon summary-icon-peach"><Icon name="clock" /></span><span><strong>{upcomingSchedules.length} menunggu publikasi</strong><small>Selesaikan dan catat performanya</small></span></div>
        <Link href="/laporan-sosmed" className="button button-outline summary-link">Lihat laporan <Icon name="arrow" /></Link>
      </div>
      <section className="panel calendar-panel">
        <div className="calendar-toolbar"><div className="calendar-title"><div className="calendar-month-label"><p className="eyebrow">KALENDER KONTEN</p><h2>{props.view === "list" ? "Semua jadwal" : props.view === "week" ? `Minggu · ${monthLabel}` : monthLabel}</h2></div><div className="calendar-nav"><button type="button" onClick={() => moveDate(-1)} aria-label="Sebelumnya">‹</button><button type="button" onClick={() => props.setDate(new Date())}>Hari ini</button><button type="button" onClick={() => moveDate(1)} aria-label="Berikutnya">›</button></div></div>
          <div className="segmented">{(["month", "week", "list"] as const).map((value) => <button key={value} type="button" className={props.view === value ? "selected" : ""} onClick={() => props.setView(value)}>{value === "month" ? "Bulan" : value === "week" ? "Minggu" : "Daftar"}</button>)}</div>
        </div>
        <div className="platform-filter-bar" role="group" aria-label="Filter jadwal berdasarkan platform">
          <button type="button" className={`platform-filter-chip ${platformFilter === "Semua platform" ? "selected" : ""}`} aria-pressed={platformFilter === "Semua platform"} onClick={() => changePlatformFilter("Semua platform")}>
            Semua platform <span className="platform-filter-count">{props.schedules.length}</span>
          </button>
          {platforms.map((platform) => {
            const count = props.schedules.filter((schedule) => platformLabel(schedule.platform) === platform).length;
            return (
              <button
                type="button"
                key={platform}
                className={`platform-filter-chip ${platformFilter === platform ? "selected" : ""}`}
                aria-pressed={platformFilter === platform}
                onClick={() => changePlatformFilter(platform)}
              >
                <span className={`platform-filter-icon ${platformClass(platform)}`} aria-hidden="true"><PlatformIcon platform={platform} /></span>
                {platform} <span className="platform-filter-count">{count}</span>
              </button>
            );
          })}
        </div>
        {props.view === "list" ? <div className="schedule-list">{filteredSchedules.length === 0 ? emptyScheduleState : [...filteredSchedules].sort((a, b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`)).map((item) => <ScheduleRow key={item.id} item={item} edit={() => props.openModal(item)} remove={() => props.deleteItem(item)} publish={() => props.publish(item)} sync={() => props.sync(item)} />)}</div> : (
          <>
            <div className={`calendar-grid ${props.view === "week" ? "week-grid" : ""}`}>
              {daysLabel.map((label) => <div className="calendar-weekday" key={label}>{label}</div>)}
              {calendarDays.map((date, index) => {
                const dateSchedules = schedulesOn(date);
                const dateId = dateKey(date);
                const isExpanded = expandedDates.has(dateId);
                const visibleSchedules = isExpanded ? dateSchedules : dateSchedules.slice(0, 3);
                return <div key={dateKey(date)} className={`calendar-day ${props.view === "month" && date.getMonth() !== props.date.getMonth() ? "outside-month" : ""} ${dateKey(date) === dateKey(new Date()) ? "today" : ""} ${props.view === "week" ? `week-day-${index}` : ""}`}>
                  <div className="day-head"><span>{date.getDate()}</span>{props.view === "week" && <small>{new Intl.DateTimeFormat("id-ID", { month: "short" }).format(date)}</small>}<button type="button" aria-label={`Tambah jadwal ${dateKey(date)}`} onClick={() => props.openModal(undefined, dateKey(date))}>+</button></div>
                  <div className="day-events">
                    {visibleSchedules.map((item) => (
                      <div className="calendar-event-row" key={item.id}>
                        <button type="button" className={`event-chip event-${item.status}`} onClick={() => props.openModal(item)}>
                          <span className={`event-platform-mark ${platformClass(item.platform)}`} aria-hidden="true"><PlatformIcon platform={platformLabel(item.platform)} /></span>
                          <span className="event-time">{item.time}</span><span className="event-title">{item.title}</span>
                        </button>
                        <button type="button" className="event-delete" title="Hapus jadwal" aria-label={`Hapus jadwal ${item.title}`} onClick={() => props.deleteItem(item)} disabled={props.busy}><Icon name="trash" /></button>
                      </div>
                    ))}
                    {dateSchedules.length > 3 && (
                      <button type="button" className="more-events" aria-expanded={isExpanded} onClick={() => toggleDateExpanded(dateId)}>
                        {isExpanded ? "Sembunyikan" : `+${dateSchedules.length - 3} lainnya`}
                      </button>
                    )}
                  </div>
                </div>;
              })}
            </div>
          </>
        )}
      </section>
      <section className="panel upcoming-panel"><div className="panel-heading"><div><p className="eyebrow">ANTREAN TERDEKAT</p><h2>Siap dipublikasikan</h2></div><span className="count-pill">{upcomingSchedules.length} jadwal</span></div>
        {upcomingSchedules.length === 0 ? <p className="muted-copy">{platformFilter === "Semua platform" ? "Tidak ada jadwal yang menunggu publikasi." : `Tidak ada jadwal ${platformFilter} yang menunggu publikasi.`}</p> : upcomingSchedules.slice(0, 3).map((item) => <ScheduleRow key={item.id} item={item} edit={() => props.openModal(item)} remove={() => props.deleteItem(item)} publish={() => props.publish(item)} sync={() => props.sync(item)} />)}
      </section>
    </>
  );
}

function ScheduleRow({ item, edit, remove, publish, sync }: { item: RecordItem; edit: () => void; remove: () => void; publish: () => void; sync: () => void }) {
  return <div className="schedule-row"><span className={`platform-avatar ${platformClass(item.platform)}`}><PlatformIcon platform={platformLabel(item.platform)} /></span><span className="schedule-row-main"><strong>{item.title}</strong><small>{formatDate(item.date)} · {item.time} · {platformLabel(item.platform)}</small></span><span className={`status-badge status-${item.status}`}>{item.status === "scheduled" ? "Terjadwal" : item.status === "published" ? "Terbit" : "Draft"}</span><div className="schedule-row-actions"><button type="button" onClick={edit} title="Edit jadwal"><Icon name="edit" /></button>{item.status === "scheduled" && <button type="button" onClick={publish} title="Tandai sudah terbit">Terbit</button>}<button type="button" onClick={sync} title="Sinkronkan ke laporan">Laporan</button><button type="button" onClick={remove} title="Hapus jadwal"><Icon name="trash" /></button></div></div>;
}

function IdeasPage(props: {
  ideas: RecordItem[];
  busy: boolean;
  openModal: (item?: RecordItem, column?: IdeaColumn) => void;
  deleteItem: (item: RecordItem) => void;
  move: (item: RecordItem, column: IdeaColumn) => void;
  sendToPlanner: (item: RecordItem) => void;
  sync: (item: RecordItem) => void;
}) {
  return (
    <>
      <PageHeading eyebrow="TANGKAP SEMUA INSPIRASI" title="Ide Konten" description="Papan kecil untuk ide-ide besar. Kembangkan, jadwalkan, dan wujudkan." action={<button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Tambah ide</button>} />
      <div className="ideas-banner"><div className="ideas-banner-symbol"><Icon name="spark" /></div><div><strong>Ide terbaik sering datang tiba-tiba.</strong><p>Simpan di sini, kamu bisa mengembangkannya kapan saja.</p></div><span className="banner-count"><strong>{props.ideas.length}</strong><small>ide tersimpan</small></span></div>
      <div className="kanban-board">{columns.map((column) => {
        const items = props.ideas.filter((idea) => idea.column === column.id);
        return <section className={`kanban-column column-${column.color}`} key={column.id}>
          <div className="kanban-column-heading"><span className={`column-dot dot-${column.color}`} /><h2>{column.label}</h2><span className="count-pill">{items.length}</span><button type="button" title={`Tambah di ${column.label}`} onClick={() => props.openModal(undefined, column.id)}>+</button></div>
          <div className="idea-stack">{items.length === 0 ? <button className="empty-column" type="button" onClick={() => props.openModal(undefined, column.id)}>Belum ada ide di sini <span>+ Tambahkan</span></button> : items.map((idea) => <article className="idea-card" key={idea.id}>
            <div className="idea-card-top"><span className={platformClass(idea.platform)}><PlatformIcon platform={platformLabel(idea.platform)} />{platformLabel(idea.platform)}</span><button type="button" className="idea-edit" onClick={() => props.openModal(idea)} aria-label="Edit ide"><Icon name="edit" /></button></div>
            <h3>{idea.title}</h3><p>{idea.description ? linkifyDescription(String(idea.description)) : "Tambahkan catatan untuk mengembangkan ide ini."}</p>
            <div className="idea-card-meta"><span>{idea.category || "General"}</span>{idea.scheduled_note && <span><Icon name="calendar" /> Terjadwal</span>}</div>
            <div className="idea-card-actions">
              <select aria-label={`Pindahkan ide ${idea.title}`} value={String(idea.column)} onChange={(event) => props.move(idea, event.target.value as IdeaColumn)} disabled={props.busy}>
                {columns.map((option) => <option key={option.id} value={option.id}>Pindah: {option.label}</option>)}
              </select>
              <button type="button" title="Kirim ke planner" onClick={() => props.sendToPlanner(idea)} disabled={props.busy}><Icon name="calendar" /></button>
              <button type="button" title="Buat laporan" onClick={() => props.sync(idea)} disabled={props.busy}><Icon name="chart" /></button>
              <button type="button" title="Hapus ide" onClick={() => props.deleteItem(idea)} disabled={props.busy}><Icon name="trash" /></button>
            </div>
          </article>)}</div>
        </section>;
      })}</div>
    </>
  );
}

function SettingsPage(props: { settings: Record<string, string>; userEmail: string; databaseConnected: boolean; accessToken: string; onSave: (event: FormEvent<HTMLFormElement>, profileImage: string) => void }) {
  const { settings } = props;
  const [profileImage, setProfileImage] = useState(getProfileImage(settings.profileImage));
  const [photoError, setPhotoError] = useState("");
  const workspaceInitial = settings.workspaceName?.trim().charAt(0).toUpperCase() || "K";

  const chooseProfileImage = async (event: ChangeEvent<HTMLInputElement>) => {
    const input = event.currentTarget;
    const file = input.files?.[0];
    input.value = "";
    if (!file) return;

    try {
      setProfileImage(await resizeProfileImage(file));
      setPhotoError("");
    } catch (error) {
      setPhotoError(error instanceof Error ? error.message : "Foto tidak dapat digunakan.");
    }
  };

  const submitSettings = (event: FormEvent<HTMLFormElement>) => {
    if (photoError) {
      event.preventDefault();
      return;
    }
    props.onSave(event, profileImage);
  };

  return (
    <>
      <PageHeading eyebrow="SESUAIKAN WORKSPACE" title="Pengaturan" description="Atur preferensi agar ruang kerjamu terasa lebih personal." />
      <form className="settings-layout" onSubmit={submitSettings}>
        <div className="settings-main">
          <section className="panel settings-panel">
            <div className="settings-panel-heading"><span className="settings-icon"><Icon name="spark" /></span><div><h2>Foto profil</h2><p>Pilih foto untuk avatar workspace di navigasi dan header.</p></div></div>
            <div className="profile-photo-layout">
              <WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="profile-photo-preview" />
              <div className="profile-photo-controls">
                <label className="field"><span>Pilih foto profil</span><input type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={chooseProfileImage} /></label>
                <small className="profile-photo-hint">Gambar akan diperkecil otomatis. Maksimal 10 MB.</small>
                {photoError && <p className="profile-photo-error" role="alert">{photoError}</p>}
                {profileImage && <button className="button button-quiet profile-photo-remove" type="button" onClick={() => { setProfileImage(""); setPhotoError(""); }}>Hapus foto</button>}
              </div>
            </div>
          </section>
          <section className="panel settings-panel"><div className="settings-panel-heading"><span className="settings-icon"><Icon name="spark" /></span><div><h2>Username</h2><p>Username ini ditampilkan di header menggantikan email akun.</p></div></div><div className="settings-fields"><label className="field field-wide"><span>Username</span><input required maxLength={40} name="username" defaultValue={settings.username || props.userEmail.split("@")[0]} placeholder="Contoh: namapengguna" autoComplete="nickname" /></label></div></section>
          <section className="panel settings-panel"><div className="settings-panel-heading"><span className="settings-icon"><Icon name="spark" /></span><div><h2>Nama workspace</h2><p>Nama ini akan tampil di navigasi dan header seluruh halaman.</p></div></div><div className="settings-fields"><label className="field field-wide"><span>Nama workspace</span><input required maxLength={60} name="workspaceName" defaultValue={settings.workspaceName || "Kundrew Studio"} placeholder="Contoh: Studio Kreatif" /></label></div></section>
          <InstagramConnectionSettings accessToken={props.accessToken} />
          <section className="panel settings-panel"><div className="settings-panel-heading"><span className="settings-icon settings-icon-pink">◎</span><div><h2>Platform tersedia</h2><p>Pilih salah satu saat menambahkan ide, jadwal, atau laporan.</p></div></div><div className="platform-listing">{platforms.map((platform) => <div className="platform-listing-row" key={platform}><span className={`platform-avatar ${platformClass(platform)}`}><PlatformIcon platform={platform} /></span><span><strong>{platform}</strong><small>Tersedia untuk konten workspace</small></span></div>)}</div></section>
          <div className="settings-save"><button className="button button-primary" type="submit" disabled={Boolean(photoError)}>Simpan perubahan <Icon name="arrow" /></button></div>
        </div>
        <aside className="settings-side"><div className="settings-aside-art">✳</div><p className="eyebrow">RUANG KREATIFMU</p><h2>Dirancang untuk ide yang terus bergerak.</h2><p>Preferensi dan foto profil disimpan di browser ini. Data laporan, jadwal, dan ide tersimpan di database.</p><span className={`storage-status ${props.databaseConnected ? "" : "storage-error"}`}><i /> {props.databaseConnected ? "Database terhubung" : "Database belum terhubung"}</span></aside>
      </form>
    </>
  );
}

function EmptyState({ title, description }: { title: string; description: string }) {
  return <div className="empty-state"><span className="empty-state-icon">✳</span><strong>{title}</strong><p>{description}</p></div>;
}

function InstagramMetric({ label, value }: { label: string; value: number | null }) {
  return <div className="instagram-metric"><small>{label}</small><strong>{formatNumber(value)}</strong></div>;
}

function formattedSyncTime(value: string | null) {
  if (!value) return "Belum pernah disinkronkan";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "Waktu sinkronisasi tidak diketahui";
  return `Diperbarui ${new Intl.DateTimeFormat("id-ID", { dateStyle: "medium", timeStyle: "short" }).format(date)}`;
}

function InstagramInsightsPanel({ accessToken }: { accessToken: string }) {
  const [connected, setConnected] = useState<boolean | null>(null);
  const [connection, setConnection] = useState<InstagramConnection | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState("");

  const syncNow = useCallback(async () => {
    setBusy(true);
    setError("");
    try {
      const fresh = await syncInstagram(accessToken);
      setConnection(fresh);
      setConnected(true);
    } catch (syncError) {
      setError(syncError instanceof Error ? syncError.message : "Insight Instagram tidak dapat disinkronkan.");
    } finally {
      setBusy(false);
    }
  }, [accessToken]);

  const loadInsights = useCallback(async () => {
    setBusy(true);
    setError("");
    try {
      const status = await getInstagramStatus(accessToken);
      setConnected(status.connected);
      if (!status.connected) {
        setConnection(null);
        return;
      }
      if (!status.connection) throw new Error("Status koneksi Instagram dari server tidak valid.");
      setConnection(status.connection);
      const lastSync = status.connection.lastSyncedAt ? Date.parse(status.connection.lastSyncedAt) : Number.NaN;
      if (!Number.isFinite(lastSync) || Date.now() - lastSync > 12 * 60 * 60 * 1000) {
        const fresh = await syncInstagram(accessToken);
        setConnection(fresh);
      }
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Insight Instagram tidak dapat dimuat.");
    } finally {
      setBusy(false);
    }
  }, [accessToken]);

  useEffect(() => {
    void Promise.resolve().then(loadInsights);
  }, [loadInsights]);

  return (
    <section className="panel instagram-insights-card" aria-labelledby="instagram-insights-title">
      <div className="panel-heading">
        <div><p className="eyebrow">INSIGHT OTOMATIS</p><h2 id="instagram-insights-title">Instagram</h2></div>
        {connected && <button className="button button-quiet" type="button" onClick={() => { void syncNow(); }} disabled={busy}>{busy ? "Memuat..." : "Sinkronkan"}</button>}
      </div>
      {busy && !connection && <div className="instagram-status"><span className="spinner" /> Memeriksa koneksi Instagram...</div>}
      {error && <p className="instagram-error" role="alert">{error}</p>}
      {!busy && connected === false && (
        <div className="instagram-empty">
          <p>Hubungkan akun Instagram Professional untuk melihat insight akun otomatis.</p>
          <Link className="button button-quiet" href="/pengaturan">Atur koneksi</Link>
        </div>
      )}
      {connection && (
        <>
          <div className="instagram-account">
            <span className={`platform-avatar ${platformClass("instagram")}`}><PlatformIcon platform="Instagram" /></span>
            <span><strong>@{connection.username}</strong><small>{connection.accountType || "Instagram Professional"} · {formatNumber(connection.profile.mediaCount)} konten · {formattedSyncTime(connection.lastSyncedAt)}</small></span>
            {busy && <span className="instagram-sync-indicator"><i /> Memperbarui</span>}
          </div>
          <div className="instagram-metrics">
            <InstagramMetric label="Followers" value={connection.profile.followers} />
            <InstagramMetric label="Jangkauan harian" value={connection.insights.reach} />
            <InstagramMetric label="Akun berinteraksi" value={connection.insights.accountsEngaged} />
            <InstagramMetric label="Total interaksi" value={connection.insights.totalInteractions} />
          </div>
          <p className="instagram-insights-note">Data 24 jam terakhir dari Meta; sebagian metrik dapat terlambat hingga 48 jam atau tidak tersedia untuk akun tertentu.</p>
        </>
      )}
    </section>
  );
}

function InstagramConnectionSettings({ accessToken }: { accessToken: string }) {
  const [configured, setConfigured] = useState(false);
  const [connected, setConnected] = useState<boolean | null>(null);
  const [connection, setConnection] = useState<InstagramConnection | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const loadConnection = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const status = await getInstagramStatus(accessToken);
      setConfigured(status.configured);
      setConnected(status.connected);
      setConnection(status.connection);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Status Instagram tidak dapat dimuat.");
    } finally {
      setLoading(false);
    }
  }, [accessToken]);

  useEffect(() => {
    void Promise.resolve().then(loadConnection);
    const url = new URL(window.location.href);
    const result = url.searchParams.get("instagram");
    if (result === "connected") void Promise.resolve().then(() => setNotice("Akun Instagram berhasil dihubungkan."));
    if (result === "cancelled") void Promise.resolve().then(() => setNotice("Proses koneksi Instagram dibatalkan."));
    if (result === "failed") void Promise.resolve().then(() => setError("Koneksi Instagram gagal. Periksa konfigurasi dan izin Meta."));
    if (result) {
      url.searchParams.delete("instagram");
      window.history.replaceState({}, "", url.toString());
    }
  }, [loadConnection]);

  const connect = async () => {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      const response = await fetch("/api/integrations/instagram/connect", {
        method: "POST",
        headers: { Authorization: `Bearer ${accessToken}` },
      });
      const payload = await readApiResponse(response, "Koneksi Instagram tidak dapat dimulai.");
      const data = isObjectRecord(payload.data) ? payload.data : {};
      if (typeof data.authorizationUrl !== "string") throw new Error("Tautan otorisasi Instagram tidak valid.");
      window.location.assign(data.authorizationUrl);
    } catch (connectError) {
      setError(connectError instanceof Error ? connectError.message : "Koneksi Instagram tidak dapat dimulai.");
      setBusy(false);
    }
  };

  const syncNow = async () => {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      setConnection(await syncInstagram(accessToken));
      setConnected(true);
      setNotice("Insight Instagram berhasil diperbarui.");
    } catch (syncError) {
      setError(syncError instanceof Error ? syncError.message : "Insight Instagram tidak dapat disinkronkan.");
    } finally {
      setBusy(false);
    }
  };

  const disconnect = async () => {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      const response = await fetch("/api/integrations/instagram", {
        method: "DELETE",
        headers: { Authorization: `Bearer ${accessToken}` },
      });
      await readApiResponse(response, "Akun Instagram tidak dapat diputus.");
      setConnection(null);
      setConnected(false);
      setNotice("Akun Instagram berhasil diputus.");
    } catch (disconnectError) {
      setError(disconnectError instanceof Error ? disconnectError.message : "Akun Instagram tidak dapat diputus.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <section className="panel settings-panel instagram-connection-panel">
      <div className="settings-panel-heading">
        <span className="settings-icon settings-icon-instagram">◎</span>
        <div><h2>Hubungkan Instagram</h2><p>Sinkronkan insight akun Instagram Professional milikmu.</p></div>
      </div>
      {loading ? <p className="instagram-status"><span className="spinner" /> Memeriksa koneksi...</p> : (
        <>
          {connected && connection ? (
            <div className="instagram-settings-account">
              <span><strong>@{connection.username}</strong><small>{connection.accountType || "Instagram Professional"} · {formatNumber(connection.profile.mediaCount)} konten · {formattedSyncTime(connection.lastSyncedAt)}</small></span>
              <div className="instagram-settings-actions">
                <button className="button button-quiet" type="button" onClick={() => { void syncNow(); }} disabled={busy}>{busy ? "Memuat..." : "Sinkronkan"}</button>
                <button className="button button-quiet instagram-disconnect" type="button" onClick={() => { void disconnect(); }} disabled={busy}>Putuskan</button>
              </div>
            </div>
          ) : (
            <div className="instagram-connect-copy">
              <p>{configured ? "Masuk ke Instagram dan izinkan akses insight untuk menghubungkan akun." : "Konfigurasi Meta App dan environment variables server diperlukan sebelum akun dapat dihubungkan."}</p>
              <button className="button button-primary" type="button" onClick={() => { void connect(); }} disabled={!configured || busy}>
                {busy ? "Menghubungkan..." : "Hubungkan akun Instagram"}
              </button>
            </div>
          )}
        </>
      )}
      {error && <p className="instagram-error" role="alert">{error}</p>}
      {notice && <p className="instagram-success" role="status">{notice}</p>}
    </section>
  );
}

function RecordModal({ modal, busy, onClose, onSubmit }: { modal: Exclude<ModalState, null>; busy: boolean; onClose: () => void; onSubmit: (event: FormEvent<HTMLFormElement>) => void }) {
  const item = modal.item;
  const title = modal.type === "report" ? "laporan" : modal.type === "schedule" ? "jadwal" : "ide";
  const initial: Record<string, string> = modal.type === "report" ? emptyReport : modal.type === "schedule" ? emptySchedule : emptyIdea;
  const value = (key: string) => item?.[key] == null ? "" : String(item[key]);
  return <div className="modal-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose(); }}><section className="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div className="modal-heading"><div><p className="eyebrow">{item ? "PERBARUI DATA" : "TAMBAH KE WORKSPACE"}</p><h2 id="modal-title">{item ? "Edit" : "Tambah"} {title}</h2></div><button className="icon-button" type="button" onClick={onClose} aria-label="Tutup"><Icon name="close" /></button></div>
    <form className="modal-form" onSubmit={onSubmit}>
      <label className="field field-wide"><span>Judul</span><input autoFocus required maxLength={255} name="title" defaultValue={value("title") || initial.title} placeholder="Tulis judul konten..." /></label>
      <div className="field-grid"><label className="field"><span>Platform</span><select name="platform" defaultValue={value("platform") ? platformLabel(value("platform")) : initial.platform}>{platforms.map((platform) => <option key={platform}>{platform}</option>)}</select></label>
        {modal.type !== "idea" && <label className="field"><span>Tag / format</span><input name="tag" defaultValue={value("tag") || initial.tag} placeholder="Contoh: Reels" /></label>}
        {modal.type === "idea" && <label className="field"><span>Kategori</span><input name="category" defaultValue={value("category")} placeholder="Contoh: Edukasi" /></label>}
      </div>
      {modal.type === "report" && <div className="field-grid three-fields"><label className="field"><span>Followers</span><input name="followers" defaultValue={value("followers") === "-" ? "" : value("followers")} placeholder="0" /></label><label className="field"><span>Tayangan</span><input name="tayangan" defaultValue={value("tayangan") === "-" ? "" : value("tayangan")} placeholder="0" /></label><label className="field"><span>Jangkauan</span><input name="jangkauan" defaultValue={value("jangkauan") === "-" ? "" : value("jangkauan")} placeholder="0" /></label></div>}
      {modal.type === "report" && <div className="field-grid"><label className="field"><span>Tanggal upload</span><input type="date" required name="date" defaultValue={value("date") || initial.date} /></label><label className="field"><span>Engagement</span><input name="engagement" defaultValue={value("engagement") === "-%" ? "" : value("engagement")} placeholder="4.5%" /></label><label className="field"><span>Periode</span><select name="period" defaultValue={value("period") || initial.period}><option value="mingguan">Mingguan</option><option value="bulanan">Bulanan</option></select></label></div>}
      {modal.type === "schedule" && <div className="field-grid"><label className="field"><span>Tanggal</span><input type="date" required name="date" defaultValue={value("date") || modal.date || initial.date} /></label><label className="field"><span>Waktu</span><input type="time" required name="time" defaultValue={value("time") || initial.time} /></label><label className="field"><span>Status</span><select name="status" defaultValue={value("status") || initial.status}><option value="scheduled">Terjadwal</option><option value="draft">Draft</option><option value="published">Terbit</option></select></label></div>}
      {modal.type === "idea" && <label className="field"><span>Status ide</span><select name="column" defaultValue={value("column") || modal.column || "ideas"}>{columns.map((column) => <option key={column.id} value={column.id}>{column.label}</option>)}</select></label>}
      <label className="field field-wide"><span>Deskripsi</span><textarea rows={3} name="description" defaultValue={value("description")} placeholder="Tambahkan catatan singkat..." /></label>
      <div className="modal-footer"><button type="button" className="button button-quiet" onClick={onClose}>Batal</button><button type="submit" className="button button-primary" disabled={busy}>{busy ? "Menyimpan..." : "Simpan"} <Icon name="arrow" /></button></div>
    </form>
  </section></div>;
}

function ConfirmationDialog({
  confirmation,
  busy,
  onCancel,
  onConfirm,
}: {
  confirmation: Exclude<ConfirmationState, null>;
  busy: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}) {
  const isDelete = confirmation.type === "delete";
  const title = isDelete ? String(confirmation.item.title ?? "data ini") : confirmation.title;
  const heading = isDelete ? "Hapus data ini?" : "Simpan perubahan?";

  return (
    <div className="confirm-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) onCancel(); }}>
      <section className="confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description">
        <span className={`confirm-icon ${isDelete ? "confirm-icon-danger" : ""}`}><Icon name={isDelete ? "trash" : "edit"} /></span>
        <p className="eyebrow">{isDelete ? "KONFIRMASI HAPUS" : "KONFIRMASI EDIT"}</p>
        <h2 id="confirm-title">{heading}</h2>
        <p id="confirm-description">
          {isDelete
            ? `Kamu yakin ingin menghapus "${title}"? Tindakan ini tidak dapat dibatalkan.`
            : `Simpan perubahan untuk "${title}"?`}
        </p>
        <div className="confirm-actions">
          <button type="button" className="button button-quiet" onClick={onCancel} disabled={busy} autoFocus>Tidak</button>
          <button type="button" className={`button ${isDelete ? "button-danger" : "button-primary"}`} onClick={onConfirm} disabled={busy}>
            {busy ? "Memproses..." : isDelete ? "Ya, hapus" : "Ya, simpan"}
          </button>
        </div>
      </section>
    </div>
  );
}
