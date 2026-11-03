// pages/cv.js - CV Upload page

(async () => {
  const form = document.getElementById('cv-upload-form');
  const submitBtn = document.getElementById('submit-btn');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const data = {
      title: formData.get('title'),
      url: formData.get('url'),
      content: formData.get('content')
    };

    // Validate
    if (!data.title || !data.content) {
      alert('Please fill in all required fields');
      return;
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="animation: spin 1s linear infinite;">
        <circle cx="8" cy="8" r="6" opacity="0.25"/>
        <path d="M8 2a6 6 0 0 1 6 6"/>
      </svg>
      Analyzing CV...
    `;

    try {
      const response = await API.extractCVSkills(data);

      if (response.success) {
        // Redirect to review page
        window.location.href = API.BASE_URL + 'mahasiswa/cv_review.php';
      } else {
        alert('Failed to analyze CV. Please try again.');
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
      alert('An error occurred. Please try again.');
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
