<?php
// UPDATED: admin/deposits.php -> display amount using amount_raw when available
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/rbac.php';
require_once __DIR__ . '/../src/deposit_processor.php';
require_once __DIR__ . '/../src/bignum.php';

if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
if (!user_has_permission((int)$_SESSION['user_id'], 'view_deposits')) { http_response_code(403); echo 'Forbidden'; exit; }
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && user_has_permission((int)$_SESSION['user_id'], 'process_deposits')) {
    $depositId = intval($_POST['deposit_id'] ?? 0);
    if ($depositId) {
        $st = $pdo->prepare('SELECT * FROM deposits WHERE id = ? LIMIT 1'); $st->execute([$depositId]); $dep = $st->fetch(PDO::FETCH_ASSOC);
        if ($dep) {
            $res = process_single_deposit($pdo, $dep);
            $msg = $res['ok'] ? 'Processed: '.$res['message'] : 'Error: '.$res['message'];
        } else $msg = 'Deposit not found';
    }
}

$rows = $pdo->query('SELECT id,tx_hash,amount,amount_raw,to_address,confirmations,chain,created_at,processed,processed_at FROM deposits ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Deposits</h1>
<?php if (!empty($msg)) echo '<p style="color:green">'.htmlspecialchars($msg).'</p>'; ?>
<table border="1" cellpadding="6" style="background:#fff;color:#000">
  <tr><th>ID</th><th>Chain</th><th>TX</th><th>To</th><th>Amount</th><th>Confirmations</th><th>Processed</th><th>Created</th><th>Action</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo $r['id'] ?></td>
      <td><?php echo htmlspecialchars($r['chain']) ?></td>
      <td style="font-family:monospace"><?php echo htmlspecialchars($r['tx_hash']) ?></td>
      <td style="font-family:monospace"><?php echo htmlspecialchars($r['to_address'] ?? '') ?></td>
      <td><?php
          if (!empty($r['amount_raw'])) echo htmlspecialchars(fromWei($r['amount_raw'],18,8));
          else echo htmlspecialchars($r['amount'] ?? $r['amount_raw'] ?? '');
      ?></td>
      <td><?php echo htmlspecialchars($r['confirmations']) ?></td>
      <td><?php echo $r['processed'] ? ('Yes at '.$r['processed_at']) : 'No' ?></td>
      <td><?php echo htmlspecialchars($r['created_at']) ?></td>
      <td>
        <?php if (!$r['processed'] && user_has_permission((int)$_SESSION['user_id'], 'process_deposits')): ?>
          <form method="post" style="display:inline"><input type="hidden" name="deposit_id" value="<?php echo $r['id'] ?>"><button type="submit">Process now</button></form>
        <?php else: ?>
          -
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
