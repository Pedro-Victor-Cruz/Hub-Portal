import {Injectable, Injector} from '@angular/core';
import {
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
  HttpErrorResponse,
} from '@angular/common/http';
import { Observable, throwError, BehaviorSubject, EMPTY } from 'rxjs';
import { catchError, switchMap, filter, take, finalize } from 'rxjs/operators';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshTokenSubject: BehaviorSubject<any> = new BehaviorSubject<any>(null);

  // Use Injector para obter o AuthService de forma lazy
  constructor(private injector: Injector) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Não adiciona token para requisições de autenticação
    if (this.isAuthEndpoint(req.url)) {
      return next.handle(req);
    }

    // Adiciona o token se disponível
    const authenticatedReq = this.addTokenToRequest(req);

    return next.handle(authenticatedReq).pipe(
      catchError((error: HttpErrorResponse) => {
        // Se for erro 401 e não for uma requisição de refresh, tenta renovar o token
        if (error.status === 401 && !this.isRefreshEndpoint(req.url)) {
          return this.handle401Error(req, next);
        }

        // Para outros erros, apenas propaga
        return throwError(() => error);
      })
    );
  }

  /**
   * Adiciona o token de autenticação à requisição
   */
  private addTokenToRequest(req: HttpRequest<any>): HttpRequest<any> {
    const authService = this.injector.get(AuthService);
    const token = authService.getToken();

    if (token) {
      return req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
      });
    }

    return req;
  }

  /**
   * Trata erros 401 (não autorizado)
   */
  private handle401Error(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshTokenSubject.next(null);

      const authService = this.injector.get(AuthService);
      const refreshToken = authService.getRefreshToken();

      if (refreshToken) {
        return authService.refreshToken().pipe(
          switchMap(() => {
            this.isRefreshing = false;
            const newToken = authService.getToken();
            this.refreshTokenSubject.next(newToken);

            // Repete a requisição original com o novo token
            return next.handle(this.addTokenToRequest(req));
          }),
          catchError((error) => {
            this.isRefreshing = false;
            this.refreshTokenSubject.next(null);

            // Se falhar ao renovar, faz logout
            authService.logout();
            return throwError(() => error);
          }),
          finalize(() => {
            this.isRefreshing = false;
          })
        );
      } else {
        // Sem refresh token disponível, faz logout
        this.isRefreshing = false;
        authService.logout();
        return throwError(() => new Error('No refresh token available'));
      }
    } else {
      // Se já está renovando o token, espera a renovação terminar
      return this.refreshTokenSubject.pipe(
        filter(token => token !== null),
        take(1),
        switchMap(() => {
          // Repete a requisição com o novo token
          return next.handle(this.addTokenToRequest(req));
        })
      );
    }
  }

  /**
   * Verifica se a URL é um endpoint de autenticação
   */
  private isAuthEndpoint(url: string): boolean {
    const authEndpoints = ['/auth/refresh', '/auth/login', '/auth/register'];
    return authEndpoints.some(endpoint => url.includes(endpoint));
  }

  /**
   * Verifica se a URL é um endpoint de refresh
   */
  private isRefreshEndpoint(url: string): boolean {
    return url.includes('/auth/refresh');
  }
}
