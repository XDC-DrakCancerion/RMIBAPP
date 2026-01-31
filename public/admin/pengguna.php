<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('error', 'CSRF tidak valid.');
    redirect('pengguna.php');
  }

  $id_peserta = (int)($_POST['id'] ?? 0);
  if (!$id_peserta) {
    flash_set('error', 'ID peserta tidak valid.');
    redirect('pengguna.php');
  }

  try {
    db()->beginTransaction();

    // 1) Ambil id_pengguna dari tbl_peserta
    $stmt = db()->prepare("SELECT id_pengguna FROM tbl_peserta WHERE id_peserta=? LIMIT 1");
    $stmt->execute([$id_peserta]);
    $id_pengguna = (int)($stmt->fetchColumn() ?: 0);

    if (!$id_pengguna) {
      db()->rollBack();
      flash_set('error', 'id_pengguna tidak ditemukan pada data peserta.');
      redirect('pengguna.php');
    }

    // 2) Cek role user di tbl_pengguna (pastikan hanya role peserta = 2 yang boleh ikut terhapus)
    $stmt = db()->prepare("SELECT role FROM tbl_pengguna WHERE id_pengguna=? LIMIT 1");
    $stmt->execute([$id_pengguna]);
    $role = (int)($stmt->fetchColumn() ?: 0);

    if ($role !== 2) {
      db()->rollBack();
      flash_set('error', 'Penghapusan ditolak: akun ini bukan peserta (role=2).');
      redirect('pengguna.php');
    }

    // 3) Hapus peserta
    db()->prepare("DELETE FROM tbl_peserta WHERE id_peserta=?")->execute([$id_peserta]);

    // 4) Hapus akun pengguna (role peserta)
    db()->prepare("DELETE FROM tbl_pengguna WHERE id_pengguna=? AND role=2")->execute([$id_pengguna]);

    db()->commit();

    flash_set('success', 'Peserta dan akun penggunanya (role=2) berhasil dihapus.');
    redirect('pengguna.php');
  } catch (Throwable $e) {
    db()->rollBack();
    flash_set('error', 'Gagal menghapus: ' . $e->getMessage());
    redirect('pengguna.php');
  }
}

// ============================
// TAMPILAN HALAMAN (GET)
// ============================

// Cek status jika ada aksi
$success = flash_get('success');
$error = flash_get('error');

// Ambil daftar peserta
$query = "
  SELECT p.id_peserta, p.id_pengguna, p.nama, p.tgl_lahir, p.jenis_kelamin, p.pendidikan,
         s.tanggal_tes
  FROM tbl_peserta p
  LEFT JOIN tbl_sesi_rmib s ON p.id_peserta = s.id_peserta
  ORDER BY p.nama ASC
";

$rows = db()->query($query)->fetchAll();

$title = 'Admin - Daftar Pengguna';
$active = 'pengguna';
include __DIR__ . '/../../views/admin_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/admin_sidebar.php'; ?>

  <main class="flex-1 p-6">
    <div class="flex items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="flex items-center gap-2 text-2xl font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-6 h-6">
            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
          </svg>
          <span>Daftar Peserta</span>
        </h1>
      </div>
      <div class="flex gap-2">
        <a class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50" href="dashboard.php">Dashboard</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="bg-white border rounded-2xl p-5">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-3 pr-4">No</th>
            <th class="py-3 pr-4">Nama</th>
            <th class="py-3 pr-4">Tanggal Lahir</th>
            <th class="py-3 pr-4">Jenis Kelamin</th>
            <th class="py-3 pr-4">Pendidikan</th>
            <th class="py-3 pr-4">Tanggal Tes</th>
            <th class="py-3 pr-4">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="py-4 text-slate-500">Belum ada peserta yang terdaftar.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $i => $row): ?>
            <tr class="border-b last:border-b-0">
              <td class="py-3 pr-4"><?= $i + 1 ?></td>
              <td class="py-3 pr-4"><?= e($row['nama']) ?></td>
              <td class="py-3 pr-4"><?= e($row['tgl_lahir']) ?></td>
              <td class="py-3 pr-4"><?= e($row['jenis_kelamin']) ?></td>
              <td class="py-3 pr-4"><?= e($row['pendidikan']) ?></td>
              <td class="py-3 pr-4"><?= e($row['tanggal_tes'] ?? '-') ?></td>
              <td class="py-3 pr-4">
                <form method="post" action="pengguna.php">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$row['id_peserta'] ?>">
                  <button class="text-red-600 bg-white border px-3 py-1 rounded-xl hover:bg-slate-50"
                          onclick="return confirm('Yakin ingin menghapus peserta dan akun login-nya?')">
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>
