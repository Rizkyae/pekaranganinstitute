<?php
/**
 * Dashboard Administrator - Pekarangan Institute
 * Ringkasan 4 kartu terpisah (Penjualan, Donasi, Pesanan Menunggu, Donasi Menunggu),
 * data terbaru, aksi cepat, dan label data contoh transparan.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'dashboard';
$judul_halaman = 'Dashboard';

// Ambil data pesanan dan donasi dari RTDB
$semua_pesanan = pesanan_semua();
$semua_donasi = donasi_semua();

// 1. Ringkasan Penjualan (Pesanan Dibayar ke atas)
$rekap_penjualan = ['total' => 0, 'jumlah' => 0, 'ada_contoh' => 0];
$rekap_pesanan_menunggu = ['jumlah' => 0, 'ada_contoh' => 0];

foreach ($semua_pesanan as $p) {
    $st = $p['status'] ?? '';
    if (in_array($st, ['dibayar', 'diproses', 'dikirim', 'selesai'], true)) {
        $rekap_penjualan['total'] += (int)($p['total'] ?? 0);
        $rekap_penjualan['jumlah']++;
        if (!empty($p['is_contoh'])) {
            $rekap_penjualan['ada_contoh'] = 1;
        }
    }
    if ($st === 'menunggu') {
        $rekap_pesanan_menunggu['jumlah']++;
        if (!empty($p['is_contoh'])) {
            $rekap_pesanan_menunggu['ada_contoh'] = 1;
        }
    }
}

// 2. Ringkasan Donasi (Berhasil)
$rekap_donasi = ['total' => 0, 'jumlah' => 0, 'ada_contoh' => 0];
$rekap_donasi_menunggu = ['jumlah' => 0, 'ada_contoh' => 0];

foreach ($semua_donasi as $d) {
    $st = $d['status'] ?? '';
    if ($st === 'berhasil') {
        $rekap_donasi['total'] += (int)($d['jumlah'] ?? 0);
        $rekap_donasi['jumlah']++;
        if (!empty($d['is_contoh'])) {
            $rekap_donasi['ada_contoh'] = 1;
        }
    }
    if ($st === 'menunggu') {
        $rekap_donasi_menunggu['jumlah']++;
        if (!empty($d['is_contoh'])) {
            $rekap_donasi_menunggu['ada_contoh'] = 1;
        }
    }
}

// Ambil program aktif teratas untuk Highlight Card
$daftar_program_aktif = program_semua(true);
$prog_unggulan = !empty($daftar_program_aktif) ? $daftar_program_aktif[0] : null;
$progres_unggulan = $prog_unggulan ? progres_program((int)$prog_unggulan['id']) : null;

// Hitung proporsi untuk bar statistik segmented
$tot_tx = (int)$rekap_penjualan['jumlah'] + (int)$rekap_donasi['jumlah'] + (int)$rekap_pesanan_menunggu['jumlah'] + (int)$rekap_donasi_menunggu['jumlah'];
$pct_order = $tot_tx > 0 ? max(10, (int)round(($rekap_penjualan['jumlah'] / $tot_tx) * 100)) : 50;
$pct_donasi = $tot_tx > 0 ? max(10, (int)round(($rekap_donasi['jumlah'] / $tot_tx) * 100)) : 35;
$pct_menunggu = max(5, 100 - $pct_order - $pct_donasi);

// 5 Pesanan Terbaru
$pesanan_terbaru = array_slice($semua_pesanan, 0, 5);

// 5 Donasi Terbaru (dengan judul program)
$donasi_terbaru = array_slice($semua_donasi, 0, 5);
foreach ($donasi_terbaru as &$d) {
    $prog = !empty($d['program_id']) ? program_ambil((string)$d['program_id']) : null;
    $d['nama_program'] = $prog['judul'] ?? '-';
}
unset($d);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <div class="admin-halaman-sapaan">
            <span>Selamat Datang Kembali, <?= e($_SESSION['admin_nama'] ?? 'Admin') ?></span>
        </div>
        <h1 class="admin-halaman-judul">Dashboard</h1>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/produk-form.php') ?>" class="tombol tombol-utama">+ Tambah Produk</a>
        <a href="<?= url('admin/program-form.php') ?>" class="tombol tombol-sekunder">+ Buat Program</a>
    </div>
</div>

<!-- Layout 2-Kolom CoachPro Grid -->
<div class="coach-grid">
    <!-- Kolom Kiri: Highlight Program Berjalan & Tabel Pesanan Terbaru -->
    <div class="coach-kolom">
        <!-- 1. Highlight Widget (Next Game style) -->
        <section class="coach-kartu" aria-labelledby="judul-program-berjalan">
            <div class="coach-kartu-kepala">
                <h2 id="judul-program-berjalan" class="coach-kartu-judul">Program Berjalan</h2>
                <a href="<?= url('admin/program.php') ?>" class="coach-kartu-tautan">Semua Program &rarr;</a>
            </div>

            <div class="coach-highlight-wadah">
                <div class="coach-highlight-pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><?= !empty($prog_unggulan['tenggat']) ? 'Tenggat: ' . tanggal_id($prog_unggulan['tenggat']) : 'Program Aktif Berkelanjutan' ?></span>
                </div>

                <div class="coach-highlight-row">
                    <div class="coach-highlight-pihak">
                        <span class="coach-highlight-nama"><?= e($nama_situs) ?></span>
                        <div class="coach-highlight-logo">
                            <?php if (!empty($url_logo_mitra)): ?>
                                <img src="<?= $url_logo_mitra ?>" alt="Logo">
                            <?php else: ?>
                                P
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="coach-highlight-vs" aria-label="Kemitraan">&amp;</div>

                    <div class="coach-highlight-pihak">
                        <div class="coach-highlight-logo" style="background:#EAF1FC;color:#1555B5;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                        </div>
                        <span class="coach-highlight-nama"><?= e($prog_unggulan['lokasi'] ?? 'Komunitas Mitra') ?></span>
                    </div>
                </div>

                <div style="margin-top: 1rem; width: 100%; max-width: 440px;">
                    <div style="font-weight: 700; color: var(--admin-teks); font-size: 1rem; margin-bottom: 0.25rem;">
                        <?= e($prog_unggulan['judul'] ?? 'Inisiatif Pemberdayaan Komunitas') ?>
                    </div>
                    <div style="font-size: 0.8125rem; color: var(--admin-teks-sub);">
                        Terkumpul <strong><?= rupiah($progres_unggulan['terkumpul'] ?? 0) ?></strong> dari target <?= rupiah($prog_unggulan['target_dana'] ?? 0) ?> (<?= (int)($progres_unggulan['persen'] ?? 0) ?>%)
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Tabel Pesanan Terbaru (Standings style) -->
        <section class="coach-kartu" aria-labelledby="judul-pesanan-terbaru">
            <div class="coach-kartu-kepala">
                <h2 id="judul-pesanan-terbaru" class="coach-kartu-judul">Pesanan Terbaru</h2>
                <a href="<?= url('admin/pesanan.php') ?>" class="coach-kartu-tautan">Lihat Semua &rarr;</a>
            </div>

            <?php if (empty($pesanan_terbaru)): ?>
                <p style="padding: 1.5rem; color: var(--admin-teks-sub); text-align: center;">Belum ada transaksi pesanan.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="coach-tabel" aria-label="Daftar Pesanan Terbaru">
                        <thead>
                            <tr>
                                <th style="width: 28px;">#</th>
                                <th>Pemesan</th>
                                <th>Produk</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pesanan_terbaru as $idx => $p): ?>
                                <tr>
                                    <td class="coach-peringkat"><?= $idx + 1 ?></td>
                                    <td>
                                        <strong style="color: var(--admin-teks);"><?= e($p['nama_pemesan']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--admin-teks-sub);">
                                            <?= e($p['kode']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; font-size: 0.8125rem;"><?= e($p['nama_produk']) ?></div>
                                        <span style="font-size: 0.75rem; color: var(--admin-teks-sub);"><?= (int)$p['jumlah'] ?> pcs</span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--admin-teal);">
                                        <?= rupiah($p['total']) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status status-<?= e($p['status']) ?>">
                                            <?= e($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Kolom Kanan: Statistik Segmented, 4 KPI Grid, Callout Banner -->
    <div class="coach-kolom">
        <!-- 3. Overview Stats (Games statistic style) -->
        <section class="coach-kartu" aria-labelledby="judul-statistik-ringkasan">
            <div class="coach-kartu-kepala">
                <h2 id="judul-statistik-ringkasan" class="coach-kartu-judul">Statistik Transaksi</h2>
                <a href="<?= url('transparansi.php') ?>" target="_blank" rel="noopener" class="coach-kartu-tautan">Transparansi &rarr;</a>
            </div>

            <div class="coach-stat-bar-wadah">
                <div class="coach-stat-bar" role="progressbar" aria-label="Statistik Penerimaan">
                    <div class="coach-segmen-teal" style="width: <?= $pct_order ?>%;" title="Penjualan Dibayar"></div>
                    <div class="coach-segmen-mint" style="width: <?= $pct_donasi ?>%;" title="Donasi Berhasil"></div>
                    <div class="coach-segmen-coral" style="width: <?= $pct_menunggu ?>%;" title="Perlu Verifikasi"></div>
                </div>
            </div>

            <div class="coach-stat-angka-grid">
                <div class="coach-stat-angka-item">
                    <span class="label">Pesanan</span>
                    <span class="nilai"><?= (int)$rekap_penjualan['jumlah'] ?></span>
                </div>
                <div class="coach-stat-angka-item">
                    <span class="label">Donasi</span>
                    <span class="nilai"><?= (int)$rekap_donasi['jumlah'] ?></span>
                </div>
                <div class="coach-stat-angka-item">
                    <span class="label">Menunggu</span>
                    <span class="nilai" style="color: #F27289;"><?= (int)($rekap_pesanan_menunggu['jumlah'] + $rekap_donasi_menunggu['jumlah']) ?></span>
                </div>
                <div class="coach-stat-angka-item">
                    <span class="label">Program</span>
                    <span class="nilai"><?= count($daftar_program_aktif) ?></span>
                </div>
            </div>
        </section>

        <!-- 4. 2x2 Metric KPI Grid (CoachPro 4 Stats Cards) -->
        <section class="coach-kpi-grid" aria-label="Metrik Utama">
            <!-- Card 1: Penjualan Produk (Lavender) -->
            <div class="coach-kpi-kartu">
                <div class="coach-kpi-ikon coach-kpi-lavender" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                </div>
                <div>
                    <div class="coach-kpi-label">Total Penjualan</div>
                    <div class="coach-kpi-nilai"><?= rupiah($rekap_penjualan['total']) ?></div>
                    <div class="coach-kpi-sub"><?= (int)$rekap_penjualan['jumlah'] ?> transaksi terbayar</div>
                </div>
            </div>

            <!-- Card 2: Donasi Berhasil (Pink) -->
            <div class="coach-kpi-kartu">
                <div class="coach-kpi-ikon coach-kpi-pink" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                </div>
                <div>
                    <div class="coach-kpi-label">Donasi Berhasil</div>
                    <div class="coach-kpi-nilai"><?= rupiah($rekap_donasi['total']) ?></div>
                    <div class="coach-kpi-sub"><?= (int)$rekap_donasi['jumlah'] ?> donasi disalurkan</div>
                </div>
            </div>

            <!-- Card 3: Pesanan Menunggu (Amber) -->
            <div class="coach-kpi-kartu">
                <div class="coach-kpi-ikon coach-kpi-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div>
                    <div class="coach-kpi-label">Pesanan Menunggu</div>
                    <div class="coach-kpi-nilai"><?= (int)$rekap_pesanan_menunggu['jumlah'] ?> Item</div>
                    <div class="coach-kpi-sub">Perlu cek mutasi transfer</div>
                </div>
            </div>

            <!-- Card 4: Donasi Menunggu (Cyan) -->
            <div class="coach-kpi-kartu">
                <div class="coach-kpi-ikon coach-kpi-cyan" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="coach-kpi-label">Donasi Menunggu</div>
                    <div class="coach-kpi-nilai"><?= (int)$rekap_donasi_menunggu['jumlah'] ?> Donasi</div>
                    <div class="coach-kpi-sub">Perlu verifikasi manual</div>
                </div>
            </div>
        </section>

        <!-- 5. Callout Banner Teal Gradien (Setup training equivalent) -->
        <div class="coach-banner">
            <div>
                <div class="coach-banner-tag">Aksi Cepat</div>
                <h3 class="coach-banner-judul">Verifikasi Pembayaran &amp; Penyaluran Donasi</h3>
                <p class="coach-banner-sub">Pastikan transfer manual segera dikonfirmasi agar stok diperbarui dan amanah donasi tersalurkan transparan.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="<?= url('admin/pesanan.php') ?>" class="coach-banner-tombol">
                    Verifikasi Pesanan &rarr;
                </a>
                <a href="<?= url('admin/donasi.php') ?>" class="coach-banner-tombol" style="background: rgba(255,255,255,0.15); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.3);">
                    Verifikasi Donasi &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 6. Tabel Donasi Terbaru (Full Width Card) -->
<section class="coach-kartu" aria-labelledby="judul-donasi-terbaru">
    <div class="coach-kartu-kepala">
        <h2 id="judul-donasi-terbaru" class="coach-kartu-judul">Donasi Terbaru</h2>
        <a href="<?= url('admin/donasi.php') ?>" class="coach-kartu-tautan">Kelola Semua Donasi &rarr;</a>
    </div>

    <?php if (empty($donasi_terbaru)): ?>
        <p style="padding: 1.5rem; color: var(--admin-teks-sub); text-align: center;">Belum ada catatan donasi masuk.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="coach-tabel" aria-label="Daftar Donasi Terbaru">
                <thead>
                    <tr>
                        <th style="width: 28px;">#</th>
                        <th>Kode Donasi</th>
                        <th>Donatur</th>
                        <th>Program Sosial</th>
                        <th style="text-align: right;">Nominal</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donasi_terbaru as $idx => $d): ?>
                        <tr>
                            <td class="coach-peringkat"><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color: var(--admin-teks);"><?= e($d['kode']) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= !empty($d['anonim']) ? 'Anonim (' . e($d['nama_donatur']) . ')' : e($d['nama_donatur']) ?></div>
                                <span style="font-size: 0.75rem; color: var(--admin-teks-sub);"><?= tanggal_id($d['dibuat_pada']) ?></span>
                            </td>
                            <td>
                                <span style="font-size: 0.8125rem; font-weight: 500;"><?= e($d['nama_program']) ?></span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--admin-teal);">
                                <?= rupiah($d['jumlah']) ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-status status-<?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
