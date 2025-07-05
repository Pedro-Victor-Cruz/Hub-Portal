import {Component, EventEmitter, Input, Output} from '@angular/core';
import {NgForOf, NgIf, NgSwitch, NgSwitchCase} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {ToggleSwitchComponent} from '../form/toggle-switch/toggle-switch.component';

/**
 * Interface que define a estrutura de um item na visualização em pasta
 */
export interface FolderItem {
  id: string | number;
  name: string;
  description?: string;
  icon?: string;
  meta?: string;
  expanded?: boolean;
  highlight?: boolean;
  children?: FolderItem[];
  parent?: FolderItem | null;
  level?: number;
  isGroup?: boolean; // Indica se é um grupo de pastas
  originalData?: any;

  [key: string]: any;
}

/**
 * Configuração para a visualização em pastas
 */
export interface FolderConfig {
  groupBy: string;               // Campo para agrupamento
  folderName?: string | ((item: any) => string); // Campo para nome da pasta
  folderIcon?: string;           // Ícone para pastas
  itemIcon?: string;             // Ícone para itens
  itemName?: string | ((item: any) => string);             // Campo para nome do item
  itemDescription?: string;      // Campo para descrição do item
  metaFields?: string[];         // Campos adicionais para exibição
  selectionType?: 'checkbox' | 'toggle';
}

@Component({
  selector: 'ub-folder-view',
  imports: [NgForOf, NgIf, FormsModule, NgSwitch, NgSwitchCase, ToggleSwitchComponent],
  templateUrl: './folder-view.component.html',
  standalone: true,
  styleUrl: './folder-view.component.scss'
})
export class FolderViewComponent {
  private _selectedItems: Record<string | number, boolean> = {};
  private _data: any[] = [];

  @Input() set data(items: any[]) {
    this._data = items || [];
    this.prepareFolderStructure();
  }

  get data(): any[] {
    return this._data;
  }

  @Input() folderConfig: FolderConfig = {
    groupBy: 'group',
    folderName: 'group',
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
    itemName: 'name',
    itemDescription: 'description',
    selectionType: 'checkbox',
  };

  @Input() placeholder: string = 'Buscar registros...';
  @Input() selectable: boolean = false;
  @Input() primaryKey: string = 'id';
  @Input() selectionMode: 'single' | 'multiple' = 'multiple';

  @Input()
  get selectedItems(): Record<string | number, boolean> {
    return this._selectedItems;
  }

  set selectedItems(items: Record<string | number, boolean> | (string | number)[]) {
    if (Array.isArray(items)) {
      // Converte array para o formato de objeto (backward compatibility)
      this._selectedItems = {};
      items.forEach(id => this._selectedItems[id] = true);
    } else {
      this._selectedItems = items || {};
    }
    this.syncSelectedItems();
  }


  @Output() itemSelected = new EventEmitter<any>();
  @Output() selectedItemsChange = new EventEmitter<Record<string | number, boolean>>();

  searchQuery = '';
  folderItems: FolderItem[] = [];
  filteredItems: FolderItem[] = [];

  /**
   * Prepara a estrutura de pastas com base nos dados de entrada
   */
  private prepareFolderStructure(): void {
    if (!this.data || !this.folderConfig) {
      this.folderItems = [];
      this.filteredItems = [];
      return;
    }

    // Agrupa itens conforme configuração
    const groups = this.groupItems(this.data);

    // Cria estrutura de pastas
    this.folderItems = Object.keys(groups).map(groupName => {
      const groupItems = groups[groupName];
      const firstItem = groupItems[0];
      const folderName = this.folderConfig.folderName ?
        (typeof this.folderConfig.folderName === 'function' ?
          this.folderConfig.folderName(firstItem) :
          firstItem[this.folderConfig.folderName]) || groupName :
        groupName;

      const groupItem: FolderItem = {
        id: `group-${groupName}`,
        name: folderName,
        icon: this.folderConfig.folderIcon,
        expanded: false,
        highlight: false,
        level: 0,
        children: [],
        meta: this.getMetaString(firstItem),
        isGroup: true,
        originalData: groupItems
      };

      // Cria itens filhos
      groupItem.children = groupItems.map(item => this.createChildItem(item, 1, groupItem));
      return groupItem;
    });

    this.filteredItems = [...this.folderItems];
    this.syncSelectedItems();
  }

  /**
   * Agrupa itens conforme campo de agrupamento
   */
  private groupItems(items: any[]): { [key: string]: any[] } {
    return items.reduce((groups, item) => {
      const groupKey = item[this.folderConfig.groupBy] || 'Sem Grupo';
      groups[groupKey] = groups[groupKey] || [];
      groups[groupKey].push(item);
      return groups;
    }, {});
  }

  /**
   * Cria um item filho na estrutura de pastas
   */
  private createChildItem(item: any, level: number, parent: FolderItem): FolderItem {

    const name = this.folderConfig.itemName ?
      (typeof this.folderConfig.itemName === 'function' ?
        this.folderConfig.itemName(item) :
        item[this.folderConfig.itemName]) || item.name :
      item.name || 'Item Sem Nome';

    return {
      id: item[this.primaryKey],
      name: name,
      description: item[this.folderConfig.itemDescription || 'description'],
      icon: this.folderConfig.itemIcon,
      expanded: false,
      highlight: false,
      level: level,
      parent: parent,
      meta: this.getMetaString(item),
      originalData: item,
      isGroup: false
    };
  }

  /**
   * Gera string de metadados para exibição
   */
  private getMetaString(item: any): string {
    if (!this.folderConfig.metaFields?.length) return '';
    return this.folderConfig.metaFields
      .map(field => item[field] ? `${field}: ${item[field]}` : '')
      .filter(Boolean)
      .join(' | ');
  }

  /**
   * Sincroniza os itens selecionados com a estrutura de pastas
   */
  private syncSelectedItems(): void {
    if (!this.folderItems.length || !Object.keys(this.selectedItems).length) return;

    const markSelected = (items: FolderItem[]) => {
      items.forEach(item => {
        if (item.children) {
          markSelected(item.children);
        }

        if (!item.isGroup && item.originalData) {
          const itemId = item.originalData[this.primaryKey];
          item.highlight = this.selectedItems[itemId] || false;
        }
      });
    };

    markSelected(this.folderItems);
  }

  /**
   * Alterna expansão de um item ou seleciona se não tiver filhos
   */
  toggleExpand(item: FolderItem): void {
    if (this.hasChildren(item)) {
      item.expanded = !item.expanded;
    } else if (!this.selectable) {
      this.selectItem(item);
    }
  }

  /**
   * Seleciona um item individualmente (quando selectable=false)
   */
  selectItem(item: FolderItem): void {
    this.clearHighlights();
    item.highlight = true;

    if (item.originalData) {
      if (this.selectionMode === 'single') {
        // Verifica se o item já estava selecionado, se sim demarca ele
        if (this.isSelected(item)) {
          item.highlight = false;
          delete this._selectedItems[item.originalData[this.primaryKey]];
          this.itemSelected.emit(null);
        } else {
          // Limpa seleções anteriores e seleciona apenas este item
          this._selectedItems = {};
          this._selectedItems[item.originalData[this.primaryKey]] = true;
          this.itemSelected.emit(item.originalData);
        }
      }
    }
  }

  /**
   * Verifica se um item tem filhos
   */
  hasChildren(item: FolderItem): boolean {
    return !!item.children && item.children.length > 0;
  }

  /**
   * Obtém nível de indentação para exibição
   */
  getIndentLevel(item: FolderItem): number {
    return item.level || 0;
  }

  /**
   * Expande todos os itens
   */
  expandAll(): void {
    this.setAllExpanded(true);
  }

  /**
   * Recolhe todos os itens
   */
  collapseAll(): void {
    this.setAllExpanded(false);
  }

  /**
   * Define estado de expansão para todos os itens
   */
  private setAllExpanded(expanded: boolean): void {
    const updateExpanded = (items: FolderItem[]) => {
      items.forEach(item => {
        if (this.hasChildren(item)) {
          item.expanded = expanded;
          updateExpanded(item.children!);
        }
      });
    };
    updateExpanded(this.filteredItems);
  }

  /**
   * Filtra itens conforme termo de busca
   */
  filterItems(): void {
    if (!this.searchQuery) {
      this.filteredItems = [...this.folderItems];
      this.clearHighlights();
      return;
    }

    const searchTerm = this.searchQuery.toLowerCase();
    this.filteredItems = this.folderItems
      .map(item => this.filterItem(item, searchTerm))
      .filter(item => item !== null) as FolderItem[];
    this.expandAllFiltered();
  }

  /**
   * Filtra um item individualmente
   */
  private filterItem(item: FolderItem, searchTerm: string): FolderItem | null {
    const matches = item.name.toLowerCase().includes(searchTerm) ||
      (item.description?.toLowerCase().includes(searchTerm)) ||
      (item.meta?.toLowerCase().includes(searchTerm));

    if (matches) {
      item.highlight = true;
      return {...item, expanded: true};
    }

    if (item.children) {
      const filteredChildren = item.children
        .map(child => this.filterItem(child, searchTerm))
        .filter(Boolean) as FolderItem[];

      if (filteredChildren.length) {
        return {...item, expanded: true, children: filteredChildren};
      }
    }

    return null;
  }

  /**
   * Expande todos os itens filtrados
   */
  private expandAllFiltered(): void {
    const expandParents = (item: FolderItem) => {
      if (item.parent) {
        item.parent.expanded = true;
        expandParents(item.parent);
      }
    };

    this.filteredItems.forEach(item => {
      const traverse = (node: FolderItem) => {
        node.expanded = true;
        if (node.children) node.children.forEach(traverse);
      };
      traverse(item);
    });
  }

  /**
   * Remove destaque de todos os itens
   */
  private clearHighlights(): void {
    const clear = (items: FolderItem[]) => {
      items.forEach(item => {
        item.highlight = false;
        if (item.children) clear(item.children);
      });
    };
    clear(this.filteredItems);
  }

  /**
   * Verifica se um item está selecionado
   */
  isSelected(item: FolderItem): boolean {
    if (!item.originalData) return false;
    if (item.isGroup) {
      return item.children?.every(child =>
        child.originalData && this.selectedItems[child.originalData[this.primaryKey]]
      ) || false;
    }
    return this.selectedItems[item.originalData[this.primaryKey]] || false;
  }

  /**
   * Alterna seleção de um item ou grupo
   */
  toggleSelection(item: FolderItem, event: MouseEvent | Event | null, newValue?: boolean): void {
    if (event) event.stopPropagation();

    if (this.selectionMode === 'single') {
      this.selectItem(item);
      return;
    }

    if (item.isGroup) {
      this.toggleGroupSelection(item, newValue);
    } else {
      this.toggleSingleSelection(item, newValue);
    }

    this.selectedItemsChange.emit({...this.selectedItems});
  }

  /**
   * Alterna seleção de um item individual
   */
  private toggleSingleSelection(item: FolderItem, newValue?: boolean): void {
    if (!item.originalData || item.isGroup) return;

    const itemId = item.originalData[this.primaryKey];
    const currentValue = this.selectedItems[itemId] || false;
    const value = newValue !== undefined ? newValue : !currentValue;

    this.selectedItems[itemId] = value;
    item.highlight = value;
  }

  /**
   * Alterna seleção de todos os itens de um grupo
   */
  private toggleGroupSelection(group: FolderItem, newValue?: boolean): void {
    if (!group.children || !group.originalData) return;

    const groupItemIds = group.children
      .filter(child => !child.isGroup && child.originalData)
      .map(child => child.originalData[this.primaryKey]);

    const allSelected = groupItemIds.every(id => this.selectedItems[id]);

    const value = newValue !== undefined ? newValue : !allSelected;

    groupItemIds.forEach(id => {
      this.selectedItems[id] = value;
    });

    // Atualiza visualização
    group.children.forEach(child => {
      if (child.originalData) {
        child.highlight = this.selectedItems[child.originalData[this.primaryKey]] || false;
      }
    });
  }

  trackByItemId(index: number, item: FolderItem): string | number {
    return item.id;
  }
}
