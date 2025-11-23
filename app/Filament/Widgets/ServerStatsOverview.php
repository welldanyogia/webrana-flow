<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStatsOverview extends BaseWidget
{
    public ?Server $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // 1. Current Status
        $status = $this->record->status === 'active' ? 'Active' : 'Down';
        $statusColor = $this->record->status === 'active' ? 'success' : 'danger';
        $statusIcon = $this->record->status === 'active' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';

        // 2. Uptime Percentage (24H)
        $totalChecks = $this->record->metrics()
            ->where('created_at', '>=', now()->subDay())
            ->count();
        
        $onlineChecks = $this->record->metrics()
            ->where('created_at', '>=', now()->subDay())
            ->where('is_online', true)
            ->count();

        $uptimePercentage = $totalChecks > 0 ? round(($onlineChecks / $totalChecks) * 100, 2) : 0;

        // 3. Last Updated
        $lastMetric = $this->record->metrics()->latest()->first();
        $lastUpdated = $lastMetric ? $lastMetric->created_at->diffForHumans() : 'Never';

        return [
            Stat::make('Current Status', $status)
                ->color($statusColor)
                ->icon($statusIcon),
            
            Stat::make('Uptime (24H)', $uptimePercentage . '%')
                ->description('Based on last 24 hours monitoring')
                ->color($uptimePercentage > 99 ? 'success' : ($uptimePercentage > 90 ? 'warning' : 'danger')),

            Stat::make('Last Updated', $lastUpdated)
                ->icon('heroicon-m-clock'),
        ];
    }
}
