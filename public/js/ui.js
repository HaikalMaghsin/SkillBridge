// ui.js - helper DOM bersama. Semua render memakai createElement/textContent

const UI = (() => {
  const NAV_ITEMS = [
    { key: "dashboard", label: "Dashboard", href: "dashboard.html" },
    { key: "skills", label: "My Skills", href: "skills.html" },
    { key: "evidence", label: "Evidence", href: "evidence.html" },
    { key: "career", label: "Career Target", href: "career.html" },
    { key: "gap", label: "Skill Gap", href: "gap.html" },
    { key: "profile", label: "Profile", href: "profile.html" },
  ];

  // el("span", { class: "tag" }, ["PHP"]) -> <span class="tag">PHP</span>
  function el(tag, props = {}, children = []) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
      if (value === undefined || value === null) continue;
      if (key === "class") node.className = value;
      else if (key === "text") node.textContent = value;
      else if (key === "onClick") node.addEventListener("click", value);
      else if (key === "innerHTML") node.innerHTML = value;
      else node.setAttribute(key, value);
    }
    for (const child of [].concat(children)) {
      if (child === null || child === undefined) continue;
      node.append(child instanceof Node ? child : document.createTextNode(String(child)));
    }
    return node;
  }

  function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
    return node;
  }

  function safeUrl(url) {
    try {
      const u = new URL(url, window.location.href);
      return u.protocol === "http:" || u.protocol === "https:" ? u.href : null;
    } catch {
      return null;
    }
  }

  function toast(message) {
    const box = el("div", { class: "toast", role: "status", text: message });
    document.body.append(box);
    setTimeout(() => box.remove(), 2600);
  }

  // modal({ title, body, submitLabel, onSubmit }) -> dialog dengan focus trap.
  // onSubmit(close, form) dipanggil saat submit; kembalikan false untuk menahan dialog terbuka.
  function modal({ title, body = [], submitLabel = "Save", cancelLabel = "Cancel", onSubmit }) {
    const previouslyFocused = document.activeElement;

    const form = el("form", { class: "modal-form", novalidate: "novalidate" }, body);
    const cancelBtn = el("button", { type: "button", class: "btn", text: cancelLabel, onClick: () => close() });
    const submitBtn = el("button", { type: "submit", class: "btn btn-accent", text: submitLabel });

    const dialog = el("div", { class: "modal", role: "dialog", "aria-modal": "true", "aria-label": title }, [
      el("h3", { class: "modal-title", text: title }),
      form,
      el("div", { class: "modal-actions" }, [cancelBtn, submitBtn]),
    ]);

    const overlay = el("div", { class: "modal-overlay" }, [dialog]);

    function focusables() {
      return [...dialog.querySelectorAll("button, input, select, textarea, a[href]")].filter(
        (node) => !node.disabled
      );
    }

    function onKeydown(event) {
      if (event.key === "Escape") {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== "Tab") return;
      const nodes = focusables();
      if (nodes.length === 0) return;
      const first = nodes[0];
      const last = nodes[nodes.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function close() {
      document.removeEventListener("keydown", onKeydown);
      overlay.remove();
      if (previouslyFocused instanceof HTMLElement) previouslyFocused.focus();
    }

    overlay.addEventListener("mousedown", (event) => {
      if (event.target === overlay) close();
    });

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      if (onSubmit && onSubmit(close, form) === false) return;
      close();
    });

    document.addEventListener("keydown", onKeydown);
    document.body.append(overlay);
    (focusables()[0] || dialog).focus();
    return close;
  }

  function renderNavbar() {
    const active = document.body.dataset.nav || "";
    const navLinks = el("div", { class: "navbar-nav" },
      NAV_ITEMS.map((item) =>
        el("a", {
          class: item.key === active ? "nav-item active" : "nav-item",
          href: item.href,
          text: item.label,
          ...(item.key === active ? { "aria-current": "page" } : {}),
        })
      )
    );

    const brand = el("div", { class: "navbar-brand" }, [
      el("div", { class: "navbar-logo", text: "S" }),
      el("div", { class: "navbar-brand-text" }, [
        el("div", { class: "navbar-brand-name", text: "SkillBridge" }),
        el("div", { class: "navbar-brand-subtitle", text: "STUDENT PORTFOLIO" }),
      ]),
    ]);

    const profile = el("div", { class: "navbar-profile" }, [
      el("div", { class: "navbar-profile-text" }, [
        el("div", { class: "navbar-profile-name", text: "Jhon Doe" }),
        el("div", { class: "navbar-profile-email", text: "jhondoe@gmail.com" }),
      ]),
      el("div", { class: "navbar-avatar", text: "JD" }),
    ]);

    const bar = el("header", { class: "navbar" }, [brand, navLinks, profile]);
    document.body.prepend(bar);
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (document.body.dataset.nav !== undefined) renderNavbar();
  });

  function formatDate(iso) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
  }

  return { el, clear, safeUrl, toast, formatDate, modal };
})();

window.UI = UI;