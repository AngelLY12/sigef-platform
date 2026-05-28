import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { SYSTEM_URL } from "../constants/api.constants";

@Injectable({ providedIn: 'root' })
export class SystemServiceApiService {
  private http = inject(HttpClient);

  getSystemStatus() {
    return this.http.get(SYSTEM_URL);
  }
}
