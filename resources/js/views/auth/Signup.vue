<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import Auth from "./Auth.vue";

setPageMeta({ title: "Create account — AiStar Prep", description: "Create your AiStar Prep account and start training for A*." });
</script>

<template>
  <Auth mode="signup" />
</template>
