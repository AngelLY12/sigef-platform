import { InputType } from "../types/input.type";

type FieldType = 'select' | 'multiselect' | 'checkbox' | 'input' | 'state-selector' | 'group-state-selector';

export interface ActionField {
  type: FieldType;
  name: string;
  label: string;
  inputType?: InputType;
  inputDisabled?: boolean;
  placeHolder?: string;
  options?: SelectOption[];
  groupOptions?: GroupedOption[];
  defaultValue?: any;
  fullWidth?: boolean;
  assigned?: string[];
  isBulkOperation?: boolean;
}

export interface SelectOption {
  label: string;
  value: any;
}

export interface GroupedOption {
  group: string;
  items: SelectOption[];
}
