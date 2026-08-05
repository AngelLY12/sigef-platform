import { Component, inject, input, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { RecordListComponent } from '../../../../shared/components/data-display/lists/record-list/record-list.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ParentsApiService } from '../../../../core/api/users/parents.api.service';
import { ParentData, Parents } from '../../models/parents/parents.model';
import { AuthService } from '../../../../core/api/auth/auth.api.service';
import { InviteParent } from '../../models/parents/invite-parent.model';
import { ModalService } from '../../../../core/services/modal.service';
import { EMPTY } from 'rxjs';
import { RelationItemComponent } from '../../components/relation/relation-item/relation-item.component';
import { RelationAvatarComponent } from '../../components/relation/relation-avatar/relation-avatar.component';

@Component({
  selector: 'app-parents',
  standalone: true,
  imports: [
    PageLayoutComponent,
    ButtonComponent,
    FolderTabsComponent,
    RecordListComponent,
    RelationItemComponent,
    RelationAvatarComponent,
  ],
  templateUrl: './parents.component.html',
  styleUrl: './parents.component.scss',
})
export class ParentsComponent implements OnInit {
  private parentsService = inject(ParentsApiService);
  private authService = inject(AuthService);
  private modalService = inject(ModalService);
  parents: Parents | null = null;
  userId: number | null = this.authService.currentUser()?.id || null;
  loading: LoadingState = 'idle';

  ngOnInit(): void {
    this.loadParents();
  }

  loadParents(forceRefresh: boolean = false) {
    this.loading = 'loading';
    this.parentsService.getParents(forceRefresh).subscribe({
      next: (parents) => {
        this.parents = parents;
        this.loading = 'success';
      },
      error: (err) => {
        this.loading = 'error';
      },
    });
  }

  onDeleteRelation(relation: ParentData) {
    this.modalService.openConfirm({
      title: 'Eliminar relación',
      message: `¿Estás seguro de que deseas eliminar la relación con ${relation.fullName}?`,

      confirmVariant: 'danger',
      confirmLabel: 'Eliminar',
      cancelLabel: 'Cancelar',

      onConfirm: () => {
        return this.parentsService.removeParent(relation.id);
      },
      onSuccess: () => {
        this.loadParents(true);

        this.modalService.show({
          message: 'Relación eliminada correctamente.',
          type: 'success',
          display: 'alert',
        });
      },
      onFailure: () => {
        this.modalService.show({
          message: 'No fue posible eliminar la relación.',
          type: 'error',
          display: 'alert',
        });
      },
    });
  }

  onRefreshData() {
    this.loadParents(true);
  }

  get parentsList(): ParentData[] {
    return this.parents?.parentsData ?? [];
  }

  onInviteParentClick() {
    this.modalService.openActions(
      {
        title: 'Invitar padre',
        description:
          'Ingresa el correo electrónico del padre que deseas invitar.',
        entityName: 'usuario',
        fields: [
          {
            name: 'parent_email',
            label: 'Correo electrónico del padre',
            type: 'input',
            inputType: 'email',
            placeHolder: 'example@gmail.com',
            fullWidth: true,
          },
        ],
        onSubmit: (data) => {
          if (!this.userId) {
            this.modalService.show({
              message: 'No se pudo obtener el ID del usuario actual.',
              type: 'error',
            });
            return EMPTY;
          }
          if (!data.parent_email) {
            this.modalService.show({
              message: 'El correo electrónico del padre es requerido.',
              type: 'error',
            });
            return EMPTY;
          }
          const inviteData: InviteParent = {
            student_id: this.userId,
            parent_email: data.parent_email,
          };
          return this.parentsService.inviteParent(inviteData);
        },
        onSuccess: (res: string) => {
          this.modalService.show({
            message: res ?? 'Invitación enviada correctamente',
            type: 'success',
            display: 'alert',
          });
          this.modalService.closeActions();
        },
      },
      [],
    );
  }
}
