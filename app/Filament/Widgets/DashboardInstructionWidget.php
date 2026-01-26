<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardInstructionWidget extends Widget
{
    // Pastikan nama view ini sesuai dengan nama file blade yang Anda buat
    // Contoh: resources/views/filament/widgets/dashboard-instruction-widget.blade.php
    protected static string $view = 'filament.widgets.dashboard-instruction-widget';

    // Agar widget melebar penuh (full width)
    protected int | string | array $columnSpan = 'full';
}