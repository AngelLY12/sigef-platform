import { Component, Input } from '@angular/core';
import { FormGroup, ReactiveFormsModule } from '@angular/forms';
import { InputComponent } from '../../../../../shared/components/form/controls/input/input.component';

@Component({
  selector: 'app-contact-step',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    InputComponent
  ],
  templateUrl: './contact-step.component.html',
  styleUrl: './contact-step.component.scss'
})
export class ContactStepComponent {
    @Input({ required: true }) form!: FormGroup;
}
