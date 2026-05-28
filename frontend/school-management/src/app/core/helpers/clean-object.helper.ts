/**
 * Limpia un objeto eliminando propiedades con valores null, undefined o vacíos
 * @param obj Objeto a limpiar
 * @returns Objeto parcial con solo las propiedades que tienen valores válidos
 */
export function cleanObject<T extends object>(obj: T): Partial<T> {
  return Object.entries(obj).reduce((acc, [key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      acc[key as keyof T] = value;
    }
    return acc;
  }, {} as Partial<T>);
}

/**
 * Versión más configurable que permite personalizar qué valores excluir
 * @param obj Objeto a limpiar
 * @param options Opciones de configuración
 * @returns Objeto limpio
 */
export function cleanObjectWithOptions<T extends object>(
  obj: T,
  options: {
    removeNull?: boolean;
    removeUndefined?: boolean;
    removeEmptyStrings?: boolean;
    removeEmptyArrays?: boolean;
    removeEmptyObjects?: boolean;
    customValidator?: (value: any) => boolean;
  } = {}
): Partial<T> {
  const {
    removeNull = true,
    removeUndefined = true,
    removeEmptyStrings = true,
    removeEmptyArrays = false,
    removeEmptyObjects = false,
    customValidator
  } = options;

  return Object.entries(obj).reduce((acc, [key, value]) => {
    let shouldRemove = false;

    if (removeNull && value === null) shouldRemove = true;
    else if (removeUndefined && value === undefined) shouldRemove = true;
    else if (removeEmptyStrings && value === '') shouldRemove = true;
    else if (removeEmptyArrays && Array.isArray(value) && value.length === 0) shouldRemove = true;
    else if (removeEmptyObjects && typeof value === 'object' && !Array.isArray(value) && value !== null && Object.keys(value).length === 0) shouldRemove = true;
    else if (customValidator && customValidator(value)) shouldRemove = true;

    if (!shouldRemove) {
      acc[key as keyof T] = value;
    }

    return acc;
  }, {} as Partial<T>);
}

/**
 * Limpia un objeto recursivamente (incluye objetos anidados)
 * @param obj Objeto a limpiar
 * @returns Objeto limpio recursivamente
 */
export function cleanObjectDeep<T extends object>(obj: T): Partial<T> {
  return Object.entries(obj).reduce((acc, [key, value]) => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      const cleanedNested = cleanObjectDeep(value as object);
      if (Object.keys(cleanedNested).length > 0) {
        acc[key as keyof T] = cleanedNested as any;
      }
    }
    else if (Array.isArray(value)) {
      const cleanedArray = value
        .map(item =>
          item && typeof item === 'object' ? cleanObjectDeep(item) : item
        )
        .filter(item =>
          item !== null &&
          item !== undefined &&
          item !== '' &&
          !(typeof item === 'object' && Object.keys(item).length === 0)
        );

      if (cleanedArray.length > 0) {
        acc[key as keyof T] = cleanedArray as any;
      }
    }
    else if (value !== null && value !== undefined && value !== '') {
      acc[key as keyof T] = value;
    }

    return acc;
  }, {} as Partial<T>);
}
