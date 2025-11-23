<?php

namespace App\Filament\Dashboard\Resources\Applications\Pages;

use App\Filament\Dashboard\Resources\Applications;
use App\Filament\Dashboard\Resources\Applications\ApplicationResource;
use App\Models\Server;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Set User ID otomatis ke user yang sedang login
        $data['user_id'] = auth()->id();

        // 2. Algoritma Load Balancing Sederhana
        // Cari server yang aktif dan beban (load)-nya paling sedikit
        $server = Server::where('status', 'active')
            ->orderBy('current_apps_count', 'asc')
            ->first();

        if (! $server) {
            // Jika tidak ada server tersedia, tolak request
            throw ValidationException::withMessages([
                'name' => 'Maaf, semua server kami sedang penuh. Silakan hubungi support.',
            ]);
        }

        // 3. Assign Server ID ke data aplikasi
        $data['server_id'] = $server->id;

        return $data;
    }

    // Redirect ke list setelah sukses
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
