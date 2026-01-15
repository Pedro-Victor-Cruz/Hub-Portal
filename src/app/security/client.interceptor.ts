import {Injectable} from '@angular/core';
import {
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
  HttpErrorResponse,
} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Router} from '@angular/router';
import {environment} from '../../environments/environment';
import {catchError} from 'rxjs/operators';
import {ClientService} from '../services/client.service';

@Injectable()
export class ClientInterceptor implements HttpInterceptor {

  private blacklistUrls: string[] = [
    'client-info'
  ]

  constructor(
    private router: Router,
    private clientService: ClientService
  ) {
  }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Verifica se a requisição é para a API configurada no environment
    if (!request.url.startsWith(environment.api) || this.blacklistUrls.some(url => request.url.includes(url))) {
      return next.handle(request);
    }

    // Extrai o client_key da URL atual
    const client = this.clientService.getCurrentClient();

    // Se temos um client_key, adiciona ao cabeçalho
    let modifiedRequest = request;
    if (client) {
      const clientKey = client.slug;
      modifiedRequest = request.clone({
        setHeaders: {
          'X-Client-Key': clientKey
        }
      });
    }

    // Intercepta erros relacionados ao cliente
    return next.handle(modifiedRequest).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.error?.code) {
          const errorCode = error.error.code;

          // Se o cliente não foi encontrado ou acesso negado, redireciona para home principal
          if (errorCode === 'client_not_found' || errorCode === 'access_client_forbidden') {
            this.clientService.clearCurrentClient();
            this.router.navigate(['/home']);
          }
        }

        return throwError(() => error);
      })
    );
  }


}
