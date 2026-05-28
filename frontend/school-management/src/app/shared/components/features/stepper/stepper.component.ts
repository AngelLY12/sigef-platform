import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { animate, style, transition, trigger } from '@angular/animations';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-stepper',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './stepper.component.html',
  styleUrl: './stepper.component.scss',
  animations: [
    trigger('slideContent', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateX(25px)' }),
        animate('0.4s ease', style({ opacity: 1, transform: 'translateX(0)' }))
      ]),
      transition(':leave', [
        animate('0.4s ease', style({ opacity: 0, transform: 'translateX(-25px)' }))
      ])
    ]),
    trigger('stepIndicator', [
      transition('inactive => active', [
        style({ transform: 'scale(1)' }),
        animate('0.4s ease', style({ transform: 'scale(1.1)' })),
        animate('0.4s ease', style({ transform: 'scale(1)' }))
      ])
    ])
  ]
})
export class StepperComponent {
  @Input() steps: string[] = [];
  @Input() currentStep = 0;
  @Input() canProceed = true;
  @Input() loading = false;

  @Output() nextStep = new EventEmitter<void>();
  @Output() prevStep = new EventEmitter<void>();
  @Output() completed = new EventEmitter<void>();

  get isLastStep(): boolean {
    return this.currentStep === this.steps.length - 1;
  }

  next() {
    if (!this.canProceed || this.loading) return;
    if (this.isLastStep) {
      this.completed.emit();
    }else{
      this.nextStep.emit();
    }
  }

  prev() {
    this.prevStep.emit();
  }

}
