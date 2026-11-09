import { NextResponse } from "next/server";
import { handleApiError, notFound, readObject, text } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";

export async function POST(request: Request, context: { params: Promise<{ id: string }> }) {
  try {
    const { id } = await context.params;
    if (!/^\d+$/.test(id)) return notFound("Ide tidak ditemukan.");
    const body = await readObject(request);
    const result = await getPool().query(
      `INSERT INTO ${tableName("reports")} (title, description, platform, tag, followers, tayangan, jangkauan, engagement, date, period, idea_id)
       SELECT title, COALESCE(NULLIF(description, ''), 'Diposting dari Ide Konten'), LOWER(platform), COALESCE(NULLIF(category, ''), INITCAP(platform)), $1, $2, $3, $4, CURRENT_DATE::text, $5, id
       FROM ${tableName("ideas")} WHERE id = $6 RETURNING *`,
      [
        text(body, "followers", { fallback: "-" }),
        text(body, "tayangan", { fallback: "-" }),
        text(body, "jangkauan", { fallback: "-" }),
        text(body, "engagement", { fallback: "-%" }),
        text(body, "period", { fallback: "mingguan" }),
        id,
      ],
    );
    if (!result.rows[0]) return notFound("Ide tidak ditemukan.");
    return NextResponse.json({ status: "success", message: "Ide berhasil disinkronkan ke laporan.", data: result.rows[0] }, { status: 201 });
  } catch (error) {
    return handleApiError(error);
  }
}
