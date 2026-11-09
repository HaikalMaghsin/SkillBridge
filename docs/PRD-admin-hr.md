# PRD: Perbaikan Modul Admin dan HR SkillBridge

> **CATATAN EVOLUSI ARSITEKTUR (FASE 2):** Dokumen ini merupakan spesifikasi Fase 1 (Fondasi Keamanan Admin & Privasi Dasar HR). Untuk pengembangan Fase 2 (Integrasi Ekosistem 3 Role, Target Role Perusahaan, & Shortlist Kandidat), sistem mengacu sepenuhnya pada docs/RUMUSAN_FINAL_ARSITEKTUR_SKILLBRIDGE.md dan docs/SPEC_EVIDENCE_DAN_ATS_SCANNER.md.

Disusun 5 November 2026. Jalur User (mahasiswa) dikembangkan secara paralel dan tidak boleh terganggu.

## 1. Latar belakang dan tujuan

SkillBridge adalah aplikasi PHP native + PostgreSQL (Supabase) dengan HTML/CSS/JS vanilla. Alur produk: user login, memilih target karier, menambah evidence, skill masuk manual atau otomatis dari evidence, HR melihat data user (read-only), admin mengelola user dan HR.

Analisis kode menemukan bahwa modul Admin dan HR belum sesuai alur tersebut: HR bisa menulis data, HR melihat semua mahasiswa tanpa memeriksa persetujuan, dan pengelolaan akun oleh admin punya celah keamanan. Dokumen ini mendefinisikan perbaikannya.

**Tujuan**

- Menjadikan HR benar-benar read-only dan hanya melihat profil yang diizinkan user.
- Menutup celah keamanan pada pengelolaan akun admin.
- Merapikan modul HR dan Admin dari fitur di luar alur produk.
- Semua dilakukan tanpa mengubah perilaku, tampilan, atau data jalur User.

**Bukan tujuan**

- Tidak ada skor, persentase kesiapan, bobot skill, maupun pertanyaan assessment.
- Tidak mengubah alur evidence, skill, CV extract, atau profil user.

## 2. Aturan produk yang tidak boleh dilanggar

1. Tidak ada penghitungan skor atau persentase kesiapan di mana pun (hanya jumlah dan label).
2. Role hanya: user, hr, admin.
3. HR read-only: tidak ada POST, PUT, atau DELETE yang bisa dipakai HR di endpoint mana pun.
4. HR hanya melihat profil dengan student\_profiles.is\_public = TRUE dan skill berstatus confirmed.
5. Semua mutasi memverifikasi CSRF dan memakai prepared statement (pola yang sudah ada dipertahankan).
6. Role tidak pernah diambil dari input client untuk registrasi publik.

## 3. Zona file

**Zona hijau: boleh diubah bebas**

- public/admin/*, public/hr/*
- public/api/admin/*, public/api/hr/*
- public/js/pages/admin-*.js, public/js/pages/recruiter*.js
- includes/layouts/admin\_*, includes/layouts/hr\_*
- tools/create\_admin.php

**Zona kuning: bersama, ubah hanya dengan aturan di bawah dan beri tahu pemilik jalur User**

- db/schema.sql: jangan diedit. Semua perubahan skema lewat file migration baru di db/migrations/.
- includes/auth/auth.php dan middleware.php: hanya perubahan kecil dan terisolasi (cek is\_active), tidak mengubah kontrak fungsi yang dipakai halaman user.
- public/js/api.js: hanya menambah atau mengubah fungsi di blok admin dan HR; fungsi user tidak disentuh.
- public/api/candidates.php: tidak ada pemanggil di repo (sudah diperiksa), boleh digabung ke api/hr/candidates.php atau dihapus.
- public/api/admin/master.php: perilaku ON DELETE CASCADE skill tidak diubah tanpa persetujuan tim.

**Zona merah: dilarang disentuh (milik jalur User)**

- public/mahasiswa/\*
- public/api/evidence.php, my-skills.php, gap.php, cv-extract.php, cv-confirm.php, profile.php
- public/js/pages untuk halaman user
- public/p.php, public/register.php (kecuali disepakati)
- public/api/evidence-debug.php, gap-debug.php, public/test-evidence.php (dihapus oleh pemilik jalur User)

Kalau sebuah tugas ternyata memerlukan perubahan di zona merah, berhenti dan laporkan; jangan dikerjakan.

## 4. Persyaratan fungsional

### Fase 1: prioritas P1 (privasi dan keamanan)

**A1. Validasi role di admin** (api/admin/users.php POST dan PUT)

- Hanya user, hr, admin yang diterima; nilai lain mengembalikan 400.
- Acceptance: request dengan role asing mengembalikan 400 dan tidak mengubah data.

**A2. Hapus password default**

- Tidak ada password default 'password123'. Password wajib, panjang 8 sampai 72 karakter.
- Acceptance: membuat user tanpa password atau dengan password pendek mengembalikan 400.

**A3. Lindungi admin dari kehilangan akses**

- Admin tidak bisa menurunkan role atau menonaktifkan dirinya sendiri; selalu ada minimal satu admin aktif.
- Acceptance: PUT yang melanggar mengembalikan 403 atau 409 dengan pesan jelas.

**A4. Kolom is\_active dan pengecekan di setiap request**

- Migration baru menambah users.is\_active BOOLEAN NOT NULL DEFAULT TRUE (data lama otomatis aktif).
- attempt\_login() menolak akun nonaktif; middleware memeriksa is\_active dan role terkini dari database, bukan hanya dari sesi.
- Acceptance: user yang dinonaktifkan atau diturunkan ditolak pada request berikutnya tanpa menunggu logout. Login dan semua halaman user tetap berfungsi untuk akun aktif.

**H1. HR hanya melihat profil yang diizinkan** (api/hr/candidates.php)

- Filter student\_profiles.is\_public = TRUE.
- Acceptance: user dengan is\_public = FALSE tidak muncul di daftar maupun detail HR.

**H2. HR read-only**

- Hapus atau nonaktifkan POST pipeline dan log aktivitas; endpoint HR hanya menerima GET (selain logout/sesi).
- Acceptance: POST, PUT, DELETE dari akun HR ke semua endpoint api/hr/\* mengembalikan 405 atau 403.

**H3. Minimalkan data**

- Hanya skill confirmed yang tampil. Email tidak tampil di daftar; di detail hanya untuk profil publik.
- Acceptance: respons HR tidak memuat password\_hash, public\_token, atau data internal lain.

### Fase 2: prioritas P2

- **A5.** Saat role diubah atau user dibuat oleh admin, buat atau hapus baris terkait (student\_profiles untuk user, company\_recruiters untuk hr). Acceptance: HR baru tidak mendapat 404 di halaman kandidat; user baru dari admin punya student\_profiles.
- **A6.** Validasi format email di PUT dan kembalikan 409 untuk email duplikat (bukan 500).
- **A7.** Ganti hapus permanen dengan penonaktifan (is\_active = FALSE) disertai konfirmasi di UI.
- **A8.** Admin dapat melihat dan mengelola akun HR (saat ini daftar hanya role = user). Periksa admin/admins.php dan api/admin/master.php.
- **A9.** Hapus master data (skill, karier) ditolak jika masih dipakai user, atau diganti arsip.
- **A10.** Keluarkan fitur lowongan (admin/vacancies.php dan api/admin/vacancies.php) dari alur utama: nonaktifkan rutenya dan sembunyikan menu, tanpa menghapus tabel database.
- **H4.** Cegah kandidat dobel: karena pipeline dinonaktifkan, tidak ada lagi baris pipeline di daftar kandidat.
- **H5.** Filter (target karier, skill, jumlah evidence), pencarian, dan pagination di daftar kandidat.
- **H6.** Ganti query N+1 (3 query per kandidat) dengan query terkelompok.
- **H7.** Halaman detail kandidat read-only: skill confirmed dan daftar evidence (judul, tipe, tautan dengan rel='noopener noreferrer').
- **H8.** Satukan api/candidates.php ke api/hr/candidates.php.
- **H9.** Putuskan perusahaan HR: pertahankan hanya sebagai nama di header atau hapus.
- **H11.** Audit api/hr/roles.php, company.php, team.php: semua operasi tulis dihapus atau dinonaktifkan.
- **X3.** Batasi percobaan login (rate limit sederhana per email dan IP).

### Fase 3: prioritas P3

- Catat aksi admin penting (ubah role, nonaktifkan, hapus master data) dan akses profil oleh HR.
- Pastikan api/admin/stats.php hanya menampilkan jumlah, tanpa skor atau persentase.
- Rapikan fungsi admin di api.js yang masih membaca data/admin-\*.json agar memakai API atau dihapus jika tidak dipakai.

## 5. Perubahan database

- Semua perubahan lewat file baru di db/migrations/ dengan nama berurutan, misalnya 2026-11-05\_01\_users\_is\_active.sql.
- Setiap migration harus kompatibel mundur: kolom baru memakai DEFAULT sehingga query jalur User yang lama tetap berjalan.
- Tidak ada DROP TABLE, DROP COLUMN, atau perubahan ON DELETE CASCADE pada tabel yang dipakai jalur User (users, student\_profiles, user\_skills, evidence, evidence\_skills, skills, careers, career\_skill\_requirements).
- Tabel HR berlebih (job\_vacancies, candidate\_pipelines, recruiter\_activity\_logs) tidak di-drop pada tahap ini; hanya tidak dipakai. Penghapusan tabel menunggu keputusan tim.
- Migration dijalankan dulu di database uji, bukan di database bersama.

## 6. Jaminan tidak mengganggu user biasa

**Prinsip**

- Tidak ada perubahan pada file di zona merah.
- Tidak ada perubahan respons, nama field, atau kode status di endpoint yang dipakai halaman user.
- Perubahan di zona kuning bersifat tambahan, bukan mengubah kontrak yang ada.

**Checklist regresi jalur User (dijalankan sebelum setiap PR digabung)**

1. Registrasi user baru berhasil dan membuat student\_profiles.
2. Login dan logout user berhasil; user nonaktif ditolak, user aktif tidak terpengaruh.
3. Memilih target karier berfungsi.
4. Menambah, mengubah, dan menghapus evidence berfungsi.
5. Halaman skill dan skill gap tampil seperti sebelum perubahan.
6. Upload dan konfirmasi CV berfungsi.
7. Halaman profil publik p.php tetap tampil untuk profil is\_public = TRUE.
8. User ditolak (403) saat mengakses halaman atau API admin dan HR.

**Pemeriksaan otomatis yang mudah**

- Sebelum PR: git diff --name-only main harus tidak memuat file dari zona merah.
- Hasil perbandingan respons endpoint user sebelum dan sesudah perubahan harus identik.

## 7. Ketergantungan pada jalur User

| Pekerjaan jalur User | Pengaruh pada Admin/HR |
| --- | --- |
| Skill dari evidence masuk user\_skills sebagai candidate dan dikonfirmasi (U2, U4) | H3 hanya efektif penuh setelah ini selesai; sebelum itu HR melihat skill dari jalur CV saja |
| Toggle is\_public dan kontrol privasi (U7) | H1 membaca kolom ini; jika default FALSE dan toggle belum ada, daftar HR bisa kosong. Periksa default kolom dan koordinasikan |
| Perbaikan hitungan evidence\_count (U1) | Query HR sudah menghitung e.id dengan benar, jadi tidak terpengaruh |

## 8. Rencana pengujian

- Uji manual per peran: user tidak bisa mengakses endpoint admin/HR; HR tidak bisa menulis; admin tidak bisa menurunkan atau menonaktifkan dirinya sendiri.
- Uji kasus tepi: role asing, email duplikat, password pendek, user is\_public = FALSE tidak terlihat HR, akun HR tanpa company\_recruiters.
- Jalankan checklist regresi jalur User (bagian 6).

## 9. Urutan PR

1. Migration is\_active + A4 + A1 + A2 + A3 (satu PR per item atau per kelompok kecil).
2. H2 (read-only) lalu H1 dan H3 (privasi).
3. Fase 2 satu per satu, mulai dari A5, A7, H5, H6.
4. Fase 3 dan pengujian akhir.

Setiap PR: satu tujuan, deskripsi singkat, daftar file yang diubah, dan hasil checklist regresi.

## 10. Risiko dan pertanyaan terbuka

- Modul pipeline dan lowongan: default PRD ini menonaktifkan, bukan menghapus tabel. Konfirmasi ke tim apakah ingin dihapus permanen.
- Apakah email kandidat boleh tampil ke HR untuk profil publik.
- Dasar persetujuan privasi (UU PDP No. 27/2022): konfirmasikan kebijakan ke pembimbing.
- File yang belum diperiksa saat analisis: api/hr/roles.php, company.php, team.php, activity.php, api/admin/master.php, stats.php, dan sebagian besar JS halaman. Audit dulu sebelum mengubah.
- Analisis ini berdasarkan pembacaan kode, bukan hasil menjalankan aplikasi.
