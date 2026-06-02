<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use App\Support\SubmissionFeedback;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

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
        Gate::policy(Appointment::class, AppointmentPolicy::class);

        BasePage::$reportValidationErrorUsing = static function (ValidationException $exception): void {
            Notification::make()
                ->danger()
                ->title(SubmissionFeedback::failureTitle())
                ->body(SubmissionFeedback::validationFailureBody($exception->errors()))
                ->persistent()
                ->send();
        };
    }
}
