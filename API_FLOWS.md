# API Flows

All JSON endpoints use the `/api/v1` prefix. Protected endpoints accept a Sanctum bearer token.

## Authentication and onboarding

- `POST /auth/register`: create a platform student and profile.
- `POST /auth/login`, `GET /auth/me`, `POST /auth/logout`.
- `GET /auth/google` and callback are available on web routes.
- `POST /guest-demos`, update progress, and convert a guest into a student.

## Administration CRUD

Full index/store/show/update/delete endpoints exist for users, roles, permissions, exam boards, courses, subjects,
institutes, students, plans, discounts, institute plan prices, commission rules, notification preferences, and devices.

## Institute workflows

Create an institute, invite admins/staff, accept invitations, remove members, create institute students, attach an
existing platform student, or detach an institute student back to platform ownership.

## Parent workflow

Invite/link a parent to a student, accept the link, list linked students, and revoke access. Parent relationships remain
view-only through their assigned permissions. `GET /parent-students` returns a
per-subject progress breakdown with session dates, mode, topic, marks, accuracy,
question totals, duration, recent daily activity, and current subscription data
when the corresponding link permissions are enabled. Subject names and every
progress aggregate are restricted to the student's active subscription-item
entitlements: single/multiple-subject plans use their captured subject IDs,
course plans use that course's subjects, and broad plans use the student's
selected profile subjects. Historical activity outside the active plan is not
returned to the parent portal.

## Subscription and billing

Create a student subscription, cancel at period end, resume, pause or activate it. Generate invoices from subscription
items and record payments. Paid institute-linked student subscriptions automatically accrue commissions using the most
specific active rule. The student dashboard has its own active-plan entitlement:
only a subscription in `active` status with an unexpired billing period and at
least one entitlement item can open or display Dashboard navigation. Students
without that state are sent to plan selection/activation. Learn/Exam grace-period
rules remain separate from the stricter dashboard gate.

## Commission payout

Create a payout from pending commission IDs and mark the payout paid. The operation updates all included commission
records transactionally.

## Notes

The exam/RAG/question-content domain remains intentionally excluded. API authorization middleware is ready; apply
granular `permission:*` middleware to routes according to deployment policy.
