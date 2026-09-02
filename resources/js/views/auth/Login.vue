<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import Auth from "./Auth.vue";

setPageMeta({ title: "Sign in — AiStar Prep", description: "Sign in to AiStar Prep." });
</script>

<template>
  <Auth mode="login" />
</template>
