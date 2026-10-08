# CLAUDE.md - SkillBridge (Front End)

## Konteks
SkillBridge adalah proyek PjBL kelompok mata kuliah Desain dan Pemrograman Web. Aplikasi web dinamis tanpa framework. Backend (PHP native + Supabase PostgreSQL) dikerjakan anggota tim lain.
**Sebelum mengerjakan apa pun, baca `docs/PRD.md`, `docs/API.md` (jika ada), dan lihat screenshot terkait di `docs/design/`.** PRD adalah sumber kebenaran fitur; mockup hanya acuan visual.

## Peran: FRONT END ONLY
- **Boleh diubah:** `public/css/`, `public/js/`, markup halaman di `public/`, `data/*.json` (data dummy), `docs/API.md`.
- **Dilarang diubah:** `src/`, `database/`, `.env`, `.env.example`, logika auth/query/CSRF di sisi server, `docs/PRD.md`, `CLAUDE.md`. Jika butuh perubahan di area itu, jelaskan kebutuhannya dan tulis di `docs/API.md` sebagai permintaan ke backend.
- Jika PRD, mockup, atau instruksi saling bertentangan, sampaikan konfliknya sebelum lanjut.

## Tech Stack
- HTML5 semantik, CSS3 native (Flexbox/Grid, CSS variables), JavaScript vanilla (DOM, Event, Fetch API).
- Dilarang: framework/library eksternal (React, Vue, Tailwind, Bootstrap, jQuery, ikon/font dari CDN pihak ketiga selain Google Fonts Inter), bundler, npm package.

## Struktur Folder (area front end)
```
public/
├── *.html atau *.php     # halaman (lihat "Keputusan tim")
├── css/style.css         # tokens + komponen
├── js/
│   ├── api.js            # SATU-SATUNYA tempat pengambilan data
│   ├── ui.js             # helper DOM (render list, toast, modal)
│   └── pages/            # satu file JS per halaman (career.js, skills.js, ...)
data/                     # data dummy: careers.json, skills.json, evidence.json
docs/                     # PRD.md, API.md, design/*.jpg
```

## Keputusan tim (default, ubah jika backend meminta)
- Halaman: `.html` statis yang memanggil `api.js`. Jika tim memutuskan `.php`, markup tetap sama dan bagian dinamis diserahkan ke backend.
- Nama halaman: `login`, `register`, `dashboard`, `skills`, `evidence`, `career`, `gap`, `profile`, `cv`, `cv-review`.
- Navbar: Dashboard, My Skills, Evidence, Career Target, Skill Gap, Profile.
- Teks antarmuka Bahasa Inggris seperti mockup.

## Lapisan Data (`public/js/api.js`)
- Semua pengambilan/pengiriman data **hanya** lewat fungsi di `api.js`. Halaman dan komponen tidak boleh memanggil `fetch` langsung.
- Fungsi: `getCareers()`, `setTarget(id)`, `getMySkills()`, `addSkill()`, `removeSkill()`, `getEvidence()`, `addEvidence()`, `removeEvidence()`, `getSkillGap()`, dan lainnya sesuai `docs/API.md`.
- Mode dummy: baca `data/*.json` dan simpan perubahan di `localStorage`. Saat backend siap, hanya isi `api.js` yang diganti ke endpoint asli (`api/*.php`); halaman tidak berubah.
- Setiap fungsi diberi komentar satu baris berisi endpoint asli yang akan menggantikannya.
- Request ke server (mode asli) menyertakan header `X-CSRF-Token` yang dibaca dari `<meta name="csrf-token">`; form `POST` memuat `<input type="hidden" name="csrf_token">` (nilai diisi backend).
- Bentuk data mengikuti `docs/API.md`. Jika butuh field baru, tambahkan ke `docs/API.md` dan beri tahu pengguna.

## Aturan Kode
- **XSS:** data dinamis dirender dengan `textContent`/`createElement`. Jangan memakai `innerHTML` dengan data dari pengguna atau API. Atribut URL hanya boleh skema `http`/`https`; link keluar memakai `target="_blank" rel="noopener noreferrer"`.
- **CSS:** semua warna, radius, shadow, spacing di `:root` sebagai variabel. Font Inter dengan fallback `system-ui, sans-serif`. Komponen reusable: `.navbar`, `.nav-pill`, `.card`, `.btn`, `.btn-primary`, `.btn-accent`, `.tag`, `.badge`. Penamaan kelas kebab-case; hindari selector ID untuk styling.
- **Responsif:** mobile-first dengan media query; uji di 375px, 768px, 1280px.
- **Aksesibilitas:** elemen semantik (`header`, `nav`, `main`, `section`), `<label>` untuk setiap input, `alt` pada gambar, `aria-label` pada tombol ikon, fokus keyboard terlihat, kontras teks cukup.
- **JS:** `const`/`let`, fungsi kecil, tanpa variabel global kecuali yang di-export lewat satu namespace; event handling dengan `addEventListener`. Validasi form di JS hanya pelengkap UX (server tetap memvalidasi).
- Satu file JS per halaman; logika bersama di `ui.js`.
- Komentar hanya untuk logika yang tidak jelas.

## Fitur Non-Scope (jangan dibuat)
Skor kesiapan karier, persentase kecocokan/match rate, progress bar persentase, teks "x of y skills covered", analisis GitHub, lowongan kerja. Skill gap hanya dua daftar: *Skills You Already Have* dan *What to Explore Next*; boleh menampilkan jumlah, bukan persentase atau rasio.

## Aturan Tampilan dari Mockup
- Tiru layout, warna, tipografi, dan komponen dari `docs/design/`.
- **Abaikan data contoh di mockup** (Node.js, Express.js, "Product Designer", angka, tanggal). Pakai data dari `data/*.json` sesuai seed di PRD bagian 7.
- Label skill: **Strong** jika ≥ 2 evidence, **Growing** jika kurang (dihitung di JS dari data).

## Cara Kerja
1. Satu halaman/fitur per sesi. Jangan menyentuh halaman lain.
2. Untuk tugas besar, buat rencana singkat dulu dan tunggu persetujuan.
3. Setelah selesai, laporkan: file yang dibuat/diubah, cara mengujinya (URL/langkah klik), dan hal yang belum selesai.
4. Setelah selesai, periksa kode baru terhadap "Aturan Kode" dan "Fitur Non-Scope".
5. Jangan refactor atau memformat ulang kode yang tidak diminta.
6. Kerja di branch `frontend`; jangan commit langsung ke `main`.

## Menjalankan
- Preview: ekstensi **Live Server** di VS Code (agar `fetch` ke file JSON berjalan), atau `php -S localhost:8000 -t public` jika memakai halaman `.php`.