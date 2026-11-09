import { NextResponse } from "next/server";
import { handleApiError, notFound } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import { readObject } from "@/lib/api";
import { InputError } from "@/lib/api";
import { text } from "@/lib/api";

type Resource = "reports" | "schedules" | "ideas";

function getResource(value: string): Resource {
  if (value === "reports" || value === "schedules" || value === "ideas") return value;
  throw new InputError("Resource tidak ditemukan.");
}

function updateValues(resource: Resource, body: Record<string, unknown>) {
  if (resource === "reports") {
    const platform = text(body, "platform", { required: true });
    const engagement = text(body, "engagement", { fallback: "-%" });
    return {
      title: text(body, "title", { required: true, max: 255 }),
      description: text(body, "description"),
      platform: platform.toLowerCase(),
      tag: text(body, "tag", { fallback: platform[0].toUpperCase() + platform.slice(1) }),
      followers: text(body, "followers", { fallback: "-" }),
      tayangan: text(body, "tayangan", { fallback: "-" }),
      jangkauan: text(body, "jangkauan", { fallback: "-" }),
      engagement: engagement !== "-%" && !engagement.includes("%") ? `${engagement}%` : engagement,
      date: text(body, "date", { fallback: new Date().toISOString().slice(0, 10) }),
      period: text(body, "period", { fallback: "mingguan" }),
    };
  }
  if (resource === "schedules") {
    const platform = text(body, "platform", { required: true });
    const status = text(body, "status", { required: true });
    if (!["scheduled", "draft", "published"].includes(status)) throw new InputError("Status jadwal tidak valid.");
    return {
      platform: platform.toLowerCase(),
      tag: text(body, "tag", { fallback: platform[0].toUpperCase() + platform.slice(1) }),
      title: text(body, "title", { required: true, max: 255 }),
      date: text(body, "date", { required: true }),
      time: text(body, "time", { required: true }),
      status,
      description: text(body, "description"),
      idea_id: body.idea_id == null ? null : String(body.idea_id),
    };
  }
  const platform = text(body, "platform", { required: true });
  const column = text(body, "column", { required: true });
  if (!["ideas", "in_progress", "ready_to_shoot", "completed"].includes(column)) throw new InputError("Kolom ide tidak valid.");
  return {
    title: text(body, "title", { required: true, max: 255 }),
    description: text(body, "description"),
    platform: platform.toLowerCase(),
    category: text(body, "category", { fallback: "General" }),
    column,
    badge: text(body, "badge") || null,
    time_ago: text(body, "time_ago", { fallback: "Baru saja" }),
    scheduled_note: text(body, "scheduled_note") || null,
  };
}

export async function PUT(request: Request, context: { params: Promise<{ resource: string; id: string }> }) {
  try {
    const { resource: key, id } = await context.params;
    const resource = getResource(key);
    if (!/^\d+$/.test(id)) return notFound("Data tidak ditemukan.");
    const values = updateValues(resource, await readObject(request));
    const columns = Object.keys(values);
    const setters = columns.map((column, index) => `"${column}" = $${index + 1}`).join(", ");
    const result = await getPool().query(
      `UPDATE ${tableName(resource)} SET ${setters} WHERE id = $${columns.length + 1} RETURNING *`,
      [...Object.values(values), id],
    );
    if (!result.rows[0]) return notFound("Data tidak ditemukan.");
    return NextResponse.json({ status: "success", message: "Data berhasil diperbarui.", data: result.rows[0] });
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: Request, context: { params: Promise<{ resource: string; id: string }> }) {
  try {
    const { resource: key, id } = await context.params;
    const resource = getResource(key);
    if (!/^\d+$/.test(id)) return notFound("Data tidak ditemukan.");
    const result = await getPool().query(`DELETE FROM ${tableName(resource)} WHERE id = $1`, [id]);
    if (!result.rowCount) return notFound("Data tidak ditemukan.");
    return NextResponse.json({ status: "success", message: "Data berhasil dihapus." });
  } catch (error) {
    return handleApiError(error);
  }
}
