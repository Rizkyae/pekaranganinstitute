<?php
/**
 * Aksi Pemrosesan Laporan Transparansi - Pekarangan Institute (Firebase RTDB)
 * Menangani tambah, ubah, dan hapus berkas laporan pertanggungjawaban publik via data.php.
 * Memastikan pembersihan berkas dokumen di server saat laporan dihapus atau diganti.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && permintaan_post_melebihi_batas()) {
    pesan_flash('merah', 'Permintaan melebihi batas ukuran yang diizinkan server. Coba unggah berkas yang lebih kecil atau sesuaikan post_max_size pada PHP.');
    alihkan('admin/laporan.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cek_csrf()) {
    pesan_flash('merah', 'Permintaan tidak valid atau token keamanan kedaluwarsa.');
    alihkan('admin/laporan.php');
}

$aksi = trim($_POST['aksi'] ?? '');

switch ($aksi) {
    case 'tambah':
    case 'ubah':
        $adalah_edit = ($aksi === 'ubah');
        $id = trim((string)($_POST['id'] ?? ''));

        if ($adalah_edit && $id === '') {
            pesan_flash('merah', 'ID laporan tidak valid.');
            alihkan('admin/laporan.php');
        }

        $judul = trim($_POST['judul'] ?? '');
        $periode = trim($_POST['periode'] ?? '');
        $ringkasan = trim($_POST['ringkasan'] ?? '');
        $status = trim($_POST['status'] ?? 'tampil');

        $errors = [];

        if ($judul === '') {
            $errors['judul'] = 'Judul laporan wajib diisi.';
        } elseif (mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul terlalu panjang (maksimal 200 karakter).';
        }

        if ($periode === '') {
            $errors['periode'] = 'Periode laporan wajib diisi.';
        } elseif (mb_strlen($periode) > 100) {
            $errors['periode'] = 'Periode terlalu panjang (maksimal 100 karakter).';
        }

        if ($ringkasan === '') {
            $errors['ringkasan'] = 'Ringkasan laporan wajib diisi.';
        }

        if (!in_array($status, ['tampil', 'draf'], true)) {
            $errors['status'] = 'Status publikasi tidak valid.';
        }

        // Ambil data laporan lama jika edit
        $laporan_lama = null;
        if ($adalah_edit) {
            $laporan_lama = laporan_ambil($id);
            if (!$laporan_lama) {
                pesan_flash('merah', 'Laporan lama tidak ditemukan.');
                alihkan('admin/laporan.php');
            }
        }

        $nama_berkas = $laporan_lama['berkas'] ?? '';
        $berkas_baru_diunggah = null;
        $berkas_lama_dihapus = null;

        // Penanganan Unggahan Berkas
        if (!empty($_FILES['berkas']['name'])) {
            $hasil_unggah = unggah_berkas($_FILES['berkas'], 'laporan');
            if (!$hasil_unggah['sukses']) {
                $errors['berkas'] = $hasil_unggah['pesan'];
            } else {
                $berkas_baru_diunggah = $hasil_unggah['nama'];
                if (!empty($nama_berkas) && empty($laporan_lama['is_contoh'])) {
                    $berkas_lama_dihapus = $nama_berkas;
                }
                $nama_berkas = $berkas_baru_diunggah;
            }
        } elseif (!$adalah_edit) {
            $errors['berkas'] = 'Berkas dokumen laporan wajib diunggah.';
        }

        if (!empty($errors)) {
            if ($berkas_baru_diunggah !== null) hapus_berkas_unggahan($berkas_baru_diunggah);
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $tujuan = $adalah_edit ? 'admin/laporan-form.php?id=' . urlencode($id) : 'admin/laporan-form.php';
            alihkan($tujuan);
        }

        try {
            $data_simpan = [
                'judul'     => $judul,
                'periode'   => $periode,
                'ringkasan' => $ringkasan,
                'berkas'    => $nama_berkas,
                'status'    => $status,
            ];

            if ($adalah_edit) {
                $tersimpan = laporan_ubah($id, $data_simpan);
            } else {
                $data_simpan['is_contoh'] = 0;
                $tersimpan = laporan_tambah($data_simpan) !== null;
            }

            if (!$tersimpan) {
                if ($berkas_baru_diunggah !== null) hapus_berkas_unggahan($berkas_baru_diunggah);
                pesan_flash('merah', 'Gagal menyimpan data laporan. Berkas lama tetap dipertahankan.');
            } else {
                $file_lama_berhasil_dihapus = $berkas_lama_dihapus === null
                    || hapus_berkas_unggahan($berkas_lama_dihapus);
                pesan_flash(
                    $file_lama_berhasil_dihapus ? 'hijau' : 'kuning',
                    'Laporan "' . $judul . '" berhasil disimpan.' . ($file_lama_berhasil_dihapus ? '' : ' Namun, berkas lama gagal dibersihkan dari server.')
                );
            }
        } catch (Throwable $e) {
            if ($berkas_baru_diunggah !== null) hapus_berkas_unggahan($berkas_baru_diunggah);
            pesan_flash('merah', pesan_error_db('Gagal menyimpan laporan', $e));
        }

        alihkan('admin/laporan.php');
        break;

    case 'hapus':
        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            pesan_flash('merah', 'ID laporan tidak valid.');
            alihkan('admin/laporan.php');
        }

        try {
            $data_lap = laporan_ambil($id);

            if (!$data_lap) {
                pesan_flash('merah', 'Laporan tidak ditemukan.');
                alihkan('admin/laporan.php');
            }

            $sukses = laporan_hapus($id);
            if ($sukses) {
                $file_dibersihkan = !empty($data_lap['is_contoh'])
                    || empty($data_lap['berkas'])
                    || hapus_berkas_unggahan((string)$data_lap['berkas']);
                pesan_flash(
                    $file_dibersihkan ? 'hijau' : 'kuning',
                    'Laporan "' . ($data_lap['judul'] ?? '') . '" berhasil dihapus.' . ($file_dibersihkan ? ' Berkas fisik juga dihapus.' : ' Berkas gagal dibersihkan dari server.')
                );
            } else {
                pesan_flash('merah', 'Gagal menghapus laporan.');
            }
        } catch (Throwable $e) {
            pesan_flash('merah', pesan_error_db('Gagal menghapus laporan', $e));
        }

        alihkan('admin/laporan.php');
        break;

    default:
        pesan_flash('merah', 'Aksi tidak dikenali.');
        alihkan('admin/laporan.php');
        break;
}
