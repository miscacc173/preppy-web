# Preppy Seed Data

Run:

```bash
php artisan migrate:fresh --seed
```

Default password for seeded password-login accounts:

```text
Password@123
```

## Seeded user types

- Platform administrator — `admin@preppy.test`
- Platform staff — `staff@preppy.test`
- Teacher — `teacher@preppy.test`
- Tutor — `tutor@preppy.test`
- Institute administrator — `institute.admin@preppy.test`
- Institute staff — `institute.staff@preppy.test`
- Independent platform student — `student.platform@preppy.test`
- Institute students — `student.institute@preppy.test`, `student.institute2@preppy.test`
- Upgrade scenario student — `student.upgrade@preppy.test`
- Downgrade scenario student — `student.downgrade@preppy.test`
- Renewal scenario student — `student.renewal@preppy.test`
- Existing Google-linked student — `google.student@preppy.test`
- Existing Apple-linked student — `apple.student@preppy.test`
- Incomplete/new Google onboarding student — `google.new@preppy.test`
- Incomplete/new Apple onboarding student — `apple.new@preppy.test`
- Parent users — `parent@preppy.test`, `parent2@preppy.test`
- Disabled account scenario — `disabled@preppy.test`

The two `*.new@preppy.test` social accounts are intentionally left academically incomplete so the first-time social onboarding path can be tested.

## Billing interval examples

Seeded plans include Monthly, Quarterly, and Yearly billing intervals. Quarterly plans advance subscription periods by three months.

## Seeded functional data

The full seed chain includes roles/permissions, courses, boards, subjects, topics, institutes, institute memberships, parent links, invitations, course-first plans, institute pricing, subscriptions, invoices, payments, upgrades, scheduled downgrades, renewal examples, commissions, discounts, institute promos, referral codes/rewards, Apple IAP products, notification preferences, and audit/demo records.
