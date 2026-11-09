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

Isi `DB_URL` di `.env.local` menggunakan connection string **Session pooler** Supabase. Jangan commit atau membagikan kredensial database. Jalankan SQL di [`database/schema.next.sql`](./database/schema.next.sql) sekali jika database belum mempunyai tabel aplikasi. Bila tabel `reports`, `schedules`, dan `ideas` sudah tersedia, aplikasi dapat langsung menggunakan tabel tersebut.

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

Hubungkan repository ke Vercel dengan root directory repository dan framework preset **Next.js**. Tambahkan variabel lingkungan berikut di Vercel untuk Production dan Preview:

- `DB_URL` — connection string PostgreSQL Supabase Session pooler.
- `DB_SSLMODE=require`
- `DB_SCHEMA=public`

Atau gunakan variabel terpisah `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Terapkan [`database/schema.next.sql`](./database/schema.next.sql) sekali ke database yang akan digunakan, lalu deploy. Jika Supabase yang sama sudah berisi tabel aplikasi, data laporan, jadwal, dan ide tetap berada di sana tanpa proses salin ulang.

## Route

| Route | Halaman |
|---|---|
| `/` | Laporan Sosmed |
| `/laporan-sosmed` | Laporan Sosmed |
| `/content-planner` | Content Planner |
| `/ide-konten` | Ide Konten |
| `/pengaturan` | Pengaturan |

API Next.js tersedia di `/api/reports`, `/api/schedules`, dan `/api/ideas`.
