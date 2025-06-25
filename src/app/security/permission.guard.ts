import {Injectable} from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  Router,
} from '@angular/router';
import {Observable, of} from 'rxjs';
import {AuthService} from './auth.service';
import {catchError, map, switchMap} from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class PermissionGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {
  }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> {
    // Obtém a permissão requerida da rota
    const requiredPermission = route.data['permission'] || route.parent?.data['permission'];

    // Se não houver permissão requerida, permite o acesso
    if (!requiredPermission) {
      return of(true);
    }

    // Verifica se o usuário tem a permissão necessária
    const hasPermission = this.authService.hasPermission(requiredPermission);

    if (hasPermission) {
      return of(true); // Permissão concedida, permite o acesso
    }

    // Se o usuário não tiver a permissão, redireciona para a página de acesso negado
    this.router.navigate(['/access-denied'], {
      queryParams: {returnUrl: state.url},
    });
    return of(false); // Permissão negada, bloqueia o acesso
  }
}
