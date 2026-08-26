import { HttpParams } from "@angular/common/http";
import { cleanObject } from "../helpers";

export function createParams<T>(
  base: T,
  overrides: Partial<T> = {}
): T {
  return {
    ...base,
    ...overrides
  };
}

export function createHttpParams<T extends object>(filters: T): HttpParams {
  return new HttpParams({
    fromObject: Object.fromEntries(
      Object.entries(cleanObject(filters))
        .map(([key, value]) => [key, String(value)])
    )
  });
}
