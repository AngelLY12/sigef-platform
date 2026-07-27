import { Component, inject } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { AdminService } from '../../../../core/api/admin.api.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ModalService } from '../../../../core/services/modal.service';
import { ImportPanelComponent } from '../../components/import/import-panel/import-panel.component';

@Component({
  selector: 'app-import-data',
  standalone: true,
  imports: [PageLayoutComponent, ImportPanelComponent],
  templateUrl: './import-data.component.html',
  styleUrl: './import-data.component.scss',
})
export class ImportDataComponent {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);

  file?: File;
  currentState: LoadingState = 'idle';
  importMode: 'students' | 'users' = 'users';

  onChangeImportMode() {
    this.importMode = this.importMode === 'users' ? 'students' : 'users';
    this.file = undefined;
    this.currentState = 'idle';
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

  onFileChange(file?: File): void {
    this.file = file;
  }

  onImport(): void {
    if (!this.file) return;

    this.currentState = 'loading';

    const request$ =
      this.importMode === 'users'
        ? this.adminService.importUsers(this.file)
        : this.adminService.importStudents(this.file);

    request$.subscribe({
      next: (res) => {
        this.currentState = 'success';

        this.modalService.show({
          message: res,
          display: 'alert',
          type: 'info',
        });
      },
      error: () => {
        this.currentState = 'error';
      },
    });
  }
}
