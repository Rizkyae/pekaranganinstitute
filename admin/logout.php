<?php
/**
 * Logout Administrator - Pekarangan Institute
 * Mengakhiri sesi admin secara aman lewat POST + verifikasi CSRF.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && cek_csrf()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();

    // Mulai sesi baru untuk menyimpan pesan kilas informasi keluar
    mulai_sesi_aman();
    session_regenerate_id(true);
    pesan_flash('hijau', 'Anda telah berhasil keluar dari sistem.');
    alihkan('admin/login.php');
} else {
    // Jika diakses langsung tanpa POST atau token tidak valid
    pesan_flash('merah', 'Permintaan keluar tidak valid.');
    alihkan('admin/index.php');
}
