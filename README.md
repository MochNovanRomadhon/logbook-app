# 📓 Logbook App

Aplikasi manajemen logbook dan tugas pegawai berbasis Laravel + Filament.

---

## ⚙️ Cara Instalasi (Setelah Clone)

> **⚠️ PENTING:** Jangan lewati langkah `migrate:fresh --seed`. Tanpa langkah ini, semua menu kecuali Dasbor **tidak akan muncul** karena sistem role belum terbuat.

### 1. Install dependencies
```bash
composer install
npm install
```

### 2. Siapkan environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi database di `.env`
```env
DB_DATABASE=logbook_app
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Jalankan migrasi + seeder ✅ WAJIB
```bash
php artisan migrate:fresh --seed
```

### 5. Reset cache permission (opsional, jika menu masih tidak muncul)
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

### 6. Jalankan server
```bash
php artisan serve
```

Akses aplikasi di: **http://localhost:8000**

---

## 🔐 Akun Default (Setelah Seeder)

| Role | Email | Password | Akses |
|------|-------|----------|-------|
| Super Admin | `admin@log.com` | `password` | Semua menu |
| Pengawas | `pengawas@log.com` | `password` | Monitoring, Dasbor |
| Pegawai | `pegawai@log.com` | `password` | Tugas, Logbook |

---

## 🗂️ Struktur Role & Menu

| Menu | Super Admin | Pengawas | Pegawai |
|------|-------------|---------|---------|
| Dasbor | ✅ | ✅ | ✅ |
| Monitoring Tugas | ✅ | ✅ | ❌ |
| Monitoring Logbook | ✅ | ✅ | ❌ |
| Daftar Tugas | ❌ | ✅ | ✅ |
| Logbook Harian | ❌ | ✅ | ✅ |
| Master Data | ✅ | ❌ | ❌ |
| Manajemen Pengguna | ✅ | ❌ | ❌ |

---

## 🛠️ Tech Stack

- **Backend:** Laravel 11
- **Admin Panel:** Filament v3
- **Role & Permission:** Spatie Laravel Permission
- **Database:** MySQL
