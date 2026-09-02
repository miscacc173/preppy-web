<script setup lang="ts"></script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-bg px-4">
    <div class="max-w-md text-center">
      <p class="label-mono">Error 404</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-ink">Page not found</h1>
      <p class="mt-3 text-sm text-ink3">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <div class="mt-8">
        <RouterLink
          to="/"
          class="inline-flex h-11 items-center justify-center rounded-md bg-blue px-6 text-sm font-semibold text-white hover:opacity-90 transition"
        >
          Return home
        </RouterLink>
      </div>
    </div>
  </div>
</template>
