<?php
// admin/users.php
session_start();
require_once __DIR__ . '/../src/rbac.php';
// require permission
if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
if (!user_has_permission((int)$_SESSION['user_id'], 'manage_users')) { http_response_code(403); echo 'Forbidden'; exit; }
$pdo = get_pdo();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if ($userId && $role) { assign_role_to_user($userId, $role); }
}
$users = $pdo->query('SELECT id,name,email,created_at FROM users ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
$roles = $pdo->query('SELECT name FROM roles')->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Users</title></head><body>
<h1>Users</h1>
<table border="1"><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($users as $u): ?>
  <tr>
    <td><?php echo $u['id'] ?></td>
    <td><?php echo htmlspecialchars($u['name']) ?></td>
    <td><?php echo htmlspecialchars($u['email']) ?></td>
    <td>
      <form method="post" style="display:inline">
        <input type="hidden" name="user_id" value="<?php echo $u['id'] ?>">
        <select name="role"><?php foreach($roles as $r) echo '<option>'.htmlspecialchars($r).'</option>'; ?></select>
        <button type="submit">Assign role</button>
      </form>
    </td>
  </tr>
<?php endforeach; ?>
</table>
</body></html>
