<?php

namespace App\Filament\Dashboard\Resources\Applications;

use App\Filament\Dashboard\Resources\Applications\Pages;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'My Projects';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    // --- STEP 1: IMPORT REPO ---
                    Step::make('Import Git Repository')
                        ->icon('heroicon-m-code-bracket')
                        ->description('Connect a Git repository to deploy.')
                        ->schema([
                            Select::make('repository_url')
                                ->label('Repository')
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $parts = explode('/', rtrim($state, '/'));
                                        $repoName = end($parts);
                                        $set('name', $repoName);

                                        // Reset branch jadi null saat repo ganti
                                        $set('branch', null);
                                    }
                                })
                                ->getSearchResultsUsing(function (string $search) {
                                    $gitAccount = auth()->user()->gitAccounts()->where('provider', 'github')->first();
                                    if (! $gitAccount) return [];

                                    $response = Http::withToken($gitAccount->access_token)
                                        ->get('https://api.github.com/search/repositories', [
                                            'q' => "$search user:{$gitAccount->username} fork:true",
                                            'per_page' => 20,
                                        ]);

                                    if ($response->failed()) return [];

                                    return collect($response->json()['items'])->pluck('full_name', 'html_url');
                                })
                                ->suffixAction(
                                    Action::make('connect_github')
                                        ->icon('heroicon-m-command-line')
                                        ->label('Connect GitHub')
                                        ->url(route('github.connect'))
                                        ->visible(fn () => ! auth()->user()->gitAccounts()->where('provider', 'github')->exists())
                                )
                                ->helperText(fn () => auth()->user()->gitAccounts()->where('provider', 'github')->exists()
                                    ? 'Connected as: ' . auth()->user()->gitAccounts->first()->username
                                    : 'Please connect your GitHub account first.'),
                        ]),

                    // --- STEP 2: CONFIGURE PROJECT ---
                    Step::make('Configure Project')
                        ->icon('heroicon-m-adjustments-horizontal')
                        ->description('Configure build settings.')
                        ->schema([
                            TextInput::make('name')
                                ->label('Project Name')
                                ->required()
                                ->placeholder('my-startup-app')
                                ->helperText('Nama aplikasi di dashboard.'),

                            Select::make('branch')
                                ->label('Git Branch')
                                ->required()
                                ->placeholder('Select branch...')
                                ->searchable()
                                ->live() // Adding live() ensures validation triggers immediately
                                ->options(function (Get $get) {
                                    $repoUrl = $get('repository_url');
                                    if (! $repoUrl) return [];

                                    $gitAccount = auth()->user()->gitAccounts()->where('provider', 'github')->first();
                                    if (! $gitAccount) return [];

                                    $path = parse_url($repoUrl, PHP_URL_PATH);
                                    $fullName = ltrim($path, '/');

                                    $response = Http::withToken($gitAccount->access_token)
                                        ->get("https://api.github.com/repos/{$fullName}/branches");

                                    if ($response->failed()) return []; // Return empty array if failed, forces selection from empty list (impossible)

                                    return collect($response->json())->pluck('name', 'name');
                                }),
                            // Removed default('main') to enforce selection

                            Select::make('build_pack')
                                ->label('Framework Preset')
                                ->options([
                                    'laravel' => 'Laravel',
                                    'nodejs' => 'NodeJS',
                                    'dockerfile' => 'Dockerfile',
                                ])
                                ->default('laravel')
                                ->required(),

                            KeyValue::make('env_variables')
                                ->label('Environment Variables')
                                ->keyLabel('KEY')
                                ->valueLabel('VALUE')
                                ->columnSpanFull(),
                        ])->columns(2),

                    // --- STEP 3: REVIEW & DEPLOY ---
                    Step::make('Deploy')
                        ->icon('heroicon-m-rocket-launch')
                        ->description('Review and launch.')
                        ->schema([
                            Placeholder::make('review_repo')
                                ->label('Selected Repository')
                                ->content(fn (Get $get) => $get('repository_url')),

                            Placeholder::make('review_branch')
                                ->label('Branch to Deploy')
                                ->content(fn (Get $get) => $get('branch')),

                            Placeholder::make('info')
                                ->content('Your application will be deployed to the Vultr High-Frequency cloud.')
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary bg-primary-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-primary-500 transition">Deploy Now</button>'))
                    ->skippable() // NOTE: Consider removing skippable() if you want strictly enforced steps
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        ImageColumn::make('build_pack')
                            ->defaultImageUrl(url('/images/laravel-icon.svg'))
                            ->circular()
                            ->grow(false),

                        Stack::make([
                            TextColumn::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->searchable(),

                            TextColumn::make('domain_name')
                                ->color('gray')
                                ->size('sm')
                                ->icon('heroicon-m-link')
                                ->limit(30),
                        ])->space(1),

                        TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'running' => 'success',
                                'building' => 'warning',
                                'failed' => 'danger',
                                'provisioning' => 'warning',
                                default => 'gray',
                            })
                            ->grow(false),
                    ])->from('md'),

                    Stack::make([
                        TextColumn::make('repository_url')
                            ->formatStateUsing(fn ($state) => str_replace('https://github.com/', '', $state))
                            ->icon('heroicon-m-code-bracket')
                            ->color('gray')
                            ->size('sm'),

                        TextColumn::make('branch')
                            ->icon('heroicon-m-arrows-right-left')
                            ->color('gray')
                            ->size('sm')
                            ->prefix('Branch: '),

                        TextColumn::make('updated_at')
                            ->since()
                            ->prefix('Last deployed: ')
                            ->color('gray')
                            ->size('xs'),
                    ])
                        ->space(2)
                        ->extraAttributes([
                            'class' => 'mt-4 pt-4 border-t border-gray-200 dark:border-white/10',
                        ]),

                ])->space(3),
            ])
            ->extraAttributes(['class' => '!border-0 !shadow-none !bg-transparent'])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('redeploy')
                        ->label('Redeploy')
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn ($record) => \App\Services\CoolifyService::redeploy($record)),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->color('gray')
                    ->tooltip('Actions'),
            ]);
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
