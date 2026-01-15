<?php

namespace App\Filament\Resources\SubunitResource\Pages;

use App\Filament\Resources\SubunitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubunit extends EditRecord
{
    protected static string $resource = SubunitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
