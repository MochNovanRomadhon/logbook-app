<?php

namespace App\Filament\Resources\MonitoringLogbookResource\Pages;

use App\Filament\Resources\MonitoringLogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

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
