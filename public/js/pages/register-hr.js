// public/js/pages/register-hr.js
// Validasi sisi klien untuk form registrasi perusahaan / HR partner

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("register-hr-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const compName = form.company_name?.value.trim();
    const industry = form.industry?.value.trim();
    const name = form.name?.value.trim();
    const email = form.email?.value.trim();
    const password = form.password?.value;

    if (!compName || !industry || !name || !email || !password) {
      e.preventDefault();
      if (typeof UI !== "undefined" && UI.toast) {
        UI.toast("Semua kolom bertanda bintang (*) wajib diisi.");
      } else {
        alert("Semua kolom bertanda bintang (*) wajib diisi.");
      }
      return;
    }

    if (password.length < 8) {
      e.preventDefault();
      if (typeof UI !== "undefined" && UI.toast) {
        UI.toast("Kata sandi minimal harus 8 karakter.");
      } else {
        alert("Kata sandi minimal harus 8 karakter.");
      }
    }
  });

  const toggleBtn = form.querySelector("[data-password-toggle], .password-toggle-btn");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (typeof window.togglePasswordVisibility === "function") {
        window.togglePasswordVisibility("password", toggleBtn);
      } else {
        const input = document.getElementById("password") || form.querySelector("input[name='password']");
        if (!input) return;
        const isHidden = input.type === "password";
        input.type = isHidden ? "text" : "password";
        const eye = toggleBtn.querySelector(".icon-eye");
        const eyeOff = toggleBtn.querySelector(".icon-eye-off");
        if (eye) eye.style.display = isHidden ? "none" : "block";
        if (eyeOff) eyeOff.style.display = isHidden ? "block" : "none";
        toggleBtn.setAttribute("aria-pressed", isHidden ? "true" : "false");
        toggleBtn.setAttribute("aria-label", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
        toggleBtn.setAttribute("title", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
      }
    });
  }
});
