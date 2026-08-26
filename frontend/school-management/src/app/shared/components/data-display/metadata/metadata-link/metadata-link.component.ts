import { Component, Input } from '@angular/core';
import { AnchorComponent } from '../../../ui/anchor/anchor.component';

@Component({
  selector: 'app-metadata-link',
  imports: [AnchorComponent],
  templateUrl: './metadata-link.component.html',
  styleUrl: './metadata-link.component.scss'
})
export class MetadataLinkComponent {
  @Input({ required: true }) label!: string;
  @Input({ required: true }) text!: string;
  @Input({ required: true }) link!: string;
}
