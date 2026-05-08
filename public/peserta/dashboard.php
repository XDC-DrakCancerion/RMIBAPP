<?php
require_once __DIR__ . '/../../app/peserta_guard.php';

$userId = (int)$_SESSION['user']['id_pengguna'];

$stmt = db()->prepare("SELECT id_peserta FROM tbl_peserta WHERE id_pengguna=?");
$stmt->execute([$userId]);
$id_peserta = (int)($stmt->fetchColumn() ?: 0);

$totalSesi  = (int)db()->query("SELECT COUNT(*) FROM tbl_sesi_rmib")->fetchColumn();

// Hitung sesi selesai dari status
$sesiSelesai = 0;
try {
  $sesiSelesai = (int)db()->query("
    SELECT COUNT(*)
    FROM tbl_sesi_rmib
    WHERE status='selesai'
  ")->fetchColumn();
} catch (Throwable $e) {
  $sesiSelesai = 0;
}

$title = "Dashboard Peserta";
$active = "dashboard";
include __DIR__ . '/../../views/peserta_layout_top.php';
?>

<div class="flex text-black">
  <?php include __DIR__ . '/../../views/peserta_sidebar.php'; ?>

  <main class="flex-1 p-6 text-black">
    <div class="flex items-center justify-between gap-4 mb-6 text-black">
      <div class="text-black">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-white">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-6 h-6">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>
          <span class="text-white">Dashboard</span>
        </h1>

        <p class="text-white">
          Selamat datang di Sistem Rekomendasi Pemilihan Jurusan Perguruan Tinggi Menggunakan Tes RMIB.
        </p>
      </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white border rounded-2xl p-5 text-black">
        <div class="text-sm text-black">Sesi Tes</div>
        <div class="text-2xl font-semibold mt-1 text-black"><?= $totalSesi ?></div>
      </div>

      <div class="bg-white border rounded-2xl p-5 text-black">
        <div class="text-sm text-black">Riwayat Hasil</div>
        <div class="text-2xl font-semibold mt-1 text-black"><?= $sesiSelesai ?></div>
        <div class="text-xs text-black mt-2">Hasil: <?= $sesiSelesai ?></div>
      </div>
    </div>

    <div class="bg-white border rounded-2xl p-6 text-black">
      <h2 class="font-semibold mb-2 text-black">Info</h2>
      <p class="text-black text-justify">
        Rothwell Miller Interest Blank (RMIB) merupakan alat tes psikologi untuk mengetahui minat seseorang terhadap 12 kategori pekerjaan,
        membantu mengidentifikasi kecocokan minat dengan jalur karier atau jurusan perguruan tinggi yang relevan.
        Alat ini membedakan pekerjaan antara laki-laki dan wanita. Adapun 12 kategori pekerjaan tersebut yaitu Outdoor, Mechanical,
        Computational, Scientific, Personal Contact, Aesthetic, Literary, Musical, Social Service, Clerical, Practical, dan Medical.
      </p>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>