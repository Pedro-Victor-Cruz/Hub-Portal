import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter, RouteReuseStrategy } from '@angular/router';
import { routes } from './app.routes';
import {HTTP_INTERCEPTORS, provideHttpClient, withInterceptorsFromDi} from '@angular/common/http'; // Importe o provideHttpClient
import { AuthInterceptor } from './security/auth.interceptor';
import {TabReuseStrategy} from './custom-route-reuse-strategy';
import {provideClientHydration} from '@angular/platform-browser';
import {provideAnimations} from '@angular/platform-browser/animations';
import {ClientInterceptor} from './security/client.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideHttpClient(withInterceptorsFromDi()), // Adicione o provideHttpClient
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true },
    { provide: HTTP_INTERCEPTORS, useClass: ClientInterceptor, multi: true },
    provideAnimations(),
    provideRouter(routes),
    provideClientHydration(),
    {
      provide: RouteReuseStrategy,
      useClass: TabReuseStrategy,
    },
  ],
};
