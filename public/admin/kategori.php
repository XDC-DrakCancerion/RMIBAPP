<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$success = flash_get('success');
$error = flash_get('error');

function kategori_find(int $id): ?array {
  $st = db()->prepare("SELECT * FROM tbl_kategori_minat WHERE id_kategori=?");
  $st->execute([$id]);
  $r = $st->fetch();
  return $r ?: null;
}

function next_kategori_id(): int {
  $used = db()->query("SELECT id_kategori FROM tbl_kategori_minat ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_COLUMN);
  $used = array_map('intval', $used);

  for ($i = 1; $i <= 12; $i++) {
    if (!in_array($i, $used, true)) return $i;
  }
  return 0; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('error', 'CSRF tidak valid.');
    redirect('kategori.php');
  }

  $mode = $_POST['mode'] ?? '';

  // CREATE
  if ($mode === 'create') {
    $kd = strtoupper(trim($_POST['kd_kategori'] ?? ''));
    $nama = trim($_POST['nama_kategori'] ?? '');
    $desc = trim($_POST['deskripsi_kategori'] ?? '');

    if ($kd === '' || $nama === '') {
      flash_set('error', 'Kode dan nama wajib diisi.');
      redirect('kategori.php?action=create');
    }

    // cek kode unik
    $cek = db()->prepare("SELECT 1 FROM tbl_kategori_minat WHERE kd_kategori=?");
    $cek->execute([$kd]);
    if ($cek->fetchColumn()) {
      flash_set('error', 'Kode kategori sudah digunakan.');
      redirect('kategori.php?action=create');
    }

    $new_id = next_kategori_id();
    if ($new_id === 0) {
      flash_set('error', 'Kategori sudah penuh. Maksimal hanya 12 kategori (ID 1–12).');
      redirect('kategori.php');
    }

    db()->prepare("INSERT INTO tbl_kategori_minat (id_kategori, kd_kategori, nama_kategori, deskripsi_kategori)
                   VALUES (?,?,?,?)")
      ->execute([$new_id, $kd, $nama, $desc ?: null]);

    flash_set('success', "Kategori berhasil ditambahkan (ID: {$new_id}).");
    redirect('kategori.php');
  }

  // UPDATE
  if ($mode === 'update') {
    $idp = (int)($_POST['id'] ?? 0);
    $kd = strtoupper(trim($_POST['kd_kategori'] ?? ''));
    $nama = trim($_POST['nama_kategori'] ?? '');
    $desc = trim($_POST['deskripsi_kategori'] ?? '');

    if (!$idp || $kd === '' || $nama === '') {
      flash_set('error', 'Data tidak lengkap.');
      redirect("kategori.php?action=edit&id={$idp}");
    }

    $cek = db()->prepare("SELECT 1 FROM tbl_kategori_minat WHERE kd_kategori=? AND id_kategori<>?");
    $cek->execute([$kd, $idp]);
    if ($cek->fetchColumn()) {
      flash_set('error', 'Kode kategori sudah dipakai kategori lain.');
      redirect("kategori.php?action=edit&id={$idp}");
    }

    db()->prepare("UPDATE tbl_kategori_minat
                   SET kd_kategori=?, nama_kategori=?, deskripsi_kategori=?
                   WHERE id_kategori=?")
      ->execute([$kd, $nama, $desc ?: null, $idp]);

    flash_set('success', 'Kategori berhasil diupdate.');
    redirect('kategori.php');
  }

  // DELETE
  if ($mode === 'delete') {
    $idp = (int)($_POST['id'] ?? 0);
    if (!$idp) {
      flash_set('error', 'ID tidak valid.');
      redirect('kategori.php');
    }

    if ($idp < 1 || $idp > 12) {
      flash_set('error', 'Kategori yang boleh dikelola hanya ID 1–12.');
      redirect('kategori.php');
    }

    db()->prepare("DELETE FROM tbl_kategori_minat WHERE id_kategori=?")->execute([$idp]);
    flash_set('success', "Kategori ID {$idp} berhasil dihapus. Slot ID akan dipakai lagi saat create.");
    redirect('kategori.php');
  }
}

// LIST
$q = trim($_GET['q'] ?? '');
$rows = [];
if ($q !== '') {
  $st = db()->prepare("SELECT * FROM tbl_kategori_minat
                       WHERE kd_kategori LIKE ? OR nama_kategori LIKE ?
                       ORDER BY id_kategori ASC");
  $like = "%{$q}%";
  $st->execute([$like, $like]);
  $rows = $st->fetchAll();
} else {
  $rows = db()->query("SELECT * FROM tbl_kategori_minat ORDER BY id_kategori ASC")->fetchAll();
}

$current = null;
if ($action === 'edit' && $id) {
  $current = kategori_find($id);
  if (!$current) {
    flash_set('error', 'Data tidak ditemukan.');
    redirect('kategori.php');
  }
}

$title = "Admin - Kategori";
$active = "kategori";
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
            <path d="M11.25 4.533A9.707 9.707 0 0 0 6 3a9.735 9.735 0 0 0-3.25.555.75.75 0 0 0-.5.707v14.25a.75.75 0 0 0 1 .707A8.237 8.237 0 0 1 6 18.75c1.995 0 3.823.707 5.25 1.886V4.533ZM12.75 20.636A8.214 8.214 0 0 1 18 18.75c.966 0 1.89.166 2.75.47a.75.75 0 0 0 1-.708V4.262a.75.75 0 0 0-.5-.707A9.735 9.735 0 0 0 18 3a9.707 9.707 0 0 0-5.25 1.533v16.103Z" />
          </svg>
          <span>Kategori RMIB</span>
        </h1>
      </div>
      <div class="flex gap-2">
        <a class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50" href="dashboard.php">Dashboard</a>
        <a class="px-4 py-2 rounded-xl bg-slate-900 text-white hover:opacity-95" href="kategori.php?action=create">+ Tambah</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
      <div class="bg-white border rounded-2xl p-6 max-w-3xl">
        <h2 class="font-semibold mb-4"><?= $action === 'create' ? 'Tambah Kategori' : 'Edit Kategori' ?></h2>

        <form method="post" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="mode" value="<?= $action === 'create' ? 'create' : 'update' ?>">
          <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= (int)$current['id_kategori'] ?>">
          <?php endif; ?>

          <div>
            <label class="text-sm font-medium">Kode (unik)</label>
            <input name="kd_kategori" required maxlength="12"
              value="<?= e($current['kd_kategori'] ?? '') ?>"
              class="mt-1 w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-slate-900/20">
          </div>

          <div>
            <label class="text-sm font-medium">Nama</label>
            <input name="nama_kategori" required maxlength="20"
              value="<?= e($current['nama_kategori'] ?? '') ?>"
              class="mt-1 w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-slate-900/20">
          </div>

          <div>
            <label class="text-sm font-medium">Deskripsi</label>
            <textarea name="deskripsi_kategori" rows="4"
              class="mt-1 w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-slate-900/20"><?= e($current['deskripsi_kategori'] ?? '') ?></textarea>
          </div>

          <div class="flex gap-2">
            <button class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
              <?= $action === 'create' ? 'Simpan' : 'Update' ?>
            </button>
            <a class="px-6 py-3 rounded-xl border bg-white hover:bg-slate-50" href="kategori.php">Batal</a>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="bg-white border rounded-2xl p-5 mb-4">
        <form class="flex gap-2" method="get">
          <input type="hidden" name="action" value="list">
          <input name="q" value="<?= e($q) ?>" placeholder="Cari kode / nama kategori..."
                 class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-slate-900/20">
          <button class="px-5 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">Cari</button>
        </form>
      </div>

      <div class="bg-white border rounded-2xl p-5">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-3 pr-4 w-16">ID</th>
                <th class="py-3 pr-4 w-28">Kode</th>
                <th class="py-3 pr-4 w-56">Nama</th>
                <th class="py-3 pr-4">Deskripsi</th>
                <th class="py-3 pr-4 w-40">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="py-4 text-slate-500">Data kategori kosong.</td></tr>
              <?php endif; ?>

              <?php foreach ($rows as $r): ?>
                <tr class="border-b last:border-b-0">
                  <td class="py-3 pr-4"><?= (int)$r['id_kategori'] ?></td>
                  <td class="py-3 pr-4"><span class="px-3 py-1 rounded-full bg-slate-100"><?= e($r['kd_kategori']) ?></span></td>
                  <td class="py-3 pr-4 font-medium"><?= e($r['nama_kategori']) ?></td>
                  <td class="py-3 pr-4 text-slate-600"><?= e($r['deskripsi_kategori'] ?? '-') ?></td>
                  <td class="py-3 pr-4">
                    <div class="flex gap-2">
                      <a class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50"
                         href="kategori.php?action=edit&id=<?= (int)$r['id_kategori'] ?>">Edit</a>

                      <form method="post" onsubmit="return confirm('Hapus kategori ini?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="mode" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id_kategori'] ?>">
                        <button class="px-3 py-2 rounded-xl bg-red-600 text-white hover:opacity-95">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>
