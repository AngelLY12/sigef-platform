import { CommonModule } from '@angular/common';
import {
  Component,
  inject,
  OnInit,
  TemplateRef,
  ViewChild,
} from '@angular/core';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ProfileService } from '../../../../core/api/profile.api.service';
import { UserProfile } from '../../models/user-profile.model';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { InfoCardItemComponent } from '../../../../shared/components/data-display/info-card-item/info-card-item.component';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import {
  AlertItem,
  AlertsListComponent,
} from '../../../../shared/components/feedback/alerts-list/alerts-list.component';
import { Status } from '../../../../core/models/enums/status.enum';
import { ModalService } from '../../../../core/services/modal.service';
import { AuthService } from '../../../../core/api/auth.api.service';
import { EditProfileParams } from '../../models/edit-profile-params.model';
import { buildDiffPayload } from '../../../../core/utils/normalize-helper.utils';
import { BloodType } from '../../../../core/models/enums/blood-type.enum';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { Gender } from '../../../../core/models/enums/gender.enum';
import { AddressComponent } from '../../../../shared/components/features/address/address.component';
import { EditAddressComponent } from '../../components/edit-address/edit-address.component';

@Component({
  selector: 'app-profile-view',
  imports: [
    CommonModule,
    ButtonComponent,
    InfoCardComponent,
    InfoCardItemComponent,
    PageLayoutComponent,
    AlertsListComponent,
  ],
  templateUrl: './profile-view.component.html',
  styleUrl: './profile-view.component.scss',
})
export class ProfileViewComponent implements OnInit {
  private authService = inject(AuthService);
  private profileService = inject(ProfileService);
  private modalService = inject(ModalService);
  profile: UserProfile | null = null;
  currentName = this.authService.currentUser()?.fullName;
  state: LoadingState = 'success';
  emailState: LoadingState = 'idle';

  @ViewChild('headerActions') headerActions!: TemplateRef<any>;
  @ViewChild('headerBottom') headerBottom!: TemplateRef<any>;

  ngOnInit(): void {
    this.loadProfile();
  }

  loadProfile() {
    this.state = 'loading';

    this.profileService.profile().subscribe({
      next: (response) => {
        this.profile = response.data.user;
        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }

  editContact() {
    this.modalService.openActions(
      {
        title: 'Actualizar contacto',
        description: 'Manten al día tu información',
        fields: [
          {
            name: 'name',
            type: 'input',
            label: 'Nombre',
            defaultValue: this.profile?.name ?? null,
          },
          {
            name: 'last_name',
            type: 'input',
            label: 'Apellido',
            defaultValue: this.profile?.last_name ?? null,
          },
          {
            name: 'email',
            type: 'input',
            inputType: 'email',
            label: 'Correo',
            defaultValue: this.profile?.email ?? null,
          },
          {
            name: 'phone_number',
            type: 'input',
            inputType: 'text',
            label: 'Número de telefono',
            defaultValue: this.profile?.phone_number ?? null,
          },
        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(this.profile!, data);
          console.log(JSON.stringify(payload, null, 2));
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          this.loadProfile();

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

  editPersonal() {
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
            defaultValue: this.profile?.birthdate ?? null,
          },
          {
            name: 'gender',
            type: 'select',
            options: enumToOptions(Gender),
            label: 'Genero',
            defaultValue: this.profile?.gender ?? null,
          },
          {
            name: 'blood_type',
            type: 'select',
            options: enumToOptions(BloodType),
            label: 'Tipo de sangre',
            defaultValue: this.profile?.blood_type ?? null,
          },

        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(this.profile!, data);
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          this.loadProfile();

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

  editAddress() {
    this.modalService.openCustom({
      title: 'Actualizar dirección',
      component: EditAddressComponent,
      data: {
        userAddress: this.profile?.address,
        onSuccess: () => this.loadProfile()
      },
    });
  }

  changePassword() {
    this.modalService.openActions(
      {
        title: 'Actualizar contraseña',
        fields: [
          {
            name: 'currentPassword',
            type: 'input',
            inputType: 'password',
            label: 'Contraseña',
            placeHolder: '**********'
          },
          {
            name: 'newPassword',
            type: 'input',
            inputType: 'password',
            label: 'Contraseña',
            placeHolder: '**********'

          },

        ],
        onSubmit: (data) => {
          const payload: Partial<EditProfileParams> = buildDiffPayload(this.profile!, data);
          return this.profileService.editProfile(payload);
        },

        onSuccess: (message) => {
          this.loadProfile();

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

  verifyEmail() {
    this.emailState = 'loading';
    this.authService.verifyEmail().subscribe({
      next: () => {
        this.modalService.show({
          message: 'Se ha enviado el correo de verificación',
          type: 'success',
          display: 'alert',
        });
        this.emailState = 'success';
      },
      error: () => (this.emailState = 'error'),
    });
  }

  get contactItems() {
    return [
      {
        icon: 'email',
        label: 'Correo electrónico',
        value: this.profile?.email || 'No disponible',
      },
      {
        icon: 'phone',
        label: 'Teléfono',
        value: this.profile?.phone_number || 'No disponible',
      },
    ];
  }

  get personalItems() {
    return [
      {
        icon: 'fingerprint',
        label: 'CURP',
        value: this.profile?.curp || 'No disponible',
      },
      {
        icon: 'date_range',
        label: 'Fecha de nacimiento',
        value: this.profile?.birthdate || 'No disponible',
      },
      {
        icon: 'wc',
        label: 'Género',
        value: this.profile?.gender || 'No disponible',
      },
      {
        icon: 'opacity',
        label: 'Tipo de sangre',
        value: this.profile?.blood_type || 'No disponible',
      },
    ];
  }

  get addressItems() {
    return [
      {
        icon: 'markunread_mailbox',
        label: 'Código Postal',
        value: this.profile?.address?.cp || 'No disponible',
      },
      {
        icon: 'map',
        label: 'Estado',
        value: this.profile?.address?.state || 'No disponible',
      },
      {
        icon: 'location_city',
        label: 'Municipio',
        value: this.profile?.address?.city || 'No disponible',
      },
      {
        icon: 'apartment',
        label: 'Colonia',
        value: this.profile?.address?.neighborhood || 'No disponible',
      },
      {
        icon: 'route',
        label: 'Calle',
        value: this.profile?.address?.street || 'No disponible',
      },
      {
        icon: 'home',
        label: 'Número',
        value: this.profile?.address?.number || 'No disponible',
      },
    ];
  }

  get accountItems() {
    return [
      {
        icon: 'info',
        label: 'Estatus',
        value: this.profile?.status || 'No disponible',
      },
      {
        icon: 'event',
        label: 'Fecha de registro',
        value: this.profile?.registration_date || 'No disponible',
      },
      {
        icon: 'verified',
        label: 'Verificación de Email',
        value: this.profile?.emailVerifiedAt || 'No disponible',
      },
      {
        icon: 'credit_card',
        label: 'ID Stripe',
        value: this.profile?.stripe_customer_id || 'No disponible',
      },
    ];
  }

  get accountAlerts(): AlertItem[] {
    if (this.profile?.emailVerifiedAt && this.profile?.status === Status.ACTIVO)
      return [];
    const alerts = [];
    if (!this.profile?.emailVerifiedAt) {
      alerts.push({
        icon: 'verified',
        title: 'Usuario sin verificar',
        count: null,
      });
    }
    if (this.profile?.status !== Status.ACTIVO) {
      alerts.push({
        icon: 'block',
        title: 'Usuario inactivo',
        count: null,
      });
    }

    return alerts;
  }
}
