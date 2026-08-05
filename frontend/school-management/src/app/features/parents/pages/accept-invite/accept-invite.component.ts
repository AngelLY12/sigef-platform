import { Component, inject, OnInit } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { ActivatedRoute, Router } from '@angular/router';
import { ModalService } from '../../../../core/services/modal.service';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { RelationshipType } from '../../../../core/models/enums/relationship-type.enum';
import { EMPTY } from 'rxjs';
import { ParentsApiService } from '../../../../core/api/users/parents.api.service';
import { AcceptInvitation } from '../../../client/models/parents/accept-invitation.model';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';

@Component({
  selector: 'app-accept-invite',
  standalone: true,
  imports: [PublicLayoutComponent],
  templateUrl: './accept-invite.component.html',
  styleUrl: './accept-invite.component.scss',
})
export class AcceptInviteComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private modalService = inject(ModalService);
  private parentsService = inject(ParentsApiService);
  token: string | null = null;

  ngOnInit(): void {
    this.token = this.route.snapshot.queryParamMap.get('token');
    if (!this.token) {
      this.router.navigate([NAVIGATION.common.notFound]);
      return;
    }
  }

  onAcceptInviteClick() {
    this.modalService.openActions(
      {
        title: 'Aceptar invitación',
        description:
          '¿Deseas aceptar la invitación para unirte a la cuenta de tu hijo?',
        entityName: 'invitación',
        fields: [
          {
            name: 'relationship',
            label: 'Relación con el estudiante',
            type: 'select',
            options: enumToOptions(RelationshipType),
            fullWidth: true,
          },
        ],
        onSubmit: (data) => {
          if (!data.relationship) {
            this.modalService.show({
              message: 'Debes elegir una opcion de relación',
              type: 'warn',
              display: 'modal',
            });
            return EMPTY;
          }

          const request: AcceptInvitation = {
            token: this.token!,
            relationship: data.relationship,
          };

          return this.parentsService.acceptInvitation(request);
        },
        onSuccess: (data) => {
          this.modalService.show({
            message: data,
            type: 'success',
            display: 'alert',
          });
          this.router.navigate([NAVIGATION.auth.login]);

        },
      },
      [],
    );
  }
}
