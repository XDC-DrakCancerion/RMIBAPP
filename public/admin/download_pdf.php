<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$sesiId = (int)($_GET['sesi_id'] ?? 0);
if ($sesiId <= 0) {
  flash_set('error', 'Sesi tidak valid.');
  redirect('hasil.php');
}

// === Ambil data sesi ===
$stmt = db()->prepare("SELECT * FROM tbl_sesi_rmib WHERE id_sesi=? LIMIT 1");
$stmt->execute([$sesiId]);
$sesi = $stmt->fetch();

if (!$sesi) {
  flash_set('error', 'Sesi tidak ditemukan.');
  redirect('hasil.php');
}

// === Ambil data peserta ===
$stmt = db()->prepare("SELECT * FROM tbl_peserta WHERE id_peserta=? LIMIT 1");
$stmt->execute([(int)$sesi['id_peserta']]);
$peserta = $stmt->fetch();

if (!$peserta) {
  flash_set('error', 'Peserta tidak ditemukan.');
  redirect('hasil.php');
}

// === Favorit (fav1..fav3) ===
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

// === Jawaban RMIB ===
$jawabanStmt = db()->prepare("
  SELECT j.peringkat, j.kelompok, p.id_kategori
  FROM tbl_jawaban_rmib j
  JOIN tbl_pekerjaan p ON p.id_pekerjaan = j.id_pekerjaan
  WHERE j.id_sesi = ?
  ORDER BY j.kelompok ASC
");
$jawabanStmt->execute([$sesiId]);
$jawaban = $jawabanStmt->fetchAll(PDO::FETCH_ASSOC);

$categories = ['Outdoor (OUT)', 'Mechanical (ME)', 'Computational (COMP)', 'Scientific (SCI)', 'Personal Contact (PERS)',
               'Aesthetic (AESTH)', 'Literary (LIT)', 'Musical (MUS)', 'Social Service (S.S)', 'Clerical (CLER)', 'Practical (PRAC)', 'Medical (MED)'];
$kelompok   = ['A','B','C','D','E','F','G','H','I'];

$rankingData = [];
foreach ($categories as $cat) {
  foreach ($kelompok as $k) {
    $rankingData[$cat][$k] = 0;
  }
}

foreach ($jawaban as $row) {
  $peringkat = (int)$row['peringkat'];
  $kel = (string)$row['kelompok'];
  $idKategori = (int)($row['id_kategori'] ?? 0);

  if ($idKategori >= 1 && $idKategori <= count($categories)) {
    $cat = $categories[$idKategori - 1];
    if (isset($rankingData[$cat][$kel])) {
      $rankingData[$cat][$kel] += $peringkat;
    }
  }
}

$categoryTotals = [];
foreach ($categories as $cat) {
  $categoryTotals[$cat] = array_sum($rankingData[$cat]);
}

$sortedTotals = $categoryTotals;
asort($sortedTotals); // rank kecil -> besar

$rankings = [];
$rank = 1;
foreach ($sortedTotals as $cat => $total) {
  $rankings[$cat] = $rank++;
}
$top3 = array_slice(array_keys($sortedTotals), 0, 3);

// === Keterangan kategori dari tbl_kategori_minat ===
$idToCode = [];
foreach ($categories as $idx => $code) $idToCode[$idx+1] = $code;

$ketStmt = db()->prepare("SELECT id_kategori, deskripsi_kategori FROM tbl_kategori_minat");
$ketStmt->execute();
$ketRows = $ketStmt->fetchAll(PDO::FETCH_ASSOC);

$kategoriKet = [];
foreach ($ketRows as $r) {
  $id = (int)$r['id_kategori'];
  if (isset($idToCode[$id])) {
    $kategoriKet[$idToCode[$id]] = (string)($r['deskripsi_kategori'] ?? '-');
  }
}
foreach ($categories as $code) {
  if (!isset($kategoriKet[$code]) || trim((string)$kategoriKet[$code]) === '') $kategoriKet[$code] = '-';
}

// === Nomor tes tampil ===
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

$html = '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111;}
  .title{font-size:16px; font-weight:bold; text-align:center; margin-bottom:14px;}
  .row{margin:2px 0;}
  table{border-collapse:collapse; width:100%; margin-top:10px;}
  th,td{border:1px solid #111; padding:6px; text-align:center;}
  th:first-child, td:first-child{text-align:left;}
  .section{margin-top:14px;}
</style>
</head>
<body>
  <div class="title">Hasil Tes Rothwell Miller Interest Blank (RMIB)</div>

  <table style="width:100%; border-collapse:collapse; border:none; margin-top:10px; margin-bottom:12px;">
    <tr style="border:none;">
      <td style="width:55%; vertical-align:top; border:none;">
        <table style="width:100%; border-collapse:collapse; border:none;">
          <tr style="border:none;">
            <td style="width:120px; padding:4px 0; border:none;">Nama</td>
            <td style="width:10px; padding:4px 0; border:none;">:</td>
            <td style="padding:4px 0; font-weight:600; border:none;">'.htmlspecialchars($peserta['nama'] ?? '-').'</td>
          </tr>
          <tr style="border:none;">
            <td style="padding:4px 0; border:none;">Tanggal Lahir</td>
            <td style="padding:4px 0; border:none;">:</td>
            <td style="padding:4px 0; font-weight:600; border:none;">'.htmlspecialchars($peserta['tgl_lahir'] ?? '-').'</td>
          </tr>
          <tr style="border:none;">
            <td style="padding:4px 0; border:none;">Jenis Kelamin</td>
            <td style="padding:4px 0; border:none;">:</td>
            <td style="padding:4px 0; font-weight:600; border:none;">'.htmlspecialchars($peserta['jenis_kelamin'] ?? '-').'</td>
          </tr>
        </table>
      </td>

      <td style="width:45%; vertical-align:top; border:none;">
        <table style="width:100%; border-collapse:collapse; border:none;">
          <tr style="border:none;">
            <td style="width:120px; padding:4px 0; border:none;">Nomor Tes</td>
            <td style="width:10px; padding:4px 0; border:none;">:</td>
            <td style="padding:4px 0; font-weight:600; border:none;">'.htmlspecialchars($nomorTesTampil).'</td>
          </tr>
          <tr style="border:none;">
            <td style="padding:4px 0; border:none;">Tanggal Tes</td>
            <td style="padding:4px 0; border:none;">:</td>
            <td style="padding:4px 0; font-weight:600; border:none;">'.htmlspecialchars($sesi['tanggal_tes'] ?? '-').'</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <hr style="border:none; border-top:1px solid #cbd5e1; margin:10px 0 14px;">

  <div class="section"><b>Tiga Pekerjaan yang paling Anda sukai</b>
    <ol>
      <li>'.htmlspecialchars($favorit[0]).'</li>
      <li>'.htmlspecialchars($favorit[1]).'</li>
      <li>'.htmlspecialchars($favorit[2]).'</li>
    </ol>
  </div>

  <table>
    <thead>
      <tr>
        <th>Kategori</th>';

foreach ($kelompok as $k) $html .= '<th>'.$k.'</th>';
$html .= '   <th>Jumlah</th><th>Rank</th>
      </tr>
    </thead>
    <tbody>';

foreach ($categories as $cat) {
  $html .= '<tr>';
  $html .= '<td><b>'.htmlspecialchars($cat).'</b></td>';
  foreach ($kelompok as $k) $html .= '<td>'.(int)$rankingData[$cat][$k].'</td>';
  $html .= '<td><b>'.(int)$categoryTotals[$cat].'</b></td>';
  $html .= '<td><b>'.(int)$rankings[$cat].'</b></td>';
  $html .= '</tr>';
}

$html .= '</tbody></table>

  <div class="section"><b>Hasil Rekomendasi:</b>';

foreach ($top3 as $i => $cat) {
  $html .= '<div style="margin-top:6px;"><b>'.($i+1).'. Kategori '.$cat.'</b><br>';
  $html .= 'Keterangan: '.htmlspecialchars($kategoriKet[$cat] ?? '-').'</div>';
}

$html .= '</div>
</body>
</html>';

// =======================
// Generate PDF
// =======================
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'hasil-rmib-' . preg_replace('/[^a-z0-9\-]+/i', '-', $nomorTesTampil) . '.pdf';
$dompdf->stream($filename, ["Attachment" => true]); // true = download

exit;
