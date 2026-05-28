import { FormGroup, Validators } from '@angular/forms';
import { ConceptAppliesTo } from '../../../core/models/enums/applies-to-concepts.enum';

export class ConceptHelper {
  private static dynamicControls = [
    'careers',
    'students',
    'semestres',
    'applicantTags',
  ];
  static resetDynamicControls(form: FormGroup) {
    this.dynamicControls.forEach((control) => {
      const formControl = form.get(control);

      formControl?.clearValidators();

      formControl?.setErrors(null);

      formControl?.setValue([]);

      formControl?.updateValueAndValidity({
        emitEvent: false,
      });
    });
  }

  static setRequired(form: FormGroup, controlName: string) {
    const control = form.get(controlName);

    control?.setValidators([Validators.required]);

    control?.updateValueAndValidity({
      emitEvent: false,
    });
  }

  static areDynamicControlsValid(form: FormGroup): boolean {
    return this.dynamicControls.every((name) => {
      const control = form.get(name);

      if (!control?.validator) return true;

      return control.valid;
    });
  }

  static setDynamicControls(loadCareers: () => void, form: FormGroup) {
    form.get('applies_to')?.valueChanges.subscribe((value) => {
      this.resetDynamicControls(form);

      switch (value) {
        case ConceptAppliesTo.CARRERA:
          this.setRequired(form, 'careers');
          loadCareers();
          break;

        case ConceptAppliesTo.SEMESTRE:
          this.setRequired(form, 'semestres');
          break;

        case ConceptAppliesTo.ESTUDIANTES:
          this.setRequired(form, 'students');
          break;

        case ConceptAppliesTo.CARRERA_SEMESTRE:
          this.setRequired(form, 'careers');
          this.setRequired(form, 'semestres');
          loadCareers();
          break;

        case ConceptAppliesTo.TAG:
          this.setRequired(form, 'applicantTags');
          break;
      }
    });
  }
}
