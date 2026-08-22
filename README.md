# SmartPKL

Platform digital untuk menghubungkan **sekolah, guru, siswa, dan perusahaan** dalam proses kerja sama, pencarian, pendaftaran, seleksi, penempatan, dan monitoring **Praktik Kerja Lapangan (PKL)**.

## Konsep Utama

Sekolah/guru bekerja sama dengan perusahaan yang ingin menerima siswa PKL. Perusahaan membuka kebutuhan/lowongan PKL, siswa melihat dan mendaftar, perusahaan melakukan seleksi, kemudian guru memantau hasil penempatan siswa.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 10 (REST API) |
| Database | MySQL (MariaDB) |
| Web Frontend | ReactJS + Tailwind CSS (Vite) |
| Mobile Frontend | Flutter (Phase 16) |
| Authentication | Laravel Sanctum |
| Deployment | Shared Hosting (lokal untuk development) |

> ⚠️ **Penting:** PHP di lingkungan ini adalah **8.1.25**, sehingga menggunakan **Laravel 10** (Laravel 11 membutuhkan PHP ≥ 8.2).

## Struktur Proyek

```
SmartPKL/
│
├── app/                 # Backend Laravel (Models, Http, Services)
├── database/            # Migrations, Seeders, Factories
├── routes/              # routes/api.php (REST API)
├── resources/           # View & asset Laravel
├── frontend/            # Web ReactJS + Tailwind CSS
├── mobile/              # (Flutter - Phase 16, folder dibuat saat fase tersebut)
├── docs/ai/             # File instruksi pengembangan (JSON)
└── konsep/              # Dokumen konsep awal
```

Dokumentasi lengkap instruksi pengembangan berada di `docs/ai/` (lihat `docs/ai/MASTER.json`).

## Database (Phase 3 ✅)

22 tabel sesuai `docs/ai/DATABASE.json` (normalisasi wajar, foreign key, index, unique constraint):

| Kelompok | Tabel |
|---|---|
| Profil | `users`, `schools`, `teachers`, `students`, `companies`, `company_profiles`, `majors` |
| Master | `skills`, `student_skills` (pivot), `internship_skills` (pivot) |
| Bisnis | `school_company_partnerships`, `internship_listings`, `internship_requirements`, `applications`, `application_status_histories`, `interviews`, `saved_internships` |
| Siswa | `documents`, `certificates`, `portfolios` |
| Pendukung | `user_notifications`, `reports` |

> 📝 `user_notifications` (bukan `notifications`) sengaja dinamai berbeda agar tidak bentrok dengan tabel notifikasi bawaan Laravel (trait `Notifiable`). `internship_skills` dibutuhkan oleh Smart Matching (docs/ai/SMART_MATCHING.json).

Seluruh relasi tersedia sebagai method Eloquent di `app/Models/` (mis. `School->teachers()`, `Company->internshipListings()`, `Application->statusHistories()`).
Constraint penting: siswa tidak bisa apply 2x ke lowongan sama, partnership unik per pasangan (sekolah, perusahaan), cascade delete dari `users` ke profil.

## Cara Menjalankan (Development)

### Backend (Laravel API)

```bash
# Install dependency
composer install

# Konfigurasi environment (sesuaikan DB_USERNAME/DB_PASSWORD)
cp .env.example .env
php artisan key:generate

# Setup database (MySQL/MariaDB harus berjalan)
# Catatan: DB_USERNAME/DB_PASSWORD di .env default-nya untuk development lokal (XAMPP: root tanpa password).
# Saat deployment ke shared hosting, wajib ganti dengan kredensial MySQL hosting.
mysql -u root -e "CREATE DATABASE IF NOT EXISTS smartpkl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrasi
php artisan migrate

# Jalankan server API
php artisan serve
```

API tersedia di `http://127.0.0.1:8000/api`.

### Phase 2 — Authentication & Roles (selesai ✅)

Semua response mengikuti struktur `{ success, message, data, errors }`.
Autentikasi memakai **Bearer token** (Laravel Sanctum).

| Method | Endpoint | Deskripsi | Akses |
|---|---|---|---|
| POST | `/api/auth/register` | Registrasi (name, email, password, role) | Publik |
| POST | `/api/auth/login` | Login, mengembalikan token (rate limit 5/menit/IP) | Publik |
| POST | `/api/auth/logout` | Logout & mencabut token | Auth |
| GET | `/api/me` | Profil user yang sedang login | Auth |
| PUT | `/api/me` | Perbarui profil sendiri (nama, email, password) | Auth |

Role yang valid: `student`, `teacher`, `company`, `admin`.
Registrasi publik hanya menerima `student`, `teacher`, `company` — **admin tidak bisa mendaftar sendiri** (anti privilege escalation; akun admin dibuat via seeder).
Otorisasi per role tersedia via middleware `role:student,teacher,...` (`EnsureUserHasRole`).
Rate limiting: login 5 percobaan/menit/IP, register 10 request/menit/IP.

Contoh request:

```bash
# Register
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"name":"Budi","email":"budi@example.com","password":"password123","password_confirmation":"password123","role":"student"}'

# Login (simpan token dari response)
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"budi@example.com","password":"password123"}'

# Akses endpoint terproteksi
curl http://127.0.0.1:8000/api/me -H 'Authorization: Bearer <TOKEN>' -H 'Accept: application/json'
```

### Frontend (React + Tailwind)

```bash
cd frontend
npm install
npm run dev        # Development server
npm run build      # Production build -> frontend/dist
```

## Pengembangan

Pengembangan dilakukan **bertahap (phase-by-phase)** sesuai `docs/ai/ROADMAP.json`:

1. Foundation ✅
2. Authentication & Roles ✅
3. Database Core ✅
4. School & Teacher
5. Company
6. Partnership
7. Internship Listing
8. Student Profile
9. Application
10. Company Selection
11. Interview
12. Teacher Monitoring
13. Notification
14. Smart Matching
15. Reporting
16. Flutter (Mobile)
17. Testing
18. Shared Hosting Deployment

Aturan utama: kerjakan satu fase pada satu waktu, jangan membangun semuanya sekaligus, dan jaga kompatibilitas shared hosting (tanpa S3, VPS, Docker, Redis).
