import { CommonModule } from '@angular/common';
import { Component, inject, Input, OnDestroy, OnInit } from '@angular/core';
import { InputComponent } from '../../form/input/input.component';
import { SelectComponent } from '../../form/select/select.component';
import { FormControl, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { SepoMexService } from '../../../../core/services/sepomex.service';
import { catchError, debounceTime, filter, of, Subscription, switchMap } from 'rxjs';
import { SpinnerComponent } from '../../ui/spinner/spinner.component';

@Component({
  selector: 'app-address',
  imports: [CommonModule, ReactiveFormsModule, SpinnerComponent,InputComponent, SelectComponent],
  templateUrl: './address.component.html',
  styleUrl: './address.component.scss'
})
export class AddressComponent implements OnInit, OnDestroy {
  @Input() control!: FormGroup;
  @Input() customErrors: { [key: string]: { [key: string]: string } } = {};
  loading = false;
  neighborhoods: string[] = [];
  neighborhoodOptions: { label: string; value: string }[] = [];

  private sepoMexService = inject(SepoMexService);
  private subscription?: Subscription;

  get cpControl(): FormControl {
    return this.control.get('cp') as FormControl;
  }

  get streetControl(): FormControl {
    return this.control.get('street') as FormControl;
  }

  get numberControl(): FormControl {
    return this.control.get('number') as FormControl;
  }

  get neighborhoodControl(): FormControl {
    return this.control.get('neighborhood') as FormControl;
  }

  get stateControl(): FormControl {
    return this.control.get('state') as FormControl;
  }

  get cityControl(): FormControl {
    return this.control.get('city') as FormControl;
  }

  ngOnInit() {
    this.subscription = this.cpControl.valueChanges
      .pipe(
        debounceTime(500),
        filter(cp => cp?.length === 5 && /^\d+$/.test(cp)),
        switchMap(cp => {
          this.loading = true;
          return this.sepoMexService.searchByCP(cp).pipe(
            catchError(error => {
              console.error('Error al buscar CP:', error);
              this.loading = false;
              return of(null);
            })
          );
        })
      )
      .subscribe(response => {
        this.loading = false;

        if (response?.response) {
          const data = response.response;

          this.control.patchValue({
            state: data.estado || '',
            city: data.municipio || '',
            neighborhood: data.asentamiento || ''
          }, { emitEvent: false });

          this.neighborhoods = data.asentamientos || [];

          this.neighborhoodOptions = this.neighborhoods.map(n => ({
            label: n,
            value: n
          }));

        } else {
          this.control.patchValue({
            state: '',
            city: '',
            neighborhood: ''
          }, { emitEvent: false });
          this.neighborhoods = [];
          this.neighborhoodOptions = [];
        }
      });
  }

  ngOnDestroy() {
    this.subscription?.unsubscribe();
  }

  getFieldErrors(fieldName: string): { [key: string]: string } {
    return this.customErrors[fieldName] || {};
  }


}
