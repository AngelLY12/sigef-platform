export class SelectionHelper {
  static isSelected<T extends { id: any }>(
    selected: T[],
    item: T
  ): boolean {
    return selected.some(i => i.id === item.id);
  }

  static toggleItem<T extends { id: any }>(
    selected: T[],
    item: T
  ): T[] {
    const exists = selected.some(i => i.id === item.id);

    if (exists) {
      return selected.filter(i => i.id !== item.id);
    }

    return [...selected, item];
  }

  static toggleAll<T>(
    items: T[],
    checked: boolean
  ): T[] {
    return checked ? [...items] : [];
  }

}
