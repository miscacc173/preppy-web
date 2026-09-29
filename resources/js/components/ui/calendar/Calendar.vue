<script setup lang="ts">
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import {
  CalendarRoot,
  CalendarHeader,
  CalendarPrev,
  CalendarHeading,
  CalendarNext,
  CalendarGrid,
  CalendarGridHead,
  CalendarGridRow,
  CalendarHeadCell,
  CalendarGridBody,
  CalendarCell,
  CalendarCellTrigger,
  type CalendarRootProps,
} from "reka-ui";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";

const props = withDefaults(defineProps<CalendarRootProps & { class?: string }>(), {
  fixedWeeks: true,
});
</script>

<template>
  <CalendarRoot
    v-bind="props"
    v-slot="{ grid, weekDays }"
    :class="cn('bg-background group/calendar p-3', props.class)"
  >
    <CalendarHeader class="relative flex w-full items-center justify-between pt-1">
      <CalendarPrev :class="cn(buttonVariants({ variant: 'ghost' }), 'h-8 w-8 select-none bg-transparent p-0 opacity-70 hover:opacity-100')">
        <ChevronLeft class="h-4 w-4" />
      </CalendarPrev>
      <CalendarHeading class="text-sm font-medium" />
      <CalendarNext :class="cn(buttonVariants({ variant: 'ghost' }), 'h-8 w-8 select-none bg-transparent p-0 opacity-70 hover:opacity-100')">
        <ChevronRight class="h-4 w-4" />
      </CalendarNext>
    </CalendarHeader>

    <div class="mt-4 flex flex-col gap-4 sm:flex-row">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse space-y-1">
        <CalendarGridHead>
          <CalendarGridRow class="mb-1 flex w-full justify-between">
            <CalendarHeadCell
              v-for="day in weekDays"
              :key="day"
              class="w-8 rounded-md text-[0.8rem] font-normal text-muted-foreground"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody class="flex flex-col gap-1">
          <CalendarGridRow v-for="(week, index) in month.rows" :key="`week-${index}`" class="flex w-full justify-between">
            <CalendarCell
              v-for="weekDate in week"
              :key="weekDate.toString()"
              :date="weekDate"
              class="relative h-8 w-8 p-0 text-center text-sm focus-within:relative focus-within:z-20"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
                :class="
                  cn(
                    buttonVariants({ variant: 'ghost' }),
                    'h-8 w-8 p-0 font-normal',
                    'data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[selected]:opacity-100',
                    'data-[today]:bg-accent data-[today]:text-accent-foreground',
                    'data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-50',
                    'data-[disabled]:text-muted-foreground data-[disabled]:opacity-50',
                  )
                "
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>
