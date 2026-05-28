import { PermissionsByUserParams } from './../models/domain/permissions-by-user-params.model';
import { UpdatePermissionsBulkResponse } from './../models/responses/update-permissions-bulk-response.model';
import { ApiSuccessResponse } from './../models/api-success-response.model';
import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { ADMIN_URL } from "../constants/api.constants";
import { DashboardSummary } from "../../features/admin/models/dashboard-summary.model";
import { UserListItem } from "../../features/admin/models/user-list-item.model";
import { Paginated } from "../utils/paginated-helper.utils";
import { PaginatedResponse } from "../models/domain/paginated-response.model";
import { UserDetails } from "../../features/admin/models/user-details.model";
import { UserListParams } from "../models/domain/user-list-params.model";
import { ActivateUsers, ChangeUsersStatus, DeleteUsers, DisableUsers, TemporaryDisableUsers } from "../models/responses/change-users-status-response.model";
import { UpdateRolesBulk } from "../../features/admin/models/update-roles-bulk.model";
import { UpdateRolesBulkResponse, UsersRoles } from "../models/responses/update-roles-bulk-response.model";
import { cleanObjectWithOptions } from "../helpers";
import { UpdatePermissionsBulk } from "../../features/admin/models/update-permissions-bulk.model";
import { UsersPermissions } from "../models/responses/update-permissions-bulk-response.model";
import { PermissionsByCurps, PermissionsByCurpsResponse } from '../models/responses/permissions-by-curp-response.model';
import { PermissionsByRole, PermissionsByRoleResponse } from '../models/responses/permissions-by-role-response.model';
import { Permission } from '../models/domain/permissions.model';
import { PermissionsByUserResponse } from '../models/responses/permissions-by-user-response.model';
import { UpdateRolesToUser } from '../../features/admin/models/update-roles-to-user.model';
import { UpdatePermissionsToUser } from '../../features/admin/models/update-permissions-to-user.model';
import { RolesByUser, UpdateRolesByUserResponse } from '../models/responses/update-roles-by-user-response.model';
import { PermissionsByUser, UpdatePermissionsByUserResponse } from '../models/responses/update-permissions-by-user-response.model';
import { AttachStudentDetailsParams, UpdateStudentDetailsParams } from '../models/domain/student-details-params.model';
import { PromoteStudentsResponse } from '../models/responses/promote-students-response.model';

@Injectable({ providedIn: 'root' })
export class AdminService {
  private http = inject(HttpClient);

  getSummary(): Observable<ApiSuccessResponse<{ summary: DashboardSummary }>> {
    return this.http.get<ApiSuccessResponse<{ summary: DashboardSummary }>>(
      `${ADMIN_URL}/users-summary`
    );
  }

  getUsers(params: UserListParams): Observable<Paginated<UserListItem>> {
    const { page, perPage, forceRefresh, status } = params;
    const url = `${ADMIN_URL}/show-users?page=${page}&perPage=${perPage}${forceRefresh ? '&forceRefresh=true' : ''}${status ? `&status=${status}` : ''}`;
    return this.http
      .get<ApiSuccessResponse<{ users: PaginatedResponse<UserListItem> }>>(
        url
      )
      .pipe(
        map(res => new Paginated(res.data.users))
      );
  }

  getUserDetails(id: number): Observable<UserDetails> {
    return this.http
      .get<ApiSuccessResponse<{ user: UserDetails }>>(
        `${ADMIN_URL}/show-users/${id}`
      )
      .pipe(
        map(res => res.data.user)
      );
  }

  activateUsers(ids: number[]): Observable<ChangeUsersStatus> {
    return this.http
    .post<ApiSuccessResponse<ActivateUsers>>(`${ADMIN_URL}/activate-users`, {ids})
    .pipe(
      map(res => res.data.activate_users)
    );
  }

  deleteUsers(ids: number[]): Observable<ChangeUsersStatus> {
    return this.http
    .post<ApiSuccessResponse<DeleteUsers>>(`${ADMIN_URL}/delete-users`, {ids})
    .pipe(
      map(res => res.data.delete_users)
    );
  }

  disableUsers(ids: number[]): Observable<ChangeUsersStatus> {
    return this.http
    .post<ApiSuccessResponse<DisableUsers>>(`${ADMIN_URL}/disable-users`, {ids})
    .pipe(
      map(res => res.data.disable_users)
    );
  }

  temporaryDisableUsers(ids: number[]): Observable<ChangeUsersStatus> {
    return this.http
    .post<ApiSuccessResponse<TemporaryDisableUsers>>(`${ADMIN_URL}/temporary-disable-users`, {ids})
    .pipe(
      map(res => res.data.temporary_disable_users)
    );
  }

  updateRolesBulk(update: UpdateRolesBulk): Observable<UsersRoles> {
    const payload = cleanObjectWithOptions({...update, curps: update.curps}, { removeEmptyArrays: true });
    return this.http
    .post<ApiSuccessResponse<UpdateRolesBulkResponse>>(`${ADMIN_URL}/updated-roles`,  payload)
    .pipe(
      map(res => res.data.users_roles)
    );
  }

  updatePermmissionsBulk(update: UpdatePermissionsBulk): Observable<UsersPermissions> {
    const payload = cleanObjectWithOptions(update, {removeEmptyArrays: true, removeEmptyStrings: true, removeNull: true});
    return this.http
    .post<ApiSuccessResponse<UpdatePermissionsBulkResponse>>(`${ADMIN_URL}/update-permissions`, payload)
    .pipe(
      map(res => res.data.users_permissions)
    );
  }

  getPermissionsByCurps(curps: string[]): Observable<PermissionsByCurps> {
    return this.http
    .post<ApiSuccessResponse<PermissionsByCurpsResponse>>(`${ADMIN_URL}/permissions/by-curps`, {curps})
    .pipe(
      map(res => res.data.permissions)
    );
  }

  getPermissionsByRole(role: string): Observable<PermissionsByRole> {
    return this.http
    .post<ApiSuccessResponse<PermissionsByRoleResponse>>(`${ADMIN_URL}/permissions/by-role`, { role })
    .pipe(
      map(res => res.data.permissions)
    );
  }

  getPermissionsByUser(userId: number, params: PermissionsByUserParams): Observable<Permission[]> {
    const payload = cleanObjectWithOptions(params, {
      removeEmptyArrays: true,
      removeEmptyStrings: true,
      removeNull: true
    });

    return this.http
      .post<ApiSuccessResponse<PermissionsByUserResponse>>(
        `${ADMIN_URL}/permissions/by-user/${userId}`,
        payload
      )
      .pipe(
        map(res => res.data.permissions)
      );
  }

  updateRolesByUser(userId:number, update: UpdateRolesToUser): Observable<RolesByUser> {
    const payload = cleanObjectWithOptions({...update}, { removeEmptyArrays: true });
    return this.http
      .post<ApiSuccessResponse<UpdateRolesByUserResponse>>(
        `${ADMIN_URL}/updated-roles/${userId}`,
        payload
      )
      .pipe(
        map(res => res.data.updated)
    );
  }

  updatePermmissionsByUser(userId:number, update: UpdatePermissionsToUser): Observable<PermissionsByUser> {
    const payload = cleanObjectWithOptions({...update}, { removeEmptyArrays: true });
    return this.http
    .post<ApiSuccessResponse<UpdatePermissionsByUserResponse>>(`${ADMIN_URL}/update-permissions/${userId}`, payload)
    .pipe(
      map(res => res.data.updated)
    );
  }

  attachStudentDetails(params: AttachStudentDetailsParams): Observable<string> {
    return this.http.
    post<ApiSuccessResponse<void>>(`${ADMIN_URL}/attach-student`, params)
    .pipe(
      map(res => res.message)
    );
  }

  updateStudentDetails(id:number,params: UpdateStudentDetailsParams): Observable<string> {
    const payload = cleanObjectWithOptions({ ...params }, {removeEmptyStrings: true, removeNull: true, removeUndefined: true})
    return this.http
    .patch<ApiSuccessResponse<void>>(`${ADMIN_URL}/update-student/${id}`, payload)
    .pipe(
      map(res => res.message)
    );
  }

  promoteStudents(): Observable<string> {
    return this.http.patch<ApiSuccessResponse<string>>(`${ADMIN_URL}/promote`, null)
    .pipe(
      map(res => res.message)
    );
  }

  importUsers(file: File): Observable<string> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ApiSuccessResponse<string>>(`${ADMIN_URL}/import-users`, formData)
    .pipe(
      map(res => res.message)
    );
  }

  importStudents(file: File): Observable<string> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ApiSuccessResponse<string>>(`${ADMIN_URL}/import-students`, formData)
    .pipe(
      map(res => res.message)
    );
  }

}
