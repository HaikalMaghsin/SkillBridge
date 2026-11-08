-- 2026-11-05_01_users_is_active.sql
-- A4: status aktif akun pengguna.
--
-- Kompatibel mundur:
--   - kolom baru memakai DEFAULT TRUE, sehingga seluruh baris lama otomatis aktif
--     dan query jalur User tetap berjalan tanpa perubahan;
--   - tidak ada DROP TABLE / DROP COLUMN;
--   - tidak ada perubahan ON DELETE CASCADE pada tabel jalur User.
--
-- Dijalankan lebih dulu di database uji, bukan di database bersama.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE;

COMMENT ON COLUMN users.is_active IS
    'FALSE = akun dinonaktifkan admin; ditolak saat login dan pada request berikutnya.';
