/**
 * DEVIATION FROM SOURCE: the React version uses the `input-otp` npm package
 * (a single hidden input + rendered "slots" driven by context). There's no
 * direct Vue port of that exact package, so this uses reka-ui's `PinInput`
 * primitive instead — same end-user behavior (paste, arrow-key nav, auto-advance)
 * but built from N real inputs rather than one hidden input + fake carets.
 * API shape is intentionally kept close to the source (InputOTP, InputOTPGroup,
 * InputOTPSlot, InputOTPSeparator) so call sites barely change.
 */
export { default as InputOTP } from "./InputOTP.vue";
export { default as InputOTPGroup } from "./InputOTPGroup.vue";
export { default as InputOTPSlot } from "./InputOTPSlot.vue";
export { default as InputOTPSeparator } from "./InputOTPSeparator.vue";
