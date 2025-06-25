import {Directive, Input, OnDestroy, TemplateRef, ViewContainerRef} from '@angular/core';
import {Subscription} from 'rxjs';
import {AuthService} from '../security/auth.service';

@Directive({
  standalone: true,
  selector: '[hasPermission]'
})
export class HasPermissionDirective implements OnDestroy {

  private permissionSubscription: Subscription | undefined;
  private currentPermission: string | string[] | undefined;

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private authService: AuthService
  ) {}

  @Input() set hasPermission(permission: string | string[] | undefined) {
    this.currentPermission = permission;
    this.updateView();
  }

  private updateView(): void {
    // Limpa a view antes de verificar novamente
    this.viewContainer.clear();

    if (!this.currentPermission) {
      // Se não houver permissão especificada, mostra o conteúdo
      this.viewContainer.createEmbeddedView(this.templateRef);
      return;
    }

    const hasPermission = this.authService.hasPermission(this.currentPermission);

    if (hasPermission) {
      // Se o usuário tem a permissão, mostra o conteúdo
      this.viewContainer.createEmbeddedView(this.templateRef);
    } else {
      // Se não tem permissão, não mostra nada
      this.viewContainer.clear();
    }
  }

  ngOnDestroy(): void {
    if (this.permissionSubscription) {
      this.permissionSubscription.unsubscribe();
    }
  }

}
