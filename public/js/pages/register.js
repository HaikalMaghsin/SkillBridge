// register.js - Register form handler

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("register-form");
  const csrfInput = form.querySelector('input[name="csrf_token"]');

  csrfInput.value = API.csrfToken();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!name || !email || !password) {
      UI.toast("All fields are required");
      return;
    }

    if (password.length < 8) {
      UI.toast("Password must be at least 8 characters");
      return;
    }

    try {
      const res = await fetch("../api/auth/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfInput.value,
        },
        body: JSON.stringify({ name, email, password }),
      });

      if (!res.ok) {
        const error = await res.json().catch(() => ({ message: "Registration failed" }));
        UI.toast(error.message || "Registration failed");
        return;
      }

      const data = await res.json();
      if (data.success) {
        UI.toast("Account created!");
        setTimeout(() => (location.href = "dashboard.html"), 600);
      } else {
        UI.toast(data.message || "Registration failed");
      }
    } catch (err) {
      UI.toast("Network error. Try again.");
    }
  });
});