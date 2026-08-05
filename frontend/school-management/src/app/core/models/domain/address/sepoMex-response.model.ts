export interface SepoMexResponse {
  response: {
    estado: string;
    municipio: string;
    asentamiento: string;
    asentamientos: string[];
  } | null;
}


export interface CpData {
  estado: string;
  municipio: string;
  colonias: string[];
}

export interface CpDataCollection {
  [cp: string]: CpData;
}
