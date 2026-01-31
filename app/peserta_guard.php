<?php
require_once __DIR__ . '/auth_guard.php';

$role = (int)($_SESSION['user']['role'] ?? 0);

if ($role !== 2) {
  // cegah redirect loop
  session_destroy();
  redirect('../login.php');
}
