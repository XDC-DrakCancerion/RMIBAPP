<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

/**
 * Catatan:
 * - Kamu sudah tidak pakai tbl_hasil_tes / tbl_hasil_kategori_rmib.
 * - "Selesai" diambil dari tbl_sesi_rmib.status='selesai'
 * - Riwayat hasil menampilkan 10 sesi terakhir yang statusnya selesai.
 */

$totalUsers = (int)db()->query("SELECT COUNT(*) FROM tbl_peserta")->fetchColumn();
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

// Ambil 10 riwayat sesi selesai terakhir
$recent = [];
try {
  $stmt = db()->prepare("
    SELECT
      s.id_sesi,
      s.created_at,
      s.waktu_selesai,
      p.nama AS nama_peserta,
      u.nama_lengkap AS nama_user
    FROM tbl_sesi_rmib s
    JOIN tbl_peserta p ON p.id_peserta = s.id_peserta
    JOIN tbl_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE s.status='selesai'
    ORDER BY s.id_sesi DESC
    LIMIT 10
  ");
  $stmt->execute();
  $recent = $stmt->fetchAll();
} catch (Throwable $e) {
  $recent = [];
}

$title  = "Admin Dashboard";
$active = "dashboard";
include __DIR__ . '/../../views/admin_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/admin_sidebar.php'; ?>

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
        <div class="text-sm text-slate-500">Total Peserta</div>
        <div class="text-2xl font-semibold mt-1"><?= $totalUsers ?></div>
      </div>

      <div class="bg-white border rounded-2xl p-5">
        <div class="text-sm text-slate-500">Sesi Tes</div>
        <div class="text-2xl font-semibold mt-1"><?= $totalSesi ?></div>
      </div>

      <div class="bg-white border rounded-2xl p-5">
        <div class="text-sm text-slate-500">Riwayat Hasil</div>
        <div class="text-2xl font-semibold mt-1"><?= $sesiSelesai ?></div>
        <div class="text-xs text-slate-500 mt-2">Hasil: <?= $sesiSelesai ?></div>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <!-- MENU -->
      <div class="bg-white border rounded-2xl p-6">
        <div class="font-semibold">Manajemen RMIB</div>
        <p class="text-slate-600 mt-2 text-sm">Kelola kategori dan pekerjaan RMIB.</p>

        <div class="mt-4 flex flex-wrap gap-2">
          <a class="inline-flex px-5 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95"
             href="kategori.php">Kelola Kategori</a>

          <a class="inline-flex px-5 py-3 rounded-xl border bg-white font-semibold hover:bg-slate-50"
             href="pekerjaan.php">Kelola Pekerjaan</a>

          <a class="inline-flex px-5 py-3 rounded-xl border bg-white font-semibold hover:bg-slate-50"
             href="pengguna.php">Kelola Peserta</a>
        </div>
      </div>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>
