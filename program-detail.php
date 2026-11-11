<?php
/**
 * Detail Program Sosial & Daftar Donatur
 * Pekarangan Institute
 *
 * Menampilkan cerita program yang faktual dan objektif tanpa eksploitasi emosional.
 * Menampilkan progres dana riil (donasi berhasil) dan daftar donatur (donasi anonim disamarkan).
 */

require_once __DIR__ . '/includes/fungsi.php';

$slug = trim($_GET['slug'] ?? '');

$program = null;
if ($slug !== '') {
    $prog_cek = program_by_slug($slug);
    if ($prog_cek && in_array($prog_cek['status'] ?? '', ['aktif', 'selesai'], true)) {
        $program = $prog_cek;
    }
}

// Jika program tidak ditemukan, tampilkan 404 ramah
if (!$program) {
    http_response_code(404);
    $judul_halaman = 'Program Tidak Ditemukan';
    $menu_aktif = 'program';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="kontainer">
        <div class="kotak-404">
            <h1>404</h1>
            <h2>Program Tidak Ditemukan</h2>
            <p>Maaf, program sosial yang Anda cari tidak ditemukan atau sudah tidak tersedia.</p>
            <div>
                <a href="<?= url('program.php') ?>" class="tombol tombol-utama">&larr; Kembali ke Daftar Program</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$judul_halaman = $program['judul'];
$menu_aktif = 'program';
$deskripsi_halaman = $program['ringkasan'];

$progres = progres_program($program['id']);
$target = (int)$program['target_dana'];
$terkumpul = (int)$progres['terkumpul'];
$persen_asli = $target > 0 ? (int)round(($terkumpul / $target) * 100) : 0;
$persen_visual = min(100, $persen_asli);
$adalah_aktif = ($program['status'] === 'aktif');

// Ambil riwayat donatur terbaru yang berstatus 'berhasil' saja dari RTDB
$semua_donasi = donasi_semua();
$daftar_donatur = [];
foreach ($semua_donasi as $d) {
    if ((string)($d['program_id'] ?? '') === (string)$program['id'] && ($d['status'] ?? '') === 'berhasil') {
        $daftar_donatur[] = $d;
    }
}
$daftar_donatur = array_slice($daftar_donatur, 0, 10);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <!-- Breadcrumb -->
    <div class="jarak-bawah-lg">
        <a href="<?= url('program.php') ?>" class="teks-sm teks-sedang">
            &larr; Kembali ke Semua Program
        </a>
    </div>

    <div class="detail-grid">
        <!-- Kolom Kiri: Sampul & Cerita Program -->
        <div class="detail-gambar">
            <div class="foto-wadah foto-sampul-program">
                <?php if (!empty($program['foto_sampul']) && file_exists(__DIR__ . '/uploads/' . $program['foto_sampul'])): ?>
                    <img src="<?= url('uploads/' . e($program['foto_sampul'])) ?>" alt="<?= e($program['judul']) ?>">
                <?php else: ?>
                    <div class="foto-kosong">[Foto Sampul Program]</div>
                <?php endif; ?>
            </div>

            <article class="artikel-konten jarak-bawah-lg">
                <h2 class="artikel-subjudul">Cerita & Sasaran Program</h2>
                <div class="artikel-paragraf">
                    <?= nl2br(e($program['cerita'])) ?>
                </div>

                <div class="program-penerima-catatan">
                    <p class="jarak-bawah-sm">
                        <strong>Penerima Manfaat:</strong> <?= e($program['penerima_manfaat']) ?>
                    </p>
                    <p class="jarak-nol">
                        <em>* Sesuai etika perlindungan hak privasi martabat komunitas dan aturan perlindungan anak, kami tidak mempublikasikan nama perorangan atau foto anak penerima manfaat.</em>
                    </p>
                </div>
            </article>

            <!-- Daftar Donatur Terbaru (Hanya Berstatus Berhasil) -->
            <section class="artikel-konten" aria-labelledby="judul-donatur-terbaru">
                <h3 id="judul-donatur-terbaru" class="teks-lg jarak-bawah-sm">
                    Donatur Terbaru (<?= (int)$progres['jumlah_donatur'] ?>)
                </h3>
                <p class="teks-sm teks-redup jarak-bawah-md">
                    Dukungan nyata yang telah diverifikasi dan masuk ke rekening program.
                </p>

                <?php if (!empty($daftar_donatur)): ?>
                    <ul class="donatur-daftar">
                        <?php foreach ($daftar_donatur as $d): ?>
                            <?php
                            $nama_tampil = !empty($d['anonim']) ? 'Anonim' : $d['nama_donatur'];
                            ?>
                            <li class="donatur-item">
                                <div class="donatur-kepala">
                                    <span><?= e($nama_tampil) ?></span>
                                    <span class="teks-biru teks-tebal"><?= rupiah($d['jumlah']) ?></span>
                                </div>
                                <div class="teks-xs teks-redup">
                                    <?= tanggal_id($d['dibuat_pada']) ?>
                                </div>
                                <?php if (!empty($d['pesan'])): ?>
                                    <div class="donatur-pesan">
                                        &ldquo;<?= e($d['pesan']) ?>&rdquo;
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="kotak-kosong jarak-pad-md">
                        <p class="jarak-nol teks-sm">Belum ada donasi yang tercatat untuk program ini. Jadilah donatur pertama!</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Kolom Kanan: Ringkasan Dana & Tombol Donasi -->
        <div class="detail-konten">
            <div class="baris-antara-wrap jarak-bawah-sm">
                <div>
                    <?php if ($adalah_aktif): ?>
                        <span class="lencana lencana-hijau">Aktif</span>
                    <?php else: ?>
                        <span class="lencana lencana-kuning">Selesai</span>
                    <?php endif; ?>
                </div>
            </div>

            <h1 class="sukses-judul jarak-bawah-md"><?= e($program['judul']) ?></h1>
            <p class="teks-redup jarak-bawah-lg">
                <?= e($program['ringkasan']) ?>
            </p>

            <!-- Kartu Progres Dana -->
            <div class="kartu-progres-dana">
                <div class="progres-label-dana">
                    Dana Terkumpul
                </div>
                <div class="progres-nilai-dana">
                    <?= rupiah($terkumpul) ?>
                </div>

                <div class="progress-track progress-track-lg" role="progressbar" aria-valuenow="<?= $persen_visual ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-fill" style="width: <?= $persen_visual ?>%;"></div>
                </div>

                <div class="baris-antara teks-sm teks-redup">
                    <span>Tercapai <strong><?= $persen_asli ?>%</strong></span>
                    <span>Target: <strong><?= rupiah($target) ?></strong></span>
                </div>
            </div>

            <!-- Detail Metadata Program -->
            <div class="detail-meta">
                <div class="detail-meta-item">
                    <span class="detail-meta-label">Lokasi Program:</span>
                    <span class="detail-meta-nilai"><?= e($program['lokasi']) ?></span>
                </div>
                <?php if (!empty($program['tenggat'])): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label">Tenggat Waktu:</span>
                        <span class="detail-meta-nilai"><?= tanggal_id($program['tenggat']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="detail-meta-item">
                    <span class="detail-meta-label">Total Donatur:</span>
                    <span class="detail-meta-nilai"><?= (int)$progres['jumlah_donatur'] ?> orang</span>
                </div>
            </div>

            <!-- Tombol Aksi / Informasi Status -->
            <div class="jarak-atas-lg">
                <?php if ($adalah_aktif): ?>
                    <a href="<?= url('donasi.php?program=' . urlencode($program['slug'])) ?>" class="tombol tombol-utama tombol-blok">
                        Donasi Sekarang
                    </a>
                    <span class="blok teks-tengah teks-xs teks-redup jarak-atas-sm">
                        Konfirmasi pembayaran aman dan terverifikasi via transfer bank manual.
                    </span>
                <?php else: ?>
                    <div class="banner-peringatan jarak-nol">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <div>
                            <strong>Penggalangan Dana Telah Selesai</strong>
                            <p class="jarak-atas-xs teks-sm">
                                Target program ini telah terpenuhi atau masa penggalangan telah ditutup. Terima kasih atas partisipasi seluruh donatur!
                            </p>
                        </div>
                    </div>
                    <div class="jarak-atas-md">
                        <a href="<?= url('program.php') ?>" class="tombol tombol-sekunder tombol-blok">
                            Lihat Program Aktif Lainnya
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
