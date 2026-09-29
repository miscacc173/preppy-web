# Preppy notification policy

Preppy uses one notification delivery path for web, iOS and Android FCM devices registered to a user.

## Policy categories

- `study` — preference controlled (for example `study_reminder`, `retry_reminder`).
- `exam` — preference controlled (`exam_countdown`).
- `performance` — preference controlled (`performance_summary`).
- `promotion` — explicit marketing opt-in through `marketing_promotions`; defaults to off.
- `billing`, `subscription`, `account`, `security`, `institute`, `announcement`, `system` — operational/transactional and mandatory; study or marketing toggles do not suppress them.

The policy is enforced in `NotificationPolicy`/`PushNotificationService`, so callers do not need to decide manually whether a preference applies.

## Admin notifications

Admin -> System data includes Send notification. Admins may target all active users, a role, or one user.

- Promotion messages honor the user's Offers & promotions opt-in.
- Announcement and System messages are operational messages and do not depend on study/marketing preferences.

Every eligible user's notification fans out to all of that user's active web, iOS and Android device registrations.

## Mobile

iOS and Android apps register their FCM token through the same authenticated `/api/v1/push/devices` endpoint using platform `ios` or `android`. No separate notification policy is needed for mobile.
