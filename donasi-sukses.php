<?php
/**
 * Halaman Donasi Sukses - Pekarangan Institute
 * Menampilkan rincian pembayaran QRIS, mencatat klaim bayar donatur, dan menunggu verifikasi admin.
 */

require_once __DIR__ . '/includes/fungsi.php';

$kode = trim($_GET['kode'] ?? '');

$donasi = null;
$klaim_dibuat = false;
if ($kode !== '') {
    $donasi = donasi_by_kode($kode);
    if ($donasi) {
        $prog = !empty($donasi['program_id']) ? program_ambil((string)$donasi['program_id']) : null;
        $donasi['judul_program'] = $prog['judul'] ?? '-';
        $donasi['slug_program'] = $prog['slug'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!cek_csrf()) {
                pesan_flash('merah', 'Sesi pembayaran kedaluwarsa. Muat ulang halaman lalu coba lagi.');
            } elseif (($donasi['status'] ?? '') !== 'menunggu') {
                pesan_flash('kuning', 'Status donasi ini sudah diproses. Silakan hubungi admin jika membutuhkan bantuan.');
            } else {
                try {
                    if (donasi_catat_klaim_pembayaran((string)$donasi['id'])) {
                        pesan_flash('hijau', 'Konfirmasi Anda sudah dicatat. Donasi tetap menunggu verifikasi admin.');
                    } else {
                        pesan_flash('merah', 'Konfirmasi pembayaran gagal dicatat. Silakan coba lagi atau hubungi admin.');
                    }
                } catch (Throwable $e) {
                    pesan_flash('merah', pesan_error_db('Gagal mencatat konfirmasi pembayaran', $e));
                }
            }
            alihkan('donasi-sukses.php?kode=' . urlencode($kode));
        }
    }
}

// Jika kode donasi tidak ditemukan, tampilkan 404 ramah
if (!$donasi) {
    http_response_code(404);
    $judul_halaman = 'Donasi Tidak Ditemukan';
    $menu_aktif = 'donasi';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="kontainer">
        <div class="kotak-404">
            <h1>404</h1>
            <h2>Donasi Tidak Ditemukan</h2>
            <p>Maaf, kode donasi yang Anda tuju tidak tercatat dalam sistem kami. Pastikan tautan yang Anda gunakan sudah benar.</p>
            <div>
                <a href="<?= url('program.php') ?>" class="tombol tombol-utama">&larr; Kembali ke Daftar Program</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$judul_halaman = 'Instruksi Donasi - ' . $donasi['kode'];
$menu_aktif = 'donasi';
$deskripsi_halaman = 'Instruksi transfer dan konfirmasi pembayaran donasi sosial Pekarangan Institute.';

$nomor_admin_wa  = ambil_pengaturan('whatsapp', '');
$klaim_dibuat = !empty($donasi['pembayaran_dikonfirmasi_donatur_pada']);

$pesan_status_wa = $klaim_dibuat
    ? 'Saya sudah membayar via QRIS dan telah mengirim klaim pembayaran. Mohon bantu verifikasi.'
    : 'Saya sedang melakukan pembayaran via QRIS. Mohon bantu jika ada kendala.';
$pesan_wa = sprintf(
    "Halo Admin Pekarangan Institute, %s\n\nKode Donasi: %s\nNama Donatur: %s\nProgram: %s\nNominal: %s\n\nTerima kasih.",
    $pesan_status_wa,
    $donasi['kode'],
    $donasi['nama_donatur'],
    $donasi['judul_program'],
    rupiah($donasi['jumlah'])
);

$tautan_konfirmasi_wa = tautan_wa($nomor_admin_wa, $pesan_wa);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="kartu-sukses">
        <div class="sukses-ikon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </div>

        <div class="teks-tengah jarak-bawah-lg">
            <h1 class="sukses-judul">Terima Kasih atas Kebaikan Anda</h1>
            <p class="teks-redup">
                Silakan selesaikan pembayaran melalui QRIS di bawah. Setelah membayar, beri tahu kami melalui tombol konfirmasi.
            </p>
        </div>

        <!-- Kode Referensi Donasi -->
        <div class="kode-kotak">
            <div class="kode-kotak-label">Kode Referensi Donasi</div>
            <div class="kode-kotak-nilai"><?= e($donasi['kode']) ?></div>
            <span class="teks-xs teks-redup blok jarak-atas-xs">
                Gunakan kode ini saat melakukan konfirmasi kepada admin kami.
            </span>
        </div>

        <!-- Status Verifikasi Donasi -->
        <div class="banner-peringatan" role="note">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div>
                <strong><?= $klaim_dibuat ? 'Konfirmasi pembayaran Anda sudah dicatat' : 'Donasi dihitung setelah diverifikasi admin' ?></strong>
                <p class="jarak-atas-xs teks-sm">
                    <?= $klaim_dibuat
                        ? 'Admin akan mencocokkan pembayaran yang masuk. Progres program bertambah setelah verifikasi.'
                        : 'Tombol konfirmasi hanya mencatat klaim pembayaran. Progres program baru bertambah setelah dana masuk dan diverifikasi admin.' ?>
                </p>
            </div>
        </div>

        <!-- Rincian Donasi -->
        <h3 class="teks-lg jarak-atas-lg jarak-bawah-sm">Rincian Donasi</h3>
        <table class="tabel-ringkasan" aria-label="Rincian Donasi">
            <thead>
                <tr>
                    <th scope="col">Keterangan</th>
                    <th scope="col" class="teks-kanan">Rincian</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Program Sosial</td>
                    <td><strong><?= e($donasi['judul_program']) ?></strong></td>
                </tr>
                <tr>
                    <td>Status Donasi</td>
                    <td>
                        <?php if (($donasi['status'] ?? '') === 'berhasil'): ?>
                            <span class="lencana lencana-hijau">Terverifikasi</span>
                        <?php elseif (($donasi['status'] ?? '') === 'gagal'): ?>
                            <span class="lencana lencana-merah">Gagal / Dibatalkan</span>
                        <?php else: ?>
                            <span class="lencana lencana-kuning"><?= $klaim_dibuat ? 'Konfirmasi Diterima' : 'Menunggu Pembayaran' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="baris-total">
                    <td>Nominal Donasi</td>
                    <td class="teks-biru teks-tebal"><?= rupiah($donasi['jumlah']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- QRIS statis: nominal dibayar harus dimasukkan di aplikasi pembayaran -->
        <div class="kotak-instruksi-bank">
            <h4>Bayar melalui QRIS</h4>
            <p>Pastikan nama penerima pada aplikasi pembayaran adalah <strong>Pekarangan Institute</strong>.</p>
            <div class="qris-rincian">
                <div><span>Nama donatur</span><strong><?= e($donasi['nama_donatur']) ?></strong></div>
                <div><span>Program</span><strong><?= e($donasi['judul_program']) ?></strong></div>
                <div><span>Nominal yang harus dibayar</span><strong><?= rupiah($donasi['jumlah']) ?></strong></div>
                <div><span>Kode donasi</span><strong><?= e($donasi['kode']) ?></strong></div>
            </div>
            <img
                src="<?= url('assets/img/qris-pekarangan.jpeg') ?>"
                alt="QRIS Pekarangan Institute"
                class="qris-gambar"
            >
            <p class="teks-xs teks-redup">
                QRIS ini bersifat statis dan tidak membawa nominal donasi. Setelah memindai, masukkan tepat <strong><?= rupiah($donasi['jumlah']) ?></strong> secara manual dan pastikan penerima adalah Pekarangan Institute.
            </p>
        </div>

        <!-- Konfirmasi donatur tetap menunggu verifikasi admin -->
        <div class="grup-aksi-sukses">
            <?php if (($donasi['status'] ?? '') === 'menunggu' && !$klaim_dibuat): ?>
                <form action="<?= url('donasi-sukses.php?kode=' . urlencode($kode)) ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(token_csrf()) ?>">
                    <button type="submit" class="tombol tombol-utama tombol-blok">Saya Sudah Membayar via QRIS</button>
                </form>
            <?php elseif ($klaim_dibuat && ($donasi['status'] ?? '') === 'menunggu'): ?>
                <p class="teks-tengah teks-sm teks-redup">Konfirmasi sudah tercatat. Admin akan memverifikasi pembayaran Anda.</p>
            <?php endif; ?>

            <a href="<?= e($tautan_konfirmasi_wa) ?>" target="_blank" rel="noopener noreferrer" class="tombol tombol-whatsapp tombol-blok jarak-atas-sm">
                Hubungi Admin via WhatsApp
            </a>

            <div class="grup-tombol grup-tombol-tengah jarak-atas-sm">
                <a href="<?= url('program-detail.php?slug=' . urlencode($donasi['slug_program'])) ?>" class="tombol tombol-sekunder">
                    Lihat Program
                </a>
                <a href="<?= url() ?>" class="tombol tombol-sekunder">
                    Halaman Utama
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
