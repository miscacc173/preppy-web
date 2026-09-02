<script setup lang="ts">
import { GraduationCap, Users, Sparkles, ArrowRight, Check } from "lucide-vue-next";
import { AnimatedButton } from "@/components/ui/animated-button";

const CARDS = [
  {
    i: GraduationCap,
    t: "I'm a Student",
    desc: "Identify weak topics, practice smarter, and boost your grades.",
    points: ["Instant topic diagnostics", "AI mark-scheme feedback", "Readiness & progress tracking", "Personalised study plans", "Exam-style practice papers"],
    to: "/student",
    badge: "Most Popular",
    badgeBg: "bg-blue-50",
    badgeText: "text-blue-600",
    iconBg: "bg-blue-50",
    iconColor: "text-blue-500",
    checkColor: "text-blue-500",
    ctaText: "text-blue-600",
    ctaBg: "bg-blue-50/60",
    ctaHover: "hover:bg-blue-50",
    borderHover: "hover:border-blue-300",
    glow: "from-blue-400/20 to-blue-300/5",
    cta: "Explore as Student",
  },
  {
    i: Users,
    t: "I'm a Teacher",
    desc: "Track class readiness and identify learning gaps.",
    points: ["Track class performance", "Identify struggling topics", "Share practice & resources", "Monitor progress over time", "Powerful teacher dashboard"],
    to: "/teacher",
    badge: "For Schools",
    badgeBg: "bg-violet-50",
    badgeText: "text-violet-600",
    iconBg: "bg-violet-50",
    iconColor: "text-violet-500",
    checkColor: "text-violet-500",
    ctaText: "text-violet-600",
    ctaBg: "bg-violet-50/60",
    ctaHover: "hover:bg-violet-50",
    borderHover: "hover:border-violet-300",
    glow: "from-violet-400/20 to-violet-300/5",
    cta: "Explore as Teacher",
  },
  {
    i: Sparkles,
    t: "I'm a Self-Learner",
    desc: "Study independently with AI-guided support.",
    points: ["Personalised study plans", "AI feedback & explanations", "Practice at your own pace", "Track your readiness", "Achieve your target grades"],
    to: "/signup",
    badge: "Flexible",
    badgeBg: "bg-emerald-50",
    badgeText: "text-emerald-600",
    iconBg: "bg-emerald-50",
    iconColor: "text-emerald-500",
    checkColor: "text-emerald-500",
    ctaText: "text-emerald-600",
    ctaBg: "bg-emerald-50/60",
    ctaHover: "hover:bg-emerald-50",
    borderHover: "hover:border-emerald-300",
    glow: "from-emerald-400/20 to-emerald-300/5",
    cta: "Explore as Self-Learner",
  },
] as const;
</script>

<template>
  <section class="px-6 py-20 lg:py-28 border-t border-border bg-[#FAFBFC]">
    <div class="max-w-[1160px] mx-auto grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-14 lg:gap-16 items-start">
      <!-- Left copy -->
      <div class="pt-2">
        <p class="label-mono mb-4 text-blue-600">Built for every learner</p>
        <h2 class="text-[32px] md:text-[40px] font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-5">
          Improve grades with an <span class="text-blue-600">AI study partner.</span>
        </h2>
        <p class="text-[15px] text-ink2 leading-[1.65] font-light mb-8 max-w-[380px]">
          Personalised diagnostics, mark-scheme feedback, readiness tracking,
          and subject-specific study plans — for students, teachers, and
          self-learners.
        </p>
        <AnimatedButton to="/signup" variant="mint" size="md">
          Start Studying Smarter <ArrowRight :size="16" />
        </AnimatedButton>
      </div>

      <!-- Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <RouterLink
          v-for="c in CARDS"
          :key="c.t"
          :to="c.to"
          :class="`group relative flex flex-col bg-white border border-[#E5E7EB] rounded-[20px] transition-all duration-300 hover:-translate-y-[4px] hover:shadow-[0_24px_60px_rgba(15,23,42,0.08)] ${c.borderHover} overflow-hidden`"
          style="box-shadow: 0 4px 20px rgba(15,23,42,0.04)"
        >
          <!-- Subtle grid pattern -->
          <div
            class="absolute inset-0 opacity-[0.025] pointer-events-none"
            style="background-image: linear-gradient(#0f172a 1px, transparent 1px), linear-gradient(90deg, #0f172a 1px, transparent 1px); background-size: 24px 24px"
          />

          <!-- Card body -->
          <div class="relative z-10 flex flex-col flex-1 p-6 pb-5">
            <!-- Top row: icon + badge -->
            <div class="flex items-start justify-between mb-5">
              <div class="relative">
                <div :class="`absolute -top-1 -left-1 w-14 h-14 rounded-full bg-gradient-to-br ${c.glow} blur-lg opacity-50 group-hover:opacity-80 transition-opacity`" />
                <div :class="`relative w-12 h-12 rounded-2xl ${c.iconBg} grid place-items-center`">
                  <component :is="c.i" :size="22" :class="c.iconColor" />
                </div>
              </div>
              <span :class="`text-[11px] font-semibold px-3 py-1.5 rounded-full ${c.badgeBg} ${c.badgeText}`">{{ c.badge }}</span>
            </div>

            <!-- Title -->
            <h3 class="text-[20px] font-bold text-ink tracking-[-0.02em] mb-2">{{ c.t }}</h3>

            <!-- Description -->
            <p class="text-[14px] text-ink2 leading-[1.55] font-light mb-4">{{ c.desc }}</p>

            <!-- Divider -->
            <div class="border-t border-[#F1F2F4] mb-4" />

            <!-- Benefits -->
            <ul class="space-y-2.5 flex-1">
              <li v-for="point in c.points" :key="point" class="text-[13.5px] text-ink3 font-light leading-[1.45] flex items-start gap-2.5">
                <span :class="`mt-[3px] shrink-0 ${c.checkColor}`">
                  <Check :size="15" :stroke-width="2.5" />
                </span>
                {{ point }}
              </li>
            </ul>
          </div>

          <!-- Bottom CTA band -->
          <div :class="`relative z-10 flex items-center justify-center gap-2 py-3.5 text-[14px] font-semibold ${c.ctaText} ${c.ctaBg} ${c.ctaHover} transition-colors border-t border-[#F1F2F4]`">
            {{ c.cta }}
            <ArrowRight :size="15" class="group-hover:translate-x-0.5 transition-transform" />
          </div>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
