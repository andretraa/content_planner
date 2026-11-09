# CreatorPulse - Social Media Report Dashboard

Dashboard laporan sosial media multi-platform yang dibangun dengan Laravel.

## Fitur

- **Laporan Sosmed** - Overview analytics dengan grafik performa, distribusi platform, dan konten terbaik
- **Content Planner** - Kalender konten dengan drag-and-drop scheduling
- **Ide Konten** - Kanban board untuk brainstorming dan ideation
- **Pengaturan** - Manajemen akun dan preferensi

## Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js (opsional)

### Setup

```bash
# Clone atau masuk ke direktori project
cd creator-pulse

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Jalankan server development
php artisan serve
```

Buka browser dan akses `http://localhost:8000`

### Supabase (PostgreSQL)

Aplikasi menyimpan laporan, jadwal, dan ide konten menggunakan database Laravel. Untuk menggunakan Supabase, pilih **Session pooler** pada Supabase Dashboard > Project Settings > Database (atau koneksi PostgreSQL yang dapat dijangkau dari hosting), lalu atur `.env` lokal:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=host-pooler-dari-supabase
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=username-pooler-dari-supabase
DB_PASSWORD=password-database-supabase
DB_SSLMODE=require
DB_SCHEMA=public
```

Jalankan `php artisan migrate --force` satu kali setelah mengatur koneksi. Migrasi membuat tabel `reports`, `schedules`, dan `ideas`; jika file JSON lokal lama tersedia, record akan diimpor dengan ID yang sama. Jangan commit atau membagikan kredensial database.

## Deploy ke Vercel

Project Laravel ini menggunakan `Dockerfile.vercel` dan FrankenPHP agar Vercel menjalankan PHP, bukan menyajikan `public/index.php` sebagai file statis.

1. Pastikan root directory project Vercel menunjuk ke root repository dan pilih framework preset **Container** pada pengaturan Build & Development.
2. Tambahkan environment variables di Vercel:
   - `APP_KEY`: hasilkan dengan `php artisan key:generate --show` secara lokal, lalu simpan nilainya hanya di pengaturan Vercel.
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://contentplanner-ashy.vercel.app`
   - `DB_CONNECTION=pgsql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dari koneksi Supabase (gunakan pooler yang sesuai untuk aplikasi serverless).
   - `DB_SSLMODE=require`
   - `DB_SCHEMA=public`
3. Deploy ulang dari branch yang terhubung.

Sebelum deploy, jalankan `php artisan migrate --force` satu kali dari lingkungan yang memiliki akses ke database Supabase. File `.env` lokal, database SQLite, data JSON, cache, dan log tidak disalin ke Docker image; data production disimpan di Supabase. Image memasang ekstensi PDO PostgreSQL untuk koneksi tersebut.

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS (CDN), Chart.js
- **Font**: Poppins (Google Fonts)

## Struktur Halaman

| Route | Halaman |
|-------|---------|
| `/` | Laporan Sosmed (default) |
| `/laporan-sosmed` | Overview Analytics |
| `/content-planner` | Content Calendar |
| `/ide-konten` | Content Ideation Board |
| `/pengaturan` | Settings |
