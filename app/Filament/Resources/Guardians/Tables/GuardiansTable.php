<?php

namespace App\Filament\Resources\Guardians\Tables;

use App\Mail\LoginCredentialsMail;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('children')
                    ->label('Children')
                    ->state(fn ($record): string => self::childrenSummary($record))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label('Login')
                    ->badge()
                    ->placeholder('No login')
                    ->toggleable(),
                TextColumn::make('occupation')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
            ])
            ->recordActions([
                Action::make('createParentLogin')
                    ->label(fn ($record): string => $record->user_id ? 'Sync login' : 'Create login')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->visible(fn ($record): bool => filled($record->email))
                    ->action(function ($record): void {
                        $existing = User::query()->where('email', $record->email)->first();
                        $temporaryPassword = null;

                        if ($existing) {
                            $user = $existing;
                        } else {
                            $temporaryPassword = Str::password(10, symbols: false);

                            $user = User::query()->create([
                                'email' => $record->email,
                                'name' => $record->name,
                                'password' => Hash::make($temporaryPassword),
                                'is_platform_admin' => false,
                                'is_active' => true,
                                'must_change_password' => false,
                            ]);
                        }

                        $record->forceFill(['user_id' => $user->getKey()])->save();

                        $user->schools()->syncWithoutDetaching([
                            $record->school_id => [
                                'role' => 'parent',
                                'is_primary' => false,
                            ],
                        ]);

                        $emailSent = false;

                        if ($temporaryPassword) {
                            $emailSent = self::sendLoginCredentials($record, $user, $temporaryPassword);
                        }

                        Notification::make()
                            ->success()
                            ->persistent()
                            ->title('Parent login ready')
                            ->body($temporaryPassword
                                ? ($emailSent
                                    ? "Login details have been emailed to {$user->email}."
                                    : "Email: {$user->email}\nPassword: {$temporaryPassword}\n\nEmail could not be sent. Share this with the parent manually.")
                                : "Email: {$user->email} already had a login — the parent role has been linked. Their existing password is unchanged.")
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function sendLoginCredentials($guardian, User $user, string $temporaryPassword): bool
    {
        $school = School::query()->find($guardian->school_id);

        try {
            Mail::to($user->email)->send(new LoginCredentialsMail(
                school: $school,
                name: $guardian->name,
                email: $user->email,
                temporaryPassword: $temporaryPassword,
                portalUrl: $school?->portalUrl() ?? url('/'),
                roleLabel: 'parent portal',
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected static function childrenSummary($guardian): string
    {
        $guardian->loadMissing('studentLinks.student.enrollments.schoolClass', 'studentLinks.student.enrollments.classSection');

        return $guardian->studentLinks
            ->map(function ($link): ?string {
                $student = $link->student;

                if (! $student) {
                    return null;
                }

                return trim($student->full_name.' · '.($student->currentClassLabel() ?: 'No class'));
            })
            ->filter()
            ->join("\n") ?: 'No children linked';
    }
}
