import { AuthUser } from "./auth-user.model"

export interface UserTokens{
  access_token: string;
  refresh_token: string;
  token_type: 'Bearer';
  user_data: AuthUser;
}
