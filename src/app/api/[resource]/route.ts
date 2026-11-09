import { NextResponse } from "next/server";
import { getPool, tableName } from "@/lib/db";
import { handleApiError, InputError, nullableId, readObject, text } from "@/lib/api";

type Resource = "reports" | "schedules" | "ideas";

function getResource(value: string): Resource {
  if (value === "reports" || value === "schedules" || value === "ideas") return value;
  throw new InputError("Resource tidak ditemukan.");
}

function createValues(resource: Resource, body: Record<string, unknown>) {
  if (resource === "reports") {
    const platform = text(body, "platform", { required: true });
    const engagement = text(body, "engagement", { fallback: "-%" });
    return {
      title: text(body, "title", { required: true, max: 255 }),
      description: text(body, "description", { fallback: "Diposting baru saja" }),
      platform: platform.toLowerCase(),
      tag: text(body, "tag", { fallback: platform[0].toUpperCase() + platform.slice(1) }),
      followers: text(body, "followers", { fallback: "-" }),
      tayangan: text(body, "tayangan", { fallback: "-" }),
      jangkauan: text(body, "jangkauan", { fallback: "-" }),
      engagement: engagement !== "-%" && !engagement.includes("%") ? `${engagement}%` : engagement,
      date: text(body, "date", { fallback: new Date().toISOString().slice(0, 10) }),
      period: text(body, "period", { fallback: "mingguan" }),
      schedule_id: nullableId(body, "schedule_id"),
      idea_id: nullableId(body, "idea_id"),
    };
  }
  if (resource === "schedules") {
    const platform = text(body, "platform", { required: true });
    const status = text(body, "status", { required: true });
    if (!["scheduled", "draft", "published"].includes(status)) {
      throw new InputError("Status jadwal tidak valid.");
    }
    return {
      platform: platform.toLowerCase(),
      tag: text(body, "tag", { fallback: platform[0].toUpperCase() + platform.slice(1) }),
      title: text(body, "title", { required: true, max: 255 }),
      date: text(body, "date", { required: true }),
      time: text(body, "time", { required: true }),
      status,
      description: text(body, "description"),
      idea_id: nullableId(body, "idea_id"),
    };
  }

  const platform = text(body, "platform", { required: true });
  const column = text(body, "column", { required: true });
  if (!["ideas", "in_progress", "ready_to_shoot", "completed"].includes(column)) {
    throw new InputError("Kolom ide tidak valid.");
  }
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

export async function GET(_request: Request, context: { params: Promise<{ resource: string }> }) {
  try {
    const { resource: key } = await context.params;
    const resource = getResource(key);
    const result = await getPool().query(`SELECT * FROM ${tableName(resource)} ORDER BY id ASC`);
    return NextResponse.json({ status: "success", data: result.rows });
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: Request, context: { params: Promise<{ resource: string }> }) {
  try {
    const { resource: key } = await context.params;
    const resource = getResource(key);
    const values = createValues(resource, await readObject(request));
    const columns = Object.keys(values);
    const placeholders = columns.map((_, index) => `$${index + 1}`).join(", ");
    const result = await getPool().query(
      `INSERT INTO ${tableName(resource)} (${columns.map((column) => `"${column}"`).join(", ")}) VALUES (${placeholders}) RETURNING *`,
      Object.values(values),
    );
    return NextResponse.json(
      { status: "success", message: "Data berhasil ditambahkan.", data: result.rows[0] },
      { status: 201 },
    );
  } catch (error) {
    return handleApiError(error);
  }
}
