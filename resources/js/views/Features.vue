<script setup lang="ts">
/**
 * Ported from features.tsx. The hero's initial fade-in-on-load animations
 * (framer-motion `initial`/`animate`, not scroll-triggered) become a simple
 * mounted-flag CSS transition here — same visual effect, no library needed.
 */
import { onMounted, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { AnimatedButton } from "@/components/ui/animated-button";
import {
  ArrowRight, LayoutDashboard, BookOpen, Timer, BarChart3, Sparkles, Brain, Target, Flame, Trophy,
  CheckCircle2, PenLine, MessageSquareText, TrendingUp, Zap, ShieldCheck, GraduationCap,
} from "lucide-vue-next";
import FeatureSection from "./features/FeatureSection.vue";
import Reveal from "./features/Reveal.vue";
import DashboardMockup from "./features/DashboardMockup.vue";
import LearnModeMockup from "./features/LearnModeMockup.vue";
import AIFeedbackMockup from "./features/AIFeedbackMockup.vue";
import AnalyticsMockup from "./features/AnalyticsMockup.vue";

setPageMeta({
  title: "Features — AiStar Prep",
  description: "Discover how AI Star Prep helps students prepare smarter with AI-powered learning, practice, and analytics.",
});

const TRUST_ROW = [
  { i: ShieldCheck, t: "Examiner-aligned" },
  { i: GraduationCap, t: "Built for A-Levels & GCSEs" },
  { i: Zap, t: "Instant AI feedback" },
];

const mounted = ref(false);
onMounted(() => requestAnimationFrame(() => (mounted.value = true)));
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col overflow-x-clip">
    <TopNav />
    <main class="flex-1">
      <!-- ================= HERO ================= -->
      <section class="relative pt-20 pb-16 md:pt-28 md:pb-24 overflow-hidden">
        <div aria-hidden class="pointer-events-none absolute inset-0 -z-10">
          <div class="absolute top-[-10%] left-1/2 -translate-x-1/2 w-[900px] h-[520px] bg-[radial-gradient(50%_50%_at_50%_50%,rgba(59,110,248,0.18),transparent_70%)]" />
          <div class="absolute top-[15%] left-[8%] w-[320px] h-[320px] bg-[radial-gradient(50%_50%_at_50%_50%,rgba(124,79,224,0.14),transparent_70%)] blur-2xl" />
          <div class="absolute top-[10%] right-[6%] w-[300px] h-[300px] bg-[radial-gradient(50%_50%_at_50%_50%,rgba(13,184,130,0.12),transparent_70%)] blur-2xl" />
          <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="grid" width="32" height="32" patternUnits="userSpaceOnUse">
                <path d="M 32 0 L 0 0 0 32" fill="none" stroke="#0E1330" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>
        </div>

        <div class="max-w-[1080px] mx-auto px-5 text-center">
          <div
            class="inline-flex items-center gap-2 rounded-full border border-border bg-bg-card/70 backdrop-blur px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] text-ink3 mb-6 shadow-sm transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
          >
            <Sparkles :size="11" class="text-blue" />
            Features
          </div>

          <h1
            class="text-[40px] md:text-[58px] font-bold text-ink tracking-[-0.035em] leading-[1.02] mb-5 transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition-delay: 0.05s"
          >
            Prepare smarter
            <span class="bg-gradient-to-r from-blue via-violet to-blue bg-clip-text text-transparent">with AI.</span>
          </h1>

          <p
            class="text-[16px] md:text-[18px] text-ink3 font-light max-w-[640px] mx-auto leading-relaxed mb-8 transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition-delay: 0.15s"
          >
            AI Star Prep combines examiner-grade AI marking, exam-style questions,
            and personalised feedback so students practise efficiently and walk
            into exams ready.
          </p>

          <div
            class="flex items-center justify-center gap-3 flex-wrap transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition-delay: 0.25s"
          >
            <AnimatedButton to="/demo" variant="primary" size="lg">
              Try the demo <ArrowRight :size="16" />
            </AnimatedButton>
            <AnimatedButton to="/pricing" variant="outline" size="lg" :sparkle="false">
              View pricing
            </AnimatedButton>
          </div>

          <!-- Quick trust row -->
          <div
            class="mt-10 flex items-center justify-center gap-6 flex-wrap text-[12px] text-ink4 transition-opacity duration-[800ms]"
            :class="mounted ? 'opacity-100' : 'opacity-0'"
            style="transition-delay: 0.4s"
          >
            <span v-for="t in TRUST_ROW" :key="t.t" class="inline-flex items-center gap-1.5">
              <component :is="t.i" :size="13" class="text-ink3" /> {{ t.t }}
            </span>
          </div>
        </div>
      </section>

      <!-- ================= PLATFORM FEATURES ================= -->
      <FeatureSection
        label="Platform Features"
        title="Everything you need to study, in one command centre."
        description="A live dashboard that tracks readiness, streaks and predicted grades across every subject — so students always know exactly what to work on next."
        :icon="LayoutDashboard"
        tone="blue"
        :bullets="[
          { icon: Target, text: 'Live readiness score across every subject' },
          { icon: Flame, text: 'Streaks and daily goals that build habit' },
          { icon: Trophy, text: 'Predicted grades updated after every session' },
        ]"
      >
        <template #preview><DashboardMockup /></template>
      </FeatureSection>

      <!-- ================= LEARNING EXPERIENCE ================= -->
      <FeatureSection
        label="Learning Experience"
        title="Practice the way you learn best."
        description="Adaptive Learn Mode surfaces the right question at the right moment, with guided hints, instant explanations and full past-paper reproduction."
        :icon="BookOpen"
        tone="violet"
        reverse
        :bullets="[
          { icon: PenLine, text: 'Type or handwrite answers — your choice' },
          { icon: MessageSquareText, text: 'Instant explanations after every question' },
          { icon: Timer, text: 'Exam Mode with real timing and mark schemes' },
        ]"
      >
        <template #preview><LearnModeMockup /></template>
      </FeatureSection>

      <!-- ================= AI FEATURES ================= -->
      <FeatureSection
        label="AI Features"
        title="AI that thinks like an examiner."
        description="Every answer is marked against the real specification. Our AI recognises method marks, partial credit and common misconceptions — then tells you exactly how to score the next one."
        :icon="Brain"
        tone="mint"
        :bullets="[
          { icon: CheckCircle2, text: 'Point-by-point marking against the spec' },
          { icon: Sparkles, text: 'Model-answer comparison and rewrites' },
          { icon: Zap, text: 'Targeted hints that unlock the next mark' },
        ]"
      >
        <template #preview><AIFeedbackMockup /></template>
      </FeatureSection>

      <!-- ================= ANALYTICS & PROGRESS ================= -->
      <FeatureSection
        label="Analytics & Progress"
        title="See exactly where you stand."
        description="Track readiness week by week, identify weak topics before they cost you marks, and watch measurable improvement build across every paper."
        :icon="BarChart3"
        tone="gold"
        reverse
        :bullets="[
          { icon: TrendingUp, text: '12-week trend of your grade trajectory' },
          { icon: Target, text: 'Automatic weak-topic detection' },
          { icon: Trophy, text: 'Milestones that map to your target grade' },
        ]"
      >
        <template #preview><AnalyticsMockup /></template>
      </FeatureSection>

      <!-- ================= CTA ================= -->
      <section class="relative py-24 md:py-32 overflow-hidden isolate">
        <div aria-hidden class="absolute inset-0 -z-30 bg-[linear-gradient(135deg,#0E1330_0%,#1a2a6b_55%,#2952d8_100%)]" />
        <div aria-hidden class="absolute -top-24 -left-16 w-72 h-72 rounded-full bg-violet/40 blur-3xl -z-20 cta-orb-a" />
        <div aria-hidden class="absolute -bottom-28 -right-16 w-96 h-96 rounded-full bg-blue/40 blur-3xl -z-20 cta-orb-b" />
        <div aria-hidden class="absolute top-1/3 left-1/2 -translate-x-1/2 w-[520px] h-[520px] rounded-full bg-[radial-gradient(circle,rgba(124,79,224,0.35),transparent_70%)] -z-20 cta-orb-c" />
        <div aria-hidden class="absolute inset-0 -z-10 opacity-[0.08]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 26px 26px" />
        <div aria-hidden class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgba(6,10,30,0.55)_0%,rgba(6,10,30,0.35)_45%,transparent_80%)]" />

        <div class="relative z-10 max-w-[880px] mx-auto px-5 text-center">
          <Reveal>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] text-white mb-6">
              <Sparkles :size="11" /> Start free
            </span>
          </Reveal>
          <Reveal :delay="0.05">
            <h2 class="text-[34px] md:text-[52px] font-bold text-white leading-[1.05] tracking-[-0.03em] mb-5" style="text-shadow: 0 2px 24px rgba(6,10,30,0.55)">
              Ready to see AI Star Prep in action?
            </h2>
          </Reveal>
          <Reveal :delay="0.1">
            <p class="text-[16px] md:text-[17px] text-white/95 max-w-[560px] mx-auto leading-relaxed mb-9" style="text-shadow: 0 1px 12px rgba(6,10,30,0.55)">
              Start with a free demo and experience how AI transforms your exam
              preparation — no card required.
            </p>
          </Reveal>
          <Reveal :delay="0.15">
            <div class="flex items-center justify-center gap-3 flex-wrap">
              <AnimatedButton to="/demo" variant="white" size="lg">
                Try the demo free <ArrowRight :size="16" />
              </AnimatedButton>
              <AnimatedButton
                to="/pricing"
                variant="outline"
                size="lg"
                :sparkle="false"
                class="!bg-white/10 !border-white/30 !text-white hover:!bg-white/20"
              >
                See pricing
              </AnimatedButton>
            </div>
          </Reveal>
        </div>
      </section>
    </main>
    <Footer />
  </div>
</template>

<style scoped>
@keyframes cta-drift-a {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(30px, 20px) scale(1.08); }
}
@keyframes cta-drift-b {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-28px, -22px) scale(1.1); }
}
@keyframes cta-drift-c {
  0%, 100% { transform: translate(-50%, 0) scale(1); opacity: 0.9; }
  50% { transform: translate(-50%, -18px) scale(1.05); opacity: 1; }
}
.cta-orb-a { animation: cta-drift-a 11s ease-in-out infinite; will-change: transform; }
.cta-orb-b { animation: cta-drift-b 13s ease-in-out infinite; will-change: transform; }
.cta-orb-c { animation: cta-drift-c 15s ease-in-out infinite; will-change: transform; }
@media (prefers-reduced-motion: reduce) {
  .cta-orb-a, .cta-orb-b, .cta-orb-c { animation: none; }
}
</style>
