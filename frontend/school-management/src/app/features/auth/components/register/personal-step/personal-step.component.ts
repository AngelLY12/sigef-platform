import { Component, Input } from '@angular/core';
import { InputComponent } from '../../../../../shared/components/form/controls/input/input.component';
import { FormGroup, ReactiveFormsModule } from '@angular/forms';
import { enumToOptions } from '../../../../../core/utils/enum-helper.utils';
import { BloodType } from '../../../../../core/models/enums/blood-type.enum';
import { Gender } from '../../../../../core/models/enums/gender.enum';
import { SelectComponent } from '../../../../../shared/components/form/controls/select/select.component';

@Component({
  selector: 'app-personal-step',
  standalone: true,
  imports: [ReactiveFormsModule, InputComponent, SelectComponent],
  templateUrl: './personal-step.component.html',
  styleUrl: './personal-step.component.scss',
})
export class PersonalStepComponent {
  @Input({ required: true })
  form!: FormGroup;
  readonly bloodTypeOptions = enumToOptions(BloodType);
  readonly genderOptions = enumToOptions(Gender);
}
