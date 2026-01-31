<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

if (is_logged_in()) {
  $role = (int)($_SESSION['user']['role'] ?? 2);
  if ($role === 1) redirect('admin/dashboard.php');
  redirect('peserta/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  $stmt = db()->prepare("SELECT * FROM tbl_pengguna WHERE username=? LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password'])) {
    $error = "Username atau password salah.";
  } else {
    // simpan session minimal yang dibutuhkan
    $_SESSION['user'] = [
      'id_pengguna'   => (int)$user['id_pengguna'],
      'username'      => $user['username'],
      'nama_lengkap'  => $user['nama_lengkap'],
      'role'          => (int)$user['role'],
    ];

    // tambahan data peserta untuk kebutuhan offline (role=2)
    if ((int)$user['role'] === 2) {
      $stp = db()->prepare("SELECT id_peserta, nama, jenis_kelamin, pendidikan, tgl_lahir
                            FROM tbl_peserta WHERE id_pengguna=? LIMIT 1");
      $stp->execute([(int)$user['id_pengguna']]);
      $peserta = $stp->fetch();
      if ($peserta) {
        $_SESSION['user']['id_peserta'] = (int)$peserta['id_peserta'];
        $_SESSION['user']['nama_peserta'] = (string)($peserta['nama'] ?? '');
        $_SESSION['user']['jenis_kelamin'] = (string)($peserta['jenis_kelamin'] ?? '');
        $_SESSION['user']['pendidikan'] = (string)($peserta['pendidikan'] ?? '');
        $_SESSION['user']['tgl_lahir'] = (string)($peserta['tgl_lahir'] ?? '');
      }
    }

    // redirect berdasarkan role
    if ((int)$user['role'] === 1) redirect('admin/dashboard.php');
    redirect('peserta/dashboard.php');
  }
}

$title = "Login RMIB";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0f172a" />
  <link rel="manifest" href="/manifest.webmanifest" />
  <link rel="apple-touch-icon" href="/icons/icon-192.png" />
  <title><?= e($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border p-6">
      <div class="text-center mb-6">
        <div class="text-xl font-semibold">Sistem Rekomendasi Pemilihan Jurusan</div>
        <div class="text-slate-500">Tes RMIB</div>
        <div class="grid grid-cols-2 gap-2 mb-6">
      <a class="text-center py-2 rounded-xl bg-slate-100 hover:bg-slate-200 font-medium" href="login.php">Login</a>
      <a class="text-center py-2 rounded-xl bg-slate-900 text-white font-medium" href="peserta/register.php">Register</a>
    </div>
      </div>

      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <div>
          <label class="text-sm font-medium">Username</label>
          <input name="username" required maxlength="25"
                 class="mt-1 w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-slate-900/20">
        </div>
        <div>
          <label class="text-sm font-medium">Password</label>
          <input type="password" name="password" required
                 class="mt-1 w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-slate-900/20">
        </div>

        <button class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
          Login
        </button>
      </form>
    </div>
  </div>

  <script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }
  </script>
</body>
</html>
