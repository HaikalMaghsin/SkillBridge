<?php
// public/logout.php
// Logout Sesi Pengguna
require_once __DIR__ . '/../includes/auth/auth.php';
logout();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logging out...</title>
</head>
<body>
  <script>
    // Bersihkan localStorage sebelum redirect
    localStorage.removeItem('sb.mySkills');
    localStorage.removeItem('sb.myEvidence');
    localStorage.removeItem('sb.targetCareer');
    localStorage.removeItem('sb.profile');
    localStorage.removeItem('sb.portfolioVisibility');
    window.location.href = 'login.php';
  </script>
</body>
</html>
