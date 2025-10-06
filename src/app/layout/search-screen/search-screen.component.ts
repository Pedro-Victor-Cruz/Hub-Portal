import { Component, OnInit, OnDestroy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { LayoutService } from '../layout.service';
import { AuthService } from '../../security/auth.service';
import { Utils } from '../../services/utils.service';

interface SearchItem {
  path: string;
  title: string;
  icon?: string;
  description?: string;
  permission?: string;
}

@Component({
  selector: 'search-screen',
  imports: [FormsModule],
  templateUrl: './search-screen.component.html',
  standalone: true,
  styleUrls: ['./search-screen.component.scss']
})
export class SearchScreenComponent implements OnInit, OnDestroy {
  searchQuery: string = '';
  isSearchModalActive = false;
  isSearchFocused = false;
  recentSearches: SearchItem[] = [];
  favoritePages: SearchItem[] = [];
  filteredResults: SearchItem[] = [];
  allPages: SearchItem[] = [];

  private readonly MAX_RECENT_SEARCHES = 8;
  private readonly STORAGE_KEYS = {
    RECENT_SEARCHES: 'recent_searches',
    FAVORITE_PAGES: 'favorite_pages'
  } as const;

  private searchTimeout?: number;
  private clickOutsideHandler?: (event: MouseEvent) => void;

  constructor(
    private router: Router,
    protected layoutService: LayoutService,
    private auth: AuthService
  ) {}

  ngOnInit(): void {
    this.loadStoredData();
    this.loadAllPages();
    this.setupClickOutsideListener();
  }

  ngOnDestroy(): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.removeClickOutsideListener();
  }

  private setupClickOutsideListener(): void {
    this.clickOutsideHandler = this.handleClickOutside.bind(this);
    document.addEventListener('click', this.clickOutsideHandler);
  }

  private removeClickOutsideListener(): void {
    if (this.clickOutsideHandler) {
      document.removeEventListener('click', this.clickOutsideHandler);
    }
  }

  private handleClickOutside(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    const searchContainer = target.closest('.selector-search');

    if (!searchContainer && this.isSearchFocused) {
      this.closeSearchResults();
    }
  }

  private loadStoredData(): void {
    try {
      const recent = localStorage.getItem(this.STORAGE_KEYS.RECENT_SEARCHES);
      const favorites = localStorage.getItem(this.STORAGE_KEYS.FAVORITE_PAGES);

      this.recentSearches = recent ? JSON.parse(recent) : [];
      this.favoritePages = favorites ? JSON.parse(favorites) : [];
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
  }

  private saveToLocalStorage(): void {
    try {
      localStorage.setItem(this.STORAGE_KEYS.RECENT_SEARCHES, JSON.stringify(this.recentSearches));
      localStorage.setItem(this.STORAGE_KEYS.FAVORITE_PAGES, JSON.stringify(this.favoritePages));
    } catch (error) {
      console.warn('Erro ao salvar no localStorage:', error);
    }
  }

  handleSearch(): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }

    this.searchTimeout = window.setTimeout(() => {
      this.performSearch();
    }, 150);
  }

  private performSearch(): void {
    const query = this.searchQuery.trim();

    if (!query) {
      this.filteredResults = this.isSearchModalActive ? this.allPages : [];
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

  navigate(path: string): void {
    const page = this.allPages.find(p => p.path === path);
    if (page) {
      this.addToRecentSearches(page);
      this.router.navigate([path]);
    }
    this.closeSearchResults();
  }

  private addToRecentSearches(item: SearchItem): void {
    // Remove se já existe
    this.recentSearches = this.recentSearches.filter(search => search.path !== item.path);

    // Adiciona no início
    this.recentSearches.unshift({
      path: item.path,
      title: item.title,
      icon: item.icon,
      description: item.description
    });

    // Mantém apenas os últimos MAX_RECENT_SEARCHES
    if (this.recentSearches.length > this.MAX_RECENT_SEARCHES) {
      this.recentSearches = this.recentSearches.slice(0, this.MAX_RECENT_SEARCHES);
    }

    this.saveToLocalStorage();
  }

  toggleSearch(): void {
    this.isSearchModalActive = !this.isSearchModalActive;

    if (this.isSearchModalActive) {
      this.searchQuery = '';
      this.filteredResults = this.allPages;
      // Foca no input após um pequeno delay para garantir que o modal está visível
      setTimeout(() => this.focusSearchInput(), 100);
    } else {
      this.closeAll();
    }
  }

  private focusSearchInput(): void {
    const input = document.querySelector('.selector-menu input') as HTMLInputElement;
    if (input) {
      input.focus();
    }
  }

  onSearchFocus(): void {
    this.isSearchFocused = true;
    if (!this.searchQuery.trim()) {
      this.filteredResults = [];
    }
  }

  onSearchBlur(event: FocusEvent): void {
    // Delay para permitir cliques nos resultados
    setTimeout(() => {
      const activeElement = document.activeElement as HTMLElement;
      const searchResults = document.querySelector('.search-results');

      // Mantém aberto se o foco está dentro dos resultados
      if (searchResults && searchResults.contains(activeElement)) {
        return;
      }

      this.isSearchFocused = false;
    }, 200);
  }

  isFavorite(item: SearchItem): boolean {
    return this.favoritePages.some(fav => fav.path === item.path);
  }

  toggleFavorite(item: SearchItem, event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    const favoriteIndex = this.favoritePages.findIndex(fav => fav.path === item.path);

    if (favoriteIndex >= 0) {
      // Remove dos favoritos
      this.favoritePages.splice(favoriteIndex, 1);
    } else {
      // Adiciona aos favoritos
      this.favoritePages.unshift({
        path: item.path,
        title: item.title,
        icon: item.icon,
        description: item.description
      });
    }

    this.saveToLocalStorage();
  }

  clearRecentSearches(): void {
    this.recentSearches = [];
    this.saveToLocalStorage();
  }

  clearFavorites(): void {
    this.favoritePages = [];
    this.saveToLocalStorage();
  }

  closeSearchResults(): void {
    this.isSearchFocused = false;
  }

  private closeAll(): void {
    this.isSearchFocused = false;
    this.isSearchModalActive = false;
    this.searchQuery = '';
    this.filteredResults = [];

    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
  }

  // Getter para verificar se deve mostrar os resultados de busca
  get showSearchResults(): boolean {
    return this.isSearchFocused && !this.isSearchModalActive;
  }

  // Getter para verificar se deve mostrar estado vazio
  get showEmptyState(): boolean {
    return this.searchQuery.trim() !== '' && this.filteredResults.length === 0;
  }
}
