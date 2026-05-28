import { ConceptAppliesTo } from '../../../../core/models/enums/applies-to-concepts.enum';
import { InputType } from '../../../../core/models/types/input.type';

export type FormItem =
  | {
      type: 'input';
      icon: string;
      label: string;
      controlName: string;
      inputType: InputType;
    }
  | {
      type: 'select';
      icon: string;
      label: string;
      controlName: string;
      options: { label: string; value: any }[];
    }
  | {
      type: 'multiselect';
      icon: string;
      label: string;
      controlName: string;
      options: { label: string; value: any }[];
      allowExceptions: boolean;
      showWhen?: ConceptAppliesTo[];
    }
  | {
      type: 'checkbox';
      label: string;
    };
