import {
  Component,
  EventEmitter,
  Input,
  Output,
  ContentChildren,
  QueryList,
  AfterContentInit,
  HostListener,
} from '@angular/core';
import { TabComponent } from './tab/tab.component';

@Component({
  selector: 'ub-tabs',
  standalone: true,
  imports: [],
  templateUrl: './tabs.component.html',
  styleUrls: ['./tabs.component.scss'],
})
export class TabsComponent implements AfterContentInit {
  @ContentChildren(TabComponent) tabs!: QueryList<TabComponent>;
  @Input() activeTabIndex: number = 0;
  @Output() activeTabIndexChange = new EventEmitter<number>();

  ngAfterContentInit(): void {
    if (this.tabs.length > 0) {
      this.selectTab(this.tabs.toArray()[this.activeTabIndex]);
    }
  }

  selectTab(tab: TabComponent): void {
    this.tabs.forEach((t) => (t.active = false));
    tab.active = true;
    this.activeTabIndex = this.tabs.toArray().indexOf(tab);
    this.activeTabIndexChange.emit(this.activeTabIndex);
  }

  // Navegação por teclado
  @HostListener('keydown', ['$event'])
  onKeyDown(event: KeyboardEvent): void {
    const tabsArray = this.tabs.toArray();
    const currentIndex = tabsArray.findIndex((tab) => tab.active);

    if (event.key === 'ArrowRight') {
      const nextIndex = (currentIndex + 1) % tabsArray.length;
      this.selectTab(tabsArray[nextIndex]);
    } else if (event.key === 'ArrowLeft') {
      const prevIndex =
        (currentIndex - 1 + tabsArray.length) % tabsArray.length;
      this.selectTab(tabsArray[prevIndex]);
    }
  }
}
