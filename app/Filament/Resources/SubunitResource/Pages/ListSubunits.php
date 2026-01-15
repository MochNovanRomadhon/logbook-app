<?php

namespace App\Filament\Resources\SubunitResource\Pages;

use App\Filament\Resources\SubunitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubunits extends ListRecords
{
    protected static string $resource = SubunitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
