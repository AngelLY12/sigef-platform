import { Component, HostListener, inject, OnInit } from '@angular/core';
import { ButtonComponent } from '../../ui/button/button.component';
import { BaseModalComponent } from '../base-modal/base-modal.component';
import { CommonModule } from '@angular/common';
import { ModalService } from '../../../../core/services/modal.service';
import { ConfirmModalConfig } from '../../../../core/models/domain/confirm-moda-config.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-confirm-modal',
  imports: [CommonModule, BaseModalComponent, ButtonComponent],
  templateUrl: './confirm-modal.component.html',
  styleUrl: './confirm-modal.component.scss',
})
export class ConfirmModalComponent implements OnInit {
  private modalService = inject(ModalService);

  config!: ConfirmModalConfig;

  isVisible = false;
  isMobile = window.innerWidth <= 768;

  actionState: LoadingState = 'idle';

  ngOnInit() {
    this.modalService.confirmModalData.subscribe((config) => {
      if (config) {
        this.config = config;
        this.isVisible = true;
      } else {
        this.isVisible = false;
      }
    });
  }

  confirm() {
    this.actionState = 'loading';

    const result = this.config.onConfirm();

    if (result?.subscribe) {
      result.subscribe({
        next: (res) => {
          this.actionState = 'success';

          this.close();

          this.config.onSuccess?.(res);
        },
        error: (err) => {
          this.actionState = 'error';

          this.config.onFailure?.(err);
        },
      });

      return;
    }

    this.actionState = 'idle';
    this.close();
  }

  close() {
    this.modalService.closeConfirm();
  }

  @HostListener('window:resize')
  onResize() {
    this.isMobile = window.innerWidth <= 768;
  }
}
