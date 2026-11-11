/**
 * Pekarangan Institute - JavaScript Sederhana & Aksesibel
 * Navigasi Sidebar Kanan (Mobile Drawer & Desktop), Aksesibilitas, Preset Donasi, Toggle Password
 */

document.addEventListener('DOMContentLoaded', function () {
    var tombolMenu = document.getElementById('tombol-menu');
    var sidebar = document.getElementById('app-sidebar') || document.getElementById('sidebar-kanan');
    var sidebarBackdrop = document.getElementById('sidebar-backdrop');
    var tombolTutup = document.getElementById('tombol-sidebar-tutup');

    function bukaSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('terbuka');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('aktif');
        if (tombolMenu) tombolMenu.setAttribute('aria-expanded', 'true');
        document.body.classList.add('sidebar-terbuka');
    }

    function tutupSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('terbuka');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('aktif');
        if (tombolMenu) {
            tombolMenu.setAttribute('aria-expanded', 'false');
            tombolMenu.focus();
        }
        document.body.classList.remove('sidebar-terbuka');
    }

    if (tombolMenu) {
        tombolMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('terbuka')) {
                tutupSidebar();
            } else {
                bukaSidebar();
            }
        });
    }

    if (tombolTutup) {
        tombolTutup.addEventListener('click', function (e) {
            e.preventDefault();
            tutupSidebar();
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            tutupSidebar();
        });
    }

    // Aksesibilitas: Tutup sidebar saat menekan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            if (sidebar && sidebar.classList.contains('terbuka')) {
                tutupSidebar();
            }
        }
    });

    // Interaksi tombol pilihan nominal donasi cepat
    var tombolNominal = document.querySelectorAll('.tombol-nominal');
    var inputNominal = document.getElementById('nominal');

    if (tombolNominal.length > 0 && inputNominal) {
        tombolNominal.forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                var nilai = this.getAttribute('data-nominal');
                if (nilai) {
                    inputNominal.value = nilai;
                    tombolNominal.forEach(function (btn) {
                        btn.classList.remove('aktif');
                    });
                    tombol.classList.add('aktif');
                    inputNominal.focus();
                }
            });
        });

        inputNominal.addEventListener('input', function () {
            var nilaiSekarang = this.value;
            tombolNominal.forEach(function (btn) {
                if (btn.getAttribute('data-nominal') === nilaiSekarang) {
                    btn.classList.add('aktif');
                } else {
                    btn.classList.remove('aktif');
                }
            });
        });
    }

    // Toggle Tampilkan/Sembunyikan Password
    var tombolTogglePwd = document.querySelectorAll('.tombol-toggle-pwd');
    tombolTogglePwd.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wadah = this.closest('.input-password-wadah');
            if (!wadah) return;
            var input = wadah.querySelector('input');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Sembunyikan';
            } else {
                input.type = 'password';
                this.textContent = 'Tampilkan';
            }
        });
    });
});
