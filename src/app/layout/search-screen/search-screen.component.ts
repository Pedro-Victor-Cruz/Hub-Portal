import { Component, OnInit, OnDestroy, HostListener } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { LayoutService, Tab } from '../layout.service';
import { AuthService } from '../../security/auth.service';
import { DashboardService } from '../../services/dashboard.service';
import { ClientService } from '../../services/client.service';
import { ClientNavigationService } from '../../services/client-navigation.service';
import { Utils } from '../../services/utils.service';
import { Subscription } from 'rxjs';
import { CommonModule } from '@angular/common';
import { trigger, transition, style, animate } from '@angular/animations';
import { Client } from '../../models/user';

interface SearchItem extends Tab {}

interface CategoryGroup {
  id: string;
  name: string;
  icon: string;
  items: SearchItem[];
}

@Component({
  selector: 'search-screen',
  imports: [FormsModule, CommonModule],
  templateUrl: './search-screen.component.html',
  standalone: true,
  styleUrls: ['./search-screen.component.scss'],
  animations: [
    trigger('fadeIn', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(10px)' }),
        animate('200ms ease-out', style({ opacity: 1, transform: 'translateY(0)' }))
      ])
    ])
  ]
})
export class SearchScreenComponent implements OnInit, OnDestroy {
  searchQuery: string = '';
  isModalOpen = false;
  isSearching = false;
  recentSearches: SearchItem[] = [];
  favoritePages: SearchItem[] = [];
  filteredResults: SearchItem[] = [];
  allPages: SearchItem[] = [];
  categorizedPages: CategoryGroup[] = [];
  isLoadingDashboards = false;
  selectedIndex = 0;
  activeTab: 'all' | 'favorites' | 'recent' = 'all';

  // Controle de clientes
  clients: Client[] = [];
  filteredClients: Client[] = [];
  currentClient: Client | null = null;
  isClientDropdownOpen = false;
  clientSearchQuery = '';
  isLoadingClients = false;

  private readonly MAX_RECENT_SEARCHES = 8;
  private readonly DEBOUNCE_TIME = 300;
  private readonly STORAGE_KEYS = {
    RECENT_SEARCHES: 'recent_searches',
    FAVORITE_PAGES: 'favorite_pages',
    LAST_ACTIVE_TAB: 'search_last_active_tab'
  } as const;

  private readonly CATEGORIES = {
    dashboard: { name: 'Dashboards', icon: 'bx bx-bar-chart-alt-2' },
    management: { name: 'Gerenciamento', icon: 'bx bx-user-circle' },
    system: { name: 'Sistema', icon: 'bx bx-cog' },
    reports: { name: 'Relatórios', icon: 'bx bx-file-find' }
  };

  private searchTimeout?: number;
  private routesSubscription?: Subscription;
  private clientSubscription?: Subscription;

  constructor(
    private router: Router,
    protected layoutService: LayoutService,
    private auth: AuthService,
    private dashboardService: DashboardService,
    private clientService: ClientService,
    private clientNavigation: ClientNavigationService
  ) {}

  @HostListener('document:keydown', ['$event'])
  handleKeyboardEvent(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
      event.preventDefault();
      this.openModal();
      return;
    }

    if (event.key === 'Escape') {
      if (this.isClientDropdownOpen) {
        event.preventDefault();
        this.closeClientDropdown();
        return;
      }

      if (this.isModalOpen) {
        event.preventDefault();
        this.closeModal();
        return;
      }
    }

    if (!this.isModalOpen || this.isClientDropdownOpen) return;

    const displayItems = this.getDisplayItems();
    if (displayItems.length === 0) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        this.selectedIndex = Math.min(this.selectedIndex + 1, displayItems.length - 1);
        this.scrollToSelected();
        break;

      case 'ArrowUp':
        event.preventDefault();
        this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
        this.scrollToSelected();
        break;

      case 'Enter':
        event.preventDefault();
        const selectedItem = displayItems[this.selectedIndex];
        if (selectedItem) {
          this.navigate(selectedItem.path);
        }
        break;
    }
  }

  async ngOnInit(): Promise<void> {
    this.loadStoredData();
    await this.loadDashboardsNavigable();
    this.loadAllPages();
    this.subscribeToRouteChanges();
    this.subscribeToClient();
    await this.loadClients();
  }

  ngOnDestroy(): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    if (this.routesSubscription) {
      this.routesSubscription.unsubscribe();
    }
    if (this.clientSubscription) {
      this.clientSubscription.unsubscribe();
    }
  }

  private subscribeToClient(): void {
    this.clientSubscription = this.clientService.currentClient$.subscribe(client => {
      this.currentClient = client;
    });
  }

  private async loadClients(): Promise<void> {
    try {
      this.isLoadingClients = true;
      const response = await this.clientService.getClients();
      this.clients = response.data || [];
      this.filteredClients = [...this.clients];
    } catch (error) {
      console.error('Erro ao carregar clientes:', error);
      this.clients = [];
      this.filteredClients = [];
    } finally {
      this.isLoadingClients = false;
    }
  }

  filterClients(): void {
    const query = Utils.prepareSearchQuery(this.clientSearchQuery.trim());

    if (!query) {
      this.filteredClients = [...this.clients];
      return;
    }

    this.filteredClients = this.clients.filter(client => {
      const nameMatch = Utils.prepareSearchQuery(client.name).includes(query);
      const slugMatch = Utils.prepareSearchQuery(client.slug || '').includes(query);
      return nameMatch || slugMatch;
    });
  }

  async selectClient(client: Client, event?: Event): Promise<void> {
    if (event) {
      event.stopPropagation();
    }

    if (!client.active) {
      return;
    }

    try {
      await this.clientNavigation.switchClient(client);
      this.closeClientDropdown();
      this.closeModal();
    } catch (error) {
      console.error('Erro ao trocar cliente:', error);
    }
  }

  toggleClientDropdown(): void {
    this.isClientDropdownOpen = !this.isClientDropdownOpen;
    if (this.isClientDropdownOpen) {
      this.clientSearchQuery = '';
      this.filteredClients = [...this.clients];
      setTimeout(() => {
        const input = document.querySelector('.client-search-input') as HTMLInputElement;
        if (input) input.focus();
      }, 100);
    }
  }

  closeClientDropdown(): void {
    this.isClientDropdownOpen = false;
    this.clientSearchQuery = '';
  }

  private async loadDashboardsNavigable(): Promise<void> {
    if (this.currentClient) {
      await this.layoutService.reloadDashboards();
    }
  }

  private subscribeToRouteChanges(): void {
    this.routesSubscription = this.layoutService.availableRoutes$.subscribe(routes => {
      this.loadAllPages();
      if (this.searchQuery.trim()) {
        this.performSearch();
      }
    });
  }

  private loadStoredData(): void {
    try {
      const recent = localStorage.getItem(this.STORAGE_KEYS.RECENT_SEARCHES);
      const favorites = localStorage.getItem(this.STORAGE_KEYS.FAVORITE_PAGES);
      const lastTab = localStorage.getItem(this.STORAGE_KEYS.LAST_ACTIVE_TAB);

      this.recentSearches = recent ? JSON.parse(recent) : [];
      this.favoritePages = favorites ? JSON.parse(favorites) : [];

      if (lastTab && ['all', 'favorites', 'recent'].includes(lastTab)) {
        this.activeTab = lastTab as 'all' | 'favorites' | 'recent';
      }
    } catch (error) {
      console.warn('Erro ao carregar dados do localStorage:', error);
      this.recentSearches = [];
      this.favoritePages = [];
    }
  }

  private loadAllPages(): void {
    this.allPages = this.layoutService.getAvailableRoutes()
      .filter(route => this.auth.hasPermission(route.permission || ''))
      .sort((a, b) => a.title.localeCompare(b.title));

    this.categorizePages();
  }

  private categorizePages(): void {
    const grouped = new Map<string, SearchItem[]>();

    this.allPages.forEach(page => {
      const category = page.category || 'system';
      if (!grouped.has(category)) {
        grouped.set(category, []);
      }
      grouped.get(category)!.push(page);
    });

    this.categorizedPages = Array.from(grouped.entries()).map(([id, items]) => ({
      id,
      name: this.CATEGORIES[id as keyof typeof this.CATEGORIES]?.name || 'Outros',
      icon: this.CATEGORIES[id as keyof typeof this.CATEGORIES]?.icon || 'bx bx-folder',
      items: items.sort((a, b) => a.title.localeCompare(b.title))
    }));

    this.categorizedPages.sort((a, b) => {
      if (a.id === 'dashboard') return -1;
      if (b.id === 'dashboard') return 1;
      return a.name.localeCompare(b.name);
    });
  }

  private saveToLocalStorage(): void {
    try {
      localStorage.setItem(this.STORAGE_KEYS.RECENT_SEARCHES, JSON.stringify(this.recentSearches));
      localStorage.setItem(this.STORAGE_KEYS.FAVORITE_PAGES, JSON.stringify(this.favoritePages));
      localStorage.setItem(this.STORAGE_KEYS.LAST_ACTIVE_TAB, this.activeTab);
    } catch (error) {
      console.warn('Erro ao salvar no localStorage:', error);
    }
  }

  handleSearch(): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }

    this.isSearching = true;

    this.searchTimeout = window.setTimeout(() => {
      this.performSearch();
      this.selectedIndex = 0;
      this.isSearching = false;
    }, this.DEBOUNCE_TIME);
  }

  private performSearch(): void {
    const query = this.searchQuery.trim();

    if (!query) {
      this.filteredResults = [];
      return;
    }

    const searchTerm = Utils.prepareSearchQuery(query);

    this.filteredResults = this.allPages.filter(page => {
      const titleMatch = Utils.prepareSearchQuery(page.title).includes(searchTerm);
      const descMatch = page.description ?
        Utils.prepareSearchQuery(page.description).includes(searchTerm) : false;

      return titleMatch || descMatch;
    });
  }

  navigate(path: string, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }

    // Só permite navegar se tiver um cliente selecionado
    if (!this.currentClient) {
      return;
    }

    const page = this.allPages.find(p => p.path === path);
    if (page) {
      this.addToRecentSearches(page);
      this.layoutService.addTab(page.path);
    }
    this.closeModal();
  }

  private addToRecentSearches(item: SearchItem): void {
    this.recentSearches = this.recentSearches.filter(search => search.path !== item.path);
    this.recentSearches.unshift({
      path: item.path,
      title: item.title,
      icon: item.icon,
      description: item.description,
      category: item.category
    });

    if (this.recentSearches.length > this.MAX_RECENT_SEARCHES) {
      this.recentSearches = this.recentSearches.slice(0, this.MAX_RECENT_SEARCHES);
    }

    this.saveToLocalStorage();
  }

  openModal(): void {
    this.isModalOpen = true;
    this.searchQuery = '';
    this.filteredResults = [];
    this.selectedIndex = 0;
    setTimeout(() => this.focusSearchInput(), 100);
  }

  closeModal(): void {
    this.isModalOpen = false;
    this.searchQuery = '';
    this.filteredResults = [];
    this.selectedIndex = 0;
    this.isSearching = false;
    this.closeClientDropdown();
  }

  private focusSearchInput(): void {
    const input = document.querySelector('.search-modal input') as HTMLInputElement;
    if (input) {
      input.focus();
    }
  }

  isFavorite(item: SearchItem): boolean {
    return this.favoritePages.some(fav => fav.path === item.path);
  }

  toggleFavorite(item: SearchItem, event: Event): void {
    event.preventDefault();
    event.stopPropagation();

    const favoriteIndex = this.favoritePages.findIndex(fav => fav.path === item.path);

    if (favoriteIndex >= 0) {
      this.favoritePages.splice(favoriteIndex, 1);
    } else {
      this.favoritePages.unshift({
        path: item.path,
        title: item.title,
        icon: item.icon,
        description: item.description,
        category: item.category
      });
    }

    this.saveToLocalStorage();
  }

  clearRecentSearches(): void {
    this.recentSearches = [];
    this.saveToLocalStorage();
    this.setActiveTab('all');
  }

  clearFavorites(): void {
    this.favoritePages = [];
    this.saveToLocalStorage();
    this.setActiveTab('all');
  }

  setActiveTab(tab: 'all' | 'favorites' | 'recent'): void {
    this.activeTab = tab;
    this.selectedIndex = 0;
    this.saveToLocalStorage();
  }

  private getDisplayItems(): SearchItem[] {
    if (this.searchQuery.trim()) {
      return this.filteredResults;
    }

    switch (this.activeTab) {
      case 'favorites':
        return this.favoritePages;
      case 'recent':
        return this.recentSearches;
      default:
        return [];
    }
  }

  private scrollToSelected(): void {
    setTimeout(() => {
      const selected = document.querySelector('.result-item.selected');
      if (selected) {
        selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }, 0);
  }

  get hasSearchQuery(): boolean {
    return this.searchQuery.trim().length > 0;
  }

  get displayItems(): SearchItem[] {
    return this.getDisplayItems();
  }

  get showTabs(): boolean {
    return !this.hasSearchQuery;
  }

  get hasClientSelected(): boolean {
    return this.currentClient !== null;
  }

  get canNavigate(): boolean {
    return this.hasClientSelected;
  }
}
