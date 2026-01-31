<?php require_once __DIR__ . '/../app/config.php'; require_once __DIR__ . '/../app/helpers.php'; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

<?php
$namaUser  = $_SESSION['user']['nama_lengkap'] ?? 'Peserta';
$roleLabel = 'Peserta';
?>

<header class="sticky top-0 z-40 bg-white border-b">
  <div class="h-16 px-4 flex items-center justify-between">
    <div class="flex items-center gap-3 min-w-[220px]">

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

        <svg class="h-4 w-4 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
        </svg>
      </button>

      <div id="profileMenu"
        class="hidden absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-sm overflow-hidden">
        <a href="../logout.php" class="block px-4 py-3 text-sm hover:bg-slate-50 text-red-600 font-semibold">
          Logout
        </a>
      </div>
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
