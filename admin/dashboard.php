<?php
// admin/dashboard.php
require_once __DIR__ . '/../src/bootstrap.php';
// Require basic admin permission (manage_settings) to view dashboard
if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
if (!user_has_permission((int)$_SESSION['user_id'], 'manage_settings') && !user_has_permission((int)$_SESSION['user_id'], 'manage_users')) { http_response_code(403); echo 'Forbidden'; exit; }
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Admin Dashboard</h1>
<p>Welcome, user ID <?php echo htmlspecialchars($_SESSION['user_id']) ?></p>
<ul>
  <li><a href="/admin/settings.php">Settings</a></li>
  <li><a href="/admin/users.php">Users</a></li>
  <li><a href="/admin/wallets.php">Wallets (placeholder)</a></li>
  <li><a href="/admin/deposits.php">Deposits (placeholder)</a></li>
  <li><a href="/tools/create_user_with_role.php">Create user CLI (tools)</a></li>
</ul>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
