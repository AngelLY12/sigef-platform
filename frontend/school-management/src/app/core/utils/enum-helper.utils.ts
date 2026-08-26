import { SelectOption } from "../../shared/components/form/controls/select/select-option.config.model";

export function enumToOptions<T extends Record<string, string>>(enumObj: T) {
  return Object.values(enumObj).map(value => ({
    label: value,
    value
  }));
}

export function enumToOptionsWithLabel<T extends Record<string, string>>(
  enumObj: T,
  labels: Record<T[keyof T], string>,
): SelectOption[] {
  return (Object.values(enumObj) as T[keyof T][]).map((value) => ({
    label: labels[value],
    value,
  }));
}