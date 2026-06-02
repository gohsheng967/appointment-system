<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\View\View;

abstract class DashboardChartWidget extends ChartWidget
{
    protected ?string $placeholderHeight = '18rem';

    public function placeholder(): View
    {
        return view('filament.widgets.chart-placeholder', [
            'columnSpan' => $this->getColumnSpan(),
            'columnStart' => $this->getColumnStart(),
            'description' => $this->getDescription(),
            'heading' => $this->getHeading(),
            'height' => $this->getPlaceholderHeight(),
        ]);
    }
}
