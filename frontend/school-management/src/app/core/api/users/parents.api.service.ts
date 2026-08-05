import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { InviteParent } from '../../../features/client/models/parents/invite-parent.model';
import { PARENTS_URL } from '../../constants/api.constants';
import { map, Observable } from 'rxjs';
import { ApiSuccessResponse } from '../../models/api/api-success-response.model';
import { AcceptInvitation } from '../../../features/client/models/parents/accept-invitation.model';
import { Children } from '../../../features/client/models/parents/children.model';
import { Parents } from '../../../features/client/models/parents/parents.model';

@Injectable({ providedIn: 'root' })
export class ParentsApiService {
  private http = inject(HttpClient);

  inviteParent(data: InviteParent): Observable<string> {
    return this.http
      .post<ApiSuccessResponse<string>>(PARENTS_URL.invite, data)
      .pipe(map((res) => res.message));
  }
  acceptInvitation(data: AcceptInvitation): Observable<string> {
    return this.http
      .post<ApiSuccessResponse<string>>(PARENTS_URL.acceptInvitation, data)
      .pipe(map((res) => res.message));
  }

  getChildren(forceRefresh: boolean = false): Observable<Children> {
    return this.http
      .get<ApiSuccessResponse<{ children: Children }>>(PARENTS_URL.children, {
        params: {
          forceRefresh: forceRefresh.toString(),
        },
      })
      .pipe(map((res) => res.data.children));
  }

  getParents(forceRefresh: boolean = false): Observable<Parents> {
    return this.http
      .get<ApiSuccessResponse<{ parents: Parents }>>(PARENTS_URL.parents, {
        params: {
          forceRefresh: forceRefresh.toString(),
        },
      })
      .pipe(map((res) => res.data.parents));
  }

  removeParent(parentId: number): Observable<string> {
    return this.http
      .delete<
        ApiSuccessResponse<string>
      >(`${PARENTS_URL.removeParent}/${parentId}`)
      .pipe(map((res) => res.message));
  }
}
