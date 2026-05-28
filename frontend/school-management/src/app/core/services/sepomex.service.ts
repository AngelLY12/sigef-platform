import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { catchError, Observable, of } from "rxjs";
import { CpData, CpDataCollection, SepoMexResponse } from "../models/sepoMex-response.model";

@Injectable({ providedIn: 'root' })
export class SepoMexService {
  private cpData: CpDataCollection | null = null;
  constructor(private http: HttpClient) {
      this.loadCPData();
  }

  private loadCPData() {
    this.http.get<CpDataCollection>('/data/codigos-postales.json')
      .pipe(catchError(err => {
        return of({});
      }))
      .subscribe(data => {
        this.cpData = data;
      });
  }

  searchByCP(cp: string): Observable<SepoMexResponse> {
    if (!this.cpData) {
      return of({ response: null });
    }

    const data = this.cpData[cp];

    if (data) {
      return of({
        response: {
          estado: data.estado,
          municipio: data.municipio,
          asentamiento: data.colonias[0] || '',
          asentamientos: data.colonias
        }
      });
    }

    // Si no se encuentra el CP
    return of({ response: null });
  }

  getNeighborhoods(cp: string): Observable<SepoMexResponse> {
    return this.searchByCP(cp);
  }

  getByEstado(estado: string): Array<{ cp: string } & CpData> {
    if (!this.cpData) return [];

    return Object.entries(this.cpData)
      .filter(([_, data]: [string, CpData]) => data.estado === estado)
      .map(([cp, data]) => ({
        cp,
        estado: data.estado,
        municipio: data.municipio,
        colonias: data.colonias
      }));
  }



}
