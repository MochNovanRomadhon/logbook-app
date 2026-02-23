<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logbook App - Sistem Manajemen Aktivitas Terpadu</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS v4 CDN (Zero Config) -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        @theme {
            --font-display: 'Instrument Sans', sans-serif;
            --color-primary: #2dd4bf;
            --color-secondary: #38bdf8;
            --color-accent: #34d399;
        }
        
        body {
            font-family: var(--font-display);
        }
        
        [x-cloak] { display: none !important; }
        
        /* Custom Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
    </style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 selection:bg-teal-200 selection:text-teal-900" x-data="{ scrolled: false, mobileMenuOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">

    <!-- Navbar -->
    <nav 
        class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-transparent"
        :class="{ 'bg-white/80 backdrop-blur-md border-slate-200/50 shadow-sm': scrolled, 'bg-transparent py-6': !scrolled, 'py-3': scrolled }"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-12">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    <img src="{{ asset('images/favicon.png') }}" alt="Logo" class="w-8 h-8">
                    <a href="#" class="text-xl font-bold tracking-tight text-slate-900">
                        Logbook<span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-sky-500">App</span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-teal-600 transition-colors">Fitur</a>
                    <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-teal-600 transition-colors">Cara Kerja</a>
                    <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-teal-600 transition-colors">FAQ</a>
                </div>

                <!-- CTA Button -->
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ url('/app/login') }}" class="group relative px-5 py-2 rounded-full overflow-hidden bg-slate-900 text-white shadow-lg transition-all hover:shadow-teal-500/25 hover:scale-105">
                        <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-teal-400 to-sky-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <span class="relative text-sm font-bold flex items-center gap-2">
                            Login 
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-600 hover:text-slate-900 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition.opacity @click.away="mobileMenuOpen = false" class="md:hidden absolute top-full left-0 w-full bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-xl">
            <div class="px-4 py-6 space-y-4">
                <a href="#features" class="block text-base font-semibold text-slate-600 hover:text-teal-600">Fitur</a>
                <a href="#how-it-works" class="block text-base font-semibold text-slate-600 hover:text-teal-600">Cara Kerja</a>
                <a href="#faq" class="block text-base font-semibold text-slate-600 hover:text-teal-600">FAQ</a>
                <hr class="border-slate-100">
                <a href="{{ url('/app') }}" class="flex w-full items-center justify-center px-4 py-3 rounded-xl bg-gradient-to-r from-teal-400 to-sky-400 text-white font-bold shadow-lg">
                    Akses Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background Blobs -->
        <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-[600px] h-[600px] rounded-full bg-teal-400/20 blur-[100px] opacity-50 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full bg-sky-400/20 blur-[100px] opacity-50 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-slate-200 shadow-sm mb-8 opacity-0 animate-fade-in-up">
                    <span class="flex h-2 w-2 rounded-full bg-teal-500"></span>
                    <span class="text-xs font-semibold text-slate-600 tracking-wide uppercase">Sistem Manajemen Terpadu v2.0</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight text-slate-900 mb-6 opacity-0 animate-fade-in-up delay-100 leading-[1.1]">
                    Kelola Aktivitas & Tugas <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-400 via-sky-400 to-indigo-400">Harian Lebih Efisien.</span>
                </h1>
                
                <p class="mt-6 text-lg md:text-xl text-slate-600 mb-10 max-w-2xl mx-auto opacity-0 animate-fade-in-up delay-200 leading-relaxed">
                    Platform logbook digital yang membantu Direktorat dan Unit kerja Anda memantau kinerja, mengelola tugas, dan mencetak laporan secara otomatis.
                </p>
                
                <div class="flex flex-col sm:flex-row justify-center gap-4 opacity-0 animate-fade-in-up delay-300">
                    <a href="{{ url('/app') }}" class="group px-8 py-4 rounded-full bg-gradient-to-r from-teal-400 to-sky-400 text-white font-bold text-lg shadow-xl shadow-teal-500/30 hover:shadow-teal-500/50 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                        Mulai Sekarang
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#how-it-works" class="px-8 py-4 rounded-full bg-white text-slate-700 border border-slate-200 font-bold text-lg hover:border-teal-400 hover:text-teal-600 transition-all duration-300 shadow-sm hover:shadow-md">
                        Pelajari Sistem
                    </a>
                </div>
            </div>

            <!-- Dashboard Visual -->
            <div class="mt-20 relative max-w-6xl mx-auto opacity-0 animate-fade-in-up delay-300">
                <div class="relative rounded-2xl bg-white/50 backdrop-blur-sm p-4 ring-1 ring-slate-900/5 shadow-2xl">
                    <div class="rounded-xl bg-white overflow-hidden relative aspect-[16/10] md:aspect-[21/9] shadow-inner border border-slate-200 flex">
                        
                        <!-- Sidebar Mockup -->
                        <div class="hidden md:flex flex-col w-64 bg-white border-r border-slate-100 flex-shrink-0">
                            <div class="p-6 border-b border-slate-50 flex items-center gap-3">
                                <img src="{{ asset('images/favicon.png') }}" class="w-8 h-8" alt="Logo">
                                <span class="font-bold text-slate-800">Logbook App</span>
                            </div>
                            <div class="p-4 space-y-1">
                                <div class="flex items-center gap-3 px-3 py-2.5 bg-teal-50 text-teal-700 rounded-lg text-sm font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                    Dashboard
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" /></svg>
                                    Monitoring
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                                    Daftar Tugas
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    Logbook Harian
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    Master Data
                                </div>
                                 <div class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    Pengguna
                                </div>
                            </div>
                        </div>

                        <!-- Content Mockup -->
                        <div class="flex-1 bg-slate-50/50 p-6 flex flex-col gap-6 relative overflow-hidden">
                            
                            <!-- Top Bar -->
                            <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                <div class="h-4 w-32 bg-slate-100 rounded-full"></div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full">
                                         <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                         <span class="text-xs font-medium text-slate-600">Online</span>
                                    </div>
                                    <div class="h-8 w-8 rounded-full bg-slate-200 border border-slate-300"></div>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(6,182,212,0.1)] group hover:-translate-y-1 transition-transform duration-300">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-slate-800 mb-1">128</div>
                                    <div class="text-xs text-slate-400 font-medium">Total Pegawai</div>
                                </div>

                                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(59,130,246,0.1)] group hover:-translate-y-1 transition-transform duration-300">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-slate-800 mb-1">12 Unit</div>
                                    <div class="text-xs text-slate-400 font-medium">Unit & Sub-Unit</div>
                                </div>

                                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(99,102,241,0.1)] group hover:-translate-y-1 transition-transform duration-300">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                        <span class="text-xs font-semibold text-green-500 bg-green-50 px-2 py-1 rounded-full">+24%</span>
                                    </div>
                                    <div class="text-2xl font-bold text-slate-800 mb-1">2,450</div>
                                    <div class="text-xs text-slate-400 font-medium">Logbook Bulan Ini</div>
                                </div>
                            </div>

                            <!-- Graph Mockup -->
                            <div class="flex-1 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col relative overflow-hidden">
                                <div class="flex justify-between items-end mb-6">
                                    <div>
                                        <div class="text-sm font-bold text-slate-800">Task Completion Rate</div>
                                        <div class="text-xs text-slate-400 mt-1">Tren penyelesaian tugas harian</div>
                                    </div>
                                </div>
                                <div class="flex-1 flex items-end gap-2 px-2 h-24">
                                    <!-- Sparkline-like bars -->
                                    <div class="w-full bg-teal-100 rounded-t-sm h-[40%]"></div>
                                    <div class="w-full bg-teal-200 rounded-t-sm h-[50%]"></div>
                                    <div class="w-full bg-teal-300 rounded-t-sm h-[45%]"></div>
                                    <div class="w-full bg-teal-400 rounded-t-sm h-[60%]"></div>
                                    <div class="w-full bg-teal-500 rounded-t-sm h-[55%]"></div>
                                    <div class="w-full bg-teal-400 rounded-t-sm h-[70%]"></div>
                                    <div class="w-full bg-teal-500 rounded-t-sm h-[80%]"></div>
                                    <div class="w-full bg-teal-600 rounded-t-sm h-[75%]"></div>
                                    <div class="w-full bg-teal-500 rounded-t-sm h-[85%]"></div>
                                    <div class="w-full bg-teal-400 rounded-t-sm h-[90%]"></div>
                                    <div class="w-full bg-teal-500 rounded-t-sm h-[95%]"></div>
                                    <div class="w-full bg-teal-600 rounded-t-sm h-[100%]"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Floating Elements (Redesigned) -->
                <div class="absolute -right-6 -top-6 md:-right-12 md:top-12 p-4 bg-white/90 backdrop-blur-md rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white ring-1 ring-slate-900/5 animate-float z-20">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-teal-400 to-emerald-400 flex items-center justify-center text-white shadow-lg shadow-teal-500/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Notifikasi</p>
                            <p class="text-base font-bold text-slate-900">Logbook Berhasil Disimpan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-10 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <p class="text-3xl font-bold text-slate-900">500+</p>
                    <p class="text-sm text-slate-500 font-medium uppercase tracking-wider mt-1">Pengguna Aktif</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-slate-900">50+</p>
                    <p class="text-sm text-slate-500 font-medium uppercase tracking-wider mt-1">Unit Kerja</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-slate-900">10k+</p>
                    <p class="text-sm text-slate-500 font-medium uppercase tracking-wider mt-1">Laporan Terkirim</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-slate-900">99.9%</p>
                    <p class="text-sm text-slate-500 font-medium uppercase tracking-wider mt-1">Uptime Server</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Features Section -->
    <section id="features" class="py-24 bg-slate-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16 max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Fitur Unggulan</h2>
                <p class="text-lg text-slate-600">Kami merancang setiap fitur untuk memudahkan alur kerja birokrasi yang kompleks menjadi sederhana.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group relative p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-teal-900/5 transition-all duration-300 hover:-translate-y-1">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-teal-400 to-teal-500 scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                    <div class="w-14 h-14 rounded-2xl bg-teal-50 flex items-center justify-center mb-6 text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Monitoring & Evaluasi</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Pantau progres harian seluruh tim Anda dalam satu dashboard terintegrasi. Deteksi hambatan lebih dini dengan visualisasi data yang intuitif.</p>
                </div>

                <!-- Feature 2 -->
                <div class="group relative p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-sky-900/5 transition-all duration-300 hover:-translate-y-1">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-sky-400 to-sky-500 scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                    <div class="w-14 h-14 rounded-2xl bg-sky-50 flex items-center justify-center mb-6 text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Manajemen Unit Kerja</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Fleksibilitas penuh dalam mengelola struktur Direktorat, Unit, dan Sub-unit. Hak akses pengguna disesuaikan otomatis berdasarkan jabatan.</p>
                </div>

                <!-- Feature 3 -->
                <div class="group relative p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-indigo-900/5 transition-all duration-300 hover:-translate-y-1">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-400 to-indigo-500 scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mb-6 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                         <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Cetak Laporan Kinerja</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Hemat waktu administrasi. Sistem otomatis merekap logbook bulanan menjadi dokumen laporan kinerja berstandar resmi siap tandatangan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Cara Kerja Sistem</h2>
                <p class="text-lg text-slate-600">Hanya butuh 3 langkah sederhana untuk memulai.</p>
            </div>
            
            <div class="relative">
                <!-- Connector Line -->
                <div class="absolute top-12 left-0 w-full h-0.5 bg-slate-100 hidden md:block"></div>
                
                <div class="grid md:grid-cols-3 gap-12 relative">
                    <!-- Step 1 -->
                    <div class="text-center relative">
                        <div class="w-24 h-24 mx-auto bg-white rounded-full border-4 border-teal-100 flex items-center justify-center relative z-10 mb-6 shadow-sm">
                            <span class="text-3xl font-bold text-teal-500">1</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Login Akun</h3>
                        <p class="text-slate-600 text-sm px-4">Masuk menggunakan kredensial yang diberikan oleh administrator unit kerja Anda.</p>
                    </div>
                     <!-- Step 2 -->
                     <div class="text-center relative">
                        <div class="w-24 h-24 mx-auto bg-white rounded-full border-4 border-sky-100 flex items-center justify-center relative z-10 mb-6 shadow-sm">
                            <span class="text-3xl font-bold text-sky-500">2</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Input Logbook</h3>
                        <p class="text-slate-600 text-sm px-4">Catat aktivitas harian, lampirkan bukti dokumen, dan set status progres.</p>
                    </div>
                     <!-- Step 3 -->
                     <div class="text-center relative">
                        <div class="w-24 h-24 mx-auto bg-white rounded-full border-4 border-indigo-100 flex items-center justify-center relative z-10 mb-6 shadow-sm">
                            <span class="text-3xl font-bold text-indigo-500">3</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Terima Laporan</h3>
                        <p class="text-slate-600 text-sm px-4">Atasan memverifikasi dan sistem otomatis merekap kinerja bulanan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section id="faq" class="py-24 bg-slate-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
             <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Pertanyaan Umum (FAQ)</h2>
            </div>
            
            <div class="space-y-4" x-data="{ active: null }">
                <!-- Item 1 -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <button @click="active = (active === 1 ? null : 1)" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-semibold text-slate-900">Apakah aplikasi ini bisa diakses mobile?</span>
                        <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200" :class="{ 'rotate-180': active === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse x-cloak>
                        <div class="px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            Ya, Logbook App didesain sepenuhnya responsif (mobile-friendly). Anda dapat mengakses dan mengisi logbook melalui smartphone, tablet, maupun desktop dengan pengalaman yang sama baiknya.
                        </div>
                    </div>
                </div>
                <!-- Item 2 -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <button @click="active = (active === 2 ? null : 2)" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-semibold text-slate-900">Bagaimana jika lupa password?</span>
                        <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200" :class="{ 'rotate-180': active === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse x-cloak>
                        <div class="px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            Silakan hubungi administrator unit kerja Anda untuk melakukan reset password. Fitur reset mandiri via email akan tersedia pada update berikutnya demi alasan keamanan internal.
                        </div>
                    </div>
                </div>
                <!-- Item 3 -->
                  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <button @click="active = (active === 3 ? null : 3)" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-semibold text-slate-900">Apakah data saya aman?</span>
                        <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200" :class="{ 'rotate-180': active === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse x-cloak>
                        <div class="px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            Tentu. Data disimpan dalam server terenkripsi dan hanya dapat diakses oleh atasan langsung dan pihak yang memiliki otorisasi sesuai struktur organisasi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <img src="{{ asset('images/favicon.png') }}" alt="Logo" class="w-8 h-8">
                        <span class="text-xl font-bold tracking-tight text-slate-900">Logbook App</span>
                    </div>
                    <p class="text-slate-500 max-w-sm leading-relaxed">
                        Kami membantu organisasi pemerintahan dan swasta meningkatkan akuntabilitas kinerja melalui sistem pencatatan digital yang modern dan transparan.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Produk</h4>
                    <ul class="space-y-4 text-slate-600 text-sm">
                        <li><a href="#features" class="hover:text-teal-600 transition-colors">Fitur Utama</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Studi Kasus</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Pricing</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Update Log</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Dukungan</h4>
                    <ul class="space-y-4 text-slate-600 text-sm">
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Pusat Bantuan</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Dokumentasi API</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Kebijakan Privasi</a></li>
                        <li><a href="#" class="hover:text-teal-600 transition-colors">Kontak Kami</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-slate-400 text-sm">© {{ date('Y') }} Logbook App Management System. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors"><span class="sr-only">Facebook</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                    <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors"><span class="sr-only">Twitter</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg></a>
                    <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors"><span class="sr-only">GitHub</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
