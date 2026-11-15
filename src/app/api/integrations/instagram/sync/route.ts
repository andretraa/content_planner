import { NextResponse } from "next/server";
import { IntegrationError, handleApiError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import {
  decryptInstagramToken,
  encryptInstagramToken,
  fetchInstagramInsights,
  fetchInstagramProfile,
  refreshInstagramToken,
} from "@/lib/instagram";
import { requireUser } from "@/lib/auth";

export async function POST(request: Request) {
  try {
    const user = await requireUser(request);
    const pool = getPool();
    const result = await pool.query(
      `SELECT instagram_user_id, token_encrypted, token_issued_at, token_expires_at
       FROM ${tableName("instagram_connections")} WHERE user_id = $1`,
      [user.id],
    );
    const connection = result.rows[0];
    if (!connection) throw new IntegrationError("Hubungkan akun Instagram terlebih dahulu dari Pengaturan.", 409);

    let token = decryptInstagramToken(connection.token_encrypted);
    let tokenIssuedAt = new Date(connection.token_issued_at);
    let tokenExpiresAt = new Date(connection.token_expires_at);
    const now = Date.now();
    if (tokenExpiresAt.getTime() <= now) {
      throw new IntegrationError("Token Instagram sudah kedaluwarsa. Hubungkan ulang akun dari Pengaturan.", 409);
    }

    if (
      tokenExpiresAt.getTime() - now < 7 * 24 * 60 * 60 * 1000 &&
      now - tokenIssuedAt.getTime() >= 24 * 60 * 60 * 1000
    ) {
      const refreshed = await refreshInstagramToken(token);
      token = refreshed.accessToken;
      tokenIssuedAt = new Date();
      tokenExpiresAt = new Date(tokenIssuedAt.getTime() + refreshed.expiresIn * 1000);
    }

    const [profile, insights] = await Promise.all([
      fetchInstagramProfile(token),
      fetchInstagramInsights(connection.instagram_user_id, token),
    ]);
    if (profile.instagramUserId !== connection.instagram_user_id) {
      throw new IntegrationError("Akun Instagram yang terhubung berubah. Hubungkan ulang akun.", 409);
    }

    const updated = await pool.query(
      `UPDATE ${tableName("instagram_connections")}
       SET username = $2, account_type = $3, token_encrypted = $4, token_issued_at = $5,
           token_expires_at = $6, profile = $7, insights = $8, last_synced_at = NOW(), updated_at = NOW()
       WHERE user_id = $1
       RETURNING username, account_type, profile, insights, last_synced_at, token_expires_at`,
      [
        user.id,
        profile.username,
        profile.accountType,
        encryptInstagramToken(token),
        tokenIssuedAt,
        tokenExpiresAt,
        JSON.stringify(profile),
        JSON.stringify(insights),
      ],
    );
    const row = updated.rows[0];
    return NextResponse.json({
      status: "success",
      data: {
        username: row.username,
        accountType: row.account_type,
        profile: row.profile,
        insights: row.insights,
        lastSyncedAt: row.last_synced_at,
        tokenExpiresAt: row.token_expires_at,
      },
    });
  } catch (error) {
    return handleApiError(error);
  }
}
