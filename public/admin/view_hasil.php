<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$sesiId = (int)($_GET['sesi_id'] ?? 0);
if ($sesiId === 0) {
    flash_set('error', 'Sesi tidak ditemukan.');
    redirect('index.php'); // list hasil admin
}

// Ambil data sesi tes
$stmt = db()->prepare("SELECT * FROM tbl_sesi_rmib WHERE id_sesi=? LIMIT 1");
$stmt->execute([$sesiId]);
$sesi = $stmt->fetch();

if (!$sesi) {
    flash_set('error', 'Sesi tidak ditemukan.');
    redirect('index.php');
}

// Ambil data peserta dari id_peserta pada sesi
$stmt = db()->prepare("SELECT * FROM tbl_peserta WHERE id_peserta=? LIMIT 1");
$stmt->execute([(int)$sesi['id_peserta']]);
$peserta = $stmt->fetch();

if (!$peserta) {
    flash_set('error', 'Data peserta tidak ditemukan.');
    redirect('index.php');
}

/**
 * -----------------------------
 * Ambil 3 pekerjaan favorit (fav1, fav2, fav3)
 * -----------------------------
 */
$favStmt = db()->prepare("SELECT fav1, fav2, fav3 FROM tbl_pekerjaan_favorit WHERE id_peserta=? LIMIT 1");
$favStmt->execute([(int)$peserta['id_peserta']]);
$favRow = $favStmt->fetch(PDO::FETCH_ASSOC);

$favorit = [
    $favRow['fav1'] ?? '-',
    $favRow['fav2'] ?? '-',
    $favRow['fav3'] ?? '-',
];

foreach ($favorit as $i => $val) {
    $val = trim((string)$val);
    $favorit[$i] = $val === '' ? '-' : $val;
}

/**
 * -----------------------------
 * Ambil jawaban RMIB + id_kategori dari pekerjaan
 * -----------------------------
 */
$jawabanStmt = db()->prepare("
    SELECT j.id_pekerjaan, j.peringkat, j.kelompok, p.id_kategori
    FROM tbl_jawaban_rmib j
    JOIN tbl_pekerjaan p ON p.id_pekerjaan = j.id_pekerjaan
    WHERE j.id_sesi = ?
    ORDER BY j.kelompok ASC
");
$jawabanStmt->execute([$sesiId]);
$jawaban = $jawabanStmt->fetchAll(PDO::FETCH_ASSOC);

// Definisi kategori & kelompok
$categories = ['Outdoor (OUT)', 'Mechanical (ME)', 'Computational (COMP)', 'Scientific (SCI)', 'Personal Contact (PERS)',
               'Aesthetic (AESTH)', 'Literary (LIT)', 'Musical (MUS)', 'Social Service (S.S)', 'Clerical (CLER)', 'Practical (PRAC)', 'Medical (MED)'];
$kelompok   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

// Siapkan wadah skor per kategori per kelompok
$rankingData = [];
foreach ($categories as $cat) {
    foreach ($kelompok as $k) {
        $rankingData[$cat][$k] = 0;
    }
}

// Akumulasi peringkat per kelompok pada kategori
foreach ($jawaban as $row) {
    $peringkat = (int)$row['peringkat'];
    $kel = (string)$row['kelompok'];
    $idKategori = (int)($row['id_kategori'] ?? 0);

    // id_kategori diasumsikan 1..12 sesuai urutan $categories
    if ($idKategori >= 1 && $idKategori <= count($categories)) {
        $cat = $categories[$idKategori - 1];

        if (isset($rankingData[$cat][$kel])) {
            $rankingData[$cat][$kel] += $peringkat;
        }
    }
}

// Total per kategori
$categoryTotals = [];
foreach ($categories as $cat) {
    $categoryTotals[$cat] = array_sum($rankingData[$cat]);
}

/**
 * -----------------------------
 * Ranking: jumlah terkecil -> terbesar
 * Rank 1 = total terkecil
 * -----------------------------
 */
$sortedTotals = $categoryTotals;
asort($sortedTotals);

$rankings = [];
$rank = 1;
foreach ($sortedTotals as $cat => $total) {
    $rankings[$cat] = $rank++;
}

// Top 3 kategori terbaik (rank 1..3) = total terkecil
$top3 = array_slice(array_keys($sortedTotals), 0, 3);

/**
 * -----------------------------
 * Ambil deskripsi kategori dari tbl_kategori_minat
 * (mapping id_kategori 1..12 -> OUT..MED)
 * -----------------------------
 */
$idToCode = [];
foreach ($categories as $idx => $code) {
    $idToCode[$idx + 1] = $code;
}

$ketStmt = db()->prepare("
    SELECT id_kategori, deskripsi_kategori
    FROM tbl_kategori_minat
");
$ketStmt->execute();
$ketRows = $ketStmt->fetchAll(PDO::FETCH_ASSOC);

$kategoriKet = [];
foreach ($ketRows as $r) {
    $id = (int)$r['id_kategori'];
    if (isset($idToCode[$id])) {
        $code = $idToCode[$id];
        $kategoriKet[$code] = (string)($r['deskripsi_kategori'] ?? '-');
    }
}

// fallback kalau ada yang kosong
foreach ($categories as $code) {
    if (!isset($kategoriKet[$code]) || trim((string)$kategoriKet[$code]) === '') {
        $kategoriKet[$code] = '-';
    }
}

/**
 * -----------------------------
 * Nomor tes: pendidikan-rmib-nomor (tanpa tanggal)
 * -----------------------------
 */
$pend = strtolower((string)($peserta['pendidikan'] ?? ''));
$pend = preg_replace('/[^a-z0-9]+/', '-', $pend);
$pend = trim($pend, '-');

$nomorTesAsli = (string)($peserta['nomor_test'] ?? '');

$nomorTesBersih = preg_replace('/.*-(\d+)$/', '$1', $nomorTesAsli);
if ($nomorTesBersih === $nomorTesAsli) {
    $parts = explode('-', $nomorTesAsli);
    $nomorTesBersih = (string) end($parts);
}

$nomorTesTampil = $pend . '-rmib-' . $nomorTesBersih;

$title = "Detail Hasil Tes";
$active = "hasil";
include __DIR__ . '/../../views/admin_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/admin_sidebar.php'; ?>

  <main class="flex-1 p-6">
    <div class="bg-white border rounded-2xl p-8">

      <h1 class="text-2xl font-semibold text-center mb-6">
        Hasil Tes Rothwell Miller Interest Blank (RMIB)
      </h1>

      <!-- Header identitas -->
      <div class="flex flex-col md:flex-row justify-between gap-8 text-lg">
        <div class="space-y-1">
          <div class="flex gap-4">
            <div class="w-40">Nama</div><div>:</div>
            <div class="font-medium"><?= e($peserta['nama'] ?? '-') ?></div>
          </div>
          <div class="flex gap-4">
            <div class="w-40">Tanggal Lahir</div><div>:</div>
            <div class="font-medium"><?= e($peserta['tgl_lahir'] ?? '-') ?></div>
          </div>
          <div class="flex gap-4">
            <div class="w-40">Jenis Kelamin</div><div>:</div>
            <div class="font-medium"><?= e($peserta['jenis_kelamin'] ?? '-') ?></div>
          </div>
        </div>

        <div class="space-y-1">
          <div class="flex gap-4">
            <div class="w-40">Nomor Tes</div><div>:</div>
            <div class="font-medium"><?= e($nomorTesTampil) ?></div>
          </div>
          <div class="flex gap-4">
            <div class="w-40">Tanggal Tes</div><div>:</div>
            <div class="font-medium"><?= e($sesi['tanggal_tes'] ?? '-') ?></div>
          </div>
        </div>
      </div>

      <hr class="my-6 border-slate-300">

      <!-- 3 Pekerjaan favorit -->
      <div class="text-lg mb-6">
        <div class="font-semibold mb-2">Tiga Pekerjaan yang paling Anda sukai</div>
        <ol class="list-decimal pl-6 space-y-1">
          <li><?= e($favorit[0]) ?></li>
          <li><?= e($favorit[1]) ?></li>
          <li><?= e($favorit[2]) ?></li>
        </ol>
      </div>

      <!-- Tabel kategori -->
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-slate-800">
          <thead>
            <tr class="border-b border-slate-800">
              <th class="p-2 border-r border-slate-800 text-left">Kategori</th>
              <?php foreach ($kelompok as $k): ?>
                <th class="p-2 border-r border-slate-800 text-center"><?= e($k) ?></th>
              <?php endforeach; ?>
              <th class="p-2 border-r border-slate-800 text-center">Jumlah</th>
              <th class="p-2 text-center">Rank</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr class="border-b border-slate-800">
                <td class="p-2 border-r border-slate-800 font-semibold"><?= e($cat) ?></td>

                <?php foreach ($kelompok as $k): ?>
                  <td class="p-2 border-r border-slate-800 text-center">
                    <?= e($rankingData[$cat][$k]) ?>
                  </td>
                <?php endforeach; ?>

                <td class="p-2 border-r border-slate-800 text-center font-semibold">
                  <?= e($categoryTotals[$cat]) ?>
                </td>

                <td class="p-2 text-center font-semibold">
                  <?= e($rankings[$cat]) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Hasil (Top 3) -->
      <div class="mt-8 text-lg">
        <div class="font-semibold mb-2">Hasil</div>

        <?php foreach ($top3 as $i => $cat): ?>
          <div class="mb-4">
            <div class="font-medium"><?= ($i + 1) ?>. Kategori <?= e($cat) ?></div>
            <div class="ml-6 text-slate-700">
              Keterangan: <?= e($kategoriKet[$cat] ?? '-') ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <a href="download_pdf.php?sesi_id=<?= e($sesiId) ?>"
        class="inline-flex items-center px-3 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
        Download PDF
      </a>

      <div class="mt-4 text-xs text-slate-500">
        Total keseluruhan skor: <strong><?= e(array_sum($categoryTotals)) ?></strong> (validasi: harus 702)
      </div>

    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>
