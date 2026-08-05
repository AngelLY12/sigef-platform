import { InputType } from "../../../../../core/models/types/input.type";
import { SelectOption } from "../../../form/controls/select/select-option.config.model";
import { GroupedOption } from "../../../form/selector/group-state-selector-list/grouped-option.config.model";

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
