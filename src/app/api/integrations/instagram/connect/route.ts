import { NextResponse } from "next/server";
import { handleApiError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import { createInstagramOAuthState, instagramOAuthUrl } from "@/lib/instagram";
import { requireUser } from "@/lib/auth";

export async function POST(request: Request) {
  try {
    const user = await requireUser(request);
    const { state, hash } = createInstagramOAuthState();
    const pool = getPool();
    await pool.query(`DELETE FROM ${tableName("instagram_oauth_states")} WHERE user_id = $1 OR expires_at < NOW()`, [user.id]);
    await pool.query(
      `INSERT INTO ${tableName("instagram_oauth_states")} (state_hash, user_id, expires_at)
       VALUES ($1, $2, NOW() + INTERVAL '10 minutes')`,
      [hash, user.id],
    );
    return NextResponse.json({ status: "success", data: { authorizationUrl: instagramOAuthUrl(state) } });
  } catch (error) {
    return handleApiError(error);
  }
}
