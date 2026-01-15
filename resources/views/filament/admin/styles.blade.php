<style>
    /* =========================================
       BAGIAN 1: SIDEBAR DASHBOARD
       (Diambil dari style lengkap dengan tombol)
       ========================================= */

    /* 1. Memberi Batas (Border) & Warna pada Sidebar */
    aside.fi-sidebar {
        background-color: #fdfdfd !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.02);
    }

    /* 2. Memperbaiki Header Sidebar */
    .fi-sidebar-header {
        border-bottom: 1px solid #f3f4f6;
        background-color: white;
    }

    /* 3. Styling Tombol Collapse (Panah Buka/Tutup) */
    .fi-sidebar-header button {
        background-color: #f3f4f6;
        border-radius: 8px;
        margin-left: auto; /* Dorong ke kanan */
    }
    .fi-sidebar-header button:hover {
        background-color: #05b9ad; /* Warna Primary (Tosca) */
        color: white;
    }


    /* =========================================
       BAGIAN 2: HALAMAN LOGIN CUSTOM
       (Diambil dari style Rata Kiri & Glassmorphism)
       ========================================= */

    /* 1. Background Image pada Body */
    /* Menggunakan selector 'has' agar hanya kena di halaman login */
    body:has(.fi-simple-layout) {
        /* Pastikan nama file sesuai (jpg/png) */
        background-image: url('{{ asset("images/login-bg.jpg") }}') !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-attachment: fixed !important;
    }

    /* 2. Layout Transparan (Supaya background body terlihat) */
    .fi-simple-layout {
        background-color: transparent !important;
    }

    /* 3. Kotak Form Login (Glassmorphism Effect) */
    .fi-simple-main {
        background-color: rgba(255, 255, 255, 0.73) !important;
        backdrop-filter: blur(10px); /* Efek Blur */
        border-radius: 12px;
        padding-left: 2rem !important;
        padding-right: 2rem !important;
        border: 1px solid rgba(255,255,255,0.6);
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    /* Support Mode Gelap */
    .dark .fi-simple-main {
        background-color: rgba(17, 24, 39, 0.61) !important;
        border: 1px solid rgba(255,255,255,0.1);
    }

    /* 4. Logo Besar di Halaman Login */
    .fi-simple-layout .fi-logo {
        height: 4rem !important;
        width: auto !important;
        margin-bottom: 1rem !important;
    }


    /* =========================================
       BAGIAN 3: ALIGNMENT RATA KIRI
       (Agar teks 'Selamat Datang' rapi)
       ========================================= */

    /* Header Container (Logo & Judul) */
    .fi-simple-header {
        align-items: flex-start !important; /* Paksa ke Kiri */
        text-align: left !important;
        padding-bottom: 1rem !important;
    }

    /* Judul Utama ("Login") */
    .fi-simple-header .fi-simple-heading {
        text-align: left !important;
        width: 100% !important;
        font-size: 2rem !important;
    }

    /* Form Container (Input Email/Pass) */
    .fi-simple-main > div {
        width: 100% !important;
        max-width: 100% !important;
    }
</style>