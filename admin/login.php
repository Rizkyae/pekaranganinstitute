<?php
/**
 * Halaman Login Administrator - Pekarangan Institute
 * Alamat login admin rahasia (/admin/login.php).
 * Dilengkapi proteksi brute force (5x gagal -> kunci 60 detik), CSRF token,
 * pencegahan open-redirect, dan meta noindex/nofollow.
 */

require_once __DIR__ . '/../includes/fungsi.php';

mulai_sesi_aman();

// Validasi parameter ?next= (cegah open redirect ke luar situs)
$next_mentah = trim($_GET['next'] ?? $_POST['next'] ?? '');
$next = '';
if ($next_mentah !== '' && !preg_match('#^(?:[a-z]+:)?//#i', $next_mentah) && !str_starts_with($next_mentah, 'javascript:') && !str_contains($next_mentah, "\n") && !str_contains($next_mentah, "\r")) {
    $next = $next_mentah;
    // Sanitasi jika parameter next bernilai 'admin/admin', 'admin', atau 'admin/'
    if ($next === 'admin/admin' || $next === 'admin' || $next === 'admin/') {
        $next = 'admin/index.php';
    }
}

// Jika sudah login, langsung alihkan ke halaman yang dituju atau dashboard admin
if (admin_sedang_login()) {
    alihkan($next !== '' ? $next : 'admin/index.php');
}

// Cek apakah ada akun administrator terdaftar
$jumlah_admin = admin_hitung();
$belum_ada_admin = ($jumlah_admin === 0);

// Cek penguncian akibat brute force
$sekarang = time();
$waktu_terkunci = (int)($_SESSION['login_terkunci_sampai'] ?? 0);
$terkunci = $waktu_terkunci > $sekarang;
$sisa_kunci = $terkunci ? ($waktu_terkunci - $sekarang) : 0;

$error = '';
$email = '';
$flash = pesan_flash();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($terkunci) {
        $error = "Terlalu banyak percobaan gagal. Silakan tunggu {$sisa_kunci} detik sebelum mencoba kembali.";
    } elseif (!cek_csrf()) {
        $error = "Permintaan tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $admin = null;
        if ($email !== '' && $password !== '') {
            $admin = admin_by_email($email);
        }

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Berhasil login: reset proteksi brute force
            unset($_SESSION['login_gagal_hitung'], $_SESSION['login_terkunci_sampai']);

            // Regenerasi ID sesi untuk mencegah session fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['terakhir_aktif'] = time();

            pesan_flash('hijau', 'Selamat datang kembali, ' . $admin['nama'] . '! Anda sekarang berada di panel pengelola web.');
            alihkan($next !== '' ? $next : 'admin/index.php');
        } else {
            // Gagal login: tingkatkan penghitung kegagalan
            $hitung = (int)($_SESSION['login_gagal_hitung'] ?? 0) + 1;
            $_SESSION['login_gagal_hitung'] = $hitung;

            if ($hitung >= 5) {
                $_SESSION['login_terkunci_sampai'] = time() + 60;
                $_SESSION['login_gagal_hitung'] = 0;
                $error = "Terlalu banyak percobaan gagal. Silakan tunggu 60 detik sebelum mencoba kembali.";
            } else {
                $error = "Email atau password salah. Jika ingin mendaftar atau menyetel ulang, silakan klik tombol 'Login sebagai admin baru' di bawah.";
            }
        }
    }
}

$nama_situs = ambil_pengaturan('nama_situs', 'Pekarangan Institute');
$url_logo_mitra = url_logo_mitra();
$url_logo_instansi = url_logo_instansi();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Admin - <?= e($nama_situs) ?></title>
    <meta name="robots" content="noindex,nofollow">

    <?php if (!empty($url_logo_mitra)): ?>
        <link rel="icon" type="image/png" href="<?= $url_logo_mitra ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php
    $v_style_lgn = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
    $v_admin_lgn = file_exists(__DIR__ . '/../assets/css/admin.css') ? filemtime(__DIR__ . '/../assets/css/admin.css') : time();
    ?>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $v_style_lgn ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=<?= $v_admin_lgn ?>">
</head>
<body class="halaman-auth">
    <div class="kartu-auth">
        <div class="kartu-auth-kepala">
            <?php if (!empty($url_logo_mitra) || !empty($url_logo_instansi)): ?>
                <div class="logo-pasangan">
                    <?php if (!empty($url_logo_mitra)): ?>
                        <img src="<?= $url_logo_mitra ?>" alt="<?= e($nama_situs) ?>" class="logo-item logo-mitra">
                    <?php endif; ?>
                    <?php if (!empty($url_logo_mitra) && !empty($url_logo_instansi)): ?>
                        <span class="logo-pemisah" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?php if (!empty($url_logo_instansi)): ?>
                        <img src="<?= $url_logo_instansi ?>" alt="Logo Instansi Mitra" class="logo-item logo-instansi">
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="kartu-auth-logo">P</div>
            <?php endif; ?>
            <h1 class="kartu-auth-judul">Masuk Admin</h1>
            <p class="kartu-auth-sub">Panel Pengelolaan <?= e($nama_situs) ?></p>
        </div>

        <!-- Banner jika belum ada admin terdaftar -->
        <?php if ($belum_ada_admin): ?>
            <div class="banner-peringatan jarak-bawah-md teks-sm">
                <div>
                    <strong>Setup Akun Diperlukan:</strong> Belum ada akun administrator yang dibuat pada sistem ini.
                    <div class="jarak-atas-xs">
                        <a href="<?= url('admin/setup.php') ?>" class="tombol tombol-utama tombol-sm">
                            Login sebagai admin baru &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pesan Flash -->
        <?php if ($flash): ?>
            <?php
            $kelas_flash = match ($flash['tipe']) {
                'hijau', 'sukses' => 'kotak-sukses',
                'kuning', 'peringatan' => 'kotak-peringatan',
                default => 'kotak-error',
            };
            ?>
            <div class="<?= $kelas_flash ?> jarak-bawah-md" role="alert">
                <?= e($flash['pesan']) ?>
            </div>
        <?php endif; ?>

        <!-- Pesan Error Login -->
        <?php if (!empty($error)): ?>
            <div class="kotak-error jarak-bawah-md" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= url('admin/login.php' . ($next !== '' ? '?next=' . urlencode($next) : '')) ?>" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
            <?php if ($next !== ''): ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
            <?php endif; ?>

            <!-- Alamat Email -->
            <div class="admin-form-grup">
                <label for="email">Alamat Email Administrator</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="admin-input" 
                    value="<?= e($email) ?>" 
                    required 
                    autocomplete="email" 
                    placeholder="nama@email.com"
                    <?= $terkunci ? 'disabled' : '' ?>
                    autofocus
                >
            </div>

            <!-- Kata Sandi -->
            <div class="admin-form-grup">
                <label for="password">Kata Sandi</label>
                <div class="input-password-wadah">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="admin-input" 
                        required 
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        <?= $terkunci ? 'disabled' : '' ?>
                    >
                    <button type="button" class="tombol-toggle-pwd" aria-label="Tampilkan atau sembunyikan kata sandi">Tampilkan</button>
                </div>
            </div>

            <button type="submit" class="tombol tombol-utama tombol-blok jarak-atas-sm" <?= $terkunci ? 'disabled' : '' ?>>
                Masuk ke Panel Admin
            </button>
        </form>

        <div style="display: flex; align-items: center; text-align: center; margin: 1.25rem 0; color: var(--teks-redup); font-size: 0.8125rem;">
            <span style="flex: 1; border-bottom: 1px solid var(--garis);"></span>
            <span style="padding: 0 0.75rem; font-weight: 500;">atau</span>
            <span style="flex: 1; border-bottom: 1px solid var(--garis);"></span>
        </div>

        <a href="<?= url('admin/setup.php' . (!empty($email) ? '?email=' . urlencode($email) : '')) ?>" class="tombol tombol-sekunder tombol-blok">
            Login sebagai admin baru
        </a>

        <div class="kartu-auth-kaki baris-antara jarak-atas-lg teks-sm">
            <a href="<?= url() ?>">&larr; Kembali ke Beranda</a>
            <span class="teks-redup"><?= e($nama_situs) ?></span>
        </div>
    </div>

    <script src="<?= url('assets/js/app.js') ?>"></script>
    <script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
