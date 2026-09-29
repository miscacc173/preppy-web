<script setup lang="ts">
const ROW1 = ["Mathematics", "Physics", "Chemistry", "Biology", "Economics", "Business", "Computer Science", "Psychology", "Geography", "History", "English Literature"];
const ROW2 = [...ROW1].reverse();
</script>

<template>
  <div class="relative overflow-hidden py-1">
    <div class="absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-bg to-transparent z-10 pointer-events-none" />
    <div class="absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-bg to-transparent z-10 pointer-events-none" />

    <div class="flex overflow-hidden py-2">
      <div class="flex gap-2 shrink-0 animate-marquee-l">
        <span
          v-for="(s, i) in [...ROW1, ...ROW1]"
          :key="i"
          class="text-[13px] text-ink2 bg-bg-card border border-border px-3.5 py-1.5 rounded-md whitespace-nowrap hover:border-blue hover:text-blue hover:bg-blue-light transition"
        >
          {{ s }}
        </span>
      </div>
    </div>
    <div class="flex overflow-hidden py-2">
      <div class="flex gap-2 shrink-0 animate-marquee-r">
        <span
          v-for="(s, i) in [...ROW2, ...ROW2]"
          :key="i"
          class="text-[13px] text-ink2 bg-bg-card border border-border px-3.5 py-1.5 rounded-md whitespace-nowrap hover:border-blue hover:text-blue hover:bg-blue-light transition"
        >
          {{ s }}
        </span>
      </div>
    </div>
  </div>
</template>
