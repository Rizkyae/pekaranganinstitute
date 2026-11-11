/**
 * Pekarangan Institute - JavaScript Khusus Panel Admin
 * Vanilla JS murni tanpa pustaka eksternal.
 * Menangani sidebar mobile, pratinjau gambar, konfirmasi aksi, slug otomatis, dan interaksi form.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle (Mobile)
    const tombolBukaSidebar = document.getElementById('admin-buka-sidebar');
    const tombolTutupSidebar = document.getElementById('admin-tutup-sidebar');
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('admin-backdrop');

    function bukaSidebar() {
        if (sidebar) sidebar.classList.add('buka');
        if (backdrop) backdrop.classList.add('aktif');
        document.body.style.overflow = 'hidden';
    }

    function tutupSidebar() {
        if (sidebar) sidebar.classList.remove('buka');
        if (backdrop) backdrop.classList.remove('aktif');
        document.body.style.overflow = '';
    }

    if (tombolBukaSidebar) {
        tombolBukaSidebar.addEventListener('click', bukaSidebar);
    }
    if (tombolTutupSidebar) {
        tombolTutupSidebar.addEventListener('click', tutupSidebar);
    }
    if (backdrop) {
        backdrop.addEventListener('click', tutupSidebar);
    }

    // 2. Tampilkan / Sembunyikan Password
    const tombolTogglePassword = document.querySelectorAll('.tombol-toggle-pwd');
    tombolTogglePassword.forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            const wadah = this.closest('.input-password-wadah');
            if (!wadah) return;
            const input = wadah.querySelector('input');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Sembunyikan';
                this.setAttribute('aria-label', 'Sembunyikan kata sandi');
            } else {
                input.type = 'password';
                this.textContent = 'Tampilkan';
                this.setAttribute('aria-label', 'Tampilkan kata sandi');
            }
        });
    });

    // 3. Pratinjau Gambar Unggahan
    const inputBerkasGambar = document.querySelectorAll('input[type="file"][data-pratinjau]');
    inputBerkasGambar.forEach(function (input) {
        input.addEventListener('change', function () {
            const targetId = this.getAttribute('data-pratinjau');
            const targetImg = document.getElementById(targetId);
            const wadah = this.closest('.admin-form-grup')?.querySelector('.admin-pratinjau-wadah');

            if (this.files && this.files[0] && targetImg) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    targetImg.src = e.target.result;
                    targetImg.style.display = 'block';
                    if (wadah) wadah.style.display = 'flex';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // 4. Konfirmasi Penghapusan & Aksi Kritis
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-sedang-proses')) {
            return;
        }

        if (form.classList.contains('form-konfirmasi')) {
            const pesan = form.getAttribute('data-pesan') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            if (!window.confirm(pesan)) {
                e.preventDefault();
                return;
            }
        }

        e.preventDefault();

        const tombolSubmit = e.submitter || form.querySelector('button[type="submit"][name], input[type="submit"][name]');
        if (tombolSubmit && tombolSubmit.name) {
            const nilaiAksi = document.createElement('input');
            nilaiAksi.type = 'hidden';
            nilaiAksi.name = tombolSubmit.name;
            nilaiAksi.value = tombolSubmit.value;
            form.appendChild(nilaiAksi);
        }

        form.setAttribute('data-sedang-proses', '1');
        const tombolProses = form.querySelector('button[type="submit"]:not(.tanpa-loading), input[type="submit"]:not(.tanpa-loading)');
        if (tombolProses) {
            tombolProses.disabled = true;
            if (tombolProses instanceof HTMLButtonElement) {
                tombolProses.innerText = 'Memproses...';
            }
        }

        setTimeout(function () {
            HTMLFormElement.prototype.submit.call(form);
        }, 0);
    });

    // 5. Generator Slug Otomatis (Jika slug belum diedit manual)
    const inputSumberSlug = document.querySelector('[data-sumber-slug]');
    const inputTargetSlug = document.querySelector('[data-target-slug]');

    if (inputSumberSlug && inputTargetSlug) {
        let dieditManual = inputTargetSlug.value.trim() !== '';

        inputTargetSlug.addEventListener('input', function () {
            dieditManual = true;
        });

        inputSumberSlug.addEventListener('input', function () {
            if (!dieditManual) {
                const teks = this.value;
                const slug = teks.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                inputTargetSlug.value = slug;
            }
        });
    }

    // 6. Modal Detail (Donasi / Pesanan)
    const tombolBukaModal = document.querySelectorAll('[data-buka-modal]');
    tombolBukaModal.forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            const targetModalId = this.getAttribute('data-buka-modal');
            const modal = document.getElementById(targetModalId);
            if (modal) {
                modal.classList.add('aktif');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    const tombolTutupModal = document.querySelectorAll('[data-tutup-modal]');
    tombolTutupModal.forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('aktif');
                document.body.style.overflow = '';
            }
        });
    });

    const modalOverlays = document.querySelectorAll('.modal-overlay');
    modalOverlays.forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('aktif');
                document.body.style.overflow = '';
            }
        });
    });

    // 7. Pengukur Kekuatan Kata Sandi & Validasi Interaktif
    const inputPassword = document.getElementById('password');
    const inputKonfirmasi = document.getElementById('konfirmasi_password');
    const meterSegmen = document.querySelectorAll('.meter-password-segmen');
    const teksStatus = document.getElementById('status-kekuatan-teks');

    const syaratPanjang = document.getElementById('syarat-panjang');
    const syaratHuruf = document.getElementById('syarat-huruf');
    const syaratAngka = document.getElementById('syarat-angka');
    const statusKonfirmasi = document.getElementById('status-konfirmasi-teks');

    function periksaKekuatanPassword() {
        if (!inputPassword) return;
        const val = inputPassword.value;

        const adaPanjang = val.length >= 8;
        const adaHurufBesar = /[A-Z]/.test(val);
        const adaHurufKecil = /[a-z]/.test(val);
        const adaHuruf = adaHurufBesar && adaHurufKecil;
        const adaAngka = /[0-9]/.test(val);

        function updateItem(el, terpenuhi) {
            if (!el) return;
            const ikon = el.querySelector('.syarat-ikon');
            if (terpenuhi) {
                el.classList.add('terpenuhi');
                if (ikon) ikon.innerHTML = '&#10003;';
            } else {
                el.classList.remove('terpenuhi');
                if (ikon) ikon.innerHTML = '&bull;';
            }
        }

        updateItem(syaratPanjang, adaPanjang);
        updateItem(syaratHuruf, adaHuruf);
        updateItem(syaratAngka, adaAngka);

        let level = 0; // 0: Kosong, 1: Rendah, 2: Sedang, 3: Tinggi

        if (val.length === 0) {
            level = 0;
        } else {
            const semuaWajib = adaPanjang && adaHuruf && adaAngka;
            if (semuaWajib) {
                level = 3; // Tinggi
            } else if (adaPanjang && ((adaHurufBesar || adaHurufKecil) && adaAngka || (adaHurufBesar && adaHurufKecil))) {
                level = 2; // Sedang
            } else if (!adaPanjang && (adaHurufBesar && adaHurufKecil && adaAngka)) {
                level = 2; // Sedang
            } else {
                level = 1; // Rendah
            }
        }

        meterSegmen.forEach(function (segmen) {
            segmen.className = 'meter-password-segmen';
        });

        if (teksStatus) {
            teksStatus.className = 'meter-password-status';
            if (level === 0) {
                teksStatus.textContent = 'Belum diisi';
                teksStatus.classList.add('status-kekuatan-kosong');
            } else if (level === 1) {
                teksStatus.textContent = 'Tingkat Keamanan: Rendah';
                teksStatus.classList.add('status-kekuatan-rendah');
                if (meterSegmen[0]) meterSegmen[0].classList.add('segmen-merah');
            } else if (level === 2) {
                teksStatus.textContent = 'Tingkat Keamanan: Sedang';
                teksStatus.classList.add('status-kekuatan-sedang');
                if (meterSegmen[0]) meterSegmen[0].classList.add('segmen-kuning');
                if (meterSegmen[1]) meterSegmen[1].classList.add('segmen-kuning');
            } else if (level === 3) {
                teksStatus.textContent = 'Tingkat Keamanan: Tinggi';
                teksStatus.classList.add('status-kekuatan-tinggi');
                if (meterSegmen[0]) meterSegmen[0].classList.add('segmen-hijau');
                if (meterSegmen[1]) meterSegmen[1].classList.add('segmen-hijau');
                if (meterSegmen[2]) meterSegmen[2].classList.add('segmen-hijau');
            }
        }

        periksaKecocokanPassword();
    }

    function periksaKecocokanPassword() {
        if (!inputKonfirmasi || !statusKonfirmasi) return;
        const valPwd = inputPassword ? inputPassword.value : '';
        const valKonf = inputKonfirmasi.value;

        if (valKonf.length === 0) {
            statusKonfirmasi.className = 'status-konfirmasi-password';
            statusKonfirmasi.textContent = '';
            statusKonfirmasi.style.display = 'none';
        } else if (valPwd === valKonf) {
            statusKonfirmasi.className = 'status-konfirmasi-password status-konfirmasi-cocok';
            statusKonfirmasi.innerHTML = '<span>&#10003;</span> Kata sandi cocok';
        } else {
            statusKonfirmasi.className = 'status-konfirmasi-password status-konfirmasi-beda';
            statusKonfirmasi.innerHTML = '<span>&#10007;</span> Kata sandi belum cocok';
        }
    }

    if (inputPassword) {
        inputPassword.addEventListener('input', periksaKekuatanPassword);
        if (inputPassword.value) periksaKekuatanPassword();
    }

    if (inputKonfirmasi) {
        inputKonfirmasi.addEventListener('input', periksaKecocokanPassword);
        if (inputKonfirmasi.value) periksaKecocokanPassword();
    }

    // 8. Pintasan Keyboard Ctrl+S / Cmd+S untuk Simpan Pengaturan
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            const formPengaturan = document.getElementById('form-pengaturan');
            if (formPengaturan) {
                e.preventDefault();
                formPengaturan.requestSubmit();
            }
        }
    });

    // 9. Kalkulasi Live Alokasi Dana Operasional & Sosial
    const inputOperasional = document.getElementById('alokasi_operasional');
    const inputSosial = document.getElementById('alokasi_sosial');
    const statusAlokasi = document.getElementById('status-total-alokasi');

    if (inputOperasional && inputSosial && statusAlokasi) {
        function hitungTotalAlokasi() {
            const op = parseInt(inputOperasional.value, 10) || 0;
            const so = parseInt(inputSosial.value, 10) || 0;
            const total = op + so;

            if (total === 100) {
                statusAlokasi.innerHTML = '<span style="color:var(--hijau);display:inline-flex;align-items:center;gap:4px;">&#10003; Total Alokasi: ' + op + '% + ' + so + '% = <strong>100% (Sesuai)</strong></span>';
            } else {
                statusAlokasi.innerHTML = '<span style="color:var(--merah);display:inline-flex;align-items:center;gap:4px;">&#10007; Total Alokasi: ' + op + '% + ' + so + '% = <strong>' + total + '%</strong> (Harus tepat 100%)</span>';
            }
        }

        inputOperasional.addEventListener('input', hitungTotalAlokasi);
        inputSosial.addEventListener('input', hitungTotalAlokasi);
        hitungTotalAlokasi();
    }
});
