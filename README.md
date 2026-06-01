# Appointment Scheduling System

Backend-first Laravel assessment project for managing branches, services, staff, customers, and appointments with strict server-side appointment rules.

## Stack
- Laravel 13
- Filament 5 (admin/staff panel)
- MySQL (native local setup)
- PHPUnit 12 (unit + feature)

## Quick Setup
1. Install dependencies:
```bash
composer install
npm install
```
2. Configure environment (already prepared in `.env`):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=root
DB_PASSWORD=
```
3. Create database (if needed), then migrate and seed:
```bash
php artisan migrate:fresh --seed
```
4. Run app:
```bash
php artisan serve
npm run dev
```

## URLs
- Filament panel: `http://127.0.0.1:8000/admin`
- Public booking form: `http://127.0.0.1:8000/book`

## Seeded Credentials
- Admin: `admin@example.com` / `password`
- Staff: `staff01@example.com` / `password`

## Architecture Overview
- **Thin entrypoints**:
  - Public booking in `BookingController`
  - Filament pages/resources for admin/staff workflows
- **Domain logic in Actions/Services**:
  - `CreateAppointmentAction`
  - `UpdateAppointmentAction`
  - `TransitionAppointmentStatusAction`
  - `CancelAppointmentAction`
  - `BranchOperatingHoursService`
  - `AppointmentOverlapService`
- **Authorization**:
  - `AppointmentPolicy` + Filament resource scoping
  - Staff can only view/update their own appointments
  - Admin-only access for branch/service/staff/customer management
- **Enums**:
  - `UserRole`: `admin`, `staff`
  - `AppointmentStatus`: `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`, `no_show`

## Appointment Rules Implemented
- `end_at` is derived from `start_at + service.duration_minutes` (never manually entered)
- Appointment must fully fit branch operating hours (in branch timezone)
- Staff must belong to appointment branch
- Overlap is blocked for statuses: `pending`, `confirmed`, `completed`
- `cancelled` and `no_show` do not block availability
- `cancelled` requires `cancellation_reason`
- Status transitions are guarded (no backwards/nonsense transitions)
- Public booking creates `pending` appointments without auto staff assignment
- Confirmation (`pending -> confirmed`) requires assigned staff and passes free-slot overlap validation

## Admin UI Notes
- Resource list pages have a column manager enabled (toggleable columns).
- Column visibility preferences persist in session per user/page.

## Timezone Assumptions
- App timezone baseline is UTC.
- Branch timezone is required (IANA timezone string, e.g. `Asia/Kuala_Lumpur`).
- Appointment datetimes are stored in UTC (`start_at`, `end_at`).
- Incoming appointment input is interpreted as **branch local time**, then converted to UTC.
- Appointment date filtering in Filament supports branch-local day filtering via conversion to UTC window.

## Known Limitations / Tradeoffs
- `no_show` is **manual-only** in this implementation (assessment brief requested automatic +15min update).
- No holiday calendars, breaks, staff roster, recurring schedules, or slot generation.
- No payment, notifications, customer self-service portal, queue processing, or audit log.

## Tests
Run:
```bash
php artisan test
```

Run unit tests only:
```bash
php artisan test --testsuite=Unit
```

Coverage currently includes:
- Unit tests:
  - `AppointmentStatus` transition/blocking/helper behavior
  - `CustomerPhoneNumberFormState` compose/split/validation behavior (shared by Customer + Branch flows)
- End-time derivation
- Operating-hours validation
- Staff-branch validation
- Overlap behavior by status
- Status transition rules + cancellation reason requirement
- Staff authorization isolation
- Public booking/customer reuse scenarios

## AI Tool Usage
AI-assisted development was used for scaffolding and implementation acceleration.  
All generated code was reviewed and validated through:
- migration/seed verification
- manual domain rule inspection
- passing automated feature tests
