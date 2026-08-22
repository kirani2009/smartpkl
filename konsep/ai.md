{
"project": {
"name": "SmartPKL",
"version": "1.0.0",
"type": "school-company-internship-platform",
"description": "Platform digital untuk menghubungkan sekolah, guru, siswa, dan perusahaan dalam proses kerja sama, pencarian, pendaftaran, seleksi, penempatan, dan monitoring Praktik Kerja Lapangan (PKL).",
"main_concept": "Sekolah/guru bekerja sama dengan perusahaan yang ingin menerima siswa PKL. Perusahaan membuka kebutuhan atau lowongan PKL, siswa dari sekolah melihat dan mendaftar, perusahaan melakukan seleksi, kemudian guru memantau hasil penempatan siswa.",
"development_method": "Vibe Coding",
"development_environment": "Local Computer",
"deployment_target": "Shared Hosting",
"database": "MySQL",
"backend": "Laravel",
"web_frontend": "ReactJS + Tailwind CSS",
"mobile_frontend": "Flutter",
"storage_strategy": "Local/shared-hosting storage, bukan S3",
"server_strategy": "Shared Hosting, bukan VPS",
"cloud_strategy": "Tidak menggunakan cloud infrastructure pada tahap awal"
},

"important_rules": {
"read_instruction_files": true,
"instruction_directory": "docs/ai/",
"main_instruction_file": "docs/ai/MASTER.json",
"development_mode": "phase-by-phase",
"do_not_build_everything_at_once": true,
"do_not_skip_phases": true,
"do_not_change_architecture_without_permission": true,
"do_not_delete_existing_features_without_permission": true,
"do_not_install_unnecessary_packages": true,
"do_not_use_s3": true,
"do_not_require_vps": true,
"do_not_require_docker": true,
"do_not_require_kubernetes": true,
"do_not_require_redis": true,
"do_not_require_queue_server": true,
"do_not_require_external_cloud_storage": true,
"prioritize_shared_hosting_compatibility": true
},

"instruction_files": {
"MASTER": "docs/ai/MASTER.json",
"PRODUCT": "docs/ai/PRODUCT.json",
"ROLES": "docs/ai/ROLES.json",
"WORKFLOW": "docs/ai/WORKFLOW.json",
"DATABASE": "docs/ai/DATABASE.json",
"API": "docs/ai/API.json",
"BACKEND": "docs/ai/BACKEND.json",
"WEB": "docs/ai/WEB.json",
"MOBILE": "docs/ai/MOBILE.json",
"UI_UX": "docs/ai/UI_UX.json",
"AUTH": "docs/ai/AUTH.json",
"PARTNERSHIP": "docs/ai/PARTNERSHIP.json",
"INTERNSHIP": "docs/ai/INTERNSHIP.json",
"APPLICATION": "docs/ai/APPLICATION.json",
"NOTIFICATION": "docs/ai/NOTIFICATION.json",
"SMART_MATCHING": "docs/ai/SMART_MATCHING.json",
"REPORTING": "docs/ai/REPORTING.json",
"SHARED_HOSTING": "docs/ai/SHARED_HOSTING.json",
"TESTING": "docs/ai/TESTING.json",
"SECURITY": "docs/ai/SECURITY.json",
"CODING_RULES": "docs/ai/CODING_RULES.json",
"VIBECODING": "docs/ai/VIBECODING.json",
"ROADMAP": "docs/ai/ROADMAP.json"
},

"architecture": {
"overview": [
"Flutter Mobile App",
"ReactJS Web Application",
"Laravel REST API",
"MySQL Database",
"Shared Hosting"
],
"flow": "Flutter/React -> Laravel REST API -> MySQL",
"backend_role": "Source of truth untuk business logic, authentication, authorization, validation, dan database operation.",
"frontend_role": "Presentation layer dan user interaction.",
"mobile_role": "Mobile application terutama untuk siswa.",
"database_role": "Menyimpan seluruh data aplikasi.",
"storage_role": "Menyimpan file upload menggunakan storage lokal Laravel yang kompatibel dengan shared hosting."
},

"roles": {
"student": {
"label": "Siswa",
"purpose": "Mencari dan mendaftar PKL.",
"main_features": [
"Register",
"Login",
"Profile",
"School information",
"Major",
"Skills",
"Certificates",
"Portfolio",
"CV",
"Search internship",
"Filter internship",
"View internship detail",
"Save internship",
"Apply internship",
"Application tracking",
"Interview information",
"Notifications",
"Recommended internships",
"Smart matching",
"Internship history"
]
},

```
"teacher": {
  "label": "Guru",
  "purpose": "Mengelola siswa dan menjadi penghubung antara sekolah dengan perusahaan.",
  "main_features": [
    "Login",
    "Teacher dashboard",
    "Student monitoring",
    "Company discovery",
    "Partnership request",
    "Partnership management",
    "View company partners",
    "View internship listings",
    "Monitor applications",
    "Monitor student placement",
    "Monitor ongoing internships",
    "Reports",
    "Notifications"
  ],
  "important_rule": "Guru tidak mengambil keputusan akhir seleksi siswa. Keputusan penerimaan atau penolakan dilakukan oleh perusahaan."
},

"company": {
  "label": "Perusahaan",
  "purpose": "Bekerja sama dengan sekolah dan menerima serta menyeleksi siswa PKL.",
  "main_features": [
    "Register",
    "Login",
    "Company profile",
    "Partnership management",
    "Accept/reject partnership request",
    "Create internship listing",
    "Define internship requirements",
    "Set quota",
    "Set period",
    "View applicants",
    "View student profiles",
    "View CV",
    "View portfolio",
    "Select students",
    "Reject students",
    "Optional online interview",
    "Notifications",
    "Internship monitoring"
  ],
  "important_rule": "Perusahaan adalah pihak yang menentukan siswa diterima atau ditolak."
},

"admin": {
  "label": "Admin",
  "purpose": "Mengelola keseluruhan platform.",
  "main_features": [
    "Dashboard",
    "User management",
    "School management",
    "Teacher management",
    "Student management",
    "Company management",
    "Partnership management",
    "Internship management",
    "Application monitoring",
    "Report management",
    "System settings"
  ]
}
```

},

"main_business_flow": {
"step_1": "Sekolah/guru memiliki akun.",
"step_2": "Guru mencari perusahaan yang ingin menerima siswa PKL.",
"step_3": "Guru mengirim permintaan kerja sama kepada perusahaan.",
"step_4": "Perusahaan menerima atau menolak kerja sama.",
"step_5": "Jika diterima, partnership menjadi ACTIVE.",
"step_6": "Perusahaan membuat kebutuhan/lowongan PKL.",
"step_7": "Siswa melihat lowongan dari perusahaan partner sekolah.",
"step_8": "Siswa memilih dan mendaftar.",
"step_9": "Perusahaan melihat data siswa.",
"step_10": "Perusahaan melakukan seleksi.",
"step_11": "Jika diperlukan, perusahaan mengadakan interview online.",
"step_12": "Perusahaan menentukan ACCEPTED atau REJECTED.",
"step_13": "Siswa mendapatkan notifikasi.",
"step_14": "Guru mendapatkan informasi penempatan.",
"step_15": "Siswa menjalani PKL.",
"step_16": "Guru memonitor status PKL.",
"step_17": "Status berubah menjadi COMPLETED setelah PKL selesai."
},

"core_features": [
"Authentication",
"Role Based Access",
"School Management",
"Student Management",
"Company Management",
"School Company Partnership",
"Internship Listing",
"Internship Search",
"Internship Filter",
"Student Application",
"Application Status",
"Company Selection",
"Optional Online Interview",
"Teacher Monitoring",
"Notification",
"Smart Matching",
"Recommendation",
"Reporting"
],

"non_goals": [
"Do not turn SmartPKL into a general job marketplace.",
"Do not require students to pay to search for internships.",
"Do not require S3.",
"Do not require VPS.",
"Do not require Docker.",
"Do not require Kubernetes.",
"Do not require complex microservices.",
"Do not require complicated AI infrastructure.",
"Do not make teachers decide company selection results.",
"Do not make interviews mandatory for every company."
],

"database_entities": [
"users",
"schools",
"teachers",
"students",
"companies",
"company_profiles",
"school_company_partnerships",
"majors",
"skills",
"student_skills",
"internship_listings",
"internship_requirements",
"applications",
"application_status_histories",
"interviews",
"saved_internships",
"documents",
"certificates",
"portfolios",
"notifications",
"reports"
],

"application_status": [
"PENDING",
"REVIEWED",
"INTERVIEW",
"ACCEPTED",
"REJECTED"
],

"partnership_status": [
"PENDING",
"ACCEPTED",
"REJECTED",
"EXPIRED"
],

"internship_status": [
"DRAFT",
"PUBLISHED",
"CLOSED",
"EXPIRED"
],

"student_internship_status": [
"ACCEPTED",
"PREPARATION",
"ONGOING",
"COMPLETED"
],

"smart_matching": {
"enabled": true,
"mvp_type": "rule_based_scoring",
"do_not_use_complex_ai": true,
"parameters": [
"major",
"skills",
"interest",
"location",
"internship_period",
"company_requirements"
],
"output": "match_percentage",
"example": "95% cocok dengan profil kamu"
},

"monetization": {
"student": "Free",
"school": [
"Free",
"Pro"
],
"company": [
"Free",
"Pro"
],
"payment_gateway": "Optional future feature",
"priority": "After MVP",
"rule": "Do not implement complex payment infrastructure during initial MVP."
},

"deployment": {
"development": "Local Computer",
"production": "Shared Hosting",
"database": "MySQL",
"storage": "Laravel local/public storage",
"s3": false,
"vps": false,
"docker": false,
"kubernetes": false,
"microservices": false,
"redis": false,
"queue_server": false,
"deployment_priority": "Shared hosting compatibility"
},

"development_phases": [
{
"phase": 1,
"name": "Project Foundation",
"tasks": [
"Laravel setup",
"React setup",
"Tailwind setup",
"MySQL connection",
"API structure",
"Sanctum setup",
"Folder structure",
"Environment configuration"
]
},
{
"phase": 2,
"name": "Authentication and Roles",
"tasks": [
"Register",
"Login",
"Logout",
"Role middleware",
"User profile"
]
},
{
"phase": 3,
"name": "Database Core",
"tasks": [
"Migration",
"Models",
"Relationships",
"Seeders",
"Factories"
]
},
{
"phase": 4,
"name": "School and Teacher",
"tasks": [
"School profile",
"Teacher dashboard",
"Student management"
]
},
{
"phase": 5,
"name": "Company",
"tasks": [
"Company profile",
"Company dashboard",
"Company verification"
]
},
{
"phase": 6,
"name": "Partnership",
"tasks": [
"Search company",
"Partnership request",
"Accept/reject partnership",
"Partnership status"
]
},
{
"phase": 7,
"name": "Internship Listing",
"tasks": [
"Create listing",
"Edit listing",
"Publish listing",
"Close listing",
"Requirements",
"Quota"
]
},
{
"phase": 8,
"name": "Student Profile",
"tasks": [
"Student profile",
"Skills",
"Certificate",
"Portfolio",
"CV"
]
},
{
"phase": 9,
"name": "Application",
"tasks": [
"Search",
"Filter",
"View detail",
"Apply",
"Saved internships",
"Application tracking"
]
},
{
"phase": 10,
"name": "Company Selection",
"tasks": [
"Applicant list",
"Student profile",
"Review",
"Accept",
"Reject"
]
},
{
"phase": 11,
"name": "Interview",
"tasks": [
"Schedule interview",
"Online meeting link",
"Interview notification"
]
},
{
"phase": 12,
"name": "Teacher Monitoring",
"tasks": [
"Student placement",
"Application monitoring",
"Internship monitoring"
]
},
{
"phase": 13,
"name": "Notification",
"tasks": [
"Application notification",
"Partnership notification",
"Interview notification",
"Acceptance notification"
]
},
{
"phase": 14,
"name": "Smart Matching",
"tasks": [
"Scoring algorithm",
"Match percentage",
"Recommendation"
]
},
{
"phase": 15,
"name": "Reporting",
"tasks": [
"Student report",
"Company report",
"Partnership report",
"Internship report",
"PDF",
"Excel"
]
},
{
"phase": 16,
"name": "Flutter",
"tasks": [
"API integration",
"Student mobile app",
"Authentication",
"Internship search",
"Application",
"Notification"
]
},
{
"phase": 17,
"name": "Testing",
"tasks": [
"Backend testing",
"API testing",
"Web testing",
"Mobile testing",
"Role testing"
]
},
{
"phase": 18,
"name": "Shared Hosting Deployment",
"tasks": [
"Production environment",
"MySQL setup",
"Build React",
"Laravel deployment",
"Storage configuration",
"Environment configuration",
"Migration",
"Testing"
]
}
],

"ai_behavior": {
"role": "Senior Full Stack Developer",
"development_style": "Incremental Vibe Coding",
"must_inspect_existing_project_before_changes": true,
"must_explain_changes": true,
"must_identify_files_before_editing": true,
"must_not_make_unrelated_changes": true,
"must_not_overengineer": true,
"must_prioritize_mvp": true,
"must_keep_shared_hosting_compatibility": true,
"must_preserve_existing_working_features": true
},

"response_format": {
"when_developing_feature": [
"Goal",
"Current project inspection",
"Files to create",
"Files to modify",
"Database changes",
"Backend implementation",
"API implementation",
"Frontend implementation",
"Testing",
"How to run",
"Expected result",
"Possible errors"
],
"coding_rule": "Always identify exact file path before giving code.",
"complete_code_rule": "When a file needs substantial modification, provide the complete relevant file content rather than an unclear fragment."
},

"first_instruction": {
"action": "Start PHASE 1 only.",
"do_not_continue_automatically": true,
"first_tasks": [
"Inspect current project structure.",
"Determine whether Laravel project already exists.",
"Determine whether React is already configured.",
"Determine whether Tailwind CSS is already configured.",
"Determine whether MySQL is connected.",
"Determine whether Flutter project already exists.",
"Do not overwrite existing configuration without checking."
]
}
}

---

FILE: docs/ai/MASTER.json

{
"purpose": "Master instruction untuk seluruh proyek SmartPKL.",
"rules": [
"SmartPKL berfokus pada hubungan Sekolah, Perusahaan, dan Siswa.",
"Guru menjadi penghubung sekolah dengan perusahaan.",
"Perusahaan menjadi pihak yang menentukan siswa diterima atau ditolak.",
"Siswa menggunakan sistem untuk mencari dan mendaftar PKL.",
"Backend Laravel adalah sumber utama business logic.",
"React dan Flutter hanya berkomunikasi melalui API.",
"MySQL digunakan sebagai database.",
"Development dilakukan di komputer lokal.",
"Deployment ditargetkan ke shared hosting.",
"Jangan menggunakan S3.",
"Jangan membuat kebutuhan VPS.",
"Jangan menggunakan Docker jika tidak diperlukan.",
"Jangan menggunakan Kubernetes.",
"Jangan menggunakan microservices.",
"Jangan membuat arsitektur terlalu kompleks.",
"Kerjakan fitur secara bertahap."
],
"golden_rule": "Jangan membuat fitur hanya karena terlihat keren. Setiap fitur harus memiliki manfaat nyata bagi siswa, guru, perusahaan, atau admin."
}

---

FILE: docs/ai/PRODUCT.json

{
"product_name": "SmartPKL",
"product_type": "Platform PKL",
"value_proposition": "Mempermudah sekolah bekerja sama dengan perusahaan dan membantu siswa mendapatkan tempat PKL melalui proses digital yang terstruktur.",
"main_problem": [
"Guru kesulitan mengelola banyak siswa.",
"Informasi tempat PKL tersebar.",
"Perusahaan kesulitan mendapatkan siswa sesuai kebutuhan.",
"Proses pendaftaran masih menggunakan WhatsApp atau formulir manual.",
"Guru sulit mengetahui status siswa.",
"Perusahaan kesulitan menyeleksi siswa."
],
"solution": [
"Partnership sekolah dan perusahaan.",
"Lowongan PKL terpusat.",
"Profil siswa.",
"Pendaftaran online.",
"Seleksi perusahaan.",
"Tracking lamaran.",
"Monitoring guru.",
"Notifikasi.",
"Smart Matching."
]
}

---

FILE: docs/ai/ROLES.json

{
"student": {
"can": [
"view partner internships",
"search internships",
"filter internships",
"apply",
"save internship",
"view own application",
"manage own profile",
"manage own CV",
"manage own portfolio",
"view own notification"
],
"cannot": [
"manage company",
"manage partnership",
"decide application result",
"access teacher dashboard",
"access admin dashboard"
]
},
"teacher": {
"can": [
"manage school students",
"search company",
"create partnership request",
"view partnership",
"monitor student application",
"monitor student placement",
"view reports"
],
"cannot": [
"accept student on behalf of company",
"reject student on behalf of company",
"change company decision"
]
},
"company": {
"can": [
"manage company profile",
"accept partnership",
"reject partnership",
"create internship",
"manage internship",
"view applicants",
"view student profile",
"accept applicant",
"reject applicant",
"schedule optional interview"
]
},
"admin": {
"can": [
"manage all users",
"manage schools",
"manage companies",
"manage partnerships",
"manage internships",
"monitor applications",
"manage reports"
]
}
}

---

FILE: docs/ai/WORKFLOW.json

{
"partnership": [
"Teacher searches company",
"Teacher sends partnership request",
"Company receives request",
"Company accepts or rejects",
"If accepted, partnership becomes ACTIVE"
],
"internship": [
"Company with active partnership creates internship",
"Company specifies school eligibility",
"Internship published",
"Students can view internship"
],
"application": [
"Student opens internship detail",
"Student submits application",
"Application becomes PENDING",
"Company reviews student",
"Company may schedule interview",
"Company accepts or rejects",
"System notifies student",
"Teacher can monitor result"
],
"internship_period": [
"ACCEPTED",
"PREPARATION",
"ONGOING",
"COMPLETED"
]
}

---

FILE: docs/ai/DATABASE.json

{
"principles": [
"Gunakan normalisasi database yang wajar.",
"Gunakan foreign key.",
"Gunakan index pada kolom pencarian dan foreign key penting.",
"Gunakan unique constraint jika diperlukan.",
"Gunakan timestamps.",
"Gunakan soft delete hanya jika memang dibutuhkan.",
"Jangan membuat tabel duplicate."
],
"tables": [
"users",
"schools",
"teachers",
"students",
"companies",
"company_profiles",
"school_company_partnerships",
"majors",
"skills",
"student_skills",
"internship_listings",
"internship_requirements",
"applications",
"application_status_histories",
"interviews",
"saved_internships",
"documents",
"certificates",
"portfolios",
"notifications",
"reports"
],
"relationship_rules": [
"One school has many students.",
"One school has many teachers.",
"One school has many company partnerships.",
"One company can have many school partnerships.",
"One company can have many internship listings.",
"One internship can have many applications.",
"One student can have many applications.",
"One student can have many skills.",
"One skill can belong to many students.",
"One application has many status histories.",
"One application may have zero or one active interview."
]
}

---

FILE: docs/ai/API.json

{
"style": "REST API",
"base": "/api",
"authentication": "Laravel Sanctum",
"rules": [
"Gunakan route API yang konsisten.",
"Gunakan Form Request untuk validasi.",
"Gunakan API Resource untuk response.",
"Gunakan HTTP status code yang sesuai.",
"Jangan menaruh business logic kompleks di controller.",
"Gunakan service class jika logic sudah kompleks.",
"Pastikan setiap endpoint memiliki authorization."
],
"response_structure": {
"success": true,
"message": "Human readable message",
"data": {},
"errors": null
}
}

---

FILE: docs/ai/BACKEND.json

{
"framework": "Laravel",
"rules": [
"Gunakan MVC.",
"Gunakan Eloquent.",
"Gunakan Form Request.",
"Gunakan API Resource.",
"Gunakan Policies atau Gates untuk authorization.",
"Gunakan middleware role.",
"Gunakan Service Layer jika business logic kompleks.",
"Gunakan database transaction untuk proses yang membutuhkan atomicity.",
"Jangan menaruh query kompleks berulang di controller.",
"Gunakan eager loading untuk menghindari N+1 query."
]
}

---

FILE: docs/ai/WEB.json

{
"framework": "ReactJS",
"styling": "Tailwind CSS",
"rules": [
"Gunakan reusable component.",
"Gunakan React Router.",
"Gunakan Axios atau API client yang konsisten.",
"Pisahkan API service dari UI component.",
"Gunakan loading state.",
"Gunakan empty state.",
"Gunakan error state.",
"Gunakan responsive design.",
"Jangan membuat component terlalu besar."
],
"main_pages": [
"/",
"/internships",
"/internships/:id",
"/student/dashboard",
"/student/profile",
"/student/applications",
"/student/interviews",
"/teacher/dashboard",
"/teacher/students",
"/teacher/partnerships",
"/teacher/reports",
"/company/dashboard",
"/company/partnerships",
"/company/internships",
"/company/applicants",
"/admin/dashboard"
]
}

---

FILE: docs/ai/MOBILE.json

{
"framework": "Flutter",
"priority": "Student experience",
"features": [
"Login",
"Dashboard",
"Profile",
"Internship search",
"Internship detail",
"Apply",
"Application status",
"Interview information",
"Notifications",
"Saved internships"
],
"rules": [
"Gunakan API Laravel.",
"Jangan membuat business logic berbeda dari backend.",
"Gunakan reusable widgets.",
"Handle loading, error, and empty states.",
"Simpan authentication token secara aman.",
"Pastikan UI responsive terhadap berbagai ukuran layar."
]
}

---

FILE: docs/ai/UI_UX.json

{
"style": [
"Modern",
"Clean",
"Professional",
"Friendly",
"Simple",
"Responsive"
],
"student_style": "Lebih sederhana, modern, dan mudah dipahami siswa.",
"teacher_style": "Dashboard informatif dan fokus pada monitoring.",
"company_style": "Professional dan fokus pada applicant management.",
"admin_style": "Data-oriented dashboard.",
"components": [
"Button",
"Input",
"Select",
"Modal",
"Card",
"Badge",
"Table",
"SearchBar",
"Filter",
"Pagination",
"Sidebar",
"Navbar",
"Notification",
"StatusBadge",
"EmptyState",
"LoadingState",
"ErrorState"
],
"rule": "Jangan membuat desain berbeda-beda untuk fitur yang sama."
}

---

FILE: docs/ai/AUTH.json

{
"authentication": "Laravel Sanctum",
"features": [
"Register",
"Login",
"Logout",
"Current user",
"Password hashing",
"Role authorization"
],
"roles": [
"student",
"teacher",
"company",
"admin"
],
"rules": [
"Password harus di-hash.",
"User hanya dapat mengakses resource sesuai role.",
"Authorization harus diperiksa di backend.",
"Jangan hanya menyembunyikan tombol frontend.",
"Token harus dikelola dengan aman."
]
}

---

FILE: docs/ai/PARTNERSHIP.json

{
"purpose": "Mengelola hubungan resmi sekolah dengan perusahaan.",
"workflow": [
"Teacher finds company",
"Teacher sends request",
"Company reviews request",
"Company accepts or rejects",
"Partnership becomes active if accepted"
],
"statuses": [
"PENDING",
"ACCEPTED",
"REJECTED",
"EXPIRED"
],
"rules": [
"Hanya partnership ACTIVE yang dapat digunakan untuk lowongan khusus sekolah.",
"Teacher dapat mengajukan partnership.",
"Company yang menentukan apakah partnership diterima.",
"Admin dapat melakukan monitoring."
]
}

---

FILE: docs/ai/INTERNSHIP.json

{
"purpose": "Mengelola kebutuhan siswa PKL dari perusahaan partner.",
"fields": [
"title",
"description",
"position",
"major",
"quota",
"period_start",
"period_end",
"location",
"allowance",
"facilities",
"requirements",
"skills",
"status"
],
"statuses": [
"DRAFT",
"PUBLISHED",
"CLOSED",
"EXPIRED"
],
"rules": [
"Company membuat internship.",
"Company menentukan kuota.",
"Company menentukan persyaratan.",
"Company menentukan periode.",
"Student hanya dapat apply jika memenuhi aturan lowongan.",
"Lowongan harus berasal dari perusahaan yang memiliki hubungan yang valid dengan sekolah terkait."
]
}

---

FILE: docs/ai/APPLICATION.json

{
"purpose": "Mengelola pendaftaran siswa ke lowongan PKL.",
"statuses": [
"PENDING",
"REVIEWED",
"INTERVIEW",
"ACCEPTED",
"REJECTED"
],
"rules": [
"Satu siswa tidak boleh membuat application duplicate pada lowongan yang sama.",
"Student hanya dapat melihat application miliknya.",
"Company hanya dapat melihat application yang berkaitan dengan internship miliknya.",
"Teacher dapat memonitor application siswa dari sekolahnya.",
"Company adalah pihak yang menentukan ACCEPTED atau REJECTED.",
"Setiap perubahan status harus dicatat di application_status_histories."
]
}

---

FILE: docs/ai/NOTIFICATION.json

{
"events": [
"Partnership request received",
"Partnership accepted",
"Partnership rejected",
"Application submitted",
"Application reviewed",
"Interview scheduled",
"Application accepted",
"Application rejected",
"Internship reminder",
"New recommended internship"
],
"implementation": {
"initial": "Database notification",
"email": "Optional",
"push": "Future Flutter feature",
"whatsapp": "Future feature"
},
"shared_hosting_rule": "Jangan membuat notification system yang membutuhkan infrastructure server tambahan pada tahap awal."
}

---

FILE: docs/ai/SMART_MATCHING.json

{
"purpose": "Memberikan rekomendasi lowongan PKL yang sesuai dengan siswa.",
"type": "Rule based scoring",
"inputs": [
"student_major",
"student_skills",
"student_interest",
"student_location",
"internship_major",
"internship_skills",
"internship_location",
"internship_period"
],
"example_weights": {
"major": 30,
"skills": 35,
"interest": 15,
"location": 10,
"period": 10
},
"output": "0-100 match percentage",
"rule": "Scoring harus transparan dan mudah diubah. Jangan memasukkan machine learning pada MVP."
}

---

FILE: docs/ai/REPORTING.json

{
"reports": [
"Student placement",
"Students without internship",
"Company partnership",
"Internship listing",
"Application",
"Accepted students",
"Rejected students"
],
"formats": [
"Web",
"PDF",
"Excel"
],
"rules": [
"Teacher hanya dapat melihat data sekolahnya.",
"Company hanya dapat melihat data yang berkaitan dengan perusahaan.",
"Admin dapat melihat data platform."
]
}

---

FILE: docs/ai/SHARED_HOSTING.json

{
"target": "Shared Hosting",
"development": "Local Computer",
"rules": [
"Jangan membutuhkan VPS.",
"Jangan membutuhkan Docker.",
"Jangan membutuhkan Kubernetes.",
"Jangan membutuhkan S3.",
"Jangan membutuhkan Redis server.",
"Jangan membutuhkan background worker yang membutuhkan server khusus.",
"Gunakan MySQL.",
"Gunakan storage Laravel yang kompatibel dengan shared hosting.",
"Pastikan konfigurasi .env mudah dipindahkan.",
"Gunakan production build untuk React.",
"Pastikan Laravel dapat dijalankan pada shared hosting dengan PHP version yang sesuai."
],
"deployment_flow": [
"Develop locally",
"Test locally",
"Build frontend",
"Prepare Laravel production",
"Upload project to shared hosting",
"Configure environment",
"Configure MySQL",
"Run migration",
"Configure storage",
"Test production"
]
}

---

FILE: docs/ai/TESTING.json

{
"levels": [
"Unit",
"Feature",
"API",
"Frontend",
"Mobile",
"Manual"
],
"critical_tests": [
"Student cannot access teacher dashboard.",
"Teacher cannot accept/reject student.",
"Company can accept/reject applicant.",
"Student cannot apply twice.",
"Company cannot edit another company internship.",
"Teacher can only monitor students from own school.",
"Company can only view relevant applicants.",
"Partnership must be active before restricted internship creation.",
"Application status history is recorded."
]
}

---

FILE: docs/ai/SECURITY.json

{
"requirements": [
"Authentication",
"Authorization",
"Role middleware",
"Validation",
"Password hashing",
"File validation",
"Upload size limits",
"MIME validation",
"SQL injection prevention",
"XSS prevention",
"CSRF protection where applicable",
"Rate limiting where necessary"
],
"critical_rule": "Security must be enforced by Laravel backend, not only frontend."
}

---

FILE: docs/ai/CODING_RULES.json

{
"rules": [
"Use clear naming.",
"Use consistent naming convention.",
"Do not duplicate business logic.",
"Do not duplicate components.",
"Do not create unnecessary abstraction.",
"Keep controllers thin.",
"Use Form Requests.",
"Use API Resources.",
"Use Policies for authorization.",
"Use Services for complex business logic.",
"Use migrations for database changes.",
"Do not manually modify production database structure.",
"Do not delete existing working features without permission.",
"Do not install packages unless necessary.",
"Explain why a package is needed.",
"Check existing dependencies before installing new ones."
]
}

---

FILE: docs/ai/VIBECODING.json

{
"method": "Incremental development",
"rules": [
"Selalu baca instruction file yang relevan sebelum coding.",
"Periksa project structure sebelum membuat file.",
"Jangan mengasumsikan file belum ada.",
"Jangan overwrite konfigurasi tanpa memeriksa.",
"Kerjakan satu fitur dalam satu waktu.",
"Setelah fitur selesai, lakukan testing.",
"Jika error, cari root cause.",
"Jangan memperbaiki error dengan mengubah banyak bagian yang tidak berkaitan.",
"Jangan melanjutkan phase jika fitur sebelumnya belum stabil.",
"Selalu jelaskan file yang dibuat atau diubah.",
"Selalu berikan command yang harus dijalankan.",
"Selalu berikan expected result.",
"Jika terdapat pilihan arsitektur, pilih yang paling sederhana dan cocok untuk shared hosting."
],
"preferred_prompt_style": "Instruksi spesifik berdasarkan file dan phase, bukan permintaan membuat seluruh aplikasi sekaligus."
}

---

FILE: docs/ai/ROADMAP.json

{
"phase_1": "Foundation",
"phase_2": "Authentication",
"phase_3": "Database",
"phase_4": "School Teacher",
"phase_5": "Company",
"phase_6": "Partnership",
"phase_7": "Internship",
"phase_8": "Student Profile",
"phase_9": "Application",
"phase_10": "Selection",
"phase_11": "Interview",
"phase_12": "Teacher Monitoring",
"phase_13": "Notification",
"phase_14": "Smart Matching",
"phase_15": "Reporting",
"phase_16": "Flutter",
"phase_17": "Testing",
"phase_18": "Shared Hosting Deployment"
}

