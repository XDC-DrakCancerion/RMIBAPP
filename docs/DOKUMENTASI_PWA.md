# Dokumentasi PWA RMIB (Non-Teknis)

## Tujuan
Fitur PWA ini dibuat agar peserta tetap bisa mengerjakan tes RMIB walaupun internet tiba-tiba mati. Hasil offline akan tetap dihitung di perangkat dan ditandai sebagai "sementara" sampai tersinkron ke server saat online kembali.

## Ringkas Manfaat
- Tes bisa tetap berjalan saat internet mati.
- Peserta bisa melanjutkan tes tanpa kehilangan jawaban.
- Hasil sementara tetap terlihat, lalu disahkan saat sinkron.

## Cara Pakai (Untuk Peserta)
1. Login seperti biasa.
2. Buka menu "Tes RMIB".
3. Klik tombol "Siapkan Paket Offline" (disarankan dilakukan saat online).
4. Jika nanti internet mati, buka "Buka Tes Offline".
5. Kerjakan tes sampai selesai. Hasil akan muncul sebagai "sementara".
6. Saat internet kembali, klik "Sinkronkan" untuk menyimpan hasil resmi ke server.

## Hal Penting yang Perlu Diketahui
- Mode offline hanya bisa dipakai di perangkat yang **pernah login**.
- Mode offline membutuhkan paket tes yang **sudah diunduh saat online**.
- Hasil offline bersifat **sementara** dan baru menjadi resmi setelah sinkron.
- Jika peserta ganti perangkat atau data browser terhapus, tes offline tidak bisa dilanjutkan.

## Alur PWA (Sederhana)

[ONLINE] -> Login -> Siapkan Paket Offline -> Paket Tersimpan
                      |
                      v
                 (Internet Mati)
                      |
                      v
                Buka Tes Offline -> Isi Tes -> Hasil Sementara
                      |
                      v
               (Internet Kembali)
                      |
                      v
                 Sinkronkan -> Hasil Resmi

## Apa yang Terjadi Saat Offline
- Peserta tetap mengisi peringkat 1-12 untuk tiap kelompok.
- Sistem menghitung skor dan peringkat kategori langsung di perangkat.
- Hasil tetap terlihat namun ada label "sementara".

## Apa yang Terjadi Saat Online Kembali
- Sistem mengirim jawaban ke server.
- Server menyimpan hasil resmi.
- Peserta bisa melihat hasil resmi di menu "Hasil" dan mengunduh PDF.

## Checklist Uji Manual (Untuk Admin / Tester)
1. Login peserta dan buka menu Tes.
2. Klik "Siapkan Paket Offline" lalu pastikan status paket siap.
3. Matikan internet.
4. Buka "Buka Tes Offline" dan selesaikan tes.
5. Pastikan hasil sementara muncul.
6. Hidupkan internet lalu klik "Sinkronkan".
7. Buka menu "Hasil" dan cek hasil resmi muncul.

