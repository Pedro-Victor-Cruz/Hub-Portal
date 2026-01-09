import { Injectable } from '@angular/core';
import {
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import {ClientKeyService} from '../services/client-key.service';

@Injectable()
export class ClientInterceptor implements HttpInterceptor {

  constructor(private clientKeyService: ClientKeyService) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Verifica se a requisição é para a API configurada no environment
    if (!request.url.startsWith(environment.api)) {
      return next.handle(request);
    }

    // Obtém o client_key atual
    const clientKey = this.clientKeyService.getClientKey();

    // Se temos um client_key, adiciona ao cabeçalho
    if (clientKey) {
      const modifiedRequest = request.clone({
        setHeaders: {
          'X-Client-Key': clientKey
        }
      });
      return next.handle(modifiedRequest);
    }

    return next.handle(request);
  }
}
