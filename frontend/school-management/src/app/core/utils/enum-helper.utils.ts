export function enumToOptions<T extends Record<string, string>>(enumObj: T) {
  return Object.values(enumObj).map(value => ({
    label: value,
    value
  }));
}
