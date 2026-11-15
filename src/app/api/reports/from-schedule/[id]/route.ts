import { NextResponse } from "next/server";
import type { PoolClient } from "pg";
import { handleApiError, notFound, readObject, text } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import { requireUser } from "@/lib/auth";

export async function POST(request: Request, context: { params: Promise<{ id: string }> }) {
  try {
    const user = await requireUser(request);
    const { id } = await context.params;
    if (!/^\d+$/.test(id)) return notFound("Jadwal tidak ditemukan.");
    const body = await readObject(request);
    const values = [
      text(body, "followers", { fallback: "-" }),
      text(body, "tayangan", { fallback: "-" }),
      text(body, "jangkauan", { fallback: "-" }),
      text(body, "engagement", { fallback: "-%" }),
      text(body, "period", { fallback: "mingguan" }),
    ];
    let client: PoolClient | undefined;
    let transactionOpen = false;
    try {
      client = await getPool().connect();
      await client.query("BEGIN");
      transactionOpen = true;

      const schedule = await client.query(`SELECT id FROM ${tableName("schedules")} WHERE id = $1 AND user_id = $2 FOR UPDATE`, [id, user.id]);
      if (!schedule.rows[0]) {
        await client.query("ROLLBACK");
        transactionOpen = false;
        return notFound("Jadwal tidak ditemukan.");
      }

      const existing = await client.query(
        `SELECT * FROM ${tableName("reports")} WHERE schedule_id = $1 AND user_id = $2 ORDER BY id ASC LIMIT 1`,
        [id, user.id],
      );
      if (existing.rows[0]) {
        await client.query("COMMIT");
        transactionOpen = false;
        return NextResponse.json({
          status: "success",
          message: "Laporan untuk jadwal ini sudah tersedia.",
          data: existing.rows[0],
        });
      }

      const result = await client.query(
        `INSERT INTO ${tableName("reports")} (title, description, platform, tag, followers, tayangan, jangkauan, engagement, date, period, schedule_id, idea_id, user_id)
         SELECT title, COALESCE(NULLIF(description, ''), 'Diposting dari Content Planner'), LOWER(platform), COALESCE(NULLIF(tag, ''), INITCAP(platform)), $1, $2, $3, $4, date, $5, id, idea_id, $7
         FROM ${tableName("schedules")} WHERE id = $6 AND user_id = $7 RETURNING *`,
        [...values, id, user.id],
      );
      if (!result.rows[0]) {
        await client.query("ROLLBACK");
        transactionOpen = false;
        return notFound("Jadwal tidak ditemukan.");
      }

      await client.query("COMMIT");
      transactionOpen = false;
      return NextResponse.json({ status: "success", message: "Jadwal berhasil disinkronkan ke laporan.", data: result.rows[0] }, { status: 201 });
    } catch (error) {
      if (client && transactionOpen) {
        try {
          await client.query("ROLLBACK");
        } catch (rollbackError) {
          console.error("API transaction rollback failed:", rollbackError);
        }
      }
      return handleApiError(error);
    } finally {
      client?.release();
    }
  } catch (error) {
    return handleApiError(error);
  }
}
