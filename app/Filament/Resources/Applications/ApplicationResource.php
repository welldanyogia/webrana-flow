<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages;
use App\Models\Application;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // v4 Standard
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string|null|\UnitEnum $navigationGroup = 'Deployments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. INFO PROJECT (User & Admin)
                Section::make('Project Information')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->visible(fn () => auth()->user()->hasRole('super_admin')) // User biasa gak perlu pilih user (otomatis diri sendiri)
                            ->label('Owner (User)'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Application Name')
                            ->placeholder('my-awesome-saas'),

                        TextInput::make('repository_url')
                            ->required()
                            ->url()
                            ->columnSpan(2)
                            ->placeholder('https://github.com/username/repository'),

                        TextInput::make('branch')
                            ->default('main')
                            ->placeholder('main'),

                        Select::make('git_account_id')
                            ->relationship('gitAccount', 'username')
                            ->label('Git Account')
                            ->helperText('Select connected GitHub/GitLab account if private repo.'),
                    ])->columns(2),

                // 2. CONFIG ENV (User & Admin)
                Section::make('Environment & Config')
                    ->schema([
                        Select::make('build_pack')
                            ->options([
                                'laravel' => 'Laravel Application',
                                'nodejs' => 'NodeJS / NextJS',
                                'dockerfile' => 'Custom Dockerfile',
                            ])
                            ->default('laravel')
                            ->required(),

                        Select::make('php_version')
                            ->options([
                                '8.3' => 'PHP 8.3',
                                '8.2' => 'PHP 8.2',
                                '8.1' => 'PHP 8.1',
                            ])
                            ->default('8.2')
                            ->visible(fn ($get) => $get('build_pack') === 'laravel'),

                        KeyValue::make('env_variables')
                            ->keyLabel('ENV KEY')
                            ->valueLabel('VALUE')
                            ->columnSpanFull()
                            ->helperText('Add your .env variables here (APP_KEY, DB_PASSWORD, etc).'),
                    ])->columns(2),

                // 3. ADMIN AREA - UNLIMITED POWER (Hanya Admin)
                Section::make('Admin Overrides (Super Admin Only)')
                    ->description('Force deployment to specific server or override resource limits.')
                    ->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->label('Force Deploy to Server')
                            ->placeholder('Auto-Balancing (Default)'),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('custom_cpu_limit')
                                    ->label('Override CPU')
                                    ->placeholder('e.g., 4'),

                                TextInput::make('custom_ram_limit')
                                    ->label('Override RAM')
                                    ->placeholder('e.g., 8G'),
                            ]),

                        Select::make('status')
                            ->options([
                                'provisioning' => 'Provisioning',
                                'running' => 'Running',
                                'stopped' => 'Stopped',
                                'failed' => 'Failed',
                                'maintenance' => 'Maintenance',
                            ]),
                    ])
                    ->visible(fn () => auth()->user()->hasRole('super_admin')) // KUNCI KEAMANAN
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (Application $record) => $record->repository_url),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // User biasa gak perlu lihat kolom owner

                TextColumn::make('server.name')
                    ->label('Node')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'provisioning',
                        'success' => 'running',
                        'danger' => 'failed',
                        'gray' => 'stopped',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'running' => 'Running',
                        'stopped' => 'Stopped',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('server')
                    ->relationship('server', 'name'),
            ])
            ->actions([
                EditAction::make(),
                // Custom Action untuk trigger redeploy manual
                Action::make('redeploy')
                    ->label('Redeploy')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Application $record) => \App\Services\CoolifyService::redeploy($record)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Bisa tambah RelationManager untuk 'Domains' atau 'Deployments' disini nanti
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
