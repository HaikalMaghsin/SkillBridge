# 📝 PANDUAN PEMBUATAN AKUN HR & ADMIN

## 🎯 Overview

SkillBridge memiliki 3 tipe role user:
1. **Mahasiswa (Student)** - Bisa register via halaman `/register.php`
2. **HR / Recruiter** - Harus dibuat via CLI (keamanan)
3. **Admin** - Harus dibuat via CLI (keamanan)

---

## 🔐 Kenapa HR & Admin Tidak Bisa Register Sendiri?

**Alasan Keamanan:**
- HR dan Admin memiliki akses sensitif (data kandidat, moderasi, CRUD master data)
- Mencegah siapa saja mendaftar sebagai HR/Admin tanpa verifikasi
- Hanya pemilik server/database yang bisa membuat akun HR/Admin

**Flow Production:**
1. Perusahaan menghubungi platform owner
2. Platform owner verifikasi identitas perusahaan
3. Platform owner membuat akun HR via CLI
4. HR login dan mulai menggunakan sistem

---

## 🚀 CARA MEMBUAT AKUN

### 1️⃣ Membuat Akun Mahasiswa (via Web Browser)

**Cara 1: Register Manual**
```
1. Buka browser: http://localhost:8000/register.php
2. Isi form:
   - Nama lengkap: Jhon Doe
   - Email: jhondoe@gmail.com
   - Password: password123
3. Klik "Register"
4. Login otomatis → redirect ke dashboard mahasiswa
```

**Cara 2: via CLI (alternatif)**
```bash
php tools/create_user.php mahasiswa jhondoe@gmail.com "Jhon Doe"
# Input password saat diminta
```

---

### 2️⃣ Membuat Akun HR (via CLI Only)

**Interactive Mode (Recommended):**
```bash
php tools/create_user.php

# Output:
╔════════════════════════════════════════════════════════════════╗
║  SkillBridge - Create User Account                            ║
╚════════════════════════════════════════════════════════════════╝

Pilih role user:
  1. Mahasiswa (Student)
  2. HR / Recruiter
  3. Admin

Pilihan (1-3): 2

Email: hr@techindonesia.com
Nama lengkap: Aditya Pratama
Password (min. 8 karakter): ********

✅ User berhasil dibuat!

╔════════════════════════════════════════════════════════════════╗
║  USER CREDENTIALS                                              ║
╠════════════════════════════════════════════════════════════════╣
║  Role  : Hr                                                    ║
║  Email : hr@techindonesia.com                                  ║
║  Name  : Aditya Pratama                                        ║
╚════════════════════════════════════════════════════════════════╝

ℹ️  INFO: Saat login pertama kali sebagai HR, sistem akan otomatis:
   - Membuat atau menghubungkan ke perusahaan default
   - User HR bisa langsung mengakses dashboard rekrutmen

🚀 Login URL: http://localhost:8000/login.php
```

**Command Line Mode (Quick):**
```bash
php tools/create_user.php hr hr@techindonesia.com "Aditya Pratama"
# Input password saat diminta
```

**Auto-Provision Perusahaan:**
- Saat HR login pertama kali, sistem otomatis:
  1. Cek apakah HR sudah terhubung ke perusahaan
  2. Jika belum, sistem buat perusahaan "Default Company"
  3. Link HR user ke perusahaan tersebut
  4. HR bisa langsung akses dashboard

**Update Perusahaan HR:**
- Setelah login, HR bisa update profil perusahaan di `hr/settings.php`
- Ganti nama dari "Default Company" ke "PT Tech Indonesia"
- Isi detail: industri, website, deskripsi

---

### 3️⃣ Membuat Akun Admin (via CLI Only)

**Interactive Mode:**
```bash
php tools/create_user.php

# Pilih: 3. Admin
# Email: admin@skillbridge.id
# Nama: Admin Utama
# Password: ********
```

**Command Line Mode:**
```bash
php tools/create_user.php admin admin@skillbridge.id "Admin Utama"
```

**Legacy Script (masih bisa digunakan):**
```bash
php tools/create_admin.php admin@skillbridge.id "Admin Utama"
# Input password saat diminta
```

---

## 📊 DATA DEMO (untuk Testing)

Jika ingin data demo lengkap (perusahaan, lowongan, kandidat):

```bash
# 1. Buat user HR terlebih dahulu
php tools/create_user.php hr hr@techindonesia.com "Aditya Pratama"

# 2. Buat user Mahasiswa (atau register via web)
php tools/create_user.php mahasiswa jhondoe@gmail.com "Jhon Doe"

# 3. Jalankan seeder
php tools/seed_demo.php
```

**Seeder akan membuat:**
- Perusahaan: PT Tech Indonesia
- 3 Lowongan aktif (Backend, Frontend, Data Analyst)
- Sample evidence untuk mahasiswa
- Link HR ke perusahaan
- Candidate pipeline (Jhon Doe → Shortlisted)

---

## 🔄 FLOW LENGKAP

### Flow Mahasiswa
```
1. Register via web (register.php)
   ↓
2. Login → Dashboard mahasiswa
   ↓
3. Pilih target career
   ↓
4. Upload CV → detect skills
   ↓
5. Tambah evidence → portfolio
   ↓
6. Enable public portfolio
   ↓
7. Share link: p.php?token=xxx
```

### Flow HR
```
1. Platform owner buat akun via CLI
   ↓
2. HR login → Auto-provision perusahaan
   ↓
3. Update profil perusahaan (settings)
   ↓
4. Buat lowongan (Target Roles)
   ↓
5. View kandidat mahasiswa riil
   ↓
6. Shortlist → Interview → Accept/Reject
   ↓
7. View activity logs & metrics
```

### Flow Admin
```
1. Platform owner buat akun via CLI
   ↓
2. Admin login → Dashboard admin
   ↓
3. Moderate lowongan perusahaan
   ↓
4. CRUD master skills & careers
   ↓
5. Manage users (approve/suspend)
   ↓
6. View platform statistics
```

---

## ❓ FAQ

### Q: Kenapa tidak ada halaman register untuk HR/Admin?
**A:** Keamanan. HR/Admin punya akses sensitif dan harus diverifikasi manual oleh platform owner.

### Q: Bagaimana cara perusahaan mendaftar?
**A:** Perusahaan hubungi platform owner → verifikasi → platform owner buat akun HR via CLI.

### Q: Bisa update role user yang sudah ada?
**A:** Bisa! Jalankan script dengan email yang sama:
```bash
php tools/create_user.php admin existing@email.com "Name"
# Script akan tanya konfirmasi untuk update role
```

### Q: Password lupa, gimana reset?
**A:** Via CLI, update password user:
```bash
php tools/create_user.php <role> <email> "Name"
# Input password baru
# Script akan update password user tersebut
```

### Q: Bisa buat multiple HR untuk 1 perusahaan?
**A:** Bisa! Buat akun HR lainnya via CLI, lalu di dashboard HR:
1. Login sebagai HR pertama
2. Masuk menu Team Management (hr/team.php)
3. Add team member (input user_id HR kedua)
4. HR kedua bisa akses dashboard perusahaan yang sama

### Q: HR user bisa pindah perusahaan?
**A:** Bisa via database atau fitur Team Management. Admin bisa update `company_recruiters` untuk re-assign HR ke perusahaan lain.

---

## 🛡️ SECURITY NOTES

### Password Storage
- Password di-hash dengan `password_hash()` (bcrypt)
- Tidak ada yang bisa melihat password plaintext
- Minimum 8 karakter, maximum 72 karakter

### Role Validation
- Role disimpan di database: `user`, `hr`, `admin`
- Role di aplikasi: `mahasiswa`, `hr`, `admin`
- Mapping otomatis di `includes/auth/auth.php`

### CLI Script Protection
- Semua script tools/*.php punya guard `PHP_SAPI === 'cli'`
- Tidak bisa diakses via browser (HTTP 403)
- Hanya bisa dijalankan dari terminal

---

## 🧪 TESTING CREDENTIALS

Untuk demo/testing, buat 3 akun:

```bash
# Admin
php tools/create_user.php admin admin@skillbridge.id "Admin Utama"
# Password: admin123

# HR
php tools/create_user.php hr hr@techindonesia.com "Aditya Pratama"
# Password: hr123

# Mahasiswa (via web atau CLI)
php tools/create_user.php mahasiswa jhondoe@gmail.com "Jhon Doe"
# Password: student123
```

Atau gunakan seeder otomatis:
```bash
php tools/seed_demo.php
# Akan link HR yang sudah ada ke PT Tech Indonesia
```

---

## 📞 SUPPORT

Jika ada masalah:
1. Cek koneksi database: `php tools/test-db.php`
2. Cek user di database: `SELECT * FROM users;`
3. Cek logs error di terminal saat menjalankan script
4. Pastikan PostgreSQL sudah running

---

**Dokumen dibuat:** 26 Oktober 2026  
**Update terakhir:** 26 Oktober 2026  
**Versi:** 1.0
