import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { ChildSelectionService } from '../services/child-selection.service';

export const studentInterceptor: HttpInterceptorFn = (req, next) => {
  const childSelection = inject(ChildSelectionService);

  const studentId = childSelection.selectedStudentId;

  if (!studentId) {
    return next(req);
  }

  return next(
    req.clone({
      setHeaders: {
        'X-Student-Id': studentId.toString(),
      },
    }),
  );
};
