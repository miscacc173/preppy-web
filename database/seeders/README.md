# Preppy Seed Data

Run all seed data after migrations:

```bash
php artisan migrate:fresh --seed
```

Or on an existing development database:

```bash
php artisan db:seed
```

## Test logins

All seeded users use password: `Password@123`

| Role | Email |
|---|---|
| System Admin | admin@preppy.test |
| System Staff | staff@preppy.test |
| Institute Admin | institute.admin@preppy.test |
| Institute Staff | institute.staff@preppy.test |
| Teacher | teacher@preppy.test |
| Tutor | tutor@preppy.test |
| Platform Student | student.platform@preppy.test |
| Institute Student | student.institute@preppy.test |
| Second Institute Student | student.institute2@preppy.test |
| Parent | parent@preppy.test |
| Second Parent | parent2@preppy.test |

## Seeded functional areas

- Roles, permissions and role assignments
- Exam boards, courses, course/board mappings, subjects and topics
- Institute, institute memberships and institute-scoped roles
- Platform and institute-owned student profiles and subject assignments
- Parent/student active and pending invitation relationships
- Teacher/tutor invitations, teaching class, class students and access grants
- Tutor question feedback
- Plans, plan subjects, discounts and institute-specific pricing
- Active subscriptions, subscription items, invoices, invoice items and payments
- Institute commission rules, commissions, payout, partial refund and commission adjustment
- Institute promo code (`SCHOOL20`) and eligible plan mapping
- Referral code/referral and promotion redemption examples
- Notification preferences and devices for every seeded user
- User application state, social login sample, contact/support message
- Learn/exam learning session history
- Guest demo session, one-email claim and email verification challenge
- Admin-manageable institute/educator/parent invitation tokens
- Apple IAP product mapping and a synthetic Sandbox transaction
- Audit log examples

The Apple IAP transaction is synthetic seed data and is not a real App Store receipt.
