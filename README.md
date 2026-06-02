# Appointment Scheduling System

Project for managing branches, services, staff, customers, and appointments with strict server-side appointment rules.

## Stack
- PHP 8.3
- Laravel 13
- Filament 5 (admin/staff panel)
- MySQL (native local setup)
- Vite / Node.js
- PHPUnit 12 (unit + feature)

## Local Setup
1. Install dependencies:
```bash
composer install
npm install
```
2. Create a local app environment file (or use your shell's equivalent copy command):
```bash
cp .env.example .env
```
3. Update `.env` with your own local MySQL settings. Do not commit credentials. Example shape only:
```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```
4. Create the local application database, then bootstrap the app:
```bash
php artisan key:generate
php artisan migrate:fresh --seed
```
5. Build frontend assets:
```bash
npm run build
```
6. Run the app:
```bash
php artisan serve
npm run dev
```

## Test Setup
Feature tests use the normal Laravel testing environment. `phpunit.xml` only forces `APP_ENV=testing`; it does not store local database credentials.

1. Create a dedicated local test env file:
```bash
cp .env .env.testing
```
2. Change `.env.testing` to use an isolated database:
```env
APP_ENV=testing
DB_DATABASE=booking_system_test
```
3. Keep the rest of the MySQL connection values aligned with your local machine.
4. Create the test database before running feature tests.
5. Run tests:
```bash
php artisan test
```

Important:
- `.env.testing` is gitignored and should stay local.
- Do not point `.env.testing` at your development database.
- Feature tests use `RefreshDatabase`, so the test database schema will be rebuilt.

## URLs
- Filament panel: `http://127.0.0.1:8000/admin`
- Public booking form: `http://127.0.0.1:8000/book`

## Seeded Credentials
- Admin: `admin@example.com` / `password`
- Staff: `staff01@example.com` / `password`

## Architecture Overview
- **Thin entry points**
  - Public booking is handled by `BookingController`.
  - Filament resources/pages handle admin and staff UI orchestration.
- **Business rules live outside Filament**
  - Appointment creation: `CreateAppointmentAction`
  - Appointment update / reschedule: `UpdateAppointmentAction`
  - Status transitions: `TransitionAppointmentStatusAction`
  - Scheduling validation: `AppointmentSchedulingResolver`
  - Overlap detection: `AppointmentOverlapService`
  - Reassignment availability: `AppointmentReassignmentAvailabilityService`
  - Customer identity reuse: `FindOrCreateCustomerAction`
- **Shared normalization / support**
  - Customer form mapping: `CustomerFormDataService`
  - Shared phone form mapping: `PhoneNumberFormDataService`
  - Public submission phone normalization: `PhoneNumberSubmissionNormalizer`
  - Standardized success/error messages: `SubmissionFeedback`
- **Authorization**
  - `UserRole`: `admin`, `staff`
  - Staff are limited to their own appointment scope.
  - Admin manages branches, services, staff, and customers.

## Business Flow
### 1. Public booking flow
- Customer submits name, branch, service, datetime, and at least one contact channel (`email` or `phone_number`).
- Phone input is normalized to E.164 format. Local Malaysia-style input is accepted through the `+60` selector.
- `FindOrCreateCustomerAction` reuses an existing customer when the submitted identity maps cleanly to one record.
- If submitted email and phone each match different existing customers, the system creates a new customer record instead of merging unrelated identities.
- `CreateAppointmentAction` creates the appointment as `pending`.
- Public booking does not auto-assign or auto-confirm staff.

### 2. Scheduling and validation flow
- `start_at` is interpreted in the selected branch timezone, then stored in UTC.
- `end_at` is always derived from `start_at + service.duration_minutes`.
- Appointment start time must be in the future relative to the branch timezone.
- The booking must fully fit within branch operating hours.
- If staff is assigned, that user must:
  - have the `staff` role
  - belong to the selected branch
  - be free for the selected time range
- Staff overlap checks currently treat these statuses as blocking:
  - `pending`
  - `confirmed`
  - `completed`
- `cancelled` and `no_show` do not block availability.
- A customer may only have one ongoing booking at a time. Ongoing means:
  - `pending`
  - `confirmed`
  - `in_progress`

### 3. Appointment lifecycle flow
- Allowed transitions:
  - `pending -> confirmed`, `cancelled`
  - `confirmed -> in_progress`, `cancelled`, `no_show`
  - `in_progress -> completed`
- Terminal appointments cannot be rescheduled.
- Confirming an appointment requires an assigned staff member and a valid free slot.
- Cancelling an appointment requires a remark.
- The cancellation remark is stored in `cancellation_reason`.
- A new cancellation remark replaces the stored value; there is no append-only remark history table in this implementation.

### 4. Reassignment flow
- Staff reassignment is allowed only while the appointment is `confirmed`.
- Reassignment options are limited to available staff in the same branch and time window.
- If no valid alternative staff exists, the reassignment action is disabled in the admin UI.
- Reassignment is enforced in domain logic, not only in Filament.

### 5. Deletion and history flow
- `Branch`, `Service`, and `User` cannot be deleted while they have active appointments.
- For delete protection, active means:
  - `confirmed`
  - `in_progress`
- These entities use soft deletes.
- Historical appointments still resolve soft-deleted branch, service, and staff relations.

### 6. Submission feedback flow
- Public booking and Filament submissions use standardized success/error feedback.
- Failed submissions return visible validation messages.
- Successful booking returns a booking reference on the success screen.

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

If feature tests fail immediately with a database connection error, check `.env.testing` first.

Run unit tests only:
```bash
php artisan test --testsuite=Unit
```

Coverage currently includes:
- Unit tests:
  - `AppointmentStatus` transition/blocking/helper behavior
  - `CustomerPhoneNumberFormState` compose/split/validation behavior (shared by Customer + Branch flows)
  - `CustomerFormDataService`, `PhoneNumberFormDataService`, and `PhoneNumberSubmissionNormalizer`
- End-time derivation
- Operating-hours validation
- Staff-branch validation
- Overlap behavior by status
- Status transition rules + remark requirement for cancelled status
- Reassignment rules
- Soft-delete protections for branch/service/staff
- Active appointment scope
- Staff authorization isolation
- Public booking/customer reuse scenarios

## AI Tool Usage
AI-assisted development was used for scaffolding and implementation acceleration.  
All generated code was reviewed and validated through:
- migration/seed verification
- manual domain rule inspection
- passing automated feature tests
