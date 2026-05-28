function normalize(value: any) {
  if (typeof value === 'string') {
    return value.trim().toLowerCase();
  }
  return value;
}

export function buildDiffPayload<T extends object>(
  original: T,
  updated: T
): Partial<T> {
  const result: Partial<T> = {};

  (Object.keys(updated) as (keyof T)[]).forEach((key) => {
    if (normalize(updated[key]) !== normalize(original[key])) {
      result[key] = updated[key];
    }
  });

  return result;
}
