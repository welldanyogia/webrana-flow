<?php

namespace App\Filament\Resources\Servers;

use App\Filament\Resources\Servers\Pages\CreateServer;
use App\Filament\Resources\Servers\Pages\EditServer;
use App\Filament\Resources\Servers\Pages\ListServers;
use App\Models\Server;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    // Disable policy checking - Bypass Filament authorization
    protected static bool $shouldCheckPolicyExistence = false;

//    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Server';

    // Di v4 icon tetap string
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|null|\UnitEnum $navigationGroup = 'Infrastructure';

    // PERHATIKAN: Parameter sekarang adalah 'Schema', bukan 'Form'
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Server Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('e.g. SG-Vultr-Primary'),

                        Select::make('provider')
                            ->options([
                                'vultr' => 'Vultr',
                                'digitalocean' => 'DigitalOcean',
                                'google' => 'Google Cloud',
                            ])->required(),

                        TextInput::make('ip_address')
                            ->required()
                            ->ip(),

                        TextInput::make('private_ip_address')
                            ->ip(),
                    ])->columns(2),

                Section::make('SSH Configuration')
                    ->description('Credentials for server monitoring')
                    ->schema([
                        TextInput::make('ssh_user')
                            ->default('root')
                            ->required(),
                        
                        TextInput::make('ssh_port')
                            ->numeric()
                            ->default(22)
                            ->required(),
                        
                        \Filament\Forms\Components\Textarea::make('ssh_private_key')
                            ->label('SSH Private Key')
                            ->helperText('Paste your OpenSSH private key here. It will be encrypted.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Coolify Engine')
                    ->schema([
                        TextInput::make('coolify_api_url')
                            ->required()
                            ->url()
                            ->placeholder('http://ip-address:8000'),

                        TextInput::make('coolify_api_token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Capacity & Status')
                    ->schema([
                        TextInput::make('max_capacity_apps')
                            ->numeric()
                            ->default(50),

                        TextInput::make('current_apps_count')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'maintenance' => 'Maintenance',
                                'full' => 'Full Capacity',
                            ])->default('active'),
                    ])->columns(3),
            ]);
    }

    // Table di v4 biasanya masih kompatibel, tapi pastikan import TextColumn benar
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->copyable()
                    ->icon('heroicon-o-computer-desktop'),

                TextColumn::make('current_apps_count')
                    ->label('Load')
                    ->formatStateUsing(fn ($state, Server $record) => "$state / {$record->max_capacity_apps}")
                    ->color(fn (Server $record) => $record->current_apps_count >= $record->max_capacity_apps ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'locked',
                        'gray' => 'full',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }

}
