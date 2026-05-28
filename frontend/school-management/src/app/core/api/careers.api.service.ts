import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { CareersResponse } from "../models/responses/careers-response.model";
import { CAREER_URL } from "../constants/api.constants";
import { ApiSuccessResponse } from "../models/api-success-response.model";

@Injectable({ providedIn: 'root' })
export class CareersService {
  private http = inject(HttpClient);

  getCareers(): Observable<CareersResponse[]>{
    return this.http.get<ApiSuccessResponse<{ careers: CareersResponse[] }>>(`${CAREER_URL}`)
    .pipe(
      map(res => res.data.careers)
    );

  }


}
