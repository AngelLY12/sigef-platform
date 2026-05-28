export function createParams<T>(
  base: T,
  overrides: Partial<T> = {}
): T {
  return {
    ...base,
    ...overrides
  };
}
