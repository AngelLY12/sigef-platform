import { UserProfile } from './../models/user-profile.model';
import { inject, Injectable } from '@angular/core';
import { ModalService } from '../../../core/services/modal.service';
import { ProfileService } from '../../../core/api/users/profile.api.service';
import { EditProfileParams } from '../models/edit-profile-params.model';
import { buildDiffPayload } from '../../../core/utils/normalize-helper.utils';
import { enumToOptions } from '../../../core/utils/enum-helper.utils';
import { BloodType } from '../../../core/models/enums/blood-type.enum';
import { Gender } from '../../../core/models/enums/gender.enum';

@Injectable({ providedIn: 'root' })
export class ProfileActionsService {
  private modalService = inject(ModalService);
  private profileService = inject(ProfileService);

  editContact(profile: UserProfile, onSuccess: () => void) {
    this.modalService.openActions(
      {
        title: 'Actualizar contacto',
        description: 'Manten al día tu información',
        fields: [
          {
            name: 'name',
            type: 'input',
            label: 'Nombre',
            defaultValue: profile.name ?? null,
          },
          {
            name: 'last_name',
            type: 'input',
            label: 'Apellido',
            defaultValue: profile.last_name ?? null,
          },
          {
            name: 'email',
            type: 'input',
            inputType: 'email',
            label: 'Correo',
            defaultValue: profile.email ?? null,
          },
          {
            name: 'phone_number',
            type: 'input',
            inputType: 'text',
            label: 'Número de telefono',
            defaultValue: profile.phone_number ?? null,
          },
        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(
            profile,
            data,
          );
          console.log(JSON.stringify(payload, null, 2));
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          onSuccess();

          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });
        },

        onFailure: () => {},
      },
      [],
    );
  }

  editPersonal(profile: UserProfile, onSuccess: () => void) {
    this.modalService.openActions(
      {
        title: 'Actualizar información personal',
        description: 'Manten al día tu información',
        fields: [
          {
            name: 'birthdate',
            type: 'input',
            inputType: 'date',
            label: 'Fecha de nacimiento',
            defaultValue: profile.birthdate ?? null,
          },
          {
            name: 'gender',
            type: 'select',
            options: enumToOptions(Gender),
            label: 'Genero',
            defaultValue: profile.gender ?? null,
          },
          {
            name: 'blood_type',
            type: 'select',
            options: enumToOptions(BloodType),
            label: 'Tipo de sangre',
            defaultValue: profile.blood_type ?? null,
          },
        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(
            profile,
            data,
          );
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          onSuccess();

          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });
        },

        onFailure: () => {},
      },
      [],
    );
  }

  changePassword(profile: UserProfile, onSuccess: () => void) {
    this.modalService.openActions(
      {
        title: 'Actualizar contraseña',
        fields: [
          {
            name: 'currentPassword',
            type: 'input',
            inputType: 'password',
            label: 'Contraseña',
            placeHolder: '**********',
          },
          {
            name: 'newPassword',
            type: 'input',
            inputType: 'password',
            label: 'Contraseña',
            placeHolder: '**********',
          },
        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(
            profile,
            data,
          );
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          onSuccess();

          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });
        },

        onFailure: () => {},
      },
      [],
    );
  }
}
