<?php
require_once __DIR__ . '/../../app/peserta_guard.php';

$rows = db()->query("SELECT * FROM tbl_kategori_minat ORDER BY id_kategori ASC")->fetchAll();

$title = "Kategori RMIB";
$active = "kategori";
include __DIR__ . '/../../views/peserta_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/peserta_sidebar.php'; ?>

  <main class="flex-1 p-6">
   <h1 class="flex items-center gap-2 text-2xl font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-6 h-6">
            <path d="M11.25 4.533A9.707 9.707 0 0 0 6 3a9.735 9.735 0 0 0-3.25.555.75.75 0 0 0-.5.707v14.25a.75.75 0 0 0 1 .707A8.237 8.237 0 0 1 6 18.75c1.995 0 3.823.707 5.25 1.886V4.533ZM12.75 20.636A8.214 8.214 0 0 1 18 18.75c.966 0 1.89.166 2.75.47a.75.75 0 0 0 1-.708V4.262a.75.75 0 0 0-.5-.707A9.735 9.735 0 0 0 18 3a9.707 9.707 0 0 0-5.25 1.533v16.103Z" />
          </svg>
          <span>Kategori RMIB</span>
        </h1>
    <div class="bg-white border rounded-2xl p-6 space-y-4">
      <?php foreach ($rows as $r): ?>
        <div class="p-4 rounded-2xl border hover:bg-slate-50">
          <div class="font-semibold">
            <?= (int)$r['id_kategori'] ?>. <?= e($r['nama_kategori']) ?>
            <span class="text-slate-400">(<?= e($r['kd_kategori']) ?>)</span>
          </div>
          <div class="text-slate-600 mt-1"><?= e($r['deskripsi_kategori'] ?? '-') ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>
