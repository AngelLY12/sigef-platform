import { Injectable } from "@angular/core";
import { BehaviorSubject } from "rxjs";
import { Theme } from "../models/types/theme.type";

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private currentTheme$ = new BehaviorSubject<Theme>('light');
  constructor() {
    const savedTheme = localStorage.getItem('app-theme') as Theme;
    if (savedTheme) {
      this.setTheme(savedTheme);
    } else {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.setTheme(prefersDark ? 'dark' : 'light');
    }
  }

  get theme() {
    return this.currentTheme$.asObservable();
  }

  get themeValue() {
    return this.currentTheme$.value;
  }

  setTheme(theme: Theme) {
    this.currentTheme$.next(theme);
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('app-theme', theme);
  }

  toggleTheme() {
    const newTheme: Theme = this.currentTheme$.value === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme);

  }
}
