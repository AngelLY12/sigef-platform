import { RelationshipType } from "../../../../core/models/enums/relationship-type.enum";

export interface Children {
  parentId: number;
  parentName: string;
  childrenData: ChildrenData[];
}

export interface ChildrenData {
  id: number;
  fullName: string;
  relationship: string;
}
