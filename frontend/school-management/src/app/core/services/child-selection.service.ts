import { Injectable } from '@angular/core';
import { ChildrenData } from '../../features/client/models/parents/children.model';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ChildSelectionService {
  private readonly KEY = 'preferredChild';

  private selectedStudentSubject = new BehaviorSubject<ChildrenData | null>(
    this.getStoredStudent(),
  );

  selectedStudent$ = this.selectedStudentSubject.asObservable();

  setChild(child: ChildrenData) {
    localStorage.setItem(this.KEY, JSON.stringify(child));
    this.selectedStudentSubject.next(child);
  }

  clearChild() {
    localStorage.removeItem(this.KEY);
    this.selectedStudentSubject.next(null);
  }

  get selectedStudent(): ChildrenData | null {
    return this.selectedStudentSubject.value;
  }

  get hasSelectedStudent(): boolean {
    return this.selectedStudentSubject.value !== null;
  }

  get selectedStudentId(): number | null {
    return this.selectedStudentSubject.value?.id ?? null;
  }
  private getStoredStudent(): ChildrenData | null {
    const data = localStorage.getItem(this.KEY);

    return data ? JSON.parse(data) : null;
  }
}
