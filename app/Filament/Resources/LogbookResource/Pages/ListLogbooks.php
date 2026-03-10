<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ListLogbooks extends ListRecords
{
    protected static string $resource = LogbookResource::class;

    // Tambahkan method mount() untuk mengecek data kedaluwarsa saat halaman tabel dimuat
    public function mount(): void
    {
        parent::mount();

        // Cari semua logbook yang masih draft (false) dan tanggalnya sebelum hari ini
        $overdueLogbooks = \App\Models\Logbook::where('is_submitted', false)
            ->whereDate('date', '<', now()->format('Y-m-d'))
            ->get();

        if ($overdueLogbooks->count() > 0) {
            foreach ($overdueLogbooks as $logbook) {
                // 1. Kunci logbook menjadi Final
                $logbook->update(['is_submitted' => true]);

                // 2. Update status tugas yang progresnya 100%
                foreach ($logbook->items as $item) {
                    if ($item->task_id && $item->current_progress == 100) {
                        \App\Models\Task::where('id', $item->task_id)->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                    }
                }
            }

            // Tampilkan notifikasi peringatan di halaman tabel
            Notification::make()
                ->title('Pembaruan Sistem')
                ->body($overdueLogbooks->count() . ' logbook hari sebelumnya telah otomatis difinalkan.')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet'),

            'draft' => Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_submitted', false)),

            'final' => Tab::make('Final')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_submitted', true)),
        ];
    }
}