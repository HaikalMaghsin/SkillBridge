// admin.js - render dashboard admin (Admin Console)
// Terhubung ke PostgreSQL untuk statistik real-time

(async () => {
  const { el } = UI;

  // ---- Stat cards dari PostgreSQL ----
  try {
    const response = await fetch(`${window.APP_BASE_URL}/api/admin/stats.php`, {
      headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
    });

    if (!response.ok) throw new Error('Failed to load stats');

    const stats = await response.json();
    const grid = document.getElementById("admin-stats-grid");

    if (grid) {
      UI.clear(grid);

      const totalStudents = Number(stats.total_students ?? 0);
      const totalHR = Number(stats.total_hr ?? 0);
      const totalUsers = totalStudents + totalHR;

      const cards = [
        {
          label: "Total Pengguna",
          value: totalUsers,
          context: `${totalStudents} mahasiswa · ${totalHR} HR`
        },
        {
          label: "Administrator",
          value: Number(stats.total_admins ?? 0),
          context: "Platform owner & admin"
        },
        {
          label: "Keterampilan",
          value: Number(stats.total_skills ?? 0),
          context: "Master skill database"
        },
        {
          label: "Target Karier",
          value: Number(stats.total_careers ?? 0),
          context: "Kategori karier master"
        },
        {
          label: "Relasi Mapping",
          value: Number(stats.total_mappings ?? 0),
          context: "Karier–skill requirements"
        },
        {
          label: "Evidence",
          value: Number(stats.total_evidence ?? 0),
          context: "Bukti kompetensi mahasiswa"
        },
        {
          label: "Lowongan Aktif",
          value: Number(stats.active_vacancies ?? 0),
          context: "Job vacancies dari perusahaan"
        },
      ];

      for (const c of cards) {
        grid.append(
          el("div", { class: "admin-stat-card" }, [
            el("div", { class: "admin-stat-card-main" }, [
              el("span", { class: "admin-stat-label", text: c.label }),
              el("span", { class: "admin-stat-value", text: c.value.toLocaleString("id-ID") }),
            ]),
            el("p", { class: "admin-stat-context", text: c.context }),
          ])
        );
      }
    }
  } catch (err) {
    console.error('Error loading admin stats:', err);
    UI.toast('Gagal memuat statistik', 'error');
  }

  // ---- Recent Activity (untuk fase mendatang - saat ini static) ----
  const systemRows = [
    { time: "Baru saja", text: "Sistem siap digunakan", cat: "Sistem" },
    { time: "—", text: "Log aktivitas akan muncul di sini", cat: "Info" },
  ];

  const sysBody = document.getElementById("system-activity-body");
  if (sysBody) {
    UI.clear(sysBody);
    for (const r of systemRows) {
      sysBody.append(
        el("div", { class: "admin-row" }, [
          el("span", { class: "admin-row-time", text: r.time }),
          el("span", { class: "admin-row-text col-activity", text: r.text }),
          el("span", { class: "admin-row-cat" }, [
            el("span", { class: "admin-badge-pill", text: r.cat }),
          ]),
        ])
      );
    }
  }

  // ---- Admin activity (untuk fase mendatang - saat ini static) ----
  const adminRows = [
    {
      actor: "Administrator",
      detail: "Dashboard Admin terhubung ke PostgreSQL",
      time: "Baru saja",
    },
  ];

  const admBody = document.getElementById("admin-activity-body");
  if (admBody) {
    UI.clear(admBody);
    for (const r of adminRows) {
      admBody.append(
        el("div", { class: "admin-admin-row" }, [
          el("div", { class: "admin-admin-main" }, [
            el("p", { class: "admin-admin-actor", text: r.actor }),
            el("p", { class: "admin-admin-detail", text: r.detail }),
          ]),
          el("span", { class: "admin-admin-time", text: r.time }),
        ])
      );
    }
  }
})();
