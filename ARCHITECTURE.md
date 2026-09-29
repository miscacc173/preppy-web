# Domain map

## Identity and access

`users`, `social_accounts`, `roles`, `permissions`, `role_permissions`, `user_roles`

Roles can be global or scoped to an institute. A tutor is a platform role for the later content-review domain.

## Academic catalogue

`exam_boards`, `courses`, `subjects`, `course_subjects`

The Python application may upsert subjects using `code + syllabus_year_start + syllabus_year_end`. Its existing topics,
subtopics and learning objectives should reference `subjects.id` or maintain an external mapping table.

## Student ownership

`student_profiles.ownership_type` controls the commercial relationship. Institute students require an institute.
Platform students cannot carry an institute ID.

## Commercial model

A plan can cover one subject, several subjects, one course, all subjects, or be institute-specific. Institute prices
override standard prices. Discounts are separate from institute negotiated prices.

## Subscription model

Students and institutes are supported as subscribers. `subscription_items` snapshot the effective price and entitlements
so future plan edits do not rewrite history.

## Commission model

A plan-specific institute commission rule overrides a general institute rule. Commission is accrued only after a
successful payment and becomes available after a configurable holding period.
