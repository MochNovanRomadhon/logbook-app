<?php

namespace Database\Seeders;

use App\Models\Logbook;
use App\Models\LogbookItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // ========== AMBIL USER ==========
        $pengawas = User::whereHas('roles', fn($q) => $q->where('name', 'pengawas'))->first();
        $pegawaiList = User::whereHas('roles', fn($q) => $q->where('name', 'pegawai'))->get();

        if (!$pengawas || $pegawaiList->count() < 2) {
            $this->command->error('Minimal butuh 1 pengawas dan 2 pegawai. Seeder dibatalkan.');
            return;
        }

        $pegawai1 = $pegawaiList[0];
        $pegawai2 = $pegawaiList[1];
        $pegawai3 = $pegawaiList->count() >= 3 ? $pegawaiList[2] : null;

        $this->command->info("Pengawas: {$pengawas->name} (ID: {$pengawas->id})");
        $this->command->info("Pegawai 1: {$pegawai1->name} (ID: {$pegawai1->id})");
        $this->command->info("Pegawai 2: {$pegawai2->name} (ID: {$pegawai2->id})");

        // ========================================
        // A) TUGAS INDIVIDU (Pegawai 1 buat sendiri)
        // ========================================
        $task1 = Task::create([
            'user_id'     => $pegawai1->id,
            'assigned_by' => null,
            'title'       => 'Menyusun Laporan Kinerja Bulanan',
            'description' => 'Membuat rekapitulasi laporan kinerja seluruh divisi untuk periode Maret 2026. Termasuk analisis pencapaian KPI dan rekomendasi perbaikan.',
            'status'      => 'in_progress',
            'urgency'     => 3,
            'deadline'    => Carbon::parse('2026-03-20'),
            'processed_at' => Carbon::parse('2026-03-05 08:30:00'),
        ]);

        $task2 = Task::create([
            'user_id'     => $pegawai1->id,
            'assigned_by' => null,
            'title'       => 'Update Database Internal',
            'description' => 'Melakukan pembaruan data pegawai pada sistem informasi internal, termasuk verifikasi alamat, nomor telepon, dan jabatan terbaru.',
            'status'      => 'completed',
            'urgency'     => 2,
            'deadline'    => Carbon::parse('2026-03-10'),
            'processed_at' => Carbon::parse('2026-03-03 09:00:00'),
            'completed_at' => Carbon::parse('2026-03-09 16:45:00'),
        ]);

        // ========================================
        // B) TUGAS INDIVIDU (Pegawai 2 buat sendiri)
        // ========================================
        $task3 = Task::create([
            'user_id'     => $pegawai2->id,
            'assigned_by' => null,
            'title'       => 'Revisi Dokumen SOP Pengadaan',
            'description' => 'Merevisi Standard Operating Procedure pengadaan barang dan jasa sesuai dengan regulasi terbaru yang dikeluarkan pada Februari 2026.',
            'status'      => 'pending',
            'urgency'     => 4,
            'deadline'    => Carbon::parse('2026-03-25'),
        ]);

        // ========================================
        // C) TUGAS DITUGASKAN PENGAWAS KE 1 ORANG
        // ========================================
        $task4 = Task::create([
            'user_id'     => $pegawai1->id,
            'assigned_by' => $pengawas->id,
            'title'       => 'Audit Inventaris Aset Kantor',
            'description' => 'Melakukan pengecekan fisik dan pencatatan ulang seluruh aset kantor di lantai 3 gedung utama. Hasilnya dilaporkan dalam format spreadsheet.',
            'status'      => 'in_progress',
            'urgency'     => 5,
            'deadline'    => Carbon::parse('2026-03-18'),
            'processed_at' => Carbon::parse('2026-03-06 10:00:00'),
        ]);

        // ========================================
        // D) TUGAS GRUP (Pengawas → 2 Pegawai)
        // ========================================
        $groupId1 = (string) Str::uuid();

        $taskGroup1a = Task::create([
            'user_id'       => $pegawai1->id,
            'assigned_by'   => $pengawas->id,
            'task_group_id' => $groupId1,
            'title'         => 'Persiapan Rapat Kerja Nasional',
            'description'   => 'Menyusun materi presentasi, menyiapkan dokumen pendukung, dan mengkoordinasikan logistik untuk Rapat Kerja Nasional tanggal 28 Maret 2026.',
            'status'        => 'in_progress',
            'urgency'       => 5,
            'deadline'      => Carbon::parse('2026-03-28'),
            'processed_at'  => Carbon::parse('2026-03-07 08:00:00'),
        ]);

        $taskGroup1b = Task::create([
            'user_id'       => $pegawai2->id,
            'assigned_by'   => $pengawas->id,
            'task_group_id' => $groupId1,
            'title'         => 'Persiapan Rapat Kerja Nasional',
            'description'   => 'Menyusun materi presentasi, menyiapkan dokumen pendukung, dan mengkoordinasikan logistik untuk Rapat Kerja Nasional tanggal 28 Maret 2026.',
            'status'        => 'in_progress',
            'urgency'       => 5,
            'deadline'      => Carbon::parse('2026-03-28'),
            'processed_at'  => Carbon::parse('2026-03-07 08:00:00'),
        ]);

        // ========================================
        // E) TUGAS GRUP KEDUA (Pengawas → 2 Pegawai, Selesai)
        // ========================================
        $groupId2 = (string) Str::uuid();

        $taskGroup2a = Task::create([
            'user_id'       => $pegawai1->id,
            'assigned_by'   => $pengawas->id,
            'task_group_id' => $groupId2,
            'title'         => 'Survei Kepuasan Pelayanan Publik',
            'description'   => 'Merancang kuesioner, mengumpulkan responden, dan menganalisis data survei kepuasan masyarakat terhadap layanan publik unit kerja.',
            'status'        => 'completed',
            'urgency'       => 3,
            'deadline'      => Carbon::parse('2026-03-15'),
            'processed_at'  => Carbon::parse('2026-03-01 09:00:00'),
            'completed_at'  => Carbon::parse('2026-03-11 17:00:00'),
        ]);

        $taskGroup2b = Task::create([
            'user_id'       => $pegawai2->id,
            'assigned_by'   => $pengawas->id,
            'task_group_id' => $groupId2,
            'title'         => 'Survei Kepuasan Pelayanan Publik',
            'description'   => 'Merancang kuesioner, mengumpulkan responden, dan menganalisis data survei kepuasan masyarakat terhadap layanan publik unit kerja.',
            'status'        => 'completed',
            'urgency'       => 3,
            'deadline'      => Carbon::parse('2026-03-15'),
            'processed_at'  => Carbon::parse('2026-03-01 09:00:00'),
            'completed_at'  => Carbon::parse('2026-03-10 15:30:00'),
        ]);

        // ========================================
        // F) TUGAS DIBATALKAN
        // ========================================
        $task5 = Task::create([
            'user_id'      => $pegawai2->id,
            'assigned_by'  => $pengawas->id,
            'title'        => 'Pengadaan Printer Warna Baru',
            'description'  => 'Melakukan pengadaan printer warna untuk ruang administrasi. Dibatalkan karena anggaran dialihkan ke kebutuhan lain.',
            'status'       => 'cancelled',
            'urgency'      => 2,
            'deadline'     => Carbon::parse('2026-03-14'),
            'processed_at' => Carbon::parse('2026-03-04 08:00:00'),
            'cancelled_at' => Carbon::parse('2026-03-06 11:00:00'),
        ]);

        $this->command->info('✅ Tugas dummy berhasil dibuat.');

        // ===================================================================
        // LOGBOOK HARIAN
        // ===================================================================

        // --- LOGBOOK PEGAWAI 1 ---
        // Hari 1: 5 Maret
        $log1_d1 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-05', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d1->id,
            'task_id'           => $task1->id,
            'previous_progress' => 0,
            'current_progress'  => 15,
            'activity'          => 'Mengumpulkan data KPI dari seluruh divisi melalui email dan sistem HRIS. Menginput data awal ke template laporan.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d1->id,
            'task_id'           => $task2->id,
            'previous_progress' => 0,
            'current_progress'  => 30,
            'activity'          => 'Memverifikasi data pegawai divisi keuangan dan divisi SDM. Menemukan 12 data yang perlu diperbaiki.',
        ]);

        // Hari 2: 6 Maret
        $log1_d2 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-06', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d2->id,
            'task_id'           => $task1->id,
            'previous_progress' => 15,
            'current_progress'  => 35,
            'activity'          => 'Melakukan analisis pencapaian KPI triwulan I untuk divisi operasional dan divisi pemasaran. Membuat grafik perbandingan target vs realisasi.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d2->id,
            'task_id'           => $task4->id,
            'previous_progress' => 0,
            'current_progress'  => 20,
            'activity'          => 'Melakukan pencatatan awal aset di ruang rapat dan ruang server lantai 3. Menginventarisir 45 unit peralatan elektronik.',
        ]);

        // Hari 3: 7 Maret
        $log1_d3 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-07', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d3->id,
            'task_id'           => $task2->id,
            'previous_progress' => 30,
            'current_progress'  => 70,
            'activity'          => 'Menyelesaikan update data pegawai divisi IT dan divisi umum. Memperbaiki 8 entri yang salah format pada sistem.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d3->id,
            'task_id'           => $taskGroup1a->id,
            'previous_progress' => 0,
            'current_progress'  => 20,
            'activity'          => 'Menyusun draft outline presentasi materi kebijakan baru. Berdiskusi dengan tim terkait pembagian tugas penyiapan dokumen.',
        ]);
        // Pekerjaan lainnya (custom)
        LogbookItem::create([
            'logbook_id'        => $log1_d3->id,
            'task_id'           => null,
            'custom_task_name'  => 'Rapat Koordinasi Internal',
            'previous_progress' => 0,
            'current_progress'  => 0,
            'activity'          => 'Menghadiri rapat koordinasi internal mingguan. Membahas prioritas kerja minggu depan dan kendala operasional.',
        ]);

        // Hari 4: 9 Maret
        $log1_d4 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-09', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d4->id,
            'task_id'           => $task2->id,
            'previous_progress' => 70,
            'current_progress'  => 100,
            'activity'          => 'Menyelesaikan seluruh entri update database. Melakukan verifikasi akhir dan mengirimkan laporan perubahan ke atasan.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d4->id,
            'task_id'           => $taskGroup2a->id,
            'previous_progress' => 0,
            'current_progress'  => 40,
            'activity'          => 'Merancang kuesioner survei kepuasan pelayanan publik. Menyusun 25 pertanyaan mencakup aspek waktu respons, keramahan, dan kualitas layanan.',
        ]);

        // Hari 5: 10 Maret
        $log1_d5 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-10', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d5->id,
            'task_id'           => $task1->id,
            'previous_progress' => 35,
            'current_progress'  => 55,
            'activity'          => 'Menyelesaikan analisis KPI divisi hukum dan divisi perencanaan. Mulai menyusun rekomendasi perbaikan kinerja.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d5->id,
            'task_id'           => $taskGroup2a->id,
            'previous_progress' => 40,
            'current_progress'  => 75,
            'activity'          => 'Menyebarkan kuesioner kepada 100 responden melalui Google Forms dan menyebarkan di media sosial unit kerja.',
        ]);

        // Hari 6: 11 Maret
        $log1_d6 = Logbook::create(['user_id' => $pegawai1->id, 'date' => '2026-03-11', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log1_d6->id,
            'task_id'           => $taskGroup2a->id,
            'previous_progress' => 75,
            'current_progress'  => 100,
            'activity'          => 'Menganalisis 87 tanggapan kuesioner. Menyusun laporan hasil survei berupa grafik persentase kepuasan dan narasi temuan utama.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log1_d6->id,
            'task_id'           => $taskGroup1a->id,
            'previous_progress' => 20,
            'current_progress'  => 50,
            'activity'          => 'Menyusun slide presentasi kebijakan baru (15 slide). Menambahkan data statistik pendukung dan infografis.',
        ]);

        // --- LOGBOOK PEGAWAI 2 ---
        // Hari 1: 5 Maret
        $log2_d1 = Logbook::create(['user_id' => $pegawai2->id, 'date' => '2026-03-05', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log2_d1->id,
            'task_id'           => $task3->id,
            'previous_progress' => 0,
            'current_progress'  => 0,
            'activity'          => 'Membaca regulasi terbaru tentang pengadaan barang dan jasa. Mencatat poin-poin yang berbeda dari SOP sebelumnya.',
        ]);

        // Hari 2: 7 Maret
        $log2_d2 = Logbook::create(['user_id' => $pegawai2->id, 'date' => '2026-03-07', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log2_d2->id,
            'task_id'           => $taskGroup1b->id,
            'previous_progress' => 0,
            'current_progress'  => 15,
            'activity'          => 'Menyusun daftar kebutuhan logistik rapat nasional: konsumsi, akomodasi, dan transportasi untuk 50 peserta.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log2_d2->id,
            'task_id'           => $taskGroup2b->id,
            'previous_progress' => 0,
            'current_progress'  => 30,
            'activity'          => 'Membantu distribusi kuesioner survei kepuasan publik ke masyarakat sekitar kantor. Mengumpulkan 35 respons langsung.',
        ]);

        // Hari 3: 8 Maret
        $log2_d3 = Logbook::create(['user_id' => $pegawai2->id, 'date' => '2026-03-08', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log2_d3->id,
            'task_id'           => $taskGroup2b->id,
            'previous_progress' => 30,
            'current_progress'  => 65,
            'activity'          => 'Melanjutkan pengumpulan data survei. Menginput hasil jawaban dari formulir kertas ke sistem digital.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log2_d3->id,
            'task_id'           => null,
            'custom_task_name'  => 'Pelatihan Aplikasi eSurat',
            'previous_progress' => 0,
            'current_progress'  => 0,
            'activity'          => 'Mengikuti pelatihan penggunaan aplikasi eSurat baru selama 3 jam. Mempelajari fitur disposisi otomatis dan tracking surat.',
        ]);

        // Hari 4: 10 Maret
        $log2_d4 = Logbook::create(['user_id' => $pegawai2->id, 'date' => '2026-03-10', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log2_d4->id,
            'task_id'           => $taskGroup2b->id,
            'previous_progress' => 65,
            'current_progress'  => 100,
            'activity'          => 'Menyelesaikan kompilasi data survei. Membuat cross-tabulation dan menyerahkan data mentah ke Pegawai 1 untuk analisis akhir.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log2_d4->id,
            'task_id'           => $taskGroup1b->id,
            'previous_progress' => 15,
            'current_progress'  => 40,
            'activity'          => 'Menghubungi vendor catering dan hotel untuk penawaran harga. Mendapatkan 3 penawaran yang dibandingkan dalam tabel evaluasi.',
        ]);

        // Hari 5: 11 Maret
        $log2_d5 = Logbook::create(['user_id' => $pegawai2->id, 'date' => '2026-03-11', 'is_submitted' => true]);
        LogbookItem::create([
            'logbook_id'        => $log2_d5->id,
            'task_id'           => $taskGroup1b->id,
            'previous_progress' => 40,
            'current_progress'  => 60,
            'activity'          => 'Memfinalisasi pemilihan vendor dan mengirimkan dokumen pemesanan. Menyusun rundown acara rapat kerja nasional.',
        ]);
        LogbookItem::create([
            'logbook_id'        => $log2_d5->id,
            'task_id'           => $task3->id,
            'previous_progress' => 0,
            'current_progress'  => 20,
            'activity'          => 'Mulai merevisi Bab 1 dan Bab 2 dokumen SOP Pengadaan. Menyesuaikan alur persetujuan sesuai regulasi baru.',
        ]);

        $this->command->info('✅ Logbook dummy berhasil dibuat.');
        $this->command->info('Selesai! Total tugas: ' . Task::count() . ', Total logbook: ' . Logbook::count());
    }
}
