<?php
/**
 * Login sebagai Admin Baru / Pendaftaran Administrator - Pekarangan Institute (Firebase RTDB)
 * Memungkinkan inisialisasi atau pembuatan akun admin baru dan langsung mengarahkan ke dashboard.
 */

require_once __DIR__ . '/../includes/fungsi.php';

mulai_sesi_aman();

// Jika admin sudah dalam kondisi login, langsung arahkan ke dashboard admin
if (admin_sedang_login()) {
    alihkan('admin/index.php');
    exit;
}

$jumlah_admin = admin_hitung();

// Keamanan: setelah akun admin ada, halaman ini ditutup di server produksi
// (kalau tidak, siapa pun bisa menimpa sandi admin lewat form ini).
if ($jumlah_admin > 0 && (!defined('APP_ENV') || APP_ENV !== 'lokal')) {
    pesan_flash('kuning', 'Akun administrator sudah terdaftar. Silakan masuk.');
    alihkan('admin/login.php');
    exit;
}
$errors = [];
$nama = '';
$email = trim($_GET['email'] ?? '');
$flash = pesan_flash();

// Proses Muat Data Contoh (Hanya berlaku di lingkungan lokal)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_isi_seed']) && defined('APP_ENV') && APP_ENV === 'lokal') {
    if (!cek_csrf()) {
        $errors['umum'] = 'Permintaan tidak valid atau sesi telah kedaluwarsa.';
    } else {
        $sukses = data_muat_seed();
        if ($sukses) {
            pesan_flash('hijau', 'Data awal (seed.json) berhasil dimuat ke Firebase RTDB.');
            alihkan('admin/setup.php');
            exit;
        } else {
            $errors['umum'] = 'Gagal memuat data ke Firebase RTDB. Pastikan konfigurasi FIREBASE_DB_URL dan berkas kunci sudah benar.';
        }
    }
}

// Proses Registrasi / Setup Akun Admin Baru
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !isset($_POST['aksi_isi_seed'])) {
    if (!cek_csrf()) {
        $errors['umum'] = 'Permintaan tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $konfirmasi_password = (string)($_POST['konfirmasi_password'] ?? '');

        // Validasi Nama Lengkap
        if ($nama === '') {
            $errors['nama'] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors['nama'] = 'Nama terlalu panjang (maksimal 100 karakter).';
        }

        // Validasi Alamat Email
        if ($email === '') {
            $errors['email'] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format alamat email tidak valid.';
        }

        // Validasi Kata Sandi
        if ($password === '') {
            $errors['password'] = 'Kata sandi wajib diisi.';
        } else {
            $syarat_kurang = [];
            if (strlen($password) < 8) {
                $syarat_kurang[] = 'minimal 8 karakter';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $syarat_kurang[] = 'setidaknya 1 huruf besar (A-Z)';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $syarat_kurang[] = 'setidaknya 1 huruf kecil (a-z)';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $syarat_kurang[] = 'setidaknya 1 angka (0-9)';
            }

            if (!empty($syarat_kurang)) {
                $errors['password'] = 'Kata sandi belum memenuhi aturan: harus mengandung ' . implode(', ', $syarat_kurang) . '.';
            }
        }

        // Validasi Konfirmasi Kata Sandi
        if ($konfirmasi_password === '') {
            $errors['konfirmasi_password'] = 'Ulangi kata sandi wajib diisi.';
        } elseif ($password !== $konfirmasi_password) {
            $errors['konfirmasi_password'] = 'Ulangi kata sandi tidak cocok dengan kata sandi di atas.';
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $admin_ada = admin_by_email($email);

                if ($admin_ada !== null) {
                    // Jika email sudah terdaftar, perbarui kata sandi dan nama
                    $id_admin = $admin_ada['id'];
                    $sukses_update = fb_ubah("admin/{$id_admin}", [
                        'nama'          => $nama,
                        'email'         => $email,
                        'password_hash' => $hash,
                        'diubah_pada'   => date('Y-m-d H:i:s'),
                    ]);

                    if ($sukses_update) {
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $id_admin;
                        $_SESSION['admin_nama'] = $nama;
                        $_SESSION['admin_email'] = $email;
                        $_SESSION['terakhir_aktif'] = time();

                        pesan_flash('hijau', 'Kata sandi berhasil diperbarui dan Anda telah masuk sebagai ' . $nama . '!');
                        alihkan('admin/index.php');
                        exit;
                    } else {
                        $errors['umum'] = 'Gagal memperbarui akun administrator ke Firebase RTDB.';
                    }
                } else {
                    // Daftarkan akun admin baru
                    $id_admin = admin_tambah([
                        'nama'          => $nama,
                        'email'         => $email,
                        'password_hash' => $hash,
                    ]);

                    if ($id_admin !== null) {
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $id_admin;
                        $_SESSION['admin_nama'] = $nama;
                        $_SESSION['admin_email'] = $email;
                        $_SESSION['terakhir_aktif'] = time();

                        pesan_flash('hijau', 'Selamat datang! Akun administrator baru berhasil dibuat dan Anda telah masuk.');
                        alihkan('admin/index.php');
                        exit;
                    } else {
                        $errors['umum'] = 'Gagal menyimpan akun administrator ke Firebase RTDB. Periksa koneksi dan izin service account.';
                    }
                }
            } catch (Throwable $e) {
                $errors['umum'] = pesan_error_db('Gagal memproses data admin', $e);
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
    <title>Login sebagai Admin Baru - <?= e($nama_situs) ?></title>
    <meta name="robots" content="noindex, nofollow">

    <?php if (!empty($url_logo_mitra)): ?>
        <link rel="icon" type="image/png" href="<?= $url_logo_mitra ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php
    $v_style_stp = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
    $v_admin_stp = file_exists(__DIR__ . '/../assets/css/admin.css') ? filemtime(__DIR__ . '/../assets/css/admin.css') : time();
    ?>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $v_style_stp ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=<?= $v_admin_stp ?>">
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
            <h1 class="kartu-auth-judul">Login sebagai Admin Baru</h1>
            <p class="kartu-auth-sub">Daftarkan akun administrator baru untuk langsung masuk ke panel admin</p>
        </div>

        <!-- Pesan Flash -->
        <?php if ($flash): ?>
            <?php
            $kelas_flash = match ($flash['tipe']) {
                'hijau', 'sukses' => 'kotak-sukses',
                'kuning', 'peringatan' => 'kotak-peringatan',
                default => 'kotak-error',
            };
            ?>
            <div class="<?= $kelas_flash ?>" role="alert" style="margin-bottom:1.25rem;">
                <?= e($flash['pesan']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['umum'])): ?>
            <div class="kotak-error" role="alert" style="margin-bottom:1.25rem;">
                <?= e($errors['umum']) ?>
            </div>
        <?php endif; ?>

        <form action="<?= url('admin/setup.php') ?>" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">

            <!-- Nama Lengkap -->
            <div class="admin-form-grup">
                <label for="nama">Nama Lengkap <span class="wajib">*</span></label>
                <input type="text" id="nama" name="nama" class="admin-input" value="<?= e($nama) ?>" required autocomplete="name" placeholder="Nama Administrator" autofocus>
                <?php if (!empty($errors['nama'])): ?>
                    <div class="pesan-error"><?= e($errors['nama']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="admin-form-grup">
                <label for="email">Alamat Email <span class="wajib">*</span></label>
                <input type="email" id="email" name="email" class="admin-input" value="<?= e($email) ?>" required autocomplete="email" placeholder="nama@email.com">
                <?php if (!empty($errors['email'])): ?>
                    <div class="pesan-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Kata Sandi -->
            <div class="admin-form-grup">
                <label for="password">Kata Sandi <span class="wajib">*</span></label>
                <div class="input-password-wadah">
                    <input type="password" id="password" name="password" class="admin-input" required autocomplete="new-password" placeholder="Masukkan kata sandi">
                    <button type="button" class="tombol-toggle-pwd" aria-label="Tampilkan kata sandi">Tampilkan</button>
                </div>

                <!-- Indikator Bar Tingkat Keamanan -->
                <div class="meter-password-wadah" aria-live="polite">
                    <div class="meter-password-track" title="Tingkat Keamanan Kata Sandi">
                        <div class="meter-password-segmen" id="segmen-1"></div>
                        <div class="meter-password-segmen" id="segmen-2"></div>
                        <div class="meter-password-segmen" id="segmen-3"></div>
                    </div>
                    <div class="meter-password-info">
                        <span class="meter-password-label">Kekuatan Kata Sandi:</span>
                        <span id="status-kekuatan-teks" class="meter-password-status status-kekuatan-kosong">Belum diisi</span>
                    </div>
                </div>

                <!-- Checklist Aturan Kata Sandi -->
                <ul class="syarat-password-daftar" aria-label="Aturan Keamanan Kata Sandi">
                    <li class="syarat-password-item" id="syarat-panjang">
                        <span class="syarat-ikon">&bull;</span>
                        <span>Minimal 8 karakter</span>
                    </li>
                    <li class="syarat-password-item" id="syarat-huruf">
                        <span class="syarat-ikon">&bull;</span>
                        <span>Kombinasi huruf besar (A-Z) & huruf kecil (a-z)</span>
                    </li>
                    <li class="syarat-password-item" id="syarat-angka">
                        <span class="syarat-ikon">&bull;</span>
                        <span>Sertakan setidaknya 1 angka (0-9)</span>
                    </li>
                </ul>

                <?php if (!empty($errors['password'])): ?>
                    <div class="pesan-error"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Konfirmasi Password -->
            <div class="admin-form-grup">
                <label for="konfirmasi_password">Ulangi Kata Sandi <span class="wajib">*</span></label>
                <div class="input-password-wadah">
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="admin-input" required autocomplete="new-password" placeholder="Ulangi kata sandi">
                    <button type="button" class="tombol-toggle-pwd" aria-label="Tampilkan kata sandi">Tampilkan</button>
                </div>
                <div id="status-konfirmasi-teks" class="status-konfirmasi-password"></div>
                <?php if (!empty($errors['konfirmasi_password'])): ?>
                    <div class="pesan-error"><?= e($errors['konfirmasi_password']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="tombol tombol-utama tombol-blok jarak-atas-sm">
                Daftar & Masuk ke Panel Admin
            </button>
        </form>

        <div class="kartu-auth-kaki baris-antara jarak-atas-lg teks-sm">
            <a href="<?= url('admin/login.php') ?>">&larr; Masuk dengan akun yang ada</a>
            <a href="<?= url() ?>">Beranda</a>
        </div>
    </div>

    <script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
