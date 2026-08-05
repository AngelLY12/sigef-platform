import { Component, inject } from '@angular/core';
import { SelectComponent } from '../../../../../../shared/components/form/controls/select/select.component';
import { InputComponent } from '../../../../../../shared/components/form/controls/input/input.component';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { FormBuilder, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { StepperComponent } from '../../../../../../shared/components/form/layouts/stepper/stepper.component';
import { AddressComponent } from '../../../../../../shared/components/form/sections/address/address.component';
import { createRegisterForm } from '../../../../utils/register-form.utils';
import { ADDRESS_CUSTOM_ERRORS } from '../../../../../auth/config/register.config';
import { enumToOptions } from '../../../../../../core/utils/enum-helper.utils';
import { BloodType } from '../../../../../../core/models/enums/blood-type.enum';
import { Gender } from '../../../../../../core/models/enums/gender.enum';
import { PersonalStepComponent } from '../../../../../auth/components/register/personal-step/personal-step.component';
import { ContactStepComponent } from '../../../../../auth/components/register/contact-step/contact-step.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { cleanObject } from '../../../../../../core/helpers';
import { CreateUserRequest } from '../../../../models/request/create-user-request.model';
import { AdminService } from '../../../../../../core/api/admin/admin.api.service';
import { ModalService } from '../../../../../../core/services/modal.service';

@Component({
  selector: 'app-register-users',
  standalone: true,
  imports: [
    StepperComponent,
    FormsModule,
    ReactiveFormsModule,
    AddressComponent,
    ContactStepComponent,
    PersonalStepComponent,
  ],
  templateUrl: './register-users.component.html',
  styleUrl: './register-users.component.scss',
})
export class RegisterUsersComponent {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);
  private fb = inject(FormBuilder).nonNullable;
  readonly addressCustomErrors = ADDRESS_CUSTOM_ERRORS;
  readonly bloodTypeOptions = enumToOptions(BloodType);
  readonly genderOptions = enumToOptions(Gender);
  steps = ['Información personal', 'Contacto', 'Dirección'];
  currentStep = 0;
  form = createRegisterForm(this.fb);
  loading: LoadingState = 'idle';

  isCurrentStepValid(): boolean {
    const controls = this.form.controls;

    switch (this.currentStep) {
      case 0:
        return [
          controls.name,
          controls.last_name,
          controls.birthdate,
          controls.gender,
          controls.curp,
          controls.blood_type,
        ].every((control) => control.valid);

      case 1:
        return [controls.email, controls.phone_number].every(
          (control) => control.valid,
        );
      case 2:
        return [controls.birthdate, controls.gender, controls.blood_type].every(
          (control) => control.valid,
        );
      case 3:
        return controls.address.valid;

      default:
        return false;
    }
  }

  nextStep() {
    this.currentStep++;
  }

  prevStep() {
    this.currentStep--;
  }

  submit() {
    if (this.form.invalid) return;

    this.loading = 'loading';

    const { address, ...rest } = this.form.getRawValue();

    const cleanedRest = cleanObject(rest);

    const user: CreateUserRequest = {
      ...cleanedRest,
      address: address,
      status: 'activo',
    } as CreateUserRequest;

    this.adminService.createUser(user).subscribe({
      next: (res: string) => {
        this.loading = 'success';
        this.modalService.show({
          message: res ?? 'Usuario creado correctamente',
          type: 'success',
          display: 'alert',
        });
        this.modalService.closeCustom(this.loading);
      },
      error: (err) => {
        this.loading = 'error';
        this.modalService.closeCustom(this.loading);
      },
    });
  }
}
