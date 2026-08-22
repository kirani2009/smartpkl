# SmartPKL API Documentation

> **Base URL:** `http://localhost:8000/api` (development)  
> **Auth:** Laravel Sanctum (Bearer token)  
> **Format:** JSON  
> **Version:** 1.0.0

---

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Error Handling](#error-handling)
- [Endpoints](#endpoints)
  - [Health](#health)
  - [Auth](#auth)
  - [School](#school)
  - [Major](#major)
  - [Student Profile](#student-profile)
  - [Student Skills](#student-skills)
  - [Student Portfolio](#student-portfolio)
  - [Student Documents](#student-documents)
  - [Student Certificates](#student-certificates)
  - [Student Internships](#student-internships)
  - [Student Applications](#student-applications)
  - [Student Saved Internships](#student-saved-internships)
  - [Student Interviews](#student-interviews)
  - [Smart Matching](#smart-matching)
  - [Teacher Profile](#teacher-profile)
  - [Teacher Dashboard](#teacher-dashboard)
  - [Teacher Partnerships](#teacher-partnerships)
  - [Teacher Applications](#teacher-applications)
  - [Teacher Monitoring](#teacher-monitoring)
  - [Teacher Reports](#teacher-reports)
  - [Company Profile](#company-profile)
  - [Company Dashboard](#company-dashboard)
  - [Company Partnerships](#company-partnerships)
  - [Company Internships](#company-internships)
  - [Company Applications](#company-applications)
  - [Company Selection](#company-selection)
  - [Company Interviews](#company-interviews)
  - [Company Reports](#company-reports)
  - [Admin Reports](#admin-reports)
  - [Notifications](#notifications)
  - [Profile](#profile)

---

## Overview

### Response Format

All endpoints return a consistent JSON structure:

```json
{
  "success": true,
  "message": "Human readable message",
  "data": { ... },
  "errors": null
}
```

### Pagination

Paginated endpoints return:

```json
{
  "success": true,
  "message": "...",
  "data": {
    "items": [ ... ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    }
  }
}
```

**Query params:** `?page=1&per_page=15`

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict (duplicate) |
| 422 | Validation Error |
| 429 | Too Many Requests |

---

## Authentication

### Register

```
POST /api/auth/register
```

**Rate limit:** `throttle:register`

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | ✅ | max:255 |
| `email` | string | ✅ | unique:users,email |
| `password` | string | ✅ | min:8, confirmed |
| `password_confirmation` | string | ✅ | must match password |
| `role` | string | ✅ | `student`, `teacher`, or `company` |

> ⚠️ Admin cannot self-register (created via seeder only)

**Response (201):**

```json
{
  "success": true,
  "message": "Registrasi berhasil.",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Budi Siswa",
      "email": "budi@example.com",
      "role": "student"
    }
  }
}
```

---

### Login

```
POST /api/auth/login
```

**Rate limit:** `throttle:login`

**Body:**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Response (200):**

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Budi Siswa",
      "email": "budi@example.com",
      "role": "student"
    }
  }
}
```

**Error (401):**

```json
{
  "success": false,
  "message": "Kredensial tidak valid.",
  "data": null,
  "errors": null
}
```

---

### Logout

```
POST /api/auth/logout
```

**Auth:** Required (Bearer token)

**Response (200):**

```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

---

### Current User

```
GET /api/me
```

**Auth:** Required

**Response (200):**

```json
{
  "success": true,
  "message": "Data user berhasil diambil.",
  "data": {
    "id": 1,
    "name": "Budi Siswa",
    "email": "budi@example.com",
    "role": "student"
  }
}
```

---

## Health

```
GET /api/health
```

**Auth:** Not required

**Response (200):**

```json
{
  "success": true,
  "message": "SmartPKL API is running.",
  "data": {
    "app": "Laravel",
    "version": "1.0.0",
    "time": "2026-08-22 10:00:00"
  }
}
```

---

## School

```
GET    /api/schools              — List all schools
GET    /api/schools/{school}     — School detail
POST   /api/schools              — Create school (admin only)
PUT    /api/schools/{school}     — Update school (admin only)
DELETE /api/schools/{school}     — Delete school (admin only)
```

**Auth:** `GET` requires login; `POST/PUT/DELETE` requires `admin` role

**Query params (GET /api/schools):** `?page=1&per_page=15`

**Response item:**

```json
{
  "id": 1,
  "name": "SMKN 1 Jakarta",
  "npsn": "20109876",
  "address": "Jl. Pemuda No. 10",
  "city": "Jakarta Timur",
  "phone": "021-4891234",
  "email": "info@smkn1jakarta.sch.id",
  "logo": null,
  "description": "Sekolah menengah kejuruan...",
  "majors_count": 3,
  "teachers_count": 1,
  "students_count": 45,
  "created_at": "2026-08-22T03:40:45.000000Z",
  "updated_at": "2026-08-22T03:40:45.000000Z"
}
```

---

## Major

```
GET    /api/schools/{school}/majors  — List majors for a school
POST   /api/schools/{school}/majors  — Create major (teacher/admin)
PUT    /api/majors/{major}           — Update major (teacher/admin)
DELETE /api/majors/{major}           — Delete major (teacher/admin)
```

**Auth:** `GET` requires login; `POST/PUT/DELETE` requires `teacher` (own school) or `admin`

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `name` | string | ✅ |
| `code` | string | ❌ |
| `description` | string | ❌ |

---

## Student Profile

```
GET  /api/me/student       — Get own profile
POST /api/me/student       — Create profile
PUT  /api/me/student       — Update profile
```

**Auth:** `student` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `school_id` | integer | ✅ |
| `major_id` | integer | ❌ |
| `nis` | string | ❌ |
| `class` | string | ❌ |
| `entry_year` | integer | ❌ |
| `gender` | string | ❌ (`male`/`female`) |
| `birth_date` | date | ❌ |
| `phone` | string | ❌ |
| `address` | string | ❌ |
| `interests` | string | ❌ |

**Response item:**

```json
{
  "id": 1,
  "user_id": 10,
  "school_id": 1,
  "major_id": 2,
  "nis": "2024001",
  "class": "XII RPL 1",
  "entry_year": 2023,
  "gender": "male",
  "birth_date": "2006-05-15",
  "phone": "081234567890",
  "address": "Jl. Contah No. 1",
  "interests": "React, Laravel, Figma",
  "school": { "id": 1, "name": "SMKN 1 Jakarta" },
  "major": { "id": 2, "name": "Rekayasa Perangkat Lunak" },
  "skills": [ { "id": 1, "name": "PHP", "level": "intermediate" } ],
  "portfolios": [],
  "documents": [],
  "certificates": []
}
```

---

## Student Skills

```
GET    /api/me/student/skills           — List my skills
POST   /api/me/student/skills           — Add skill
PUT    /api/me/student/skills           — Sync skills (replace all)
DELETE /api/me/student/skills/{skill}   — Remove skill
```

**Auth:** `student` role

**Body (POST):**

| Field | Type | Required |
|-------|------|----------|
| `skill_id` | integer | ✅ |
| `level` | string | ❌ (`beginner`/`intermediate`/`advanced`) |

**Body (PUT — sync):**

```json
{
  "skills": [
    { "skill_id": 1, "level": "advanced" },
    { "skill_id": 5, "level": "beginner" }
  ]
}
```

---

## Student Portfolio

```
GET    /api/me/student/portfolios                  — List portfolios
POST   /api/me/student/portfolios                  — Create portfolio
GET    /api/me/student/portfolios/{portfolio}      — Portfolio detail
PUT    /api/me/student/portfolios/{portfolio}      — Update portfolio
DELETE /api/me/student/portfolios/{portfolio}      — Delete portfolio
```

**Auth:** `student` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `title` | string | ✅ |
| `description` | string | ❌ |
| `url` | string | ❌ |
| `file_path` | string | ❌ |

---

## Student Documents

```
GET    /api/me/student/documents              — List documents
POST   /api/me/student/documents              — Upload document
DELETE /api/me/student/documents/{document}   — Delete document
```

**Auth:** `student` role

**Body (POST):**

| Field | Type | Required |
|-------|------|----------|
| `type` | string | ❌ (`CV`/`PORTFOLIO`/`OTHER`) |
| `title` | string | ✅ |
| `file` | file | ✅ |

---

## Student Certificates

```
GET    /api/me/student/certificates                     — List certificates
POST   /api/me/student/certificates                     — Add certificate
GET    /api/me/student/certificates/{certificate}       — Certificate detail
PUT    /api/me/student/certificates/{certificate}       — Update certificate
DELETE /api/me/student/certificates/{certificate}       — Delete certificate
```

**Auth:** `student` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `title` | string | ✅ |
| `issuer` | string | ❌ |
| `issued_at` | date | ❌ |
| `file_path` | string | ❌ |

---

## Student Internships

```
GET    /api/student/internships                    — Browse published internships
GET    /api/student/internships/{internship}       — Internship detail
POST   /api/student/internships/{internship}/apply — Apply to internship
```

**Auth:** `student` role

**Query params (GET index):** `?q=search&major_id=1&page=1&per_page=15`

**Body (POST apply):**

| Field | Type | Required |
|-------|------|----------|
| `message` | string | ❌ (cover letter, max:2000) |

**Response item (index):**

```json
{
  "id": 1,
  "company_id": 1,
  "title": "Frontend Developer Intern",
  "position": "Frontend Developer",
  "description": "...",
  "quota": 3,
  "location": "Jakarta Selatan",
  "period_start": "2026-09-22",
  "period_end": "2026-12-22",
  "status": "PUBLISHED",
  "applications_count": 5,
  "requirements": [
    { "id": 1, "description": "Mahasiswa jurusan RPL" }
  ],
  "skills": [
    { "id": 1, "name": "React" },
    { "id": 2, "name": "JavaScript" }
  ],
  "school": null,
  "major": { "id": 2, "name": "Rekayasa Perangkat Lunak" },
  "company": {
    "id": 1,
    "profile": {
      "name": "PT TechCorp Indonesia",
      "industry": "Technology",
      "city": "Jakarta Selatan"
    }
  }
}
```

---

## Student Applications

```
GET    /api/student/applications                   — My applications
GET    /api/student/applications/{application}     — Application detail
```

**Auth:** `student` role

**Query params:** `?status=PENDING&page=1&per_page=15`

**Status values:** `PENDING`, `REVIEWED`, `INTERVIEW`, `ACCEPTED`, `REJECTED`

**Response item:**

```json
{
  "id": 1,
  "internship_id": 1,
  "student_id": 1,
  "status": "PENDING",
  "message": "Saya tertarik...",
  "rating": null,
  "selection_notes": null,
  "applied_at": "2026-08-22T03:40:54.000000Z",
  "internship": { "id": 1, "title": "Frontend Dev", "company": { ... } },
  "student": { "id": 1, "user": { "name": "Rina" }, "school": { ... } },
  "status_histories": [
    {
      "id": 1,
      "status": "PENDING",
      "note": "Lamaran dikirim.",
      "changed_by_user": { "name": "Rina Wulandari" },
      "created_at": "2026-08-22T03:40:54.000000Z"
    }
  ]
}
```

---

## Student Saved Internships

```
GET    /api/student/saved-internships                        — List saved
POST   /api/student/internships/{internship}/save            — Save internship
DELETE /api/student/internships/{internship}/save            — Unsave internship
```

**Auth:** `student` role

---

## Student Interviews

```
GET    /api/student/interviews                — List interviews
GET    /api/student/interviews/{interview}    — Interview detail
```

**Auth:** `student` role

**Response item:**

```json
{
  "id": 1,
  "application_id": 1,
  "scheduled_at": "2026-09-01T10:00:00.000000Z",
  "mode": "onsite",
  "location": "Kantor PT TechCorp",
  "notes": "Bawa laptop",
  "status": "SCHEDULED"
}
```

---

## Smart Matching

```
GET    /api/student/matchings                            — Get recommendations
GET    /api/student/matchings/{internship}/detail        — Match detail
```

**Auth:** `student` role

**Response item:**

```json
{
  "internship": {
    "id": 1,
    "title": "Frontend Developer Intern",
    "company": { "name": "PT TechCorp" },
    "skills": [ { "name": "React" }, { "name": "JavaScript" } ]
  },
  "match_score": 18.8,
  "match_breakdown": {
    "major": 0,
    "skills": 8.8,
    "interest": 0,
    "location": 0,
    "period": 10
  },
  "match_weights": {
    "major": 30,
    "skills": 35,
    "interest": 15,
    "location": 10,
    "period": 10
  }
}
```

---

## Teacher Profile

```
GET    /api/me/teacher     — Get own profile
POST   /api/me/teacher     — Create profile
PUT    /api/me/teacher     — Update profile
```

**Auth:** `teacher` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `school_id` | integer | ✅ |
| `nip` | string | ❌ |
| `position` | string | ❌ |
| `phone` | string | ❌ |

---

## Teacher Dashboard

```
GET /api/teacher/dashboard
```

**Auth:** `teacher` role

**Response:**

```json
{
  "school": { "id": 1, "name": "SMKN 1 Jakarta" },
  "students": { "total": 45, "placed": 30, "without_internship": 15 },
  "partnerships": { "total": 5, "active": 3, "pending": 2 },
  "recent_applications": [
    {
      "id": 1,
      "status": "PENDING",
      "applied_at": "2026-08-20T10:00:00.000000Z",
      "student_name": "Rina Wulandari",
      "internship_title": "Frontend Developer Intern"
    }
  ]
}
```

---

## Teacher Partnerships

```
GET    /api/teacher/partnerships/companies  — Search companies
POST   /api/teacher/partnerships            — Create partnership request
GET    /api/teacher/partnerships            — List my partnerships
GET    /api/teacher/partnerships/{id}       — Partnership detail
```

**Auth:** `teacher` role

**Query params (companies):** `?q=search`

**Body (POST):**

| Field | Type | Required |
|-------|------|----------|
| `company_id` | integer | ✅ |

**Response item:**

```json
{
  "id": 1,
  "school_id": 1,
  "company_id": 1,
  "status": "PENDING",
  "created_at": "2026-08-22T03:40:52.000000Z",
  "company": {
    "id": 1,
    "profile": {
      "name": "PT TechCorp Indonesia",
      "industry": "Technology",
      "city": "Jakarta Selatan"
    }
  },
  "requester": { "id": 1, "name": "Pak Budi Santoso" }
}
```

---

## Teacher Applications

```
GET    /api/teacher/applications/stats        — Application stats
GET    /api/teacher/applications              — List applications
GET    /api/teacher/applications/{id}         — Application detail
```

**Auth:** `teacher` role

---

## Teacher Monitoring

```
GET    /api/teacher/monitoring/overview       — Monitoring overview
GET    /api/teacher/monitoring/students       — List students
GET    /api/teacher/monitoring/students/{id}  — Student detail
```

**Auth:** `teacher` role

---

## Teacher Reports

```
GET    /api/teacher/reports/placement        — Placement report
GET    /api/teacher/reports/no-internship    — Students without internship
GET    /api/teacher/reports/applications     — Application stats
```

**Auth:** `teacher` role

---

## Company Profile

```
GET    /api/me/company      — Get own profile
POST   /api/me/company      — Create profile
PUT    /api/me/company      — Update profile
```

**Auth:** `company` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `name` | string | ✅ |
| `industry` | string | ❌ |
| `address` | string | ❌ |
| `city` | string | ❌ |
| `phone` | string | ❌ |
| `email` | string | ❌ |
| `website` | string | ❌ |
| `description` | string | ❌ |
| `established_year` | integer | ❌ |
| `employee_count` | integer | ❌ |

---

## Company Dashboard

```
GET /api/company/dashboard
```

**Auth:** `company` role

**Response:**

```json
{
  "company": { "id": 1, "name": "PT TechCorp", "status": "active" },
  "partnerships": { "total": 3, "active": 2, "pending": 1 },
  "internship_listings": { "total": 5, "draft": 1, "published": 4, "closed": 0 },
  "applicants": {
    "total": 20, "pending": 5, "reviewed": 8, "interview": 2, "accepted": 4, "rejected": 1
  },
  "recent_applications": [ ... ]
}
```

---

## Company Partnerships

```
GET    /api/company/partnerships                     — List partnerships
GET    /api/company/partnerships/{id}                — Partnership detail
PUT    /api/company/partnerships/{id}/accept         — Accept partnership
PUT    /api/company/partnerships/{id}/reject         — Reject partnership
```

**Auth:** `company` role

---

## Company Internships

```
GET    /api/company/internships                  — List my internships
POST   /api/company/internships                  — Create internship
GET    /api/company/internships/{id}             — Internship detail
PUT    /api/company/internships/{id}             — Update internship
DELETE /api/company/internships/{id}             — Delete internship
```

**Auth:** `company` role

**Body (POST/PUT):**

| Field | Type | Required |
|-------|------|----------|
| `title` | string | ✅ |
| `position` | string | ✅ |
| `description` | string | ✅ |
| `school_id` | integer | ❌ (null = all schools) |
| `major_id` | integer | ❌ |
| `quota` | integer | ❌ (default: 1) |
| `location` | string | ❌ |
| `period_start` | date | ✅ |
| `period_end` | date | ✅ |
| `allowance` | decimal | ❌ |
| `facilities` | string | ❌ |
| `status` | string | ❌ (`DRAFT`/`PUBLISHED`) |
| `requirements` | array | ❌ |
| `skills` | array | ❌ (skill IDs) |

**Response item:**

```json
{
  "id": 1,
  "company_id": 1,
  "title": "Frontend Developer Intern",
  "position": "Frontend Developer",
  "description": "...",
  "quota": 3,
  "location": "Jakarta Selatan",
  "period_start": "2026-09-22",
  "period_end": "2026-12-22",
  "status": "PUBLISHED",
  "applications_count": 5,
  "requirements": [ ... ],
  "skills": [ ... ],
  "school": null,
  "major": { "name": "Rekayasa Perangkat Lunak" }
}
```

---

## Company Applications

```
GET    /api/company/applications                        — List applicants
GET    /api/company/applications/{id}                   — Application detail
PUT    /api/company/applications/{id}/status            — Update status
POST   /api/company/applications/{id}/interview         — Schedule interview
```

**Auth:** `company` role

**Body (PUT status):**

| Field | Type | Required |
|-------|------|----------|
| `status` | string | ✅ (`REVIEWED`/`INTERVIEW`/`ACCEPTED`/`REJECTED`) |
| `note` | string | ❌ |

**Body (POST interview):**

| Field | Type | Required |
|-------|------|----------|
| `scheduled_at` | datetime | ✅ |
| `mode` | string | ❌ (`onsite`/`online`, default: `onsite`) |
| `location` | string | ❌ |
| `notes` | string | ❌ |

---

## Company Selection

```
GET    /api/company/selection/stats                — Selection statistics
GET    /api/company/selection/top-applicants       — Top applicants
PUT    /api/company/applications/{id}/rate         — Rate application
PUT    /api/company/selection/batch                — Batch update status
```

**Auth:** `company` role

**Body (PUT rate):**

| Field | Type | Required |
|-------|------|----------|
| `rating` | integer | ✅ (1-5) |
| `selection_notes` | string | ❌ |

**Body (PUT batch):**

```json
{
  "application_ids": [1, 2, 3],
  "status": "ACCEPTED",
  "note": "Semua diterima"
}
```

---

## Company Interviews

```
GET    /api/company/interviews                  — List interviews
GET    /api/company/interviews/{id}             — Interview detail
PUT    /api/company/interviews/{id}/complete    — Mark as completed
PUT    /api/company/interviews/{id}/cancel      — Cancel interview
```

**Auth:** `company` role

---

## Company Reports

```
GET    /api/company/reports/applications        — Application report
GET    /api/company/reports/internships         — Internship report
GET    /api/company/reports/partnerships        — Partnership report
```

**Auth:** `company` role

---

## Admin Reports

```
GET    /api/admin/reports/overview              — Platform overview
GET    /api/admin/reports/placement             — Placement report
GET    /api/admin/reports/no-internship         — Students without internship
GET    /api/admin/reports/partnerships          — Partnership report
GET    /api/admin/reports/internships           — Internship report
GET    /api/admin/reports/applications          — Application report
```

**Auth:** `admin` role

**Response (overview):**

```json
{
  "users": { "total": 100, "students": 80, "teachers": 10, "companies": 8, "admins": 2 },
  "schools": { "total": 5, "with_partnerships": 4 },
  "companies": { "total": 8, "active": 7, "suspended": 1 },
  "partnerships": { "total": 12, "active": 8, "pending": 3, "rejected": 1 },
  "internships": { "total": 20, "published": 15, "draft": 3, "closed": 2 },
  "applications": { "total": 50, "pending": 10, "accepted": 25, "rejected": 15 }
}
```

---

## Notifications

```
GET    /api/notifications                            — List notifications
GET    /api/notifications/unread-count               — Unread count
GET    /api/notifications/{id}                       — Notification detail
PUT    /api/notifications/{id}/read                  — Mark as read
PUT    /api/notifications/read-all                   — Mark all as read
DELETE /api/notifications/{id}                       — Delete notification
```

**Auth:** Any authenticated user

**Response item:**

```json
{
  "id": 1,
  "type": "APPLICATION_RECEIVED",
  "title": "Lamaran Baru",
  "message": "Rina Wulandari telah melamar ke lowongan Frontend Developer Intern.",
  "data": { "application_id": 1 },
  "read_at": null,
  "created_at": "2026-08-22T03:40:54.000000Z"
}
```

---

## Profile (Update)

```
PUT /api/me
```

**Auth:** Any authenticated user

**Body:**

| Field | Type | Required |
|-------|------|----------|
| `name` | string | ❌ |
| `email` | string | ❌ |
| `password` | string | ❌ (min:8, confirmed) |

---

## Role-Based Access Summary

| Endpoint Group | student | teacher | company | admin |
|----------------|:-------:|:-------:|:-------:|:-----:|
| Auth (register/login/logout/me) | ✅ | ✅ | ✅ | ✅ |
| Schools (GET) | ✅ | ✅ | ✅ | ✅ |
| Schools (CRUD) | ❌ | ❌ | ❌ | ✅ |
| Student Profile | ✅ | ❌ | ❌ | ❌ |
| Teacher Profile | ❌ | ✅ | ❌ | ❌ |
| Company Profile | ❌ | ❌ | ✅ | ❌ |
| Browse Internships | ✅ | ✅ | ❌ | ❌ |
| Apply to Internship | ✅ | ❌ | ❌ | ❌ |
| Manage Internships | ❌ | ❌ | ✅ | ❌ |
| Partnerships (teacher) | ❌ | ✅ | ❌ | ❌ |
| Partnerships (company) | ❌ | ❌ | ✅ | ❌ |
| Applications (student) | ✅ | ❌ | ❌ | ❌ |
| Applications (company) | ❌ | ❌ | ✅ | ❌ |
| Applications (teacher) | ❌ | ✅ | ❌ | ❌ |
| Interviews (student) | ✅ | ❌ | ❌ | ❌ |
| Interviews (company) | ❌ | ❌ | ✅ | ❌ |
| Smart Matching | ✅ | ❌ | ❌ | ❌ |
| Notifications | ✅ | ✅ | ✅ | ✅ |
| Admin Reports | ❌ | ❌ | ❌ | ✅ |
