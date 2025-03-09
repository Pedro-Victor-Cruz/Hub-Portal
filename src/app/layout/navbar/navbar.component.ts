import { Component, OnInit, ViewChild, ElementRef, HostListener, AfterViewInit } from '@angular/core';
import {CommonModule, NgOptimizedImage} from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { LayoutService } from '../layout.service';
import { AuthService } from '../../security/auth.service';
import { DropdownComponent } from '../../components/form/dropdown/dropdown.component';
import { SearchScreenComponent } from '../search-screen/search-screen.component';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, DropdownComponent, SearchScreenComponent, NgOptimizedImage]
})
export class NavbarComponent implements OnInit, AfterViewInit {
  isMobile: boolean = false;
  isMobileMenuOpen: boolean = false;
  isUserMenuOpen: boolean = false;
  showTabControls: boolean = false;
  canScrollLeft: boolean = false;
  canScrollRight: boolean = false;
  showMobileMenu = false;
  showScrollButtons = false;

  @ViewChild('tabsScroll') tabsScroll!: ElementRef;

  constructor(
    public router: Router,
    public layoutService: LayoutService,
    public auth: AuthService
  ) {}

  ngOnInit(): void {
    this.checkMobileView();
  }

  ngAfterViewInit(): void {
    this.checkTabsOverflow();
  }

  @HostListener('window:resize')
  onResize(): void {
    this.checkMobileView();
    this.checkTabsOverflow();
  }

  private checkMobileView(): void {
    this.isMobile = window.innerWidth < 768;
  }

  toggleMobileMenu(): void {
    this.showMobileMenu = !this.showMobileMenu;
    if (this.showMobileMenu) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }

  toggleUserMenu(): void {
    this.isUserMenuOpen = !this.isUserMenuOpen;
  }

  selectMobileTab(tab: any): void {
    this.router.navigate([tab.path]);
    this.toggleMobileMenu();
  }

  closeTab(id: string, event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    this.layoutService.closeTab(id);
  }

  getTabIcon(type: string): string {
    switch (type) {
      case 'department':
        return 'bx bx-building';
      case 'user':
        return 'bx bx-user';
      default:
        return 'bx bx-window';
    }
  }

  private checkTabsOverflow(): void {
    if (!this.tabsScroll) return;

    const element = this.tabsScroll.nativeElement;
    this.showTabControls = element.scrollWidth > element.clientWidth;
    this.canScrollLeft = element.scrollLeft > 0;
    this.canScrollRight = element.scrollLeft < (element.scrollWidth - element.clientWidth);
    this.showScrollButtons = element.scrollWidth > element.clientWidth;
  }

  scrollTabs(direction: 'left' | 'right'): void {
    if (!this.tabsScroll) return;

    const element = this.tabsScroll.nativeElement;
    const scrollAmount = element.clientWidth / 2;

    if (direction === 'left') {
      element.scrollLeft -= scrollAmount;
    } else {
      element.scrollLeft += scrollAmount;
    }

    // Atualizar estado dos botões após a rolagem
    setTimeout(() => this.checkTabsOverflow(), 100);
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    // Fechar menu do usuário quando clicar fora
    const userMenu = document.querySelector('.user-menu');
    if (userMenu && !userMenu.contains(event.target as Node)) {
      this.isUserMenuOpen = false;
    }
  }
}
