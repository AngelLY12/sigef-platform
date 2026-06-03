import { CommonModule } from '@angular/common';
import {
  AfterViewInit,
  Component,
  ElementRef,
  Input,
  OnDestroy,
  ViewChild,
} from '@angular/core';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-expandable-section',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './expandable-section.component.html',
  styleUrl: './expandable-section.component.scss',
})
export class ExpandableSectionComponent implements AfterViewInit, OnDestroy {
  @Input() expanded = false;
  @Input() showToggle = true;
  @Input() showFade = true;
  @Input() maxHeight = 120;
  @Input() expandText = 'Ver más';
  @Input() collapseText = 'Ver menos';

  @ViewChild('contentRef') contentRef!: ElementRef<HTMLDivElement>;
  private resizeObserver?: ResizeObserver;

  contentHeight = 0;
  shouldShowToggle = false;

  ngAfterViewInit() {
    this.resizeObserver = new ResizeObserver(() => {
      requestAnimationFrame(() => {
        this.updateHeight();
      });
    });

    this.resizeObserver.observe(this.contentRef.nativeElement);

    this.updateHeight();
  }

  ngOnDestroy() {
    this.resizeObserver?.disconnect();
  }

  toggle() {
    this.expanded = !this.expanded;
    requestAnimationFrame(() => {
      this.updateHeight();
    });
  }

  private updateHeight() {
    if (!this.contentRef) return;

    const el = this.contentRef.nativeElement;

    this.shouldShowToggle = el.scrollHeight > this.maxHeight;

    if (!this.shouldShowToggle) {
      this.contentHeight = el.scrollHeight;
      return;
    }

    this.contentHeight = this.expanded ? el.scrollHeight : this.maxHeight;
  }
}
