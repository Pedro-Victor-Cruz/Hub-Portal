import { Injectable } from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  Router,
} from '@angular/router';
import { Observable, of } from 'rxjs';
import { AuthService } from './auth.service';
import { catchError, map, switchMap } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> {
    // Verifica se o usuário está autenticado
    if (this.authService.isAuthenticated()) {
      return of(true);
    }

    // Se o token estiver expirado, tenta renovar
    if (this.authService.getRefreshToken()) {
      return this.authService.refreshToken().pipe(
        switchMap(async () => this.authService.refreshUserData()),
        map(() => true),
        catchError(() => {
          this.handleUnauthorized(state);
          return of(false);
        })
      );
    }

    this.handleUnauthorized(state);
    return of(false);
  }

  private handleUnauthorized(state: RouterStateSnapshot): void {
    this.authService.logout();
    this.router.navigate(['/auth/logar'], {
      queryParams: { returnUrl: state.url },
    });
  }
}
