<?php
// user/dashboard.php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/validate.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php'); exit;
}
$uid = (int)$_SESSION['user_id'];
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));

// fetch wallets for user
$st = $pdo->prepare('SELECT id,chain,address,label,created_at FROM wallets WHERE user_id = ? ORDER BY id');
$st->execute([$uid]);
$wallets = $st->fetchAll(PDO::FETCH_ASSOC);

function explorer_for_chain($chain, $address) {
    $map = [
        'bsc' => 'https://bscscan.com/address/',
        'arbitrum' => 'https://arbiscan.io/address/',
        'ethereum' => 'https://etherscan.io/address/'
    ];
    return ($map[$chain] ?? 'https://etherscan.io/address/') . $address;
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Your Dashboard</h1>
<p>Welcome back. Below are your wallets that were auto-created during signup.</p>
<?php if (empty($wallets)): ?>
  <p>You don't have any wallets yet.</p>
<?php else: ?>
  <table border="1" cellpadding="8" style="background:#fff;color:#000;">
    <tr><th>ID</th><th>Chain</th><th>Address</th><th>Label</th><th>Created</th><th>Actions</th></tr>
    <?php foreach ($wallets as $w): ?>
      <tr>
        <td><?php echo htmlspecialchars($w['id']) ?></td>
        <td><?php echo htmlspecialchars(strtoupper($w['chain'])) ?></td>
        <td style="font-family:monospace;"><?php echo htmlspecialchars($w['address']) ?></td>
        <td><?php echo htmlspecialchars($w['label']) ?></td>
        <td><?php echo htmlspecialchars($w['created_at']) ?></td>
        <td>
          <a href="<?php echo htmlspecialchars(explorer_for_chain($w['chain'],$w['address'])) ?>" target="_blank">View on block explorer</a>
          <!-- future: show balance, export options -->
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<p><a href="/user/profile.php">Edit profile</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
