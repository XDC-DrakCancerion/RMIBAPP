<?php
require_once __DIR__ . '/../../app/peserta_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$userId = (int)($_SESSION['user']['id_pengguna'] ?? 0);
$sesi   = (int)($_GET['sesi'] ?? 0);

if (!$userId) die("User tidak valid.");
if (!$sesi) die("Sesi tidak valid.");

$stmt = db()->prepare("SELECT id_peserta FROM tbl_peserta WHERE id_pengguna=? LIMIT 1");
$stmt->execute([$userId]);
$idPeserta = (int)($stmt->fetchColumn() ?: 0);
if (!$idPeserta) die("Peserta tidak ditemukan.");

$stmt = db()->prepare("SELECT id_sesi FROM tbl_sesi_rmib WHERE id_sesi=? AND id_peserta=? LIMIT 1");
$stmt->execute([$sesi, $idPeserta]);
$idSesiValid = (int)($stmt->fetchColumn() ?: 0);
if (!$idSesiValid) die("Sesi tidak ditemukan.");

db()->beginTransaction();
try {
  // (Opsional tapi disarankan) Pastikan jawaban memang ada sebelum diselesaikan
  $stmt = db()->prepare("SELECT COUNT(*) FROM tbl_jawaban_rmib WHERE id_sesi=?");
  $stmt->execute([$sesi]);
  $cntJawaban = (int)$stmt->fetchColumn();

  if ($cntJawaban <= 0) {
    throw new RuntimeException("Jawaban belum ada, sesi tidak bisa difinalisasi.");
  }

  // Finalisasi sesi: cukup update status (tanpa tabel hasil_kategori_rmib)
  db()->prepare("UPDATE tbl_sesi_rmib SET status='selesai', waktu_selesai=? WHERE id_sesi=?")
     ->execute([date('H:i:s'), $sesi]);

  db()->commit();
  redirect("hasil.php");
} catch (Throwable $e) {
  db()->rollBack();
  die("Finalize gagal: " . $e->getMessage());
}
