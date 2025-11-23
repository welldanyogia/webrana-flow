<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use Filament\Widgets\ChartWidget;

class ServerResourceChart extends ChartWidget
{
    protected ?string $heading = 'Server Resource Usage (24H)';

    public ?Server $record = null;

    protected function getData(): array
    {
        if (!$this->record) {
            return [];
        }

        $metrics = $this->record->metrics()
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'CPU Usage (%)',
                    'data' => $metrics->map(fn ($m) => $m->is_online ? $m->cpu_usage : 0)->toArray(),
                    'borderColor' => '#ef4444', // Red
                ],
                [
                    'label' => 'RAM Usage (%)',
                    'data' => $metrics->map(fn ($m) => $m->is_online ? $m->ram_usage : 0)->toArray(),
                    'borderColor' => '#3b82f6', // Blue
                ],
            ],
            'labels' => $metrics->map(fn ($m) => $m->created_at->format('H:i'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
