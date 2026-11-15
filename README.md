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

Isi `.env.local` dengan detail koneksi **Session pooler** Supabase (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require`, dan `DB_SCHEMA=public`), atau gunakan connection string `DB_URL`. Tambahkan URL project Supabase di `NEXT_PUBLIC_SUPABASE_URL` serta public anon/publishable key di `NEXT_PUBLIC_SUPABASE_ANON_KEY`; jangan gunakan service-role key di browser. Jangan commit atau membagikan kredensial database.

Aktifkan Email provider di Supabase Authentication. Jalankan [`database/schema.next.sql`](./database/schema.next.sql) sekali jika database belum mempunyai tabel aplikasi. Untuk tabel yang sudah ada, jalankan [`database/migrations/2026-03-26-add-user-id.sql`](./database/migrations/2026-03-26-add-user-id.sql) sebelum deploy. Migration menambahkan `user_id` kosong untuk data lama; atur `APP_OWNER_EMAIL` ke email akun yang akan memilikinya. Setelah pemilik tersebut masuk, aplikasi akan mengklaim semua baris yang belum memiliki pemilik. Tanpa `APP_OWNER_EMAIL`, data lama tetap tersembunyi sampai dipetakan secara manual. Data baru dibatasi pada akun yang sedang masuk.

Pendaftaran email dibuka dari halaman login. Akun dengan konfirmasi email aktif perlu memverifikasi alamatnya sebelum masuk.

Jika pendaftaran menampilkan `email rate limit exceeded`, Supabase sedang membatasi pengiriman email karena terlalu banyak permintaan. Jangan mengulang pendaftaran berkali-kali; tunggu hingga batasnya pulih. Untuk pemakaian rutin, admin project perlu mengatur SMTP khusus di pengaturan Authentication Supabase. Batas ini tidak dapat dihilangkan hanya dengan perubahan pada aplikasi.

### Integrasi Instagram Insights

Integrasi menggunakan **Instagram API with Instagram Login** untuk akun Instagram Professional (Business atau Creator). Buat Meta app bertipe Business, tambahkan produk Instagram, dan aktifkan izin `instagram_business_basic` serta `instagram_business_manage_insights`. Tambahkan callback URL yang sama persis dengan `INSTAGRAM_REDIRECT_URI` ke pengaturan Business Login di Meta. Untuk pengujian Development Mode, tambahkan akun Instagram sebagai tester. Penggunaan live memerlukan App Review Meta; akses ke akun yang tidak Anda kelola juga dapat memerlukan Advanced Access. Lihat panduan resmi Meta untuk [membuat app Instagram](https://developers.facebook.com/documentation/instagram-platform/create-an-instagram-app) dan [Business Login](https://developers.facebook.com/documentation/instagram-platform/instagram-api-with-instagram-login/business-login).

Jalankan [`database/migrations/2026-11-15-add-instagram-insights.sql`](./database/migrations/2026-11-15-add-instagram-insights.sql) pada database Supabase yang dipakai aplikasi. Atur `INSTAGRAM_APP_ID`, `INSTAGRAM_APP_SECRET`, `INSTAGRAM_REDIRECT_URI`, dan `INSTAGRAM_TOKEN_ENCRYPTION_KEY` di `.env.local` dan Vercel. Buat encryption key 32 byte dengan `node -e "console.log(require('crypto').randomBytes(32).toString('base64'))"`. Jangan bagikan atau commit App Secret, encryption key, maupun access token. Token Instagram disimpan terenkripsi di database dan hanya digunakan oleh server.

Setelah akun dihubungkan lewat Pengaturan, ringkasan insight akun akan disinkronkan saat halaman Laporan dibuka jika sinkronisasi terakhir lebih dari 12 jam. Tombol sinkronisasi manual tersedia di Pengaturan. Beberapa metrik mungkin kosong atau tidak tersedia menurut batasan akun/API Meta.

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
- `NEXT_PUBLIC_SUPABASE_URL` — URL project Supabase yang menyediakan Auth.
- `NEXT_PUBLIC_SUPABASE_ANON_KEY` — public anon/publishable key project tersebut; jangan gunakan service-role key.
- `INSTAGRAM_APP_ID`, `INSTAGRAM_APP_SECRET`, `INSTAGRAM_REDIRECT_URI`, dan `INSTAGRAM_TOKEN_ENCRYPTION_KEY` — kredensial server untuk Instagram Insights. Jangan beri prefix `NEXT_PUBLIC_`.
- `APP_OWNER_EMAIL` — opsional; email akun yang boleh mengklaim baris lama tanpa `user_id`.

Sebagai alternatif, atur `DB_URL` memakai connection string **Session pooler** lengkap. Terapkan schema baru atau migration yang sesuai ke database yang akan digunakan, lalu buat deployment baru atau **Redeploy** deployment terbaru agar variabel lingkungan diterapkan. Atur Email provider dan kebijakan konfirmasi pendaftaran di Supabase Authentication. Endpoint API menolak akses tanpa sesi akun yang valid.

## Route

| Route | Halaman |
|---|---|
| `/` | Laporan Sosmed |
| `/laporan-sosmed` | Laporan Sosmed |
| `/content-planner` | Content Planner |
| `/ide-konten` | Ide Konten |
| `/pengaturan` | Pengaturan |

API Next.js tersedia di `/api/reports`, `/api/schedules`, dan `/api/ideas`.
