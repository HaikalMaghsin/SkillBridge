// admin-settings.js - halaman pengaturan sistem (Admin Console)
// Terhubung ke PostgreSQL untuk profil admin

document.addEventListener("DOMContentLoaded", async () => {
  // Load profil admin dari session/API
  try {
    const response = await fetch(`${window.APP_BASE_URL}/api/profile.php`, {
      headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
    });

    if (response.ok) {
      const profile = await response.json();

      // Populate form dengan data dari database
      const formProfile = document.getElementById("form-admin-profile");
      if (formProfile) {
        formProfile.name.value = profile.name || '';
        formProfile.email.value = profile.email || '';
      }
    }
  } catch (err) {
    console.error('Error loading admin profile:', err);
  }

  // Handler submit profil administrator
  const formProfile = document.getElementById("form-admin-profile");
  if (formProfile) {
    formProfile.addEventListener("submit", async (e) => {
      e.preventDefault();

      const name = formProfile.name.value.trim();
      const email = formProfile.email.value.trim();
      const password = formProfile.password.value.trim();

      if (!name || !email) {
        UI.toast("Nama dan email wajib diisi", "error");
        return;
      }

      try {
        const payload = { name, email };
        if (password) {
          payload.password = password;
        }

        const response = await fetch(`${window.APP_BASE_URL}/api/profile.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
          },
          body: JSON.stringify(payload)
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.error || 'Failed to update profile');
        }

        UI.toast("Profil administrator berhasil diperbarui");

        // Clear password field
        formProfile.password.value = '';

        // Reload profile
        setTimeout(() => window.location.reload(), 1500);
      } catch (err) {
        console.error('Error updating profile:', err);
        UI.toast(err.message || 'Gagal memperbarui profil', 'error');
      }
    });
  }

  // Handler konfigurasi platform (untuk fase mendatang - saat ini hanya UI)
  const formConfig = document.getElementById("form-platform-config");
  if (formConfig) {
    formConfig.addEventListener("submit", (e) => {
      e.preventDefault();

      // Simpan sementara ke localStorage (untuk fase mendatang akan ke DB)
      const config = {
        site_name: formConfig.site_name.value.trim(),
        max_evidence: Number(formConfig.max_evidence.value),
        default_visibility: formConfig.default_visibility.value,
        registration_open: formConfig.registration_open.checked,
      };

      localStorage.setItem("sb.admin.config", JSON.stringify(config));
      UI.toast("Konfigurasi platform disimpan (lokal)");
    });

    // Load saved config if any
    const saved = localStorage.getItem("sb.admin.config");
    if (saved) {
      try {
        const cfg = JSON.parse(saved);
        if (cfg.site_name) formConfig.site_name.value = cfg.site_name;
        if (cfg.max_evidence) formConfig.max_evidence.value = cfg.max_evidence;
        if (cfg.default_visibility) formConfig.default_visibility.value = cfg.default_visibility;
        if (typeof cfg.registration_open === "boolean") formConfig.registration_open.checked = cfg.registration_open;
      } catch {}
    }
  }
});
