import { ButtonSize } from "./btn-size.type";
import { ButtonVariant } from "./btn-variant.type";

export type ButtonConfig = {
  text: string;
  icon: string;
  loading?: boolean;
  variant?: ButtonVariant;
  size?: ButtonSize;
  action: 'navigate' | 'function' | 'logout' | 'back';
  route?: string;
  handler?: () => void;
};
