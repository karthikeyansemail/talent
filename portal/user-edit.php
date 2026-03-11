<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$db = db();
$id = (int)($_GET['id'] ?? 0);
$user = null;
$errors = [];

if ($id) {
    $stmt = $db->prepare('SELECT id, name, email, role, is_active FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash('error', 'User not found.');
        header('Location: ' . BASE . '/users.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit User' : 'New User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'support';
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if (!$name) $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!in_array($role, ['admin','sales','support','dev'], true)) $errors[] = 'Invalid role.';
    if (!$id && !$password) $errors[] = 'Password is required for new users.';
    if ($password && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    // Self-protection
    if ($id && $id === (int)$_SESSION['admin_id']) {
        if ($role !== 'admin') $errors[] = 'You cannot change your own role.';
        if (!$isActive) $errors[] = 'You cannot deactivate yourself.';
    }

    // Email uniqueness
    if (empty($errors)) {
        $check = $db->prepare('SELECT id FROM admin_users WHERE email = ? AND id != ?');
        $check->execute([$email, $id]);
        if ($check->fetch()) $errors[] = 'Email already in use by another user.';
    }

    if (empty($errors)) {
        if ($id) {
            $sql = 'UPDATE admin_users SET name=?, email=?, role=?, is_active=?';
            $params = [$name, $email, $role, $isActive];
            if ($password) {
                $sql .= ', password=?';
                $params[] = password_hash($password, PASSWORD_BCRYPT);
            }
            $sql .= ' WHERE id=?';
            $params[] = $id;
            $db->prepare($sql)->execute($params);
            flash('success', 'User updated successfully.');
        } else {
            $db->prepare('INSERT INTO admin_users (name, email, password, role, is_active) VALUES (?,?,?,?,?)')
               ->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $isActive]);
            flash('success', 'User created successfully.');
        }
        header('Location: ' . BASE . '/users.php');
        exit;
    }

    // Preserve form data on error
    $user = [
        'id'        => $id,
        'name'      => $name,
        'email'     => $email,
        'role'      => $role,
        'is_active' => $isActive,
    ];
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE ?>/users.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Users</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?>
    <div><?= h($err) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <div class="card-header"><span><?= $id ? 'Edit User' : 'Create User' ?></span></div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="<?= h($user['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($user['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" <?= ($id && $id === (int)$_SESSION['admin_id']) ? 'disabled' : '' ?>>
                    <?php foreach (['admin'=>'Admin — Full access','sales'=>'Sales — Marketing, leads, pipeline, customers, orders','support'=>'Support — Tickets, chat','dev'=>'Dev — Error logs, dev tickets, instances'] as $rVal => $rLabel): ?>
                    <option value="<?= $rVal ?>" <?= ($user['role'] ?? 'support') === $rVal ? 'selected' : '' ?>><?= $rLabel ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($id && $id === (int)$_SESSION['admin_id']): ?>
                <input type="hidden" name="role" value="admin">
                <small style="color:var(--gray-400)">You cannot change your own role.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
                <input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?> minlength="8" placeholder="<?= $id ? '••••••••' : 'Min 8 characters' ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?> <?= ($id && $id === (int)$_SESSION['admin_id']) ? 'disabled' : '' ?>>
                <label for="is_active" style="margin:0">Active</label>
                <?php if ($id && $id === (int)$_SESSION['admin_id']): ?>
                <input type="hidden" name="is_active" value="1">
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Update User' : 'Create User' ?></button>
                <a href="<?= BASE ?>/users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
