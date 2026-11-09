# Planning Kundrew

Dashboard berbahasa Indonesia untuk mengelola ide konten, jadwal publikasi, dan laporan performa sosial media. Aplikasi menggunakan Next.js App Router, TypeScript, dan Supabase PostgreSQL.

## Fitur

- **Laporan Sosmed** — ringkasan performa, filter konten, CRUD laporan, dan ekspor CSV.
- **Content Planner** — kalender bulanan/mingguan, daftar jadwal, pengubahan status, dan sinkronisasi ke laporan.
- **Ide Konten** — papan Kanban empat tahap, CRUD ide, penjadwalan ide, dan sinkronisasi ke laporan.
- **Pengaturan** — preferensi akun serta notifikasi.
- Animasi transisi halaman, kartu, dan dialog; animasi menghormati preferensi `prefers-reduced-motion`.

## Menjalankan secara lokal

Prasyarat: Node.js 20.9 atau lebih baru dan akses ke database Supabase PostgreSQL.

```bash
npm install
Copy-Item .env.example .env.local
```

Isi `.env.local` menggunakan detail koneksi **Session pooler** Supabase (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require`, dan `DB_SCHEMA=public`), atau gunakan connection string `DB_URL`. Jangan commit atau membagikan kredensial database. Jalankan SQL di [`database/schema.next.sql`](./database/schema.next.sql) sekali jika database belum mempunyai tabel aplikasi. Bila tabel `reports`, `schedules`, dan `ideas` sudah tersedia, aplikasi dapat langsung menggunakan tabel tersebut.

Database SQLite Laravel lama, bila ada, dibiarkan utuh tetapi tidak dibaca atau diimpor otomatis oleh Next.js. Pastikan data yang ingin dipakai sudah ada di database Supabase sebelum beralih.

```bash
npm run dev
```

Buka `http://localhost:3000`. Perintah pemeriksaan yang tersedia:

```bash
npm run typecheck
npm run build
```

## Deploy ke Vercel

Pastikan project Vercel terhubung ke repository GitHub ini, root directory mengarah ke root repository, dan framework preset **Next.js**. Di **Project Settings → Environment Variables**, tambahkan variabel berikut untuk **Production** dan, jika digunakan, **Preview**:

- `DB_HOST` — host **Session pooler** dari Supabase.
- `DB_PORT=5432`
- `DB_DATABASE=postgres`
- `DB_USERNAME` — user **Session pooler** dari Supabase.
- `DB_PASSWORD` — password database Supabase. Masukkan hanya langsung di dashboard Vercel, jangan di commit atau dibagikan lewat chat.
- `DB_SSLMODE=require`
- `DB_SCHEMA=public`

Sebagai alternatif, atur `DB_URL` memakai connection string **Session pooler** lengkap. Terapkan [`database/schema.next.sql`](./database/schema.next.sql) sekali ke database yang akan digunakan, lalu buat deployment baru atau **Redeploy** deployment terbaru agar variabel lingkungan diterapkan. Tabel untuk Supabase yang digunakan aplikasi ini sudah disiapkan; data laporan, jadwal, dan ide tersimpan di Supabase.

## Route

| Route | Halaman |
|---|---|
| `/` | Laporan Sosmed |
| `/laporan-sosmed` | Laporan Sosmed |
| `/content-planner` | Content Planner |
| `/ide-konten` | Ide Konten |
| `/pengaturan` | Pengaturan |

API Next.js tersedia di `/api/reports`, `/api/schedules`, dan `/api/ideas`.
