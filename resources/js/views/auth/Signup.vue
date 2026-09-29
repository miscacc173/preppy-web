<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import Auth from "./Auth.vue";

setPageMeta({ title: "Create account — Preppy", description: "Create your Preppy account and start training for A*." });
</script>

<template>
  <Auth mode="signup" />
</template>
