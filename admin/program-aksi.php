<?php
/**
 * Aksi Pemrosesan Program Sosial - Pekarangan Institute (Firebase RTDB)
 * Menangani tambah, ubah, ubah status, dan hapus program sosial via data.php.
 * Melindungi integritas data donasi (tidak boleh hapus program berdonasi) dan berkas sampul.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && permintaan_post_melebihi_batas()) {
    pesan_flash('merah', 'Permintaan melebihi batas ukuran yang diizinkan server. Coba unggah berkas yang lebih kecil atau sesuaikan post_max_size pada PHP.');
    alihkan('admin/program.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cek_csrf()) {
    pesan_flash('merah', 'Permintaan tidak valid atau token keamanan kedaluwarsa.');
    alihkan('admin/program.php');
}

$aksi = trim($_POST['aksi'] ?? '');

switch ($aksi) {
    case 'tambah':
    case 'ubah':
        $adalah_edit = ($aksi === 'ubah');
        $id = trim((string)($_POST['id'] ?? ''));

        if ($adalah_edit && $id === '') {
            pesan_flash('merah', 'ID program tidak valid.');
            alihkan('admin/program.php');
        }

        $judul = trim($_POST['judul'] ?? '');
        $slug_input = trim($_POST['slug'] ?? '');
        $ringkasan = trim($_POST['ringkasan'] ?? '');
        $cerita = trim($_POST['cerita'] ?? '');
        $target_input = trim($_POST['target_dana'] ?? '');
        $tenggat_input = trim($_POST['tenggat'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $penerima_manfaat = trim($_POST['penerima_manfaat'] ?? '');
        $status = trim($_POST['status'] ?? 'aktif');

        $errors = [];

        if ($judul === '') {
            $errors['judul'] = 'Judul program wajib diisi.';
        } elseif (mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul program terlalu panjang (maksimal 200 karakter).';
        }

        $slug = buat_slug($slug_input !== '' ? $slug_input : $judul);
        $semua_program = program_semua();
        foreach ($semua_program as $p) {
            if (($p['slug'] ?? '') === $slug) {
                if (!$adalah_edit || (string)($p['id'] ?? '') !== $id) {
                    $slug = $slug . '-' . time();
                    break;
                }
            }
        }

        if ($target_input === '' || !is_numeric($target_input) || (int)$target_input <= 0) {
            $errors['target_dana'] = 'Target dana harus berupa angka lebih besar dari 0.';
        }
        $target_dana = (int)$target_input;

        $tenggat = null;
        if ($tenggat_input !== '') {
            $waktu_tenggat = strtotime($tenggat_input);
            if (!$waktu_tenggat) {
                $errors['tenggat'] = 'Format tanggal tenggat tidak valid.';
            } else {
                $tenggat = date('Y-m-d', $waktu_tenggat);
            }
        }

        if ($lokasi === '') {
            $errors['lokasi'] = 'Lokasi program wajib diisi.';
        }

        if ($penerima_manfaat === '') {
            $errors['penerima_manfaat'] = 'Sasaran penerima manfaat wajib diisi.';
        }

        if ($ringkasan === '') {
            $errors['ringkasan'] = 'Ringkasan program wajib diisi.';
        } elseif (mb_strlen($ringkasan) > 255) {
            $errors['ringkasan'] = 'Ringkasan tidak boleh lebih dari 255 karakter.';
        }

        if ($cerita === '') {
            $errors['cerita'] = 'Cerita program wajib diisi.';
        }

        if (!in_array($status, ['aktif', 'draf', 'selesai'], true)) {
            $errors['status'] = 'Status program tidak valid.';
        }

        // Ambil data program lama jika edit
        $program_lama = null;
        if ($adalah_edit) {
            $program_lama = program_ambil($id);
            if (!$program_lama) {
                pesan_flash('merah', 'Program lama tidak ditemukan.');
                alihkan('admin/program.php');
            }
        }

        // Unggah berkas baru terlebih dahulu; sampul lama baru dibersihkan setelah data berhasil disimpan.
        $foto_sampul = $program_lama['foto_sampul'] ?? null;
        $foto_baru_diunggah = null;
        $foto_lama_dihapus = null;
        if (!empty($_FILES['foto_sampul']['name'])) {
            $hasil_unggah = unggah_berkas($_FILES['foto_sampul'], 'gambar');
            if (!$hasil_unggah['sukses']) {
                $errors['foto_sampul'] = $hasil_unggah['pesan'];
            } else {
                $foto_baru_diunggah = $hasil_unggah['nama'];
                $foto_lama_dihapus = empty($program_lama['is_contoh']) ? $foto_sampul : null;
                $foto_sampul = $foto_baru_diunggah;
            }
        } elseif (!empty($_POST['hapus_foto'])) {
            $foto_lama_dihapus = $foto_sampul;
            $foto_sampul = null;
        }

        if (!empty($errors)) {
            if ($foto_baru_diunggah !== null) hapus_berkas_unggahan($foto_baru_diunggah);
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $tujuan = $adalah_edit ? 'admin/program-form.php?id=' . urlencode($id) : 'admin/program-form.php';
            alihkan($tujuan);
        }

        try {
            $data_simpan = [
                'judul'            => $judul,
                'slug'             => $slug,
                'ringkasan'        => $ringkasan,
                'cerita'           => $cerita,
                'target_dana'      => $target_dana,
                'tenggat'          => $tenggat,
                'lokasi'           => $lokasi,
                'penerima_manfaat' => $penerima_manfaat,
                'foto_sampul'      => $foto_sampul,
                'status'           => $status,
            ];

            if ($adalah_edit) {
                $tersimpan = program_ubah($id, $data_simpan);
            } else {
                $data_simpan['is_contoh'] = 0;
                $tersimpan = program_tambah($data_simpan) !== null;
            }

            if (!$tersimpan) {
                if ($foto_baru_diunggah !== null) hapus_berkas_unggahan($foto_baru_diunggah);
                pesan_flash('merah', 'Gagal menyimpan data program. Foto lama tetap dipertahankan.');
            } else {
                $file_lama_berhasil_dihapus = $foto_lama_dihapus === null
                    || $foto_lama_dihapus === ''
                    || hapus_berkas_unggahan($foto_lama_dihapus);
                $pesan_sukses = $adalah_edit
                    ? 'Program "' . $judul . '" berhasil diperbarui.'
                    : 'Program baru "' . $judul . '" berhasil dibuat.';
                if (!$file_lama_berhasil_dihapus) {
                    $pesan_sukses .= ' Namun, berkas lama gagal dibersihkan dari server.';
                }
                pesan_flash($file_lama_berhasil_dihapus ? 'hijau' : 'kuning', $pesan_sukses);
            }
        } catch (Throwable $e) {
            if ($foto_baru_diunggah !== null) hapus_berkas_unggahan($foto_baru_diunggah);
            pesan_flash('merah', pesan_error_db('Gagal menyimpan program', $e));
        }

        alihkan('admin/program.php');
        break;

    case 'ubah_status':
        $id = trim((string)($_POST['id'] ?? ''));
        $status_baru = trim($_POST['status_baru'] ?? '');

        if ($id === '' || !in_array($status_baru, ['aktif', 'draf', 'selesai'], true)) {
            pesan_flash('merah', 'Data status tidak valid.');
            alihkan('admin/program.php');
        }

        try {
            $sukses = program_ubah($id, ['status' => $status_baru]);
            if ($sukses) {
                pesan_flash('hijau', 'Status program berhasil diubah menjadi ' . $status_baru . '.');
            } else {
                pesan_flash('merah', 'Gagal memperbarui status program.');
            }
        } catch (Throwable $e) {
            pesan_flash('merah', pesan_error_db('Gagal memperbarui status', $e));
        }
        alihkan('admin/program.php');
        break;

    case 'hapus':
        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            pesan_flash('merah', 'ID program tidak valid.');
            alihkan('admin/program.php');
        }

        try {
            // Periksa apakah program memiliki riwayat donasi
            if (program_punya_donasi($id)) {
                pesan_flash('kuning', 'Program ini tidak dapat dihapus karena sudah memiliki catatan donasi. Silakan ubah status program menjadi Selesai.');
                alihkan('admin/program.php');
            }

            // Ambil data foto sampul sebelum dihapus
            $data_prog = program_ambil($id);
            if (!$data_prog) {
                pesan_flash('merah', 'Program tidak ditemukan.');
                alihkan('admin/program.php');
            }

            $sukses = program_hapus($id);
            if ($sukses) {
                $file_dibersihkan = !empty($data_prog['is_contoh'])
                    || empty($data_prog['foto_sampul'])
                    || hapus_berkas_unggahan((string)$data_prog['foto_sampul']);
                pesan_flash($file_dibersihkan ? 'hijau' : 'kuning', 'Program "' . ($data_prog['judul'] ?? '') . '" berhasil dihapus.' . ($file_dibersihkan ? '' : ' Berkas foto gagal dibersihkan dari server.'));
            } else {
                pesan_flash('merah', 'Gagal menghapus program.');
            }
        } catch (Throwable $e) {
            pesan_flash('merah', pesan_error_db('Gagal menghapus program', $e));
        }

        alihkan('admin/program.php');
        break;

    default:
        pesan_flash('merah', 'Aksi tidak dikenali.');
        alihkan('admin/program.php');
        break;
}
