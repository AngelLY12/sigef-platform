import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { QueryParamsHelper } from './query-params-helper.utils';

export class SearchHelper {
  static initSearch<T>({
    searchSubject,
    getParams,
    update,
    changeSearch,
    debounce = 500,
  }: {
    searchSubject: Subject<string>;
    getParams: () => T;
    update: (params: T) => void;
    changeSearch: (params: T, value: string) => T;
    debounce?: number;
  }) {
    searchSubject
      .pipe(debounceTime(debounce), distinctUntilChanged())
      .subscribe((value) => {
        const updatedParams = changeSearch(getParams(), value);

        update(updatedParams);
      });
  }
}
