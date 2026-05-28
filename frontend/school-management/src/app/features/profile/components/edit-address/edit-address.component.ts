import { CommonModule } from '@angular/common';
import { Component, inject, Input, OnInit } from '@angular/core';
import { AddressComponent } from '../../../../shared/components/features/address/address.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ProfileService } from '../../../../core/api/profile.api.service';
import { FormControl, FormGroup } from '@angular/forms';
import { Address } from '../../../../core/models/domain/address.model';
import { ModalService } from '../../../../core/services/modal.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-edit-address',
  imports: [CommonModule, AddressComponent, ButtonComponent],
  templateUrl: './edit-address.component.html',
  styleUrl: './edit-address.component.scss'
})
export class EditAddressComponent implements OnInit {
  private profileService = inject(ProfileService);
  private modalService = inject(ModalService);

  @Input() userAddress!: Address;
  @Input() onSuccess?: () => void;

  form!: FormGroup;
  updateState: LoadingState = 'idle';

  ngOnInit() {
    this.form = new FormGroup({
      cp: new FormControl(this.userAddress?.cp || ''),
      state: new FormControl(this.userAddress?.state || ''),
      city: new FormControl(this.userAddress?.city || ''),
      neighborhood: new FormControl(this.userAddress?.neighborhood || ''),
      street: new FormControl(this.userAddress?.street || ''),
      number: new FormControl(this.userAddress?.number || ''),
    });
  }

  onCloseModal() {
    this.modalService.closeCustom();
  }

  submit() {
    const updated: Address = this.form.value;
    this.updateState = 'loading';
    if (JSON.stringify(updated) === JSON.stringify(this.userAddress)) {
      this.modalService.closeCustom();
      return;
    }

    this.profileService.editProfile({
      address: updated
    }).subscribe({
      next: (message) => {
        this.modalService.closeCustom();
        this.updateState='success';
        this.onSuccess?.();
        this.modalService.show({
          message,
          type: 'success',
          display: 'modal'
        });
      }
    });
  }


}
