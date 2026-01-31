<?php
require_once __DIR__ . '/../../app/peserta_guard.php';

$userId = (int)$_SESSION['user']['id_pengguna'];
$stmt = db()->prepare("SELECT id_peserta FROM tbl_peserta WHERE id_pengguna=?");
$stmt->execute([$userId]);
$id_peserta = (int)($stmt->fetchColumn() ?: 0);

db()->prepare("INSERT INTO tbl_sesi_rmib (id_peserta, tanggal_tes, waktu_mulai, status) VALUES (?,?,?,?)")
   ->execute([$id_peserta, date('Y-m-d'), date('H:i:s'), 'draft']);

$id_sesi = (int)db()->lastInsertId();
header("Location: tes_wizard.php?sesi={$id_sesi}&k=A");
exit;
