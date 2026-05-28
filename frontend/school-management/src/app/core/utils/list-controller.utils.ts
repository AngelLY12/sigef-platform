export class ListController<T> {
  constructor(
    private getParams: () => T,
    private setParams: (params: T) => void,
    private loadFn: () => void
  ) {}

  update(params: T) {
    this.setParams(params);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    this.loadFn();
  }
}
