// pages/cv.js - CV Upload page with Cloudflare R2 file storage & PDF.js extraction
(async () => {
  const form = document.getElementById('cv-upload-form');
  const submitBtn = document.getElementById('submit-btn');
  const fileInput = document.getElementById('cv-file');
  const titleInput = document.getElementById('cv-title');
  const contentInput = document.getElementById('cv-content');

  if (!form) return;

  let uploadedFileBase64 = null;
  let uploadedFileName = null;

  // Helper ekstraksi teks dari berkas PDF menggunakan Mozilla PDF.js
  async function extractTextFromPdf(file) {
    if (typeof pdfjsLib === 'undefined') {
      console.warn('pdfjsLib is not defined');
      return null;
    }

    try {
      const arrayBuffer = await file.arrayBuffer();
      const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
      const pdf = await loadingTask.promise;
      const allLines = [];

      for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const textContent = await page.getTextContent();

        // Kelompokkan item berdasarkan koordinat Y
        const items = textContent.items
          .filter(item => typeof item.str === 'string' && item.str.trim() && item.transform)
          .map(item => ({
            str: item.str,
            x: item.transform[4],
            y: item.transform[5],
            width: item.width || 0,
            height: Math.abs(item.transform[3]) || 10
          }));

        const lines = [];
        items.forEach(item => {
          let foundLine = lines.find(line => Math.abs(item.y - line.y) <= 3.5);
          if (!foundLine) {
            foundLine = { y: item.y, items: [] };
            lines.push(foundLine);
          }
          foundLine.items.push(item);
        });

        lines.sort((a, b) => b.y - a.y);
        lines.forEach(line => {
          line.items.sort((a, b) => a.x - b.x);
        });

        const reconstructed = lines.map(line => {
          let text = '';
          line.items.forEach((item, idx) => {
            if (idx === 0) {
              text = item.str;
            } else {
              const prev = line.items[idx - 1];
              const gap = item.x - (prev.x + prev.width);
              if (gap < 2.2 || prev.str.endsWith('-') || /^[,.;:!?)\]}]/.test(item.str)) {
                text += item.str;
              } else {
                text += ' ' + item.str;
              }
            }
          });
          return text;
        });

        allLines.push(...reconstructed);
      }

      let fullText = allLines.join('\n');
      // Defragment kata berjarak spasi seperti "P y t h o n" -> "Python"
      fullText = fullText.replace(/\b([A-Za-z])\s+([A-Za-z])\s+([A-Za-z])(?:\s+([A-Za-z]))*\b/g, match => match.replace(/\s+/g, ''));
      return fullText.trim();
    } catch (e) {
      console.warn('PDF text extraction error:', e);
      return null;
    }
  }

  if (fileInput) {
    fileInput.addEventListener('change', async () => {
      if (fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        uploadedFileName = file.name;

        // Auto-fill title jika kosong
        if (!titleInput.value) {
          const cleanName = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ');
          titleInput.value = `CV ATS — ${cleanName}`;
        }

        // Tampilkan placeholder loading pada konten
        contentInput.placeholder = 'Sedang memindai dan mengekstrak teks dari dokumen CV PDF dengan Mozilla PDF.js...';

        // Baca file ke Base64 Data URL untuk Cloudflare R2
        try {
          uploadedFileBase64 = await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
          });
        } catch (err) {
          console.error("Gagal membaca berkas CV:", err);
        }

        // Ekstraksi teks dari PDF
        if (file.name.toLowerCase().endsWith('.pdf')) {
          const extractedText = await extractTextFromPdf(file);
          if (extractedText && extractedText.length > 20) {
            contentInput.value = extractedText;
            if (typeof UI !== 'undefined' && UI.toast) {
              UI.toast('✓ Dokumen PDF berhasil dipindai dan teks diekstrak!');
            }
          } else if (!contentInput.value) {
            contentInput.value = `Berkas CV: ${file.name}\nDiunggah untuk ekstraksi keterampilan dan verifikasi portofolio.`;
          }
        }
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (fileInput && fileInput.files && fileInput.files[0] && !uploadedFileBase64) {
      try {
        uploadedFileBase64 = await new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result);
          reader.onerror = reject;
          reader.readAsDataURL(fileInput.files[0]);
        });
        uploadedFileName = fileInput.files[0].name;
      } catch (err) {
        console.error("Gagal membaca berkas CV saat submit:", err);
      }
    }

    // Jika user upload PDF tapi contentInput masih kosong, coba ekstrak teks sekali lagi
    if (fileInput && fileInput.files && fileInput.files[0] && !contentInput.value.trim()) {
      const extractedText = await extractTextFromPdf(fileInput.files[0]);
      if (extractedText && extractedText.length > 20) {
        contentInput.value = extractedText;
      } else {
        contentInput.value = `Berkas CV: ${fileInput.files[0].name}\nDiunggah untuk ekstraksi keterampilan dan verifikasi portofolio.`;
      }
    }

    const formData = new FormData(form);
    const data = {
      title: formData.get('title'),
      url: formData.get('url'),
      content: formData.get('content')
    };

    if (uploadedFileBase64 && uploadedFileName) {
      data.file_base64 = uploadedFileBase64;
      data.file_name = uploadedFileName;
    }

    // Validate
    if (!data.title || !data.content) {
      alert('Mohon isi Judul CV dan Konten/Ringkasan CV');
      return;
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="animation: spin 1s linear infinite;">
        <circle cx="8" cy="8" r="6" opacity="0.25"/>
        <path d="M8 2a6 6 0 0 1 6 6"/>
      </svg>
      Mengunggah ke Cloudflare R2 & Menganalisis CV...
    `;

    try {
      const response = await API.extractCVSkills(data);

      if (response.success) {
        // Redirect to review page
        const baseUrl = (typeof window !== "undefined" && window.APP_BASE_URL !== undefined) ? window.APP_BASE_URL.replace(/\/+$/, "") : "";
        window.location.href = baseUrl + '/mahasiswa/cv_review.php';
      } else {
        alert('Gagal memproses CV. Silakan coba lagi.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M8 2v12M2 8h12"/>
          </svg>
          Extract Skills from CV
        `;
      }
    } catch (error) {
      console.error('CV extraction error:', error);
      alert('Terjadi kesalahan: ' + error.message);
      submitBtn.disabled = false;
      submitBtn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M8 2v12M2 8h12"/>
        </svg>
        Extract Skills from CV
      `;
    }
  });
})();
