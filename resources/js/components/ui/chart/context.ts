import type { InjectionKey } from "vue";
import type { ChartConfig } from ".";

export interface ChartContext {
  config: ChartConfig;
}
export const CHART_CONTEXT_KEY: InjectionKey<ChartContext> = Symbol("ChartContext");
