import { CommonModule } from '@angular/common';
import {
  Component,
  ContentChild,
  EventEmitter,
  Input,
  Output,
  TemplateRef,
} from '@angular/core';

@Component({
  selector: 'app-dashboard-header',
  imports: [CommonModule],
  templateUrl: './dashboard-header.component.html',
  styleUrl: './dashboard-header.component.scss',
})
export class DashboardHeaderComponent {
  @Input() title!: string;
  @Input() icon: string = '';
  @Input() iconSize: 'sm' | 'md' | 'lg' | 'xl' = 'lg';
  @Input() welcomeMessage!: string;
  @Input() date: Date = new Date();
  @Input() showAlertBadge = false;
  @Input() alertCount = 0;
  @Input() hasHeaderBottomContent: boolean = false;
  @Input() iconClickable = false;
  @Output() iconAction = new EventEmitter<void>();

  onClickAction() {
    if (this.iconAction.observed) {
      this.iconAction.emit();
    }
  }
}
