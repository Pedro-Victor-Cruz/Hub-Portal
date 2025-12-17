import { Component, Input, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'ub-tab',
  standalone: true,
  imports: [CommonModule],
  template: `
    @if (active) {
      <div class="tab-content active">
        <ng-content></ng-content>
      </div>
    }
  `,
  styleUrl: './tab.component.scss'
})
export class TabComponent {
  @Input() label: string = '';

  private _active: boolean = false;

  @Input()
  set active(value: boolean) {
    this._active = value;
    this.cdr.markForCheck();
  }

  get active(): boolean {
    return this._active;
  }

  constructor(private cdr: ChangeDetectorRef) {}
}
