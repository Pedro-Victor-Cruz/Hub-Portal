import { Injectable } from '@angular/core';
import {
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, switchMap } from 'rxjs/operators';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private authService: AuthService) {}

  intercept(
    req: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    const token = this.authService.getToken();

    // Adiciona o token ao cabeçalho da requisição, se existir
    if (token) {
      req = req.clone({
        headers: req.headers.set('Authorization', `Bearer ${token}`),
      });
    }

    return next.handle(req).pipe(
      catchError((error) => {
        // Verifica se o erro é 401 (não autorizado) e se há um refresh token
        console.log("AuthInterceptor -> intercept -> error", error)
        if (error.status === 401 && this.authService.getRefreshToken()) {
          console.log("AuthInterceptor -> intercept -> error", this.authService.getRefreshToken())
          return this.authService.refreshToken().pipe(
            switchMap(() => {
              const newToken = this.authService.getToken();

              // Se o novo token foi obtido, clona a requisição com o novo token
              if (newToken) {
                const cloned = req.clone({
                  headers: req.headers.set('Authorization', `Bearer ${newToken}`),
                });

                // Repete a requisição com o novo token
                return next.handle(cloned);
              } else {
                // Se não foi possível renovar o token, faz logout e retorna o erro
                this.authService.logout();
                return throwError(() => new Error('Failed to refresh token'));
              }
            }),
            catchError((refreshError) => {
              this.authService.logout();
              return throwError(() => refreshError);
            })
          );
        }

        // Se o erro não for 401 ou não houver refresh token, retorna o erro
        return throwError(() => error);
      })
    );
  }
}
