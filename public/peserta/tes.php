<?php
require_once __DIR__ . '/../../app/peserta_guard.php';
require_once __DIR__ . '/../../app/helpers.php';

$userId = (int)($_SESSION['user']['id_pengguna'] ?? 0);

// Ambil peserta
$stmt = db()->prepare("SELECT * FROM tbl_peserta WHERE id_pengguna=? LIMIT 1");
$stmt->execute([$userId]);
$peserta = $stmt->fetch();

if (!$peserta) {
  flash_set('error', 'Data peserta tidak ditemukan.');
  redirect('../logout.php');
}

$id_peserta = (int)$peserta['id_peserta'];

// Ambil sesi draft terakhir
$stmt = db()->prepare("SELECT id_sesi FROM tbl_sesi_rmib WHERE id_peserta=? AND status='draft' ORDER BY id_sesi DESC LIMIT 1");
$stmt->execute([$id_peserta]);
$draft = (int)($stmt->fetchColumn() ?: 0);

// Ambil data favorit (kalau sudah pernah diisi)
$stmt = db()->prepare("SELECT fav1,fav2,fav3 FROM tbl_pekerjaan_favorit WHERE id_peserta=? LIMIT 1");
$stmt->execute([$id_peserta]);
$fav = $stmt->fetch() ?: ['fav1'=>'', 'fav2'=>'', 'fav3'=>''];

$success = flash_get('success');
$error   = flash_get('error');

// Simpan favorit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('error', 'CSRF tidak valid.');
    redirect('tes.php');
  }

  $fav1 = trim($_POST['fav1'] ?? '');
  $fav2 = trim($_POST['fav2'] ?? '');
  $fav3 = trim($_POST['fav3'] ?? '');

  // Boleh kosong, tapi kalau diisi batasi panjang
  $fav1 = mb_substr($fav1, 0, 80);
  $fav2 = mb_substr($fav2, 0, 80);
  $fav3 = mb_substr($fav3, 0, 80);

  // Upsert per peserta (UNIQUE id_peserta)
  try {
    db()->prepare("
      INSERT INTO tbl_pekerjaan_favorit (id_peserta, fav1, fav2, fav3)
      VALUES (?,?,?,?)
      ON DUPLICATE KEY UPDATE
        fav1=VALUES(fav1),
        fav2=VALUES(fav2),
        fav3=VALUES(fav3),
        updated_at=NOW()
    ")->execute([$id_peserta, $fav1 ?: null, $fav2 ?: null, $fav3 ?: null]);

    flash_set('success', 'Pekerjaan favorit berhasil disimpan.');
    redirect('tes.php');
  } catch (Throwable $e) {
    flash_set('error', 'Gagal menyimpan pekerjaan favorit: ' . $e->getMessage());
    redirect('tes.php');
  }
}

$title = "Tes RMIB";
$active = "tes";
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
            <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z" clip-rule="evenodd" />
            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
         </svg>
          <span>Tes RMIB</span>
        </h1>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 border border-green-200"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="bg-white border rounded-2xl p-6">

      <!-- PETUNJUK (seperti gambar) -->
      <div class="border-2 border-blue-500 rounded-xl p-5">
        <div class="font-semibold mb-2">Petunjuk :</div>
        <div class="text-slate-700 leading-relaxed text-sm">
          <p style="text-align: justify; text-indent: 3ch;">
            Di bawah ini akan Anda temui daftar-daftar berbagai macam pekerjaan yang tersusun dalam beberapa kelompok.
            Setiap kelompok terdiri dari 12 macam pekerjaan. Setiap pekerjaan merupakan keahlian khusus yang memerlukan
            latihan atau pendidikan keahlian tersendiri. Mungkin hanya beberapa di antaranya yang Anda sukai.
            Di sini Anda diminta untuk memilih pekerjaan mana yang ingin Anda lakukan atau pekerjaan mana yang Anda sukai,
            terlepas dari besarnya upah atau gaji yang akan diterima. Juga terlepas dari apakah Anda berhasil atau tidak
            dalam mengerjakan pekerjaan tersebut.
          </p><br>
          <p style="text-align: justify; text-indent: 3ch;">
            Tugas Anda adalah mencantumkan nomor atau angka pada setiap pekerjaan dalam kelompok-kelompok yang tersedia.
            Berikanlah nomor satu untuk pekerjaan yang paling Anda sukai di antara kedua belas pekerjaan pada setiap kelompok,
            lalu nomor dua, tiga, dan seterusnya berurutan berdasarkan kadar kesukaan atau minat Anda, dan nomor dua belas
            untuk pekerjaan yang paling tidak Anda sukai.
          </p><br>
          <p style="text-align: justify; text-indent: 3ch;">
            Bekerjalah secepatnya, dan tulislah nomor-nomor sesuai dengan kesan dan keinginan Anda yang pertama muncul.
            <br>
            Selamat bekerja.
          </p>
        </div>
      </div>

      <!-- FORM 3 PEKERJAAN FAVORIT -->
      <div class="mt-5 border rounded-xl p-5 bg-slate-50">
        <div class="font-semibold mb-2">
          Tulislah di bawah ini 3 (tiga) macam pekerjaan yang paling ingin Anda lakukan atau paling Anda sukai
          <span class="text-slate-500 font-normal">(tidak harus tercantum dalam daftar)</span>
        </div>

        <form method="post" class="space-y-3">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

          <div class="grid md:grid-cols-[32px_1fr] gap-3 items-center">
            <div class="font-semibold">1.</div>
            <input name="fav1" value="<?= e($fav['fav1'] ?? '') ?>" maxlength="80"
                   class="w-full px-4 py-3 rounded-xl border bg-white"
                   placeholder="Pekerjaan favorit 1">
          </div>

          <div class="grid md:grid-cols-[32px_1fr] gap-3 items-center">
            <div class="font-semibold">2.</div>
            <input name="fav2" value="<?= e($fav['fav2'] ?? '') ?>" maxlength="80"
                   class="w-full px-4 py-3 rounded-xl border bg-white"
                   placeholder="Pekerjaan favorit 2">
          </div>

          <div class="grid md:grid-cols-[32px_1fr] gap-3 items-center">
            <div class="font-semibold">3.</div>
            <input name="fav3" value="<?= e($fav['fav3'] ?? '') ?>" maxlength="80"
                   class="w-full px-4 py-3 rounded-xl border bg-white"
                   placeholder="Pekerjaan favorit 3">
          </div>

          <div class="flex gap-2 pt-2">
            <button class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95">
              Simpan Pekerjaan Favorit
            </button>
          </div>
        </form>
      </div>

      <!-- TOMBOL TES -->
      <div class="mt-6">

        <div class="flex gap-3 flex-wrap">
          <?php if ($draft): ?>
            <a class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95"
               href="tes_wizard.php?sesi=<?= $draft ?>&k=A">Lanjutkan Tes</a>
          <?php else: ?>
            <a class="px-6 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:opacity-95"
               href="tes_start.php">Mulai Tes</a>
          <?php endif; ?>

          <a class="px-6 py-3 rounded-xl border bg-white hover:bg-slate-50" href="hasil.php">Lihat Hasil</a>
        </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?php include __DIR__ . '/../../views/peserta_layout_bottom.php'; ?>
