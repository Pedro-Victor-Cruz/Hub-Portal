import {inject, Injectable, Injector} from '@angular/core';
import {NavigationEnd, Router} from '@angular/router';
import {BehaviorSubject} from 'rxjs';
import {ReuseStrategyService} from '../services/reuse-strategy.service';
import {DashboardService} from '../services/dashboard.service';
import {ClientService} from '../services/client.service';

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
  private dashboardService = inject(DashboardService);
  private injector?: Injector;

  constructor(private router: Router, injector: Injector) {
    this.injector = injector;

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

  public getCurrentRoute(): string {
    return this.currentRoute;
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
   * Remove rotas dinâmicas (dashboards) da lista
   */
  public removeDynamicRoutes(): void {
    this.availableRoutes = this.availableRoutes.filter(r => r.category !== 'dashboard' || r.path === '/dashboards' || r.path === '/dashboard-templates');
    this.availableRoutesSubject.next([...this.availableRoutes]);
  }

  /**
   * Recarrega os dashboards navegáveis
   */
  public async reloadDashboards(): Promise<void> {
    try {
      // Remove dashboards antigos
      this.removeDynamicRoutes();

      // Carrega novos dashboards
      const response = await this.dashboardService.getNavigableDashboards();

      if (response && response.data) {
        const dashboardRoutes: Tab[] = response.data.map((dash: any) => ({
          title: dash.name,
          description: dash.description || 'Dashboard personalizado',
          icon: dash.icon ? 'bx ' + dash.icon : 'bx bx-bar-chart-alt-2',
          path: `/dashboard/${dash.key}`,
          category: 'dashboard' as const
        }));

        this.addMultipleRoutes(dashboardRoutes);
      }

    } catch (error) {
      console.error('Erro ao recarregar dashboards:', error);
    }
  }

  /**
   * Extrai o path raiz de uma URL completa, removendo o client_key se presente
   */
  private extractRootPath(fullPath: string): string {
    const pathWithoutQuery = fullPath.split('?')[0];
    let segments = pathWithoutQuery.split('/').filter(Boolean);

    if (segments.length > 0) {
      const firstSegment = segments[0];
      const isKnownRoute = this.availableRoutes.some(r => r.path === '/' + firstSegment);
      if (!isKnownRoute && segments.length > 1) {
        segments = segments.slice(1);
      }
    }

    return segments.length > 0 ? '/' + segments[0] : pathWithoutQuery;
  }

  private updateTabLastRoute(fullUrl: string): void {
    const pathWithoutQuery = fullUrl.split('?')[0];
    const rootPath = this.extractRootPath(pathWithoutQuery);

    const tab = this.open_tabs.find(t => t.path === rootPath);
    if (tab) {
      const segments = pathWithoutQuery.split('/').filter(Boolean);
      const isKnownRoute = this.availableRoutes.some(r => r.path === '/' + segments[0]);
      tab.lastRoute = !isKnownRoute && segments.length > 1
        ? '/' + segments.slice(1).join('/')
        : pathWithoutQuery;

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
        this.addTab(route.path, false);
      }
    }
  }

  addTab(path: string, navigate: boolean = true): void {
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
        this.router.navigate([this.buildUrlWithClientKey(path)]);
      }
    }
  }

  navigateToTab(tab: TabOpen): void {
    const targetRoute = tab.lastRoute || tab.path;
    const queryParams = tab.lastQueryParams || {};
    const fullPath = this.buildUrlWithClientKey(targetRoute);

    if (Object.keys(queryParams).length > 0) {
      this.router.navigate([fullPath], {queryParams});
    } else {
      this.router.navigate([fullPath]);
    }
  }

  /**
   * Constrói a URL completa adicionando o client_key
   * Prioridade: 1) URL atual, 2) ClientService
   */
  private buildUrlWithClientKey(path: string): string {
    // Tenta obter o client_key da URL atual primeiro
    let clientKey = this.extractClientKeyFromCurrentUrl();

    // Se não encontrou na URL, tenta obter do ClientService
    if (!clientKey && this.injector) {
      try {
        const clientService = this.injector.get(ClientService);
        const currentClient = clientService?.getCurrentClient();
        clientKey = currentClient?.slug || null;
      } catch (error) {
        console.warn('Não foi possível obter ClientService:', error);
      }
    }

    if (!clientKey) {
      return path;
    }

    const cleanPath = path.startsWith('/') ? path.substring(1) : path;

    if (cleanPath.startsWith(clientKey + '/')) {
      return '/' + cleanPath;
    }

    return `/${clientKey}/${cleanPath}`;
  }

  /**
   * Extrai o client_key da URL atual do router
   */
  private extractClientKeyFromCurrentUrl(): string | null {
    const url = this.router.url;
    const segments = url.split('/').filter(Boolean);

    if (segments.length > 0) {
      const firstSegment = segments[0].split('?')[0];

      // Lista de rotas públicas conhecidas
      const publicRoutes = ['auth', 'dashboard', 'home'];
      const isKnownRoute = this.availableRoutes.some(r => r.path === '/' + firstSegment) ||
        publicRoutes.includes(firstSegment);

      if (!isKnownRoute) {
        return firstSegment;
      }
    }

    return null;
  }

  closeTab(tabId: string): void {
    const index = this.open_tabs.findIndex(tab => tab.id === tabId);
    if (index === -1) return;

    const tab = this.open_tabs[index];
    const currentUrlSegments = this.currentRoute.split('/').filter(Boolean);
    const isKnownRoute = this.availableRoutes.some(r => r.path === '/' + currentUrlSegments[0]);
    const currentPathWithoutClientKey = !isKnownRoute && currentUrlSegments.length > 1
      ? '/' + currentUrlSegments.slice(1).join('/')
      : this.currentRoute;

    const isActive = currentPathWithoutClientKey.startsWith(tab.path);

    this.open_tabs.splice(index, 1);
    this.saveTabsToStorage();

    if (isActive && this.open_tabs.length > 0) {
      const nextTab = this.open_tabs[Math.max(0, index - 1)];
      this.navigateToTab(nextTab);
    } else if (this.open_tabs.length === 0) {
      const clientKey = this.extractClientKeyFromCurrentUrl();
      if (!clientKey && this.injector) {
        try {
          const clientService = this.injector.get(ClientService);
          const currentClient = clientService?.getCurrentClient();
          const slug = currentClient?.slug;
          if (slug) {
            this.router.navigate([slug, 'home']);
            return;
          }
        } catch (error) {
          console.warn('Erro ao obter cliente:', error);
        }
      }

      if (clientKey) {
        this.router.navigate([clientKey, 'home']);
      } else {
        this.router.navigate(['/home']);
      }
    }

    try {
      if (this.reuseStrategy) this.reuseStrategy.clearTabRoutes(tab.path);
    } catch (error) {
      console.warn('Não foi possível limpar cache da tab:', error);
    }
  }

  private saveTabsToStorage(): void {
    localStorage.setItem('open_tabs', JSON.stringify(this.open_tabs));
  }

  private generateUniqueId(): string {
    return Math.random().toString(36).substr(2, 9);
  }

  public isTabRoute(path: string): boolean {
    return this.availableRoutes.some(route => route.path === path);
  }

  public getActiveTab(): TabOpen | null {
    const rootPath = this.extractRootPath(this.currentRoute);
    return this.open_tabs.find(t => t.path === rootPath) || null;
  }
}
