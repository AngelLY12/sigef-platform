import { Component, inject, Input, OnInit } from '@angular/core';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { MultiSelectComponent } from '../../../../shared/components/form/multi-select/multi-select.component';
import { ModalService } from '../../../../core/services/modal.service';
import { PaymentConceptApiService } from '../../../../core/api/financial-staff/payment-concepts.api.service';
import { ConceptDetailResponse } from '../../models/concepts/concept-detail-response.model';
import { ConceptRelationsResponse } from '../../models/concepts/concept-relations-response.model';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { CareersResponse } from '../../../../core/models/responses/careers-response.model';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { ConceptAppliesTo } from '../../../../core/models/enums/applies-to-concepts.enum';
import { SEMESTERS } from '../../../../core/constants/semesters.constants';
import { PaymentConceptApplicantTags } from '../../../../core/models/enums/payment-concept-applicant-tags.enum';
import { FormItem } from '../../models/concepts/edit-config.type';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { debounceTime, distinctUntilChanged, Subject, switchMap } from 'rxjs';
import { SearchStudentsByNControlResponse } from '../../models/concepts/search-students-response.model';
import { CheckboxComponent } from '../../../../shared/components/form/checkbox/checkbox.component';
import { ConceptHelper } from '../../helpers/concept.helper';
import { CareersService } from '../../../../core/api/careers.api.service';
import {
  ConceptUpdateRelationsRequest,
  ConceptUpdateRequest,
} from '../../models/concepts/concept-update-request.model';

@Component({
  selector: 'app-concept-edit-form',
  standalone: true,
  imports: [
    CommonModule,
    InputComponent,
    SelectComponent,
    MultiSelectComponent,
    ButtonComponent,
    ReactiveFormsModule,
    CheckboxComponent,
  ],
  templateUrl: './concept-edit-form.component.html',
  styleUrl: './concept-edit-form.component.scss',
})
export class ConceptEditFormComponent implements OnInit {
  private modalService = inject(ModalService);
  private conceptsService = inject(PaymentConceptApiService);
  private careersService = inject(CareersService);
  private fb = inject(FormBuilder).nonNullable;
  private searchStudentsSubject = new Subject<string>();
  protected readonly ConceptAppliesTo = ConceptAppliesTo;
  @Input() concept: ConceptDetailResponse | null = null;
  @Input() relations: ConceptRelationsResponse | null = null;
  @Input() careers: CareersResponse[] | null = null;

  activeSection = 'general';
  canSend = false;
  loading: LoadingState = 'idle';
  students: SearchStudentsByNControlResponse[] = [];

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
    careers: this.fb.control<number[]>([]),
    students: this.fb.control<string[]>([]),
    exceptionStudents: this.fb.control<string[]>([]),
    semestres: this.fb.control<number[]>([]),
    applicantTags: this.fb.control<string[]>([]),
    replaceRelations: this.fb.control(true),
    replaceExceptions: this.fb.control(false),
    removeAllExceptions: this.fb.control(false),
  });

  ngOnInit(): void {
    ConceptHelper.setDynamicControls(() => this.loadCareers(), this.form);
    this.initStudentSearch();
    this.loadConceptData();
    this.form.statusChanges.subscribe(() => {
      this.canSend = this.form.valid;
    });
  }

  private loadConceptData() {
    if (!this.concept || !this.relations) return;

    this.form.patchValue({
      concept_name: this.concept.concept_name,
      amount: this.concept.amount,
      description: this.concept.description,
      start_date: this.concept.start_date,
      end_date: this.concept.end_date,
      status: this.concept.status,

      applies_to: this.relations.applies_to,

      careers: this.relations.careers ?? [],
      students: this.relations.users ?? [],
      semestres: this.relations.semesters ?? [],
      applicantTags: this.relations.applicantTags ?? [],
      exceptionStudents: this.relations.exceptionUsers ?? [],

      replaceRelations: true,
      replaceExceptions: false,
      removeAllExceptions: false,
    });
  }

  private initStudentSearch() {
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

  loadCareers() {
    if (this.careers?.length) return;
    this.careersService.getCareers().subscribe({
      next: (res) => {
        this.careers = res;
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

  shouldShowField(item: any): boolean {
    if (!item.showWhen?.length) return true;

    return item.showWhen.includes(this.form.get('applies_to')?.value);
  }

  get appliesToValue(): ConceptAppliesTo | null {
    return this.form.get('applies_to')?.value as ConceptAppliesTo | null;
  }

  get studentOptions(): { label: string; value: string }[] {
    return (
      this.students?.map((student) => ({
        label: student.text,
        value: student.n_control,
      })) ?? []
    );
  }

  formsSection: {
    key: string;
    title: string;
    icon: string;
    items: FormItem[];
  }[] = [
    {
      key: 'general',
      title: 'Información general',
      icon: 'info',
      items: [
        {
          icon: 'title',
          type: 'input',
          inputType: 'text',
          label: 'Nombre',
          controlName: 'concept_name',
        },
        {
          icon: 'description',
          type: 'input',
          inputType: 'text',
          label: 'Descripción',
          controlName: 'description',
        },
        {
          icon: 'attach_money',
          type: 'input',
          inputType: 'number',
          label: 'Monto',
          controlName: 'amount',
        },
        {
          icon: 'event',
          type: 'input',
          inputType: 'date',
          label: 'Fecha inicio',
          controlName: 'start_date',
        },
        {
          icon: 'event',
          type: 'input',
          inputType: 'date',
          label: 'Fecha de fin',
          controlName: 'end_date',
        },
      ],
    },

    {
      key: 'relations',
      title: 'Relaciones',
      icon: 'account_tree',
      items: [
        {
          icon: 'groups',
          type: 'select',
          label: 'Aplica a',
          controlName: 'applies_to',
          options: enumToOptions(ConceptAppliesTo),
        },

        {
          icon: 'school',
          type: 'multiselect',
          label: 'Carreras',
          controlName: 'careers',

          showWhen: [
            ConceptAppliesTo.CARRERA,
            ConceptAppliesTo.CARRERA_SEMESTRE,
          ],
          allowExceptions: true,
          options:
            this.careers?.map((c) => ({
              label: c.career_name,
              value: c.id,
            })) ?? [],
        },

        {
          icon: 'person',
          type: 'multiselect',
          label: 'Usuarios',
          controlName: 'students',
          showWhen: [ConceptAppliesTo.ESTUDIANTES],
          allowExceptions: true,
          options: this.studentOptions,
        },

        {
          icon: 'calendar_view_month',
          type: 'multiselect',
          label: 'Semestres',
          controlName: 'semestres',
          showWhen: [
            ConceptAppliesTo.SEMESTRE,
            ConceptAppliesTo.CARRERA_SEMESTRE,
          ],
          allowExceptions: true,
          options: SEMESTERS,
        },

        {
          icon: 'sell',
          type: 'multiselect',
          label: 'Tags aspirantes',
          controlName: 'applicantTags',
          showWhen: [ConceptAppliesTo.TAG],
          allowExceptions: true,
          options: enumToOptions(PaymentConceptApplicantTags),
        },
      ],
    },
  ];

  submit() {
    this.loading = 'loading';

    switch (this.activeSection) {
      case 'general':
        this.updateGeneral();
        break;

      case 'relations':
        this.updateRelations();
        break;
    }
  }
  private updateGeneral() {
    const payload: ConceptUpdateRequest = {
      concept_name: this.form.value.concept_name!,
      amount: this.form.value.amount!,
      description: this.form.value.description!,
      start_date: this.form.value.start_date!,
      end_date: this.form.value.end_date!,
    };

    this.conceptsService.updateConcept(payload, this.concept!.id).subscribe({
      next: (res) => {
        console.log(res);
        this.loading = 'success';
        this.modalService.closeCustom(true);
        this.modalService.show({
          message: `${res.message}
          `,
          type: 'success',
          display: 'modal',
        });
      },
      error: () => {
        this.loading = 'error';
      },
    });
  }
  private updateRelations() {
    const payload: ConceptUpdateRelationsRequest = {
      applies_to: this.form.value.applies_to! as ConceptAppliesTo,
      careers: this.form.value.careers!,
      students: this.form.value.students!,
      semestres: this.form.value.semestres!,
      applicantTags: this.form.value.applicantTags!,
      exceptionStudents: this.form.value.exceptionStudents!,
      replaceRelations: this.form.value.replaceRelations!,
      replaceExceptions: this.form.value.replaceExceptions!,
      removeAllExceptions: this.form.value.removeAllExceptions!,
    };

    this.conceptsService
      .updateConceptRelations(payload, this.concept!.id)
      .subscribe({
        next: () => {
          this.loading = 'success';
          this.modalService.closeCustom(true);
          this.modalService.show({
            message: 'Concepto actualizado correctamente',
            type: 'success',
            display: 'alert',
          });
        },
        error: () => {
          this.loading = 'error';
        },
      });
  }
}
