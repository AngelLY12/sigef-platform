import { ConceptAppliesTo } from "../../../../core/models/enums/applies-to-concepts.enum";

export interface ConceptUpdateRequest {
  concept_name: string;
  description: string;
  start_date: string;
  end_date: string;
  amount: string;
}

export interface ConceptUpdateRelationsRequest {
  applies_to: ConceptAppliesTo;
  semestres: number[];
  careers: number[];
  students: string[];
  replaceRelations: boolean;
  exceptionStudents: string[];
  replaceExceptions: boolean;
  removeAllExceptions: boolean;
  applicantTags: string[];
}
