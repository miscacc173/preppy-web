<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<template>
  <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-bg-card px-6 py-5">
    <p class="text-xs text-ink4">© {{ year }} Preppy. Built for top grades.</p>
    <div class="flex gap-4 flex-wrap">
      <RouterLink to="/pricing" class="text-xs text-ink4 hover:text-ink2">Pricing</RouterLink>
      <RouterLink to="/login" class="text-xs text-ink4 hover:text-ink2">Sign in</RouterLink>
      <RouterLink to="/demo" class="text-xs text-ink4 hover:text-ink2">Demo</RouterLink>
      <a href="#" class="text-xs text-ink4 hover:text-ink2">Privacy</a>
      <a href="#" class="text-xs text-ink4 hover:text-ink2">Terms</a>
    </div>
  </footer>
</template>
