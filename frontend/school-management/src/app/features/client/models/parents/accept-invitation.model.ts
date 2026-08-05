import { RelationshipType } from "../../../../core/models/enums/relationship-type.enum";

export interface AcceptInvitation{
  token: string;
  relationship: RelationshipType;
}
