<?php
require_once __DIR__ . '/../../app/admin_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

/**
 * PERUBAHAN UTAMA:
 * - Hapus memakai cara "mode=delete" di halaman yang sama (seperti pekerjaan.php)
 * - Tidak pakai action="delete.php" lagi (biar tidak 404 Not Found)
 * - Hapus data yang relevan: tbl_jawaban_rmib (jawaban) + tbl_sesi_rmib (sesi)
 *   (kalau kamu mau reset sesi, tinggal ubah bagian DELETE sesi menjadi UPDATE status)
 */

$success = flash_get('success');
$error   = flash_get('error');

// ===== POST Handler (hapus di halaman yang sama) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('error', 'CSRF tidak valid.');
    redirect("hasil.php");
  }

  $mode = $_POST['mode'] ?? '';

  // HAPUS HASIL (jawaban + sesi)
  if ($mode === 'delete') {
    $sesiId = (int)($_POST['id_sesi'] ?? 0);
    if (!$sesiId) {
      flash_set('error', 'ID sesi tidak valid.');
      redirect("hasil.php");
    }

    db()->beginTransaction();
    try {
      // Hapus jawaban dulu (agar tidak kena FK kalau ada)
      db()->prepare("DELETE FROM tbl_jawaban_rmib WHERE id_sesi=?")->execute([$sesiId]);

      // Hapus sesi
      db()->prepare("DELETE FROM tbl_sesi_rmib WHERE id_sesi=?")->execute([$sesiId]);

      db()->commit();
      flash_set('success', 'Hasil tes berhasil dihapus.');
      redirect("hasil.php");
    } catch (Throwable $e) {
      db()->rollBack();
      flash_set('error', 'Gagal hapus: ' . $e->getMessage());
      redirect("hasil.php");
    }
  }

  redirect("hasil.php");
}

// ===== LIST DATA =====
$stmt = db()->prepare("
  SELECT
    s.id_sesi,
    s.tanggal_tes,
    s.status,
    p.id_peserta,
    p.nama,
    p.tgl_lahir,
    p.jenis_kelamin,
    p.pendidikan,
    p.nomor_test
  FROM tbl_sesi_rmib s
  JOIN tbl_peserta p ON p.id_peserta = s.id_peserta
  ORDER BY s.id_sesi DESC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function nomor_tes_tampil(array $peserta): string
{
  $pend = strtolower((string)($peserta['pendidikan'] ?? ''));
  $pend = preg_replace('/[^a-z0-9]+/', '-', $pend);
  $pend = trim($pend, '-');

  $nomorTesAsli = (string)($peserta['nomor_test'] ?? '');
  if ($nomorTesAsli === '') return $pend . '-rmib-';

  $nomorTesBersih = preg_replace('/.*-(\d+)$/', '$1', $nomorTesAsli);

  // fallback bila format tidak sesuai
  if ($nomorTesBersih === $nomorTesAsli) {
    $parts = explode('-', $nomorTesAsli);
    $nomorTesBersih = (string) end($parts);
  }

  return $pend . '-rmib-' . $nomorTesBersih;
}

$title  = "Hasil Tes";
$active = "hasil";
include __DIR__ . '/../../views/admin_layout_top.php';
?>

<div class="flex">
  <?php include __DIR__ . '/../../views/admin_sidebar.php'; ?>

  <main class="flex-1 p-6">
    <div class="flex items-center justify-between mb-4">
      <h1 class="flex items-center gap-2 text-2xl font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-6 h-6">
            <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h15ZM1.5 10.146V6a3 3 0 0 1 3-3h5.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 0 1 3 3v1.146A4.483 4.483 0 0 0 19.5 9h-15a4.483 4.483 0 0 0-3 1.146Z" />
         </svg>
          <span>Hasil Tes</span>
        </h1>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="bg-white border rounded-2xl p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-3 pr-4">Nama</th>
              <th class="py-3 pr-4">Tanggal Lahir</th>
              <th class="py-3 pr-4">Jenis Kelamin</th>
              <th class="py-3 pr-4">Nomor Tes</th>
              <th class="py-3 pr-4">Tanggal Tes</th>
              <th class="py-3 pr-4">Status</th>
              <th class="py-3 pr-4">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="7" class="py-4 text-slate-500">
                  Belum ada hasil tes.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <?php $nomorTesTampil = nomor_tes_tampil($r); ?>

                <tr class="border-b last:border-b-0">
                  <td class="py-3 pr-4 font-medium"><?= e($r['nama'] ?? '-') ?></td>
                  <td class="py-3 pr-4"><?= e($r['tgl_lahir'] ?? '-') ?></td>
                  <td class="py-3 pr-4"><?= e($r['jenis_kelamin'] ?? '-') ?></td>
                  <td class="py-3 pr-4"><?= e($nomorTesTampil) ?></td>
                  <td class="py-3 pr-4"><?= e($r['tanggal_tes'] ?? '-') ?></td>

                  <td class="py-3 pr-4">
                    <span class="px-3 py-1 rounded-full text-xs
                      <?= ($r['status'] ?? '') === 'selesai'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-yellow-100 text-yellow-800' ?>">
                      <?= e($r['status'] ?? '-') ?>
                    </span>
                  </td>

                  <td class="py-3 pr-4">
                    <div class="flex items-center gap-2">
                      <a href="view_hasil.php?sesi_id=<?= (int)$r['id_sesi'] ?>"
                         class="px-3 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                        View
                      </a>

                      <!-- HAPUS: cara seperti pekerjaan.php (POST ke halaman ini sendiri) -->
                      <form method="post" onsubmit="return confirm('Yakin hapus hasil ini?');" class="inline">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="mode" value="delete">
                        <input type="hidden" name="id_sesi" value="<?= (int)$r['id_sesi'] ?>">
                        <button type="submit"
                          class="px-3 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>

        </table>
      </div>

      <!-- OPSIONAL: Kalau kamu maunya "reset" bukan hapus sesi, ganti DELETE tbl_sesi_rmib dengan UPDATE:
           UPDATE tbl_sesi_rmib SET status='draft', waktu_selesai=NULL WHERE id_sesi=?
      -->
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/admin_layout_bottom.php'; ?>
