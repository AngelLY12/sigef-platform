import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-file-input',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './file-input.component.html',
  styleUrl: './file-input.component.scss'
})
export class FileInputComponent {
  @Input() accept = '.xlsx';
  @Input() label = 'Arrastra o selecciona archivo';
  @Input() file?: File;

  @Output() fileChange = new EventEmitter<File>();

  onDragOver(event: DragEvent) {
    event.preventDefault();
  }

  onDrop(event: DragEvent) {
    event.preventDefault();

    const file = event.dataTransfer?.files?.[0];
    if (!file) return;

    this.emitFile(file);
  }

  onFileSelected(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    this.emitFile(file);
  }

  private emitFile(file: File) {
    this.fileChange.emit(file);
  }

}
