import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { RouterModule } from '@angular/router';
import { SpinnerComponent } from '../spinner/spinner.component';
import { ButtonType } from '../../../../core/models/types/button/btn-type.type';
import { ButtonVariant } from '../../../../core/models/types/button/btn-variant.type';
import { ButtonSize } from '../../../../core/models/types/button/btn-size.type';

@Component({
  selector: 'app-button',
  standalone: true,
  imports: [CommonModule, RouterModule, SpinnerComponent],
  templateUrl: './button.component.html',
  styleUrl: './button.component.scss'
})
export class ButtonComponent {
  @Input() type: ButtonType = 'button';
  @Input() variant: ButtonVariant = 'primary';
  @Input() size: ButtonSize = 'md';

  @Input() loading = false;
  @Input() disabled = false;
  @Input() fullWidth = false;
  @Input() iconOnly: boolean = false;

  @Input() iconLeft?: string;
  @Input() iconRight?: string;

  @Input() routerLink?: string;

  get isDisabled(): boolean {
    return this.disabled || this.loading;
  }

}
