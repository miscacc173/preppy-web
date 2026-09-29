<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import Auth from "./Auth.vue";

setPageMeta({ title: "Sign in — Preppy", description: "Sign in to Preppy." });
</script>

<template>
  <Auth mode="login" />
</template>
