"use client";

import { FormEvent, useState } from "react";
import type { SupabaseClient } from "@supabase/supabase-js";

function getAuthErrorMessage(error: unknown) {
  const message = error instanceof Error ? error.message : "";
  if (/email rate limit exceeded/i.test(message)) {
    return "Pengiriman email pendaftaran sedang dibatasi karena terlalu banyak permintaan. Jangan mencoba daftar berulang kali. Tunggu hingga batasnya pulih, atau minta admin aplikasi mengatur SMTP khusus di pengaturan Authentication Supabase.";
  }
  if (/invalid login credentials/i.test(message)) {
    return "Email atau kata sandi tidak cocok. Jika pendaftaran sebelumnya terkena batas email dan Anda belum menerima email konfirmasi, akun belum siap digunakan. Tunggu hingga batas email pulih atau minta admin mengatur SMTP Supabase, lalu selesaikan pendaftaran.";
  }
  if (/email not confirmed/i.test(message)) {
    return "Email akun belum dikonfirmasi. Minta admin mengonfirmasi akun di Supabase → Authentication → Users, atau buka tautan konfirmasi dari email. Jika sudah dikonfirmasi, pastikan aplikasi memakai project Supabase yang sama.";
  }
  return message || "Autentikasi gagal. Silakan coba lagi.";
}

export default function AuthGate({ client, loading }: { client: SupabaseClient | null; loading: boolean }) {
  const [mode, setMode] = useState<"sign-in" | "sign-up">("sign-in");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!client) return;

    setBusy(true);
    setError("");
    setMessage("");
    try {
      const result = mode === "sign-up"
        ? await client.auth.signUp({ email: email.trim(), password })
        : await client.auth.signInWithPassword({ email: email.trim(), password });
      if (result.error) {
        setError(getAuthErrorMessage(result.error));
      } else if (mode === "sign-up" && !result.data.session) {
        setMessage("Akun berhasil dibuat. Periksa email untuk konfirmasi sebelum masuk.");
      } else {
        setMessage("Berhasil masuk.");
      }
    } catch (authError) {
      setError(getAuthErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return <main className="auth-page"><div className="loading-banner"><span className="spinner" /> Memeriksa sesi akun...</div></main>;
  }

  if (!client) {
    return (
      <main className="auth-page">
        <section className="auth-card">
          <div className="auth-showcase-brand">
            <span className="auth-mark" aria-hidden="true">✦</span>
            <span><strong>Kundrew Studio</strong><small>CONTENT WORKSPACE</small></span>
          </div>
          <p className="eyebrow">PENYIAPAN AKUN</p>
          <h1>Hubungkan Supabase Auth</h1>
          <p>Tambahkan URL project dan anon key Supabase di environment variables agar login dapat digunakan.</p>
          <code>NEXT_PUBLIC_SUPABASE_URL</code>
          <code>NEXT_PUBLIC_SUPABASE_ANON_KEY</code>
          <small>Jangan gunakan service role key di browser.</small>
        </section>
      </main>
    );
  }

  return (
    <main className="auth-page">
      <div className="auth-shell">
        <aside className="auth-showcase">
          <div className="auth-showcase-brand">
            <span className="auth-mark" aria-hidden="true">✦</span>
            <span><strong>Kundrew Studio</strong><small>CONTENT WORKSPACE</small></span>
          </div>
          <div className="auth-showcase-content">
            <p className="auth-showcase-kicker">IDE · PLANNER · ANALITIK</p>
            <h2>Ide besar layak punya rencana.</h2>
            <p>Simpan inspirasi, atur jadwal publikasi, dan lihat perkembangan kontenmu dalam satu ruang kerja.</p>
            <div className="auth-flow" aria-label="Alur kerja konten">
              <span><i>01</i> Kumpulkan ide</span>
              <b aria-hidden="true">→</b>
              <span><i>02</i> Atur jadwal</span>
              <b aria-hidden="true">→</b>
              <span><i>03</i> Lihat hasil</span>
            </div>
          </div>
          <p className="auth-showcase-footer">Ruang yang rapi untuk proses kreatif yang konsisten.</p>
          <span className="auth-orb auth-orb-one" aria-hidden="true" />
          <span className="auth-orb auth-orb-two" aria-hidden="true" />
        </aside>
        <section className="auth-card">
          <div className="auth-card-heading">
            <p className="eyebrow">{mode === "sign-in" ? "AKSES WORKSPACE" : "MULAI WORKSPACE"}</p>
            <h1>{mode === "sign-in" ? "Selamat datang kembali" : "Buat akun workspace"}</h1>
            <p>{mode === "sign-in" ? "Masuk untuk melihat workspace milikmu." : "Daftar dengan email untuk mulai mengatur konten."}</p>
          </div>
          <form className="auth-form" onSubmit={submit}>
            <div className="field">
              <label htmlFor="auth-email">Email</label>
              <input id="auth-email" type="email" autoComplete="email" placeholder="nama@email.com" required value={email} onChange={(event) => setEmail(event.target.value)} />
            </div>
            <div className="field">
              <label htmlFor="auth-password">Kata sandi</label>
              <div className="auth-password-field">
                <input id="auth-password" type={passwordVisible ? "text" : "password"} autoComplete={mode === "sign-in" ? "current-password" : "new-password"} placeholder="Minimal 8 karakter" minLength={8} required value={password} onChange={(event) => setPassword(event.target.value)} />
                <button
                  className="auth-password-toggle"
                  type="button"
                  aria-label={passwordVisible ? "Sembunyikan kata sandi" : "Tampilkan kata sandi"}
                  aria-pressed={passwordVisible}
                  onClick={() => setPasswordVisible((visible) => !visible)}
                >
                  {passwordVisible ? (
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" /><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.4 15.4 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10.5 10.5 0 0 0 3.2-.5" /></svg>
                  ) : (
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                  )}
                </button>
              </div>
            </div>
            {error && <p className="auth-message auth-error" role="alert">{error}</p>}
            {message && <p className="auth-message" role="status">{message}</p>}
            <button className="button button-primary auth-submit" type="submit" disabled={busy}>
              {busy ? "Memproses..." : mode === "sign-in" ? "Masuk ke workspace" : "Buat akun"}
            </button>
          </form>
          <p className="auth-switch-prompt">
            {mode === "sign-in" ? "Belum punya akun?" : "Sudah punya akun?"}
            <button className="auth-switch" type="button" onClick={() => { setMode(mode === "sign-in" ? "sign-up" : "sign-in"); setPasswordVisible(false); setError(""); setMessage(""); }}>
              {mode === "sign-in" ? "Daftar" : "Masuk"}
            </button>
          </p>
          <p className="auth-card-note">Dengan masuk, data workspace-mu tetap tersimpan pada akunmu.</p>
        </section>
      </div>
    </main>
  );
}
