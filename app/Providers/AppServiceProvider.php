<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentScore;
use App\Models\User;
use App\Observers\AuditModelObserver;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels() as $model) {
            $model::observe(AuditModelObserver::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            AuditLogger::log('login', 'Signed in', user: $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            AuditLogger::log('logout', 'Signed out', user: $event->user);
        });
    }

    /**
     * @return array<int, class-string>
     */
    protected function auditedModels(): array
    {
        return [
            Enrollment::class,
            Expense::class,
            FeePayment::class,
            ReportCard::class,
            School::class,
            Staff::class,
            Student::class,
            StudentInvoice::class,
            StudentScore::class,
            User::class,
        ];
    }
}
