<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

if (!is_logged_in()) {
  redirect('../login.php');
}
