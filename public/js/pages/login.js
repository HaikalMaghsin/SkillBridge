// login.js - validasi sisi klien saja.
// Pengiriman dilakukan oleh <form method="POST"> biasa ke login.php (PHP yang memverifikasi
// CSRF dan kredensial), sehingga tidak ada preventDefault/fetch di sini.

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email || !password) {
      e.preventDefault();
      UI.toast("Email and password required");
    }
  });

  const toggleBtn = form.querySelector("[data-password-toggle]");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", (e) => {
      e.preventDefault();
      const input = document.getElementById("password");
      if (!input) return;
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      const eye = toggleBtn.querySelector('.icon-eye');
      const eyeOff = toggleBtn.querySelector('.icon-eye-off');
      if (eye) eye.style.display = isHidden ? 'none' : 'block';
      if (eyeOff) eyeOff.style.display = isHidden ? 'block' : 'none';
      toggleBtn.setAttribute("aria-pressed", isHidden ? "true" : "false");
      toggleBtn.setAttribute("aria-label", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
      toggleBtn.setAttribute("title", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
    });
  }
});
