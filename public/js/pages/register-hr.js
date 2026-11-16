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
});
