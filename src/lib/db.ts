import { Pool } from "pg";
import { DatabaseConfigurationError } from "@/lib/api";

declare global {
  // Keep one connection pool during Next.js development hot reloads.
  var contentPlannerPool: Pool | undefined;
}

export function getPool(): Pool {
  if (globalThis.contentPlannerPool) return globalThis.contentPlannerPool;

  const connectionString = process.env.DB_URL ?? process.env.DATABASE_URL;
  const host = process.env.DB_HOST;
  if (!connectionString && !host) {
    throw new DatabaseConfigurationError();
  }

  const sslMode = process.env.DB_SSLMODE;
  const pool = new Pool({
    ...(connectionString
      ? { connectionString }
      : {
          host,
          port: Number(process.env.DB_PORT ?? 5432),
          database: process.env.DB_DATABASE ?? "postgres",
          user: process.env.DB_USERNAME,
          password: process.env.DB_PASSWORD,
        }),
    ...(sslMode === "require" ? { ssl: { rejectUnauthorized: false } } : {}),
    max: 3,
    idleTimeoutMillis: 30_000,
    connectionTimeoutMillis: 8_000,
  });

  globalThis.contentPlannerPool = pool;
  return pool;
}

export function tableName(name: "reports" | "schedules" | "ideas"): string {
  const schema = process.env.DB_SCHEMA ?? "public";
  if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(schema)) {
    throw new Error("DB_SCHEMA hanya boleh berisi huruf, angka, dan underscore.");
  }
  return `"${schema}"."${name}"`;
}
