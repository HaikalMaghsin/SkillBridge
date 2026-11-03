# TROUBLESHOOTING GUIDE - Database Login/Register Issues

## Masalah: Tidak bisa Login/Register

### Langkah 1: Cek PostgreSQL Running
```bash
# Windows (PowerShell)
Get-Service -Name postgresql*

# Atau cek di Services (tekan Win+R, ketik services.msc)
# Cari service "postgresql" dan pastikan statusnya "Running"
```

Jika tidak running, start servicenya:
```bash
# PowerShell (Run as Administrator)
Start-Service postgresql-x64-16  # sesuaikan dengan versi PostgreSQL kamu
```

---

### Langkah 2: Cek Database Sudah Dibuat
```bash
# Buka psql (PostgreSQL command line)
psql -U postgres

# Di dalam psql, cek list database:
\l

# Cari database bernama "skillbridge"
```

**Jika database `skillbridge` BELUM ada**, buat dulu:
```sql
CREATE DATABASE skillbridge;
\q
```

---

### Langkah 3: Import Schema & Seed Data

Jalankan file SQL untuk membuat tabel-tabel:

```bash
# Windows Command Prompt atau PowerShell
# Pastikan kamu di folder D:\Skillbridge

# 1. Import schema (buat tabel)
psql -U postgres -d skillbridge -f db\schema.sql

# 2. Import seed data (data awal: skills, careers)
psql -U postgres -d skillbridge -f db\seed.sql
```

Jika muncul prompt password, masukkan password postgres kamu: `PasswordBaru123!`

---

### Langkah 4: Verifikasi Tabel Sudah Ada

```bash
# Masuk ke database skillbridge
psql -U postgres -d skillbridge

# Cek tabel yang ada:
\dt

# Seharusnya muncul tabel:
# - users
# - careers
# - skills
# - career_skill_requirements
# - student_profiles
# - evidence
# - user_skills
# - evidence_skills

# Cek jumlah skills & careers:
SELECT COUNT(*) FROM skills;    -- Seharusnya 18 rows
SELECT COUNT(*) FROM careers;   -- Seharusnya 4 rows

# Keluar:
\q
```

---

### Langkah 5: Test di Browser

1. **Jalankan PHP server:**
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Test koneksi database:**
   Buka: http://localhost:8000/test-db.php
   
   Seharusnya muncul tanda centang hijau:
   - ✓ Database connected successfully!
   - ✓ Table 'users' exists
   - ✓ Table 'careers' exists
   - ✓ Table 'skills' exists

3. **Coba Register:**
   Buka: http://localhost:8000/register.php
   
   Isi form dan klik "Create Account"

4. **HAPUS file test setelah berhasil:**
   ```bash
   del public\test-db.php
   ```

---

## Checklist Troubleshooting

- [ ] PostgreSQL service running?
- [ ] Database `skillbridge` sudah dibuat?
- [ ] File `db/schema.sql` sudah di-import?
- [ ] File `db/seed.sql` sudah di-import?
- [ ] File `.env` sudah ada dan isinya benar?
- [ ] Password postgres di `.env` sesuai dengan password asli?
- [ ] PHP extension `pdo_pgsql` aktif? (cek `php -m | grep pdo_pgsql`)

---

## Error Umum & Solusi

### Error: "could not connect to server"
- PostgreSQL belum running → Start servicenya

### Error: "database skillbridge does not exist"
- Database belum dibuat → Jalankan `CREATE DATABASE skillbridge;`

### Error: "relation users does not exist"
- Schema belum di-import → Jalankan `psql -U postgres -d skillbridge -f db\schema.sql`

### Error: "FATAL: password authentication failed"
- Password salah → Cek password di `.env` sesuai dengan password postgres asli

### Error: "could not find driver"
- Extension pdo_pgsql tidak aktif → Aktifkan di `php.ini`:
  ```
  extension=pdo_pgsql
  extension=pgsql
  ```

---

## Bandingkan dengan Teman yang Berhasil

Tanya temanmu:
1. Apakah mereka sudah jalankan `schema.sql` dan `seed.sql`?
2. Apakah PostgreSQL mereka running?
3. Apakah mereka buat database `skillbridge` dulu?

Kemungkinan besar temanmu sudah setup database sedangkan kamu belum.

---

## Jika Masih Gagal

Screenshot error yang muncul di:
1. Browser (saat register/login)
2. Output dari `http://localhost:8000/test-db.php`
3. Terminal saat import schema.sql

Dan share ke grup/dosen/asisten.
