// ui.js - helper DOM bersama. Semua render memakai createElement/textContent

const UI = (() => {
  const getBaseUrl = () => {
    return (typeof window !== "undefined" && window.APP_BASE_URL !== undefined)
      ? (window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, ""))
      : "";
  };

  const NAV_ITEMS = [
    { key: "dashboard", label: "Dashboard", href: "mahasiswa/dashboard.php" },
    { key: "career", label: "Career Target", href: "mahasiswa/career.php" },
    { key: "skills", label: "My Skills", href: "mahasiswa/skills.php" },
    { key: "evidence", label: "Evidence", href: "mahasiswa/evidence.php" },
    { key: "gap", label: "Skill Gap", href: "mahasiswa/gap.php" },
    { key: "profile", label: "Profile", href: "mahasiswa/profile.php" },
  ];

  const SVG_NS = "http://www.w3.org/2000/svg";
  const SVG_TAGS = new Set(["svg", "path", "circle", "rect", "line", "polyline", "polygon", "g", "defs", "linearGradient", "stop"]);

  // el("span", { class: "tag" }, ["PHP"]) -> <span class="tag">PHP</span>
  function el(tag, props = {}, children = []) {
    const isSvg = SVG_TAGS.has(tag);
    const node = isSvg ? document.createElementNS(SVG_NS, tag) : document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
      if (value === undefined || value === null) continue;
      if (key === "class") {
        if (isSvg) node.setAttribute("class", value);
        else node.className = value;
      }
      else if (key === "text") node.textContent = value;
      else if (key === "onClick") node.addEventListener("click", value);
      // HANYA untuk markup statis tepercaya (mis. ikon SVG). Jangan isi dengan data pengguna/API.
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

  function toast(message, type = "info") {
    const box = el("div", { class: `toast toast-${type}`, role: "status", text: message });
    document.body.append(box);
    setTimeout(() => box.remove(), 2600);
  }

  // modal({ id, title, body, submitLabel, onSubmit, footer }) -> dialog dengan focus trap.
  function modal({ id = null, title, body = [], submitLabel = "Save", cancelLabel = "Cancel", onSubmit, footer = null }) {
    const previouslyFocused = document.activeElement;

    const cancelBtn = el("button", { type: "button", class: "btn btn-secondary", text: cancelLabel, onClick: () => close() });
    const submitBtn = submitLabel ? el("button", { type: "submit", class: "btn btn-accent", text: submitLabel }) : null;
    const closeIconBtn = el("button", {
      type: "button",
      class: "modal-close-btn",
      "aria-label": "Close modal",
      innerHTML: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
      onClick: () => close()
    });

    const header = el("div", { class: "modal-header" }, [
      el("h3", { class: "modal-title", text: title }),
      closeIconBtn
    ]);

    const modalBody = el("div", { class: "modal-body" }, body);
    
    let footerChildren = [];
    if (typeof footer === "function") {
      footerChildren = footer(close);
    } else if (Array.isArray(footer)) {
      footerChildren = footer;
    } else {
      footerChildren = [cancelBtn, submitBtn].filter(Boolean);
    }
    const modalFooter = el("div", { class: "modal-footer modal-actions" }, footerChildren);

    const form = el("form", { class: "modal-form-wrapper", style: "display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;", novalidate: "novalidate" }, [
      modalBody,
      modalFooter
    ]);

    const dialog = el("div", { id: id || undefined, class: "modal modal-card", role: "dialog", "aria-modal": "true", "aria-label": title }, [
      header,
      form
    ]);

    const overlay = el("div", { class: "modal-overlay is-open" }, [dialog]);

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
      if (!document.querySelector(".modal-overlay") && !document.querySelector(".modal-backdrop.is-open")) {
        document.documentElement.classList.remove("modal-open");
        document.body.classList.remove("modal-open");
      }
      if (previouslyFocused instanceof HTMLElement) previouslyFocused.focus();
    }

    overlay.addEventListener("mousedown", (event) => {
      if (event.target === overlay) close();
    });

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (!onSubmit) {
        close();
        return;
      }

      // Disable submit button saat processing
      submitBtn.disabled = true;
      const originalText = submitBtn.textContent;
      submitBtn.textContent = "Processing...";

      try {
        const result = await onSubmit(close, form);

        if (result === false) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          return;
        }
        close();
      } catch (err) {
        console.error('[UI.modal] Submit error:', err);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });

    document.addEventListener("keydown", onKeydown);
    document.documentElement.classList.add("modal-open");
    document.body.classList.add("modal-open");
    document.body.append(overlay);
    (focusables()[0] || dialog).focus();
    return close;
  }

  function renderNavbar() {
    // Cegah render ulang jika navbar sudah di-render oleh server (PHP)
    if (document.querySelector("header.navbar")) return;

    const active = document.body.dataset.nav || "";
    const base = getBaseUrl();

    const navLinks = el(
      "div",
      { class: "navbar-nav" },
      NAV_ITEMS.map((item) =>
        el("a", {
          class: item.key === active ? "nav-item active" : "nav-item",
          href: `${base}/${item.href}`,
          text: item.label,
          ...(item.key === active ? { "aria-current": "page" } : {}),
        })
      )
    );

    const brand = el("a", { class: "navbar-brand", href: `${base}/index.php`, "aria-label": "SkillBridge home" }, [
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
      el("button", {
        type: "button",
        class: "navbar-logout-btn",
        "aria-label": "Logout",
        title: "Logout",
        onClick: () => {
          // Bersihkan localStorage sebelum redirect
          localStorage.removeItem('sb.mySkills');
          localStorage.removeItem('sb.myEvidence');
          localStorage.removeItem('sb.targetCareer');
          localStorage.removeItem('sb.profile');
          localStorage.removeItem('sb.portfolioVisibility');
          window.location.href = `${base}/logout.php`;
        },
      }),
    ]);

    const toggle = el("button", {
      type: "button",
      class: "navbar-toggle",
      "aria-label": "Buka menu navigasi",
      "aria-expanded": "false",
      innerHTML: '<span class="navbar-toggle-bar"></span><span class="navbar-toggle-bar"></span><span class="navbar-toggle-bar"></span>',
    });

    const bar = el("header", { class: "navbar" }, [brand, toggle, navLinks, profile]);
    document.body.prepend(bar);
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (document.body.dataset.nav !== undefined) renderNavbar();

    // Event Delegation: Navbar Hamburger Toggle Mobile
    document.addEventListener("click", (e) => {
      const toggle = e.target.closest(".navbar-toggle");
      if (toggle) {
        const navbar = toggle.closest(".navbar");
        if (navbar) {
          const isOpen = navbar.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", String(isOpen));
        }
        return;
      }

      // Close mobile navbar when clicking outside
      const openNav = document.querySelector(".navbar.is-open");
      if (openNav && !openNav.contains(e.target)) {
        openNav.classList.remove("is-open");
        const toggleBtn = openNav.querySelector(".navbar-toggle");
        if (toggleBtn) toggleBtn.setAttribute("aria-expanded", "false");
      }

      // Sidebar Toggle HR & Admin Drawer Mobile
      const sidebarToggle = e.target.closest("[data-toggle-sidebar], .sidebar-toggle");
      if (sidebarToggle) {
        const sidebar = document.querySelector(".rec-sidebar, .admin-sidebar");
        const backdrop = document.querySelector(".sidebar-backdrop");
        if (sidebar) sidebar.classList.toggle("is-open");
        if (backdrop) backdrop.classList.toggle("is-open");
        return;
      }

      // Sidebar Backdrop / Close Drawer Mobile
      const sidebarClose = e.target.closest("[data-close-sidebar], .sidebar-backdrop, .sidebar-close-btn");
      if (sidebarClose) {
        const sidebar = document.querySelector(".rec-sidebar, .admin-sidebar");
        const backdrop = document.querySelector(".sidebar-backdrop");
        if (sidebar) sidebar.classList.remove("is-open");
        if (backdrop) backdrop.classList.remove("is-open");
        return;
      }

      const passToggle = e.target.closest("[data-password-toggle]");
      if (passToggle) {
        const targetId = passToggle.getAttribute("data-password-toggle");
        const input = targetId
          ? document.getElementById(targetId)
          : passToggle.closest(".password-input-wrap")?.querySelector("input");
        if (!input) return;

        const isHidden = input.type === "password";
        input.type = isHidden ? "text" : "password";
        passToggle.setAttribute("aria-pressed", isHidden ? "true" : "false");
        passToggle.setAttribute("aria-label", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
        return;
      }
    });

    // Close mobile menus on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const openNav = document.querySelector(".navbar.is-open");
        if (openNav) {
          openNav.classList.remove("is-open");
          const toggle = openNav.querySelector(".navbar-toggle");
          if (toggle) toggle.setAttribute("aria-expanded", "false");
        }
        const openSidebar = document.querySelector(".rec-sidebar.is-open, .admin-sidebar.is-open");
        if (openSidebar) {
          openSidebar.classList.remove("is-open");
          const backdrop = document.querySelector(".sidebar-backdrop");
          if (backdrop) backdrop.classList.remove("is-open");
        }
      }
    });
  });

  function formatDate(iso) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
  }

  function formatDateTime(raw) {
    if (!raw) return "—";
    const str = String(raw).trim();
    if (!/^\d{4}-\d{2}-\d{2}/.test(str)) return str;
    const normalized = str.replace(" ", "T").replace(/([+-]\d{2})$/, "$1:00");
    const d = new Date(normalized);
    if (Number.isNaN(d.getTime())) return str.slice(0, 16);
    const datePart = d.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
    const timePart = d.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", hour12: false });
    return `${datePart}, ${timePart}`;
  }

  return { el, clear, safeUrl, toast, formatDate, formatDateTime, modal };
})();

window.UI = UI;
