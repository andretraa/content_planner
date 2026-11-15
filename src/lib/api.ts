import { NextResponse } from "next/server";

export class InputError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "InputError";
  }
}

export class DatabaseConfigurationError extends Error {
  constructor() {
    super("Konfigurasi Supabase belum ditemukan. Di lokal, isi DB_HOST atau DB_URL di .env.local. Di Vercel, tambahkan POSTGRES_URL atau POSTGRES_HOST beserta variabel database di Project Settings → Environment Variables lalu redeploy.");
    this.name = "DatabaseConfigurationError";
  }
}

export class SupabaseAuthConfigurationError extends Error {
  constructor() {
    super("Supabase Auth belum dikonfigurasi. Tambahkan NEXT_PUBLIC_SUPABASE_URL dan NEXT_PUBLIC_SUPABASE_ANON_KEY.");
    this.name = "SupabaseAuthConfigurationError";
  }
}

export class AuthenticationError extends Error {
  constructor() {
    super("Sesi tidak valid atau sudah berakhir. Silakan masuk kembali.");
    this.name = "AuthenticationError";
  }
}

export class IntegrationError extends Error {
  constructor(message: string, readonly status: 400 | 409 | 502 | 503) {
    super(message);
    this.name = "IntegrationError";
  }
}

export async function readObject(request: Request): Promise<Record<string, unknown>> {
  let value: unknown;
  try {
    value = await request.json();
  } catch {
    throw new InputError("Format request tidak valid.");
  }
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    throw new InputError("Data request harus berupa object.");
  }
  return value as Record<string, unknown>;
}

export function text(
  body: Record<string, unknown>,
  key: string,
  options: { required?: boolean; fallback?: string; max?: number } = {},
): string {
  const value = body[key];
  if (value === undefined || value === null || value === "") {
    if (options.required) throw new InputError(`${key} wajib diisi.`);
    return options.fallback ?? "";
  }
  if (typeof value !== "string") throw new InputError(`${key} harus berupa teks.`);
  const result = value.trim();
  if (options.required && !result) throw new InputError(`${key} wajib diisi.`);
  if (options.max && result.length > options.max) {
    throw new InputError(`${key} tidak boleh lebih dari ${options.max} karakter.`);
  }
  return result;
}

export function nullableId(body: Record<string, unknown>, key: string): string | null {
  const value = body[key];
  if (value === undefined || value === null || value === "") return null;
  if (typeof value !== "string" && typeof value !== "number") {
    throw new InputError(`${key} tidak valid.`);
  }
  const result = String(value);
  if (!/^\d+$/.test(result)) throw new InputError(`${key} tidak valid.`);
  return result;
}

export function handleApiError(error: unknown) {
  if (error instanceof InputError) {
    return NextResponse.json({ status: "error", message: error.message }, { status: 400 });
  }
  if (error instanceof DatabaseConfigurationError) {
    return NextResponse.json({ status: "error", message: error.message }, { status: 503 });
  }
  if (error instanceof SupabaseAuthConfigurationError) {
    return NextResponse.json({ status: "error", message: error.message }, { status: 503 });
  }
  if (error instanceof AuthenticationError) {
    return NextResponse.json({ status: "error", message: error.message }, { status: 401 });
  }
  if (error instanceof IntegrationError) {
    return NextResponse.json({ status: "error", message: error.message }, { status: error.status });
  }
  console.error("API request failed:", error);
  return NextResponse.json(
    { status: "error", message: "Permintaan gagal. Periksa koneksi database dan coba lagi." },
    { status: 500 },
  );
}

export function notFound(message: string) {
  return NextResponse.json({ status: "error", message }, { status: 404 });
}
