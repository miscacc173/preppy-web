import { inject } from "vue";
import { useField, useFormContext } from "vee-validate";
import { FORM_FIELD_CONTEXT_KEY, FORM_ITEM_CONTEXT_KEY } from "./context";

export function useFormField() {
  const fieldContext = inject(FORM_FIELD_CONTEXT_KEY);
  const itemContext = inject(FORM_ITEM_CONTEXT_KEY);

  if (!fieldContext) throw new Error("useFormField should be used within <FormField>");
  if (!itemContext) throw new Error("useFormField should be used within <FormItem>");

  const { errorMessage } = useField(fieldContext.name);
  const { id } = itemContext;

  return {
    id,
    name: fieldContext.name,
    formItemId: `${id}-form-item`,
    formDescriptionId: `${id}-form-item-description`,
    formMessageId: `${id}-form-item-message`,
    error: errorMessage,
  };
}
