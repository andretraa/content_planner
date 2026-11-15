import { NextResponse } from "next/server";
import { IntegrationError } from "@/lib/api";
import { getPool, tableName } from "@/lib/db";
import {
  encryptInstagramToken,
  exchangeInstagramCode,
  fetchInstagramProfile,
  hashInstagramOAuthState,
  instagramSettingsUrl,
} from "@/lib/instagram";

export async function GET(request: Request) {
  const url = new URL(request.url);
  try {
    const state = url.searchParams.get("state");
    if (!state || !/^[A-Za-z0-9_-]{40,50}$/.test(state)) {
      throw new IntegrationError("Sesi koneksi Instagram tidak valid atau sudah kedaluwarsa. Mulai ulang proses koneksi.", 400);
    }

    const hash = hashInstagramOAuthState(state);
    const stateResult = await getPool().query(
      `DELETE FROM ${tableName("instagram_oauth_states")}
       WHERE state_hash = $1 AND expires_at > NOW()
       RETURNING user_id`,
      [hash],
    );
    if (!stateResult.rows[0]) {
      throw new IntegrationError("Sesi koneksi Instagram tidak valid atau sudah kedaluwarsa. Mulai ulang proses koneksi.", 400);
    }

    if (url.searchParams.has("error")) {
      return NextResponse.redirect(instagramSettingsUrl(request.url, "cancelled"));
    }

    const code = url.searchParams.get("code");
    if (!code) throw new IntegrationError("Instagram tidak mengirim kode otorisasi. Coba hubungkan ulang akun.", 400);

    const token = await exchangeInstagramCode(code);
    const profile = await fetchInstagramProfile(token.accessToken);
    const tokenIssuedAt = new Date();
    const tokenExpiresAt = new Date(tokenIssuedAt.getTime() + token.expiresIn * 1000);
    const encryptedToken = encryptInstagramToken(token.accessToken);

    await getPool().query(
      `INSERT INTO ${tableName("instagram_connections")}
         (user_id, instagram_user_id, username, account_type, token_encrypted, token_issued_at, token_expires_at, profile, insights, last_synced_at, updated_at)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, '{}'::jsonb, NULL, NOW())
       ON CONFLICT (user_id) DO UPDATE SET
         instagram_user_id = EXCLUDED.instagram_user_id,
         username = EXCLUDED.username,
         account_type = EXCLUDED.account_type,
         token_encrypted = EXCLUDED.token_encrypted,
         token_issued_at = EXCLUDED.token_issued_at,
         token_expires_at = EXCLUDED.token_expires_at,
         profile = EXCLUDED.profile,
         insights = '{}'::jsonb,
         last_synced_at = NULL,
         updated_at = NOW()`,
      [
        stateResult.rows[0].user_id,
        profile.instagramUserId,
        profile.username,
        profile.accountType,
        encryptedToken,
        tokenIssuedAt,
        tokenExpiresAt,
        JSON.stringify(profile),
      ],
    );

    return NextResponse.redirect(instagramSettingsUrl(request.url, "connected"));
  } catch (error) {
    console.error(
      "Instagram OAuth callback failed:",
      error instanceof Error ? error.message : "Unexpected error",
    );
    return NextResponse.redirect(instagramSettingsUrl(request.url, "failed"));
  }
}
