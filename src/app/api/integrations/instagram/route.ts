import { NextResponse } from "next/server";
import { handleApiError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import { isInstagramConfigured } from "@/lib/instagram";
import { requireUser } from "@/lib/auth";

export async function GET(request: Request) {
  try {
    const user = await requireUser(request);
    const result = await getPool().query(
      `SELECT username, account_type, profile, insights, last_synced_at, token_expires_at
       FROM ${tableName("instagram_connections")} WHERE user_id = $1`,
      [user.id],
    );
    const connection = result.rows[0];
    return NextResponse.json({
      status: "success",
      configured: isInstagramConfigured(),
      connected: Boolean(connection),
      data: connection
        ? {
            username: connection.username,
            accountType: connection.account_type,
            profile: connection.profile,
            insights: connection.insights,
            lastSyncedAt: connection.last_synced_at,
            tokenExpiresAt: connection.token_expires_at,
          }
        : null,
    });
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(request: Request) {
  try {
    const user = await requireUser(request);
    const pool = getPool();
    const client = await pool.connect();
    try {
      await client.query("BEGIN");
      await client.query(`DELETE FROM ${tableName("instagram_connections")} WHERE user_id = $1`, [user.id]);
      await client.query(`DELETE FROM ${tableName("instagram_oauth_states")} WHERE user_id = $1`, [user.id]);
      await client.query("COMMIT");
    } catch (error) {
      await client.query("ROLLBACK");
      throw error;
    } finally {
      client.release();
    }
    return NextResponse.json({ status: "success", message: "Akun Instagram berhasil diputus." });
  } catch (error) {
    return handleApiError(error);
  }
}
