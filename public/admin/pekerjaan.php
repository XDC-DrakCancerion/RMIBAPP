<?php
// public/admin/pekerjaan.php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$action = $_GET['action'] ?? 'list';   
if (!in_array($action, ['list', 'Tambah', 'edit_mapping'], true)) $action = 'list';

// Filter list
$k  = strtoupper(trim($_GET['k'] ?? 'A'));   
$jk = strtoupper(trim($_GET['jk'] ?? 'L'));   
$q  = trim($_GET['q'] ?? '');

$steps = ['A','B','C','D','E','F','G','H','I'];
if (!in_array($k, $steps, true)) $k = 'A';
if (!in_array($jk, ['L','P'], true)) $jk = 'L';

$success = flash_get('success');
$error   = flash_get('error');

// Kategori RMIB
$categories = [
    1 => 'OUT (Outdoor)',
    2 => 'ME (Mechanical)',
    3 => 'COMP (Computational)',
    4 => 'SCI (Scientific)',
    5 => 'PERS (Personal Contact)',
    6 => 'AESTH (Aesthetic)',
    7 => 'LIT (Literary)',
    8 => 'MUS (Musical)',
    9 => 'S.S (Social Service)',
    10 => 'CLER (Clerical)',
    11 => 'PRAC (Practical)',
    12 => 'MED (Medical)'
];

function kelompok_id_from_kode(string $kode): int {
  $kode = strtoupper(trim($kode));
  if ($kode < 'A' || $kode > 'I') return 1; 
  return (ord($kode) - ord('A')) + 1;      
}


$kelompok_id = kelompok_id_from_kode($k);

// ---------- POST Handler ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('error', 'CSRF tidak valid.');
    redirect("pekerjaan.php?k={$k}&jk={$jk}");
  }

  $mode = $_POST['mode'] ?? '';

  // UPDATE MAPPING KATEGORI
  if ($mode === 'update_mapping') {
    $idp = (int)($_POST['id'] ?? 0);
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    
    if (!$idp) {
      flash_set('error', 'ID pekerjaan tidak valid.');
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    }

    // Validasi kategori (1-12 atau NULL)
    if ($id_kategori < 0 || $id_kategori > 12) {
      flash_set('error', 'ID kategori tidak valid.');
      redirect("pekerjaan.php?action=edit_mapping&id={$idp}&k={$k}&jk={$jk}");
    }

    // NULL jika 0
    $kategoriValue = $id_kategori === 0 ? null : $id_kategori;

    try {
      $stmt = db()->prepare("UPDATE tbl_pekerjaan SET id_kategori = ? WHERE id_pekerjaan = ?");
      $stmt->execute([$kategoriValue, $idp]);
      
      flash_set('success', 'Mapping kategori berhasil diperbarui.');
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    } catch (Throwable $e) {
      flash_set('error', 'Gagal update mapping: ' . $e->getMessage());
      redirect("pekerjaan.php?action=edit_mapping&id={$idp}&k={$k}&jk={$jk}");
    }
  }

  // DELETE
  if ($mode === 'delete') {
    $idp = (int)($_POST['id'] ?? 0);
    if (!$idp) {
      flash_set('error', 'ID tidak valid.');
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    }

    try {
      db()->prepare("DELETE FROM tbl_pekerjaan WHERE id_pekerjaan=?")->execute([$idp]);
      flash_set('success', 'Pekerjaan berhasil dihapus.');
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    } catch (Throwable $e) {
      flash_set('error', 'Gagal hapus: ' . $e->getMessage());
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    }
  }

  // TAMBAH (bulk) + pilih kelompok A-I
  if ($mode === 'Tambah') {
    $kode_kelompok = strtoupper(trim($_POST['kode_kelompok'] ?? $k));
    if (!in_array($kode_kelompok, $steps, true)) $kode_kelompok = 'A';

    // kunci utama: id_kelompok langsung dari huruf
    $kelompok_id_post = kelompok_id_from_kode($kode_kelompok);

    $jk_post = strtoupper(trim($_POST['jenis_kelamin'] ?? $jk));
    if (!in_array($jk_post, ['L','P'], true)) $jk_post = 'L';

    $lines = trim($_POST['Tambah_text'] ?? '');
    if ($lines === '') {
      flash_set('error', 'Daftar pekerjaan belum diisi.');
      redirect("pekerjaan.php?action=Tambah&k={$k}&jk={$jk}");
    }

    $items = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $lines))));
    if (count($items) < 12) {
      flash_set('error', 'Minimal 12 pekerjaan untuk memenuhi 1 kelompok tes.');
      redirect("pekerjaan.php?action=Tambah&k={$k}&jk={$jk}");
    }

    db()->beginTransaction();
    try {
      $ins = db()->prepare("INSERT INTO tbl_pekerjaan (id_kelompok, jenis_kelamin, nama_pekerjaan)
                            VALUES (?,?,?)");
      foreach ($items as $nm) {
        $ins->execute([$kelompok_id_post, $jk_post, $nm]);
      }
      db()->commit();

      flash_set('success', "Tambah pekerjaan berhasil untuk Kelompok {$kode_kelompok} (id={$kelompok_id_post}) JK {$jk_post}. Silakan mapping kategori untuk setiap pekerjaan.");
      redirect("pekerjaan.php?k={$kode_kelompok}&jk={$jk_post}");
    } catch (Throwable $e) {
      db()->rollBack();
      flash_set('error', 'Tambah gagal: ' . $e->getMessage());
      redirect("pekerjaan.php?action=Tambah&k={$k}&jk={$jk}");
    }
  }

  redirect("pekerjaan.php?k={$k}&jk={$jk}");
}

// ---------- Data untuk Edit Mapping ----------
$editData = null;
if ($action === 'edit_mapping') {
  $editId = (int)($_GET['id'] ?? 0);
  if ($editId > 0) {
    $stmt = db()->prepare("SELECT * FROM tbl_pekerjaan WHERE id_pekerjaan = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    
    if (!$editData) {
      flash_set('error', 'Data pekerjaan tidak ditemukan.');
      redirect("pekerjaan.php?k={$k}&jk={$jk}");
    }
  }
}

// ---------- Data List ----------
$sql = "
  SELECT p.*, kl.kode AS kode_kelompok
  FROM tbl_pekerjaan p
  JOIN tbl_kelompok_rmib kl ON kl.id_kelompok = p.id_kelompok
  WHERE p.id_kelompok = ?
    AND TRIM(p.jenis_kelamin) = ?
";
$params = [$kelompok_id, $jk];

if ($q !== '') {
  $sql .= " AND p.nama_pekerjaan LIKE ? ";
  $params[] = "%{$q}%";
}

$sql .= " ORDER BY p.id_pekerjaan ASC ";

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// ---------- View ----------
$title  = "Admin - Pekerjaan RMIB (Tambah + Hapus + Mapping)";
$active = "pekerjaan";
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
            <path fill-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0 1 12 15.75c-2.73 0-5.357-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.488 49.488 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Zm-3 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
            <path d="M3 18.4v-2.796a4.3 4.3 0 0 0 .713.31A26.226 26.226 0 0 0 12 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 0 1-6.477-.427C4.047 21.128 3 19.852 3 18.4Z" />
          </svg>
          <span>Pekerjaan</span>
        </h1>
      </div>
      <div class="flex gap-2">
        <a class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50" href="dashboard.php">Dashboard</a>
        <a class="px-4 py-2 rounded-xl bg-slate-900 text-white hover:opacity-95"
           href="pekerjaan.php?action=Tambah&k=<?= e($k) ?>&jk=<?= e($jk) ?>">+ Tambah</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="bg-white border rounded-2xl p-5 mb-4">
      <form method="get" class="grid md:grid-cols-4 gap-3">
        <input type="hidden" name="action" value="list">

        <div>
          <label class="text-sm font-medium">Kelompok</label>
          <select name="k" class="mt-1 w-full px-3 py-3 rounded-xl border bg-white">
            <?php foreach ($steps as $s): ?>
              <option value="<?= $s ?>" <?= $k===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium">Jenis Kelamin</label>
          <select name="jk" class="mt-1 w-full px-3 py-3 rounded-xl border bg-white">
            <option value="L" <?= $jk==='L'?'selected':'' ?>>L (Laki-laki)</option>
            <option value="P" <?= $jk==='P'?'selected':'' ?>>P (Perempuan)</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="text-sm font-medium">Cari Pekerjaan</label>
          <div class="mt-1 flex gap-2">
            <input name="q" value="<?= e($q) ?>" placeholder="ketik nama pekerjaan..."
                   class="w-full px-4 py-3 rounded-xl border">
            <button class="px-5 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">Filter</button>
          </div>
        </div>
      </form>

      <div class="mt-3 text-xs text-slate-500">
    </div>

    <?php if ($action === 'edit_mapping' && $editData): ?>
      <!-- FORM EDIT MAPPING KATEGORI -->
      <div class="bg-white border rounded-2xl p-6 max-w-2xl">
        <h2 class="font-semibold mb-2">Mapping Kategori Pekerjaan</h2>
        <p class="text-slate-600 text-sm mb-4">
          Pilih kategori RMIB yang sesuai untuk pekerjaan ini.
        </p>

        <form method="post" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="mode" value="update_mapping">
          <input type="hidden" name="id" value="<?= (int)$editData['id_pekerjaan'] ?>">

          <div>
            <label class="text-sm font-medium">Nama Pekerjaan</label>
            <div class="mt-1 px-4 py-3 rounded-xl border bg-slate-50 font-semibold">
              <?= e($editData['nama_pekerjaan']) ?>
            </div>
          </div>

          <div>
            <label class="text-sm font-medium">Kategori RMIB</label>
            <select name="id_kategori" class="mt-1 w-full px-4 py-3 rounded-xl border bg-white" required>
              <option value="0" <?= !$editData['id_kategori'] ? 'selected' : '' ?>>-- Pilih Kategori --</option>
              <?php foreach ($categories as $catId => $catName): ?>
                <option value="<?= $catId ?>" <?= (int)$editData['id_kategori'] === $catId ? 'selected' : '' ?>>
                  <?= e($catName) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="text-xs text-slate-500 mt-1">
              Pilih kategori yang paling sesuai dengan pekerjaan ini. Setiap pekerjaan harus memiliki kategori.
            </div>
          </div>

          <div class="flex gap-2">
            <button class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
              Simpan Mapping
            </button>
            <a class="px-6 py-3 rounded-xl border bg-white hover:bg-slate-50"
               href="pekerjaan.php?k=<?= e($k) ?>&jk=<?= e($jk) ?>">Batal</a>
          </div>
        </form>
      </div>

    <?php elseif ($action === 'Tambah'): ?>
      <!-- FORM TAMBAH -->
      <div class="bg-white border rounded-2xl p-6 max-w-4xl">
        <h2 class="font-semibold mb-2">Tambah Pekerjaan</h2>
        <p class="text-slate-600 text-sm mb-4">
          1 baris = 1 pekerjaan. Minimal 12 baris. Setelah ditambahkan, Anda perlu mapping kategori untuk setiap pekerjaan.
        </p>

        <form method="post" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="mode" value="Tambah">

          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium">Kelompok</label>
              <select name="kode_kelompok" class="mt-1 w-full px-4 py-3 rounded-xl border bg-white">
                <?php foreach ($steps as $s): ?>
                  <option value="<?= e($s) ?>" <?= $k===$s?'selected':'' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="text-xs text-slate-500 mt-1">A=1, B=2, ..., I=9 (id_kelompok otomatis).</div>
            </div>

            <div>
              <label class="text-sm font-medium">Jenis Kelamin</label>
              <select name="jenis_kelamin" class="mt-1 w-full px-4 py-3 rounded-xl border bg-white">
                <option value="L" <?= $jk==='L'?'selected':'' ?>>L (Laki-laki)</option>
                <option value="P" <?= $jk==='P'?'selected':'' ?>>P (Perempuan)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-sm font-medium">Daftar Pekerjaan</label>
            <textarea name="Tambah_text" rows="12" required
                      class="mt-1 w-full px-4 py-3 rounded-xl border"
                      placeholder="Contoh:
Dokter
Perawat
Apoteker
...
(min 12 baris)"></textarea>
          </div>

          <div class="flex gap-2">
            <button class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
              Simpan
            </button>
            <a class="px-6 py-3 rounded-xl border bg-white hover:bg-slate-50"
               href="pekerjaan.php?k=<?= e($k) ?>&jk=<?= e($jk) ?>">Batal</a>
          </div>
        </form>
      </div>

    <?php else: ?>
      <!-- LIST -->
      <div class="bg-white border rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <div class="font-semibold">
            Data Pekerjaan — Kelompok <?= e($k) ?> (JK: <?= e($jk) ?>)
          </div>
          <div class="text-xs text-slate-500">
            Total: <b><?= count($rows) ?></b>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-3 pr-4 w-16">No</th>
                <th class="py-3 pr-4">Pekerjaan</th>
                <th class="py-3 pr-4 w-48">Kategori</th>
                <th class="py-3 pr-4 w-28">JK</th>
                <th class="py-3 pr-4 w-48">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="py-4 text-slate-500">Data kosong. Tambahkan pekerjaan dengan Tambah.</td></tr>
              <?php endif; ?>

              <?php foreach ($rows as $i => $r): ?>
                <tr class="border-b last:border-b-0">
                  <td class="py-3 pr-4"><?= $i+1 ?></td>
                  <td class="py-3 pr-4 font-medium"><?= e($r['nama_pekerjaan']) ?></td>
                  <td class="py-3 pr-4">
                    <?php 
                    $idKat = (int)($r['id_kategori'] ?? 0);
                    if ($idKat > 0 && isset($categories[$idKat])): 
                    ?>
                      <span class="px-2 py-1 rounded-lg bg-blue-100 text-blue-800 text-xs font-semibold">
                        <?= e($categories[$idKat]) ?>
                      </span>
                    <?php else: ?>
                      <span class="px-2 py-1 rounded-lg bg-red-100 text-red-800 text-xs">
                        Belum di-mapping
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 pr-4">
                    <span class="px-3 py-1 rounded-full bg-slate-100"><?= e(trim($r['jenis_kelamin'])) ?></span>
                  </td>
                  <td class="py-3 pr-4">
                    <div class="flex gap-2">
                      <a href="pekerjaan.php?action=edit_mapping&id=<?= (int)$r['id_pekerjaan'] ?>&k=<?= e($k) ?>&jk=<?= e($jk) ?>"
                         class="px-3 py-2 rounded-xl bg-blue-600 text-white hover:opacity-95 text-xs">
                        <?= $idKat > 0 ? 'Edit Mapping' : 'Set Kategori' ?>
                      </a>
                      <form method="post" onsubmit="return confirm('Hapus pekerjaan ini?')" class="inline">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="mode" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id_pekerjaan'] ?>">
                        <button class="px-3 py-2 rounded-xl bg-red-600 text-white hover:opacity-95 text-xs">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 text-xs text-slate-500">
          Tips: Pastikan Kelompok <?= e($k) ?> JK <?= e($jk) ?> memiliki minimal 12 item dan semua sudah di-mapping ke kategori.
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>