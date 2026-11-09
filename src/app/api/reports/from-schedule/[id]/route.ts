import { NextResponse } from "next/server";
import { handleApiError, notFound, readObject, text } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";

export async function POST(request: Request, context: { params: Promise<{ id: string }> }) {
  try {
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
    const result = await getPool().query(
      `INSERT INTO ${tableName("reports")} (title, description, platform, tag, followers, tayangan, jangkauan, engagement, date, period, schedule_id, idea_id)
       SELECT title, COALESCE(NULLIF(description, ''), 'Diposting dari Content Planner'), LOWER(platform), COALESCE(NULLIF(tag, ''), INITCAP(platform)), $1, $2, $3, $4, date, $5, id, idea_id
       FROM ${tableName("schedules")} WHERE id = $6 RETURNING *`,
      [...values, id],
    );
    if (!result.rows[0]) return notFound("Jadwal tidak ditemukan.");
    return NextResponse.json({ status: "success", message: "Jadwal berhasil disinkronkan ke laporan.", data: result.rows[0] }, { status: 201 });
  } catch (error) {
    return handleApiError(error);
  }
}
