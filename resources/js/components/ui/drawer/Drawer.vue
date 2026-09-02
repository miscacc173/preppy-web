<script setup lang="ts">
import { DrawerRoot, type DrawerRootProps } from "vaul-vue";

const props = withDefaults(defineProps<DrawerRootProps>(), {
  shouldScaleBackground: true,
});
</script>
<template>
  <DrawerRoot v-bind="props">
    <slot />
  </DrawerRoot>
</template>
