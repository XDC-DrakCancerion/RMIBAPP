<?php
require_once __DIR__ . '/../../app/peserta_guard.php';

$userId = (int)$_SESSION['user']['id_pengguna'];

$stmt = db()->prepare("SELECT id_peserta FROM tbl_peserta WHERE id_pengguna=?");
$stmt->execute([$userId]);
$id_peserta = (int)($stmt->fetchColumn() ?: 0);

$stmt = db()->prepare("SELECT COUNT(*) FROM tbl_sesi_rmib WHERE id_peserta=?");
$stmt->execute([$id_peserta]);
$tesCount = (int)$stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM tbl_sesi_rmib WHERE id_peserta=? AND status='selesai'");
$stmt->execute([$id_peserta]);
$hasilCount = (int)$stmt->fetchColumn();

$title = "Dashboard Peserta";
$active = "dashboard";
include __DIR__ . '/../../views/peserta_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/peserta_sidebar.php'; ?>

  <main class="flex-1 p-6">
    <div class="flex items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="flex items-center gap-2 text-2xl font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-6 h-6">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>
          <span>Dashboard</span>
        </h1>
        <p class="text-slate-500">Selamat datang, <?= e($_SESSION['user']['nama_lengkap']) ?>.</p>
      </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white border rounded-2xl p-5">
        <div class="text-sm text-slate-500">Sesi Tes</div>
        <div class="text-2xl font-semibold mt-1"><?= $tesCount ?></div>
      </div>

      <div class="bg-white border rounded-2xl p-5">
        <div class="text-sm text-slate-500">Riwayat Hasil</div>
        <div class="text-2xl font-semibold mt-1"><?= $hasilCount ?></div>
        <div class="text-xs text-slate-500 mt-2">Hasil: <?= $hasilCount ?></div>
      </div>
    </div>


    <div class="bg-white border rounded-2xl p-6">
      <h2 class="font-semibold mb-2">Info</h2>
      <p class="text-slate-600 text-justify">
        Rothwell Miller Interest Blank (RMIB) merupakan alat tes psikologi untuk mengetahui minat seseorang terhadap 12 kategori pekerjaan,
        membantu mengidentifikasi kecocokan minat dengan jalur karier atau Jurusan Perguruan Tinggi yang relevan. 
        Yang mana alat ini membadakan pekerjaan antara Laki-Laki dan Wanita. Adapun 12 pekerjaan yaitu kategori Outdoor, Mechanical, Computational, Scientific, Personal Contact, Aesthetic, Literary, Musical, Social Service, Clerical, Practical, Medical.</p>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>
