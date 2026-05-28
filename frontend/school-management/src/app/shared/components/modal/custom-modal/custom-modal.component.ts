import { CommonModule } from '@angular/common';
import {
  Component,
  HostListener,
  inject,
  OnInit,
  ViewChild,
  ViewContainerRef,
} from '@angular/core';
import { ModalService } from '../../../../core/services/modal.service';
import { BaseModalComponent } from '../base-modal/base-modal.component';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-custom-modal',
  standalone: true,
  imports: [CommonModule, BaseModalComponent, ButtonComponent],
  templateUrl: './custom-modal.component.html',
  styleUrl: './custom-modal.component.scss',
})
export class CustomModalComponent implements OnInit {
  private modalService = inject(ModalService);

  @ViewChild('container', { read: ViewContainerRef })
  container!: ViewContainerRef;

  isVisible = false;
  title?: string;
  isMobile = window.innerWidth <= 768;

  ngOnInit() {
    this.modalService.customModalData.subscribe((config) => {
      if (config) {
        this.isVisible = true;
        this.title = config.title;

        setTimeout(() => {
          this.container.clear();

          const componentRef = this.container.createComponent<any>(
            config.component,
          );

          if (config.data) {
            Object.entries(config.data).forEach(([key, value]) => {
              componentRef.setInput(key, value);
            });
          }

          componentRef.changeDetectorRef.detectChanges();
        });
      } else {
        this.isVisible = false;
        this.container?.clear();
      }
    });
  }

  close() {
    this.modalService.closeCustom();
  }

  @HostListener('window:resize')
  onResize() {
    this.isMobile = window.innerWidth <= 768;
  }
}
