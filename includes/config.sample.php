<?php
/**
 * Konfigurasi Sistem Pekarangan Institute (Firebase RTDB) - Mode Produksi
 *
 * PANDUAN PENYIAPAN DI HOSTING (cPanel IdWebHost):
 * 1. Salin berkas ini menjadi 'config.php' di folder yang sama (includes/config.php).
 * 2. Unggah file JSON Service Account Google ke direktori SATU TINGKAT DI ATAS public_html:
 *    Contoh letak: /home/username/kunci-firebase.json
 * 3. Isi konstanta FIREBASE_DB_URL dengan URL Firebase RTDB Anda.
 * 4. Isi konstanta FIREBASE_KUNCI_PATH dengan jalur absolut berkas kunci tersebut.
 * 5. BASE_URL dibiarkan kosong ('') karena website terpasang di akar domain (pekarangan-institute.my.id).
 * 6. APP_ENV disetel ke 'produksi' agar pesan error internal disembunyikan demi keamanan.
 *
 * PENTING: Berkas config.php dan file kunci JSON bersifat rahasia, jangan di-commit ke Git.
 */

// URL Firebase Realtime Database (Region Singapore asia-southeast1)
// Format baku: https://<project-id>-default-rtdb.asia-southeast1.firebasedatabase.app
define('FIREBASE_DB_URL', 'https://pekarangan-institute-default-rtdb.asia-southeast1.firebasedatabase.app');

// Path berkas kunci Service Account Google (format JSON)
// Di cPanel IdWebHost, letakkan di luar public_html (misal: /home/username/kunci-firebase.json)
// atau secara relatif satu tingkat di atas public_html (__DIR__ . '/../../kunci-firebase.json')
define('FIREBASE_KUNCI_PATH', __DIR__ . '/../../kunci-firebase.json');

// URL Dasar Aplikasi (string kosong '' bila di akar domain pekarangan-institute.my.id)
define('BASE_URL', '');

// Lingkungan Aplikasi: 'produksi' (sembunyikan error) atau 'lokal' (tampilkan error)
define('APP_ENV', 'produksi');

// Batas Ukuran Unggahan Berkas (dalam satuan byte)
define('BATAS_UNGGAH_GAMBAR', 2 * 1024 * 1024); // 2 MB
define('BATAS_UNGGAH_BERKAS', 5 * 1024 * 1024); // 5 MB

// Zona Waktu Baku
date_default_timezone_set('Asia/Jakarta');

// Penanganan Tampilan Error Sistem
if (APP_ENV === 'lokal') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
