<?php require_once __DIR__ . '/../app/config.php'; require_once __DIR__ . '/../app/helpers.php'; ?>
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
<body class="bg-slate-50 text-slate-800">

<?php
$namaUser  = $_SESSION['user']['nama_lengkap'] ?? 'Admin';
$roleLabel = 'Admin';
?>

<header class="sticky top-0 z-40 bg-white border-b">
  <div class="h-16 px-4 flex items-center justify-between">
    <div class="flex items-center gap-3 min-w-[240px]">

      <div class="text-lg font-semibold text-slate-900">
        Metode Tes RMIB
      </div>
    </div>

    <div class="hidden md:block text-slate-800 font-medium">
      Sistem Rekomendasi Pemilihan Jurusan
    </div>

    <div class="relative">
      <button id="btnProfile" type="button"
        class="flex items-center gap-2 px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">
        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center">
          <span class="text-slate-700 font-semibold"><?= strtoupper(mb_substr($namaUser,0,1)) ?></span>
        </div>

        <div class="text-left leading-tight hidden sm:block">
          <div class="text-sm font-semibold text-slate-900"><?= e($roleLabel) ?></div>
          <div class="text-xs text-slate-500"><?= e($namaUser) ?></div>
        </div>
      </button>
    </div>
  </div>
</header>

<script>
(function(){
  const btn = document.getElementById('btnSidebar');
  const sidebar = document.getElementById('sidebar');
  if(btn && sidebar){
    btn.addEventListener('click', () => {
      sidebar.classList.toggle('hidden');
    });
  }

  const btnP = document.getElementById('btnProfile');
  const menu = document.getElementById('profileMenu');
  if(btnP && menu){
    btnP.addEventListener('click', (e) => {
      e.stopPropagation();
      menu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => {
      if(!menu.classList.contains('hidden')) menu.classList.add('hidden');
    });
  }
})();
</script>

<script>
(function(){
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }
})();
</script>
