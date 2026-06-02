<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Support\AppointmentDashboardScope;
use App\Filament\Widgets\AppointmentsByBranchChartWidget;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_scope_only_returns_own_appointments(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staffA = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $staffB = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);

        $appointmentForA = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffA->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffB->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc->copy()->addHours(2),
            'end_at' => $startUtc->copy()->addHours(3),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $ids = app(AppointmentDashboardScope::class)
            ->appointmentsQuery($staffA)
            ->pluck('id')
            ->all();

        $this->assertSame([$appointmentForA->id], $ids);
    }

    public function test_branch_breakdown_widget_is_hidden_for_staff_and_visible_for_admin(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $admin = User::factory()->admin()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($staff);
        $this->assertFalse(AppointmentsByBranchChartWidget::canView());

        $this->actingAs($admin);
        $this->assertTrue(AppointmentsByBranchChartWidget::canView());
    }

    public function test_staff_dashboard_headings_use_my_wording(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $admin = User::factory()->admin()->create(['role' => UserRole::ADMIN]);
        $scope = app(AppointmentDashboardScope::class);

        $this->assertSame('My Appointment Status', $scope->statusHeading($staff));
        $this->assertSame('My Bookings Last 7 Days', $scope->trendHeading($staff));
        $this->assertSame('Appointment Status', $scope->statusHeading($admin));
        $this->assertSame('Bookings Last 7 Days', $scope->trendHeading($admin));
    }
}
