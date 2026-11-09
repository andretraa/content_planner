import { NextResponse } from "next/server";
import type { PoolClient } from "pg";
import { handleApiError, notFound, readObject, text } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";

export async function POST(request: Request, context: { params: Promise<{ id: string }> }) {
  let client: PoolClient | undefined;
  try {
    client = await getPool().connect();
    const { id } = await context.params;
    if (!/^\d+$/.test(id)) return notFound("Ide tidak ditemukan.");
    const body = await readObject(request);
    const date = text(body, "date", { fallback: new Date(Date.now() + 2 * 86_400_000).toISOString().slice(0, 10) });
    const time = text(body, "time", { fallback: "14:00" });
    await client.query("BEGIN");
    const schedule = await client.query(
      `INSERT INTO ${tableName("schedules")} (platform, tag, title, date, time, status, description, idea_id)
       SELECT LOWER(platform), COALESCE(NULLIF(category, ''), INITCAP(platform)), title, $1, $2, 'scheduled', description, id
       FROM ${tableName("ideas")} WHERE id = $3 RETURNING *`,
      [date, time, id],
    );
    if (!schedule.rows[0]) {
      await client.query("ROLLBACK");
      return notFound("Ide tidak ditemukan.");
    }
    await client.query(
      `UPDATE ${tableName("ideas")} SET "column" = 'ready_to_shoot', scheduled_note = $1 WHERE id = $2`,
      [`Scheduled for ${date}, ${time}`, id],
    );
    await client.query("COMMIT");
    return NextResponse.json({ status: "success", message: "Ide berhasil dijadwalkan.", data: schedule.rows[0] }, { status: 201 });
  } catch (error) {
    if (client) {
      await client.query("ROLLBACK").catch((rollbackError: unknown) => console.error("Database rollback failed:", rollbackError));
    }
    return handleApiError(error);
  } finally {
    client?.release();
  }
}
