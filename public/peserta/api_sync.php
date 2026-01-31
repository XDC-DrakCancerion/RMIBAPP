<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$role = (int)($_SESSION['user']['role'] ?? 0);
if (!is_logged_in() || $role !== 2) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Payload tidak valid.']);
  exit;
}

$userId = (int)($_SESSION['user']['id_pengguna'] ?? 0);
$idPeserta = (int)($_SESSION['user']['id_peserta'] ?? 0);
if (!$idPeserta) {
  $st = db()->prepare("SELECT id_peserta FROM tbl_peserta WHERE id_pengguna=? LIMIT 1");
  $st->execute([$userId]);
  $idPeserta = (int)($st->fetchColumn() ?: 0);
}
if (!$idPeserta) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Peserta tidak ditemukan.']);
  exit;
}

$answers = $payload['answers'] ?? null;
if (!is_array($answers)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Jawaban tidak valid.']);
  exit;
}

$groups = ['A','B','C','D','E','F','G','H','I'];
$jobIds = [];
foreach ($groups as $g) {
  if (!isset($answers[$g]) || !is_array($answers[$g]) || count($answers[$g]) < 12) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => "Jawaban kelompok {$g} belum lengkap."]);
    exit;
  }
  foreach ($answers[$g] as $row) {
    $pid = (int)($row['id_pekerjaan'] ?? 0);
    $rank = (int)($row['peringkat'] ?? 0);
    if ($pid <= 0 || $rank < 1 || $rank > 12) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => "Data jawaban tidak valid pada kelompok {$g}."]);
      exit;
    }
    $jobIds[] = $pid;
  }
}

$jobIds = array_values(array_unique($jobIds));
if (!$jobIds) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Jawaban kosong.']);
  exit;
}

// Ambil mapping kelompok
$kelompokMap = [];
$kelRows = db()->query("SELECT id_kelompok, kode FROM tbl_kelompok_rmib")->fetchAll();
foreach ($kelRows as $r) {
  $kelompokMap[strtoupper((string)$r['kode'])] = (int)$r['id_kelompok'];
}

// Validasi job id -> kelompok
$placeholders = implode(',', array_fill(0, count($jobIds), '?'));
$st = db()->prepare("SELECT id_pekerjaan, id_kelompok FROM tbl_pekerjaan WHERE id_pekerjaan IN ({$placeholders})");
$st->execute($jobIds);
$jobRows = $st->fetchAll();
$jobMap = [];
foreach ($jobRows as $r) {
  $jobMap[(int)$r['id_pekerjaan']] = (int)$r['id_kelompok'];
}

foreach ($groups as $g) {
  $kelId = $kelompokMap[$g] ?? 0;
  if (!$kelId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => "Kelompok {$g} tidak valid."]);
    exit;
  }
  foreach ($answers[$g] as $row) {
    $pid = (int)$row['id_pekerjaan'];
    if (!isset($jobMap[$pid]) || $jobMap[$pid] !== $kelId) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => "Job tidak sesuai kelompok {$g}."]);
      exit;
    }
  }
}

function dt_or_now(?string $s): DateTime {
  try {
    if ($s) return new DateTime($s);
  } catch (Throwable $e) {
    // ignore
  }
  return new DateTime();
}

$startDt = dt_or_now($payload['client_started_at'] ?? null);
$finishDt = dt_or_now($payload['client_finished_at'] ?? null);

$tanggal = $startDt->format('Y-m-d');
$waktuMulai = $startDt->format('H:i:s');
$waktuSelesai = $finishDt->format('H:i:s');

$fav = $payload['fav'] ?? [];
$fav1 = mb_substr(trim((string)($fav['fav1'] ?? '')), 0, 80);
$fav2 = mb_substr(trim((string)($fav['fav2'] ?? '')), 0, 80);
$fav3 = mb_substr(trim((string)($fav['fav3'] ?? '')), 0, 80);

try {
  db()->beginTransaction();

  // Simpan sesi (selesai)
  $stmt = db()->prepare("INSERT INTO tbl_sesi_rmib (id_peserta, tanggal_tes, waktu_mulai, waktu_selesai, status)
                         VALUES (?,?,?,?,?)");
  $stmt->execute([$idPeserta, $tanggal, $waktuMulai, $waktuSelesai, 'selesai']);
  $idSesi = (int)db()->lastInsertId();

  // Simpan jawaban
  $ins = db()->prepare("INSERT INTO tbl_jawaban_rmib (id_sesi, id_pekerjaan, peringkat, kelompok) VALUES (?,?,?,?)");
  foreach ($groups as $g) {
    foreach ($answers[$g] as $row) {
      $ins->execute([$idSesi, (int)$row['id_pekerjaan'], (int)$row['peringkat'], $g]);
    }
  }

  // Simpan favorit (upsert)
  db()->prepare("
      INSERT INTO tbl_pekerjaan_favorit (id_peserta, fav1, fav2, fav3)
      VALUES (?,?,?,?)
      ON DUPLICATE KEY UPDATE
        fav1=VALUES(fav1),
        fav2=VALUES(fav2),
        fav3=VALUES(fav3),
        updated_at=NOW()
    ")->execute([$idPeserta, $fav1 ?: null, $fav2 ?: null, $fav3 ?: null]);

  db()->commit();

  echo json_encode(['ok' => true, 'id_sesi' => $idSesi]);
} catch (Throwable $e) {
  db()->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
}
