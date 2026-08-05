import { TemplateRef } from '@angular/core';

export type InfoCardType = 'academic' | 'alerts' | 'demographics'

export interface InfoCardConfig {
  icon: string;
  title: string;
  type?: InfoCardType;
  loading?: boolean;
  defaultExpanded?: boolean;

}

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
