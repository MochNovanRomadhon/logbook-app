<?php

namespace App\Filament\Resources\MonitoringLogbookResource\Pages;

use App\Filament\Resources\MonitoringLogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoringLogbooks extends ListRecords
{
    protected static string $resource = MonitoringLogbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
