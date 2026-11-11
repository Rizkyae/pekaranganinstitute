<?php
/**
 * Uji Koneksi Firebase Realtime Database - Pekarangan Institute
 * Wajib login admin. Memeriksa konfigurasi, token OAuth2, dan uji tulis/baca/hapus node /_tes.
 */

$menu_aktif = 'pengaturan';
$judul_halaman = 'Uji Koneksi Firebase';

require_once __DIR__ . '/../includes/admin-header.php';

// Hasil tes
$hasil_tes = [];
$semua_sukses = true;

// 1. Uji Ekstensi PHP cURL
$curl_aktif = extension_loaded('curl');
$hasil_tes[] = [
    'nama'   => 'Ekstensi PHP cURL',
    'status' => $curl_aktif,
    'detail' => $curl_aktif ? 'Aktif (' . (curl_version()['version'] ?? 'versi terdeteksi') . ')' : 'Tidak aktif. Aktifkan extension=curl di php.ini.',
];
if (!$curl_aktif) $semua_sukses = false;

// 2. Uji Ekstensi PHP OpenSSL
$openssl_aktif = extension_loaded('openssl');
$hasil_tes[] = [
    'nama'   => 'Ekstensi PHP OpenSSL',
    'status' => $openssl_aktif,
    'detail' => $openssl_aktif ? 'Aktif (' . OPENSSL_VERSION_TEXT . ')' : 'Tidak aktif. Aktifkan extension=openssl di php.ini.',
];
if (!$openssl_aktif) $semua_sukses = false;

// 3. Uji Konfigurasi FIREBASE_DB_URL
$db_url = defined('FIREBASE_DB_URL') ? FIREBASE_DB_URL : '';
$url_valid = !empty($db_url) && filter_var($db_url, FILTER_VALIDATE_URL) && str_contains($db_url, 'firebasedatabase.app');
$hasil_tes[] = [
    'nama'   => 'Konfigurasi URL Database (FIREBASE_DB_URL)',
    'status' => $url_valid,
    'detail' => $url_valid ? e($db_url) : 'URL tidak valid atau belum diisi di includes/config.php (harus https://<project-id>-default-rtdb.<region>.firebasedatabase.app)',
];
if (!$url_valid) $semua_sukses = false;

// 4. Uji Berkas Kunci Service Account (FIREBASE_KUNCI_PATH)
$kunci_path = defined('FIREBASE_KUNCI_PATH') ? FIREBASE_KUNCI_PATH : '';
$kunci_ada = !empty($kunci_path) && file_exists($kunci_path) && is_readable($kunci_path);
$email_service_account = '';
$kunci_valid = false;

if ($kunci_ada) {
    $konten_kunci = @file_get_contents($kunci_path);
    $data_kunci = json_decode($konten_kunci, true);
    if (is_array($data_kunci) && !empty($data_kunci['client_email']) && !empty($data_kunci['private_key'])) {
        $kunci_valid = true;
        $email_service_account = $data_kunci['client_email'];
    }
}

$hasil_tes[] = [
    'nama'   => 'Berkas Kunci Service Account (JSON)',
    'status' => $kunci_valid,
    'detail' => $kunci_valid
        ? 'Ditemukan dan valid. Client Email: ' . e($email_service_account)
        : ($kunci_ada
            ? 'Berkas ditemukan tetapi isi JSON tidak memuat client_email atau private_key yang valid.'
            : 'Berkas kunci tidak ditemukan di path: ' . e($kunci_path) . '. Pastikan letak berkas sesuai di includes/config.php.'),
];
if (!$kunci_valid) $semua_sukses = false;

// 5. Uji Pembuatan Token OAuth2 (JWT RS256)
$token = null;
if ($semua_sukses) {
    $token = fb_token();
    $token_sukses = !empty($token);
    $hasil_tes[] = [
        'nama'   => 'Otentikasi OAuth2 (Google Token)',
        'status' => $token_sukses,
        'detail' => $token_sukses
            ? 'Token Bearer berhasil diperoleh dari Google OAuth2 (tersimpan di cache aman).'
            : 'Gagal menukarkan JWT RS256 ke Google OAuth2. Periksa kecocokan jam server, izin service account, atau log error.',
    ];
    if (!$token_sukses) $semua_sukses = false;
}

// 6. Uji Tulis, Baca, dan Hapus Node Pengujian (/_tes)
if ($semua_sukses) {
    $id_tes = 'tes_' . time() . '_' . random_int(1000, 9999);
    $data_tes = [
        'pesan'  => 'Tes koneksi sistem Pekarangan Institute',
        'waktu'  => date('Y-m-d H:i:s'),
        'penulis'=> $admin_nama,
    ];

    // Simpan ke /_tes/{id_tes}
    $simpan_tes = fb_simpan("_tes/{$id_tes}", $data_tes);
    $baca_tes = ($simpan_tes !== false) ? fb_ambil("_tes/{$id_tes}") : null;
    $baca_cocok = is_array($baca_tes) && ($baca_tes['pesan'] ?? '') === $data_tes['pesan'];

    // Hapus kembali
    if ($simpan_tes !== false) {
        fb_hapus("_tes/{$id_tes}");
    }

    $hasil_tes[] = [
        'nama'   => 'Uji Baca & Tulis REST RTDB (Node /_tes)',
        'status' => $baca_cocok,
        'detail' => $baca_cocok
            ? 'Berhasil menulis data uji, membaca kembali secara identik, lalu menghapus node /_tes/' . e($id_tes) . '.'
            : 'Gagal menulis atau membaca data pada RTDB. Pastikan Rules Firebase RTDB atau izin Service Account telah aktif.',
    ];
    if (!$baca_cocok) $semua_sukses = false;
}
?>

<main class="admin-konten" id="konten-admin">
    <div class="admin-kepala-halaman">
        <div>
            <h1 class="admin-judul-halaman">Uji Koneksi Firebase Realtime Database</h1>
            <p class="admin-subjudul-halaman">Pemeriksaan status komunikasi REST API, kredensial service account, dan pembacaan/penulisan data.</p>
        </div>
        <div>
            <a href="<?= url('admin/pengaturan.php') ?>" class="tombol tombol-sekunder">
                &larr; Kembali ke Pengaturan
            </a>
        </div>
    </div>

    <!-- Ringkasan Status Keseluruhan -->
    <?php if ($semua_sukses): ?>
        <div class="kotak-sukses" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>
                <strong>Koneksi Firebase RTDB Berfungsi Normal!</strong>
                <p style="margin: 0.25rem 0 0; font-size: 0.875rem;">Seluruh modul (cURL, OpenSSL, Token OAuth2, dan REST API RTDB) berjalan dengan lancar.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="kotak-error" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>
                <strong>Ditemukan Masalah pada Koneksi Firebase</strong>
                <p style="margin: 0.25rem 0 0; font-size: 0.875rem;">Silakan periksa detail pemeriksaan di bawah dan ikuti saran perbaikan yang ditampilkan.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Pemeriksaan -->
    <div class="admin-kartu">
        <h2 style="font-size: 1.125rem; font-weight: 600; color: var(--biru-tua); margin-bottom: 1rem;">
            Hasil Diagnosa Komponen Koneksi
        </h2>

        <div class="admin-tabel-wadah">
            <table class="admin-tabel">
                <thead>
                    <tr>
                        <th style="width: 250px;">Komponen</th>
                        <th style="width: 120px;">Status</th>
                        <th>Keterangan / Diagnosa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil_tes as $tes): ?>
                        <tr>
                            <td><strong><?= e($tes['nama']) ?></strong></td>
                            <td>
                                <?php if ($tes['status']): ?>
                                    <span class="lencana lencana-sukses">Sukses</span>
                                <?php else: ?>
                                    <span class="lencana lencana-error">Gagal</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.875rem; color: <?= $tes['status'] ? 'var(--teks)' : 'var(--merah)' ?>;">
                                <?= $tes['detail'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panduan Pemecahan Masalah -->
    <?php if (!$semua_sukses): ?>
        <div class="admin-kartu" style="margin-top: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: var(--biru-tua); margin-bottom: 0.75rem;">
                Saran Pemecahan Masalah (Troubleshooting)
            </h2>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem; color: var(--teks); line-height: 1.6;">
                <?php if (!$curl_aktif || !$openssl_aktif): ?>
                    <li><strong>Ekstensi PHP:</strong> Pastikan baris <code>extension=curl</code> dan <code>extension=openssl</code> aktif pada berkas <code>php.ini</code> server Anda, kemudian muat ulang web server.</li>
                <?php endif; ?>
                <?php if (!$url_valid): ?>
                    <li><strong>URL Database:</strong> Periksa konstanta <code>FIREBASE_DB_URL</code> di <code>includes/config.php</code>. Format baku: <code>https://&lt;project-id&gt;-default-rtdb.asia-southeast1.firebasedatabase.app</code>.</li>
                <?php endif; ?>
                <?php if (!$kunci_valid): ?>
                    <li><strong>Berkas Kunci Service Account:</strong> Pastikan Anda telah mengunduh kunci baru berformat JSON dari <em>Firebase Console &rarr; Project Settings &rarr; Service Accounts &rarr; Generate new private key</em>, lalu tentukan lokasi jalurnya pada <code>FIREBASE_KUNCI_PATH</code> di <code>includes/config.php</code>.</li>
                <?php endif; ?>
                <?php if (isset($token_sukses) && !$token_sukses): ?>
                    <li><strong>Sinkronisasi Waktu Server:</strong> Pembuatan token JWT Google bergantung pada waktu sistem (NTP). Pastikan jam pada server tidak mengalami deviasi waktu lebih dari beberapa detik.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="margin-top: 1.5rem;">
        <a href="<?= url('admin/tes-koneksi.php') ?>" class="tombol tombol-utama">
            Uji Ulang Koneksi Sekarang
        </a>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
