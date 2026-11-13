// pages/cv.js - CV Upload page with Cloudflare R2 file storage
(async () => {
  const form = document.getElementById('cv-upload-form');
  const submitBtn = document.getElementById('submit-btn');
  const fileInput = document.getElementById('cv-file');
  const titleInput = document.getElementById('cv-title');
  const contentInput = document.getElementById('cv-content');

  if (!form) return;

  let uploadedFileBase64 = null;
  let uploadedFileName = null;

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

        // Auto-fill ringkasan dasar jika kosong agar validasi required lolos
        if (!contentInput.value) {
          contentInput.value = `Berkas CV: ${file.name}\nDiunggah untuk ekstraksi keterampilan dan verifikasi portofolio.`;
        }

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
