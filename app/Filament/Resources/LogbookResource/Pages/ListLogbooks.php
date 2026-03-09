<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLogbooks extends ListRecords
{
    protected static string $resource = LogbookResource::class;

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

            'draft' => Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_submitted', false)),

            'final' => Tab::make('Final')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_submitted', true)),
        ];
    }
}
