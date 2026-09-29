/**
 * DEVIATION FROM SOURCE (the biggest one in this conversion): the React version
 * wraps `recharts`, whose components render as JSX children that recharts calls
 * back into with computed payload/data at draw time (e.g. `<Tooltip content={<ChartTooltipContent/>} />`).
 * The Vue equivalent chosen for this project is `vue-chartjs` + `chart.js`
 * (documented in CONVERSION_NOTES.md), which draws to canvas and does NOT support
 * passing Vue components as tooltip/legend renderers — it uses a JS options/plugin
 * callback model instead.
 *
 * So this file keeps everything that's pure logic and framework-agnostic (the
 * `ChartConfig` type, the CSS custom-property theme injection, and the
 * `getPayloadConfigFromPayload` lookup helper) ported 1:1, and re-shapes the
 * two "content" components into presentational Vue components that take a
 * `payload` array of the same shape recharts produces. To actually wire them
 * up to chart.js you render them from chart.js's `external` tooltip callback
 * (Chart.js option: `plugins.tooltip.enabled: false` + `plugins.tooltip.external`)
 * or from the `plugins.legend.labels.generateLabels` result — that glue code is
 * chart-specific and isn't written here since it depends on each chart's data
 * shape. Flag it if you want a specific chart's tooltip/legend wired end-to-end.
 */
import type { Component } from "vue";

const THEMES = { light: "", dark: ".dark" } as const;

export type ChartConfig = {
  [k in string]: {
    label?: string;
    icon?: Component;
  } & (
    | { color?: string; theme?: never }
    | { color?: never; theme: Record<keyof typeof THEMES, string> }
  );
};

export { default as ChartContainer } from "./ChartContainer.vue";
export { default as ChartStyle } from "./ChartStyle.vue";
export { default as ChartTooltipContent } from "./ChartTooltipContent.vue";
export { default as ChartLegendContent } from "./ChartLegendContent.vue";

export function getPayloadConfigFromPayload(config: ChartConfig, payload: unknown, key: string) {
  if (typeof payload !== "object" || payload === null) return undefined;

  const payloadPayload =
    "payload" in payload && typeof (payload as any).payload === "object" && (payload as any).payload !== null
      ? (payload as any).payload
      : undefined;

  let configLabelKey: string = key;

  if (key in payload && typeof (payload as any)[key] === "string") {
    configLabelKey = (payload as any)[key];
  } else if (payloadPayload && key in payloadPayload && typeof payloadPayload[key] === "string") {
    configLabelKey = payloadPayload[key];
  }

  return configLabelKey in config ? config[configLabelKey] : config[key as keyof typeof config];
}
