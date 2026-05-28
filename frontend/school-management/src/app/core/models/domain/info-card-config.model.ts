import { TemplateRef } from '@angular/core';

export interface InfoCardActionConfig {
  singleButton?: {
    label?: string;
    icon?: string;
    loading?: boolean;
    disabled?: boolean;
    onClick?: () => void;
  };

  listActionsTemplate?: TemplateRef<any>;
}
