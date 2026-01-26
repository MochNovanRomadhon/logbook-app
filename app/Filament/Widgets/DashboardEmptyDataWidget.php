<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardEmptyDataWidget extends Widget
{
    // Pastikan nama view ini sesuai dengan nama file blade yang Anda buat
    // Contoh: resources/views/filament/widgets/dashboard-empty-data-widget.blade.php
    protected static string $view = 'filament.widgets.dashboard-empty-data-widget';

    // Agar widget melebar penuh (full width)
    protected int | string | array $columnSpan = 'full';
}