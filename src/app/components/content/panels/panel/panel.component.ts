import { Component, Input, Output, EventEmitter, HostBinding } from '@angular/core';
import { CommonModule, NgIf } from '@angular/common';

@Component({
  selector: 'ub-panel',
  standalone: true,
  imports: [NgIf, CommonModule],
  templateUrl: './panel.component.html',
  styleUrls: ['./panel.component.scss'],
  host: {
    '[class.panel]': 'true',
    '[class.panel-expanded]': 'expanded',
    '[style.flex]': 'flexStyle'
  }
})
export class PanelComponent {
  @Input() size: number | null = null;
  @Input() minSize: number = 20;
  @Input() title: string = '';
  @Input() expandable: boolean = true;
  @Input() panelClass: string = '';

  @Output() sizeChange = new EventEmitter<number>();
  @Output() expandedChange = new EventEmitter<boolean>();

  expanded = false;

  get flexStyle(): string {
    if (this.expanded) return '0 0 auto'; // Remove flex quando expandido
    return this.size !== null ? `1 1 ${this.size}%` : '1 1 auto';
  }

  toggleExpand(): void {
    this.expanded = !this.expanded;
    this.expandedChange.emit(this.expanded);
  }

  updateSize(newSize: number): void {
    if (!this.expanded && this.size !== null) {
      this.size = Math.max(this.minSize, Math.min(100, newSize));
      this.sizeChange.emit(this.size);
    }
  }
}
