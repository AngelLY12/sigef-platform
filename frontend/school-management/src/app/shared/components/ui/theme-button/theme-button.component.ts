import { CommonModule } from '@angular/common';
import { Component, inject, Input } from '@angular/core';
import { ButtonComponent } from '../button/button.component';
import { ThemeService } from '../../../../core/services/theme.service';
import { ButtonVariant } from '../../../../core/models/types/button/btn-variant.type';
import { ButtonSize } from '../../../../core/models/types/button/btn-size.type';
import { MenuItemComponent } from '../../navigation/menu-item/menu-item.component';

@Component({
  selector: 'app-theme-button',
  imports: [CommonModule, ButtonComponent, MenuItemComponent],
  templateUrl: './theme-button.component.html',
  styleUrl: './theme-button.component.scss'
})
export class ThemeButtonComponent {
  @Input() variant: ButtonVariant = 'ghost';
  @Input() size: ButtonSize = 'sm';
  @Input() fullWidth: boolean= false;
  @Input() iconOnly: boolean= true;
  @Input() textButton:string = '';
  @Input() isInMenu?: boolean = false;
  private themeService = inject(ThemeService);

  theme$ = this.themeService.theme;

  toggleTheme() {
    this.themeService.toggleTheme();
  }



}
