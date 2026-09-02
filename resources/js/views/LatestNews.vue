<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { Newspaper } from "lucide-vue-next";

setPageMeta({
  title: "Latest News — AiStar",
  description: "Product updates, exam-prep tips, and student success stories from AiStar.",
});

const NEWS = [
  { tag: "Product", title: "Coming soon: examiner-graded essay feedback" },
  { tag: "Study tips", title: "The 3-minute review that lifts your mark by a grade" },
  { tag: "Student story", title: "How Aisha went from C to A* in 12 weeks" },
  { tag: "Behind the scenes", title: "Inside our mark-scheme training pipeline" },
];
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg">
    <TopNav />
    <main class="flex-1 max-w-[900px] w-full mx-auto px-6 py-16">
      <p class="label-mono mb-3">Latest News</p>
      <h1 class="font-display text-[40px] md:text-[52px] tracking-[-0.02em] text-ink leading-tight">
        Updates, insights, and <span class="text-blue">student stories</span>.
      </h1>
      <p class="text-[15px] text-ink3 mt-4 max-w-[620px] leading-[1.6]">
        A home for AiStar product news, exam-prep tips from real examiners, and
        spotlights on students hitting their target grades. New articles land here soon.
      </p>

      <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-5">
        <article v-for="n in NEWS" :key="n.title" class="bg-bg-card border border-border rounded-xl p-6 flex flex-col gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-light grid place-items-center">
            <Newspaper :size="18" class="text-blue" />
          </div>
          <p class="label-mono">{{ n.tag }}</p>
          <h2 class="text-[16px] font-semibold text-ink tracking-[-0.01em] leading-snug">{{ n.title }}</h2>
          <p class="text-[12.5px] text-ink4 mt-auto">Coming soon</p>
        </article>
      </div>
    </main>
    <Footer />
  </div>
</template>
