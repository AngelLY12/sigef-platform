import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { DisplayType } from '../models/types/display-type.type';
import { ModalType } from '../models/types/modal-error.type';
import { ActionModalConfig } from '../models/domain/action-modal-config.model';

@Injectable({ providedIn: 'root' })
export class ModalService {
  private closeCallback?: (result?: any) => void;
  public modalData = new BehaviorSubject<{
    message?: string;
    errors?: string[];
    type?: ModalType;
    display?: DisplayType;
  } | null>(null);
  public actionsModalData = new BehaviorSubject<{
    config: ActionModalConfig;
    models: any[];
  } | null>(null);

  public customModalData = new BehaviorSubject<{
    title?: string;
    component: any;
    data?: any;
  } | null>(null);

  show(data: {
    message?: string;
    errors?: string[];
    type?: ModalType;
    display?: DisplayType;
  }) {
    this.modalData.next({ ...data, display: data.display ?? 'modal' });
  }

  close() {
    this.modalData.next(null);
  }

  openActions(config: ActionModalConfig, models: any[]) {
    this.actionsModalData.next({ config, models });
  }

  closeActions() {
    this.actionsModalData.next(null);
  }

  openCustom(config: {
    title?: string;
    component: any;
    data?: any;
    onClose?: (result?: any) => void;
  }) {
    this.closeCallback = config.onClose;

    this.customModalData.next(config);
  }

  closeCustom(result?: any) {
    this.closeCallback?.(result);

    this.closeCallback = undefined;

    this.customModalData.next(null);
  }
}
