# Product Requirement Document (PRD) - SkillBridge

## 1. Ringkasan Produk
SkillBridge adalah aplikasi web dinamis yang membantu mahasiswa dan fresh graduate memahami keterampilan yang dimiliki dan mengidentifikasi *skill gap* terhadap satu target karier, secara sederhana dan terstruktur.

## 2. Tim & Mata Kuliah
- Ketua Tim & Pengembang Utama: Haikal Maghsin
- Analis & Desainer: Marvelino Husca
- Sekretaris & Dokumentator: M. Javier Thufail
- QA & Penguji: Geraldi Rama Nugraha
- Mata Kuliah: Desain dan Pemrograman Web (Dosen: Dimas Wahyu Wibowo, S.T., M.T.)

## 3. Batasan (CRITICAL)
- **Tech stack:** HTML5 Semantic, CSS3 Native (Flexbox/Grid), JavaScript Vanilla (DOM, Event, Fetch API), PHP Native 8.x, PostgreSQL Supabase via `PDO_PGSQL`.
- **Dilarang:** framework/library eksternal (Laravel, React, Next.js, Tailwind, Bootstrap, ORM), Supabase JS/PHP SDK, Composer package untuk logika inti.
- **Non-scope:** skor kesiapan karier, persentase kecocokan / match rate, progress bar persentase, analisis otomatis GitHub, fitur lowongan/rekrutmen.
- **Skill gap** disajikan murni deskriptif dalam dua daftar: *Keterampilan yang Dimiliki* dan *Keterampilan yang Perlu Dikembangkan*. Boleh menampilkan jumlah (mis. "5 skill"), tidak boleh persentase atau rasio "x dari y".

## 4. Aturan Penggunaan Desain (Figma / Mockup)
- Mockup di `docs/design/` adalah **referensi visual saja**: layout, warna, tipografi, komponen.
- **Data contoh di mockup diabaikan** (nama skill, angka, tanggal, nama karier "Product Designer", teks "5 of 8 skills covered", Express.js, dll.). Data sebenarnya dari database dan seed pada bagian 7.
- Teks antarmuka memakai Bahasa Inggris seperti mockup.
- Gaya: font Inter; biru navy (primer), pink (aksen/CTA), latar abu sangat terang, kartu dengan sudut membulat dan border tipis, navbar atas berisi pill menu.

## 5. Halaman & Fitur

| Halaman | File | Akses | Isi |
|---|---|---|---|
| Register | `register.php` | publik | form nama, email, password |
| Login | `login.php` | publik | form email, password |
| Logout | `logout.php` | login | hancurkan sesi |
| Dashboard | `dashboard.php` | login | ringkasan target karier, jumlah skill, 3 evidence terbaru, jumlah skill yang perlu dikembangkan, tombol Add Evidence |
| My Skills | `skills.php` | login | CRUD skill pengguna; label Strong/Growing |
| Evidence | `evidence.php` | login | CRUD evidence (Project, Certificate, Portfolio, Other) dengan link dan skill terkait |
| Career Target | `career.php` | login | kartu 4 karier + required skills; tombol Set as Target; target aktif ditandai |
| Skill Gap | `gap.php` | login | dua daftar: dimiliki vs perlu dikembangkan, jumlah evidence per skill |
| Profile | `profile.php` | login | ubah nama, ganti password |
| Upload CV | `cv.php` | login | upload PDF/TXT, tampil kandidat skill |
| Review Skill | `cv_review.php` | login | centang/tambah/hapus kandidat sebelum disimpan ke My Skills |

### Aturan fitur
1. **Auth:** `password_hash`/`password_verify`, `session_regenerate_id(true)` saat login, halaman login-only memanggil `require_login()`.
2. **Career Target:** satu target aktif per pengguna (`student_profiles.target_career_id`), bisa diganti kapan saja. Daftar karier dan required skills dari database.
3. **My Skills:** tambah skill dari tabel `skills` (dropdown) atau skill baru; hapus; tidak boleh duplikat per pengguna. Label: **Strong** jika skill punya ≥ 2 evidence, **Growing** jika kurang. Dihitung lewat query, tidak disimpan.
4. **Evidence:** field: tipe, judul, deskripsi singkat, URL, tanggal, skill terkait (boleh banyak). Tombol "Open" membuka URL di tab baru (`rel="noopener noreferrer"`). Hanya URL `http/https` yang diterima.
5. **Skill Gap:** query `SELECT` membandingkan `career_skills` target dengan `user_skills`. Skill yang dimiliki menampilkan jumlah evidence. Skill yang kurang menampilkan nama + kategori + tombol tambah ke My Skills. Jika belum memilih target, arahkan ke Career Target.
6. **Upload CV:** terima `.pdf` atau `.txt`, maks 2 MB, validasi ekstensi + MIME (`finfo`). Ekstraksi teks dasar (TXT langsung; PDF best-effort tanpa library, jika gagal tampilkan opsi tempel teks manual). Cocokkan kata kunci dengan `skills.name` (case-insensitive, whole word). File tidak disimpan permanen: diproses lalu dihapus.
7. **Review Skill:** kandidat tampil sebagai checklist; pengguna bisa menambah atau menghapus; hanya yang dikonfirmasi masuk `user_skills`.
8. **JavaScript Fetch:** dipakai minimal untuk (a) hapus item tanpa reload, (b) set target karier, (c) filter/pencarian di My Skills. Endpoint JSON di `public/api/`, tetap memverifikasi sesi dan CSRF.

## 6. Skema Basis Data (PostgreSQL, snake_case huruf kecil)

```sql
users(
  id SERIAL PK, name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'student', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)

skills(id SERIAL PK, name VARCHAR(100) UNIQUE NOT NULL, category VARCHAR(50))

careers(id SERIAL PK, name VARCHAR(100) NOT NULL, description TEXT)

career_skills(                                   -- BARU: syarat skill per karier
  id SERIAL PK,
  career_id INT REFERENCES careers(id) ON DELETE CASCADE,
  skill_id  INT REFERENCES skills(id)  ON DELETE CASCADE,
  UNIQUE(career_id, skill_id))

student_profiles(
  user_id INT PK REFERENCES users(id) ON DELETE CASCADE,
  target_career_id INT REFERENCES careers(id))

user_skills(
  id SERIAL PK,
  user_id  INT REFERENCES users(id)  ON DELETE CASCADE,
  skill_id INT REFERENCES skills(id),
  UNIQUE(user_id, skill_id))                     -- BARU: cegah duplikat

evidence(
  id SERIAL PK, user_id INT REFERENCES users(id) ON DELETE CASCADE,
  source_type VARCHAR(50) NOT NULL,              -- project|certificate|portfolio|other
  title VARCHAR(150) NOT NULL,                   -- BARU
  description TEXT,                              -- BARU
  file_url VARCHAR(255),
  metadata JSONB,                                -- cadangan, mis. tanggal terbit
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)

evidence_skills(                                 -- BARU: evidence <-> skill
  evidence_id INT REFERENCES evidence(id) ON DELETE CASCADE,
  skill_id    INT REFERENCES skills(id)   ON DELETE CASCADE,
  PRIMARY KEY(evidence_id, skill_id))
```

Skema ini sedikit diperluas dari ERD proposal (tabel/kolom bertanda BARU) agar fitur skill gap, label skill, dan evidence berfungsi. Catat perubahan ini di dokumentasi.

## 7. Data Awal (seed)

Karier dan required skills (final, menggantikan data di mockup):

| Karier | Deskripsi | Required skills |
|---|---|---|
| Backend Developer | Build APIs, databases, and server-side services for web products. | PHP, SQL, PostgreSQL, REST APIs, Git |
| Frontend Developer | Create accessible, responsive interfaces for websites and web apps. | HTML, CSS, JavaScript, Responsive Design, Git |
| Data Analyst | Prepare data, explore patterns, and explain useful findings clearly. | Excel, SQL, Python, Data Visualisation, Statistics |
| UI/UX Designer | Plan simple digital experiences and create clear interface designs. | User Research, Wireframing, Figma, Prototyping, Usability Testing |

Kategori skill: Programming, Database, Web, Tools, Data, Design.
Akun dan data demo untuk presentasi dibuat terpisah di `database/demo.sql`.

## 8. Keamanan (Wajib)
- **SQL Injection:** semua query pakai `$pdo->prepare()` lalu `$stmt->execute([...])`; `PDO::ATTR_EMULATE_PREPARES => false`.
- **XSS:** semua output ke HTML lewat `e()` = `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
- **CSRF:** token di sesi, input hidden di setiap form POST, dan header `X-CSRF-Token` untuk Fetch; verifikasi dengan `hash_equals`.
- **Session fixation:** `session_regenerate_id(true)` saat login; cookie `HttpOnly`, `SameSite=Lax`.
- **Upload:** validasi ekstensi, MIME, ukuran; nama file diacak; tidak disimpan di folder publik.
- **Otorisasi:** setiap query data pengguna memfilter `user_id` dari sesi (cegah akses data pengguna lain).
- **Kredensial:** `.env` di luar `public/`, masuk `.gitignore`.

## 9. Kriteria Selesai
- Semua halaman di bagian 5 berfungsi end-to-end dengan data dari Supabase.
- Tampilan sesuai mockup (layout/warna/komponen), responsif dasar di lebar mobile.
- Tidak ada query tanpa prepared statement, output tanpa escape, atau form POST tanpa CSRF.
- Tidak ada fitur non-scope (bagian 3).
- README berisi cara setup dan `.env.example`.