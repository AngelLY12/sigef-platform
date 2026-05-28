import { Injectable } from "@angular/core";
import { NAVIGATION } from "../../navigation/navigation.config";

@Injectable({
  providedIn: 'root'
})
export class AuthNavigationHelper {
  get login(): string {
    return NAVIGATION.auth.login;
  }

  get register(): string {
    return NAVIGATION.auth.register;
  }

  get forgotPassword(): string {
    return NAVIGATION.auth.forgotPassword;
  }

  get resetPassword(): string {
    return NAVIGATION.auth.resetPassword;
  }

}
