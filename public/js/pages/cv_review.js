// pages/cv_review.js - CV Review page

(async () => {
  const confirmBtn = document.getElementById('confirm-btn');
  const checkboxes = document.querySelectorAll('input[type="checkbox"][name="skill_ids[]"]');

  if (!confirmBtn) return;

  confirmBtn.addEventListener('click', async () => {
    // Get selected skill IDs
    const selectedSkills = Array.from(checkboxes)
      .filter(cb => cb.checked)
      .map(cb => parseInt(cb.value));

    if (selectedSkills.length === 0) {
      alert('Please select at least one skill to add to your profile');
      return;
    }

    // Confirm with user
    const confirmed = confirm(
      `You are about to add ${selectedSkills.length} skill(s) to your profile. Continue?`
    );

    if (!confirmed) return;

    // Disable button
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="animation: spin 1s linear infinite;">
        <circle cx="8" cy="8" r="6" opacity="0.25"/>
        <path d="M8 2a6 6 0 0 1 6 6"/>
      </svg>
      Saving...
    `;

    try {
      const response = await API.confirmCVSkills({
        title: window.CV_DATA.title,
        url: window.CV_DATA.url,
        content: window.CV_DATA.content,
        skill_ids: selectedSkills
      });

      if (response.success) {
        alert(`Success! ${response.skills_added} skills added to your profile.`);
        window.location.href = API.BASE_URL + 'mahasiswa/skills.php';
      } else {
        alert('Failed to save skills. Please try again.');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = `
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 8l4 4 6-8"/>
          </svg>
          Confirm & Add to Profile
        `;
      }
    } catch (error) {
      console.error('CV confirmation error:', error);
      alert('An error occurred. Please try again.');
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 8l4 4 6-8"/>
        </svg>
        Confirm & Add to Profile
      `;
    }
  });

  // Toggle all checkboxes functionality (optional enhancement)
  const selectAllBtn = document.getElementById('select-all-btn');
  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', () => {
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      checkboxes.forEach(cb => cb.checked = !allChecked);
    });
  }
})();
