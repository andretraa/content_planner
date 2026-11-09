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

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS (CDN), Chart.js
- **Font**: Inter (Google Fonts)

## Struktur Halaman

| Route | Halaman |
|-------|---------|
| `/` | Laporan Sosmed (default) |
| `/laporan-sosmed` | Overview Analytics |
| `/content-planner` | Content Calendar |
| `/ide-konten` | Content Ideation Board |
| `/pengaturan` | Settings |

