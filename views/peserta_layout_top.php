<?php 
require_once __DIR__ . '/../app/config.php'; 
require_once __DIR__ . '/../app/helpers.php'; 
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0f172a" />
  <link rel="manifest" href="/manifest.webmanifest" />
  <link rel="apple-touch-icon" href="/icons/icon-192.png" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-slate-100">

<?php
$namaUser  = $_SESSION['user']['nama_lengkap'] ?? 'Peserta';
$roleLabel = 'Peserta';
?>

<header class="sticky top-0 z-50 bg-slate-950/95 backdrop-blur border-b border-slate-800">
  <div class="h-16 px-4 sm:px-6 flex items-center justify-between gap-3">

    <div class="flex items-center gap-3">
      <button id="btnSidebar" type="button"
        class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      <div class="text-base sm:text-lg font-semibold text-white whitespace-nowrap">
        Metode Tes RMIB
      </div>
    </div>

    <div class="hidden lg:block text-slate-300 font-medium text-sm">
      Sistem Rekomendasi Pemilihan Jurusan
    </div>

    <div class="relative">
      <button id="btnProfile" type="button"
        class="flex items-center gap-2 px-2 sm:px-3 py-2 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800">
        <div class="h-9 w-9 rounded-full bg-slate-800 flex items-center justify-center">
          <span class="text-slate-100 font-semibold">
            <?= strtoupper(mb_substr($namaUser,0,1)) ?>
          </span>
        </div>

        <div class="text-left leading-tight hidden sm:block">
          <div class="text-sm font-semibold text-white"><?= e($roleLabel) ?></div>
          <div class="text-xs text-slate-400 max-w-32 truncate"><?= e($namaUser) ?></div>
        </div>
      </button>
    </div>

  </div>
</header>