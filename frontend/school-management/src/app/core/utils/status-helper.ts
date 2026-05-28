import { Status } from "../models/enums/status.enum";

export class StatusHelper {
  static getStatusOptions(): { label: string; value: Status }[] {
    return [
      { label: 'Activo', value: Status.ACTIVO },
      { label: 'Baja Temporal', value: Status.BAJA_TEMPORAL },
      { label: 'Baja', value: Status.BAJA },
      { label: 'Eliminado', value: Status.ELIMINADO }
    ];
  }
}
