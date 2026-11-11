<?php
/**
 * Header Halaman Publik Pekarangan Institute
 * Tata letak tetap sama seperti sebelumnya (Navigasi responsif sidebar-kanan & header-mobile),
 * dilengkapi cache-busting otomatis pada stylesheet dan tampilan modern visual CoachPro.
 */

require_once __DIR__ . '/fungsi.php';

// Pastikan sesi aman aktif dan ambil flash message sebelum ada output HTML
mulai_sesi_aman();
$flash = pesan_flash();

$nama_situs = ambil_pengaturan('nama_situs', 'Pekarangan Institute');
$tagline_situs = ambil_pengaturan('tagline', 'Berbelanja, Berbagi, Berdampak');

$judul_lengkap = !empty($judul_halaman)
    ? e($judul_halaman) . ' - ' . e($nama_situs)
    : e($nama_situs) . ' - ' . e($tagline_situs);

$deskripsi_meta = !empty($deskripsi_halaman)
    ? e($deskripsi_halaman)
    : e(ambil_pengaturan('hero_teks', 'Inisiatif kewirausahaan sosial yang menghubungkan produk hasil pekarangan dengan pemberdayaan dan program sosial kemasyarakatan.'));

$url_saat_ini = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
    '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');

$menu_aktif = $menu_aktif ?? '';
$url_logo_mitra = url_logo_mitra();
$url_logo_instansi = url_logo_instansi();

$adalah_admin_login = admin_sedang_login();
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';

// Cache-busting otomatis berdasarkan waktu ubah file CSS
$v_css = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul_lengkap ?></title>
    <meta name="description" content="<?= $deskripsi_meta ?>">

    <!-- Favicon & Open Graph Gambar -->
    <?php if (!empty($url_logo_mitra)): ?>
        <link rel="icon" href="<?= $url_logo_mitra ?>">
        <meta property="og:image" content="<?= $url_logo_mitra ?>">
    <?php endif; ?>

    <!-- Open Graph Dasar -->
    <meta property="og:title" content="<?= $judul_lengkap ?>">
    <meta property="og:description" content="<?= $deskripsi_meta ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($url_saat_ini) ?>">
    <meta property="og:locale" content="id_ID">

    <!-- Huruf Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Berkas CSS Utama (dengan Cache-Busting Otomatis) -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $v_css ?>">
</head>
<body class="tata-letak-sidebar-kanan">
    <!-- Aksesibilitas: Lewati ke konten -->
    <a href="#konten-utama" class="lompat-konten">Lewati ke konten utama</a>

    <!-- Topbar Mobile & Tablet (Layar < 1024px) -->
    <header class="header-mobile" id="header-mobile">
        <div class="kontainer header-mobile-konten">
            <a href="<?= url() ?>" class="logo-tautan" aria-label="Beranda <?= e($nama_situs) ?>">
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
                    <span class="logo-teks-simbol" aria-hidden="true">&#9851;</span>
                    <span class="logo-teks-nama"><?= e(strtoupper($nama_situs)) ?></span>
                <?php endif; ?>
            </a>

            <div class="header-mobile-kanan">
                <a href="<?= url('donasi.php') ?>" class="tombol tombol-utama tombol-sm tombol-donasi-mobile">
                    Donasi
                </a>
                <button type="button" class="tombol-menu-mobile" id="tombol-menu" aria-expanded="false" aria-controls="sidebar-kanan" aria-label="Buka menu navigasi">
                    <span class="baris-hamburger"></span>
                    <span class="baris-hamburger"></span>
                    <span class="baris-hamburger"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Latar Gelap / Backdrop Overlay (Mobile/Tablet) -->
    <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <!-- Sidebar Navigasi Kanan (Navbar Utama) -->
    <aside class="sidebar-kanan" id="sidebar-kanan" aria-label="Navigasi Samping Kanan">
        <div class="sidebar-kanan-kepala">
            <a href="<?= url() ?>" class="logo-tautan" aria-label="Beranda <?= e($nama_situs) ?>">
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
                    <span class="logo-teks-simbol" aria-hidden="true">&#9851;</span>
                    <div class="logo-info">
                        <span class="logo-teks-nama"><?= e(strtoupper($nama_situs)) ?></span>
                        <span class="logo-teks-tagline"><?= e($tagline_situs) ?></span>
                    </div>
                <?php endif; ?>
            </a>
            <button type="button" class="tombol-sidebar-tutup" id="tombol-sidebar-tutup" aria-label="Tutup navigasi">
                &times;
            </button>
        </div>

        <div class="sidebar-kanan-badan">
            <!-- Navigasi Menu Utama -->
            <nav class="menu-utama" id="menu-utama" aria-label="Menu Utama Halaman">
                <span class="sidebar-label-nav">NAVIGASI HALAMAN</span>
                <ul class="menu-daftar">
                    <li class="menu-item">
                        <a href="<?= url() ?>" class="<?= $menu_aktif === 'beranda' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            <span>Beranda</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?= url('produk.php') ?>" class="<?= $menu_aktif === 'produk' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            <span>Produk</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?= url('program.php') ?>" class="<?= $menu_aktif === 'program' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Program</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?= url('transparansi.php') ?>" class="<?= $menu_aktif === 'transparansi' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <span>Transparansi</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?= url('tentang.php') ?>" class="<?= $menu_aktif === 'tentang' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span>Tentang</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?= url('kontak.php') ?>" class="<?= $menu_aktif === 'kontak' ? 'aktif' : '' ?>">
                            <svg class="menu-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <span>Kontak</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Aksi Donasi -->
            <div class="sidebar-aksi">
                <a href="<?= url('donasi.php') ?>" class="tombol tombol-utama tombol-blok <?= $menu_aktif === 'donasi' ? 'aktif' : '' ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    <span>Donasi Sekarang</span>
                </a>
            </div>

            <?php if ($adalah_admin_login): ?>
                <!-- Modul Admin / Pengelola Halaman Web (Hanya tampil saat sesi admin aktif) -->
                <div class="sidebar-admin-kotak">
                    <div class="sidebar-admin-terautentikasi">
                        <div class="sidebar-admin-info-bar">
                            <span class="lencana lencana-hijau">Admin Aktif</span>
                            <span class="sidebar-admin-nama" title="<?= e($admin_nama) ?>"><?= e($admin_nama) ?></span>
                        </div>
                        <p class="sidebar-admin-ket">Anda sedang login sebagai pengelola situs.</p>
                        <a href="<?= url('admin/index.php') ?>" class="tombol tombol-utama tombol-sm tombol-blok">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            <span>Panel Admin (Kelola Web)</span>
                        </a>
                        <form action="<?= url('admin/logout.php') ?>" method="POST" class="sidebar-admin-keluar-form">
                            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                            <button type="submit" class="tautan-keluar-mini tanpa-loading">
                                &larr; Keluar dari Admin
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-kanan-kaki">
            <p>&copy; <?= date('Y') ?> <?= e($nama_situs) ?></p>
            <p class="sidebar-kaki-sub">Ketahanan Pangan & Komunitas</p>
        </div>
    </aside>

    <!-- Wadah Konten Utama & Footer (Mengisi Sisa Layar di Kiri Sidebar) -->
    <div class="wadah-situs">
        <!-- Konten Utama Halaman -->
        <main id="konten-utama">
            <?php if ($flash): ?>
                <div class="kontainer">
                    <div class="pesan-flash pesan-flash-<?= e($flash['tipe']) ?>" role="alert">
                        <?= e($flash['pesan']) ?>
                    </div>
                </div>
            <?php endif; ?>
