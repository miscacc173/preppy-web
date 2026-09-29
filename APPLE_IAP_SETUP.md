# Apple In-App Purchase integration

This integration is additive. Existing JazzCash, Google Pay and mock/dev payment routes are unchanged.

## New endpoints
- `GET /api/v1/apple-iap/products` (authenticated): active App Store product-to-Preppy-plan mappings.
- `POST /api/v1/apple-iap/verify` (authenticated): verifies a StoreKit transaction against Apple's App Store Server API, then mirrors it into Preppy billing/subscription state.
- `POST /api/v1/apple-iap/notifications` (public HTTPS callback): App Store Server Notifications V2 receiver.

## Required server configuration
Set:

```
APPLE_IAP_ENABLED=true
APPLE_IAP_ENVIRONMENT=auto
APPLE_IAP_BUNDLE_ID=com.yourcompany.preppy
APPLE_IAP_ISSUER_ID=<App Store Connect issuer id>
APPLE_IAP_KEY_ID=<In-App Purchase key id>
APPLE_IAP_PRIVATE_KEY_PATH=/absolute/secure/path/AuthKey_XXXXXXXXXX.p8
```

Do not commit the `.p8` key.

Run migrations and create product mappings in `apple_iap_products`. Product IDs must exactly match App Store Connect auto-renewable subscription products.

Configure the production/sandbox App Store Server Notifications URL in App Store Connect as:

`https://YOUR_API_HOST/api/v1/apple-iap/notifications`

Use Notifications V2.

## Existing-flow isolation
Apple IAP does not alter `CheckoutController`, `PaymentGatewayManager`, JazzCash, Google Pay, mock capture, promotion validation, or existing subscription-change routes. StoreKit purchases enter only through the new Apple routes/service.
