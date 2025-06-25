import { Component } from '@angular/core';
import {NgForOf, NgIf} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {ClickOutsideDirective} from '../../directives/click-outside.directive';
import {Router} from '@angular/router';
import {LayoutService} from '../layout.service';
import {HasPermissionDirective} from '../../directives/has-permission.directive';
import {AuthService} from '../../security/auth.service';

@Component({
  selector: 'search-screen',
  imports: [
    NgForOf,
    NgIf,
    ReactiveFormsModule,
    FormsModule
  ],
  templateUrl: './search-screen.component.html',
  standalone: true,
  styleUrl: './search-screen.component.scss'
})
export class SearchScreenComponent {

  searchQuery: string = '';
  isSearchActive = false;

  constructor(
    private router: Router,
    protected layoutService: LayoutService,
    private auth: AuthService
  ) {
  }

  /**
   * Retorna as rotas filtradas com base na busca.
   */
  searchResults() {
    return this.layoutService.getAvailableRoutes().filter(route =>
      route.title.toLowerCase().includes(this.searchQuery.toLowerCase()) && this.auth.hasPermission(route.permission || '')
    );
  }

  navigate(path: string) {
    this.router.navigate([path]);
    this.isSearchActive = false; // Fecha a busca após a navegação
  }

  // Alterna a visibilidade da busca
  toggleSearch() {
    this.isSearchActive = !this.isSearchActive;
  }

  handleSearch() {

  }
}
