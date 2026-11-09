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

## Deploy ke Vercel

Project Laravel ini menggunakan `Dockerfile.vercel` dan FrankenPHP agar Vercel menjalankan PHP, bukan menyajikan `public/index.php` sebagai file statis.

1. Pastikan root directory project Vercel menunjuk ke root repository dan pilih framework preset **Container** pada pengaturan Build & Development.
2. Tambahkan environment variables di Vercel:
   - `APP_KEY`: hasilkan dengan `php artisan key:generate --show` secara lokal, lalu simpan nilainya hanya di pengaturan Vercel.
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://contentplanner-ashy.vercel.app`
3. Deploy ulang dari branch yang terhubung.

File `.env` lokal, database SQLite, data JSON, cache, dan log tidak disalin ke Docker image. Data aplikasi saat ini disimpan di file JSON lokal, sehingga perubahan yang dibuat saat aplikasi berjalan di Vercel tidak memiliki penyimpanan persisten dan dapat hilang saat instance diganti. Gunakan database atau object storage eksternal jika data harus tetap tersimpan di production.

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
