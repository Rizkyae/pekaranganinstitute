<?php
/**
 * Header Panel Admin - Pekarangan Institute
 * Otentikasi wajib, navigasi terstruktur, CSS admin, dan penanganan pesan flash.
 */

require_once __DIR__ . '/fungsi.php';
wajib_login();

$menu_aktif = $menu_aktif ?? 'dashboard';
$judul_halaman = $judul_halaman ?? 'Panel Admin';
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? '';
$flash = pesan_flash();

$nama_situs = ambil_pengaturan('nama_situs', 'Pekarangan Institute');
$url_logo_mitra = url_logo_mitra();
$url_logo_instansi = url_logo_instansi();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($judul_halaman) ?> - Admin <?= e($nama_situs) ?></title>

    <?php if (!empty($url_logo_mitra)): ?>
        <link rel="icon" href="<?= $url_logo_mitra ?>">
    <?php endif; ?>

    <!-- Huruf Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Utama & CSS Khusus Admin (dengan Cache-Busting) -->
    <?php
    $v_style_adm = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
    $v_admin_adm = file_exists(__DIR__ . '/../assets/css/admin.css') ? filemtime(__DIR__ . '/../assets/css/admin.css') : time();
    ?>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $v_style_adm ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=<?= $v_admin_adm ?>">
</head>
<body class="admin-badan">
    <!-- Aksesibilitas: Lewati ke konten utama -->
    <a href="#konten-admin" class="lompat-konten">Lewati ke konten utama</a>

    <div class="admin-wadah">
        <!-- Sidebar Kiri -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-kepala">
                <a href="<?= url('admin/index.php') ?>" class="admin-sidebar-brand" aria-label="Dashboard Admin">
                    <?php if (!empty($url_logo_mitra) || !empty($url_logo_instansi)): ?>
                        <span class="logo-pasangan">
                            <?php if (!empty($url_logo_mitra)): ?>
                                <img src="<?= $url_logo_mitra ?>" alt="<?= e($nama_situs) ?>" class="logo-item logo-mitra">
                            <?php endif; ?>
                            <?php if (!empty($url_logo_mitra) && !empty($url_logo_instansi)): ?>
                                <span class="logo-pemisah" aria-hidden="true"></span>
                            <?php endif; ?>
                            <?php if (!empty($url_logo_instansi)): ?>
                                <img src="<?= $url_logo_instansi ?>" alt="Logo Instansi Mitra" class="logo-item logo-instansi">
                            <?php endif; ?>
                        </span>
                    <?php else: ?>
                        <div class="admin-sidebar-logo">P</div>
                        <div>
                            <div class="admin-sidebar-judul"><?= e($nama_situs) ?></div>
                            <div class="admin-sidebar-subjudul">Panel Admin</div>
                        </div>
                    <?php endif; ?>
                </a>
                <button type="button" class="admin-sidebar-tutup" id="admin-tutup-sidebar" aria-label="Tutup menu">&times;</button>
            </div>

            <nav class="admin-nav">
                <!-- 1. Dashboard -->
                <a href="<?= url('admin/index.php') ?>" class="admin-nav-item <?= $menu_aktif === 'dashboard' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Dashboard</span>
                </a>

                <!-- 2. Produk -->
                <a href="<?= url('admin/produk.php') ?>" class="admin-nav-item <?= $menu_aktif === 'produk' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    <span>Produk</span>
                </a>

                <!-- 3. Program -->
                <a href="<?= url('admin/program.php') ?>" class="admin-nav-item <?= $menu_aktif === 'program' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    <span>Program</span>
                </a>

                <!-- 4. Donasi -->
                <a href="<?= url('admin/donasi.php') ?>" class="admin-nav-item <?= $menu_aktif === 'donasi' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    <span>Donasi</span>
                </a>

                <!-- 5. Pesanan -->
                <a href="<?= url('admin/pesanan.php') ?>" class="admin-nav-item <?= $menu_aktif === 'pesanan' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                    <span>Pesanan</span>
                </a>

                <!-- 6. Laporan -->
                <a href="<?= url('admin/laporan.php') ?>" class="admin-nav-item <?= $menu_aktif === 'laporan' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span>Laporan</span>
                </a>

                <!-- 7. Konten Web -->
                <a href="<?= url('admin/konten.php') ?>" class="admin-nav-item <?= $menu_aktif === 'konten' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Konten Web</span>
                </a>

                <!-- 8. Pengaturan -->
                <a href="<?= url('admin/pengaturan.php') ?>" class="admin-nav-item <?= $menu_aktif === 'pengaturan' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                    <span>Pengaturan</span>
                </a>

                <div class="admin-nav-pisah"></div>

                <!-- 8. Lihat Situs -->
                <a href="<?= url('index.php') ?>" target="_blank" rel="noopener" class="admin-nav-item">
                    <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Lihat Situs</span>
                </a>

                <!-- 9. Keluar (POST + CSRF) -->
                <form action="<?= url('admin/logout.php') ?>" method="POST" class="admin-nav-keluar-form">
                    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                    <button type="submit" class="admin-nav-keluar-tombol tanpa-loading" aria-label="Keluar dari sesi administrator">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </nav>
        </aside>

        <!-- Latar Gelap (Backdrop Mobile) -->
        <div class="admin-backdrop" id="admin-backdrop"></div>

        <!-- Wadah Konten Kanan -->
        <div class="admin-konten-bungkus">
            <!-- Topbar CoachPro (Mobile & Desktop) -->
            <header class="admin-topbar">
                <div class="admin-topbar-kiri">
                    <button type="button" class="admin-topbar-toggle" id="admin-buka-sidebar" aria-label="Buka menu navigasi">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <span class="admin-topbar-judul"><?= e($judul_halaman) ?></span>
                </div>
                <div class="admin-topbar-kanan">
                    <a href="<?= url('index.php') ?>" target="_blank" rel="noopener" class="admin-topbar-ikon-btn" title="Buka Situs Publik">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </a>
                    <a href="<?= url('admin/pesanan.php') ?>" class="admin-topbar-ikon-btn" title="Notifikasi Pesanan">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span class="admin-notifikasi-titik" aria-hidden="true"></span>
                    </a>
                    <div class="admin-profil-kapsul">
                        <div class="admin-profil-avatar" aria-hidden="true">
                            <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                        </div>
                        <span class="admin-profil-nama"><?= e($admin_nama) ?></span>
                    </div>
                </div>
            </header>

            <!-- Konten Utama -->
            <main class="admin-konten" id="konten-admin">
                <!-- Pesan Kilas (Flash) -->
                <?php if ($flash): ?>
                    <?php
                    $kelas_flash = match ($flash['tipe']) {
                        'hijau', 'sukses' => 'kotak-sukses',
                        'kuning', 'peringatan' => 'kotak-peringatan',
                        default => 'kotak-error',
                    };
                    ?>
                    <div class="kontainer" style="padding: 0; margin-bottom: 1.5rem;">
                        <div class="<?= $kelas_flash ?>" role="alert">
                            <?= e($flash['pesan']) ?>
                        </div>
                    </div>
                <?php endif; ?>
