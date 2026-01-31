<?php
require_once __DIR__ . '/../../app/peserta_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$userId = (int)$_SESSION['user']['id_pengguna'];
$sesi = (int)($_GET['sesi'] ?? 0);
$k = strtoupper(trim($_GET['k'] ?? 'A'));
$steps = ['A','B','C','D','E','F','G','H','I'];

if (!$sesi || !in_array($k, $steps, true)) die("Parameter tidak valid.");

$stmt = db()->prepare("SELECT * FROM tbl_peserta WHERE id_pengguna=?");
$stmt->execute([$userId]);
$peserta = $stmt->fetch();
if (!$peserta) die("Peserta tidak ditemukan.");

$jk = $peserta['jenis_kelamin'];

$stmt = db()->prepare("SELECT * FROM tbl_sesi_rmib WHERE id_sesi=? AND id_peserta=?");
$stmt->execute([$sesi, (int)$peserta['id_peserta']]);
if (!$stmt->fetch()) die("Sesi tidak ditemukan.");

$stmt = db()->prepare("SELECT id_kelompok FROM tbl_kelompok_rmib WHERE kode=?");
$stmt->execute([$k]);
$id_kelompok = (int)($stmt->fetchColumn() ?: 0);
if (!$id_kelompok) die("Kelompok tidak ada.");

$stmt = db()->prepare("
  SELECT * FROM tbl_pekerjaan
  WHERE id_kelompok=? AND (jenis_kelamin=? OR jenis_kelamin='U')
  ORDER BY FIELD(jenis_kelamin, ?, 'U'), id_pekerjaan ASC
");
$stmt->execute([$id_kelompok, $jk, $jk]);
$jobs = $stmt->fetchAll();

if (count($jobs) < 12) die("Pekerjaan kelompok {$k} belum lengkap (min 12).");
$jobs = array_slice($jobs, 0, 12);

$stmt = db()->prepare("
  SELECT j.id_pekerjaan, j.peringkat
  FROM tbl_jawaban_rmib j
  JOIN tbl_pekerjaan p ON p.id_pekerjaan=j.id_pekerjaan
  WHERE j.id_sesi=? AND p.id_kelompok=?
");
$stmt->execute([$sesi, $id_kelompok]);
$pref = [];
foreach ($stmt->fetchAll() as $r) $pref[(int)$r['id_pekerjaan']] = (int)$r['peringkat'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ranks = $_POST['rank'] ?? [];
  $nums = [];

  // Validasi peringkat untuk setiap pekerjaan dalam kelompok A–I
  foreach ($jobs as $job) {
    $pid = (int)$job['id_pekerjaan'];
    $v = (int)($ranks[$pid] ?? 0);
    if ($v < 1 || $v > 12) { 
        $error = "Peringkat harus 1–12."; 
        break; 
    }
    $nums[] = $v;
  }

  // Jika tidak ada error, simpan jawaban
  if (!$error) {
    db()->beginTransaction();
    try {
      // Hapus jawaban lama untuk sesi dan kelompok ini
      $del = db()->prepare("
        DELETE j FROM tbl_jawaban_rmib j
        JOIN tbl_pekerjaan p ON p.id_pekerjaan=j.id_pekerjaan
        WHERE j.id_sesi=? AND p.id_kelompok=?
      ");
      $del->execute([$sesi, $id_kelompok]);

      // Simpan jawaban baru per kelompok A–I
      $ins = db()->prepare("INSERT INTO tbl_jawaban_rmib (id_sesi, id_pekerjaan, peringkat, kelompok) VALUES (?,?,?,?)");
      foreach ($jobs as $job) {
        $pid = (int)$job['id_pekerjaan'];
        $ins->execute([$sesi, $pid, (int)$ranks[$pid], $k]); // Tambahkan 'kelompok' di sini
      }
      db()->commit();

      // Redirect ke halaman berikutnya (kelompok selanjutnya)
      $idx = array_search($k, $steps, true);
      $next = $steps[$idx + 1] ?? null;

      if ($next) redirect("tes_wizard.php?sesi={$sesi}&k={$next}");
      redirect("tes_finish.php?sesi={$sesi}");
    } catch (Throwable $e) {
      db()->rollBack();
      $error = "Gagal menyimpan: " . $e->getMessage();
    }
  }
}

$idx = array_search($k, $steps, true);
$prev = $steps[$idx - 1] ?? null;

$title = "Tes Kelompok {$k}";
$active = "tes";
include __DIR__ . '/../../views/peserta_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/peserta_sidebar.php'; ?>

  <main class="flex-1 p-6">
    <div class="flex items-center justify-between gap-4 mb-4">
      <div>
        <h1 class="text-2xl font-semibold">Tes RMIB</h1>
        <p class="text-slate-500">Kelompok <b><?= e($k) ?></b> — isi peringkat 1–12</p>
      </div>
      <div class="px-4 py-2 rounded-xl bg-white border text-sm">
        Jenis Kelamin: <b><?= e($peserta['jenis_kelamin']) ?></b> | <?= e($peserta['nama']) ?>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white border rounded-2xl p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-3 pr-4 w-12">No</th>
              <th class="py-3 pr-4">Pekerjaan</th>
              <th class="py-3 pr-4 w-44">Peringkat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jobs as $i => $job): $pid=(int)$job['id_pekerjaan']; ?>
              <tr class="border-b last:border-b-0">
                <td class="py-3 pr-4"><?= $i+1 ?></td>
                <td class="py-3 pr-4"><?= e($job['nama_pekerjaan']) ?></td>
                <td class="py-3 pr-4">
                  <select name="rank[<?= $pid ?>]" required
                          class="w-full px-3 py-2 rounded-xl border bg-white focus:ring-2 focus:ring-slate-900/20">
                    <option value="">Pilih</option>
                    <?php for ($n=1;$n<=12;$n++): ?>
                      <option value="<?= $n ?>" <?= (isset($pref[$pid]) && $pref[$pid]===$n) ? 'selected':'' ?>><?= $n ?></option>
                    <?php endfor; ?>
                  </select>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex items-center justify-between gap-3">
        <div>
          <?php if ($prev): ?>
            <a class="inline-flex px-5 py-3 rounded-xl border bg-white hover:bg-slate-50"
               href="tes_wizard.php?sesi=<?= $sesi ?>&k=<?= $prev ?>">Sebelumnya</a>
          <?php else: ?>
            <a class="inline-flex px-5 py-3 rounded-xl border bg-white hover:bg-slate-50"
               href="tes.php">Kembali</a>
          <?php endif; ?>
        </div>

        <button class="inline-flex px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
          <?= ($k === 'I') ? 'Selesai' : 'Selanjutnya' ?>
        </button>
      </div>
    </form>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>
