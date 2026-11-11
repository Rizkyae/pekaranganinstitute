<?php
/**
 * Halaman Kontak - Pekarangan Institute
 * Menampilkan alamat resmi, tautan WhatsApp, dan email tanpa formulir pesan langsung.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Hubungi Kami';
$menu_aktif = 'kontak';
$deskripsi_halaman = 'Hubungi tim Pekarangan Institute untuk pertanyaan seputar produk, donasi, program pemberdayaan, dan kemitraan.';

$whatsapp = ambil_pengaturan('whatsapp', '0813-5744-1144');
if (empty($whatsapp) || $whatsapp === '08xx-xxxx-xxxx') {
    $whatsapp = '0813-5744-1144';
}
$email = ambil_pengaturan('email', 'kontak@pekarangan.org');
$alamat = ambil_pengaturan('alamat', '[Alamat Kantor/Sekretariat Contoh]');

// Format nomor WhatsApp untuk tautan https://wa.me/
$wa_digit = preg_replace('/\D/', '', $whatsapp);
if (str_starts_with($wa_digit, '0')) {
    $wa_link = '62' . substr($wa_digit, 1);
} else {
    $wa_link = $wa_digit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala">
        <h1>Hubungi Kami</h1>
        <p>Punya pertanyaan mengenai produk, donasi sosial, atau ingin berkolaborasi? Kami siap menyambut Anda.</p>
    </div>

    <div class="kontak-grid">
        <!-- Kartu WhatsApp -->
        <div class="kontak-kartu">
            <div class="baris-antara jarak-bawah-sm">
                <span class="kartu-kategori">Layanan Cepat</span>
            </div>
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                WhatsApp Resmi
            </h3>
            <p>
                Konfirmasi pemesanan produk, pertanyaan donasi, dan layanan informasi pelanggan melalui pesan WhatsApp:
            </p>
            <p class="teks-lg teks-tebal teks-biru">
                <?= e($whatsapp) ?>
            </p>
            <div class="kontak-kaki">
                <a href="https://wa.me/<?= e($wa_link) ?>" target="_blank" rel="noopener noreferrer" class="tombol tombol-utama tombol-blok">
                    Hubungi Kami
                </a>
            </div>
        </div>

        <!-- Kartu Email -->
        <div class="kontak-kartu">
            <div class="baris-antara jarak-bawah-sm">
                <span class="kartu-kategori">Korespondensi</span>
            </div>
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Surat Elektronik (Email)
            </h3>
            <p>
                Untuk penawaran kerja sama, proposal kemitraan institusi, dan surat resmi tertulis:
            </p>
            <p class="teks-md teks-tebal teks-biru">
                <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            </p>
            <div class="kontak-kaki">
                <a href="mailto:<?= e($email) ?>" class="tombol tombol-sekunder tombol-blok">
                    Kirim Email
                </a>
            </div>
        </div>

        <!-- Kartu Alamat Kantor -->
        <div class="kontak-kartu">
            <div class="baris-antara jarak-bawah-sm">
                <span class="kartu-kategori">Kantor & Kebun</span>
            </div>
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Alamat Sekretariat
            </h3>
            <p>
                Kunjungi sekretariat dan kebun komunitas kami pada jam kerja (Senin - Jumat, 08.00 - 16.00 WIB):
            </p>
            <p class="teks-sm">
                <?= e($alamat) ?>
            </p>
            <div class="kontak-kaki">
                <span class="tombol tombol-sekunder tombol-blok tombol-tetap">
                    Sekretariat Komunitas
                </span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>