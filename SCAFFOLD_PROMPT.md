Build a Laravel 13 + Filament 5 app named **Appointment Scheduling System** (MySQL, PHP 8.3+, PHPUnit).

I want these modules:
- Branches
- Services
- Users (admin/staff)
- Customers
- Appointments
- Public booking form

Use clean architecture with domain actions/services. Keep controllers/resources thin.

Data model:
- `branches`: name, timezone(IANA), opening/closing time, address, phone
- `services`: name, duration_minutes, price, image nullable, description
- `users`: branch_id nullable, role enum(`admin`,`staff`)
- `customers`: name, email nullable, phone_number nullable
- `appointments`: branch_id, staff_id nullable, customer_id, service_id, start_at UTC, end_at UTC, status enum(`pending`,`confirmed`,`in_progress`,`completed`,`cancelled`,`no_show`), cancellation_reason nullable
- Add indexes for fast overlap checks.

Business rules (must enforce server-side):
- `end_at` = `start_at + service duration`
- Input datetime is branch-local, store UTC
- Must be inside branch operating hours
- Staff must belong to branch
- Overlap blocking statuses only: `pending`, `confirmed`, `completed`
- `cancelled` and `no_show` do not block
- Status flow only:
  - pending -> confirmed -> in_progress -> completed
  - pending -> confirmed -> no_show
  - pending -> confirmed -> cancelled
  - pending -> cancelled
- Cancel requires reason
- Completed is terminal (no edit/update/reassign)
- Public booking creates `pending` (no auto-assign staff)
- Confirm requires assigned staff and re-check free slot
- Add race protection to prevent double-booking under concurrent requests (per-staff lock around overlap check + write)

Filament:
- Resources for all modules above
- Appointments list with status badges, status tabs + counts, branch-local date filter
- Icon row actions: view/edit/update status/reassign
- Update status modal with remark, cancel remark mandatory
- Reassign modal should show booking context + choose new staff
- Disabled icons should look grey + cursor not-allowed
- Customer view should show booking history table with status tabs + summary stats
- Appointment edit page full width

Public booking UI:
- Fields: name, email, phone_number
- Country code dropdown now hardcoded to `+60`
- Date cannot be earlier than today

Performance:
- Prevent N+1 issue
- Add dashboard widgets with 30s cache 
- Keep queries scoped/index-friendly

Seed data (idempotent):
- at least 2 branches
- at least 2 admins
- at least 8 staff
- at least 40 customers
- at least 500 appointments with mixed statuses

Testing:
- feature tests for timezone conversion, operating-hours guard, staff-branch guard, overlap behavior by status, transition rules, cancel reason requirement, completed lock, staff scope, public booking flow, and concurrency behavior.

Deliver complete project artifacts:
- migrations, models, enums, actions/services, policies
- Filament resources/pages/widgets
- public booking controller/request/views
- factories/seeders
- tests
- README (setup, credentials, rules, tradeoffs)
