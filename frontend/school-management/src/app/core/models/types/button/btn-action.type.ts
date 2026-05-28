export type ButtonAction = {
  type: 'navigate' | 'function' | 'logout';
  route?: string;
  handler?: () => void;
};
