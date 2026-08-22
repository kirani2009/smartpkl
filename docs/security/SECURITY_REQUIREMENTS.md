# SmartPKL — Security Requirements

> Documented berdasarkan security requirements dari guru.
> Terakhir diperbarui: 2026-08-22

---

## 1. Security Audit Checklist

Sebelum deployment ke shared hosting, aplikasi **WAJIB** diaudit terhadap:

| # | Vulnerability | Status | Catatan |
|---|---------------|--------|---------|
| 1 | SQL Injection | ⬜ Belum di-audit | Gunakan Eloquent/Query Builder, jangan raw SQL |
| 2 | XSS (Cross-Site Scripting) | ⬜ Belum di-audit | React auto-escapes; backend validate semua input |
| 3 | CORS | ✅ Dikonfigurasi | `config/cors.php` — restrict di production |
| 4 | CSRF | ✅ Proteksi bawaan Laravel | Stateless API menggunakan Sanctum, tidak pakai session cookie |
| 5 | DoS/DDoS & Abuse | ⬜ Belum di-audit | Rate limiting sudah ada di auth; tambah untuk API lain |
| 6 | MITM | ✅ | Gunakan HTTPS di production |
| 7 | URL Interpretation / Open Redirect | ⬜ Belum di-audit | Validasi URL input, jangan redirect ke user-provided URLs |
| 8 | Session Hijacking | ✅ | Sanctum token-based, bukan session cookie |
| 9 | Brute Force | ✅ Sebagian | Rate limiting di login/register; tambah limit di endpoint lain |
| 10 | IDOR / Unauthorized Access | ✅ Mitigated | Controller gunakan `$request->user()->*` untuk akses resource sendiri |
| 11 | Privilege Escalation | ✅ Mitigated | Role middleware, admin tidak bisa self-register |
| 12 | File Upload Vulnerability | ⬜ Belum di-audit | Validate MIME, size, jangan izinkan executable |
| 13 | Mass Assignment | ✅ Mitigated | `$fillable` dibatasi field, FormRequest validate input |
| 14 | Password / Credential Security | ✅ | Hashed (bcrypt), hidden from serialization, no password in API response |
| 15 | Error Information Leakage | ⬜ Belum di-audit | `APP_DEBUG=false` di production |
| 16 | Dependency Vulnerability | ⬜ Belum di-audit | Jalankan `composer audit` dan `npm audit` |

**Status legenda:**
- ✅ = Sudah di-mitigate atau dikonfigurasi
- ⬜ = Belum di-audit (akan dilakukan sebelum deployment)
- 🔴 = Vulnerability ditemukan, perlu diperbaiki

---

## 2. Secure Coding Rules

### 2.1 Authentication & Authorization

- Selalu gunakan `auth:sanctum` middleware untuk endpoint terproteksi
- Selalu gunakan `role:xxx` middleware untuk role-based access
- Jangan pernah trust `user_id` dari request body — selalu dari `$request->user()->id`
- Selalu pastikan user hanya mengakses resource miliknya sendiri
- Jangan expose password, token, atau secret dalam response

### 2.2 Input Validation

- Selalu gunakan Form Request untuk validasi input
- Selalu validasi di backend, jangan trust frontend validation
- Gunakan `exists` rule untuk foreign keys
- Gunakan `max` rule untuk string length
- Gunakan `in` rule untuk enum/limited values

### 2.3 Mass Assignment

- `$fillable` model harus minimal — hanya field yang boleh diisi user
- Jangan pernah `$fillable`.includes: `role`, `id`, `user_id` (untuk create), `verified_at`, `status` (privilege fields)
- Selalu gunakan Form Request `validated()` untuk create/update

### 2.4 SQL Injection

- Gunakan Eloquent ORM atau Query Builder
- Jangan pernah `DB::raw()` dengan input user
- Jangan gunakan string interpolation dalam query

### 2.5 XSS Prevention

- Frontend: React auto-escapes JSX — jangan gunakan `dangerouslySetInnerHTML`
- Backend: Output di JSON API, bukan HTML
- Sanitize semua input string

### 2.6 File Upload

- Validasi MIME type menggunakan Laravel validation rules
- Validasi ukuran file (max 2MB untuk foto, max 5MB untuk dokumen)
- Gunakan `Storage::putFile()` — bukan nama file dari user
- Jangan izinkan executable files (`.php`, `.exe`, `.sh`)
- Gunakan signed URLs untuk file access

### 2.7 CORS

- Development: `allowed_origins => ['*']` (OK untuk local)
- Production: **WAJIB** restrict ke domain yang benar
- Jangan gunakan `allowed_origins => ['*']` di production

### 2.8 Rate Limiting

- Login: 5 attempts per minute
- Register: 3 per minute
- API lain: pertimbangkan rate limiting untuk endpoint write-heavy

### 2.9 Error Handling

- Production: `APP_DEBUG=false`
- Jangan expose stack trace, SQL queries, atau internal error details
- Gunakan `response()->json()` untuk error responses

### 2.10 Password Security

- Minimum 8 characters
- Gunakan `Hash::make()` (bcrypt), jangan `md5`/`sha1`
- Password di-hidden dari API response (`$hidden` array)
- Gunakan `password_confirmation` field

---

## 3. Pre-Deployment Security Audit

### 3.1 Automated Checks

```bash
# Composer dependencies
composer audit

# NPM dependencies
cd frontend && npm audit

# PHP security checker
composer require sensitivity/security-checker
php vendor/bin/security-checker security:check composer.lock
```

### 3.2 Manual Checks

- [ ] `APP_DEBUG=false` di `.env` production
- [ ] `APP_ENV=production` di `.env` production
- [ ] Database credentials aman (bukan default)
- [ ] `APP_KEY` sudah di-generate
- [ ] CORS restricted ke production domain
- [ ] HTTPS enforced
- [ ] Rate limiting aktif di semua auth endpoints
- [ ] File upload validation aktif
- [ ] Error handler tidak expose stack trace
- [ ] Sanctum tokens expire time dikonfigurasi
- [ ] No sensitive data in logs

### 3.3 Security Scan Tools

- OWASP ZAP (dynamic scan)
- Nmap (port scan)
- SSL Labs (SSL/TLS check)

---

## 4. Security Incident Response

Jika vulnerability ditemukan selama development:

1. **Catat** di file ini
2. **Perbaiki** segera jika bisa dilakukan tanpa mengubah banyak bagian
3. **Test** untuk memastikan fix tidak breaking
4. **Document** perubahan yang dilakukan
5. **Jangan deploy** sampai semua vulnerability di-fix

---

## 5. Known Issues

| Date | Issue | Severity | Status | Fix |
|------|-------|----------|--------|-----|
| — | Belum ada | — | — | — |

---

*Document ini WAJIB di-update setiap kali security requirement berubah atau vulnerability ditemukan.*
