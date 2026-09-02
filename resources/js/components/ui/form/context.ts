import type { InjectionKey, Ref } from "vue";

export interface FormFieldContext {
  name: string;
}
export const FORM_FIELD_CONTEXT_KEY: InjectionKey<FormFieldContext> = Symbol("FormFieldContext");

export interface FormItemContext {
  id: string;
}
export const FORM_ITEM_CONTEXT_KEY: InjectionKey<FormItemContext> = Symbol("FormItemContext");
