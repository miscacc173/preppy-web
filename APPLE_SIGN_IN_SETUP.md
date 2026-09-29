# Sign in with Apple setup

This project supports **Sign in with Apple** separately from Apple In-App Purchase.

## Supported authentication paths

- Web/browser: `GET /auth/apple` → Apple → `POST /auth/apple/callback` → `/auth/apple/complete`.
- Native/mobile API: `POST /api/v1/auth/apple` with an Apple `identity_token`; an optional nonce can also be supplied and will be verified.

Both paths use the same Preppy identity rules as Google/email login: existing Apple identity first, email fallback second, disabled-account protection, recovery of legacy soft-deleted users without duplicate creation, one active login session, and first-time/incomplete student onboarding before plan activation.

## Apple Developer configuration

Create/configure Sign in with Apple in the Apple Developer account:

1. Enable **Sign in with Apple** for the iOS App ID / Bundle ID.
2. Create a **Services ID** for browser Sign in with Apple.
3. Configure the production website domain and return URL, for example `https://your-domain.com/auth/apple/callback`.
4. Create a Sign in with Apple key (`.p8`) and retain its Key ID.
5. Record the Apple Team ID.

Apple requires an HTTPS registered return URL for real web authentication; localhost is only a placeholder in `.env.example` and must be replaced for Apple Developer configuration.

## Environment values

```env
APPLE_SIGN_IN_ENABLED=true
APPLE_SIGN_IN_CLIENT_ID=com.preppy.preppy.web
APPLE_SIGN_IN_NATIVE_CLIENT_ID=com.preppy.preppy
APPLE_SIGN_IN_TEAM_ID=YOUR_TEAM_ID
APPLE_SIGN_IN_KEY_ID=YOUR_KEY_ID
APPLE_SIGN_IN_PRIVATE_KEY_PATH=/secure/path/AuthKey_XXXXXXXXXX.p8
APPLE_SIGN_IN_REDIRECT_URI=https://your-domain.com/auth/apple/callback
```

`APPLE_SIGN_IN_CLIENT_ID` is the web Services ID. `APPLE_SIGN_IN_NATIVE_CLIENT_ID` is the iOS app Bundle ID and defaults to `APPLE_IAP_BUNDLE_ID` if omitted.

`APPLE_SIGN_IN_PRIVATE_KEY` may be used instead of a key file path. Escaped newlines (`\\n`) are supported.

After changing environment values, clear cached configuration:

```bash
php artisan config:clear
```

## First-time student behavior

A new Apple student is created only when Apple supplies an email address. Apple may provide a private relay address when the user selects **Hide My Email**; Preppy stores that address and links future sign-ins by Apple's stable user identifier.

The first-time/incomplete student flow is:

`Sign in with Apple → Basic Information → Learning Path Information → Plan Activation`

Returning users with complete academic setup go to their normal role-appropriate destination.

## Native/mobile response

`POST /api/v1/auth/apple` returns the normal Preppy API token plus:

```json
{
  "onboarding_required": true
}
```

The mobile app should route `onboarding_required=true` through Basic Information and Learning Path before plan activation, matching Google/guest onboarding behavior.
