<?php

namespace App\Filament\Resources\MonitoringTaskResource\Pages;

use App\Filament\Resources\MonitoringTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoringTasks extends ListRecords
{
    protected static string $resource = MonitoringTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Disable Create
        ];
    }
}