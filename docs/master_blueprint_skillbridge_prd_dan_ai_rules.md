# MASTER BLUEPRINT SKILLBRIDGE: Requirements, Struktural Per Role, Hak Akses, Aturan, Batasan, & Protokol Kerja AI

**Proyek:** SkillBridge — Platform Pemetaan Keterampilan, Portofolio Bukti Kompetensi, dan Rekrutmen Berbasis Target Karier
**Repositori:** [HaikalMaghsin/SkillBridge](https://github.com/HaikalMaghsin/SkillBridge)
**Mata Kuliah:** Desain dan Pemrograman Web (PjBL) — Dosen Pengampu: Dimas Wahyu Wibowo, S.T., M.T.
**Tim Pengembang:** Haikal Maghsin (Ketua & Dev Utama), Marvelino Husca (Analis & Desainer), M. Javier Thufail (Sekretaris & Dokumentator), Geraldi Rama Nugraha (QA & Penguji)
**Sumber Acuan Terintegrasi:** `PRD.md`, `prd_responsive_dan_button.md`, `ai_rules_responsive_dan_button.md`, dan `Laporan Scanning & Audit Sistem SkillBridge`.

---

## 1. SYSTEM REQUIREMENTS (KEBUTUHAN FUNGSIONAL & NON-FUNGSIONAL)

### 1.1. Kebutuhan Fungsional Utama (Functional Requirements)

| Kode | Modul / Role | Deskripsi Requirement Fungsional | Sumber Data / Tabel DB |
| :--- | :--- | :--- | :--- |
| **FR-PUB-01** | Publik & Auth | Pengunjung dapat melihat informasi platform, daftar jalur karier, dan katalog skill di Landing Page (`index.php`), serta mengakses profil portofolio publik mahasiswa melalui tautan token (`p.php?token=...`). | `careers`, `skills`, `student_profiles` |
| **FR-PUB-02** | Publik & Auth | Pengguna dapat melakukan registrasi (`register.php`), login (`login.php`), dan logout (`logout.php`). Saat login berhasil, sistem wajib mengarahkan ke konsol sesuai kolom `role` (`mahasiswa`, `hr`, `admin`). Saat logout, sesi PHP dihancurkan dan cache `localStorage` dibersihkan. | `users`, `student_profiles` |
| **FR-MHS-01** | Mahasiswa | Mahasiswa dapat memilih dan mengubah satu **Target Career** aktif (`mahasiswa/career.php`) yang langsung tersimpan ke database. | `careers`, `student_profiles` |
| **FR-MHS-02** | Mahasiswa | Mahasiswa dapat mengelola daftar keahlian (**My Skills** di `mahasiswa/skills.php`: tambah, lihat, hapus) dengan label otomatis **Strong** ($\ge 2$ bukti terkait) atau **Growing** ($< 2$ bukti terkait). | `user_skills`, `evidence_skills` |
| **FR-MHS-03** | Mahasiswa | Mahasiswa dapat mengelola bukti kompetensi (**Evidence** di `mahasiswa/evidence.php`: tambah, lihat, hapus) berupa `cv`, `project`, `portfolio`, `certificate` beserta tautan URL/file dan memetakannya ke satu atau lebih skill. | `evidence`, `evidence_skills` |
| **FR-MHS-04** | Mahasiswa | Mahasiswa dapat melihat analisis **Skill Gap** (`mahasiswa/gap.php`) yang membandingkan syarat skill pada target karier aktif dengan skill yang dimiliki dalam 2 daftar: **Skills You Already Have** dan **What to Explore Next**, serta menambahkan skill langsung dari daftar eksplorasi. | `career_skill_requirements`, `user_skills` |
| **FR-MHS-05** | Mahasiswa | Mahasiswa dapat mengunggah CV (`mahasiswa/cv.php`) dan meninjau hasil pencocokan kata kunci skill (`mahasiswa/cv_review.php`) dengan status `candidate` sebelum dikonfirmasi menjadi `confirmed`. | `user_skills`, `evidence` |
| **FR-MHS-06** | Mahasiswa | Mahasiswa dapat memperbarui profil pribadi, mengatur visibilitas portofolio publik (`is_public` & `public_token`), serta mengganti password (`mahasiswa/profile.php`). | `users`, `student_profiles` |
| **FR-HR-01** | HR / Perusahaan | Recruiter dapat mengelola identitas perusahaan mitra (`hr/index.php`: nama perusahaan, industri, website, deskripsi) dan melihat metrik rekrutmen perusahaan. | `companies`, `company_recruiters` |
| **FR-HR-02** | HR / Perusahaan | Recruiter dapat melakukan CRUD posisi/kebutuhan peran perusahaan (**Target Roles** di `hr/roles.php`) yang dikaitkan dengan target karier dan spesifikasi skill wajib/pendukung. | `job_vacancies` |
| **FR-HR-03** | HR / Perusahaan | Recruiter dapat menjelajahi, memfilter (berdasarkan karier/skill), meninjau bukti portofolio asli mahasiswa, dan mengubah status seleksi kandidat (`Shortlisted`, `Interview`, `Accepted`, `Rejected`) di **Pipeline Kandidat** (`hr/candidates.php`). | `users`, `student_profiles`, `user_skills`, `evidence`, `candidate_pipelines` |
| **FR-HR-04** | HR / Perusahaan | Recruiter dapat melihat riwayat aktivitas tim (`hr/activity.php`), mengelola anggota tim HR dalam satu perusahaan (`hr/team.php`), dan memperbarui pengaturan akun HR (`hr/settings.php`). | `recruiter_activity_logs`, `company_recruiters`, `users` |
| **FR-ADM-01** | Admin Platform | Admin dapat memantau metrik platform secara *real-time* di Dashboard (`admin/index.php`): total mahasiswa, HR, master skill, karier, lowongan aktif, dan pendaftar terbaru. | Seluruh tabel utama |
| **FR-ADM-02** | Admin Platform | Admin dapat melakukan CRUD penuh pada **Master Keterampilan** (`admin/skills.php`), **Master Target Karier** (`admin/careers.php`), dan **Career-Skill Mapping** (`admin/mapping.php`). Perubahan langsung berdampak pada konsol Mahasiswa dan HR. | `skills`, `careers`, `career_skill_requirements` |
| **FR-ADM-03** | Admin Platform | Admin dapat mengelola seluruh pengguna (`admin/users.php`), mengubah role pengguna (`mahasiswa`/`hr`/`admin`), mengelola akun sesama admin (`admin/admins.php`), memoderasi lowongan HR (`admin/vacancies.php`), serta mengatur konfigurasi sistem (`admin/settings.php`). | `users`, `job_vacancies` |

### 1.2. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **NFR-01 (Persistensi Data PostgreSQL):** Seluruh operasi tulis (CRUD) wajib tersimpan secara permanen di PostgreSQL melalui lapisan REST API PHP (`public/api/`), bukan hanya di `localStorage` atau file JSON statis.
2. **NFR-02 (Desain Responsif 3 Breakpoint):** Antarmuka wajib tampil proporsional tanpa *horizontal scroll* pada level `<body>` di layar **Mobile (`< 768px`, termasuk `375px`)**, **Tablet (`768px–1023px`)**, dan **Desktop (`>= 1024px`)**.
3. **NFR-03 (Konsistensi Komponen Button):** Seluruh tombol wajib mengikuti standar class `.btn` di `public/css/style.css` dengan tinggi sentuh minimal `40px` (`44px` di layar mobile) dan atribut `type` eksplisit (`type="button"` atau `type="submit"`).
4. **NFR-04 (Keamanan Aplikasi Web):** Wajib menerapkan PDO Prepared Statements (anti-SQL Injection), escaping output `e()` / `textContent` (anti-XSS), validasi token CSRF (anti-CSRF), dan isolasi sesi antar-pengguna.

---

## 2. STRUKTURAL ARSITEKTUR PER ROLE

### 2.1. Peta Direktori Lengkap Berdasarkan Role & Lapisan Sistem

```text
SkillBridge/
├── db/
│   └── schema.sql                       # Skema tunggal PostgreSQL (3 Role: mahasiswa, hr, admin)
├── tools/                               # Script CLI internal (DILARANG di dalam public/)
│   ├── create_admin.php
│   ├── create_hr.php
│   ├── test-db.php                      # Dipindahkan dari public/ demi keamanan
│   └── diagnose.php                     # Dipindahkan dari public/ demi keamanan
├── includes/                            # Lapisan Server-Side PHP (Tertutup dari akses URL langsung)
│   ├── config/
│   │   ├── app.php                      # BASE_URL, base_url(), asset(), e()
│   │   └── db.php                       # Koneksi PDO PostgreSQL
│   ├── auth/
│   │   ├── session.php                  # Manajemen session PHP
│   │   ├── csrf.php                     # Generator & validator CSRF token
│   │   └── auth.php                     # current_user(), require_login(), require_role()
│   └── layouts/                         # Komponen Layout Terpusat per Role
│       ├── head.php                     # <!DOCTYPE html>, <head>, meta CSRF, CSS, window.APP_BASE_URL
│       ├── scripts.php                  # Pemanggil ui.js, api.js, dan $page_js
│       ├── public_header.php            # Navbar publik + tombol .navbar-toggle (hamburger)
│       ├── public_footer.php            # Footer halaman publik
│       ├── mahasiswa_header.php         # Navbar konsol Mahasiswa + tombol .navbar-toggle
│       ├── mahasiswa_footer.php         # Footer konsol Mahasiswa + scripts.php
│       ├── hr_header.php                # Topbar HR (.rec-header) + tombol .sidebar-toggle
│       ├── hr_sidebar.php               # Sidebar HR (.rec-sidebar) + .sidebar-backdrop
│       ├── hr_footer.php                # Footer konsol HR + scripts.php
│       ├── admin_header.php             # Topbar Admin (.admin-header) + tombol .sidebar-toggle
│       ├── admin_sidebar.php            # Sidebar Admin (.admin-sidebar) + .sidebar-backdrop
│       └── admin_footer.php             # Footer konsol Admin + scripts.php
└── public/                              # Document Root Web Server
    ├── index.php                        # Landing Page Publik
    ├── login.php                        # Halaman Login Multi-Role
    ├── register.php                     # Halaman Registrasi
    ├── logout.php                       # Handler Logout + Pembersih localStorage
    ├── p.php                            # Profil Portofolio Publik Mahasiswa (?token=...)
    ├── mahasiswa/                       # [ROLE MAHASISWA]
    │   ├── dashboard.php
    │   ├── skills.php
    │   ├── evidence.php
    │   ├── career.php
    │   ├── gap.php
    │   ├── profile.php
    │   ├── cv.php
    │   └── cv_review.php
    ├── hr/                              # [ROLE HR / PERUSAHAAN]
    │   ├── index.php
    │   ├── roles.php
    │   ├── candidates.php
    │   ├── activity.php
    │   ├── team.php
    │   └── settings.php
    ├── admin/                           # [ROLE ADMIN PLATFORM]
    │   ├── index.php
    │   ├── users.php
    │   ├── admins.php
    │   ├── skills.php
    │   ├── careers.php
    │   ├── mapping.php
    │   ├── vacancies.php
    │   └── settings.php
    ├── api/                             # [LAPISAN REST API PHP NATIVE]
    │   ├── skills.php                   # GET katalog skills & careers
    │   ├── my-skills.php                # GET, POST, DELETE user_skills mahasiswa
    │   ├── evidence.php                 # GET, POST, DELETE evidence & evidence_skills
    │   ├── target.php                   # GET, POST target_career_id mahasiswa
    │   ├── profile.php                  # GET, POST profil & password pengguna aktif
    │   ├── hr/
    │   │   ├── company.php              # GET, POST profil perusahaan & tim HR
    │   │   ├── roles.php                # GET, POST, PUT, DELETE lowongan/target role HR
    │   │   └── candidates.php           # GET kandidat mahasiswa riil & POST status pipeline
    │   └── admin/
    │       ├── stats.php                # GET metrik dashboard admin
    │       ├── master.php               # POST, PUT, DELETE master skills, careers, mapping
    │       ├── users.php                # GET, POST, PUT, DELETE akun users & admins
    │       └── vacancies.php            # GET, PUT moderasi lowongan perusahaan
    ├── css/
    │   ├── style.css                    # CSS Global, Mahasiswa, Button System, & Responsive Grid
    │   ├── recruiter.css                # CSS Khusus Konsol HR + Off-Canvas Drawer Mobile
    │   └── admin.css                    # CSS Khusus Konsol Admin + Off-Canvas Drawer Mobile
    └── js/
        ├── ui.js                        # Helper DOM, Toast, Modal, Hamburger & Sidebar Drawer Toggle
        ├── api.js                       # Client Fetch API ke public/api/*.php (dengan CSRF Header)
        ├── landing.js                   # Interaksi halaman publik
        ├── admin-nav.js                 # Interaksi kategori menu sidebar admin
        └── pages/                       # Script spesifik per halaman (1 halaman = 1 script)
```

### 2.2. Tabel Spesifikasi Struktural Per Role

| Komponen Struktural | Role Publik / Guest | Role Mahasiswa (`mahasiswa`) | Role HR / Perusahaan (`hr`) | Role Admin (`admin`) |
| :--- | :--- | :--- | :--- | :--- |
| **Direktori Halaman** | `public/*.php` | `public/mahasiswa/*.php` | `public/hr/*.php` | `public/admin/*.php` |
| **Guard Middleware** | Tidak ada (`guest` untuk login/register) | `require_role('mahasiswa')` | `require_role('hr')` | `require_role('admin')` |
| **Layout Header & Nav** | `public_header.php` (`<header class="navbar">`) | `mahasiswa_header.php` (`<header class="navbar">`) | `hr_sidebar.php` (`.rec-sidebar`) + `hr_header.php` (`.rec-header`) | `admin_sidebar.php` (`.admin-sidebar`) + `admin_header.php` (`.admin-header`) |
| **Layout Footer** | `public_footer.php` | `mahasiswa_footer.php` | `hr_footer.php` | `admin_footer.php` |
| **File CSS yang Dimuat** | `css/style.css` | `css/style.css` | `css/style.css` + `css/recruiter.css` | `css/style.css` + `css/admin.css` |
| **Perilaku Navigasi Mobile** | Topbar `64px` + Hamburger `.navbar-toggle` (`< 768px`) | Topbar `64px` + Hamburger `.navbar-toggle` (`< 768px`) | Off-canvas drawer `280px` + `.sidebar-backdrop` (`< 1024px`) | Off-canvas drawer `280px` + `.sidebar-backdrop` (`< 1024px`) |
| **Redirect Pasca-Login** | — | `/mahasiswa/dashboard.php` | `/hr/index.php` | `/admin/index.php` |

---

## 3. MATRIKS HAK AKSES & OTORISASI (RBAC)

### 3.1. Matriks Hak Akses Halaman & Data

| Modul / Sumber Daya | Guest (Belum Login) | Mahasiswa (`mahasiswa`) | HR / Perusahaan (`hr`) | Admin Platform (`admin`) |
| :--- | :---: | :---: | :---: | :---: |
| **Landing Page (`index.php`) & Portofolio Publik (`p.php`)** | Baca (Read) | Baca (Read) | Baca (Read) | Baca (Read) |
| **Login (`login.php`) & Register (`register.php`)** | Akses Penuh | Redirect ke Dashboard | Redirect ke Konsol HR | Redirect ke Konsol Admin |
| **Master Data (`skills`, `careers`, `career_skill_requirements`)** | Baca (via Landing) | Baca (Read) | Baca (Read) | **CRUD Penuh** |
| **Portofolio Mahasiswa (`user_skills`, `evidence`, `student_profiles`)** | Baca jika `is_public = true` | **CRUD Milik Sendiri** (`user_id = session_id`) | Baca (Read semua kandidat & bukti) | Baca & Moderasi |
| **Profil Perusahaan & Tim HR (`companies`, `company_recruiters`)** | Ditolak (403/Login) | Ditolak (403) | **CRUD Perusahaan Sendiri** | Baca & Moderasi |
| **Lowongan / Target Role HR (`job_vacancies`)** | Ditolak | Baca (Opsional) | **CRUD Milik Perusahaan Sendiri** | Baca, Moderasi Status, & Hapus |
| **Pipeline Seleksi Kandidat (`candidate_pipelines`)** | Ditolak | Ditolak (403) | **CRUD Status Kandidat** | Baca (Monitoring) |
| **Manajemen Akun Pengguna & Admin (`users`)** | Hanya Register Diri Sendiri | Update Profil & Password Sendiri | Update Profil & Password Sendiri | **CRUD Semua User & Ubah Role** |

### 3.2. Aturan Penegakan Hak Akses (Access Control Rules)

1. **Verifikasi Server-Side Wajib di Setiap Halaman & Endpoint API:**
   - Setiap halaman di `public/mahasiswa/*.php`, `public/hr/*.php`, dan `public/admin/*.php` wajib memanggil `require_role('...')` di baris paling atas sebelum me-render HTML apa pun.
   - Setiap endpoint di `public/api/**/*.php` wajib memeriksa `current_user()` dan mengembalikan HTTP `401 Unauthorized` (jika belum login) atau `403 Forbidden` (jika role tidak sesuai).
2. **Kepemilikan Data (*Row-Level Ownership*):**
   - Pada endpoint `public/api/my-skills.php`, `public/api/evidence.php`, dan `public/api/target.php`, parameter `user_id` **WAJIB** diambil dari `current_user()['id']` di session PHP (dilarang percaya `user_id` dari body request client).
3. **Aturan Mode Demo (`APP_DEMO`):**
   - `demo_mode()` di `includes/auth/auth.php` **HANYA** boleh aktif apabila `APP_DEMO=1` **DAN** server berjalan di `localhost` / `127.0.0.1`. Pada server produksi, `demo_mode()` wajib selalu bernilai `false`.

---

## 4. CARA AI BEKERJA (AI OPERATIONAL WORKFLOW)

Saat AI Coding Assistant diberikan tugas pada repositori SkillBridge, AI **wajib** bekerja mengikuti siklus 5 langkah berikut:

1. **Langkah 1 — Context & Dependency Check (Baca Sebelum Menulis):**
   - Sebelum mengedit sebuah halaman `.php` atau `.js`, AI wajib memeriksa pasangan file terkaitnya:
     - File View PHP (`public/<role>/<page>.php`)
     - File Layout Partial (`includes/layouts/<role>_*.php`)
     - File Script Halaman (`public/js/pages/<page>.js`)
     - File Client API (`public/js/api.js`) & Endpoint Backend (`public/api/...`)
     - File Stylesheet (`public/css/style.css` + CSS khusus role).
2. **Langkah 2 — Database-First & API Contract Alignment:**
   - Pastikan nama tabel, nama kolom, dan nilai `ENUM` di `db/schema.sql` cocok 100% dengan query PDO di `public/api/*.php` dan payload JSON yang dikirim oleh `public/js/pages/*.js`.
3. **Langkah 3 — Zero Visual Regression pada DOM & CSS:**
   - Saat memperbaiki logika atau membuat tampilan responsif, AI wajib mempertahankan struktur class CSS dan atribut `id` / `data-*` yang menjadi target selector JavaScript.
4. **Langkah 4 — Incremental Execution (Eksekusi Bertahap):**
   - AI harus menyelesaikan pekerjaan per modul secara tuntas (misal: selesaikan bug runtime JS dulu -> bangun endpoint API -> sambungkan `api.js` -> rapikan CSS responsif & tombol), tidak mencampur perubahan acak yang meninggalkan kode setengah jadi.
5. **Langkah 5 — Post-Edit Verification:**
   - Setelah mengedit file PHP/JS/CSS, AI wajib memverifikasi tidak ada sintaks PHP yang rusak (`php -l`), tidak ada pemanggilan fungsi JS yang *undefined*, tidak ada link `.html` lama atau path relatif tanpa `base_url()` / `asset()`, dan tidak ada `<button>` tanpa atribut `type`.

---

## 5. CARA AI MENGOREKSI (AI SELF-CORRECTION & DEBUGGING PROTOCOL)

Bagian ini mengatur bagaimana AI **mendeteksi dan mengoreksi kesalahan yang ada di kode saat ini** (berdasarkan temuan audit sistem):

### 5.1. Protokol Koreksi Bug Runtime & Logika JavaScript
1. **Koreksi Pemanggilan Fungsi API yang Tidak Sinkron:**
   - *Deteksi:* Di `public/js/pages/evidence.js` baris 171 tertulis `API.createEvidence(payload)`, sedangkan di `public/js/api.js` fungsi yang ada adalah `addEvidence(data)`.
   - *Koreksi AI:* Ubah menjadi `await API.addEvidence(payload)` (atau sediakan alias `createEvidence: addEvidence` di `api.js` sekaligus memperbaiki pemanggilnya).
2. **Koreksi Referensi Variabel State yang Salah Ketik:**
   - *Deteksi:* Di `public/js/pages/admin-mapping.js` baris 75 tertulis `allMappings.find(...)`, padahal variabel array di atasnya bernama `allRows`.
   - *Koreksi AI:* Ubah `allMappings` menjadi `allRows`.
3. **Koreksi Duplikasi Event Listener & Copy-Paste Error:**
   - *Deteksi:* Di `recruiter-team.js` (baris 212–236), `admin-users.js` (baris 92), `admin-admins.js` (baris 84, 100, 115), dan `admin-vacancies.js` (baris 65, 75, 86), terdapat pendaftaran `addEventListener` di dalam fungsi render ulang atau di dalam properti `onClick` tombol.
   - *Koreksi AI:* Hapus seluruh `addEventListener` yang bersarang di dalam fungsi render/`onClick`, dan pasang **satu kali** *event delegation* bersih pada elemen `tbody` saat `DOMContentLoaded`.
4. **Koreksi Pemotongan Daftar Skill Gap & Rasio Terlarang:**
   - *Deteksi:* Di `public/js/pages/gap.js` baris 66 terdapat `.slice(0, 1)` yang membuat *What to Explore Next* hanya menampilkan 1 skill; di `public/js/pages/career.js` baris 94 terdapat teks `${matched} of ${skillNames.length} skills covered`.
   - *Koreksi AI:* Hapus `.slice(0, 1)` di `gap.js` agar seluruh skill gap tampil utuh, fungsikan tombol *"Add [Skill]"* untuk memanggil `API.addMySkill()`, dan hapus elemen rasio *"x of y skills covered"* di `career.js`.

### 5.2. Protokol Koreksi "Database Disconnect" & Kontaminasi Sesi
1. **Koreksi Penyimpanan `localStorage` ke PostgreSQL:**
   - *Deteksi:* `public/js/api.js` masih menyimpan skill, evidence, dan target karier ke `localStorage` tanpa menyentuh PostgreSQL.
   - *Koreksi AI:* Buat endpoint `public/api/*.php` dengan PDO Prepared Statements dan ubah `public/js/api.js` agar melakukan `fetch()` ke endpoint tersebut dengan menyertakan header `X-CSRF-Token`.
2. **Koreksi Kontaminasi Data Antar-User Saat Logout:**
   - *Deteksi:* Saat logout, `localStorage` tidak dihapus sehingga akun berikutnya melihat data akun sebelumnya.
   - *Koreksi AI:* Pada handler tombol logout di `public/js/ui.js` dan pada halaman transisi `public/logout.php`, bersihkan seluruh kunci `sb.*` di `localStorage` (`localStorage.removeItem('sb.mySkills')`, `sb.myEvidence`, `sb.targetCareer`, `sb.profile`).
3. **Koreksi Identitas Hardcoded ("Jhon Doe" & "Aditya"):**
   - *Deteksi:* `public/mahasiswa/profile.php` masih menampilkan `"Jhon Doe"` dan `public/admin/index.php` masih menyapa `"Aditya"`.
   - *Koreksi AI:* Ganti seluruh nama dan email hardcoded dengan nilai sesi aktif `<?= e(current_user()['name']) ?>` dan `<?= e(current_user()['email']) ?>`.

### 5.3. Protokol Koreksi Button, Path Navigasi, & Responsivitas
1. **Koreksi Redirect Login & Tombol Logout:**
   - Di `public/js/pages/login.js`, koreksi redirect `"dashboard.php"` menjadi rute berbasis role dengan `window.APP_BASE_URL` (`/mahasiswa/dashboard.php`, `/hr/index.php`, `/admin/index.php`).
   - Di `includes/layouts/*.php`, ganti seluruh `$path_prefix . 'logout.php'` menjadi `<?= e(base_url('logout.php')) ?>`.
2. **Koreksi Semantik & Styling Button:**
   - Lengkapi varian `.btn-primary`, `.btn-accent`, `.btn-secondary`, `.btn-outline`, `.btn-danger`, `.btn-ghost`, `.btn-sm`, `.btn-lg`, `.btn-icon`, dan `.btn-block` di `public/css/style.css`.
   - Tambahkan `type="button"` pada seluruh `<button>` non-submit di 23 halaman PHP dan di fungsi pembentuk elemen di `public/js/pages/*.js`.
   - Bungkus tombol aksi di dalam sel tabel `<td>` dengan `<div class="table-actions">` (`flex-wrap: nowrap`).
3. **Koreksi Layout Responsif:**
   - Tambahkan tombol `.navbar-toggle` (hamburger menu) pada `public_header.php` & `mahasiswa_header.php` untuk layar `< 768px`.
   - Ubah `.rec-sidebar` (`recruiter.css`) dan `.admin-sidebar` (`admin.css`) pada layar `< 1024px` dari *chip-bar* horizontal menjadi **Off-Canvas Drawer** (`transform: translateX(-100%)` -> `.is-open` `translateX(0)`) dengan `.sidebar-backdrop` dan tombol `.sidebar-toggle` di header.
   - Bungkus seluruh `<table class="table">` dengan `<div class="table-responsive">` (`overflow-x: auto`).

---

## 6. BATASAN & ATURAN KERAS (HARD CONSTRAINTS & SECURITY RULES)

### 6.1. Batasan Teknologi (Technology Constraints)
1. **DILARANG** menggunakan framework PHP eksternal (Laravel, CodeIgniter, Symfony) maupun library Composer tanpa izin eksplisit.
2. **DILARANG** menggunakan framework CSS/JS (Bootstrap, Tailwind CSS, React, Vue, Alpine.js, jQuery).
3. **DILARANG** menambahkan CDN font/ikon baru selain Google Fonts `Inter` (gunakan SVG inline atau aset lokal di `public/assets/`).

### 6.2. Batasan Fitur Non-Scope (Product Boundaries)
1. **DILARANG** menampilkan persentase kecocokan karier (*match rate* %), skor kesiapan karier numerik, progress bar persentase, atau teks rasio *"x of y skills covered"* pada konsol Mahasiswa.
2. **DILARANG** membuat fitur integrasi/scraping repositori GitHub otomatis; bukti proyek GitHub diinput melalui URL pada modul *Evidence*.
3. Halaman *Skill Gap* (`mahasiswa/gap.php`) **HANYA** boleh menampilkan dua daftar: **Skills You Already Have** dan **What to Explore Next**.

### 6.3. Aturan Keamanan & Kualitas Kode (Security & Code Quality Rules)
1. **Single Source of Truth Layout:** File halaman di `public/mahasiswa/`, `public/hr/`, dan `public/admin/` dilarang menulis tag `<!DOCTYPE html>`, `<head>`, `<header>`, `<aside>`, atau `<footer>` secara langsung; wajib memanggil partial dari `includes/layouts/` (dan `mahasiswa_footer.php` wajib memuat `scripts.php` agar tag penutup tidak ditulis manual di setiap file).
2. **Kewajiban Helper URL (`base_url()` & `asset()`):** Dilarang menulis path relatif mentah (`href="css/..."`, `src="assets/..."`, `href="dashboard.php"`). Wajib menggunakan `asset('...')` dan `base_url('...')` di PHP serta `window.APP_BASE_URL` di JavaScript.
3. **Kewajiban Keamanan Database & Input:**
   - Semua query SQL wajib menggunakan **PDO Prepared Statements** (`prepare()` + `execute()`).
   - Semua output variabel di PHP wajib dibungkus `e($var)` (`htmlspecialchars`).
   - Semua manipulasi teks dari user/API di JS wajib menggunakan `textContent` / `createElement` (dilarang `innerHTML` untuk data dinamis).
   - Semua request tulis (`POST`, `PUT`, `DELETE`) wajib memvalidasi token CSRF (`X-CSRF-Token` atau `csrf_token`).
4. **Kebersihan Document Root (`public/`):** File diagnostik atau utilitas database (`test-db.php`, `diagnose.php`, `create_admin.php`) dilarang berada di dalam folder `public/` dan wajib ditempatkan di `tools/` dengan proteksi `PHP_SAPI === 'cli'`.
