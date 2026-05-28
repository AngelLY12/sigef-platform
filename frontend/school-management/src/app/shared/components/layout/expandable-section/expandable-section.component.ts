import { CommonModule } from '@angular/common';
import {
  AfterViewInit,
  Component,
  ElementRef,
  Input,
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
export class ExpandableSectionComponent implements AfterViewInit {
  @Input() expanded = false;
  @Input() showToggle = true;
  @Input() showFade = true;
  @Input() maxHeight = 120;
  @Input() expandText = 'Ver más';
  @Input() collapseText = 'Ver menos';

  @ViewChild('contentRef') contentRef!: ElementRef<HTMLDivElement>;

  contentHeight = 0;
  shouldShowToggle = false;

  ngAfterViewInit(): void {
    requestAnimationFrame(() => {
      this.updateHeight();
    });
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

    this.contentHeight = this.expanded ? el.scrollHeight : this.maxHeight;
  }
}
