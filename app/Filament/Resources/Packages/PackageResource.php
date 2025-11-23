<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages;
use App\Models\Package;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // v4 Standard
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-archive-box';
    protected static string|null|\UnitEnum $navigationGroup = 'Product & Billing';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pricing Plan Details')
                    ->description('Set the pricing and identity of the package.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('price_monthly')
                            ->label('Monthly Price')
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),

                        TextInput::make('price_yearly')
                            ->label('Yearly Price')
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),
                    ])->columns(2),

                Section::make('Docker Resource Limits')
                    ->description('These limits will be enforced by Coolify.')
                    ->schema([
                        TextInput::make('max_applications')
                            ->numeric()
                            ->required()
                            ->label('Max Apps Count'),

                        TextInput::make('max_databases')
                            ->numeric()
                            ->required()
                            ->label('Max Databases'),

                        TextInput::make('cpu_limit')
                            ->default('0.5')
                            ->label('CPU Limit (vCPU)')
                            ->helperText('e.g., 0.5 for half core, 1 for full core'),

                        TextInput::make('ram_limit')
                            ->default('256M')
                            ->label('RAM Limit')
                            ->helperText('e.g., 256M, 512M, 1G'),
                    ])->columns(2),

                Section::make('Features & Toggles')
                    ->schema([
                        Toggle::make('is_shared_db')
                            ->label('Use Shared Database')
                            ->helperText('If enabled, apps will use a shared DB instance instead of a dedicated container.')
                            ->default(true),

                        Toggle::make('allow_custom_domain')
                            ->label('Allow Custom Domain')
                            ->default(false),

                        Toggle::make('allow_auto_backup')
                            ->label('Enable Auto Backup')
                            ->default(false),

                        Toggle::make('allow_high_availability')
                            ->label('Enable High Availability (Mirroring)')
                            ->helperText('Requires standby server infrastructure.')
                            ->default(false),

                        Toggle::make('is_featured')
                            ->label('Highlight as "Popular"')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('price_monthly')
                    ->money('IDR')
                    ->label('Monthly'),

                TextColumn::make('max_applications')
                    ->label('Apps Limit')
                    ->alignCenter(),

                TextColumn::make('ram_limit')
                    ->label('RAM')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
