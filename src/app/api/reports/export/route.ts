import { handleApiError, InputError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";

const platforms = ["Instagram", "TikTok", "Threads", "YouTube"];

function csvCell(value: unknown): string {
  const text = value == null ? "" : String(value);
  return `"${text.replaceAll('"', '""')}"`;
}

function platformLabel(value: unknown): string {
  const platform = String(value ?? "").toLowerCase();
  return platforms.find((item) => item.toLowerCase() === platform) ?? String(value ?? "");
}

export async function GET(request: Request) {
  try {
    const platform = new URL(request.url).searchParams.get("platform");
    if (platform && !platforms.includes(platform)) throw new InputError("Filter platform tidak valid.");

    const { rows } = await getPool().query(
      `SELECT * FROM ${tableName("reports")}
       ${platform ? 'WHERE LOWER("platform") = LOWER($1)' : ""}
       ORDER BY LOWER("platform") ASC, "date" DESC NULLS LAST, id DESC`,
      platform ? [platform] : [],
    );
    const headers = ["No.", "ID Laporan", "Platform", "Tanggal Publikasi", "Nama Konten", "Format", "Followers", "Tayangan", "Jangkauan", "Engagement", "Periode", "Deskripsi"];
    const lines = [
      headers.map(csvCell).join(","),
      ...rows.map((row, index) =>
        [
          index + 1,
          row.id,
          platformLabel(row.platform),
          row.date,
          row.title,
          row.tag,
          row.followers,
          row.tayangan,
          row.jangkauan,
          row.engagement,
          row.period === "mingguan" ? "Mingguan" : row.period === "bulanan" ? "Bulanan" : row.period,
          row.description,
        ]
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
