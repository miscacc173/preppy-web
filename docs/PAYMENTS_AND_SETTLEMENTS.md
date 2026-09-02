# Payments, invoices and institute settlements

## Development-only payment bypass

For local development, set:

```dotenv
APP_ENV=local
PAYMENT_DEV_BYPASS=true
```

Checkout still creates the subscription, entitlements, items, and invoice, but activates the subscription immediately and marks the invoice `waived` with an audit marker. It does not create a JazzCash or Google Pay payment record. The pricing UI identifies development activation and hides provider choices.

The bypass is honored only when `APP_ENV` is `local`, `development`, or `testing`. It is ignored automatically in staging and production even if `PAYMENT_DEV_BYPASS=true`. With the flag disabled, paid plans retain the normal `incomplete → verified provider payment → active` lifecycle.

## Development-only email-verification bypass

For local development, direct student-signup and guest-diagnostic email verification can be bypassed with:

```dotenv
APP_ENV=local
EMAIL_VERIFICATION_DEV_BYPASS=true
```

The server issues the normal short-lived, single-use verification grant without sending an email or displaying the OTP step. Direct signup still requires and consumes that server-issued grant, and created accounts are marked email-verified. This setting is honored only in `local`, `development`, and `testing`; staging and production always require the six-digit email code. The lifetime limit of one guest diagnostic session per normalized email remains enforced.

The platform is the merchant of record for subscription checkout. Students pay
the platform, the invoice is marked paid only after a verified gateway result,
and the corresponding institute settlement entry is accrued from that payment.

## Checkout providers

### JazzCash

JazzCash uses its hosted merchant form. The backend creates the signed form,
the browser posts it to JazzCash, and JazzCash returns to the public callback at
`POST /api/v1/payments/callbacks/jazzcash`. The callback signature and response
code are verified before any invoice, subscription or settlement state changes.

Configure these values in `.env`:

```dotenv
PAYMENT_PROVIDER=jazzcash
JAZZCASH_ENDPOINT=https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/
JAZZCASH_MERCHANT_ID=
JAZZCASH_PASSWORD=
JAZZCASH_INTEGRITY_SALT=
JAZZCASH_RETURN_URL=https://your-domain.example/api/v1/payments/callbacks/jazzcash
JAZZCASH_REFUND_ENDPOINT=
```

JazzCash checkout requires a PKR-priced plan. Replace the sandbox endpoint with
the endpoint assigned in the production merchant package.

### Google Pay

Google Pay supplies a payment token; it does not itself act as the card
acquirer. The Vue checkout obtains the token using Google Pay JS, then posts it
to Laravel. Laravel sends that token to the configured approved payment service
provider or acquirer and trusts only its server-side capture result.

```dotenv
GOOGLE_PAY_ENVIRONMENT=TEST
GOOGLE_PAY_MERCHANT_ID=
GOOGLE_PAY_MERCHANT_NAME="AiStar Prep"
GOOGLE_PAY_GATEWAY=
GOOGLE_PAY_GATEWAY_MERCHANT_ID=
GOOGLE_PAY_PROCESSOR_URL=
GOOGLE_PAY_PROCESSOR_SECRET=
GOOGLE_PAY_REFUND_URL=
```

`GOOGLE_PAY_PROCESSOR_URL` must accept the token and payment context and return
JSON with `success`, `transaction_id`, and optional `code` and `message` fields.
Use `PRODUCTION` only after the Google Pay merchant and gateway configuration
has been approved. Merchant and processor secrets remain server-side.

## Invoice and subscription lifecycle

- Every checkout requires an `Idempotency-Key` header. Repeating the same key
  returns the original payment attempt instead of charging again.
- Every new paid subscription starts as `incomplete` and receives an invoice.
- A successful verified payment marks the payment and invoice paid, then
  activates the subscription.
- A failed attempt keeps the invoice outstanding, records its failure code and
  enters the configured retry/dunning schedule.
- Plan trial configuration is retained for possible future system use, but it
  is hidden from the UI and ignored during subscription creation. Current
  subscription flows never enter `trialing`.
- Invoice creation and renewal are idempotent for a subscription and billing
  period. Stale attempts expire, overdue subscriptions become `past_due`, and
  access is canceled when the collection window is exhausted.
- Learn and Exam writes enforce subscription status, period, grace window and
  subject/course entitlements on the server. There is no trial exception to
  verified payment activation.

Run Laravel's scheduler in production:

```bash
php artisan schedule:work
```

The scheduled `billing:renew-subscriptions` command runs hourly, closes
subscriptions configured to cancel at period end, and creates renewal invoices
for due active subscriptions (plus legacy trialing records). `billing:process-dunning` also runs
hourly to expire attempts, advance retries, apply grace periods and cancel
uncollectible subscriptions. JazzCash and Google Pay are
customer-present payment methods, so the student completes each outstanding
renewal invoice from the Billing screen; no reusable payment token is stored.

The defaults are configurable with `BILLING_PAYMENT_ATTEMPT_EXPIRES_MINUTES`,
`BILLING_RETRY_INTERVALS_HOURS`, `BILLING_GRACE_DAYS`,
`BILLING_COLLECTION_DAYS` and `BILLING_ALLOW_PAST_DUE_DURING_GRACE`.

## Billing records, documents and refunds

- Students, institute admins and system admins receive role-scoped invoice and
  payment history. Invoice PDFs and payment receipts are generated by Laravel.
- Only a system admin may initiate a JazzCash or Google Pay provider refund.
  Refund calls are idempotent, recorded in `payment_refunds`, and have a
  downloadable refund receipt.
- Invoice gross payment is retained for audit, while `refunded_minor` records
  refunds separately. This prevents a refund from making an invoice payable a
  second time.
- Refunds reverse the associated institute commission proportionally. The
  reversal is immutable in `institute_commission_adjustments`; pending payout
  totals are recalculated. An administrator may explicitly revoke or downgrade
  the subscription entitlement while issuing the refund.

## Two-way institute settlement rules

Each active commission rule has one direction:

| Direction | Meaning after platform collection | Ledger state |
|---|---|---|
| `platform_pays_institute` | The configured amount is owed to the institute | Pending, then included in an admin payout |
| `platform_collects_from_institute` | The configured amount is retained by the platform from collected funds | Collected immediately |

Rules may be fixed or percentage-based and may apply to a specific plan or all
plans for an institute. Institute admins can view their statement but cannot
change the rules or mark payouts paid. System admins create rules and settle
pending platform-to-institute entries.

The UI and API keep these as two separately named ledgers:

- **Platform fees**: `GET /api/v1/institutes/{institute}/platform-fees`
- **Institute earnings**: `GET /api/v1/institutes/{institute}/earnings`

Gross amount, reversals and net amount are shown independently. Only institute
earnings can be included in a payout.

## Verification

```bash
php artisan migrate
php artisan test
yarn typecheck
yarn check:import-case
yarn check:data-boundary
yarn build
```

Frontend billing behavior is also covered by `yarn test:frontend`. Backend
feature coverage includes checkout/callback idempotency, renewal, dunning,
entitlement denial, documents, refunds and commission reversals.

Before production launch, perform both successful and failed sandbox payments,
verify callback reachability over HTTPS, confirm processor response mapping,
and reconcile the payment, invoice and institute settlement records.
