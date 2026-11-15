import { getPool, tableName } from "@/lib/db";
import { AuthenticationError, SupabaseAuthConfigurationError } from "@/lib/api";

type AuthenticatedUser = { id: string; email: string | null };

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

export async function requireUser(request: Request): Promise<AuthenticatedUser> {
  const authorization = request.headers.get("authorization");
  const token = authorization?.match(/^Bearer\s+(\S+)$/i)?.[1];
  if (!token) throw new AuthenticationError();

  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const anonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
  if (!supabaseUrl || !anonKey) throw new SupabaseAuthConfigurationError();

  const response = await fetch(`${supabaseUrl.replace(/\/+$/, "")}/auth/v1/user`, {
    headers: { apikey: anonKey, Authorization: `Bearer ${token}` },
    cache: "no-store",
  });
  if (response.status === 401 || response.status === 403) throw new AuthenticationError();
  if (!response.ok) throw new Error(`Supabase Auth gagal memverifikasi sesi (HTTP ${response.status}).`);

  const payload: unknown = await response.json();
  if (!isRecord(payload) || typeof payload.id !== "string") {
    throw new Error("Supabase Auth mengirim data pengguna dengan format tidak valid.");
  }

  const user = { id: payload.id, email: typeof payload.email === "string" ? payload.email : null };
  const ownerEmail = process.env.APP_OWNER_EMAIL?.trim().toLowerCase();
  if (ownerEmail && user.email?.toLowerCase() === ownerEmail) {
    const pool = getPool();
    for (const resource of ["reports", "schedules", "ideas"] as const) {
      await pool.query(`UPDATE ${tableName(resource)} SET user_id = $1 WHERE user_id IS NULL`, [user.id]);
    }
  }

  return user;
}
