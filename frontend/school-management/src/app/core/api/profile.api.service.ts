import { HttpClient } from "@angular/common/http";
import { inject, Injectable, TemplateRef } from "@angular/core";
import { ApiSuccessResponse } from "../models/api-success-response.model";
import { PROFILE_URL } from "../constants/api.constants";
import { UserProfileResponse } from "../models/responses/profile-response.model";
import { BehaviorSubject, map, Observable } from "rxjs";
import { EditPassword, EditProfileParams } from "../../features/profile/models/edit-profile-params.model";
import { cleanObjectWithOptions } from "../helpers";

@Injectable({ providedIn: 'root'})
export class ProfileService {
  private http = inject(HttpClient);

  profile()
  {
    return this.http.get<ApiSuccessResponse<UserProfileResponse>>(`${PROFILE_URL}/user`);
  }

  editProfile(params: EditProfileParams): Observable<string>
  {
    const payload = cleanObjectWithOptions({...params}, { removeEmptyStrings: true, removeEmptyObjects: true, removeNull: true, removeUndefined: true, removeEmptyArrays: true });
    return this.http.patch<ApiSuccessResponse<string>>(`${PROFILE_URL}/update`, payload)
    .pipe(
      map(res => res.message)
    );
  }

  editPassword(params: EditPassword): Observable<string>
  {
    return this.http.patch<ApiSuccessResponse<string>>(`${PROFILE_URL}/update/password`,params)
    .pipe(
      map(res => res.message)
    );
  }

}
