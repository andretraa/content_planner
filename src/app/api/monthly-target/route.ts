import { NextResponse } from "next/server";
import { handleApiError, InputError, readObject, text } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { getPool, tableName } from "@/lib/db";

const targetPlatforms = ["all", "instagram", "tiktok", "threads", "youtube"] as const;
type TargetPlatform = (typeof targetPlatforms)[number];

function validateMonth(value: string) {
  if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(value)) {
    throw new InputError("Bulan target harus menggunakan format YYYY-MM.");
  }
  return value;
}

function validatePlatform(value: string): TargetPlatform {
  const platform = targetPlatforms.find((candidate) => candidate === value);
  if (!platform) throw new InputError("Platform target tidak valid.");
  return platform;
}

export async function GET(request: Request) {
  try {
    const user = await requireUser(request);
    const params = new URL(request.url).searchParams;
    const month = validateMonth(params.get("month") ?? "");
    const platform = validatePlatform(params.get("platform") ?? "all");
    const [result, allTargets] = await Promise.all([
      getPool().query(
        `SELECT target_count FROM ${tableName("monthly_content_targets")} WHERE user_id = $1 AND target_month = $2 AND platform = $3`,
        [user.id, month, platform],
      ),
      getPool().query(
        `SELECT platform, target_count FROM ${tableName("monthly_content_targets")} WHERE user_id = $1 AND target_month = $2`,
        [user.id, month],
      ),
    ]);
    const targets: Record<string, number> = {};
    for (const row of allTargets.rows) {
      targets[row.platform] = row.target_count;
    }
    return NextResponse.json({
      status: "success",
      data: {
        month,
        platform,
        targetCount: result.rows[0]?.target_count ?? null,
        targets,
      },
    });
  } catch (error) {
    return handleApiError(error);
  }
}

export async function PUT(request: Request) {
  try {
    const user = await requireUser(request);
    const body = await readObject(request);
    const month = validateMonth(text(body, "month", { required: true }));
    const platform = validatePlatform(text(body, "platform", { fallback: "all" }));
    const rawTargetCount = body.targetCount;
    const targetCount = typeof rawTargetCount === "number"
      ? rawTargetCount
      : typeof rawTargetCount === "string" && /^\d+$/.test(rawTargetCount)
        ? Number(rawTargetCount)
        : NaN;
    if (!Number.isSafeInteger(targetCount) || targetCount < 1 || targetCount > 2_147_483_647) {
      throw new InputError("Target konten harus berupa bilangan bulat positif.");
    }

    const [result, allTargets] = await Promise.all([
      getPool().query(
        `INSERT INTO ${tableName("monthly_content_targets")} (user_id, target_month, platform, target_count)
         VALUES ($1, $2, $3, $4)
         ON CONFLICT (user_id, target_month, platform)
         DO UPDATE SET target_count = EXCLUDED.target_count
         RETURNING target_month, platform, target_count`,
        [user.id, month, platform, targetCount],
      ),
      getPool().query(
        `SELECT platform, target_count FROM ${tableName("monthly_content_targets")} WHERE user_id = $1 AND target_month = $2`,
        [user.id, month],
      ),
    ]);
    const targets: Record<string, number> = {};
    for (const row of allTargets.rows) {
      targets[row.platform] = row.target_count;
    }
    return NextResponse.json({
      status: "success",
      message: "Target bulanan berhasil disimpan.",
      data: {
        month: result.rows[0].target_month,
        platform: result.rows[0].platform,
        targetCount: result.rows[0].target_count,
        targets,
      },
    });
  } catch (error) {
    return handleApiError(error);
  }
}
