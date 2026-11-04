// public/js/pages/evidence.js
// Bukti Kompetensi (Evidence) Responsif Data & Scan CV ATS Otomatis · SkillBridge
// Full implementation - Vanilla JS ES6+ - Strict Anti-XSS

(async () => {
  const { el } = UI;

  // Elemen DOM Halaman
  const countLabel = document.getElementById("count-label");
  const gridEl = document.getElementById("evidence-grid");
  const modal = document.getElementById("modal-add-evidence");
  const form = document.getElementById("form-add-evidence");
  const modalTitle = document.getElementById("modal-evidence-title");
  const submitBtn = document.getElementById("btn-submit-evidence");
  const btnCloseModal = document.getElementById("btn-close-modal");
  const btnCancel = document.getElementById("btn-cancel");
  const btnAddEvidence = document.getElementById("btn-add-evidence");
  const demoBanner = document.getElementById("demo-banner");
  const typeGrid = document.getElementById("type-grid");
  const typeInput = document.getElementById("input-type");
  const titleInput = document.getElementById("input-title");
  const summaryInput = document.getElementById("input-summary");
  const levelSelect = document.getElementById("input-level");
  const attachmentSection = document.getElementById("attachment-dynamic-section");
  const hiddenAttachment = document.getElementById("input-attachment");
  const skillsContainer = document.getElementById("skills-container");
  const skillsInput = document.getElementById("input-skills");
  const skillSearchInput = document.getElementById("ev-skill-search");
  const skillCounter = document.getElementById("skill-selection-counter");
  const btnApplySummaryCv = document.getElementById("btn-apply-summary-cv");

  // State
  let evidenceList = [];
  let catalog = [];
  let mySkills = [];
  let targetCareerId = null;
  let careerRequiredSkillIds = new Set();
  let selectedSkillIds = [];
  let currentType = "cv";
  let editEvidenceId = null;
  let currentUploadedFile = null;
  let detectedCvSummary = "";

  // Kamus Alias & Sinonim Cerdas untuk Pemindaian CV ATS
  const SKILL_SYNONYMS = {
    "JavaScript": [/\bjs\b/i, /\bjavascript\b/i, /\bes6\b/i, /\becmascript\b/i, /\bvanilla\s*js\b/i],
    "TypeScript": [/\bts\b/i, /\btypescript\b/i],
    "Node.js": [/\bnode(\.?js)?\b/i, /\bnodejs\b/i],
    "Express.js": [/\bexpress(\.?js)?\b/i, /\bexpressjs\b/i],
    "SQL": [/\bsql\b/i, /\bmysql\b/i, /\bmariadb\b/i, /\bpostgres(ql)?\b/i, /\bsqlite\b/i, /\brdbms\b/i],
    "PostgreSQL": [/\bpostgres(ql)?\b/i, /\bpsql\b/i],
    "REST APIs": [/\brest(ful)?(\s*apis?)?\b/i, /\bweb apis?\b/i, /\bapi design\b/i, /\brest api\b/i],
    "HTML": [/\bhtml5?\b/i, /\bsemantic html\b/i],
    "CSS": [/\bcss3?\b/i, /\bsass\b/i, /\bscss\b/i, /\btailwind(css)?\b/i, /\bbootstrap\b/i],
    "Responsive Design": [/\bresponsive( design)?\b/i, /\bmobile-first\b/i, /\bmedia queries\b/i],
    "Git": [/\bgit\b/i, /\bgithub\b/i, /\bgitlab\b/i, /\bversion control\b/i],
    "Figma": [/\bfigma\b/i],
    "Excel": [/\bexcel\b/i, /\bspreadsheets?\b/i, /\bgoogle sheets\b/i],
    "Data Visualisation": [/\bdata visuali[sz]ation\b/i, /\btableau\b/i, /\bpower\s*bi\b/i, /\bchart(\.?js)?\b/i, /\bd3(\.?js)?\b/i, /\bmatplotlib\b/i, /\bseaborn\b/i],
    "Statistics": [/\bstatisti[ck]s?\b/i, /\bstatistical\b/i, /\bhypothesis testing\b/i],
    "User Research": [/\buser research\b/i, /\bux research\b/i, /\buser interviews?\b/i, /\bheuristic evaluation\b/i],
    "Wireframing": [/\bwirefram(ing|es?)\b/i, /\bmockups?\b/i, /\bbalsamiq\b/i],
    "Prototyping": [/\bprototyp(ing|es?)\b/i, /\binteractive prototype\b/i],
    "Usability Testing": [/\busability testing\b/i, /\buser testing\b/i, /\ba\/b test(ing)?\b/i],
    "PHP": [/\bphp\b/i, /\blaravel\b/i, /\bsymfony\b/i, /\bcodeigniter\b/i],
    "Python": [/\bpython\b/i, /\bdjango\b/i, /\bflask\b/i, /\bfastapi\b/i, /\bpandas\b/i, /\bnumpy\b/i],
    "User Interface": [/\bui\b/i, /\buser interface\b/i, /\bui design\b/i],
    "User Experience": [/\bux\b/i, /\buser experience\b/i, /\bux design\b/i],
    "React": [/\breact(\.?js)?\b/i, /\breactjs\b/i],
    "Docker": [/\bdocker\b/i, /\bcontainers?\b/i, /\bcontainerization\b/i],
    "Testing": [/\bunit testing\b/i, /\bjest\b/i, /\bphpunit\b/i, /\bcypress\b/i],
    "Data analysis": [/\bdata analysis\b/i, /\bdata analytics\b/i]
  };

  // Helper Escape Regex
  function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  // Load Data Awal
  try {
    const [fetchedEvidence, fetchedCatalog, fetchedMySkills, fetchedTargetId, fetchedGap] = await Promise.all([
      API.getEvidence().catch(() => []),
      API.getSkillCatalog().catch(() => []),
      API.getMySkills().catch(() => []),
      API.getTargetCareer().catch(() => null),
      API.getSkillGap().catch(() => ({ required: [], have: [], explore: [] }))
    ]);

    evidenceList = fetchedEvidence || [];
    catalog = fetchedCatalog || [];
    mySkills = fetchedMySkills || [];
    targetCareerId = fetchedTargetId;

    // Kumpulkan skill wajib untuk target karier mahasiswa
    if (fetchedGap && Array.isArray(fetchedGap.have) && Array.isArray(fetchedGap.explore)) {
      fetchedGap.have.forEach(s => careerRequiredSkillIds.add(s.id || s.skill_id));
      fetchedGap.explore.forEach(s => careerRequiredSkillIds.add(s.id || s.skill_id));
    }
  } catch (err) {
    console.error("Gagal memuat data awal:", err);
    UI.toast("Gagal memuat data awal, silakan muat ulang halaman.", "error");
  }

  // Render Grid Utama
  renderEvidenceGrid();
  renderSkillChips();
  renderAttachmentSection(currentType);

  // =========================================================================
  // 1. RENDER EVIDENCE CARDS & GRID
  // =========================================================================
  function renderEvidenceGrid() {
    countLabel.textContent = `${evidenceList.length} recent ${evidenceList.length === 1 ? "item" : "items"}`;
    gridEl.replaceChildren();

    if (evidenceList.length === 0) {
      gridEl.style.gridTemplateColumns = "1fr";
      const emptyCard = document.createElement("div");
      emptyCard.style.cssText = "text-align: center; padding: 48px 20px; color: var(--text-muted); background: var(--surface); border-radius: 16px; border: 1px dashed var(--border);";

      const titleP = document.createElement("p");
      titleP.style.cssText = "font-size: 18px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);";
      titleP.textContent = "Belum ada bukti kompetensi";

      const descP = document.createElement("p");
      descP.style.cssText = "font-size: 15px; color: var(--text-muted);";
      descP.textContent = "Gunakan tombol Add Evidence untuk mulai mendokumentasikan karya, CV, atau sertifikasimu.";

      emptyCard.append(titleP, descP);
      gridEl.appendChild(emptyCard);
      return;
    }

    gridEl.style.gridTemplateColumns = "repeat(2, 1fr)";
    // Render urutan terbaru di atas
    const sorted = evidenceList.slice().reverse();
    sorted.forEach((ev) => {
      gridEl.appendChild(createEvidenceCardElement(ev));
    });
  }

  function createEvidenceCardElement(ev) {
    const typeMeta = {
      cv: { label: "CV ATS", color: "#6366F1", bg: "#F3F0FF" },
      github: { label: "GitHub", color: "#334155", bg: "#F1F5F9" },
      portfolio: { label: "Portfolio", color: "#0891B2", bg: "#ECFEFF" },
      project: { label: "Project", color: "var(--accent, #FF007A)", bg: "var(--tint-pink, #FFF0F6)" },
      certificate: { label: "Certificate", color: "#7C3AED", bg: "#F5F3FF" },
      assessment: { label: "Assessment", color: "#059669", bg: "#ECFDF5" },
      other: { label: "Other", color: "var(--text-muted)", bg: "var(--tint-gray, #F1F5F9)" }
    };
    const currentMeta = typeMeta[ev.source_type] || typeMeta.other;

    const card = document.createElement("article");
    card.className = "evidence-card";

    // Top: Badge & Source Type Tag
    const topRow = document.createElement("div");
    topRow.style.cssText = "display: flex; justify-content: space-between; align-items: center;";

    const badgeSpan = document.createElement("span");
    badgeSpan.style.cssText = `display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: ${currentMeta.bg}; color: ${currentMeta.color};`;
    badgeSpan.textContent = currentMeta.label;

    const levelBadge = document.createElement("span");
    const levelVal = ev.metadata?.level || "intermediate";
    levelBadge.style.cssText = "font-size: 11px; font-weight: 600; color: #64748B; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 2px 8px; border-radius: 12px; text-transform: capitalize;";
    levelBadge.textContent = levelVal;

    topRow.append(badgeSpan, levelBadge);

    // Title & Description
    const contentBlock = document.createElement("div");
    contentBlock.style.cssText = "min-height: 70px; display: flex; flex-direction: column; gap: 6px;";

    const titleEl = document.createElement("h3");
    titleEl.style.cssText = "font-size: 17px; font-weight: 700; line-height: 1.35; color: var(--text-primary); margin: 0;";
    titleEl.textContent = ev.title;

    const descEl = document.createElement("p");
    descEl.style.cssText = "font-size: 13.5px; line-height: 1.5; color: var(--text-muted); margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;";
    descEl.textContent = ev.description || ev.metadata?.description || "Tidak ada ringkasan deskripsi.";

    contentBlock.append(titleEl, descEl);

    // Skills Tag List
    const skillsListEl = document.createElement("div");
    skillsListEl.className = "evidence-card-skills";

    // Resolusi nama skill dari skill_ids atau metadata.skills
    const linkedSkills = [];
    if (Array.isArray(ev.skill_ids) && ev.skill_ids.length > 0) {
      ev.skill_ids.forEach((id) => {
        const found = catalog.find((c) => c.id === id);
        if (found) linkedSkills.push(found.name);
      });
    }
    if (linkedSkills.length === 0 && Array.isArray(ev.metadata?.skills)) {
      ev.metadata.skills.forEach((s) => {
        if (typeof s === "string") linkedSkills.push(s);
        else if (s && s.name) linkedSkills.push(s.name);
      });
    }

    if (linkedSkills.length > 0) {
      linkedSkills.slice(0, 6).forEach((skillName) => {
        const skillTag = document.createElement("span");
        skillTag.className = "evidence-card-skill-tag";
        skillTag.textContent = skillName;
        skillsListEl.appendChild(skillTag);
      });
    }

    // Footer: Date / File info & Actions
    const footerEl = document.createElement("div");
    footerEl.className = "evidence-card-footer";

    const leftMeta = document.createElement("div");
    leftMeta.style.cssText = "display: flex; flex-direction: column; gap: 2px;";

    const dateP = document.createElement("p");
    dateP.style.cssText = "font-size: 12px; color: var(--text-light, #94A3B8); font-weight: 500; margin: 0;";
    const evDate = ev.created_at || ev.metadata?.date;
    dateP.textContent = evDate ? `Diperbarui ${UI.formatDate(evDate)}` : "Tersimpan";

    leftMeta.appendChild(dateP);

    // Link Lampiran jika ada
    if (ev.file_url) {
      const isExternalUrl = /^https?:\/\//i.test(ev.file_url);
      if (isExternalUrl) {
        const linkA = document.createElement("a");
        linkA.href = UI.safeUrl(ev.file_url) || "#";
        linkA.target = "_blank";
        linkA.rel = "noopener noreferrer";
        linkA.style.cssText = "font-size: 12.5px; font-weight: 600; color: var(--primary, #6366F1); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;";
        linkA.textContent = "Buka Tautan ↗";
        leftMeta.appendChild(linkA);
      } else {
        const fileSpan = document.createElement("span");
        fileSpan.style.cssText = "font-size: 12px; color: #475569; font-weight: 500; margin-top: 2px;";
        fileSpan.textContent = `📎 ${ev.file_url}`;
        leftMeta.appendChild(fileSpan);
      }
    }

    // Action Buttons: Edit & Hapus
    const actionGroup = document.createElement("div");
    actionGroup.className = "evidence-card-actions";

    // Tombol Edit
    const btnEdit = document.createElement("button");
    btnEdit.type = "button";
    btnEdit.className = "btn btn-secondary btn-sm";
    btnEdit.style.cssText = "padding: 6px 12px; font-size: 12.5px;";
    btnEdit.textContent = "Edit";
    btnEdit.onclick = () => openEditModal(ev);

    // Tombol Hapus
    const btnDelete = document.createElement("button");
    btnDelete.type = "button";
    btnDelete.className = "btn btn-ghost btn-sm";
    btnDelete.style.cssText = "padding: 6px 12px; font-size: 12.5px; color: #EF4444;";
    btnDelete.textContent = "Hapus";
    btnDelete.onclick = async () => {
      const confirmDelete = window.confirm(`Apakah kamu yakin ingin menghapus bukti "${ev.title}"?`);
      if (!confirmDelete) return;

      try {
        await API.removeEvidence(ev.id);
        UI.toast("Bukti kompetensi berhasil dihapus");
        evidenceList = evidenceList.filter((item) => item.id !== ev.id);
        renderEvidenceGrid();
      } catch (err) {
        console.error("Gagal menghapus bukti:", err);
        UI.toast(`Gagal menghapus: ${err.message}`, "error");
      }
    };

    actionGroup.append(btnEdit, btnDelete);
    footerEl.append(leftMeta, actionGroup);

    card.append(topRow, contentBlock, skillsListEl, footerEl);
    return card;
  }

  // =========================================================================
  // 2. FIELD INPUT DINAMIS RESPONSIF TERHADAP JENIS BUKTI
  // =========================================================================
  function renderAttachmentSection(type, existingValue = null) {
    attachmentSection.replaceChildren();

    if (type === "cv") {
      // Area Upload File PDF CV + Feedback ATS
      const container = document.createElement("div");

      const label = document.createElement("label");
      label.style.cssText = "display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px;";
      label.textContent = "Unggah Dokumen CV (PDF ATS Scanner)";

      // Upload Dropzone
      const dropzone = document.createElement("div");
      dropzone.className = "upload-dropzone";
      dropzone.tabIndex = 0;

      const fileInput = document.createElement("input");
      fileInput.type = "file";
      fileInput.id = "ev-file-input";
      fileInput.accept = ".pdf,application/pdf";
      fileInput.style.display = "none";

      const iconDiv = document.createElement("div");
      iconDiv.className = "upload-dropzone-icon";
      iconDiv.textContent = "📄";

      const textPrimary = document.createElement("span");
      textPrimary.style.cssText = "font-size: 14px; font-weight: 600; color: #1E293B;";
      textPrimary.textContent = existingValue ? `Berkas terpilih: ${existingValue}` : "Pilih atau letakkan berkas PDF CV kamu di sini";

      const textSub = document.createElement("span");
      textSub.style.cssText = "font-size: 12px; color: #64748B;";
      textSub.textContent = "Format .pdf · Sistem akan otomatis mengekstrak & mencocokkan keahlian via ATS Scanner";

      dropzone.append(iconDiv, textPrimary, textSub, fileInput);

      dropzone.onclick = () => fileInput.click();
      dropzone.onkeydown = (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          fileInput.click();
        }
      };

      // Drag & Drop
      dropzone.ondragover = (e) => {
        e.preventDefault();
        dropzone.classList.add("dragover");
      };
      dropzone.ondragleave = () => dropzone.classList.remove("dragover");
      dropzone.ondrop = async (e) => {
        e.preventDefault();
        dropzone.classList.remove("dragover");
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
          const file = e.dataTransfer.files[0];
          await handlePdfSelection(file, textPrimary);
        }
      };

      fileInput.onchange = async () => {
        if (fileInput.files && fileInput.files[0]) {
          const file = fileInput.files[0];
          await handlePdfSelection(file, textPrimary);
        }
      };

      // Kotak Feedback ATS
      const feedbackBox = document.createElement("div");
      feedbackBox.id = "ats-scan-feedback";
      feedbackBox.className = "ats-feedback-box ats-feedback--idle";
      feedbackBox.textContent = "Belum ada pemindaian dokumen. Unggah PDF untuk deteksi otomatis.";

      container.append(label, dropzone, feedbackBox);
      attachmentSection.appendChild(container);

    } else if (["github", "portfolio", "project"].includes(type)) {
      // Input URL dengan validasi
      const container = document.createElement("div");

      const label = document.createElement("label");
      label.htmlFor = "ev-url-input";
      label.style.cssText = "display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 6px;";

      let placeholder = "https://...";
      if (type === "github") {
        label.textContent = "Tautan Repositori GitHub";
        placeholder = "https://github.com/username/repository";
      } else if (type === "portfolio") {
        label.textContent = "Tautan Portofolio / Situs Live";
        placeholder = "https://portofolio-saya.vercel.app";
      } else {
        label.textContent = "Tautan Demo Proyek";
        placeholder = "https://project-demo.com atau link dokumentasi";
      }

      const input = document.createElement("input");
      input.type = "url";
      input.id = "ev-url-input";
      input.className = "form-input";
      input.placeholder = placeholder;
      input.style.cssText = "width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 14px; background: white;";
      if (existingValue) input.value = existingValue;

      input.oninput = () => {
        hiddenAttachment.value = input.value.trim();
      };

      const hint = document.createElement("p");
      hint.style.cssText = "margin-top: 6px; font-size: 12px; color: #64748B;";
      hint.textContent = "Gunakan tautan lengkap diawali http:// atau https://";

      container.append(label, input, hint);
      attachmentSection.appendChild(container);

    } else if (["certificate", "assessment"].includes(type)) {
      // Input Gabungan: Upload Berkas ATAU Tautan Kredensial
      const container = document.createElement("div");

      const label = document.createElement("label");
      label.style.cssText = "display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px;";
      label.textContent = type === "certificate" ? "Berkas Sertifikat atau Tautan Kredensial" : "Berkas Hasil Asesmen atau Tautan Nilai";

      const fileGroup = document.createElement("div");
      fileGroup.style.cssText = "display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;";

      const fileInput = document.createElement("input");
      fileInput.type = "file";
      fileInput.id = "ev-file-input";
      fileInput.accept = ".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg";
      fileInput.style.cssText = "font-size: 13px; color: #334155;";

      fileInput.onchange = () => {
        if (fileInput.files && fileInput.files[0]) {
          hiddenAttachment.value = fileInput.files[0].name;
          currentUploadedFile = fileInput.files[0];
          urlInput.value = ""; // Bersihkan url jika memilih file
        }
      };

      fileGroup.appendChild(fileInput);

      const divider = document.createElement("div");
      divider.style.cssText = "display: flex; align-items: center; gap: 8px; margin: 6px 0; color: #94A3B8; font-size: 11px; font-weight: 700; text-transform: uppercase;";
      const dLine1 = document.createElement("span");
      dLine1.style.cssText = "flex: 1; height: 1px; background: #E2E8F0;";
      const dText = document.createElement("span");
      dText.textContent = "Atau tautan online";
      const dLine2 = document.createElement("span");
      dLine2.style.cssText = "flex: 1; height: 1px; background: #E2E8F0;";
      divider.append(dLine1, dText, dLine2);

      const urlInput = document.createElement("input");
      urlInput.type = "url";
      urlInput.id = "ev-url-input";
      urlInput.placeholder = type === "certificate" ? "https://coursera.org/verify/... atau ID kredensial" : "https://assessment-provider.com/result/...";
      urlInput.style.cssText = "width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 14px; background: white;";

      if (existingValue && /^https?:\/\//i.test(existingValue)) {
        urlInput.value = existingValue;
      }

      urlInput.oninput = () => {
        if (urlInput.value.trim()) {
          hiddenAttachment.value = urlInput.value.trim();
          fileInput.value = ""; // Bersihkan file jika mengisi URL
          currentUploadedFile = null;
        }
      };

      container.append(label, fileGroup, divider, urlInput);
      attachmentSection.appendChild(container);
    }
  }

  // Handle Pemilihan File PDF & Trigger ATS Scanner
  async function handlePdfSelection(file, labelElement) {
    if (!file || !file.name.toLowerCase().endsWith(".pdf")) {
      UI.toast("Silakan pilih berkas format .pdf", "error");
      return;
    }

    currentUploadedFile = file;
    hiddenAttachment.value = file.name;
    if (labelElement) labelElement.textContent = `Berkas terpilih: ${file.name}`;

    // Otomatis isi judul jika masih kosong
    const cleanFileName = file.name.replace(/\.[^/.]+$/, "").replace(/[_-]+/g, " ");
    if (!titleInput.value || titleInput.value.startsWith("CV ATS")) {
      titleInput.value = `CV ATS — ${cleanFileName}`;
    }

    // Jalankan ekstraksi dan pencocokan keahlian
    await extractAndMatchSkillsFromPdf(file);
  }

  // =========================================================================
  // 3. INTEGRASI PENUH SCAN CV ATS DENGAN MOZILLA PDF.JS
  // =========================================================================
  async function extractAndMatchSkillsFromPdf(file) {
    const feedbackBox = document.getElementById("ats-scan-feedback");
    if (feedbackBox) {
      feedbackBox.className = "ats-feedback-box ats-feedback--loading";
      feedbackBox.textContent = "⏳ Membaca dan memindai teks dokumen CV ATS dengan Mozilla PDF.js...";
    }

    try {
      if (typeof pdfjsLib === "undefined") {
        throw new Error("Library Mozilla PDF.js belum selesai dimuat.");
      }

      const arrayBuffer = await file.arrayBuffer();
      const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
      const pdf = await loadingTask.promise;
      let fullText = "";

      for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const textContent = await page.getTextContent();
        const pageStr = textContent.items.map((item) => item.str).join(" ");
        fullText += " " + pageStr;
      }

      // Normalisasi teks
      const cleanFullText = fullText.replace(/\s+/g, " ").trim();
      const lowerFullText = cleanFullText.toLowerCase();

      // Pencocokan keahlian cerdas
      const detectedSkillObjects = [];

      catalog.forEach((skill) => {
        let isMatch = false;

        // Cek sinonim/alias jika ada
        const synonyms = SKILL_SYNONYMS[skill.name];
        if (synonyms && Array.isArray(synonyms)) {
          for (const regex of synonyms) {
            if (regex.test(lowerFullText)) {
              isMatch = true;
              break;
            }
          }
        }

        // Cek boundary nama skill asli
        if (!isMatch) {
          const defaultRegex = new RegExp(`\\b${escapeRegExp(skill.name)}\\b`, "i");
          if (defaultRegex.test(lowerFullText)) {
            isMatch = true;
          }
        }

        if (isMatch) {
          detectedSkillObjects.push(skill);
        }
      });

      // Deteksi Ringkasan Profil dari Teks CV
      detectedCvSummary = extractSummaryFromCvText(cleanFullText);
      if (detectedCvSummary) {
        if (!summaryInput.value.trim()) {
          summaryInput.value = detectedCvSummary;
        }
        btnApplySummaryCv.style.display = "inline-block";
        btnApplySummaryCv.onclick = () => {
          summaryInput.value = detectedCvSummary;
          UI.toast("Ringkasan CV diterapkan ke form");
        };
      }

      // Seleksi Chip Keahlian
      let skillsToSelect = [];
      if (detectedSkillObjects.length > 6) {
        // Urutkan berdasarkan relevansi dengan Target Career mahasiswa
        const prioritized = detectedSkillObjects.slice().sort((a, b) => {
          const aRequired = careerRequiredSkillIds.has(a.id) ? 1 : 0;
          const bRequired = careerRequiredSkillIds.has(b.id) ? 1 : 0;
          if (bRequired !== aRequired) return bRequired - aRequired;

          const aOwned = mySkills.some((ms) => ms.skill_id === a.id) ? 1 : 0;
          const bOwned = mySkills.some((ms) => ms.skill_id === b.id) ? 1 : 0;
          return bOwned - aOwned;
        });

        skillsToSelect = prioritized.slice(0, 6);
      } else {
        skillsToSelect = detectedSkillObjects.slice();
      }

      // Terapkan ke state selectedSkillIds
      selectedSkillIds = skillsToSelect.map((s) => s.id);
      skillsInput.value = JSON.stringify(selectedSkillIds);
      updateSelectedChipsUi();

      // Tampilkan Umpan Balik Visual Sukses
      if (feedbackBox) {
        feedbackBox.className = "ats-feedback-box ats-feedback--success";
        feedbackBox.replaceChildren();

        const successText = document.createElement("div");
        const detectedNames = detectedSkillObjects.map((s) => s.name).join(", ");
        successText.textContent = `✓ Berhasil memindai CV ATS (${file.name}): Terdeteksi ${detectedSkillObjects.length} skill [${detectedNames || "Tidak ada yang cocok"}].`;

        if (detectedSkillObjects.length > 6) {
          const noteSpan = document.createElement("span");
          noteSpan.style.cssText = "display: block; font-size: 11.5px; color: #047857; margin-top: 4px;";
          noteSpan.textContent = `(Dipilih otomatis 6 skill paling relevan dengan Target Career. Kamu bisa menyesuaikannya di daftar skill bawah).`;
          successText.appendChild(noteSpan);
        }

        if (detectedCvSummary) {
          const summarySnippet = document.createElement("div");
          summarySnippet.className = "ats-summary-snippet";
          summarySnippet.textContent = `Ringkasan profil terdeteksi: "${detectedCvSummary.slice(0, 140)}..."`;
          successText.appendChild(summarySnippet);
        }

        feedbackBox.appendChild(successText);
      }

      UI.toast(`Pemindaian ATS berhasil! ${detectedSkillObjects.length} skill terdeteksi.`);

    } catch (err) {
      console.error("Gagal memindai CV dengan PDF.js:", err);
      if (feedbackBox) {
        feedbackBox.className = "ats-feedback-box ats-feedback--error";
        feedbackBox.textContent = `Gagal memindai PDF CV: ${err.message || "Pastikan berkas PDF dapat dibaca teksnya"}`;
      }
      UI.toast("Gagal memindai PDF CV", "error");
    }
  }

  // Ekstraksi ringkasan profil sederhana dari teks CV
  function extractSummaryFromCvText(text) {
    const summaryMatch = text.match(/(?:summary|professional summary|ringkasan|profil|about me|tentang saya)[:\s\-]+([^.!?\n]+(?:[.!?][^.!?\n]+){1,3})/i);
    if (summaryMatch && summaryMatch[1]) {
      return summaryMatch[1].trim().replace(/\s+/g, " ");
    }
    // Fallback: ambil potongan kalimat pertama yang relevan (tanpa email atau angka panjang)
    const sentences = text.split(/(?<=[.!?])\s+/);
    for (const s of sentences) {
      const clean = s.trim();
      if (clean.length > 40 && clean.length < 240 && !clean.includes("@") && !/\d{6,}/.test(clean)) {
        return clean;
      }
    }
    return "";
  }

  // =========================================================================
  // 4. SKILL PICKER CERDAS & SEARCH RESPONSIF DATA
  // =========================================================================
  function renderSkillChips(filterKeyword = "") {
    skillsContainer.replaceChildren();

    // Urutkan cerdas: Skill yang SUDAH dimiliki mahasiswa diletakkan paling atas
    const mySkillIds = new Set(mySkills.map((s) => s.skill_id));
    const sortedCatalog = catalog.slice().sort((a, b) => {
      const aOwned = mySkillIds.has(a.id) ? 1 : 0;
      const bOwned = mySkillIds.has(b.id) ? 1 : 0;
      if (bOwned !== aOwned) return bOwned - aOwned;
      return a.name.localeCompare(b.name);
    });

    const keyword = filterKeyword.toLowerCase().trim();
    let renderedCount = 0;

    sortedCatalog.forEach((skill) => {
      if (keyword && !skill.name.toLowerCase().includes(keyword) && !(skill.category && skill.category.toLowerCase().includes(keyword))) {
        return;
      }

      renderedCount++;
      const isOwned = mySkillIds.has(skill.id);
      const isSelected = selectedSkillIds.includes(skill.id);

      const chip = document.createElement("button");
      chip.type = "button";
      chip.className = `skill-chip ${isOwned ? "is-owned" : ""} ${isSelected ? "is-selected" : ""}`;
      chip.dataset.skillId = skill.id;

      const nameSpan = document.createElement("span");
      nameSpan.textContent = skill.name;
      chip.appendChild(nameSpan);

      // Badge tanda milik user
      if (isOwned) {
        const badge = document.createElement("span");
        badge.className = "skill-chip-badge";
        badge.textContent = "✓ Milikmu";
        chip.appendChild(badge);
      }

      chip.onclick = () => {
        if (selectedSkillIds.includes(skill.id)) {
          selectedSkillIds = selectedSkillIds.filter((id) => id !== skill.id);
        } else {
          if (selectedSkillIds.length >= 6) {
            UI.toast("Maksimal 6 skill yang dapat didukung pada satu bukti", "warning");
            return;
          }
          selectedSkillIds.push(skill.id);
        }

        skillsInput.value = JSON.stringify(selectedSkillIds);
        updateSelectedChipsUi();
      };

      skillsContainer.appendChild(chip);
    });

    if (renderedCount === 0) {
      const noRes = document.createElement("p");
      noRes.style.cssText = "font-size: 13px; color: #94A3B8; padding: 8px 4px;";
      noRes.textContent = `Tidak ada skill yang cocok dengan "${filterKeyword}".`;
      skillsContainer.appendChild(noRes);
    }

    updateCounter();
  }

  function updateSelectedChipsUi() {
    skillsContainer.querySelectorAll(".skill-chip").forEach((chip) => {
      const id = parseInt(chip.dataset.skillId, 10);
      if (selectedSkillIds.includes(id)) {
        chip.classList.add("is-selected");
      } else {
        chip.classList.remove("is-selected");
      }
    });
    updateCounter();
  }

  function updateCounter() {
    skillCounter.textContent = `${selectedSkillIds.length} / 6 dipilih`;
  }

  // Pencarian Cepat Skill
  skillSearchInput.oninput = (e) => {
    renderSkillChips(e.target.value);
  };

  // =========================================================================
  // 5. INTERAKSI TIPE BUKTI & MODAL
  // =========================================================================
  typeGrid.querySelectorAll(".type-option").forEach((btn) => {
    btn.onclick = () => {
      typeGrid.querySelectorAll(".type-option").forEach((b) => {
        b.style.border = "2px solid #E2E8F0";
        b.style.background = "#FFFFFF";
        const labelSpan = b.querySelector("span");
        if (labelSpan) labelSpan.style.color = "#475569";
        const svg = b.querySelector("svg");
        if (svg) {
          if (svg.hasAttribute("stroke")) svg.setAttribute("stroke", "#475569");
          if (svg.hasAttribute("fill") && svg.getAttribute("fill") !== "none") svg.setAttribute("fill", "#475569");
        }
      });

      btn.style.border = "2px solid #6366F1";
      btn.style.background = "#F3F0FF";
      const activeSpan = btn.querySelector("span");
      if (activeSpan) activeSpan.style.color = "#6366F1";
      const activeSvg = btn.querySelector("svg");
      if (activeSvg) {
        if (activeSvg.hasAttribute("stroke")) activeSvg.setAttribute("stroke", "#6366F1");
        if (activeSvg.hasAttribute("fill") && activeSvg.getAttribute("fill") !== "none") activeSvg.setAttribute("fill", "#6366F1");
      }

      currentType = btn.dataset.value;
      typeInput.value = currentType;
      renderAttachmentSection(currentType);
    };
  });

  // Tombol Isi Data Demo
  demoBanner.onclick = () => {
    titleInput.value = "CV Full Stack Developer - ATS Optimized";
    summaryInput.value = "Pengalaman dalam rekayasa perangkat lunak web full-stack menggunakan JavaScript, Node.js, SQL, dan REST APIs. Terbiasa membangun aplikasi yang terukur, responsif, dan terintegrasi dengan database relasional.";
    levelSelect.value = "intermediate";

    // Auto-select 5 demo skills dari katalog
    const demoSkillNames = ["JavaScript", "Node.js", "SQL", "REST APIs", "Git"];
    selectedSkillIds = catalog
      .filter((s) => demoSkillNames.includes(s.name))
      .map((s) => s.id);

    // Fallback jika id tidak ketemu
    if (selectedSkillIds.length === 0 && catalog.length > 0) {
      selectedSkillIds = catalog.slice(0, 4).map((s) => s.id);
    }

    skillsInput.value = JSON.stringify(selectedSkillIds);
    updateSelectedChipsUi();

    // Set demo attachment
    hiddenAttachment.value = "CV_Demo_Fullstack_ATS.pdf";
    const dropzoneLabel = attachmentSection.querySelector(".upload-dropzone span");
    if (dropzoneLabel) dropzoneLabel.textContent = "Berkas demo: CV_Demo_Fullstack_ATS.pdf";

    UI.toast("Formulir berhasil diisi dengan contoh CV demo");
  };

  // Buka Modal Tambah
  btnAddEvidence.onclick = () => {
    editEvidenceId = null;
    modalTitle.textContent = "Ceritakan yang sudah kamu kerjakan";
    submitBtn.textContent = "Simpan Bukti";
    resetForm();
    modal.style.display = "flex";
  };

  // Buka Modal Edit
  function openEditModal(ev) {
    editEvidenceId = ev.id;
    modalTitle.textContent = "Edit Bukti Kompetensi";
    submitBtn.textContent = "Perbarui Bukti";

    // Isi Nilai
    titleInput.value = ev.title;
    summaryInput.value = ev.description || ev.metadata?.description || "";
    levelSelect.value = ev.metadata?.level || "intermediate";
    hiddenAttachment.value = ev.file_url || "";
    currentType = ev.source_type || "cv";
    typeInput.value = currentType;

    // Aktifkan tombol tipe yang sesuai
    typeGrid.querySelectorAll(".type-option").forEach((b) => {
      const isCurrent = b.dataset.value === currentType;
      b.style.border = isCurrent ? "2px solid #6366F1" : "2px solid #E2E8F0";
      b.style.background = isCurrent ? "#F3F0FF" : "#FFFFFF";
      const span = b.querySelector("span");
      if (span) span.style.color = isCurrent ? "#6366F1" : "#475569";
    });

    renderAttachmentSection(currentType, ev.file_url);

    // Resolusi Skills terpilih
    selectedSkillIds = [];
    if (Array.isArray(ev.skill_ids) && ev.skill_ids.length > 0) {
      selectedSkillIds = ev.skill_ids.slice();
    } else if (Array.isArray(ev.metadata?.skills)) {
      ev.metadata.skills.forEach((s) => {
        const found = catalog.find((c) => c.name.toLowerCase() === (typeof s === "string" ? s.toLowerCase() : s.name.toLowerCase()));
        if (found) selectedSkillIds.push(found.id);
      });
    }

    skillsInput.value = JSON.stringify(selectedSkillIds);
    renderSkillChips();
    updateSelectedChipsUi();

    modal.style.display = "flex";
  }

  // Tutup Modal
  function closeModal() {
    modal.style.display = "none";
    resetForm();
  }

  function resetForm() {
    form.reset();
    editEvidenceId = null;
    selectedSkillIds = [];
    skillsInput.value = "";
    hiddenAttachment.value = "";
    currentUploadedFile = null;
    detectedCvSummary = "";
    btnApplySummaryCv.style.display = "none";
    skillSearchInput.value = "";

    // Reset tipe ke CV
    currentType = "cv";
    typeInput.value = "cv";
    typeGrid.querySelectorAll(".type-option").forEach((b, idx) => {
      const isFirst = idx === 0;
      b.style.border = isFirst ? "2px solid #6366F1" : "2px solid #E2E8F0";
      b.style.background = isFirst ? "#F3F0FF" : "#FFFFFF";
      const span = b.querySelector("span");
      if (span) span.style.color = isFirst ? "#6366F1" : "#475569";
    });

    renderAttachmentSection("cv");
    renderSkillChips();
    updateCounter();
  }

  btnCloseModal.onclick = closeModal;
  btnCancel.onclick = closeModal;
  modal.onclick = (e) => {
    if (e.target === modal) closeModal();
  };
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "flex") closeModal();
  });

  // =========================================================================
  // 6. FORM SUBMIT & AUTO-SYNC KE MY SKILLS
  // =========================================================================
  form.onsubmit = async (e) => {
    e.preventDefault();

    if (selectedSkillIds.length === 0) {
      UI.toast("Pilih minimal 1 skill yang didukung", "error");
      return;
    }

    if (selectedSkillIds.length > 6) {
      UI.toast("Maksimal 6 skill yang dapat didukung", "error");
      return;
    }

    const titleVal = titleInput.value.trim();
    if (!titleVal) {
      UI.toast("Judul bukti wajib diisi", "error");
      return;
    }

    // Resolusi attachment file_url
    let fileUrlVal = hiddenAttachment.value.trim() || null;
    const urlInputEl = document.getElementById("ev-url-input");
    if (urlInputEl && urlInputEl.value.trim()) {
      fileUrlVal = urlInputEl.value.trim();
    } else if (currentUploadedFile) {
      fileUrlVal = currentUploadedFile.name;
    }

    const selectedSkillNames = selectedSkillIds.map((id) => {
      const found = catalog.find((c) => c.id === id);
      return found ? found.name : String(id);
    });

    const payload = {
      title: titleVal,
      source_type: typeInput.value,
      type: typeInput.value,
      file_url: fileUrlVal,
      attachment: fileUrlVal,
      description: summaryInput.value.trim(),
      summary: summaryInput.value.trim(),
      level: levelSelect.value,
      skills: selectedSkillNames,
      skill_ids: selectedSkillIds,
      metadata: {
        date: new Date().toISOString().split("T")[0],
        level: levelSelect.value,
        skills: selectedSkillNames,
        description: summaryInput.value.trim(),
        fileName: currentUploadedFile ? currentUploadedFile.name : null
      }
    };

    submitBtn.disabled = true;
    submitBtn.textContent = "Menyimpan...";

    try {
      if (editEvidenceId) {
        await API.updateEvidence({ id: editEvidenceId, ...payload });
      } else {
        await API.addEvidence(payload);
      }

      // SINKRONISASI OTOMATIS KE MY SKILLS
      // Jika ada skill yang belum terdaftar di mySkills mahasiswa, panggil API.addMySkill(skillId)
      const existingMySkillIds = new Set(mySkills.map((s) => s.skill_id));
      const newlyAddedSkills = [];

      for (const sId of selectedSkillIds) {
        if (!existingMySkillIds.has(sId)) {
          try {
            await API.addMySkill(sId);
            newlyAddedSkills.push(sId);
          } catch (syncErr) {
            console.warn(`Gagal sinkronisasi skill ${sId} ke mySkills:`, syncErr);
          }
        }
      }

      // Ambil data terbaru & render ulang tanpa reload halaman
      const [updatedEvidence, updatedMySkills] = await Promise.all([
        API.getEvidence(),
        API.getMySkills().catch(() => mySkills)
      ]);

      evidenceList = updatedEvidence;
      mySkills = updatedMySkills;

      renderEvidenceGrid();
      closeModal();

      if (newlyAddedSkills.length > 0) {
        UI.toast(`Bukti berhasil disimpan dan ${newlyAddedSkills.length} skill baru disinkronkan ke My Skills!`);
      } else {
        UI.toast(editEvidenceId ? "Bukti kompetensi berhasil diperbarui!" : "Bukti kompetensi berhasil disimpan!");
      }

    } catch (err) {
      console.error("Gagal menyimpan bukti:", err);
      UI.toast(`Gagal menyimpan: ${err.message}`, "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = editEvidenceId ? "Perbarui Bukti" : "Simpan Bukti";
    }
  };

})();
