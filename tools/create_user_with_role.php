<?php
// tools/create_user_with_role.php
// CLI helper to create a user and assign a role. Usage:
// php tools/create_user_with_role.php --email=admin@example.com --name=Admin --password=secret123 --role=superadmin

$options = getopt('', ['email:', 'name::', 'password::', 'role::']);
if (php_sapi_name() !== 'cli') { echo "This script is CLI only.\n"; exit(1); }
$email = $options['email'] ?? null;
$name = $options['name'] ?? 'Admin';
$password = $options['password'] ?? bin2hex(random_bytes(6));
$role = $options['role'] ?? 'superadmin';
if (!$email) { echo "Missing --email\n"; exit(1); }
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1'); $st->execute([$email]);
if ($st->fetchColumn()) { echo "User already exists.\n"; exit(1); }
$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,is_active,created_at,email_verified) VALUES (?,?,?,?,1,NOW(),1)');
$ins->execute([$name,$email,$hash,$role]);
$uid = $pdo->lastInsertId();
// assign role
require_once __DIR__ . '/../src/rbac.php';
assign_role_to_user((int)$uid, $role);
echo "Created user {$email} with role {$role}. Password: {$password}\n";
