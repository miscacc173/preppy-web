<script setup lang="ts">
import { LoaderCircle } from "lucide-vue-next";
defineProps<{ loading: boolean; loadingLabel?: string }>();
</script>

<template><LoaderCircle v-if="loading" :size="14" class="animate-spin" /><span>{{ loading ? loadingLabel || 'Saving…' : '' }}</span><slot v-if="!loading" /></template>
