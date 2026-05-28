import { Pipe, PipeTransform } from "@angular/core";

@Pipe({ name: 'currencyMXN', standalone:true })
export class CurrencyMXNPipe implements PipeTransform {
  transform(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
      return '$0.00 MXN';
    }

    const numericValue = Number(value);

    if (isNaN(numericValue)) {
      return '$0.00 MXN';
    }

    return `$${numericValue.toFixed(2)} MXN`;
  }
}
