# Apple IAP changes (v2.4)

- Added `apple_iap_products` mapping table.
- Added immutable-per-transaction `apple_iap_transactions` ledger with unique Apple transaction IDs.
- Added App Store Server API client using ES256 App Store Connect authentication.
- Added authenticated product-list and transaction-verification endpoints.
- Added App Store Server Notifications V2 callback and renewal/revocation/expiration reconciliation.
- Added store-managed plan synchronization helper without modifying existing checkout/provider controllers.
- Existing CheckoutController, PaymentService and PaymentGatewayManager are unchanged.
