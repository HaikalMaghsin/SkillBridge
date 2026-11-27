// public/js/pages/recruiter-settings.js
// Pengaturan Lengkap Akun Recruiter & Perusahaan · SkillBridge

const TABS = ["company", "email", "security", "api"];
let pendingLogoBase64 = null;

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  const dateEl = document.getElementById("rec-today");
  if (dateEl) {
    dateEl.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
  }
}

function switchTab(name) {
  for (const t of TABS) {
    const active = t === name;
    const tabBtn = document.querySelector(`.rec-tab[data-tab="${t}"]`);
    const panel = document.getElementById(`tab-${t}`);
    if (tabBtn) {
      tabBtn.classList.toggle("active", active);
      tabBtn.setAttribute("aria-selected", String(active));
    }
    if (panel) {
      panel.classList.toggle("active", active);
    }
  }
}

function initTabs() {
  for (const t of TABS) {
    const tabBtn = document.querySelector(`.rec-tab[data-tab="${t}"]`);
    if (tabBtn) {
      tabBtn.addEventListener("click", () => switchTab(t));
    }
  }
}

// 1. Profil Perusahaan & Logo
async function loadCompanySettings() {
  if (typeof API === "undefined" || !API.getRecruiterCompany) return;
  try {
    const data = await API.getRecruiterCompany();
    const c = data?.company;
    if (!c) return;

    const nameEl = document.getElementById("company-name");
    const subEl = document.getElementById("settings-company-sub");
    const industryEl = document.getElementById("company-location");
    const websiteEl = document.getElementById("company-website");
    const descEl = document.getElementById("company-description");
    const logoImg = document.getElementById("company-logo-img");
    const defaultIcon = document.getElementById("company-logo-default-icon");

    if (nameEl) nameEl.value = c.name || "";
    if (subEl) subEl.textContent = `Informasi resmi ${c.name} yang ditampilkan kepada kandidat talenta di SkillBridge.`;
    if (industryEl) industryEl.value = c.industry || "";
    if (websiteEl) websiteEl.value = c.website || "";
    if (descEl) descEl.value = c.description || "";

    if (c.logo_url && logoImg) {
      logoImg.src = c.logo_url;
      logoImg.style.display = "block";
      if (defaultIcon) defaultIcon.style.display = "none";
    }
  } catch (err) {
    console.error("Gagal memuat profil perusahaan:", err);
  }
}

function initCompanyForm() {
  const form = document.getElementById("form-company-profile");
  const logoInput = document.getElementById("company-logo-file");
  const triggerBtn = document.getElementById("btn-trigger-logo");
  const logoStatus = document.getElementById("logo-file-status");
  const logoImg = document.getElementById("company-logo-img");
  const defaultIcon = document.getElementById("company-logo-default-icon");
  const saveBtn = document.getElementById("btn-save-company");

  if (triggerBtn && logoInput) {
    triggerBtn.addEventListener("click", () => logoInput.click());
  }

  if (logoInput) {
    logoInput.addEventListener("change", (e) => {
      const file = e.target.files?.[0];
      if (!file) return;

      if (file.size > 2 * 1024 * 1024) {
        UI.toast("Ukuran file logo maksimal 2MB", "error");
        logoInput.value = "";
        return;
      }

      const reader = new FileReader();
      reader.onload = async (ev) => {
        const base64 = ev.target.result;
        pendingLogoBase64 = base64;
        if (logoImg) {
          logoImg.src = base64;
          logoImg.style.display = "block";
          if (defaultIcon) defaultIcon.style.display = "none";
        }
        if (logoStatus) {
          logoStatus.textContent = "Mengunggah logo...";
          logoStatus.style.color = "#2563eb";
        }

        // Simpan langsung logo ke backend
        const compName = document.getElementById("company-name")?.value.trim() || "Perusahaan";
        try {
          const res = await API.updateRecruiterCompany({ name: compName, logo_base64: base64 });
          pendingLogoBase64 = null;
          if (logoStatus) {
            logoStatus.textContent = "Logo berhasil disimpan!";
            logoStatus.style.color = "#16a34a";
            setTimeout(() => { if (logoStatus) logoStatus.textContent = ""; }, 3000);
          }
          if (res?.company?.logo_url && logoImg) {
            logoImg.src = res.company.logo_url;
          }
          UI.toast("Logo perusahaan berhasil diperbarui!");
        } catch (err) {
          console.warn("Gagal auto-upload logo, akan disimpan bersama formulir:", err);
          if (logoStatus) {
            logoStatus.textContent = `${file.name} (klik Simpan untuk menerapkan)`;
            logoStatus.style.color = "#d97706";
          }
        }
      };
      reader.readAsDataURL(file);
    });
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = document.getElementById("company-name")?.value.trim() || "";
      const website = document.getElementById("company-website")?.value.trim() || "";
      const industry = document.getElementById("company-location")?.value.trim() || "";
      const description = document.getElementById("company-description")?.value.trim() || "";

      if (!name) {
        UI.toast("Nama perusahaan wajib diisi", "error");
        return;
      }

      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = "Menyimpan...";
      }

      try {
        const payload = { name, website, industry, description };
        if (pendingLogoBase64) {
          payload.logo_base64 = pendingLogoBase64;
        }

        const res = await API.updateRecruiterCompany(payload);
        UI.toast(res.message || "Profil perusahaan berhasil diperbarui!");
        pendingLogoBase64 = null;
        if (logoStatus) logoStatus.textContent = "";

        // Update header nama perusahaan bila ada
        const headerCompName = document.querySelector(".rec-header-company");
        if (headerCompName) headerCompName.textContent = name;
      } catch (err) {
        UI.toast(err.message || "Gagal memperbarui profil perusahaan", "error");
      } finally {
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.textContent = "Simpan Profil Perusahaan";
        }
      }
    });
  }
}

// 2. Profil Akun HR Pribadi & Avatar
function initHrProfileForm() {
  const form = document.getElementById("form-hr-profile");
  const avatarInput = document.getElementById("hr-avatar-file");
  const triggerBtn = document.getElementById("btn-trigger-avatar");
  const avatarStatus = document.getElementById("avatar-file-status");
  const avatarImg = document.getElementById("hr-avatar-img");
  const initialsSpan = document.getElementById("hr-avatar-initials");
  const saveBtn = document.getElementById("btn-save-hr-profile");

  if (triggerBtn && avatarInput) {
    triggerBtn.addEventListener("click", () => avatarInput.click());
  }

  if (avatarInput) {
    avatarInput.addEventListener("change", (e) => {
      const file = e.target.files?.[0];
      if (!file) return;

      if (file.size > 2 * 1024 * 1024) {
        UI.toast("Ukuran foto maksimal 2MB", "error");
        avatarInput.value = "";
        return;
      }

      const reader = new FileReader();
      reader.onload = async (ev) => {
        const base64 = ev.target.result;
        if (avatarStatus) {
          avatarStatus.textContent = "Mengunggah foto...";
          avatarStatus.style.color = "#2563eb";
        }

        try {
          const res = await API.uploadAvatar(base64);
          if (avatarImg) {
            avatarImg.src = res.avatar_url || base64;
            avatarImg.style.display = "block";
            if (initialsSpan) initialsSpan.style.display = "none";
          }
          if (avatarStatus) {
            avatarStatus.textContent = "Foto profil diperbarui!";
            avatarStatus.style.color = "#16a34a";
            setTimeout(() => { if (avatarStatus) avatarStatus.textContent = ""; }, 3000);
          }
          UI.toast("Foto profil berhasil diperbarui!");
        } catch (err) {
          if (avatarStatus) {
            avatarStatus.textContent = "Gagal mengunggah foto";
            avatarStatus.style.color = "#ef4444";
          }
          UI.toast(err.message || "Gagal mengunggah foto profil", "error");
        }
      };
      reader.readAsDataURL(file);
    });
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = document.getElementById("hr-name")?.value.trim() || "";
      const email = document.getElementById("hr-email")?.value.trim() || "";

      if (!name || !email) {
        UI.toast("Nama lengkap dan email wajib diisi", "error");
        return;
      }

      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = "Menyimpan...";
      }

      try {
        const res = await API.updateUserProfile({ name, email });
        UI.toast(res.message || "Profil akun berhasil diperbarui!");

        // Update display nama di header
        const hrNameHeader = document.querySelector(".rec-user-name");
        if (hrNameHeader) hrNameHeader.textContent = name;
      } catch (err) {
        UI.toast(err.message || "Gagal memperbarui profil akun", "error");
      } finally {
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.textContent = "Simpan Profil Akun";
        }
      }
    });
  }
}

// 3. Notifikasi Email & Test Email
async function loadEmailSettings() {
  if (typeof API === "undefined" || !API.getEmailSettings) return;
  try {
    const data = await API.getEmailSettings();
    const s = data?.settings;
    if (!s) return;

    const newAppEl = document.getElementById("pref-new-applicant");
    const pipeEl = document.getElementById("pref-pipeline-status");
    const intEl = document.getElementById("pref-interview");
    const weekEl = document.getElementById("pref-weekly-digest");
    const recipEl = document.getElementById("pref-recipient-email");

    if (newAppEl) newAppEl.checked = Boolean(s.notify_new_applicant);
    if (pipeEl) pipeEl.checked = Boolean(s.notify_pipeline_status);
    if (intEl) intEl.checked = Boolean(s.notify_interview_schedule);
    if (weekEl) weekEl.checked = Boolean(s.notify_weekly_digest);
    if (recipEl && s.recipient_email) recipEl.value = s.recipient_email;
  } catch (err) {
    console.error("Gagal memuat pengaturan email:", err);
  }
}

function initEmailForms() {
  const formPref = document.getElementById("form-email-preferences");
  const formTest = document.getElementById("form-test-email");
  const btnSavePref = document.getElementById("btn-save-email-pref");
  const btnSendTest = document.getElementById("btn-send-test-email");
  const resultBox = document.getElementById("test-email-result");

  if (formPref) {
    formPref.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (btnSavePref) {
        btnSavePref.disabled = true;
        btnSavePref.textContent = "Menyimpan...";
      }

      const payload = {
        notify_new_applicant: document.getElementById("pref-new-applicant")?.checked || false,
        notify_pipeline_status: document.getElementById("pref-pipeline-status")?.checked || false,
        notify_interview_schedule: document.getElementById("pref-interview")?.checked || false,
        notify_weekly_digest: document.getElementById("pref-weekly-digest")?.checked || false,
        recipient_email: document.getElementById("pref-recipient-email")?.value.trim() || ""
      };

      try {
        const res = await API.updateEmailSettings(payload);
        UI.toast(res.message || "Preferensi notifikasi email disimpan!");
      } catch (err) {
        UI.toast(err.message || "Gagal menyimpan preferensi email", "error");
      } finally {
        if (btnSavePref) {
          btnSavePref.disabled = false;
          btnSavePref.textContent = "Simpan Preferensi Email";
        }
      }
    });
  }

  if (formTest) {
    formTest.addEventListener("submit", async (e) => {
      e.preventDefault();
      const target = document.getElementById("test-email-target")?.value.trim() || "";
      if (!target) {
        UI.toast("Masukkan email tujuan pengujian", "error");
        return;
      }

      if (btnSendTest) {
        btnSendTest.disabled = true;
        btnSendTest.textContent = "Mengirim...";
      }
      if (resultBox) {
        resultBox.style.display = "none";
      }

      try {
        const res = await API.sendTestEmail(target);
        if (resultBox) {
          resultBox.style.display = "block";
          resultBox.style.padding = "12px 16px";
          resultBox.style.borderRadius = "8px";
          resultBox.style.background = "#f0fdf4";
          resultBox.style.border = "1px solid #bbf7d0";
          resultBox.style.color = "#166534";
          resultBox.style.fontSize = "13px";
          resultBox.innerHTML = `<strong>Sukses:</strong> ${res.message || "Email pengujian terkirim!"}`;
        }
        UI.toast("Email uji coba berhasil dikirim!");
      } catch (err) {
        if (resultBox) {
          resultBox.style.display = "block";
          resultBox.style.padding = "12px 16px";
          resultBox.style.borderRadius = "8px";
          resultBox.style.background = "#fef2f2";
          resultBox.style.border = "1px solid #fecaca";
          resultBox.style.color = "#991b1b";
          resultBox.style.fontSize = "13px";
          resultBox.innerHTML = `<strong>Gagal:</strong> ${err.message || "Gagal mengirim email uji coba"}`;
        }
        UI.toast(err.message || "Gagal mengirim email uji coba", "error");
      } finally {
        if (btnSendTest) {
          btnSendTest.disabled = false;
          btnSendTest.textContent = "Kirim Email Uji Coba";
        }
      }
    });
  }
}

// 4. Keamanan & Ubah Password
function initSecurityForm() {
  const form = document.getElementById("form-hr-password");
  const btn = document.getElementById("btn-change-password");

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const currPass = document.getElementById("hr-curr-password")?.value || "";
      const newPass = document.getElementById("hr-new-password")?.value || "";
      const confirmPass = document.getElementById("hr-confirm-password")?.value || "";

      if (!currPass || !newPass || !confirmPass) {
        UI.toast("Semua kolom kata sandi wajib diisi", "error");
        return;
      }

      if (newPass.length < 8) {
        UI.toast("Kata sandi baru minimal 8 karakter", "error");
        return;
      }

      if (newPass !== confirmPass) {
        UI.toast("Konfirmasi kata sandi baru tidak cocok", "error");
        return;
      }

      if (btn) {
        btn.disabled = true;
        btn.textContent = "Memperbarui...";
      }

      try {
        const res = await API.changeUserPassword(currPass, newPass);
        UI.toast(res.message || "Kata sandi berhasil diperbarui!");
        form.reset();
      } catch (err) {
        UI.toast(err.message || "Gagal memperbarui kata sandi", "error");
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = "Perbarui Kata Sandi";
        }
      }
    });
  }
}

// Inisialisasi Saat Halaman Siap
document.addEventListener("DOMContentLoaded", () => {
  renderDate();
  initTabs();
  loadCompanySettings();
  initCompanyForm();
  initHrProfileForm();
  loadEmailSettings();
  initEmailForms();
  initSecurityForm();
});

// Fallback langsung jika script dimuat di akhir dokumen
renderDate();
initTabs();
loadCompanySettings();
initCompanyForm();
initHrProfileForm();
loadEmailSettings();
initEmailForms();
initSecurityForm();
