import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ClientService } from './client.service';
import { LayoutService } from '../layout/layout.service';
import { AuthService } from '../security/auth.service';
import { ReuseStrategyService } from './reuse-strategy.service';
import { Client } from '../models/user';

@Injectable({
  providedIn: 'root'
})
export class ClientNavigationService {

  constructor(
    private router: Router,
    private clientService: ClientService,
    private layoutService: LayoutService,
    private authService: AuthService,
    private reuseStrategy: ReuseStrategyService
  ) {}

  /**
   * Troca o cliente atual e realiza todas as atualizações necessárias
   */
  async switchClient(client: Client, navigate: boolean = true): Promise<void> {
    try {
      // 1. Define o novo cliente
      this.clientService.setCurrentClient(client);

      // 2. Limpa o cache de rotas
      this.clearRouteCache();

      // 3. Fecha todas as tabs abertas
      this.layoutService.setOpenTabs([]);

      // 4. Atualiza informações do usuário
      this.authService.refreshUserData();

      // 5. Recarrega os dashboards navegáveis
      await this.layoutService.reloadDashboards();

      if (navigate) {
        // 6. Navega para a home do cliente
        await this.router.navigate([`/${client.slug}/home`]);
      }

    } catch (error) {
      console.error('Erro ao trocar cliente:', error);
      throw error;
    }
  }

  /**
   * Carrega informações do cliente atual baseado na URL
   */
  async loadCurrentClientFromUrl(): Promise<void> {
    const clientKey = this.extractClientKeyFromUrl();

    if (!clientKey) {
      this.clientService.clearCurrentClient();
      return;
    }

    try {
      // Busca informações do cliente via API
      const client = await this.clientService.getClientInfo(clientKey);

      if (client) {
        // Atualiza informações do usuário
        this.authService.refreshUserData();

        // Recarrega dashboards
        await this.layoutService.reloadDashboards();
      } else {
        // Se não conseguiu carregar, vai para home principal
        this.router.navigate(['/home']);
      }
    } catch (error) {
      console.error('Erro ao carregar cliente da URL:', error);
      this.router.navigate(['/home']);
    }
  }

  /**
   * Limpa todo o cache de rotas
   */
  private clearRouteCache(): void {
    try {
      if (this.reuseStrategy) {
        this.reuseStrategy.clearAll();
      }
    } catch (error) {
      console.warn('Não foi possível limpar cache de rotas:', error);
    }
  }

  /**
   * Extrai o client_key da URL atual
   */
  private extractClientKeyFromUrl(): string | null {
    const url = this.router.url;
    const segments = url.split('/').filter(Boolean);

    if (segments.length > 0) {
      const firstSegment = segments[0].split('?')[0];
      const publicRoutes = ['auth', 'dashboard', 'home'];

      if (!publicRoutes.includes(firstSegment)) {
        return firstSegment;
      }
    }

    return null;
  }

  /**
   * Verifica se está em um contexto de cliente
   */
  isInClientContext(): boolean {
    return this.extractClientKeyFromUrl() !== null;
  }

  /**
   * Retorna para a seleção de clientes
   */
  async goToClientSelection(): Promise<void> {
    this.clientService.clearCurrentClient();
    this.clearRouteCache();
    this.layoutService.setOpenTabs([]);
    await this.router.navigate(['/home']);
  }
}
