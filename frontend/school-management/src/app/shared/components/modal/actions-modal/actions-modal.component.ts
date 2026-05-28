import { LoadingState } from './../../../../core/models/types/loading-state.type';
import { CommonModule } from '@angular/common';
import { Component, HostListener, inject, OnInit } from '@angular/core';
import { ActionModalConfig } from '../../../../core/models/domain/action-modal-config.model';
import { InputComponent } from '../../form/input/input.component';
import { SelectComponent } from '../../form/select/select.component';
import { CheckboxComponent } from '../../form/checkbox/checkbox.component';
import { FormsModule } from '@angular/forms';
import { ButtonComponent } from '../../ui/button/button.component';
import { ModalService } from '../../../../core/services/modal.service';
import { BaseModalComponent } from '../base-modal/base-modal.component';
import { SelectorActionState } from '../../../../core/models/types/permissions-state.type';
import { StateSelectorListComponent } from '../../form/selector/state-selector-list/state-selector-list.component';
import { GroupStateSelectorListComponent } from '../../form/selector/group-state-selector-list/group-state-selector-list.component';

@Component({
  selector: 'app-actions-modal',
  standalone: true,
  imports: [
    CommonModule,
    StateSelectorListComponent,
    GroupStateSelectorListComponent,
    BaseModalComponent,
    InputComponent,
    SelectComponent,
    CheckboxComponent,
    ButtonComponent,
    FormsModule,
  ],
  templateUrl: './actions-modal.component.html',
  styleUrl: './actions-modal.component.scss',
})
export class ActionsModalComponent implements OnInit {
  private modalService = inject(ModalService);
  config!: ActionModalConfig;
  models: any[] = [];

  isVisible = false;
  isMobile = window.innerWidth <= 768;
  actionState: LoadingState = 'idle';

  formData: Record<string, any> = {};

  ngOnInit() {
    this.modalService.actionsModalData.subscribe((data) => {
      if (data) {
        this.config = data.config;
        this.models = data.models;
        this.isVisible = true;
        this.initForm();
      } else {
        this.isVisible = false;
      }
    });
  }

  initForm() {
    this.formData = {};
    this.config.fields.forEach((field) => {
      switch (field.type) {
        case 'multiselect':
          this.formData[field.name] = field.defaultValue ?? [];
          break;

        case 'checkbox':
          this.formData[field.name] = field.defaultValue ?? false;
          break;
        case 'state-selector':
          this.formData[field.name] = field.defaultValue ?? {};
          break;

        default:
          this.formData[field.name] = field.defaultValue ?? null;
      }
    });
  }

  isChecked(field: string, value: any): boolean {
    return this.formData[field]?.includes(value);
  }
  toSet(arr?: string[]): Set<string> {
    return new Set(arr ?? []);
  }

  onCheckboxChange(field: string, value: any, checked: boolean) {
    if (!this.formData[field]) {
      this.formData[field] = [];
    }

    if (checked) {
      if (!this.formData[field].includes(value)) {
        this.formData[field].push(value);
      }
    } else {
      this.formData[field] = this.formData[field].filter(
        (v: any) => v !== value,
      );
    }
  }

  onStateSelectorChange(
    fieldName: string,
    event: { value: string; state: SelectorActionState },
  ) {
    const current: Record<string, SelectorActionState> = {
      ...(this.formData[fieldName] || {}),
    };

    current[event.value] = event.state;

    this.formData[fieldName] = current;
  }

  submit() {
    const payload = {
      ...this.formData,
      models: this.models,
    };
    this.actionState = 'loading';
    const result = this.config.onSubmit(payload);

    if (result?.subscribe) {
      result.subscribe({
        next: (res) => {
          this.actionState = 'success';
          this.close();
          if (this.config.onSuccess) {
            this.config.onSuccess(res);
          }
        },
        error: (err) => {
          this.actionState = 'error';
          if (this.config.onFailure) {
            this.config.onFailure(err);
          }
        },
      });
    } else {
      this.actionState = 'idle';
      this.close();
    }
  }

  close() {
    this.modalService.closeActions();
  }

  @HostListener('window:resize')
  onResize() {
    this.isMobile = window.innerWidth <= 768;
  }
}
