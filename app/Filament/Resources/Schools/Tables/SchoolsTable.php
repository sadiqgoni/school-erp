<?php

namespace App\Filament\Resources\Schools\Tables;

use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

class SchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNull('parent_school_id'))
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->visibility('public')
                    ->state(fn (School $record): ?string => $record->displayLogoPath())
                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => collect([$record->code, $record->email, $record->phone])->filter()->join(' | ')),
                TextColumn::make('sections')
                    ->label('Sections')
                    ->state(function (School $record): string {
                        return $record->divisions()
                            ->pluck('division')
                            ->map(fn (string $division): string => School::DIVISIONS[$division] ?? $division)
                            ->join(', ') ?: 'None';
                    })
                    ->toggleable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Portal URL')
                    ->state(fn (School $record): string => '/portal/'.$record->portalSchool()->slug)
                    ->copyable()
                    ->copyMessage('Portal URL copied')
                    ->toggleable(),
                TextColumn::make('city')
                    ->state(fn ($record): string => collect([$record->city, $record->state])->filter()->join(', '))
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('subscription_plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trial' => 'gray',
                        'basic_ngn' => 'info',
                        'standard_ngn' => 'success',
                        'premium_ngn' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('student_limit')
                    ->numeric()
                    ->sortable()
                    ->label('Capacity'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_plan')
                    ->options([
                        'trial' => 'Free Trial',
                        'basic_ngn' => 'Basic',
                        'standard_ngn' => 'Standard',
                        'premium_ngn' => 'Premium',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('openPortal')
                    ->label('Open portal')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (School $record): string => $record->portalUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (School $record): bool => ! $record->trashed()),
                Action::make('sendPortalAccess')
                    ->label('Send portal access')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send portal access email?')
                    ->modalDescription('This sends a fresh password reset link to the school admin so they can set a password and log in.')
                    ->visible(fn (School $record): bool => ! $record->trashed())
                    ->action(function (School $record): void {
                        $admin = self::schoolAdminFor($record);

                        if (! $admin) {
                            Notification::make()
                                ->title('No school admin found')
                                ->body('Create or link a school admin user before sending portal access.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Password::deleteToken($admin);

                        $status = Password::sendResetLink(['email' => $admin->email]);

                        if ($status === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title('Portal access email sent')
                                ->body("A reset link was sent to {$admin->email}.")
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Portal access email was not sent')
                            ->body(__($status))
                            ->danger()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (School $record): bool => ! $record->trashed()),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->label('Delete permanently'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->label('Delete permanently'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No schools yet')
            ->emptyStateDescription('Create the first school and its login to open the school portal.')
            ->striped();
    }

    protected static function schoolAdminFor(School $school): ?User
    {
        return User::query()
            ->whereHas('schools', fn ($query) => $query
                ->withoutGlobalScope('school-panel-current-tenant')
                ->where(fn ($query) => $query
                    ->whereKey($school->getKey())
                    ->orWhere('parent_school_id', $school->getKey()))
                ->where('school_user.role', User::SCHOOL_ROLE_ADMIN))
            ->orderBy('id')
            ->first();
    }
}
