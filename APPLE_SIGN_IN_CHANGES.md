# Preppy v2.4.3 - Sign in with Apple

- Added browser Sign in with Apple with Apple OIDC authorization, signed client secret, authorization-code exchange, Apple JWKS identity-token verification, encrypted expiring state, and nonce verification.
- Added native/mobile `POST /api/v1/auth/apple` identity-token authentication with optional nonce validation.
- Added Apple button to login/signup UI and Apple completion route.
- Added shared social identity resolver used by both Google and Apple so account matching, disabled-user handling, legacy soft-delete recovery, student profile creation, and onboarding decisions remain consistent.
- New/incomplete Apple students route through Basic Information → Learning Path Information → Plan Activation.
- Returning Apple users go to their normal role-based destination.
- Apple private relay email is supported; future logins match primarily on Apple's stable user identifier.
- Sign in with Apple remains separate from Apple In-App Purchase configuration and transaction processing.
- No database migration required.
