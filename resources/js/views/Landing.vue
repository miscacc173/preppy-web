<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import Footer from "@/components/site/Footer.vue";
import TopNav from "@/components/site/TopNav.vue";
import DoodleBg from "@/components/decor/DoodleBg.vue";
import SubjectMarquee from "@/components/site/SubjectMarquee.vue";
import AudienceSection from "@/components/site/AudienceSection.vue";
import { TestimonialsSection } from "@/components/site/testimonials";
import HeroOurlearn from "@/components/site/HeroOurlearn.vue";
import { DashboardShowcase } from "@/components/site/dashboard-showcase";
import { AnimatedButton } from "@/components/ui/animated-button";
import { ArrowRight } from "lucide-vue-next";

setPageMeta({
  title: "Preppy — Think Like an A* Student",
  description: "Your AI study partner — trained on examiner reports, mark schemes, and top-scoring answers. Get instant feedback and improve with every question.",
});
</script>

<template>
  <div class="min-h-screen flex flex-col relative bg-bg">
    <DoodleBg />
    <TopNav />
    <main class="flex-1">
      <!-- Hero (includes hero content + stats band) -->
      <HeroOurlearn />

      <!-- Dashboard product preview with feature callouts -->
      <DashboardShowcase />

      <!-- Subject marquee -->
      <div class="bg-bg-soft/60 border-y border-border py-3">
        <SubjectMarquee />
      </div>

      <!-- Testimonials -->
      <TestimonialsSection />

      <!-- Audience section -->
      <AudienceSection />

      <!-- Bottom CTA -->
      <section class="bg-blue px-6 py-14 text-center">
        <h2 class="text-[26px] md:text-[34px] text-white leading-tight mb-2 tracking-[-0.02em]">
          Your A* isn't a mystery.<br />It's how top students answer.
        </h2>
        <p class="text-[15px] text-white/80 mb-6">Start free. See how Preppy reads your answers in minutes.</p>
        <AnimatedButton to="/demo" variant="violet" size="lg">
          Start Your A* Journey <ArrowRight :size="16" />
        </AnimatedButton>
      </section>
    </main>
    <Footer />
  </div>
</template>
