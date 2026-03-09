<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // [5] Tab berdasarkan status
    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet'),

            'menunggu' => Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'proses' => Tab::make('Proses')
                ->icon('heroicon-o-arrow-path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'selesai' => Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'batal' => Tab::make('Batal')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}