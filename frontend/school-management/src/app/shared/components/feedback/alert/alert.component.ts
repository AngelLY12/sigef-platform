import { CommonModule } from '@angular/common';
import { Component, inject, Input, OnDestroy, OnInit } from '@angular/core';
import { ModalService } from '../../../../core/services/modal.service';
import { Subscription } from 'rxjs';
import { ModalType } from '../../../../core/models/types/modal-error.type';

@Component({
  selector: 'app-alert',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './alert.component.html',
  styleUrl: './alert.component.scss'
})
export class AlertComponent implements OnInit, OnDestroy {
  message: string | null = null;
  visible = false;
  isClosing = false;
  type: ModalType = 'error';
  private timeoutId: any;
  private hideTimeoutId: any;
  private subscription?: Subscription;
  private modalService = inject(ModalService);

  private iconMap: Record<ModalType, string> = {
    error: 'error',
    warn: 'warning',
    info: 'info',
    success: 'check_circle'
  };


 ngOnInit() {
    this.subscription = this.modalService.modalData.subscribe(data => {
      if (data && data.display === 'alert') {
        this.message = data.message ?? null;
        this.type = data.type ?? 'error';
        this.visible = true;

        if (this.timeoutId) clearTimeout(this.timeoutId);
        if (this.hideTimeoutId) clearTimeout(this.hideTimeoutId);

        const duration = 10000 ;

        if (duration > 0) {
          this.timeoutId = setTimeout(() => {
            this.visible = false;

            this.hideTimeoutId = setTimeout(() => {
              this.message = null;
            }, 10000);
          }, duration);
        }
      } else {
        this.visible = false;
        setTimeout(() => {
          this.message = null;
        }, 10000);
      }
    });
  }

  getIcon(): string {
    return this.iconMap[this.type];
  }

  close() {
    this.isClosing = true;
    this.visible = false;

    setTimeout(() => {
      this.message=null;
    }, 10000);
  }

  ngOnDestroy() {
    if (this.timeoutId) clearTimeout(this.timeoutId);
    if (this.hideTimeoutId) clearTimeout(this.hideTimeoutId);
    this.subscription?.unsubscribe();
  }
}
