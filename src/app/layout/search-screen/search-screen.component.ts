import { Component, OnInit } from '@angular/core';
import { NgForOf, NgIf } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { LayoutService } from '../layout.service';
import { AuthService } from '../../security/auth.service';

interface SearchItem {
  path: string;
  title: string;
  icon?: string;
  description?: string;
  permission?: string;
}

@Component({
  selector: 'search-screen',
  imports: [NgForOf, NgIf, FormsModule],
  templateUrl: './search-screen.component.html',
  standalone: true,
  styleUrls: ['./search-screen.component.scss']
})
export class SearchScreenComponent implements OnInit {
  searchQuery: string = '';
  isSearchModalActive = false;
  isSearchText = false;
  recentSearches: SearchItem[] = [];
  favoritePages: SearchItem[] = [];
  filteredResults: SearchItem[] = [];

  private readonly MAX_RECENT_SEARCHES = 10;
  private readonly STORAGE_KEYS = {
    RECENT_SEARCHES: 'recent_searches',
    FAVORITE_PAGES: 'favorite_pages'
  };

  constructor(
    private router: Router,
    protected layoutService: LayoutService,
    private auth: AuthService
  ) {}

  ngOnInit(): void {
    this.loadFromLocalStorage();
  }

  private loadFromLocalStorage(): void {
    const recent = localStorage.getItem(this.STORAGE_KEYS.RECENT_SEARCHES);
    const favorites = localStorage.getItem(this.STORAGE_KEYS.FAVORITE_PAGES);

    this.recentSearches = recent ? JSON.parse(recent) : [];
    this.favoritePages = favorites ? JSON.parse(favorites) : [];
  }

  private saveToLocalStorage(): void {
    localStorage.setItem(this.STORAGE_KEYS.RECENT_SEARCHES, JSON.stringify(this.recentSearches));
    localStorage.setItem(this.STORAGE_KEYS.FAVORITE_PAGES, JSON.stringify(this.favoritePages));
  }

  handleSearch(): void {
    if (this.searchQuery.trim() === '') {
      this.filteredResults = [];
      return;
    }

    this.filteredResults = this.layoutService.getAvailableRoutes()
      .filter(route =>
        route.title.toLowerCase().includes(this.searchQuery.toLowerCase()) &&
        this.auth.hasPermission(route.permission || ''));
  }

  navigate(path: string): void {
    const route = this.layoutService.getAvailableRoutes().find(r => r.path === path);
    if (route) {
      this.addToRecentSearches(route);
    }
    this.router.navigate([path]);
    this.closeAll();
  }

  private addToRecentSearches(item: SearchItem): void {
    this.recentSearches = this.recentSearches.filter(search => search.path !== item.path);
    this.recentSearches.unshift({
      path: item.path,
      title: item.title,
      icon: item.icon
    });

    if (this.recentSearches.length > this.MAX_RECENT_SEARCHES) {
      this.recentSearches.pop();
    }

    this.saveToLocalStorage();
  }

  toggleSearch(): void {
    this.isSearchModalActive = !this.isSearchModalActive;
    if (this.isSearchModalActive) {
      this.isSearchText = false;
      this.searchQuery = '';
      this.filteredResults = [];
    }
  }

  onSearchFocus(): void {
    this.isSearchText = true;
    this.searchQuery = '';
    this.filteredResults = [];
  }

  onSearchBlur(): void {
    setTimeout(() => {
      if (!this.isSearchModalActive) {
        this.isSearchText = false;
      }
    }, 200);
  }

  isFavorite(item: SearchItem): boolean {
    return this.favoritePages.some(fav => fav.path === item.path);
  }

  toggleFavorite(item: SearchItem, event?: Event): void {
    if (event) event.stopPropagation();

    if (this.isFavorite(item)) {
      this.favoritePages = this.favoritePages.filter(fav => fav.path !== item.path);
    } else {
      this.favoritePages.unshift({
        path: item.path,
        title: item.title,
        icon: item.icon
      });
    }
    this.saveToLocalStorage();
  }

  private closeAll(): void {
    this.isSearchText = false;
    this.isSearchModalActive = false;
    this.searchQuery = '';
    this.filteredResults = [];
  }
}
