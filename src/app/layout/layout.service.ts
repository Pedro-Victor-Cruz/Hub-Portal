import { Injectable } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { CustomRouteReuseStrategy } from '../custom-route-reuse-strategy';

interface Tab {
  title: string;
  path: string;
  icon?: string;
  description?: string;
  permission?: string;
}

export interface TabOpen extends Tab {
  id: string;
}

@Injectable({
  providedIn: 'root',
})
export class LayoutService {
  private availableRoutes: Tab[] = [
    {
      title: 'Usuários',
      path: '/users',
      icon: 'bx bx-user',
      permission: 'user.view'
    },
    {
      title: 'Empresas',
      path: '/companies',
      icon: 'bx bx-building',
      permission: 'company.view'
    },
    {
      title: 'Permissões',
      path: '/permissions',
      icon: 'bx bx-lock',
      permission: 'permission.view'
    },
    {
      title: 'Grupos',
      path: '/groups',
      icon: 'bx bx-group',
      permission: 'permission_group.view'
    }
  ];
  private open_tabs: TabOpen[] = [];
  private currentRoute: string = '';

  constructor(
    private router: Router,
    private routeReuseStrategy: CustomRouteReuseStrategy
  ) {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        this.currentRoute = event.url;
        this.syncTabsWithRoute(event.url);
      }
    });

    const open_tabs = localStorage.getItem('open_tabs');
    if (open_tabs) {
      this.open_tabs = JSON.parse(open_tabs);
    }
  }

  public setOpenTabs(tabs: TabOpen[]): void {
    this.open_tabs = tabs;
    localStorage.setItem('open_tabs', JSON.stringify(tabs));
  }

  public getOpenTabs(): TabOpen[] {
    return this.open_tabs;
  }

  public getAvailableRoutes(): Tab[] {
    return this.availableRoutes
  }

  public getCurrentRoute(): string {
    return this.currentRoute;
  }

  /**
   * Sincroniza as abas abertas com a navegação atual.
   */
  private syncTabsWithRoute(currentPath: string): void {
    const existingTab = this.open_tabs.find((tab) => tab.path === currentPath);
    if (!existingTab) {
      const route = this.availableRoutes.find((route) => route.path === currentPath);
      if (route) {
        this.addTab(route.path, route.title, false);
      }
    }
  }

  /**
   * Adiciona uma nova aba se ela ainda não estiver aberta.
   */
  addTab(path: string, title: string, navigate: boolean = true): void {
    if (!this.open_tabs.some((tab) => tab.path === path)) {
      const tab = this.availableRoutes.find((route) => route.path === path);
      this.open_tabs.push({ id: this.generateUniqueId(), ...tab! });
      localStorage.setItem('open_tabs', JSON.stringify(this.open_tabs));
    }

    if (navigate) {
      this.router.navigate([path]);
    }
  }

  /**
   * Fecha uma aba e redireciona para uma aba válida se necessário.
   */
  closeTab(tabId: string): void {
    const index = this.open_tabs.findIndex((tab) => tab.id === tabId);
    if (index === -1) return;

    const tabToClose = this.open_tabs[index];
    const isActiveTab = tabToClose.path === this.currentRoute;

    // Remove a aba da lista de abas abertas
    this.open_tabs.splice(index, 1);
    localStorage.setItem('open_tabs', JSON.stringify(this.open_tabs));

    // Notifica a estratégia de reutilização para limpar a rota armazenada
    this.routeReuseStrategy.clearStoredRoute(tabToClose.path);

    if (this.open_tabs.length > 0) {
      // Se a aba fechada for a ativa, redireciona para a anterior ou a primeira aba
      if (isActiveTab) {
        const newIndex = Math.max(0, index - 1);
        this.router.navigate([this.open_tabs[newIndex].path]);
      }
    } else {
      this.router.navigate(['/']);
    }
  }

  /**
   * Gera um ID único para cada aba.
   */
  private generateUniqueId(): string {
    return Math.random().toString(36).substr(2, 9);
  }
}
