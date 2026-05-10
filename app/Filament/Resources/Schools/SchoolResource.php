<?php

namespace App\Filament\Resources\Schools;

use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\Pages\ViewSchool;
use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Filament\Resources\Schools\Tables\SchoolsTable;
use App\Models\School;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'School';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return SchoolForm::configure($schema, includeAdminAccount: true);
    }

    public static function table(Table $table): Table
    {
        return SchoolsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('School profile')
                            ->schema([
                                ImageEntry::make('logo_path')
                                    ->label('Logo')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                                    ->height(120),
                                TextEntry::make('name')
                                    ->label('School name')
                                    ->weight('700'),
                                TextEntry::make('code')
                                    ->label('School code')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('slug')
                                    ->label('Portal link')
                                    ->state(fn (School $record): string => url("/portal/{$record->slug}"))
                                    ->copyable()
                                    ->copyMessage('Portal link copied'),
                            ])
                            ->columnSpan(1),
                        Section::make('Contact information')
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email address')
                                    ->placeholder('Not set'),
                                TextEntry::make('phone')
                                    ->label('Phone number')
                                    ->placeholder('Not set'),
                                TextEntry::make('address')
                                    ->label('Address')
                                    ->placeholder('Not set'),
                                TextEntry::make('city')
                                    ->placeholder('Not set'),
                                TextEntry::make('state')
                                    ->placeholder('Not set'),
                                TextEntry::make('country')
                                    ->placeholder('Not set'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ]),
                Grid::make(3)
                    ->schema([
                        Section::make('Subscription')
                            ->schema([
                                TextEntry::make('subscription_plan')
                                    ->label('Current plan')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'trial' => 'Free Trial',
                                        'basic_ngn' => 'Basic',
                                        'standard_ngn' => 'Standard',
                                        'premium_ngn' => 'Premium',
                                        default => (string) $state,
                                    })
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'trial' => 'gray',
                                        'basic_ngn' => 'info',
                                        'standard_ngn' => 'success',
                                        'premium_ngn' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('subscription_expires_at')
                                    ->label('Expires at')
                                    ->dateTime()
                                    ->placeholder('Not set'),
                            ])
                            ->columnSpan(2),
                        Section::make('Status')
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('student_limit')
                                    ->label('Student capacity')
                                    ->numeric(),
                                TextEntry::make('created_at')
                                    ->label('Created at')
                                    ->dateTime(),
                            ])
                            ->columnSpan(1),
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
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'view' => ViewSchool::route('/{record}'),
            'edit' => EditSchool::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && (bool) Filament::auth()->user()?->is_platform_admin;
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canEdit(Model $record): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canView(Model $record): bool
    {
        return static::shouldRegisterNavigation();
    }
}
