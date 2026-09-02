/**
 * DEVIATION FROM SOURCE: the React version wraps `react-day-picker` v9 with a
 * huge custom `classNames`/`components` override (its own Calendar/CalendarDayButton).
 * There's no Vue port of react-day-picker. This uses reka-ui's `Calendar` primitive
 * instead (built on `@internationalized/date`, the same foundation shadcn-vue's own
 * Calendar component uses) — same look, same keyboard nav, but modifiers/data-attributes
 * are reka-ui's own set rather than react-day-picker's, so custom modifier classes from
 * the source (data-range-start, data-range-middle, etc.) are re-derived from reka-ui's
 * equivalent slot props below. Needs `@internationalized/date` as a dependency.
 */
export { default as Calendar } from "./Calendar.vue";
