import { NextResponse } from "next/server";
import { handleApiError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";

function csvCell(value: unknown): string {
  const text = value == null ? "" : String(value);
  return `"${text.replaceAll('"', '""')}"`;
}

export async function GET(_request: Request, context: { params: Promise<{ id: string }> }) {
  try {
    const { id } = await context.params;
    if (id !== "export") return NextResponse.json({ status: "error", message: "Laporan tidak ditemukan." }, { status: 404 });
    const { rows } = await getPool().query(`SELECT * FROM ${tableName("reports")} ORDER BY id ASC`);
    const headers = ["ID", "Judul Konten", "Platform", "Tag/Format", "Followers", "Tayangan", "Jangkauan", "Engagement", "Tanggal", "Periode", "Deskripsi"];
    const lines = [
      headers.map(csvCell).join(","),
      ...rows.map((row) =>
        [row.id, row.title, row.platform, row.tag, row.followers, row.tayangan, row.jangkauan, row.engagement, row.date, row.period, row.description]
          .map(csvCell)
          .join(","),
      ),
    ];
    return new Response(`\uFEFF${lines.join("\r\n")}`, {
      headers: {
        "Content-Type": "text/csv; charset=utf-8",
        "Content-Disposition": `attachment; filename="laporan-sosmed-${new Date().toISOString().slice(0, 10)}.csv"`,
      },
    });
  } catch (error) {
    return handleApiError(error);
  }
}
