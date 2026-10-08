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

  return { el, clear, safeUrl, toast, formatDate };
})();

window.UI = UI;