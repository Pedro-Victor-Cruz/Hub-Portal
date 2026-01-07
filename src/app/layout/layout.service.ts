import {inject, Injectable} from '@angular/core';
import {NavigationEnd, Router} from '@angular/router';
import {BehaviorSubject} from 'rxjs';
import {ReuseStrategyService} from '../services/reuse-strategy.service';

export interface Tab {
  title: string;
  path: string;
  icon?: string;
  description?: string;
  permission?: string;
  category?: 'system' | 'dashboard' | 'management' | 'reports';
}

export interface TabOpen extends Tab {
  id: string;
  lastRoute?: string;
  lastQueryParams?: any;
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
      description: 'Acesse e controle os usuários cadastrados no sistema',
      permission: 'user.view',
      category: 'management'
    },
    {
      title: 'Integrações',
      path: '/integrations',
      icon: 'bx bx-plug',
      description: 'Gerencie integrações com serviços externos',
      permission: 'integration.manage',
      category: 'system'
    },
    {
      title: 'Permissões',
      path: '/permissions',
      icon: 'bx bx-lock',
      description: 'Defina regras de acesso e controle de funcionalidades',
      permission: 'permission.view',
      category: 'management'
    },
    {
      title: 'Grupos',
      path: '/groups',
      icon: 'bx bx-group',
      description: 'Organize permissões em grupos e associe aos usuários',
      permission: 'permission_group.view',
      category: 'management'
    },
    {
      title: 'Consultas Dinâmicas',
      path: '/dynamic-queries',
      icon: 'bx bx-search',
      description: 'Crie e execute consultas personalizadas',
      permission: 'dynamic_query.view',
      category: 'management'
    },
    {
      title: 'Parâmetros',
      path: '/parameters',
      icon: 'bx bx-cog',
      description: 'Configure preferências e ajustes do sistema',
      permission: 'parameter.view',
      category: 'system'
    },
    {
      title: 'Construtor de Dashboards',
      path: '/dashboards',
      icon: 'bx bx-bar-chart-alt-2',
      description: 'Construa e visualize dashboards personalizados',
      permission: 'dashboard.view',
      category: 'dashboard'
    },
    {
      title: 'Modelos de Dashboards',
      path: '/dashboard-templates',
      icon: 'bx bx-grid-alt',
      description: 'Explore e importe modelos pré-definidos de dashboards',
      category: 'dashboard'
    },
    {
      title: 'Logs do Sistema',
      path: '/logs',
      icon: 'bx bx-file',
      description: 'Monitore atividades e eventos do sistema',
      permission: 'log.view',
      category: 'reports'
    },
    {
      title: 'Desempenho do Sistema',
      path: '/system-performance',
      icon: 'bx bx-tachometer',
      description: 'Acompanhe métricas e saúde do sistema em tempo real',
      permission: 'system_performance.view',
      category: 'reports'
    }
  ];

  private availableRoutesSubject = new BehaviorSubject<Tab[]>(this.availableRoutes);
  public availableRoutes$ = this.availableRoutesSubject.asObservable();

  private open_tabs: TabOpen[] = [];
  private currentRoute: string = '';
  private reuseStrategy = inject(ReuseStrategyService);

  constructor(
    private router: Router
  ) {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        this.currentRoute = event.url.split('?')[0];
        this.syncTabsWithRoute(event.url);
        this.updateTabLastRoute(event.url);
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
    return this.availableRoutes;
  }

  public getRoutesByCategory(category: string): Tab[] {
    return this.availableRoutes.filter(route => route.category === category);
  }

  public getCurrentRoute(): string {
    return this.currentRoute;
  }

  public addAvailableRoute(route: Tab): void {
    const exists = this.availableRoutes.some(r => r.path === route.path);
    if (!exists) {
      this.availableRoutes.push(route);
      this.availableRoutesSubject.next([...this.availableRoutes]);
    }
  }

  public addMultipleRoutes(routes: Tab[]): void {
    let hasChanges = false;
    routes.forEach(route => {
      const exists = this.availableRoutes.some(r => r.path === route.path);
      if (!exists) {
        this.availableRoutes.push(route);
        hasChanges = true;
      }
    });

    if (hasChanges) {
      this.availableRoutesSubject.next([...this.availableRoutes]);
    }
  }

  /**
   * Extrai o path raiz de uma URL completa
   * /users/manage/123 -> /users
   */
  private extractRootPath(fullPath: string): string {
    const pathWithoutQuery = fullPath.split('?')[0];
    const segments = pathWithoutQuery.split('/').filter(Boolean);
    return segments.length > 0 ? '/' + segments[0] : pathWithoutQuery;
  }

  /**
   * Atualiza a última rota acessada dentro de uma tab
   */
  private updateTabLastRoute(fullUrl: string): void {
    const pathWithoutQuery = fullUrl.split('?')[0];
    const rootPath = this.extractRootPath(pathWithoutQuery);

    const tab = this.open_tabs.find(t => t.path === rootPath);
    if (tab) {
      tab.lastRoute = pathWithoutQuery;

      // Extrai queryParams da URL
      const urlParts = fullUrl.split('?');
      if (urlParts.length > 1) {
        const params = new URLSearchParams(urlParts[1]);
        tab.lastQueryParams = Object.fromEntries(params.entries());
      } else {
        tab.lastQueryParams = {};
      }

      this.saveTabsToStorage();
    }
  }

  private syncTabsWithRoute(currentPath: string): void {
    const normalizedPath = currentPath.split('?')[0];
    const rootPath = this.extractRootPath(normalizedPath);

    const existingTab = this.open_tabs.find(tab => tab.path === rootPath);
    if (!existingTab) {
      const route = this.availableRoutes.find(r => r.path === rootPath);
      if (route) {
        this.addTab(route.path, route.title, false);
      }
    }
  }

  /**
   * Adiciona uma tab
   * Se navigate = true, vai para a última rota conhecida ou para o path raiz
   */
  addTab(path: string, title: string, navigate: boolean = true): void {
    const rootPath = this.extractRootPath(path);

    if (!this.open_tabs.some((tab) => tab.path === rootPath)) {
      const tab = this.availableRoutes.find((route) => route.path === rootPath);
      if (tab) {
        this.open_tabs.push({
          id: this.generateUniqueId(),
          ...tab,
          lastRoute: rootPath,
          lastQueryParams: {}
        });
        this.saveTabsToStorage();
      }
    }

    if (navigate) {
      const openedTab = this.open_tabs.find(t => t.path === rootPath);
      if (openedTab) {
        this.navigateToTab(openedTab);
      } else {
        this.router.navigate([path]);
      }
    }
  }

  /**
   * Navega para uma tab, restaurando a última rota acessada
   */
  navigateToTab(tab: TabOpen): void {
    const targetRoute = tab.lastRoute || tab.path;
    const queryParams = tab.lastQueryParams || {};

    if (Object.keys(queryParams).length > 0) {
      this.router.navigate([targetRoute], {queryParams});
    } else {
      this.router.navigate([targetRoute]);
    }
  }

  /**
   * Fecha uma tab e limpa seu cache
   */
  closeTab(tabId: string): void {
    const index = this.open_tabs.findIndex(tab => tab.id === tabId);
    if (index === -1) return;

    const tab = this.open_tabs[index];
    const isActive = this.currentRoute.startsWith(tab.path);

    // Remove a tab
    this.open_tabs.splice(index, 1);
    this.saveTabsToStorage();

    // Se estava ativa, navega para outra tab
    if (isActive && this.open_tabs.length > 0) {
      const nextTab = this.open_tabs[Math.max(0, index - 1)];
      this.navigateToTab(nextTab);
    } else if (this.open_tabs.length === 0) {
      this.router.navigate(['/']);
    }

    // Tenta limpar o cache apenas se a estratégia estiver disponível
    try {
      if (this.reuseStrategy) this.reuseStrategy.clearTabRoutes(tab.path);
    } catch (error) {
      console.warn('Não foi possível limpar cache da tab:', error);
    }
  }

  /**
   * Salva tabs no localStorage
   */
  private saveTabsToStorage(): void {
    localStorage.setItem('open_tabs', JSON.stringify(this.open_tabs));
  }

  private generateUniqueId(): string {
    return Math.random().toString(36).substr(2, 9);
  }

  public isTabRoute(path: string): boolean {
    return this.availableRoutes.some(route => route.path === path);
  }

  /**
   * Obtém a tab ativa atualmente
   */
  public getActiveTab(): TabOpen | null {
    const rootPath = this.extractRootPath(this.currentRoute);
    return this.open_tabs.find(t => t.path === rootPath) || null;
  }
}
