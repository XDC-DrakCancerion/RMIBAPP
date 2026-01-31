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

try {
  $kelompokRows = db()->query("SELECT id_kelompok, kode FROM tbl_kelompok_rmib ORDER BY id_kelompok ASC")->fetchAll();
  if (!$kelompokRows) {
    throw new RuntimeException('Data kelompok tidak ditemukan.');
  }

  $groups = [];
  foreach ($kelompokRows as $row) {
    $code = strtoupper((string)$row['kode']);
    $idKel = (int)$row['id_kelompok'];

    foreach (['L','P'] as $jk) {
      $stmt = db()->prepare("
        SELECT id_pekerjaan, nama_pekerjaan, id_kategori
        FROM tbl_pekerjaan
        WHERE id_kelompok=? AND (jenis_kelamin=? OR jenis_kelamin='U')
        ORDER BY FIELD(jenis_kelamin, ?, 'U'), id_pekerjaan ASC
        LIMIT 12
      ");
      $stmt->execute([$idKel, $jk, $jk]);
      $jobs = $stmt->fetchAll();

      if (count($jobs) < 12) {
        throw new RuntimeException("Kelompok {$code} JK {$jk} belum lengkap (min 12)." );
      }
      $groups[$code][$jk] = $jobs;
    }
  }

  $categories = db()->query("SELECT id_kategori, kd_kategori, nama_kategori, deskripsi_kategori FROM tbl_kategori_minat ORDER BY id_kategori ASC")
    ->fetchAll();

  echo json_encode([
    'ok' => true,
    'generated_at' => date('c'),
    'data' => [
      'categories' => $categories,
      'groups' => $groups,
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
