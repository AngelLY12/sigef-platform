import { CommonModule } from '@angular/common';
import { Component, inject, Input, OnInit } from '@angular/core';
import { ModalService } from '../../../../core/services/modal.service';
import { trigger, transition, style, animate } from '@angular/animations';
import { ModalType } from '../../../../core/models/types/modal-error.type';


@Component({
  selector: 'app-notification-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './notification-modal.component.html',
  styleUrl: './notification-modal.component.scss',
  animations: [
    trigger('fadeSlide', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(100px)' }),
        animate('0.3s ease', style({ opacity: 1, transform: 'translateY(0)' }))
      ]),
      transition(':leave', [
        animate('0.5s ease', style({ opacity: 0, transform: 'translateY(100px)' }))
      ])
    ])
  ]
})
export class NotificationModalComponent implements OnInit {
  message: string | null = null;
  errors: string[] | null = null;
  type: ModalType = 'error';
  isVisible = false;
  isClosing = false;
  private modalService = inject(ModalService);

  private iconMap = {
    error: 'close',
    warn: 'warning',
    info: 'info',
    success: 'check'
  };

  private titleMap = {
    error: '¡Algo salió mal!',
    warn: '¡Atención!',
    info: 'Información',
    success: '¡Operación exitosa!'
  };

  ngOnInit() {
    this.modalService.modalData.subscribe(data => {
      if (data && data.display === 'modal') {
        this.message = data.message ?? null;
        this.errors = data.errors ?? null;
        this.type = data.type ?? 'error';
        this.isVisible = true;
        this.isClosing = false;
      } else {
        this.startClose();
      }
    });
  }

  getIcon(): string {
    return this.iconMap[this.type];
  }

  getTitle(): string {
    return this.titleMap[this.type];
  }

  startClose() {
    this.isClosing = true;

    setTimeout(() => {
      this.isVisible = false;
      this.message = null;
      this.errors = null;
      this.isClosing = false;
    }, 500);
  }

  close() {
    this.startClose();
  }
  closeOnBackdrop(event: MouseEvent) {
    if ((event.target as HTMLElement).classList.contains('modal-overlay')) {
      this.close();
    }
  }
}
