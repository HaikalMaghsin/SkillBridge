// register.js - validasi sisi klien saja.
// Pengiriman dilakukan oleh <form method="POST"> biasa ke register.php (PHP yang memverifikasi
// CSRF, memvalidasi ulang, dan menyimpan ke database).

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("register-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!name || !email || !password) {
      e.preventDefault();
      UI.toast("All fields are required");
      return;
    }

    if (password.length < 8) {
      e.preventDefault();
      UI.toast("Password must be at least 8 characters");
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
