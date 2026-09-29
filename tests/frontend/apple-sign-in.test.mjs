import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const source = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("web Sign in with Apple uses the same identity and onboarding rules as Google", async () => {
  const [apple, social, complete, auth, routes, services] = await Promise.all([
    source("app/Http/Controllers/Auth/AppleAuthController.php"),
    source("app/Services/Auth/SocialLoginService.php"),
    source("resources/js/views/auth/AppleComplete.vue"),
    source("resources/js/views/auth/Auth.vue"),
    source("routes/web.php"),
    source("config/services.php"),
  ]);
  assert.match(apple, /SocialLoginService/);
  assert.match(apple, /auth\/apple\/complete\?onboarding=/);
  assert.match(social, /provider.*providerUserId/s);
  assert.match(social, /withTrashed\(\)/);
  assert.match(social, /status = 'disabled'/);
  assert.match(social, /incompleteAcademicSetup/);
  assert.match(complete, /exchangeWebSession/);
  assert.match(complete, /\/onboarding\?source=apple/);
  assert.doesNotMatch(auth, /Continue with Apple/, "Apple sign-in remains implemented but hidden from the web sign-in UI");
  assert.match(routes, /auth\/apple\/callback/);
  assert.match(routes, /withoutMiddleware/);
  assert.match(services, /apple_sign_in/);
});

test("Apple identity tokens are verified against Apple signing keys and application audiences", async () => {
  const service = await source("app/Services/Auth/AppleSignInService.php");
  assert.match(service, /appleid\.apple\.com\/auth\/keys/);
  assert.match(service, /JWK::parseKeySet/);
  assert.match(service, /JWT::decode/);
  assert.match(service, /https:\/\/appleid\.apple\.com/);
  assert.match(service, /array_intersect\(\$audiences, \$tokenAudiences\)/);
  assert.match(service, /expectedNonce/);
});

test("native Apple sign-in issues the normal single-session API token and reports onboarding requirement", async () => {
  const [controller, apiRoutes, api] = await Promise.all([
    source("app/Http/Controllers/Api/AppleSignInController.php"),
    source("routes/api.php"),
    source("resources/js/lib/api.ts"),
  ]);
  assert.match(controller, /verifyIdentityToken/);
  assert.match(controller, /SocialLoginService/);
  assert.match(controller, /issueApiToken/);
  assert.match(controller, /onboarding_required/);
  assert.match(apiRoutes, /auth\/apple/);
  assert.match(api, /appleNativeLogin/);
  assert.match(api, /onboardingRequired/);
});
