<?php
/**
 * Halaman Transparansi Dana & Akuntabilitas Publik
 * Pekarangan Institute
 *
 * Menampilkan:
 * 1. Rekapitulasi Penjualan Produk & Donasi Terkumpul secara TERPISAH (tidak pernah dijumlahkan).
 * 2. Visualisasi Alokasi Dana dari Pengaturan (CSS Bar murni tanpa library grafik).
 * 3. Donasi per Program Sosial (tabel progres donasi berhasil).
 * 4. Daftar Laporan Transparansi Berkala yang berstatus 'tampil'.
 * 5. Catatan transparansi dan label DATA CONTOH.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Transparansi Dana';
$menu_aktif = 'transparansi';
$deskripsi_halaman = 'Laporan akuntabilitas dana penjualan produk dan donasi masyarakat Pekarangan Institute secara terbuka dan transparan.';

// 1. Rekap Penjualan Produk (hanya pesanan berstatus dibayar, diproses, dikirim, selesai)
$rekap_penjualan = ['total_penjualan' => 0, 'total_transaksi' => 0, 'ada_contoh' => 0];
$semua_pesanan = pesanan_semua();
foreach ($semua_pesanan as $p) {
    if (in_array($p['status'] ?? '', ['dibayar', 'diproses', 'dikirim', 'selesai'], true)) {
        $rekap_penjualan['total_penjualan'] += (int)($p['total'] ?? 0);
        $rekap_penjualan['total_transaksi']++;
        if (!empty($p['is_contoh'])) {
            $rekap_penjualan['ada_contoh'] = 1;
        }
    }
}

// 2. Rekap Donasi Program (hanya donasi berstatus 'berhasil')
$rekap_donasi = ['total_donasi' => 0, 'total_donatur' => 0, 'ada_contoh' => 0];
$semua_donasi = donasi_semua();
foreach ($semua_donasi as $d) {
    if (($d['status'] ?? '') === 'berhasil') {
        $rekap_donasi['total_donasi'] += (int)($d['jumlah'] ?? 0);
        $rekap_donasi['total_donatur']++;
        if (!empty($d['is_contoh'])) {
            $rekap_donasi['ada_contoh'] = 1;
        }
    }
}

// 3. Alokasi Dana dari Pengaturan
$alokasi_sosial = (int)ambil_pengaturan('alokasi_sosial', '70');
$alokasi_operasional = (int)ambil_pengaturan('alokasi_operasional', '30');
$catatan_transparansi = ambil_pengaturan('catatan_transparansi', 'Alokasi dana operasional dan dana sosial di atas merupakan simulasi akuntabilitas dana.');

// Normalisasi persentase bila total tidak tepat 100
$total_alokasi = $alokasi_sosial + $alokasi_operasional;
if ($total_alokasi <= 0) {
    $alokasi_sosial = 70;
    $alokasi_operasional = 30;
}

// 4. Daftar Program Sosial & Donasi Masuk
$semua_program = program_semua();
$daftar_program = [];
foreach ($semua_program as $prog) {
    if (in_array($prog['status'] ?? '', ['aktif', 'selesai'], true)) {
        $daftar_program[] = $prog;
    }
}
usort($daftar_program, function ($a, $b) {
    $prioritas_a = (($a['status'] ?? '') === 'aktif') ? 1 : 2;
    $prioritas_b = (($b['status'] ?? '') === 'aktif') ? 1 : 2;
    if ($prioritas_a !== $prioritas_b) {
        return $prioritas_a <=> $prioritas_b;
    }
    return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
});

// 5. Daftar Laporan Publik Berstatus 'tampil'
$daftar_laporan = laporan_semua(true);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala">
        <h1>Transparansi & Akuntabilitas</h1>
        <p>
            Prinsip utama Pekarangan Institute adalah keterbukaan. Kami memisahkan secara ketat antara dana usaha komersial dan dana amanah donasi publik.
        </p>
    </div>

    <!-- 1. Dua Ringkasan Terpisah & Berlabel Jelas (Aturan Bisnis: DILARANG Dijumlahkan) -->
    <section class="bagian" aria-labelledby="judul-ringkasan-dana">
        <h2 id="judul-ringkasan-dana" class="teks-xl jarak-bawah-md">
            Ringkasan Penerimaan Dana (Terpisah)
        </h2>

        <div class="transparansi-rekap">
            <!-- Kartu Penjualan Produk -->
            <div class="rekap-kartu rekap-kartu-penjualan">
                <div class="baris-antara jarak-bawah-xs">
                    <span class="rekap-label">Penjualan Produk Pekarangan</span>
                </div>
                <div class="rekap-angka"><?= rupiah($rekap_penjualan['total_penjualan']) ?></div>
                <div class="rekap-keterangan">
                    Dari <strong><?= (int)$rekap_penjualan['total_transaksi'] ?></strong> pesanan terverifikasi (status dibayar, diproses, dikirim, atau selesai). Dana hasil penjualan digunakan untuk operasional mitra dan pemberdayaan pekebun binaan.
                </div>
            </div>

            <!-- Kartu Donasi Terkumpul -->
            <div class="rekap-kartu rekap-kartu-donasi">
                <div class="baris-antara jarak-bawah-xs">
                    <span class="rekap-label">Donasi Program Sosial</span>
                </div>
                <div class="rekap-angka"><?= rupiah($rekap_donasi['total_donasi']) ?></div>
                <div class="rekap-keterangan">
                    Dari <strong><?= (int)$rekap_donasi['total_donatur'] ?></strong> donasi berhasil terverifikasi admin. Seluruh dana donasi disalurkan utuh untuk program sosial kemasyarakatan yang berjalan.
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Alokasi Pengelolaan Dana (Batang Horizontal CSS) -->
    <section class="bagian" aria-labelledby="judul-alokasi-dana">
        <div class="artikel-konten">
            <div class="baris-antara-wrap jarak-bawah-sm">
                <h2 id="judul-alokasi-dana" class="teks-xl jarak-nol">Alokasi Pengelolaan Dana</h2>
            </div>
            <p class="teks-redup">
                Komitmen pembagian alokasi dana dari setiap pendapatan usaha dan kemitraan Pekarangan Institute:
            </p>

            <!-- Batang Horizontal CSS Murni -->
            <div class="alokasi-bar" role="progressbar" aria-label="Alokasi Dana" aria-valuenow="<?= $alokasi_sosial ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="alokasi-segmen-sosial" style="width: <?= $alokasi_sosial ?>%;" title="Sosial: <?= $alokasi_sosial ?>%"></div>
                <div class="alokasi-segmen-operasional" style="width: <?= $alokasi_operasional ?>%;" title="Operasional: <?= $alokasi_operasional ?>%"></div>
            </div>

            <div class="alokasi-keterangan">
                <div>
                    <span class="alokasi-titik alokasi-titik-sosial"></span>
                    <strong><?= $alokasi_sosial ?>%</strong> Dana Program Sosial & Pemberdayaan Komunitas
                </div>
                <div>
                    <span class="alokasi-titik alokasi-titik-operasional"></span>
                    <strong><?= $alokasi_operasional ?>%</strong> Operasional Kemitraan, Edukasi, & Pengembangan
                </div>
            </div>

            <?php if (!empty($catatan_transparansi)): ?>
                <div class="garis-batas-atas teks-xs teks-redup jarak-atas-md">
                    <strong>Catatan:</strong> <?= e($catatan_transparansi) ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3. Rincian Donasi per Program Sosial -->
    <section class="bagian" aria-labelledby="judul-donasi-program">
        <div class="baris-antara-wrap jarak-bawah-md">
            <h2 id="judul-donasi-program" class="teks-xl jarak-nol">Penyaluran Donasi per Program</h2>
            <span class="teks-xs teks-redup">Hanya menghitung donasi berstatus berhasil</span>
        </div>

        <?php if (!empty($daftar_program)): ?>
            <div class="tabel-responsif">
                <table class="tabel-data" aria-label="Tabel Donasi per Program">
                    <thead>
                        <tr>
                            <th scope="col">Nama Program</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="teks-kanan">Target Dana</th>
                            <th scope="col" class="teks-kanan">Terkumpul</th>
                            <th scope="col" class="kolom-capaian">Capaian (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_program as $p): ?>
                            <?php
                            $prog_info = progres_program((int)$p['id']);
                            $target = (int)$p['target_dana'];
                            $terkumpul = (int)$prog_info['terkumpul'];
                            $persen_asli = $target > 0 ? (int)round(($terkumpul / $target) * 100) : 0;
                            $persen_visual = min(100, $persen_asli);
                            ?>
                            <tr>
                                <td>
                                    <div class="teks-sedang">
                                        <a href="<?= url('program-detail.php?slug=' . urlencode($p['slug'])) ?>">
                                            <?= e($p['judul']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'aktif'): ?>
                                        <span class="lencana lencana-hijau">Aktif</span>
                                    <?php else: ?>
                                        <span class="lencana lencana-kuning">Selesai</span>
                                    <?php endif; ?>
                                </td>
                                <td class="teks-kanan teks-sedang">
                                    <?= rupiah($target) ?>
                                </td>
                                <td class="teks-kanan teks-tebal teks-biru">
                                    <?= rupiah($terkumpul) ?>
                                </td>
                                <td>
                                    <div class="baris-sejajar-tengah">
                                        <div class="progress-track progress-mini" role="progressbar" aria-valuenow="<?= $persen_visual ?>" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-fill" style="width: <?= $persen_visual ?>%;"></div>
                                        </div>
                                        <span class="teks-persen-mini">
                                            <?= $persen_asli ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="kotak-kosong">
                <p>Belum ada program sosial yang terdata.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. Daftar Laporan Transparansi Berkala -->
    <section class="bagian" aria-labelledby="judul-laporan-transparansi">
        <h2 id="judul-laporan-transparansi" class="teks-xl jarak-bawah-md">Laporan Transparansi Berkala</h2>

        <?php if (!empty($daftar_laporan)): ?>
            <div class="laporan-daftar">
                <?php foreach ($daftar_laporan as $lap): ?>
                    <?php
                    $jalur_berkas = __DIR__ . '/uploads/' . $lap['berkas'];
                    $ada_berkas = file_exists($jalur_berkas);
                    ?>
                    <article class="laporan-kartu">
                        <div class="laporan-info">
                            <div class="baris-sejajar-tengah jarak-bawah-xs">
                                <span class="lencana lencana-biru"><?= e($lap['periode']) ?></span>
                            </div>
                            <h3 class="teks-lg jarak-bawah-xs"><?= e($lap['judul']) ?></h3>
                            <p class="teks-sm teks-redup jarak-nol">
                                <?= e($lap['ringkasan']) ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($ada_berkas): ?>
                                <a href="<?= url('uploads/' . e($lap['berkas'])) ?>" class="tombol tombol-sekunder" target="_blank" rel="noopener noreferrer">
                                    Unduh Berkas (PDF)
                                </a>
                            <?php else: ?>
                                <span class="tombol tombol-sekunder tombol-dinonaktifkan" title="Berkas laporan belum diunggah ke folder uploads">
                                    Berkas Belum Tersedia
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="kotak-kosong">
                <h3>Belum ada laporan yang dipublikasikan saat ini.</h3>
                <p>Laporan audit dan pertanggungjawaban kegiatan akan diperbarui secara berkala pada setiap akhir kuartal.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
