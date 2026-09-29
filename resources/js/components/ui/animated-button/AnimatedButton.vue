<script setup lang="ts">
/**
 * Premium SaaS-style CTA button with AI-energy animations.
 * Ported from animated-button.tsx. The React version drives hover/tap scale via
 * framer-motion `whileHover`/`whileTap`; here that's replaced with a plain CSS
 * transition (`hover:scale-[1.03] active:scale-[0.97]`) to avoid pulling in a
 * motion library for two transform values — everything else (glow, particles,
 * shimmer, twinkle, ripple) uses the same keyframe classes from styles.css and
 * behaves identically. `prefers-reduced-motion` is handled globally in styles.css
 * (see the `@media (prefers-reduced-motion: reduce)` block), so no JS reduced-motion
 * branch is needed here.
 */
import { ref } from "vue";
import { cn } from "@/lib/utils";
import { Sparkles } from "lucide-vue-next";

type Variant = "primary" | "violet" | "mint" | "dark" | "white" | "outline";
type Size = "sm" | "md" | "lg";

const props = withDefaults(
  defineProps<{
    to?: string;
    href?: string;
    variant?: Variant;
    size?: Size;
    class?: string;
    external?: boolean;
    /** Show floating sparkle accent on the right side */
    sparkle?: boolean;
  }>(),
  { variant: "primary", size: "md", sparkle: true },
);

const emit = defineEmits<{ click: [] }>();

const isPressed = ref(false);

const sizeClasses = (size: Size) =>
  cn(
    size === "sm" && "text-[13px] px-5 py-2.5 rounded-full",
    size === "md" && "text-[14px] px-6 py-3 rounded-full",
    size === "lg" && "text-[15px] px-8 py-3.5 rounded-full",
  );

const variantGradients: Record<Variant, string> = {
  primary: "bg-[linear-gradient(135deg,#3B6EF8_0%,#5B8BFF_50%,#3B6EF8_100%)] bg-[length:200%_200%]",
  violet: "bg-[linear-gradient(135deg,#7C4FE0_0%,#A855F7_50%,#7C4FE0_100%)] bg-[length:200%_200%]",
  mint: "bg-[linear-gradient(135deg,#0DB882_0%,#34D399_50%,#0DB882_100%)] bg-[length:200%_200%]",
  dark: "bg-[linear-gradient(135deg,#0f172a_0%,#1e293b_50%,#0f172a_100%)] bg-[length:200%_200%]",
  white: "bg-white",
  outline: "border border-border bg-bg-card",
};
const variantText: Record<Variant, string> = {
  primary: "text-white", violet: "text-white", mint: "text-white", dark: "text-white",
  white: "text-blue", outline: "text-ink2",
};
const variantShadow: Record<Variant, string> = {
  primary: "shadow-[0_6px_20px_-4px_rgba(59,110,248,0.45)]",
  violet: "shadow-[0_6px_20px_-4px_rgba(124,79,224,0.45)]",
  mint: "shadow-[0_6px_20px_-4px_rgba(13,184,130,0.45)]",
  dark: "shadow-[0_6px_20px_-4px_rgba(15,23,42,0.45)]",
  white: "shadow-[0_6px_20px_-4px_rgba(59,110,248,0.18)]",
  outline: "",
};
const variantHoverShadow: Record<Variant, string> = {
  primary: "hover:shadow-[0_12px_40px_-4px_rgba(59,110,248,0.55)]",
  violet: "hover:shadow-[0_12px_40px_-4px_rgba(124,79,224,0.55)]",
  mint: "hover:shadow-[0_12px_40px_-4px_rgba(13,184,130,0.55)]",
  dark: "hover:shadow-[0_12px_40px_-4px_rgba(15,23,42,0.55)]",
  white: "hover:shadow-[0_12px_40px_-4px_rgba(59,110,248,0.28)]",
  outline: "hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.25)]",
};
const variantGlow: Record<Variant, string> = {
  primary: "bg-[radial-gradient(circle_at_center,rgba(91,139,255,0.6),transparent_70%)]",
  violet: "bg-[radial-gradient(circle_at_center,rgba(168,85,247,0.6),transparent_70%)]",
  mint: "bg-[radial-gradient(circle_at_center,rgba(52,211,153,0.6),transparent_70%)]",
  dark: "bg-[radial-gradient(circle_at_center,rgba(30,41,59,0.6),transparent_70%)]",
  white: "bg-[radial-gradient(circle_at_center,rgba(59,110,248,0.35),transparent_70%)]",
  outline: "bg-[radial-gradient(circle_at_center,rgba(59,110,248,0.25),transparent_70%)]",
};
const variantRing: Record<Variant, string> = {
  primary: "shadow-[0_0_0_1px_rgba(91,139,255,0.5),0_0_20px_rgba(91,139,255,0.3)]",
  violet: "shadow-[0_0_0_1px_rgba(168,85,247,0.5),0_0_20px_rgba(168,85,247,0.3)]",
  mint: "shadow-[0_0_0_1px_rgba(52,211,153,0.5),0_0_20px_rgba(52,211,153,0.3)]",
  dark: "shadow-[0_0_0_1px_rgba(100,116,139,0.4),0_0_20px_rgba(15,23,42,0.3)]",
  white: "shadow-[0_0_0_1px_rgba(255,255,255,0.6),0_0_20px_rgba(59,110,248,0.2)]",
  outline: "shadow-[0_0_0_1px_rgba(59,110,248,0.4),0_0_20px_rgba(59,110,248,0.2)]",
};
const variantShimmer: Record<Variant, string> = {
  primary: "from-transparent via-white/35 to-transparent",
  violet: "from-transparent via-white/35 to-transparent",
  mint: "from-transparent via-white/35 to-transparent",
  dark: "from-transparent via-white/20 to-transparent",
  white: "from-transparent via-blue/20 to-transparent",
  outline: "from-transparent via-blue/15 to-transparent",
};
const variantSparkle: Record<Variant, string> = {
  primary: "text-white/90", violet: "text-white/90", mint: "text-white/90", dark: "text-white/90",
  white: "text-blue", outline: "text-blue",
};
const variantParticle: Record<Variant, string> = {
  primary: "bg-blue", violet: "bg-violet", mint: "bg-mint", dark: "bg-slate-400",
  white: "bg-blue", outline: "bg-blue",
};

const baseClasses = cn(
  "group relative inline-flex items-center justify-center gap-2 font-semibold overflow-hidden isolate select-none",
  "transition-[box-shadow,filter,transform] duration-300 ease-out will-change-transform",
  "hover:scale-[1.03] active:scale-[0.97]",
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent focus-visible:ring-blue/60",
  sizeClasses(props.size),
  variantText[props.variant],
  variantGradients[props.variant],
  variantShadow[props.variant],
  variantHoverShadow[props.variant],
  "hover:brightness-110",
  props.class,
);

const hasGradient = props.variant !== "white" && props.variant !== "outline";

function handlePointerDown() { isPressed.value = true; }
function handlePointerUp() { isPressed.value = false; }
function onClick() { emit("click"); }
</script>

<template>
  <component
    :is="to ? 'router-link' : href ? 'a' : 'button'"
    :to="to"
    :href="href"
    v-bind="href && external ? { target: '_blank', rel: 'noopener noreferrer' } : {}"
    :class="baseClasses"
    @click="onClick"
    @pointerdown="handlePointerDown"
    @pointerup="handlePointerUp"
    @pointerleave="handlePointerUp"
  >
    <!-- OUTER GLOW (hover) -->
    <span aria-hidden :class="cn('pointer-events-none absolute -inset-2 rounded-full opacity-0 blur-xl transition-opacity duration-500 group-hover:opacity-70 -z-10', variantGlow[variant])" />

    <!-- GLOW BORDER RING (hover) -->
    <span aria-hidden :class="cn('pointer-events-none absolute -inset-[1px] rounded-full opacity-0 blur-[2px] transition-all duration-400 group-hover:opacity-100 -z-[5]', variantRing[variant])" />

    <!-- FLOATING PARTICLES (hover only) -->
    <span aria-hidden :class="cn('pointer-events-none absolute w-1 h-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300', '-top-1 -left-2 animate-particle-float-1', variantParticle[variant])" />
    <span aria-hidden :class="cn('pointer-events-none absolute w-[5px] h-[5px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100', '-top-2 right-4 animate-particle-float-2', variantParticle[variant])" />
    <span aria-hidden :class="cn('pointer-events-none absolute w-[3px] h-[3px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-200', '-bottom-2 left-3 animate-particle-float-3', variantParticle[variant])" />
    <span aria-hidden :class="cn('pointer-events-none absolute w-1.5 h-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-150', '-bottom-1 -right-1 animate-particle-float-1', variantParticle[variant])" />
    <span aria-hidden :class="cn('pointer-events-none absolute w-[4px] h-[4px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-75', '-top-3 left-1/2 animate-particle-float-2', variantParticle[variant])" />

    <!-- ANIMATED GRADIENT DRIFT (gradient variants only) -->
    <span v-if="hasGradient" aria-hidden class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-inherit animate-gradient-drift" />

    <!-- SHIMMER SWEEP ON HOVER -->
    <span aria-hidden class="pointer-events-none absolute inset-0 overflow-hidden rounded-full">
      <span :class="cn('absolute top-0 left-0 h-full w-1/2 -translate-x-full skew-x-[-20deg]', 'opacity-0 group-hover:opacity-100 group-hover:animate-shimmer-sweep', 'bg-gradient-to-r', variantShimmer[variant])" />
    </span>

    <!-- INNER TOP HIGHLIGHT (glossy depth) -->
    <span v-if="hasGradient" aria-hidden class="pointer-events-none absolute inset-x-4 top-px h-px rounded-full bg-white/40 opacity-60" />

    <!-- CLICK RIPPLE (active state) -->
    <span
      aria-hidden
      :class="cn(
        'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full opacity-0',
        isPressed && 'animate-ripple-pulse',
        variant === 'primary' && 'bg-blue/30',
        variant === 'violet' && 'bg-violet/30',
        variant === 'mint' && 'bg-mint/30',
        variant === 'dark' && 'bg-slate-400/20',
        variant === 'white' && 'bg-blue/20',
        variant === 'outline' && 'bg-blue/15',
      )"
    />

    <!-- CONTENT -->
    <span class="relative z-10 flex items-center gap-2">
      <slot />
      <span v-if="sparkle" class="relative inline-flex items-center justify-center w-4 h-4">
        <Sparkles :class="cn('w-4 h-4 transition-transform duration-300 group-hover:rotate-12 group-hover:scale-110 animate-twinkle', variantSparkle[variant])" />
        <span aria-hidden :class="cn('absolute -top-0.5 -right-1 w-1 h-1 rounded-full animate-twinkle-dot', (variant === 'primary' || variant === 'violet' || variant === 'mint' || variant === 'dark') ? 'bg-white' : 'bg-blue')" />
        <span aria-hidden :class="cn('absolute -bottom-1 -left-0.5 w-[3px] h-[3px] rounded-full animate-twinkle-dot-2', (variant === 'primary' || variant === 'violet' || variant === 'mint' || variant === 'dark') ? 'bg-white/80' : 'bg-blue/80')" />
      </span>
    </span>
  </component>
</template>
