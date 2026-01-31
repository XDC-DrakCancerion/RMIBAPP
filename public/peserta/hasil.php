<?php
require_once __DIR__ . '/../../app/peserta_guard.php';

$userId = (int)$_SESSION['user']['id_pengguna'];

$stmt = db()->prepare("SELECT * FROM tbl_peserta WHERE id_pengguna=? LIMIT 1");
$stmt->execute([$userId]);
$peserta = $stmt->fetch();

if (!$peserta) {
  die('Data peserta tidak ditemukan.');
}

$stmt = db()->prepare("
  SELECT * 
  FROM tbl_sesi_rmib 
  WHERE id_peserta=? 
  ORDER BY id_sesi DESC
");
$stmt->execute([(int)$peserta['id_peserta']]);
$sesi = $stmt->fetchAll();

$namaSekolah = strtolower($peserta['pendidikan']);
$namaSekolah = preg_replace('/[^a-z0-9]+/', '-', $namaSekolah);
$namaSekolah = trim($namaSekolah, '-');

$nomorTesAsli = (string)$peserta['nomor_test'];

$nomorTesBersih = preg_replace('/.*-(\d+)$/', '$1', $nomorTesAsli);

if ($nomorTesBersih === $nomorTesAsli) {
  $parts = explode('-', $nomorTesAsli);
  $nomorTesBersih = end($parts);
}

$nomorTesTampil = $namaSekolah . '-rmib-' . $nomorTesBersih;

$title = "Hasil Tes";
$active = "hasil";
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
            <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h15ZM1.5 10.146V6a3 3 0 0 1 3-3h5.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 0 1 3 3v1.146A4.483 4.483 0 0 0 19.5 9h-15a4.483 4.483 0 0 0-3 1.146Z" />
         </svg>
          <span>Hasil Tes</span>
        </h1>

    <div class="bg-white border rounded-2xl p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-3 pr-4">Nomor Tes</th>
              <th class="py-3 pr-4">Tanggal Tes</th>
              <th class="py-3 pr-4">Status</th>
              <th class="py-3 pr-4">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!$sesi): ?>
              <tr>
                <td colspan="4" class="py-4 text-slate-500">
                  Belum ada hasil tes.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($sesi as $row): ?>
                <tr class="border-b last:border-b-0">
                  <td class="py-3 pr-4 font-medium">
                    <?= e($nomorTesTampil) ?>
                  </td>
                  <td class="py-3 pr-4">
                    <?= e($row['tanggal_tes']) ?>
                  </td>
                  <td class="py-3 pr-4">
                    <span class="px-3 py-1 rounded-full text-xs
                      <?= $row['status'] === 'selesai'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-yellow-100 text-yellow-800' ?>">
                      <?= e($row['status']) ?>
                    </span>
                  </td>
                  <td class="py-3 pr-4">
                    <a href="view_hasil.php?sesi_id=<?= e($row['id_sesi']) ?>"
                       class="px-3 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                      View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>
