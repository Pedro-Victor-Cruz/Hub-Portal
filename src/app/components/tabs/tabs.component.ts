import {
  Component,
  EventEmitter,
  Input,
  Output,
  ContentChildren,
  QueryList,
  AfterContentInit,
  ChangeDetectorRef
} from '@angular/core';
import { TabComponent } from './tab/tab.component';
import {CommonModule} from '@angular/common';

@Component({
  selector: 'ub-tabs',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tabs.component.html',
  styleUrls: ['./tabs.component.scss'],
})
export class TabsComponent implements AfterContentInit {
  @ContentChildren(TabComponent) tabs!: QueryList<TabComponent>;
  @Input() activeTabIndex: number = 0;
  @Output() activeTabIndexChange = new EventEmitter<number>();

  constructor(private cdr: ChangeDetectorRef) {}

  ngAfterContentInit(): void {
    // Inicializa a primeira vez
    this.initializeTabs();

    // Observa mudanças no QueryList (quando tabs são adicionados/removidos)
    this.tabs.changes.subscribe(() => {
      this.initializeTabs();
    });
  }

  private initializeTabs(): void {
    if (this.tabs.length > 0) {
      // Garante que o índice ativo seja válido
      if (this.activeTabIndex >= this.tabs.length) {
        this.activeTabIndex = 0;
      }

      // Ativa o tab correto imediatamente
      this.tabs.forEach((tab, i) => {
        tab.active = i === this.activeTabIndex;
      });

      this.cdr.detectChanges();
    }
  }

  activateTab(index: number): void {
    if (index < 0 || index >= this.tabs.length) {
      return;
    }

    this.activeTabIndex = index;
    this.activeTabIndexChange.emit(index);

    this.tabs.forEach((tab, i) => {
      tab.active = i === index;
    });

    this.cdr.detectChanges();
  }

  selectTab(index: number): void {
    this.activateTab(index);
  }
}
