// profile.js - My Profile page

(async () => {
  const { el } = UI;

  // ---- Profile data (loaded from API backend) ----
  let profile = {};

  // Load profile from API
  async function loadProfile() {
    try {
      const data = await API.getProfile();
      profile = {
        name: data.name,
        email: data.email,
        institution: data.institution || "Politeknik Negeri Malang",
        program: data.study_program || data.program || "Teknik Informatika",
        semester: String(data.semester || "3"),
        location: data.location || "Malang, Indonesia",
        memberSince: data.created_at ? new Date(data.created_at).toLocaleDateString("id-ID", { month: "long", year: "numeric" }) : "Januari 2026",
        photo: data.avatar_url || null,
        is_public: data.is_public || false,
        public_url: data.public_url || null
      };
      return profile;
    } catch (err) {
      console.error('Failed to load profile:', err);
      return {
        name: "Student",
        email: "student@example.com",
        institution: "Politeknik Negeri Malang",
        program: "Teknik Informatika",
        semester: "3",
        location: "Malang, Indonesia",
        memberSince: "Januari 2026",
        photo: null,
        is_public: false
      };
    }
  }

  // Wait for profile to load
  await loadProfile();

  // ---- Load dynamic counts ----
  const [careers, mine, evidence, targetId] = await Promise.all([
    API.getCareers(),
    API.getMySkills(),
    API.getEvidence(),
    API.getTargetCareer()
  ]);
  const target = careers.find((c) => c.id === targetId) || null;

  // ---- Render static fields ----
  const $ = (id) => document.getElementById(id);

  // Mockup shows a user-round icon in the blue avatar circle; a chosen photo replaces it.
  const USER_ICON = `<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>`;

  function renderAvatar() {
    const box = $("profile-avatar");
    UI.clear(box);
    if (profile.photo) {
      box.append(el("img", { src: profile.photo, alt: profile.name }));
    } else {
      box.innerHTML = USER_ICON;
    }
  }

  function render() {
    renderAvatar();
    $("profile-display-name").textContent = profile.name;
    $("detail-fullname").textContent = profile.name;
    $("detail-email").textContent = profile.email;
    $("detail-institution").textContent = profile.institution;
    $("detail-program").textContent = profile.program;
    $("detail-semester").textContent = profile.semester;
    $("detail-location").textContent = profile.location;
    $("detail-member-since").textContent = profile.memberSince;
    $("detail-visibility").textContent = profile.is_public ? "Public" : "Private";

    // Portfolio overview counts (real data, not mockup numbers)
    $("summary-skills").textContent = String(mine.length);
    $("summary-evidence").textContent = `${evidence.length} ${evidence.length === 1 ? "item" : "items"}`;
    $("summary-career").textContent = target ? target.name : "Not set";
  }

  render();

  // ---- Edit Profile (top button) -> opens personal info modal ----
  $("btn-edit-profile").addEventListener("click", openEditPersonal);
  $("btn-edit-personal").addEventListener("click", openEditPersonal);
  $("btn-change-password").addEventListener("click", openChangePassword);
  $("btn-visibility").addEventListener("click", openVisibility);
  $("btn-change-photo").addEventListener("click", openChangePhoto);

  // ---- Modal: Edit Personal Information ----
  function openEditPersonal() {
    const fields = [
      ["Full name", "edit-name", "text", profile.name],
      ["Email address", "edit-email", "email", profile.email],
      ["Institution", "edit-institution", "text", profile.institution],
      ["Study program", "edit-program", "text", profile.program],
      ["Semester", "edit-semester", "text", profile.semester],
      ["Location", "edit-location", "text", profile.location],
    ];

    const inputs = {};
    const body = fields.map(([label, id, type, value]) => {
      const input = el("input", { type, id, value, required: "required" });
      inputs[id] = input;
      return el("div", { class: "field" }, [el("label", { for: id, text: label }), input]);
    });

    const errorEl = el("p", { class: "field-error", style: "display: none" });
    body.push(errorEl);

    UI.modal({
      title: "Edit Personal Information",
      body,
      submitLabel: "Save changes",
      onSubmit: async (close) => {
        errorEl.style.display = "none";

        const name = inputs["edit-name"].value.trim();
        const email = inputs["edit-email"].value.trim();
        const institution = inputs["edit-institution"].value.trim();
        const study_program = inputs["edit-program"].value.trim();
        const semester = parseInt(inputs["edit-semester"].value.trim(), 10) || 3;
        const location = inputs["edit-location"].value.trim();

        if (!name || !email) {
          errorEl.textContent = "Name and email are required.";
          errorEl.style.display = "block";
          return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          errorEl.textContent = "Enter a valid email address.";
          errorEl.style.display = "block";
          return false;
        }

        try {
          // Call backend API with all fields
          await API.updateProfile({
            name,
            email,
            institution,
            study_program,
            semester,
            location
          });

          // Update local state
          profile.name = name;
          profile.email = email;
          profile.institution = institution;
          profile.program = study_program;
          profile.semester = String(semester);
          profile.location = location;

          render();
          UI.toast("Profile updated successfully!");
          return true;
        } catch (err) {
          errorEl.textContent = err.message || "Failed to update profile";
          errorEl.style.display = "block";
          return false;
        }
      },
    });
  }

  // ---- Modal: Change Password ----
  function openChangePassword() {
    const cur = el("input", { type: "password", id: "pw-current", required: "required" });
    const next = el("input", { type: "password", id: "pw-new", required: "required" });
    const confirm = el("input", { type: "password", id: "pw-confirm", required: "required" });
    const errorEl = el("p", { class: "field-error", style: "display: none" });

    UI.modal({
      title: "Change Password",
      body: [
        el("div", { class: "field" }, [el("label", { for: "pw-current", text: "Current password" }), cur]),
        el("div", { class: "field" }, [el("label", { for: "pw-new", text: "New password" }), next]),
        el("div", { class: "field" }, [el("label", { for: "pw-confirm", text: "Confirm new password" }), confirm]),
        errorEl,
      ],
      submitLabel: "Update password",
      onSubmit: async (close) => {
        errorEl.style.display = "none";

        if (!cur.value || !next.value) {
          errorEl.textContent = "Fill in all password fields.";
          errorEl.style.display = "block";
          return false;
        }

        if (next.value.length < 6) {
          errorEl.textContent = "New password must be at least 6 characters.";
          errorEl.style.display = "block";
          return false;
        }

        if (next.value !== confirm.value) {
          errorEl.textContent = "Passwords do not match.";
          errorEl.style.display = "block";
          return false;
        }

        try {
          await API.changePassword(cur.value, next.value);
          UI.toast("Password updated successfully!");
          return true;
        } catch (err) {
          errorEl.textContent = err.message || "Failed to update password";
          errorEl.style.display = "block";
          return false;
        }
      },
    });
  }

  // ---- Modal: Portfolio Visibility ----
  function openVisibility() {
    const current = profile.is_public ? "Public" : "Private";
    const options = [
      ["Public", "Anyone can view your student portfolio."],
      ["Private", "Only you can view your portfolio."],
    ];
    const radios = {};
    const body = options.map(([value, desc]) => {
      const radio = el("input", {
        type: "radio",
        name: "visibility",
        value,
        id: `vis-${value.toLowerCase()}`,
        ...(value === current ? { checked: true } : {}),
      });
      radios[value] = radio;
      return el("label", {
        for: `vis-${value.toLowerCase()}`,
        style: "display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer",
      }, [
        radio,
        el("div", {}, [
          el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: value }),
          el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 2px", text: desc }),
        ]),
      ]);
    });

    const errorEl = el("p", { class: "field-error", style: "display: none" });
    body.push(errorEl);

    UI.modal({
      title: "Portfolio Visibility",
      body,
      submitLabel: "Save",
      onSubmit: async () => {
        errorEl.style.display = "none";

        const chosen = Object.entries(radios).find(([, r]) => r.checked);
        if (chosen) {
          const isPublic = chosen[0] === "Public";

          try {
            const result = await API.updateProfile({ is_public: isPublic });

            profile.is_public = isPublic;
            if (result.public_url) {
              profile.public_url = result.public_url;
            }

            render();
            UI.toast(`Visibility set to ${chosen[0]}`);

            if (isPublic && result.public_url) {
              UI.toast(`Public URL: ${result.public_url}`);
            }

            return true;
          } catch (err) {
            errorEl.textContent = err.message || "Failed to update visibility";
            errorEl.style.display = "block";
            return false;
          }
        }
        return true;
      },
    });
  }

  // Helper: crop to center square & compress to WebP via HTML5 Canvas
  function cropAndCompressImage(file, size = 256, quality = 0.85) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = reject;
      reader.onload = (e) => {
        const img = new Image();
        img.onerror = reject;
        img.onload = () => {
          const canvas = document.createElement("canvas");
          canvas.width = size;
          canvas.height = size;
          const ctx = canvas.getContext("2d");

          const naturalW = img.naturalWidth || img.width;
          const naturalH = img.naturalHeight || img.height;
          const minDim = Math.min(naturalW, naturalH);
          const sx = (naturalW - minDim) / 2;
          const sy = (naturalH - minDim) / 2;

          ctx.drawImage(img, sx, sy, minDim, minDim, 0, 0, size, size);
          const dataUrl = canvas.toDataURL("image/webp", quality);
          resolve(dataUrl);
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  // ---- Change Photo (file input -> crop canvas -> R2/backend) ----
  function openChangePhoto() {
    const input = el("input", { type: "file", id: "photo-file", accept: "image/png,image/jpeg,image/webp" });
    const preview = el("div", {
      style: "width: 80px; height: 80px; border-radius: 50%; background: var(--tint-gray); display: grid; place-items: center; overflow: hidden; color: var(--text-muted); font-size: 12px; border: 2px solid var(--border)",
      text: "Preview",
    });
    const errorEl = el("p", { class: "field-error", style: "display: none" });
    let preparedDataUrl = null;

    input.addEventListener("change", async () => {
      const file = input.files && input.files[0];
      errorEl.style.display = "none";
      if (!file) return;
      if (file.size > 8 * 1024 * 1024) {
        errorEl.textContent = "Image must be under 8 MB.";
        errorEl.style.display = "block";
        input.value = "";
        return;
      }
      try {
        preparedDataUrl = await cropAndCompressImage(file, 256, 0.85);
        UI.clear(preview);
        preview.append(el("img", { src: preparedDataUrl, alt: "Preview", style: "width: 100%; height: 100%; object-fit: cover" }));
      } catch (err) {
        errorEl.textContent = "Failed to process image.";
        errorEl.style.display = "block";
      }
    });

    UI.modal({
      title: "Change Photo",
      body: [
        el("div", { style: "display: flex; gap: 16px; align-items: center" }, [
          preview,
          el("div", {}, [
            el("p", { style: "font-size: 14px; font-weight: 600; color: var(--text-primary)", text: "Upload New Avatar" }),
            el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: "JPG, PNG, or WEBP. Automatically center-cropped & compressed." })
          ])
        ]),
        el("div", { class: "field" }, [el("label", { for: "photo-file", text: "Pilih file foto" }), input]),
        errorEl,
      ],
      submitLabel: "Save photo",
      onSubmit: async () => {
        if (!preparedDataUrl) {
          errorEl.textContent = "Pilih file gambar terlebih dahulu.";
          errorEl.style.display = "block";
          return false;
        }

        try {
          const res = await API.uploadAvatar(preparedDataUrl);
          profile.photo = res.avatar_url;
          renderAvatar();

          // Sync navbar avatar image
          const navAvatar = document.querySelector(".navbar-avatar");
          if (navAvatar) {
            navAvatar.innerHTML = `<img src="${res.avatar_url}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">`;
          }

          UI.toast("Foto profil berhasil diperbarui!");
          return true;
        } catch (err) {
          errorEl.textContent = err.message || "Gagal mengunggah foto profil.";
          errorEl.style.display = "block";
          return false;
        }
      },
    });
  }
})();
