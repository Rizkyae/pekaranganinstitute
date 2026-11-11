<?php
/**
 * Footer Halaman Publik Pekarangan Institute
 * Tata letak tetap sama seperti sebelumnya (3 kolom informasi kontak dan hak cipta penuh),
 * dilengkapi cache-busting otomatis pada skrip javascript.
 */

require_once __DIR__ . '/fungsi.php';

$nama_situs = ambil_pengaturan('nama_situs', 'Pekarangan Institute');
$tagline_situs = ambil_pengaturan('tagline', 'Berbelanja, Berbagi, Berdampak');
$alamat = ambil_pengaturan('alamat', '');
$email = ambil_pengaturan('email', '');
$whatsapp = ambil_pengaturan('whatsapp', '');
$url_logo_mitra = url_logo_mitra();
$url_logo_instansi = url_logo_instansi();

$v_js = file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : time();
?>
    </main>

    <!-- Footer Utama -->
    <footer class="footer-utama">
        <div class="kontainer">
            <div class="footer-grid">
                <!-- Kolom Tentang / Brand & Kemitraan -->
                <div class="footer-tentang">
                    <a href="<?= url() ?>" class="logo-tautan jarak-bawah-sm" aria-label="Beranda <?= e($nama_situs) ?>">
                        <?php if (!empty($url_logo_mitra) || !empty($url_logo_instansi)): ?>
                            <span class="logo-pasangan">
                                <?php if (!empty($url_logo_mitra)): ?>
                                    <img src="<?= $url_logo_mitra ?>" alt="<?= e($nama_situs) ?>" class="logo-item logo-mitra">
                                <?php endif; ?>
                                <?php if (!empty($url_logo_mitra) && !empty($url_logo_instansi)): ?>
                                    <span class="logo-pemisah" aria-hidden="true"></span>
                                <?php endif; ?>
                                <?php if (!empty($url_logo_instansi)): ?>
                                    <span class="logo-instansi-bungkus">
                                        <span class="logo-instansi-label">Bekerja sama dengan</span>
                                        <img src="<?= $url_logo_instansi ?>" alt="Logo Instansi Mitra" class="logo-item logo-instansi">
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="logo-teks-simbol" aria-hidden="true">&#9851;</span>
                            <span class="logo-teks-nama"><?= e(strtoupper($nama_situs)) ?></span>
                        <?php endif; ?>
                    </a>
                    <p class="teks-sedang jarak-bawah-xs"><strong><?= e($tagline_situs) ?></strong></p>
                    <p class="teks-sm teks-redup jarak-atas-xs">
                        <?= e(ambil_pengaturan('footer_teks', 'Pemberdayaan pekarangan produktif dan penguatan ekonomi komunitas menuju ketahanan pangan keluarga mandiri.')) ?>
                    </p>
                </div>

                <!-- Kolom Tautan Cepat -->
                <div class="footer-kolom">
                    <h3 class="footer-judul">Navigasi</h3>
                    <ul class="footer-menu">
                        <li><a href="<?= url() ?>">Beranda</a></li>
                        <li><a href="<?= url('produk.php') ?>">Katalog Produk</a></li>
                        <li><a href="<?= url('program.php') ?>">Program Sosial</a></li>
                        <li><a href="<?= url('transparansi.php') ?>">Transparansi Dana</a></li>
                        <li><a href="<?= url('tentang.php') ?>">Tentang Kami</a></li>
                        <li><a href="<?= url('kontak.php') ?>">Hubungi Kami</a></li>
                    </ul>
                </div>

                <!-- Kolom Kontak Resmi -->
                <div class="footer-kolom footer-kontak">
                    <h3 class="footer-judul">Kontak</h3>
                    <?php if (!empty($alamat)): ?>
                        <p><strong>Alamat:</strong> <?= e($alamat) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($email)): ?>
                        <p><strong>Email:</strong> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($whatsapp)): ?>
                        <p><strong>WhatsApp:</strong> <?= e($whatsapp) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hak Cipta -->
            <div class="footer-bawah">
                <div>
                    &copy; <?= date('Y') ?> <?= e($nama_situs) ?>. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </footer>
</div><!-- /.wadah-situs -->

    <!-- Berkas JavaScript Interaksi Sederhana (dengan Cache-Busting) -->
    <script src="<?= url('assets/js/app.js') ?>?v=<?= $v_js ?>"></script>
</body>
</html>
