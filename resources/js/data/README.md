# Vue data layer

This directory is the boundary between Vue presentation components and data
sources. Views render stable UI models; they do not depend on Laravel response
keys and they do not contain reusable demo datasets.

## Why this boundary exists

The API contract and the design contract change for different reasons. Laravel
may return `school_year`, while a Vue component is easier to read with
`schoolYear`. The mapper owns that translation so an API change is corrected in
one place without editing multiple screens.

```text
Laravel API / authenticated app-state
            |
            v
       repository
            |
            v
          mapper
            |
            v
       stable UI model
            |
            v
         Vue view
```

## Directory ownership

| Directory | Responsibility | May know API keys? | May contain dummy data? |
|---|---|---:|---:|
| `config/` | API field aliases and compatibility keys | Yes | No |
| `core/` | Safe field-reading and primitive conversions | Yes | No |
| `models/` | Stable TypeScript contracts consumed by Vue | No | No |
| `mappers/` | Raw API response to UI-model conversion | Yes | No |
| `repositories/` | API queries, commands and persisted app-state access | Yes | No |
| Vue views/components | Rendering and interaction state | No | No |

## Existing data domains

### Student platform

- `models/platform.ts` contains catalog, account, preference and subscription UI
  models.
- `mappers/platformMapper.ts` maps catalog/account/subscription API responses.
- `repositories/platformRepository.ts` is used by student onboarding, account,
  settings, pricing and guest-demo screens.
- Direct student signup requires a single-use verified email grant. Guest demos require a verified email and Laravel permits only one lifetime
  session per normalized email. Vue displays the backend eligibility error and
  never attempts to enforce this security rule locally.
- `config/apiFieldMap.ts` contains aliases for API keys that may vary.
- Catalog mappers normalize the legacy `enabled` status to `coming_soon`.
  Active records are selectable, coming-soon records are displayed with a
  badge but disabled, and all other states are filtered by Laravel.

### Admin and institute operations

- `models/operations.ts` contains users, students, institutes, members, catalog,
  billing, access-control, payout and audit UI models.
- `mappers/operationsMapper.ts` converts paginated Laravel responses to those
  models.
- `repositories/operationsRepository.ts` owns every Admin and Institute API
  request, including create/update/delete commands and workflow actions.

The operations repository currently covers role assignment, institute-member
invites, platform-student attachment, parent invites, institute price
overrides, subscriptions, invoice payments, commissions, payouts, audit detail
and registered devices. These are live API calls; the Admin and Institute
workspaces do not fall back to sample records.

The Admin and Institute Vue files never read Laravel keys such as
`ownership_type`, `current_period_ends_at`, `billing_email` or `actor_user_id`
directly.

### Student workspace

- `models/studentWorkspace.ts` defines calendar, goals and notification records.
- `mappers/studentWorkspaceMapper.ts` validates persisted JSON into stable UI
  models.
- `repositories/studentWorkspaceRepository.ts` stores those records per user
  through `/app-state` using versioned keys.
- Dashboard side-panel rows are projections of the session and achievement
  stores; there is no fallback dataset.

### Tutor feedback

- `models/tutorFeedback.ts` and `mappers/tutorFeedbackMapper.ts` isolate the
  feedback response contract.
- `repositories/tutorFeedbackRepository.ts` lists, upserts and deletes tutor
  feedback against a session/question key. Question and answer feedback use
  separate stable keys (`result-N:question` and `result-N:answer`).
- Tutors hydrate Learn/Exam subject choices from `/tutor/academic-access`, which
  resolves admin-granted subjects against the live catalog. No mock subjects
  are substituted when a tutor has no grant.
- The same feedback component is mounted on every active Learn and Exam
  question and on review screens. Tutors otherwise use those modes like a
  student, without requiring a student subscription.
- Laravel enforces the `tutor` role. Hiding the Vue editor from non-tutors is
  only a presentation convenience, not the authorization boundary.

## Replacing API response keys

If the API returns a different key, change only its alias or mapper.

Example: the backend changes `billing_email` to `accounts_email`.

```ts
// In the appropriate mapper:
billingEmail: text(raw, ["accounts_email", "billing_email", "billingEmail"]),
```

The first available value wins. No Vue template needs to change because it
continues to render `institute.billingEmail`.

For fields shared by the student platform, prefer updating
`config/apiFieldMap.ts`:

```ts
export const API_FIELD_MAP = {
  account: {
    name: ["full_name", "name"],
  },
};
```

## Replacing an app-state document with a dedicated API

1. Keep the existing UI model in `models/` unchanged.
2. Add the dedicated API request to the matching repository.
3. Add or update a mapper that returns the existing UI model.
4. Switch the repository method from `/app-state` to the new endpoint.
5. Run the boundary, import-case, type and production-build checks.

Example:

```ts
// Before: goals are stored as a versioned app-state document.
async recommendations() {
  return this.state("aistar.recommendations.v1");
}

// After: the endpoint is available.
async recommendations() {
  const response = await api<{ data: unknown[] }>("/recommendations");
  return response.data.map(mapRecommendation);
}
```

The Vue component still calls `repository.recommendations()` and does not need
to know which data source is active.

## Adding a new API-backed screen

1. Define the screen contract in `models/<domain>.ts`.
2. Map the raw response in `mappers/<domain>Mapper.ts`.
3. Add query and command methods in `repositories/<domain>Repository.ts`.
4. Call only repository methods from the Vue view.
5. Keep form payload construction in the repository when it translates UI keys
   to API keys.
6. Add aliases for response-key differences instead of branching in templates.

## Pagination

Laravel resource listings return pagination metadata and a `data` array.
`mapPage()` normalizes that to:

```ts
interface UiPage<T> {
  items: T[];
  page: number;
  lastPage: number;
  total: number;
}
```

Views therefore use `page.items` and never depend on Laravel's
`current_page`/`last_page` naming.

## Authentication and role context

The shared API client in `lib/api.ts` attaches the Sanctum bearer token. Router
guards use mapped user roles to select the appropriate workspace:

- `system_admin`, `system_staff` -> `/admin`
- `institute_admin`, `institute_staff` -> `/institute/{context_id}`
- `teacher` -> `/teacher/workspace`
- `tutor` -> `/learn`
- `parent` -> `/parent`
- `student` -> `/dashboard`

Frontend guards improve navigation but do not replace Laravel authorization.
Every privileged backend endpoint must still enforce permissions server-side.
Institute-scoped APIs additionally check the authenticated user's role context
and never trust an `institute_id` supplied by the browser as authorization.
Inactive institute memberships are removed from the authenticated role payload,
so their routes and content disappear even if an old scoped role row remains.

Parent portal data is owned by `parentRepository.ts` and normalized by
`parentMapper.ts`. Academic, performance and subscription visibility is decided
by the backend link status and permission flags rather than by Vue templates.
One parent account may hold multiple independent student links; the endpoint
returns every link and the portal renders a separate card for each student.
The student name appears once in that card's header, while all progress,
subjects, sessions and billing data remain contained below it.
For each assigned subject the progress payload includes readiness, aggregate
marks, average/best/latest score, study time, last activity, and up to twelve
recent active or completed sessions. Session rows include Learn/Exam mode,
topic, start/completion date, score, grade, marks, question counts and duration.
The portal is read-only: parent-link listing, invitation and revocation are
admin operations exposed by `operationsRepository.ts`. Platform links require
a system admin; institute links allow the relevant institute admin as well.

Learn/Exam results are owned by `learningSessionRepository.ts` and normalized
by `learningSessionMapper.ts`. Student and tutor completion and active progress
are persisted through the session endpoints. Teachers can list only
grant-resolved students and load their progress/results. Tutors can list their
own sessions, while the separate review queue exposes only AI content from
granted subjects and does not expose general student identity.

## Operations endpoint map

| UI action | Repository method | API endpoint |
|---|---|---|
| Create/update targeted plan | `create("plans", ...)`, `update("plans", ...)` | `POST /plans`, `PUT /plans/{plan}` |
| Resolve tutor subjects | `tutorAcademicAccess()` | `GET /tutor/academic-access` |
| Save question/answer feedback | `tutorFeedbackRepository.save()` | `POST /tutor-question-feedback` |
| Assign user role | `assignRoles()` | `PUT /users/{user}/roles` |
| Attach existing student | `findAvailableStudent()`, `attachStudent()` | `GET /institutes/{institute}/available-student`, `POST /institutes/{institute}/students/{student}/attach` |
| List parent access | `parentLinks()` | `GET /students/{user}/parents` |
| Invite/link parent | `inviteParent()` | `POST /students/{user}/parents` |
| Revoke parent access | `revokeParentLink()` | `DELETE /parent-student-links/{link}` |
| Set institute price | `create("institute-plan-prices", ...)` | `POST /institute-plan-prices` |
| Record invoice payment | `recordPayment()` | `POST /invoices/{invoice}/payments` |
| Start subscription checkout | `checkout()` | `POST /checkout/subscriptions` |
| Pay existing invoice | `checkoutInvoice()` | `POST /invoices/{invoice}/checkout` |
| Capture Google Pay token | `captureGooglePay()` | `POST /payments/{payment}/capture/google-pay` |
| Check current entitlement | `entitlements()` | `GET /billing/entitlements` |
| View payment/refund history | `payments()`, `refunds()` | `GET /payments`, `GET /refunds` |
| Download invoice/receipts | `downloadInvoice()`, `downloadReceipt()`, `downloadRefundReceipt()` | `/invoices/{invoice}/pdf`, `/payments/{payment}/receipt`, `/refunds/{refund}/receipt` |
| Issue provider refund | `refundPayment()` | `POST /payments/{payment}/refunds` |
| View platform fees | `institutePlatformFees()` | `GET /institutes/{institute}/platform-fees` |
| View institute earnings | `instituteEarnings()` | `GET /institutes/{institute}/earnings` |
| Build/settle payout | `createPayout()`, `markPayoutPaid()` | `POST /commission-payouts`, `POST /commission-payouts/{payout}/mark-paid` |
| Inspect audit event | `audit()` | `GET /audit-logs/{auditLog}` |
| Register/remove device | `create("devices", ...)`, `remove()` | `POST /devices`, `DELETE /devices/{device}` |
| Update institute member | `updateMember()` | `PUT /institutes/{institute}/members/{user}` |
| Invite teacher/tutor | `inviteEducator()` | `POST /educator-invites` |
| Grant/revoke educator scope | `grantEducatorAccess()`, `revokeEducatorAccess()` | `/educators/{user}/access-grants` |
| Manage institute classes | `createTeachingClass()`, `updateTeachingClass()`, `removeTeachingClass()` | `/institutes/{institute}/teaching-classes` |

## CRUD interaction standard

Operations screens use the same interaction contract:

- Create and edit forms share one mapped UI model and one repository boundary.
- Edit buttons populate the form from stable camelCase UI fields; templates do
  not read Laravel response keys.
- Destructive actions use the animated in-application confirmation modal. No
  browser `alert()` or `confirm()` calls are permitted in operations views.
- List loading uses shimmer skeletons, while submit and workflow actions show
  an inline spinner and remain disabled until the API request completes.
- Modal open/close transitions respect the operating system's reduced-motion
  preference.

## Required checks

Run these before delivery:

```bash
yarn check:import-case
yarn check:data-boundary
yarn typecheck
yarn build
```

Or run all frontend checks together:

```bash
yarn verify:frontend
```

`check:data-boundary` prevents reusable dummy datasets from moving back into
Vue views. `check:import-case` catches paths that work on case-insensitive
machines but fail on Linux.
