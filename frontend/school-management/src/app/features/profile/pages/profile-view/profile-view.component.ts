import { CommonModule } from '@angular/common';
import {
  Component,
  inject,
  OnInit,
} from '@angular/core';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ProfileService } from '../../../../core/api/users/profile.api.service';
import { UserProfile } from '../../models/user-profile.model';
import { InfoCardItemComponent } from '../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import {
  AlertItem,
  AlertsListComponent,
} from '../../../../shared/components/feedback/alerts-list/alerts-list.component';
import { ModalService } from '../../../../core/services/modal.service';
import { AuthService } from '../../../../core/api/auth/auth.api.service';
import { EditAddressComponent } from '../../components/edit-address/edit-address.component';
import { FolderTab } from '../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { InfoCardItemConfig } from '../../../../shared/components/data-display/cards/info-card-item/info-card-item-config.model';
import { PROFILE_TABS } from '../../config/profile.config';
import { ProfileActionsService } from '../../services/profile-actions.service';
import {
  getAccountAlerts,
  getAccountItems,
  getAddressItems,
  getContactItems,
  getPersonalItems,
} from '../../helpers/profile.mapper';

@Component({
  selector: 'app-profile-view',
  imports: [
    CommonModule,
    ButtonComponent,
    InfoCardItemComponent,
    PageLayoutComponent,
    AlertsListComponent,
    FolderTabsComponent,
  ],
  templateUrl: './profile-view.component.html',
  styleUrl: './profile-view.component.scss',
})
export class ProfileViewComponent implements OnInit {
  private authService = inject(AuthService);
  private profileService = inject(ProfileService);
  private modalService = inject(ModalService);
  private profileActions = inject(ProfileActionsService);
  private listParams!: ListController<boolean>;
  refreshProfile: boolean = false;
  profile: UserProfile | null = null;
  currentName = this.authService.currentUser()?.fullName;
  state: LoadingState = 'success';
  emailState: LoadingState = 'idle';

  ngOnInit(): void {
    this.listParams = new ListController<boolean>(
      () => this.refreshProfile,
      (params) => (this.refreshProfile = params),
      () => this.loadProfile(),
    );
    this.loadProfile();
  }

  loadProfile() {
    this.state = 'loading';

    this.profileService.profile(this.refreshProfile).subscribe({
      next: (response) => {
        this.profile = response.data.user;
        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }

  onRefreshData() {
    this.listParams.update(true);
  }

  editContact() {
    this.profileActions.editContact(this.profile!, () => this.loadProfile());
  }

  editPersonal() {
    this.profileActions.editPersonal(this.profile!, () => this.loadProfile());
  }

  editAddress() {
    this.modalService.openCustom({
      title: 'Actualizar dirección',
      component: EditAddressComponent,
      data: {
        userAddress: this.profile?.address,
        onSuccess: () => this.loadProfile(),
      },
    });
  }

  changePassword() {
    this.profileActions.changePassword(this.profile!, () => this.loadProfile());
  }

  verifyEmail() {
    this.emailState = 'loading';
    this.authService.verifyEmail().subscribe({
      next: (response) => {
        this.modalService.show({
          message: response,
          type: 'success',
          display: 'alert',
        });
        this.emailState = 'success';
      },
      error: () => (this.emailState = 'error'),
    });
  }

  profileTabs: FolderTab[] = PROFILE_TABS;

  activeProfileTab = 'contact';

  get contactItems(): InfoCardItemConfig[] {
    return getContactItems(this.profile);
  }

  get personalItems(): InfoCardItemConfig[] {
    return getPersonalItems(this.profile);
  }

  get addressItems(): InfoCardItemConfig[] {
    return getAddressItems(this.profile);
  }

  get accountItems(): InfoCardItemConfig[] {
    return getAccountItems(this.profile);
  }

  get accountAlerts(): AlertItem[] {
    return getAccountAlerts(this.profile);
  }
}
