// login.js - Login form handler

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  const csrfInput = form.querySelector('input[name="csrf_token"]');

  // Set CSRF token from meta tag or API
  csrfInput.value = API.csrfToken();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email || !password) {
      UI.toast("Email and password required");
      return;
    }

    try {
      const res = await fetch("../api/auth/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfInput.value,
        },
        body: JSON.stringify({ email, password }),
      });

      if (!res.ok) {
        const error = await res.json().catch(() => ({ message: "Login failed" }));
        UI.toast(error.message || "Login failed");
        return;
      }

      const data = await res.json();
      if (data.success) {
        UI.toast("Welcome!");
        setTimeout(() => (location.href = "dashboard.html"), 600);
      } else {
        UI.toast(data.message || "Login failed");
      }
    } catch (err) {
      UI.toast("Network error. Try again.");
    }
  });
});
