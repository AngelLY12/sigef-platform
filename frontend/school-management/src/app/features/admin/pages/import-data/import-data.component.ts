import { Component, inject } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { CommonModule } from '@angular/common';
import { AdminService } from '../../../../core/api/admin.api.service';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { FileInputComponent } from '../../../../shared/components/form/file-input/file-input.component';
import { ModalService } from '../../../../core/services/modal.service';

@Component({
  selector: 'app-import-data',
  standalone: true,
  imports: [
    PageLayoutComponent,
    CommonModule,
    InfoCardComponent,
    ButtonComponent,
    FileInputComponent
    ,
  ],
  templateUrl: './import-data.component.html',
  styleUrl: './import-data.component.scss',
})
export class ImportDataComponent {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);
  usersFile?: File;
  studentsFile?: File;

  usersState: LoadingState = 'idle';
  studentsState: LoadingState = 'idle';
  importMode: 'students' | 'users' = 'users';

  onChangeImportMode() {
    this.importMode = this.importMode === 'users' ? 'students' : 'users';

    this.usersFile = undefined;
    this.studentsFile = undefined;

    this.usersState = 'idle';
    this.studentsState = 'idle';
  }
  onDragOver(event: DragEvent) {
    event.preventDefault();
  }

  onDrop(event: DragEvent, type: 'users' | 'students') {
    event.preventDefault();

    const file = event.dataTransfer?.files?.[0];
    if (!file) return;

    this.setFile(file, type);
  }

  downloadTemplate() {
    const url =
      this.importMode === 'users'
        ? '/assets/templates/users.xlsx'
        : '/assets/templates/students.xlsx';

    const a = document.createElement('a');
    a.href = url;
    a.download = url.split('/').pop()!;
    a.click();
  }

  onFileSelected(event: Event, type: 'users' | 'students') {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    this.setFile(file, type);
  }

  private setFile(file: File, type: 'users' | 'students') {
    if (type === 'users') {
      this.usersFile = file;
    } else {
      this.studentsFile = file;
    }
  }

  onImportUsers() {
    if (!this.usersFile) return;

    this.usersState = 'loading';

    this.adminService.importUsers(this.usersFile).subscribe({
      next: (res) => {
        this.usersState = 'success';
        this.modalService.show({ message: res, display:'alert', type: 'info'})
      },
      error: () => {
        this.usersState = 'error';
      },
    });
  }

  onImportStudents() {
    if (!this.studentsFile) return;

    this.studentsState = 'loading';

    this.adminService.importStudents(this.studentsFile).subscribe({
      next: () => {
        this.studentsState = 'success';
      },
      error: () => {
        this.studentsState = 'error';
      },
    });
  }
}
