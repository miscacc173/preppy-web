# AI Exam Prep Platform

Complete Laravel source foundation for an AI-assisted exam preparation platform covering O Level, IGCSE, GCSE, AS Level
and A Level.

## Included domains

- Email/password identity and Google Socialite configuration
- Roles, permissions and scoped institute roles
- Guest demo sessions and conversion to registered students
- Platform-owned and institute-owned students with mutual exclusivity
- Parents linked to multiple students for read-only analytics access
- Institutes, institute administrators, staff, invitations and student attachment
- Courses, exam boards, subjects and subject-linked topics
- Idempotent topic seeding for every demo subject (`php artisan db:seed --class=TopicSeeder`)
- Per-subject, multi-subject, course, all-subject and institute plans
- Discounts, institute pricing, subscription entitlements, invoices, receipts, refunds and payment history
- Percentage or fixed two-way institute settlements, reversal ledgers and payouts
- Platform-collected JazzCash and Google Pay checkout with automatic invoices
- Notification preferences and device registrations
- Audit logging
- Laravel services, requests, controllers, routes, seeders and tests
- Vue 3 SPA in `resources/js`, served by Laravel and built with Laravel Vite
- Docker, Nginx, PHP-FPM, queue worker and PostgreSQL setup

The AI examination content, RAG pipeline, question generation, tutor review workflow and analytics calculations are
deliberately excluded for the next phase.

## Local setup

Use PHP 8.4.1 or newer with the supplied `composer.lock`. Although the root constraint permits PHP 8.3, the uploaded
lockfile currently selects Symfony 8.1 packages, which require PHP 8.4.1.

```bash
cp .env.example .env
composer install
corepack enable
yarn install
php artisan key:generate
php artisan migrate --seed
yarn dev
php artisan serve
```

The Vue application is mounted by `resources/views/app.blade.php`, and its source lives entirely under
`resources/js`. Laravel serves SPA routes through `routes/web.php`; API endpoints remain under `/api/v1`.

For a production frontend build, run:

```bash
yarn build
```

Vue data is separated from presentation components. API keys are configured in
`resources/js/data/config/apiFieldMap.ts`, Laravel-backed operations go through
`resources/js/data/repositories/platformRepository.ts`, and the remaining backend-less
records are isolated behind typed repositories and mappers. See
[`docs/DATA_INTEGRATION.md`](docs/DATA_INTEGRATION.md) for the integration map
and replacement instructions.

Validate this boundary with:

```bash
yarn typecheck
yarn check:import-case
yarn check:data-boundary
yarn test:frontend
yarn build
```

The integrated UI uses the Laravel authentication API for registration, login, logout and the current user. It also
persists browser application state per authenticated user through `/api/v1/app-state`, and submits contact/support
messages through `/api/v1/contact-messages`.

## Integrated Vue API sections

- Public academic catalog and pricing plans (`GET /api/v1/catalog`)
- Student academic setup, exam board, course, school year and subject selection
- Admin-managed subject/topic assignment consumed by student Learn setup
- Catalog availability states: active, coming soon (also accepts legacy `enabled`), and hidden/inactive
- Current-user profile and notification preferences (`/api/v1/account`)
- Student subject and subscription summaries
- Subscription activation, cancellation and resumption
- Guest diagnostic session creation and progress persistence
- One guest diagnostic session per verified, normalized email
- Guest diagnostic conversion continues directly to eligible course/subject plan activation
- Contact and support message submission
- Calendar, goals and notification feed persistence through authenticated app-state
- Tutor Learn and Exam modes with separate question/answer feedback on every question, plus the AI review queue
- Course, single-subject and multiple-subject plan targeting in the admin billing UI and API
- Backend-persisted Learn/Exam sessions and results shared across signed-in devices
- Dashboard subject cards open Learn Mode with the selected allowed subject prefilled
- One active login session per user; a successful new login signs out every previous device
- Active Learn-session progress, completed sessions, daily activity and subject readiness summaries
- Grant-scoped teacher progress visibility by class, course, student or subject
- Dedicated educator email activation; teachers and tutors have no public signup flow
- System-admin tutor management; institute admins can invite/manage teachers but cannot manage tutors
- Parent activation and multiple-child visibility with administrator-controlled linking and revocation
- Parent subject cards, readiness totals, daily activity, and session history restricted to each student's active-plan entitlements
- Delegated teacher parent management, limited to explicitly granted students
- Institute invitation activation with password setup and active-membership scoping
- Platform-collected JazzCash hosted checkout and Google Pay token capture through an approved processor
- Idempotent checkout, verified activation, automatic renewal invoices, retry/dunning and entitlement enforcement
- Trial configuration retained for future system use but hidden and disabled; current plans activate only after verified payment
- Invoice PDFs, receipts, payment/refund history and commission reversals
- Separately named platform-fee and institute-earnings ledgers
- Role-gated student, tutor, parent, institute and system-administration routes

AI question generation/marking and provider-grade analytics calculations remain frontend demonstrations.
Payment adapters, invoice state changes and settlement accrual are backend implementations; production capture still requires
valid merchant/processor credentials. Question content remains
in the isolated question-bank module; academic catalog data is never substituted with question-bank mock data.

Payment and settlement configuration is documented in
[`docs/PAYMENTS_AND_SETTLEMENTS.md`](docs/PAYMENTS_AND_SETTLEMENTS.md). Run the scheduler in production so renewal invoices are generated:

```bash
php artisan schedule:work
```

Create the first administrator after seeding using Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name' => 'System Admin',
    'email' => 'admin@example.com',
    'password' => 'ChangeMe123!',
]);
app(App\Services\RoleAssignmentService::class)->assign($user, 'system_admin');
```

## Docker setup

```bash
cp .env.example .env
# Change DB_HOST to postgres

docker compose up --build -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open `http://localhost:8000`.

## Ownership invariant

A student profile is always exactly one of:

- `platform`: `institute_id` must be null
- `institute`: `institute_id` must be present

The PostgreSQL check constraint and `StudentOwnershipService` both enforce this invariant.

## Implemented application flows

The project now includes API CRUDs for users, exam boards, courses, subjects, institutes, plans, discounts, institute
pricing, commission rules, notification preferences and devices. It also includes guest demo conversion, student
registration/login, institute invitations and membership, institute student creation/attachment/detachment, parent
links, subscription lifecycle, invoice generation, payment capture, commission accrual and payouts. All endpoints are
under `/api/v1`. See `routes/api.php`.

## Educator and parent access rules

| Role | Onboarding | Visible learning data | Managed by |
|---|---|---|---|
| Teacher | Email invitation only | Granted classes, courses, students and subjects; progress and results | System admin or the relevant institute admin |
| Tutor | Email invitation only | Learn/Exam for granted subjects, own results, inline question/answer feedback, and AI content review queue | System admin only |
| Parent | Email invitation linked to one or more students | Active linked children; performance/subscription blocks follow link permissions | System admin, relevant institute admin, or an explicitly delegated teacher |

Assigning a teacher to an institute class creates the corresponding class access grant. Revoking a grant immediately removes that data from teacher/tutor APIs. Institute admins receive HTTP 403 for tutor invitations or tutor grant changes.

Only a system admin may manage parent access for platform-owned students. A system admin or the relevant institute admin may list, create and revoke links for institute-owned students. A teacher may do so only when an administrator enables parent management on an active grant that resolves to that student. Parents, tutors and staff cannot create or revoke these links; a parent only accepts an issued invitation.
