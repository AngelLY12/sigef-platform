import { CommonModule } from '@angular/common';
import {
  Component,
  Input,
  Output,
  EventEmitter,
  HostListener,
  AfterViewInit,
  ViewChild,
  ElementRef,
  ChangeDetectionStrategy,
  OnInit,
} from '@angular/core';
import { ButtonComponent } from '../../../ui/button/button.component';
import { SpinnerComponent } from '../../../ui/spinner/spinner.component';
import { InfoCardActionConfig, InfoCardConfig } from './info-card-config.model';

@Component({
  selector: 'app-info-card',
  standalone: true,
  imports: [CommonModule, ButtonComponent, SpinnerComponent],
  templateUrl: './info-card.component.html',
  styleUrl: './info-card.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class InfoCardComponent implements OnInit, AfterViewInit {
  @Input({ required: true }) item!: InfoCardConfig;
  @Input() actionConfig: InfoCardActionConfig | null = null;

  @Output() action = new EventEmitter<void>();
  isMobile = window.innerWidth <= 768;
  @ViewChild('contentRef', { static: false })
  contentRef?: ElementRef<HTMLDivElement>;

  expanded = true;
  contentHeight: number | null = null;

  ngOnInit(): void {
    this.expanded = this.item.defaultExpanded ?? false;
  }

  ngAfterViewInit(): void {
    requestAnimationFrame(() => {
      this.updateHeight();
    });
  }

  toggleExpand() {
    this.expanded = !this.expanded;

    requestAnimationFrame(() => {
      this.updateHeight();
    });
  }

  @HostListener('window:resize')
  onResize() {
    const next = window.innerWidth <= 768;

    if (next !== this.isMobile) {
      this.isMobile = next;

      requestAnimationFrame(() => {
        this.updateHeight();
      });
    }
  }

  private updateHeight() {
    if (!this.contentRef?.nativeElement) return;

    const el = this.contentRef.nativeElement;

    this.contentHeight = this.expanded ? el.scrollHeight : 0;
  }
}
