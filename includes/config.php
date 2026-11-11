<?php
/**
 * Konfigurasi PRODUKSI - Pekarangan Institute (pekarangan-institute.my.id)
 * Berkas ini tidak berisi rahasia. Kunci Service Account disimpan di LUAR public_html.
 */

define('FIREBASE_DB_URL', 'https://pekarangan-institute-default-rtdb.asia-southeast1.firebasedatabase.app');

// Letak kunci: /home/<username>/kunci-firebase.json  (satu tingkat di atas public_html)
define('FIREBASE_KUNCI_PATH', __DIR__ . '/kunci-firebase.json');

define('BASE_URL', 'https://pekarangan-institute.my.id');
define('APP_ENV', 'produksi');

define('BATAS_UNGGAH_GAMBAR', 2 * 1024 * 1024);
define('BATAS_UNGGAH_BERKAS', 5 * 1024 * 1024);

date_default_timezone_set('Asia/Jakarta');

if (APP_ENV === 'lokal') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
