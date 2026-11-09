# Rumusan Final Arsitektur & Workflow Terpadu SkillBridge (3-Role Ecosystem)

**Proyek:** SkillBridge — Platform Pemetaan Keterampilan, Validasi Bukti Proyek Mandiri, dan Penjodohan Talenta Berbasis Bukti (*Evidence-Driven Talent Readiness & Discovery*)
**Repositori:** [HaikalMaghsin/SkillBridge](https://github.com/HaikalMaghsin/SkillBridge)
**Stack Teknologi:** PHP 8.x Native, PostgreSQL (Supabase via `PDO_PGSQL`), HTML5, CSS3 Native, Vanilla JavaScript (ES6+), Mozilla PDF.js, & GitHub REST/OAuth API.

---

## 1. Identitas & Filosofi Inti Produk (Apa Itu SkillBridge & Apa yang Bukan)

1. **Bukan Portal Lamaran Kerja Konvensional (Bukan JobStreet/LinkedIn):**
   Mahasiswa tidak bisa sekadar menekan tombol *"Easy Apply / Kirim CV Kosong"*. Untuk dapat masuk ke radar HR perusahaan mitra, mahasiswa wajib membuktikan kompetensinya terlebih dahulu melalui **Evidence** dan menutup kesenjangan di **Skill Gap**.
2. **Bukan LMS / Tempat Kursus Online (Bukan Ruangguru/Coursera):**
   SkillBridge **tidak** menyediakan video materi atau modul belajar di dalam aplikasi. Halaman **Skill Gap** bertindak sebagai *Kompas & To-Do List Proyek Mandiri*: mahasiswa belajar secara mandiri di luar aplikasi dengan membuat *project dummy* / studi kasus nyata (di VS Code, GitHub, atau Figma), lalu memvalidasi hasilnya kembali ke dalam fitur **Evidence**.
3. **Tanpa Skor Angka / Persentase Kesiapan Semu:**
   Sistem tidak menampilkan angka persentase kecocokan (*match rate %*) atau rasio *"x of y skills covered"*. Kekuatan kompetensi diukur secara kualitatif dan transparan melalui **Triangulasi Bukti (Exploring $\rightarrow$ Growing $\rightarrow$ Strong)**.

---

## 2. Pembagian Peran & Struktur Data 3 Role (Admin, HR, Mahasiswa)

Mengadopsi prinsip pemetaan kompetensi [ESCO (Essential vs Contextual Skills)](https://esco.ec.europa.eu/en/about-esco/escopedia/escopedia/occupations-pillar) dan [SFIA Role Profiles](https://sfia-online.org/en/tools-and-resources/using-sfia/sfia-assessment/self-assessment-guidelines), SkillBridge memisahkan **Standar Payung Kampus/Admin** dari **Kebutuhan Spesifik Perusahaan (HR)**:

| Role | Tanggung Jawab Utama di Sistem | Halaman Utama | Tabel PostgreSQL (`db/schema.sql`) |
| :--- | :--- | :--- | :--- |
| **1. ADMIN (Pengelola Platform / Kampus)** | • Mengelola **Kamus Master Skill** agar penamaan teknologi seragam di seluruh sistem.<br>• Mengelola **Kategori Payung Karier** (*Backend Developer*, *Frontend Developer*, *Data Analyst*, *UI/UX Designer*) beserta 3–4 skill fondasi dasar.<br>• Mengelola status aktif pengguna (`users.is_active`) dan memoderasi lowongan perusahaan. | `admin/index.php`<br>`admin/skills.php`<br>`admin/careers.php`<br>`admin/mapping.php`<br>`admin/users.php`<br>`admin/vacancies.php` | `skills`, `careers`, `career_skill_requirements`, `users` |
| **2. HR (Perusahaan Mitra Sekitar)** | • Mengelola profil perusahaan mitra (lokasi Malang/Jatim/Remote & bidang industri).<br>• Membuka **Target Role / Posisi Perusahaan** yang menginduk ke salah satu *Kategori Payung Karier* Admin, namun memilih sendiri **daftar skill spesifik yang dibutuhkan perusahaan tersebut**.<br>• Meninjau portofolio bukti nyata mahasiswa (`is_public = TRUE`) dan menekan **Shortlist / Undang Wawancara**. | `hr/index.php`<br>`hr/roles.php`<br>`hr/candidates.php`<br>`hr/candidate.php`<br>`hr/activity.php` | `companies`, `company_recruiters`, `job_vacancies`, `candidate_pipelines`, `recruiter_activity_logs` |
| **3. MAHASISWA (Talenta)** | • Memilih **Kategori Payung Karier** dan mengeksplorasi **Perusahaan Sekitar** yang membuka posisi di jalur tersebut.<br>• Mengunci target belajar ke syarat skill spesifik perusahaan incaran di **Skill Gap**.<br>• Membuat *project dummy* mandiri di luar web, lalu memvalidasinya lewat **Scan CV ATS (PDF.js)** dan **Scan Repo GitHub (OAuth/API)** di halaman **Evidence**.<br>• Menekan **Tandai Minat & Buka Portofolio** pada posisi perusahaan incaran setelah syarat bukti terpenuhi. | `mahasiswa/dashboard.php`<br>`mahasiswa/career.php`<br>`mahasiswa/gap.php`<br>`mahasiswa/evidence.php`<br>`mahasiswa/skills.php`<br>`mahasiswa/profile.php` | `student_profiles`, `user_skills`, `evidence`, `evidence_skills` |

---

## 3. Alur Kerja (Workflow) End-to-End 5 Tahap

### Tahap 1 — Standarisasi Kamus oleh Admin & Pembuatan Kebutuhan oleh HR
1. **Admin** mendaftarkan katalog *Master Skills* (`PHP`, `Laravel`, `Node.js`, `Express.js`, `SQL`, `PostgreSQL`, `REST APIs`, `Docker`, `Git`, `Figma`, dll.) dan kategori karier induk (misal: **Backend Developer**).
2. **HR Perusahaan A (misal: PT Sekawan Media — Malang)** masuk ke `hr/roles.php`, memilih kategori induk **Backend Developer**, lalu memilih syarat skill spesifik perusahaannya: **`PHP`, `Laravel`, `SQL`, `REST APIs`, `Git`**.
3. **HR Perusahaan B (misal: PT DOT Indonesia — Malang)** memilih kategori induk **Backend Developer**, lalu memilih syarat skill spesifik perusahaannya: **`Node.js`, `Express.js`, `PostgreSQL`, `Docker`, `Git`**.

### Tahap 2 — Eksplorasi Karier & Pemilihan Target Perusahaan oleh Mahasiswa (`mahasiswa/career.php`)
1. Mahasiswa membuka `mahasiswa/career.php` dan memilih kartu kategori **Backend Developer**.
2. Di bawah kartu karier aktif, sistem otomatis menampilkan daftar **Perusahaan Sekitar yang Membuka Posisi Backend Developer** (PT Sekawan Media, PT DOT Indonesia, dll.) lengkap dengan lokasi, tipe (*Magang / Full-time*), dan chip skill yang mereka cari.
3. Mahasiswa melihat bahwa dirinya ingin masuk ke **PT Sekawan Media (Malang)**, lalu menekan tombol **`[ 🎯 Fokuskan Skill Gap ke Perusahaan Ini ]`** pada kartu PT Sekawan Media.

### Tahap 3 — Peta Kesenjangan Adaptif & Panduan Project Mandiri (`mahasiswa/gap.php`)
1. Halaman `mahasiswa/gap.php` menyesuaikan daftar target skill-nya dengan **5 skill spesifik milik PT Sekawan Media** (`PHP`, `Laravel`, `SQL`, `REST APIs`, `Git`) — sehingga mahasiswa **tidak** dipaksa mempelajari `Node.js` milik Perusahaan B.
2. Sistem membagi tampilan menjadi 2 kolom:
   - **Skills You Already Have:** Skill syarat perusahaan yang sudah dimiliki mahasiswa (misal: `PHP`, `SQL`, `Git`).
   - **What to Explore Next:** Skill syarat perusahaan yang belum dimiliki (misal: `Laravel`, `REST APIs`), dilengkapi rekomendasi proyek mandiri (*"Buat 1 project dummy REST API / CRUD Laravel di GitHub lalu unggah ke halaman Evidence"*).

### Tahap 4 — Belajar Mandiri & Validasi Otomatis di Evidence (`mahasiswa/evidence.php`)
1. Mahasiswa belajar secara mandiri dan membuat *project dummy* di komputer/GitHub-nya.
2. Mahasiswa membuka `mahasiswa/evidence.php` dan mengunggah buktinya:
   - **Jika jenis bukti `CV`:** [Mozilla PDF.js](https://mozilla.github.io/pdf.js/) dengan algoritma *Coordinate-Aware Line Grouping* (`X, Y`) membaca teks PDF, memilah section **`Ringkasan Profil`** ke field *Ringkasan kontribusi*, mencentang otomatis **1–6 Skill** yang relevan dengan target karier/perusahaan, dan menyetel otomatis **Level Bukti (`Dasar` / `Menengah` / `Mahir`)**.
   - **Jika jenis bukti `GitHub`:** Mahasiswa melakukan *Authorize GitHub (OAuth 2.0)* atau memindai repositori publik via [GitHub REST API](https://docs.github.com/en/rest/repos/repos). Sistem otomatis mengambil nama repo, deskripsi/README, mendeteksi bahasa pemrograman (`/languages` & `/topics`), mencentang chip skill terkait, dan menentukan level bukti.
3. Saat bukti disimpan, skill yang dipilih otomatis masuk/tersinkron ke daftar **My Skills (`mahasiswa/skills.php`)**.

### Tahap 5 — Triangulasi Status Skill, Sinyal Minat Mahasiswa, & Shortlist HR
1. **Penilaian Status Skill Tanpa Skor Angka (Triangulasi Bukti):**
   - **Exploring:** Skill ada di target perusahaan/karier, tetapi belum ada di *My Skills* / `0 bukti`.
   - **Growing:** Skill sudah ada di *My Skills* dengan **1 bukti** (atau beberapa bukti tetapi hanya dari 1 jenis sumber yang sama).
   - **Strong:** Skill didukung oleh **minimal 2 bukti dari dimensi/sumber yang saling memvalidasi** (misal: *CV ATS / Sertifikat* + *Proyek GitHub / Portofolio*), dengan level bukti minimal *Menengah*.
2. **Sinyal Kesiapan Mahasiswa ke Perusahaan A (`Express Verified Interest`):**
   - Setelah mahasiswa memiliki bukti skill yang dibutuhkan **PT Sekawan Media**, tombol **`[ 📌 Tandai Minat & Buka Portofolio ke HR Ini ]`** pada kartu lowongan PT Sekawan Media menjadi aktif.
   - Saat diklik, sistem menyetel `student_profiles.is_public = TRUE` dan mendaftarkan mahasiswa ke `candidate_pipelines` untuk posisi PT Sekawan Media tersebut.
3. **Eksekusi Talent Scouting oleh HR (`hr/candidates.php` & `hr/candidate.php`):**
   - HR PT Sekawan Media membuka konsol `hr/candidates.php` dan melihat kandidat yang memenuhi syarat skill spesifik posisi mereka.
   - HR mengklik detail kandidat (`hr/candidate.php`), memeriksa tautan repo GitHub dan ringkasan CV ATS asli milik mahasiswa, lalu menekan tombol **`[ 📩 Undang Wawancara / Shortlist ]`**.
   - Mahasiswa langsung menerima pemberitahuan di **`mahasiswa/dashboard.php`**: *"Selamat! Portofolio buktinya telah di-shortlist untuk tahap Wawancara oleh HR PT Sekawan Media (Malang)."*

---

## 4. Urutan Rencana Kerja Eksekusi (Roadmap 4 Sprint Solo-Carry)

| Sprint | Target Pengerjaan | File yang Disentuh |
| :--- | :--- | :--- |
| **Sprint 1: Sinkronisasi DB & Bug Kritis P1** | • Jalankan migrasi `users.is_active` di PostgreSQL.<br>• Perbaiki query `evidence_count` (`e.user_id = :uid`) di `api/my-skills.php` & `api/gap.php`.<br>• Perbaiki `base_url()` di `includes/config/app.php` dan hapus file debug publik (`evidence-debug.php`, `gap-debug.php`, `test-evidence.php`). | `db/migrations/`, `public/api/my-skills.php`, `public/api/gap.php`, `includes/config/app.php` |
| **Sprint 2: Upgrade Evidence Scanner (PDF Koordinat + GitHub OAuth)** | • Terapkan rekonstruksi koordinat `(x, y)` PDF.js, pemilahan section `Ringkasan Profil`, dan `Auto-Level` di `evidence.js`.<br>• Tambahkan *GitHub OAuth / Repo Scanner* pada tab jenis bukti `GitHub`.<br>• Terapkan logika penilaian **Strong** (minimal 2 bukti berbeda sumber/valid). | `public/js/pages/evidence.js`, `public/api/github-auth.php`, `public/api/github-callback.php`, `public/api/my-skills.php` |
| **Sprint 3: Loker Perusahaan Sekitar & Fokus Skill Gap per Perusahaan** | • Aktifkan kembali `public/hr/roles.php` & `public/api/hr/roles.php` agar HR bisa menetapkan syarat skill spesifik per posisi.<br>• Tampilkan daftar **Perusahaan Sekitar** di `public/mahasiswa/career.php` beserta tombol *"Fokuskan ke Perusahaan Ini"* dan *"Tandai Minat & Buka Portofolio"*.<br>• Tambahkan mode filter skill perusahaan incaran di `public/mahasiswa/gap.php` beserta panduan pembuatan *project dummy*. | `public/mahasiswa/career.php`, `public/js/pages/career.js`, `public/mahasiswa/gap.php`, `public/js/pages/gap.js`, `public/hr/roles.php` |
| **Sprint 4: Shortlist HR, Notifikasi Dashboard Mahasiswa, & Admin Real DB** | • Tambahkan tombol *Shortlist / Undang Wawancara* di `public/hr/candidate.php` yang terhubung ke `candidate_pipelines` dan memunculkan kartu notifikasi di `public/mahasiswa/dashboard.php`.<br>• Sambungkan `public/admin/admins.php` dan `public/admin/settings.php` ke PostgreSQL (hapus sisa dummy `localStorage`). | `public/hr/candidate.php`, `public/api/hr/candidates.php`, `public/mahasiswa/dashboard.php`, `public/admin/admins.php`, `public/admin/settings.php` |
