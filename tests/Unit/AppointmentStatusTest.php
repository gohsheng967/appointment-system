<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function test_blocking_statuses_match_latest_rule(): void
    {
        $this->assertSame(
            [
                AppointmentStatus::PENDING,
                AppointmentStatus::CONFIRMED,
                AppointmentStatus::COMPLETED,
            ],
            AppointmentStatus::blockingStatuses(),
        );
    }

    public function test_status_flow_matches_latest_rule(): void
    {
        $this->assertTrue(AppointmentStatus::PENDING->canTransitionTo(AppointmentStatus::CONFIRMED));
        $this->assertTrue(AppointmentStatus::PENDING->canTransitionTo(AppointmentStatus::CANCELLED));
        $this->assertFalse(AppointmentStatus::PENDING->canTransitionTo(AppointmentStatus::IN_PROGRESS));
        $this->assertFalse(AppointmentStatus::PENDING->canTransitionTo(AppointmentStatus::NO_SHOW));
        $this->assertFalse(AppointmentStatus::PENDING->canTransitionTo(AppointmentStatus::COMPLETED));

        $this->assertTrue(AppointmentStatus::CONFIRMED->canTransitionTo(AppointmentStatus::IN_PROGRESS));
        $this->assertTrue(AppointmentStatus::CONFIRMED->canTransitionTo(AppointmentStatus::NO_SHOW));
        $this->assertTrue(AppointmentStatus::CONFIRMED->canTransitionTo(AppointmentStatus::CANCELLED));
        $this->assertFalse(AppointmentStatus::CONFIRMED->canTransitionTo(AppointmentStatus::COMPLETED));

        $this->assertTrue(AppointmentStatus::IN_PROGRESS->canTransitionTo(AppointmentStatus::COMPLETED));
        $this->assertFalse(AppointmentStatus::IN_PROGRESS->canTransitionTo(AppointmentStatus::CANCELLED));
        $this->assertFalse(AppointmentStatus::IN_PROGRESS->canTransitionTo(AppointmentStatus::NO_SHOW));

        $this->assertFalse(AppointmentStatus::COMPLETED->canTransitionTo(AppointmentStatus::PENDING));
        $this->assertFalse(AppointmentStatus::CANCELLED->canTransitionTo(AppointmentStatus::PENDING));
        $this->assertFalse(AppointmentStatus::NO_SHOW->canTransitionTo(AppointmentStatus::PENDING));
    }

    public function test_terminal_statuses_match_latest_rule(): void
    {
        $this->assertFalse(AppointmentStatus::PENDING->isTerminal());
        $this->assertFalse(AppointmentStatus::CONFIRMED->isTerminal());
        $this->assertFalse(AppointmentStatus::IN_PROGRESS->isTerminal());

        $this->assertTrue(AppointmentStatus::COMPLETED->isTerminal());
        $this->assertTrue(AppointmentStatus::CANCELLED->isTerminal());
        $this->assertTrue(AppointmentStatus::NO_SHOW->isTerminal());
    }

    public function test_label_and_color_helpers_for_known_and_unknown_values(): void
    {
        $this->assertSame('Confirmed', AppointmentStatus::labelFor(AppointmentStatus::CONFIRMED));
        $this->assertSame('No Show', AppointmentStatus::labelFor('no_show'));
        $this->assertSame('Unknown Status', AppointmentStatus::labelFor('unknown_status'));

        $this->assertSame('info', AppointmentStatus::colorFor(AppointmentStatus::CONFIRMED));
        $this->assertSame('slate', AppointmentStatus::colorFor('no_show'));
        $this->assertSame('gray', AppointmentStatus::colorFor('unknown_status'));
    }
}
