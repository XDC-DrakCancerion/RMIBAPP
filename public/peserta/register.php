<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/helpers.php';

if (is_logged_in()) redirect('dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
  $tgl_lahir = $_POST['tgl_lahir'] ?? '';
  $pendidikan = trim($_POST['pendidikan'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $ulang = (string)($_POST['ulang_password'] ?? '');

  if ($password !== $ulang) $error = "Ulang password tidak sama.";
  elseif (!in_array($jenis_kelamin, ['L','P'], true)) $error = "Jenis kelamin tidak valid.";
  elseif (!$tgl_lahir) $error = "Tanggal lahir wajib diisi.";
  else {
    $stmt = db()->prepare("SELECT 1 FROM tbl_pengguna WHERE username=?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn()) $error = "Username sudah dipakai.";
    else {
      $hash = password_hash($password, PASSWORD_BCRYPT);

      db()->beginTransaction();
      try {
        db()->prepare("INSERT INTO tbl_pengguna (username,password,nama_lengkap,role) VALUES (?,?,?,2)")
            ->execute([$username, $hash, $nama_lengkap]);
        $id_pengguna = (int)db()->lastInsertId();

        $usia = (int)(new DateTime($tgl_lahir))->diff(new DateTime())->y;
        $nomor_test = 'RMIB-' . date('Ymd') . '-' . str_pad((string)$id_pengguna, 5, '0', STR_PAD_LEFT);

        db()->prepare("INSERT INTO tbl_peserta (id_pengguna, nomor_test, nama, jenis_kelamin, usia, pendidikan, tgl_lahir)
                       VALUES (?,?,?,?,?,?,?)")
          ->execute([$id_pengguna, $nomor_test, mb_substr($nama_lengkap,0,30), $jenis_kelamin, $usia, mb_substr($pendidikan,0,20), $tgl_lahir]);

        db()->commit();
        $success = "Registrasi berhasil. Silakan login.";
      } catch (Throwable $e) {
        db()->rollBack();
        $error = "Registrasi gagal: " . $e->getMessage();
      }
    }
  }
}

$title = "Register Peserta";
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
<div class="min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border p-6">
    <div class="text-center mb-6">
      <div class="text-xl font-semibold">Sistem Rekomendasi Pemilihan Jurusan</div>
      <div class="text-slate-500">Metode Tes RMIB</div>
      <div class="mt-2 text-xs px-3 py-1 inline-block rounded-full bg-slate-900 text-white">PESERTA</div>
    </div>

    <div class="grid grid-cols-2 gap-2 mb-6">
      <a class="text-center py-2 rounded-xl bg-slate-100 text-black hover:bg-slate-200 font-medium" href="../login.php">Login</a>
      <a class="text-center py-2 rounded-xl bg-slate-900 text-white font-medium" href="register.php">Register</a>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>

      <form method="post" class="space-y-4">
      
      <div>
        <label class="text-sm font-medium text-black">Nama Lengkap</label>
        <input name="nama_lengkap" required maxlength="30"
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

      <div>
        <label class="text-sm font-medium text-black">Username</label>
        <input name="username" required maxlength="25"
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

      <div>
        <label class="text-sm font-medium text-black">Jenis Kelamin</label>
        <select name="jenis_kelamin" required
          class="mt-1 w-full px-4 py-3 rounded-xl border bg-white text-black focus:ring-2 focus:ring-slate-900/20">
          <option value="">Pilih</option>
          <option value="L">Laki-laki</option>
          <option value="P">Perempuan</option>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium text-black">Tanggal Lahir</label>
        <input type="date" name="tgl_lahir" required
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

      <div>
        <label class="text-sm font-medium text-black">Pendidikan</label>
        <input name="pendidikan" required maxlength="20"
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

      <div>
        <label class="text-sm font-medium text-black">Password</label>
        <input type="password" name="password" required
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

      <div>
        <label class="text-sm font-medium text-black">Ulang Password</label>
        <input type="password" name="ulang_password" required
          class="mt-1 w-full px-4 py-3 rounded-xl border text-black focus:ring-2 focus:ring-slate-900/20">
      </div>

    <button 
        type="submit"
        class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
        Register
      </button>
  </div>
</div>
</body>
</html>