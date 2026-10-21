// login.js - Login form handler with dynamic role redirection

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  if (!form) return;

  const csrfInput = form.querySelector('input[name="csrf_token"]');
  if (csrfInput && typeof API !== "undefined") {
    csrfInput.value = API.csrfToken();
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email || !password) {
      UI.toast("Email and password required");
      return;
    }

    const baseUrl = (typeof window !== "undefined" && window.APP_BASE_URL !== undefined)
      ? (window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, ""))
      : "";

    const loginEndpoint = `${baseUrl}/login.php`;

    try {
      const res = await fetch(loginEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfInput ? csrfInput.value : "",
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
        UI.toast("Welcome back!");

        let redirectUrl = `${baseUrl}/mahasiswa/dashboard.php`;
        if (data.role === "admin") {
          redirectUrl = `${baseUrl}/admin/index.php`;
        } else if (data.role === "hr") {
          redirectUrl = `${baseUrl}/hr/index.php`;
        }

        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 500);
      } else {
        UI.toast(data.message || "Email atau kata sandi tidak valid.");
      }
    } catch (err) {
      // Fallback: submit standard form jika fetch gagal
      form.submit();
    }
  });
});
