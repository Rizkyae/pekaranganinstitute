<?php
/**
 * Halaman Tentang Kami (Tentang) - Pekarangan Institute
 * Menceritakan visi, tiga nilai utama, dan tata kelola alokasi dana dari pengaturan.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Tentang Kami';
$menu_aktif = 'tentang';

$tentang_teks = konten_ambil('tentang_teks');
$tentang_foto = konten_ambil('tentang_foto');
$alokasi_operasional = (int)ambil_pengaturan('alokasi_operasional', '30');
$alokasi_sosial = (int)ambil_pengaturan('alokasi_sosial', '70');
$catatan_transparansi = ambil_pengaturan('catatan_transparansi', 'Alokasi dana disalurkan secara berkala dan dapat dipantau pada halaman Transparansi.');

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala">
        <h1>Tentang Pekarangan Institute</h1>
        <p>Membangun kemandirian pangan, martabat keluarga, dan solidaritas sosial berbasis komunitas pekarangan.</p>
    </div>

    <!-- 1. Cerita & Profil -->
    <article class="artikel-konten">
        <?php if (!empty($tentang_foto) && file_exists(__DIR__ . '/uploads/' . $tentang_foto)): ?>
            <div class="foto-wadah jarak-bawah-lg">
                <img src="<?= url('uploads/' . e($tentang_foto)) ?>" alt="Profil Pekarangan Institute" class="foto-persegi">
            </div>
        <?php endif; ?>

        <h2>Inisiatif Kami</h2>
        <p><?= nl2br(e($tentang_teks)) ?></p>

        <!-- 2. Tiga Nilai Utama -->
        <h2>Tiga Pilar: Berbelanja, Berbagi, Berdampak</h2>
        <div class="langkah-grid jarak-atas-lg jarak-bawah-lg">
            <div class="langkah-kartu langkah-1">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                </div>
                <h3><?= e(konten_ambil('pilar_1_judul')) ?></h3>
                <p><?= e(konten_ambil('pilar_1_teks')) ?></p>
            </div>
            <div class="langkah-kartu langkah-2">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <h3><?= e(konten_ambil('pilar_2_judul')) ?></h3>
                <p><?= e(konten_ambil('pilar_2_teks')) ?></p>
            </div>
            <div class="langkah-kartu langkah-3">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"></path><path d="M12 13a5 5 0 0 0 5-5c0-4-5-6-5-6s-5 2-5 6a5 5 0 0 0 5 5z"></path><path d="M12 17a3 3 0 0 1 3-3"></path></svg>
                </div>
                <h3><?= e(konten_ambil('pilar_3_judul')) ?></h3>
                <p><?= e(konten_ambil('pilar_3_teks')) ?></p>
            </div>
        </div>

        <!-- 3. Bagaimana Dana Dikelola -->
        <h2>Bagaimana Dana Dikelola</h2>
        <p>
            Sebagai inisiatif kewirausahaan sosial, kami menjunjung tinggi keterbukaan dan akuntabilitas. Alokasi pemanfaatan dana komersial dan donasi sosial diatur secara proporsional:
        </p>

        <div class="kotak-alokasi">
            <div class="baris-antara teks-sedang">
                <span>Distribusi Alokasi Dana</span>
            </div>

            <!-- Bilah Proporsi Alokasi Dana -->
            <div class="alokasi-bar" role="img" aria-label="Grafik proporsi: <?= $alokasi_sosial ?>% Dana Sosial dan <?= $alokasi_operasional ?>% Operasional">
                <div class="alokasi-segmen-sosial" style="width: <?= $alokasi_sosial ?>%;"></div>
                <div class="alokasi-segmen-operasional" style="width: <?= $alokasi_operasional ?>%;"></div>
            </div>

            <div class="alokasi-keterangan">
                <div>
                    <span class="alokasi-titik alokasi-titik-sosial"></span>
                    <strong><?= $alokasi_sosial ?>% Dana Sosial & Pemberdayaan</strong>
                    <div class="teks-xs teks-redup">
                        Pengadaan bibit, pelatihan budidaya, pendampingan warga dhuafa, dan program kemasyarakatan.
                    </div>
                </div>
                <div>
                    <span class="alokasi-titik alokasi-titik-operasional"></span>
                    <strong><?= $alokasi_operasional ?>% Operasional & Keberlanjutan</strong>
                    <div class="teks-xs teks-redup">
                        Logistik, pemeliharaan sistem, pengemasan, administrasi, dan koordinasi relawan lapangan.
                    </div>
                </div>
            </div>

            <?php if (!empty($catatan_transparansi)): ?>
                <p class="teks-xs teks-redup garis-titik-atas jarak-atas-md">
                    <em>Catatan: <?= e($catatan_transparansi) ?></em>
                </p>
            <?php endif; ?>
        </div>

        <p>
            Laporan pertanggungjawaban berkala dapat diakses secara terbuka oleh publik melalui halaman transparansi kami.
        </p>
        <div class="jarak-atas-lg">
            <a href="<?= url('transparansi.php') ?>" class="tombol tombol-sekunder">
                Lihat Laporan Transparansi &rarr;
            </a>
        </div>
    </article>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
