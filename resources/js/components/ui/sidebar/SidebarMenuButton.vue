<script setup lang="ts">
import { computed } from "vue";
import { Primitive, type PrimitiveProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { useSidebar } from "./useSidebar";
import { sidebarMenuButtonVariants, type SidebarMenuButtonVariants } from "./menu-button-variants";

const props = withDefaults(
  defineProps<
    PrimitiveProps & {
      isActive?: boolean;
      variant?: SidebarMenuButtonVariants["variant"];
      size?: SidebarMenuButtonVariants["size"];
      /** string is shorthand for `{ children: tooltip }` in the source; here just pass text via the `tooltip` prop, or use the `tooltip` slot for full TooltipContent props/content. */
      tooltip?: string;
      class?: string;
    }
  >(),
  { as: "button", isActive: false, variant: "default", size: "default" },
);

const { isMobile, state } = useSidebar();

const hidden = computed(() => state !== "collapsed" || isMobile.value);
</script>

<template>
  <Tooltip v-if="tooltip">
    <TooltipTrigger as-child>
      <Primitive
        :as="as"
        :as-child="asChild"
        data-sidebar="menu-button"
        :data-size="size"
        :data-active="isActive"
        :class="cn(sidebarMenuButtonVariants({ variant, size }), props.class)"
      >
        <slot />
      </Primitive>
    </TooltipTrigger>
    <TooltipContent side="right" align="center" :hidden="hidden">
      {{ tooltip }}
    </TooltipContent>
  </Tooltip>
  <Primitive
    v-else
    :as="as"
    :as-child="asChild"
    data-sidebar="menu-button"
    :data-size="size"
    :data-active="isActive"
    :class="cn(sidebarMenuButtonVariants({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
