# Base Sync Note

This package was rebuilt from the user-supplied `preppy-plan-upgrade-downgrade-billing (1).zip` as the source of truth.

Apple IAP support was then applied additively. Existing checkout, JazzCash, Google Pay, mock-payment, promo/referral, upgrade/downgrade, invoice, and entitlement flows were preserved.

The only pre-existing Laravel correction retained is the missing `use App\\Models\\Invoice;` import required by the plan-upgrade code path.
