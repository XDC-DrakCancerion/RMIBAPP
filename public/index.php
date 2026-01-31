<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

if (!is_logged_in()) redirect('login.php');

$role = (int)($_SESSION['user']['role'] ?? 2);
if ($role === 1) redirect('admin/dashboard.php');
redirect('peserta/dashboard.php');
