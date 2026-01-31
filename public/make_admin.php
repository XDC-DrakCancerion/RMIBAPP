<?php
require_once __DIR__ . '/../app/config.php';

$username = 'admin';
$pass = 'admin123';
$nama = 'Administrator';

$hash = password_hash($pass, PASSWORD_BCRYPT);

$stmt = db()->prepare("INSERT INTO tbl_pengguna (username,password,nama_lengkap,role) VALUES (?,?,?,1)");
$stmt->execute([$username, $hash, $nama]);

echo "Admin dibuat. Username: admin | Password: admin123";
