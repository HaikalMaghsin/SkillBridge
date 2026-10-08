# CLAUDE.md - SkillBridge (PHP Native + Supabase PostgreSQL)

## Konteks
SkillBridge adalah proyek PjBL mata kuliah Desain dan Pemrograman Web: aplikasi web dinamis end-to-end tanpa framework.
**Sebelum mengerjakan apa pun, baca `docs/PRD.md` dan lihat screenshot terkait di `docs/design/`.** PRD adalah sumber kebenaran; mockup hanya referensi visual (data contoh di mockup diabaikan).

## Tech Stack
- Front-End: HTML5 semantik, CSS3 native (Flexbox/Grid, CSS variables), JavaScript vanilla (DOM, Fetch API).
- Back-End: PHP native 8.x, prosedural/modular rapi.
- Database: Supabase PostgreSQL via `PDO_PGSQL` (port 5432 atau 6543), kredensial dari `.env`.

## Larangan
- Tidak ada framework/library eksternal: Laravel, React, Next.js, Tailwind, Bootstrap, jQuery, ORM, Composer package.
- Tidak memakai Supabase JS/PHP SDK. Hanya PDO.
- Jangan menambah fitur non-scope: skor kesiapan, persentase/match rate, analisis GitHub, lowongan kerja.
- Jangan mengubah `CLAUDE.md` atau `docs/PRD.md` tanpa izin. Jika menemukan konflik atau kekurangan di PRD, tanyakan dulu.
- Jangan membaca, mencetak, atau commit isi `.env`.

## Struktur Folder
```
skillbridge/
├── CLAUDE.md
├── README.md
├── .env                 # rahasia, di .gitignore
├── .env.example
├── .gitignore
├── docs/                # PRD.md, design/*.jpg
├── database/            # schema.sql, seed.sql, demo.sql
├── src/
│   ├── config/db.php    # koneksi PDO
│   ├── helpers/         # auth.php, csrf.php, escape.php, flash.php
│   ├── models/          # fungsi query per entitas (user, skill, career, evidence)
│   └── views/           # header.php, footer.php, partial komponen
└── public/              # document root (php -S localhost:8000 -t public)
    ├── *.php            # halaman (login, dashboard, skills, ...)
    ├── api/             # endpoint JSON untuk Fetch
    ├── css/style.css
    └── js/
```
Hanya `public/` yang boleh diakses web. Logika dan kredensial berada di luar `public/`.

## Aturan Keamanan (Wajib, tiap kode baru)
1. **SQL Injection:** semua query memakai prepared statement: `$stmt = $pdo->prepare($sql); $stmt->execute([...]);`. Dilarang menyambung variabel ke string SQL. Set `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION` dan `ATTR_EMULATE_PREPARES => false`.
2. **XSS:** semua output ke HTML lewat `e($v)` = `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
3. **CSRF:** token sesi di setiap form POST (input hidden) dan request Fetch (header `X-CSRF-Token`); verifikasi dengan `hash_equals`.
4. **Sesi:** `session_regenerate_id(true)` saat login berhasil; cookie `HttpOnly` dan `SameSite=Lax`; logout menghancurkan sesi.
5. **Password:** `password_hash(..., PASSWORD_DEFAULT)` dan `password_verify`.
6. **Otorisasi:** halaman dan endpoint privat memanggil `require_login()`; setiap query data pengguna memfilter `user_id` dari `$_SESSION`, bukan dari input.
7. **Upload:** validasi ekstensi, MIME (`finfo`), ukuran maks 2 MB; nama file diacak; jangan simpan di folder publik.
8. **Error:** jangan tampilkan pesan error DB ke pengguna; catat ke log, tampilkan pesan umum.
9. **URL evidence:** hanya terima skema `http`/`https`; link keluar memakai `rel="noopener noreferrer"`.

## Gaya Kode
- Nama tabel/kolom snake_case huruf kecil. Nama fungsi dan variabel PHP: snake_case.
- Satu tanggung jawab per file; query di `src/models/`, bukan di file halaman.
- Pola halaman: `require` bootstrap → cek auth → proses POST (verifikasi CSRF, validasi, query, redirect/flash) → render view. Gunakan pola Post/Redirect/Get.
- Validasi input di server (wajib); validasi di JS hanya pelengkap UX.
- CSS: design tokens di `:root` (warna navy, pink, abu, radius, shadow), font Inter dengan fallback sans-serif, komponen reusable (`.card`, `.pill`, `.btn`, `.btn-primary`, `.btn-accent`, `.tag`). Responsif dengan media query.
- Komentar hanya untuk logika yang tidak jelas.

## Cara Kerja
1. Kerjakan **satu fitur per sesi**. Jangan menyentuh fitur lain.
2. Untuk tugas besar, buat rencana singkat dulu dan tunggu persetujuan.
3. Setelah selesai, laporkan: file yang dibuat/diubah, cara mengujinya (langkah klik/URL), dan hal yang belum selesai.
4. Setelah mengerjakan fitur, periksa ulang kode baru terhadap checklist keamanan di atas.
5. Jangan refactor kode yang tidak diminta.
6. Jika instruksi pengguna bertentangan dengan PRD atau larangan di sini, sampaikan konfliknya sebelum lanjut.

## Menjalankan Proyek
- Server lokal: `php -S localhost:8000 -t public`
- Cek ekstensi: `php -m | grep pdo_pgsql`
- Skema dan seed dijalankan manual di Supabase SQL Editor (`database/schema.sql`, lalu `seed.sql`).
- `.env` berisi: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. Sediakan `.env.example` tanpa nilai asli.