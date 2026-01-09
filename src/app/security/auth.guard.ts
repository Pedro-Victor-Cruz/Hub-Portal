import { Injectable } from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  Router,
  UrlTree,
} from '@angular/router';
import { Observable, of } from 'rxjs';
import { AuthService } from './auth.service';
import { catchError, map, switchMap, timeout } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    // Verifica se o usuário está autenticado
    if (this.authService.isAuthenticated()) {
      return true;
    }

    // Se não está autenticado mas tem refresh token, tenta renovar
    const refreshToken = this.authService.getRefreshToken();
    if (refreshToken) return this.attemptTokenRefresh(state);

    // Sem autenticação e sem refresh token - redireciona para login
    return this.redirectToLogin(state.url);
  }

  /**
   * Tenta renovar o token usando refresh token
   */
  private attemptTokenRefresh(state: RouterStateSnapshot): Observable<boolean | UrlTree> {
    return this.authService.refreshToken().pipe(
      // Timeout de 10 segundos para evitar travamentos
      timeout(10000),

      // Se o refresh foi bem-sucedido, atualiza dados do usuário
      switchMap(() => {
        if (this.authService.isAuthenticated()) {
          // Opcionalmente, atualiza os dados do usuário
          return this.authService.refreshUserData().pipe(
            map(() => true),
            catchError((error) => {
              console.warn('Failed to refresh user data after token refresh:', error);
              // Mesmo que falhe ao atualizar dados do usuário, permite acesso se o token é válido
              return of(true);
            })
          );
        }
        return of(false);
      }),

      // Em caso de erro, redireciona para login
      catchError((error) => {
        console.warn('Token refresh failed in AuthGuard:', error);
        return of(this.redirectToLogin(state.url));
      })
    );
  }

  /**
   * Redireciona para a página de login
   */
  private redirectToLogin(returnUrl: string): UrlTree {
    return this.router.createUrlTree(['/auth/logar'], {
      queryParams: { returnUrl }
    });
  }
}
