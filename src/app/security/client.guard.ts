import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  Router,
  RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import { Observable } from 'rxjs';
import { ClientService } from '../services/client.service';
import { ClientNavigationService } from '../services/client-navigation.service';

@Injectable({
  providedIn: 'root'
})
export class ClientGuard implements CanActivate {

  constructor(
    private clientService: ClientService,
    private clientNavigationService: ClientNavigationService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {
    return this.checkClientContext(route, state);
  }

  private async checkClientContext(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Promise<boolean> {
    // Extrai o client_key da rota
    const urlClientKey = route.paramMap.get('client_key');

    // Se não há client_key na URL, permite navegação (rota pública)
    if (!urlClientKey) return true;

    // Obtém o cliente atual do serviço
    const currentClient = this.clientService.getCurrentClient();

    // Se não há cliente atual OU o slug é diferente do da URL
    if (!currentClient || currentClient.slug !== urlClientKey) {
      try {
        // Tenta carregar as informações do cliente da URL
        await this.loadAndSwitchClient(urlClientKey);
        return true;
      } catch (error) {
        console.error('Erro ao carregar cliente:', error);
        // Se falhar, redireciona para seleção de clientes
        this.router.navigate(['/home']);
        return false;
      }
    }

    // Cliente da URL coincide com o atual, permite navegação
    return true;
  }

  /**
   * Carrega informações do cliente e faz a troca
   */
  private async loadAndSwitchClient(clientKey: string): Promise<void> {
    try {

      const client = await this.clientService.getClientInfo(clientKey);

      if (!client) {
        throw new Error('Cliente não encontrado');
      }

      // Verifica se o slug retornado corresponde ao esperado
      if (client.slug !== clientKey) {
        throw new Error('Client key não corresponde ao cliente retornado');
      }

      // Faz o switch do cliente sem navegar (já estamos navegando)
      await this.clientNavigationService.switchClient(client, false);

    } catch (error) {
      console.error('Erro ao carregar e trocar cliente:', error);
      throw error;
    }
  }
}
