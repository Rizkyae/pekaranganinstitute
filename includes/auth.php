<?php
/**
 * Otentikasi dan Manajemen Sesi Aman
 * Pekarangan Institute
 */

/**
 * Memulai sesi dengan konfigurasi cookie aman dan proteksi kedaluwarsa
 *
 * @return void
 */
function mulai_sesi_aman(): void
{
    $adalah_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (session_status() === PHP_SESSION_NONE) {
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.use_only_cookies', '1');
        if ($adalah_https) {
            @ini_set('session.cookie_secure', '1');
        }

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 7200, // 2 jam
                'path'     => '/',
                'domain'   => '',
                'secure'   => $adalah_https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        } else {
            @session_start();
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        // Periksa kedaluwarsa sesi akibat tidak aktif selama 2 jam
        $batas_inaktif = 7200;
        if (isset($_SESSION['terakhir_aktif']) && (time() - $_SESSION['terakhir_aktif'] > $batas_inaktif)) {
            $pernah_admin = !empty($_SESSION['admin_id']);
            session_unset();
            session_destroy();
            if (!headers_sent()) {
                session_set_cookie_params([
                    'lifetime' => 7200,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $adalah_https,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                session_start();
                session_regenerate_id(true);
            }
            if ($pernah_admin) {
                $_SESSION['flash'] = ['tipe' => 'kuning', 'pesan' => 'Sesi berakhir, silakan masuk lagi.'];
            }
        }
        $_SESSION['terakhir_aktif'] = time();
    }
}

/**
 * Memeriksa apakah admin sedang login
 *
 * @return bool
 */
function admin_sedang_login(): bool
{
    mulai_sesi_aman();
    return !empty($_SESSION['admin_id']);
}

/**
 * Memastikan pengguna sudah login sebagai admin, mengalihkan jika belum
 *
 * @return void
 */
function wajib_login(): void
{
    mulai_sesi_aman();
    if (!admin_sedang_login()) {
        if (empty($_SESSION['flash'])) {
            pesan_flash('merah', 'Silakan masuk terlebih dahulu.');
        }
        $target = '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($uri)) {
            $path = parse_url($uri, PHP_URL_PATH) ?? '';
            if (str_contains($path, 'admin/') && !str_contains($path, 'login.php') && !str_contains($path, 'logout.php')) {
                $file = basename($path);
                // Jika mengakses folder /admin/ langsung atau tanpa berkas .php, arahkan ke index.php
                if ($file === 'admin' || $file === '' || !str_ends_with($file, '.php')) {
                    $file = 'index.php';
                }
                $target = 'admin/' . $file;
                $query = parse_url($uri, PHP_URL_QUERY);
                if (!empty($query)) {
                    $target .= '?' . $query;
                }
            }
        }
        // Jika target adalah admin/index.php, tidak perlu ?next= karena default login adalah index.php
        $query_next = ($target !== '' && $target !== 'admin/index.php') ? '?next=' . urlencode($target) : '';
        alihkan('admin/login.php' . $query_next);
        exit;
    }
}
