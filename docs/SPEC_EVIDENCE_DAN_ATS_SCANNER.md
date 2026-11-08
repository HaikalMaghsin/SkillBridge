# Spesifikasi Teknis & Aturan AI: Modul Bukti Kompetensi (Evidence) & CV ATS Scanner

> **Catatan Integrasi (Zero Duplication):** Dokumen ini adalah **spesifikasi khusus modul Evidence (`public/mahasiswa/evidence.php`, `public/js/pages/evidence.js`, `public/api/evidence.php`)**. Aturan global proyek (Native PHP 8.x, Vanilla JS, Native CSS3, larangan framework, dan RBAC 3 Role) tetap mengacu pada `CLAUDE.md` dan `MASTER_BLUEPRINT_SKILLBRIDGE_PRD_DAN_AI_RULES.md`.

---

## 1. Kontrak Utama Modul Evidence

Modul **Bukti Kompetensi (Evidence)** berfungsi sebagai pusat pembuktian keahlian mahasiswa yang menentukan status kekuatan skill (**Strong** jika $\ge 2$ bukti terhubung, **Growing** jika $< 2$ bukti terhubung). Setiap perubahan kode pada modul ini wajib mematuhi 5 kontrak berikut:

| Komponen | Aturan & Batasan Wajib | Target Elemen / Endpoint |
| :--- | :--- | :--- |
| **1. Struktur Modal Scrollable** | `.modal-card` wajib `max-height: 90vh; display: flex; flex-direction: column;`, `.modal-body` wajib `overflow-y: auto; flex: 1;`, dan `.modal-footer` wajib sticky di bawah (`flex-shrink: 0`) agar tombol **Simpan / Perbarui Bukti** selalu terlihat di semua ukuran layar. | `public/mahasiswa/evidence.php`, `public/css/style.css` |
| **2. Field Lampiran Dinamis** | Input lampiran wajib menyesuaikan `source_type` yang dipilih: **`cv`** (Upload File `.pdf` + ATS Scanner), **`github` / `portfolio` / `project`** (Input URL valid `https://...`), **`certificate` / `assessment`** (Upload File `.pdf/.png/.jpg` atau Link Kredensial). | `#attachment-dynamic-section` di `public/js/pages/evidence.js` |
| **3. Pemilahan Akurat CV ATS** | Pemindaian PDF wajib menyusun ulang kata berdasarkan koordinat `(x, y)` (dilarang `.join(' ')` mentah), memilah section CV (`Ringkasan Profil`, `Pengalaman`, `Keahlian`), memilih otomatis 1–6 skill, dan menyetel otomatis dropdown **Level Bukti**. | `extractAndMatchSkillsFromPdf()` di `public/js/pages/evidence.js` |
| **4. Skill Picker Responsif Data** | Wajib menampilkan skill yang sudah dimiliki mahasiswa (`mySkills`) di urutan paling atas dengan badge `✓ Milikmu`, diikuti katalog skill lainnya, dengan batas pilih **1 s.d. 6 skill** per bukti. | `#skills-container`, `#ev-skill-search` |
| **5. Auto-Sync ke My Skills** | Saat bukti disimpan/diperbarui, setiap skill yang dipilih pada bukti tersebut namun belum ada di `user_skills` mahasiswa **wajib otomatis ditambahkan** melalui `API.addMySkill(skillId)`. | `handleFormSubmit()` di `public/js/pages/evidence.js` & `public/api/evidence.php` |

---

## 2. Aturan Ketat Ekstraksi Teks & Pemilahan CV ATS (Mozilla PDF.js)

AI Coding Assistant **DILARANG** menggabungkan item teks dari `page.getTextContent().items` menggunakan `.map(i => i.str).join(' ')` karena PDF dengan *font kerning* akan menghasilkan teks renggang per huruf (contoh: `"F u ll s t a c k"`, `"P y t h o n"`) yang merusak akurasi Regex dan Ringkasan.

### 2.1. Aturan Rekonstruksi Teks Berbasis Koordinat (`X` dan `Y`)
1. **Pengelompokan Baris (`Y-Coordinate Grouping`):**
   - Kelompokkan item teks yang memiliki selisih vertikal `Math.abs(item.transform[5] - lineY) <= 3.5` ke dalam satu baris yang sama.
   - Urutkan baris dari atas ke bawah (`y` menurun pada sistem koordinat PDF) dan urutkan item di dalam baris dari kiri ke kanan (`x = item.transform[4]` menaik).
2. **Penggabungan Kata Tanpa Merusak Kerning (`X-Gap Calculation`):**
   - Hitung jarak horizontal antar-item berurutan di baris yang sama: `gap = item.x - (prevItem.x + (prevItem.width || 0))`.
   - **Jika `gap < 2.2`** (atau item sebelumnya berakhir dengan `-`, atau item saat ini adalah tanda baca `,.;:)`): Gabungkan **TANPA SPASI (`""`)** agar potongan kerning `"P" + "y" + "t" + "h" + "o" + "n"` menyatu menjadi `"Python"`.
   - **Jika `gap >= 2.2`**: Gabungkan dengan **satu spasi (`" "`)**.
3. **Normalisasi Fallback Huruf Tunggal:**
   - Sebelum pencocokan, jalankan normalisasi untuk menyatukan rangkaian huruf kapital/kecil tunggal yang terpisah spasi akibat encoding font PDF (misal `/(?<=\b[A-Za-z])\s+(?=[A-Za-z]\b)/g`).

### 2.2. Aturan Pemilahan 3 Komponen Utama dari CV ATS

#### A. Pemilahan "Ringkasan Kontribusi" (`#input-summary`)
- **DILARANG** mengambil baris pertama dokumen CV (karena baris teratas berisi Nama, Jabatan, Email, Nomor Telepon, dan Domisili).
- Sistem wajib memecah baris dokumen ke dalam blok section berdasarkan judul baris (`RINGKASAN PROFIL`, `TENTANG SAYA`, `PROFIL`, `SUMMARY`, `PROFESSIONAL SUMMARY`, `ABOUT ME`, `PENGALAMAN`, `EXPERIENCE`, `KEAHLIAN`, `SKILLS`, `PENDIDIKAN`, `EDUCATION`).
- **Urutan Prioritas Pengisian `#input-summary`:**
  1. **Prioritas 1:** Isi dari blok section **`summary`** (*Ringkasan Profil / Summary*), dibersihkan dari judul section-nya (maksimal 350 karakter).
  2. **Prioritas 2 (Jika CV tidak memiliki section Summary):** Ambil 1–2 kalimat deskripsi pencapaian pertama dari blok section **`experience`** (*Pengalaman Proyek / Kerja*).

#### B. Pemilahan "Skill yang Didukung" (1–6 Skill)
- Pencocokan dilakukan secara *case-insensitive* menggunakan batas kata utuh (`\b...\b`) terhadap katalog `skills` dari database ditambah kamus sinonim (`SKILL_SYNONYMS`).
- Jika terdeteksi **lebih dari 6 skill** di dalam CV, sistem wajib mengurutkan dan memilih **6 skill teratas** dengan prioritas:
  1. Skill yang menjadi syarat pada **Target Career** aktif mahasiswa (`career_skill_requirements`).
  2. Skill yang sudah tercatat di **My Skills** mahasiswa (`user_skills`).
  3. Skill lainnya sesuai urutan kemunculan di CV.

#### C. Penentuan Otomatis "Level yang Ditunjukkan Bukti" (`#input-level`)
Saat pemindaian CV ATS selesai, sistem wajib menentukan dan memilih otomatis nilai dropdown `#input-level` berdasarkan indikator objektif pada teks `experience` dan `summary`:
1. **`advanced` (Mahir — mampu memimpin / merancang solusi kompleks):**
   - Terdeteksi kata kunci seperti `2+ tahun`, `3+ tahun`, `2+ years`, `3+ years`, `senior`, `lead`, `ketua`, `architect`, `production`, **ATAU** terdapat $\ge 2$ riwayat proyek/pengalaman dengan $\ge 8$ skill teknis yang terdeteksi di CV.
2. **`intermediate` (Menengah — mampu mengaplikasikan pada proyek nyata):**
   - Terdeteksi kata kunci praktik nyata seperti `intern`, `internship`, `magang`, `freelance`, `proyek`, `project`, `pjbl`, `pbl`, `capstone`, `asisten`, `1+ tahun`, **ATAU** terdeteksi $4\text{–}7$ skill teknis di CV.
3. **`basic` (Dasar — memahami konsep):**
   - Jika teks CV hanya mencantumkan pelatihan dasar/kursus pengenalan dan terdeteksi $< 4$ skill tanpa kata kunci proyek/magang.
- *Catatan UX:* Mahasiswa tetap diizinkan mengubah pilihan dropdown `#input-level` secara manual setelah nilai otomatis disetel oleh sistem.

---

## 3. Aturan Validasi & Keamanan Penyimpanan Bukti (`API.addEvidence` & `API.updateEvidence`)

1. **Validasi Input Sebelum Submit:**
   - `title` (Judul bukti): Wajib diisi, minimal 3 karakter, maksimal 150 karakter.
   - `source_type` (Jenis bukti): Wajib salah satu dari `'cv'`, `'github'`, `'portfolio'`, `'project'`, `'certificate'`, `'assessment'`.
   - `skill_ids` (Skill yang didukung): Wajib memilih minimal **1 skill** dan maksimal **6 skill**.
   - `file_url` / `attachment`: Wajib berisi nama berkas yang diunggah atau URL valid (`https://...` untuk jenis `github`, `portfolio`, `project`).
2. **Keamanan & Isolasi Data:**
   - Pada `public/api/evidence.php`, `user_id` wajib selalu diambil dari `current_user()['id']` di session PHP.
   - Saat mengedit (`PUT`) atau menghapus (`DELETE`) bukti, query wajib memverifikasi `WHERE id = :id AND user_id = :user_id` agar pengguna tidak dapat memodifikasi bukti milik mahasiswa lain.
   - Seluruh manipulasi teks hasil ekstraksi PDF ke dalam DOM wajib menggunakan `.value` atau `.textContent` (dilarang menyuntikkan teks mentah dari PDF ke dalam `.innerHTML` untuk mencegah *PDF-based XSS*).
