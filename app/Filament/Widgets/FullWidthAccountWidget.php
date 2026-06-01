<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class FullWidthAccountWidget extends AccountWidget
{
    protected int|string|array $columnSpan = 'full';
}
