import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { StepperComponent } from '../../../../shared/components/features/stepper/stepper.component';
import { PaymentConceptApiService } from '../../../../core/api/financial-staff/payment-concepts.api.service';
import { ModalService } from '../../../../core/services/modal.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ConceptsCreateRequest } from '../../models/concepts/concepts-create-request.model';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { PaymentConceptStatus } from '../../../../core/models/enums/payment-concepts-status.enum';
import { ConceptAppliesTo } from '../../../../core/models/enums/applies-to-concepts.enum';
import { CareersService } from '../../../../core/api/careers.api.service';
import { MultiSelectComponent } from '../../../../shared/components/form/multi-select/multi-select.component';
import { SearchStudentsByNControlResponse } from '../../models/concepts/search-students-response.model';
import { SEMESTERS } from '../../../../core/constants/semesters.constants';
import { PaymentConceptApplicantTags } from '../../../../core/models/enums/payment-concept-applicant-tags.enum';
import { AppliesToConfig } from '../../models/concepts/applies-to-config.type';
import { debounceTime, distinctUntilChanged, Subject, switchMap } from 'rxjs';
import { ConceptHelper } from '../../helpers/concept.helper';

@Component({
  selector: 'app-concept-created-form',
  standalone: true,
  imports: [
    CommonModule,
    StepperComponent,
    InputComponent,
    SelectComponent,
    MultiSelectComponent,
    ReactiveFormsModule,
  ],
  templateUrl: './concept-created-form.component.html',
  styleUrl: './concept-created-form.component.scss',
})
export class ConceptCreatedFormComponent implements OnInit {
  private conceptsService = inject(PaymentConceptApiService);
  private careersService = inject(CareersService);
  private modalService = inject(ModalService);
  private fb = inject(FormBuilder).nonNullable;
  private _mode: ConceptAppliesTo | null = null;
  private searchStudentsSubject = new Subject<string>();

  ngOnInit(): void {
    ConceptHelper.setDynamicControls(this.loadCareers, this.form);
    this.searchStudentsSubject
      .pipe(
        debounceTime(500),
        distinctUntilChanged(),
        switchMap((nControl) =>
          this.conceptsService.searchStudentsByNControl(nControl),
        ),
      )
      .subscribe({
        next: (students) => {
          this.students = students;
        },
      });
  }

  get mode(): ConceptAppliesTo | null {
    return this._mode;
  }

  set mode(value: ConceptAppliesTo | null) {
    this._mode = value;
    this.resetState();
  }

  form = this.fb.group({
    concept_name: this.fb.control('', [
      Validators.required,
      Validators.minLength(2),
      Validators.maxLength(50),
      Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/),
    ]),
    amount: this.fb.control('', [
      Validators.required,
      Validators.min(10.0),
      Validators.max(25000.0),
    ]),
    status: this.fb.control('', [Validators.required]),
    description: this.fb.control('', [Validators.maxLength(200)]),
    start_date: this.fb.control('', [Validators.required]),
    end_date: this.fb.control('', []),
    applies_to: this.fb.control('', [Validators.required]),
    careers: this.fb.control([]),
    students: this.fb.control([]),
    exceptionStudents: this.fb.control([]),
    semestres: this.fb.control([]),
    applicantTags: this.fb.control([]),
  });

  careers: { label: string; value: any }[] = [];
  students: SearchStudentsByNControlResponse[] = [];
  statusOptions = enumToOptions(PaymentConceptStatus);
  semesters = SEMESTERS;
  appliesToOptions = enumToOptions(ConceptAppliesTo);
  currentStep = 0;

  loading: LoadingState = 'idle';

  steps: string[] = [];

  get stepLabels(): string[] {
    return [
      'Información general',
      'Rango de aplicabilidad',
      'Reglas de aplicación',
    ];
  }

  get studentOptions() {
    return this.students.map((student) => ({
      label: student.text,
      value: student.n_control,
    }));
  }

  loadCareers() {
    this.careersService.getCareers().subscribe({
      next: (careers) => {
        this.careers = careers.map((career) => ({
          label: career.career_name,
          value: career.id,
        }));
      },
    });
  }

  searchStudents(nControl?: string) {
    if (!nControl || nControl.trim().length < 3) {
      this.students = [];
      return;
    }

    this.searchStudentsSubject.next(nControl);
  }

  canProceed(): boolean {
    switch (this.currentStep) {
      case 0:
        return !!(
          this.form.get('concept_name')?.valid &&
          this.form.get('amount')?.valid &&
          this.form.get('status')?.valid
        );

      case 1:
        return !!this.form.get('start_date')?.valid;

      case 2:
        return (
          !!this.form.get('applies_to')?.valid &&
          !!ConceptHelper.areDynamicControlsValid(this.form)
        );

      default:
        return false;
    }
  }

  get appliesToSelection(): AppliesToConfig | null {
    const value = this.form.get('applies_to')?.value;
    switch (value) {
      case ConceptAppliesTo.CARRERA:
        return {
          type: 'multiselect',
          label: 'Selecciona carreras',
          controlName: 'careers',
          options: this.careers,
          allowExceptions: true,
        };
      case ConceptAppliesTo.SEMESTRE:
        return {
          type: 'multiselect',
          label: 'Selecciona semestres',
          controlName: 'semestres',
          options: this.semesters,
          allowExceptions: true,
        };
      case ConceptAppliesTo.ESTUDIANTES:
        return {
          type: 'search',
          label: 'Selecciona estudiantes',
          controlName: 'students',
          options:
            this.students.map((student) => ({
              label: `${student.text}`,
              value: student.n_control,
            })) ?? [],
        };
      case ConceptAppliesTo.CARRERA_SEMESTRE:
        return {
          type: 'career-semester',
          label: 'Selecciona carreras y semestres',
          allowExceptions: true,
        };
      case ConceptAppliesTo.TAG:
        return {
          type: 'multiselect',
          label: 'Selecciona tags',
          controlName: 'applicantTags',
          options: enumToOptions(PaymentConceptApplicantTags),
          allowExceptions: true,
        };
      case ConceptAppliesTo.TODOS:
        return {
          type: 'info',
          message: 'Aplica a todos los estudiantes.',
          allowExceptions: true,
        };
      default:
        return null;
    }
  }

  nextStep() {
    this.currentStep++;
  }

  prevStep() {
    this.currentStep--;
  }

  resetState() {
    this.currentStep = 2;
  }

  complete() {
    const request: ConceptsCreateRequest = {
      concept_name: this.form.value.concept_name!,
      amount: this.form.value.amount!,
      status: this.form.value.status! as PaymentConceptStatus,
      description: this.form.value.description!,
      start_date: this.form.value.start_date!,
      end_date: this.form.value.end_date!,
      applies_to: this.form.value.applies_to! as ConceptAppliesTo,
      careers: this.form.value.careers!,
      students: this.form.value.students!,
      exceptionStudents: this.form.value.exceptionStudents!,
      semestres: this.form.value.semestres!,
      applicantTags: this.form.value
        .applicantTags! as PaymentConceptApplicantTags[],
    };

    this.loading = 'loading';
    this.conceptsService.createPaymentConcept(request).subscribe({
      next: (response) => {
        this.loading = 'success';
        this.modalService.closeCustom();
        this.modalService.show({
          message: response.message ?? 'Concepto creado correctamente',
          type: 'success',
          display: 'alert',
        });
      },
      error: (error) => {
        this.loading = 'error';
      },
    });
  }
}
