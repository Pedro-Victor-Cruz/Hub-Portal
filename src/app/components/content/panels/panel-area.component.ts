import { Component, Input, ContentChildren, QueryList, AfterContentInit, OnDestroy, Renderer2, ElementRef, ChangeDetectorRef } from '@angular/core';
import { PanelComponent } from './panel/panel.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'ub-panel-area',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="panel-area"
         [class.vertical]="direction === 'vertical'"
         [class.horizontal]="direction === 'horizontal'"
         [class.dragging]="isDragging">

      @for (panel of visiblePanels; track panel?.id; let i = $index) {
        <div class="panel-wrapper" [style.flex]="getPanelFlex(panel)">
          <ng-container [ngTemplateOutlet]="panel.template"></ng-container>
        </div>

        @if (i < visiblePanels.length - 1) {
          <div class="panel-gutter"
               [class.vertical]="direction === 'vertical'"
               [class.horizontal]="direction === 'horizontal'"
               [class.dragging]="draggingGutterIndex === i"
               (mousedown)="startDrag($event, i)"
               (touchstart)="startDrag($event, i)">
          </div>
        }
      }
    </div>
  `,
  styleUrls: ['./panel-area.component.scss']
})
export class PanelAreaComponent implements AfterContentInit, OnDestroy {
  @Input() direction: 'horizontal' | 'vertical' = 'horizontal';
  @Input() gutterSize: number = 8;

  @ContentChildren(PanelComponent) panels!: QueryList<PanelComponent>;
  visiblePanels: PanelComponent[] = [];

  isDragging = false;
  draggingGutterIndex = -1;
  private startPosition = 0;
  private startSizes: number[] = [];
  private moveListeners: (() => void)[] = [];
  private endListeners: (() => void)[] = [];

  constructor(
    private renderer: Renderer2,
    private el: ElementRef,
    private cdRef: ChangeDetectorRef
  ) {}

  ngAfterContentInit(): void {
    this.updateVisiblePanels();
    this.panels.changes.subscribe(() => this.updateVisiblePanels());
  }

  ngOnDestroy(): void {
    this.removeEventListeners();
  }

  startDrag(event: MouseEvent | TouchEvent, gutterIndex: number): void {
    event.preventDefault();
    this.isDragging = true;
    this.draggingGutterIndex = gutterIndex;

    const clientPos = this.getClientPosition(event);
    this.startPosition = this.direction === 'horizontal' ? clientPos.x : clientPos.y;
    this.startSizes = [
      this.visiblePanels[gutterIndex].size || 0,
      this.visiblePanels[gutterIndex + 1].size || 0
    ];

    // Adiciona listeners para mouse e touch
    this.moveListeners = [
      this.renderer.listen('document', 'mousemove', this.onDrag.bind(this)),
      this.renderer.listen('document', 'touchmove', this.onDrag.bind(this), { passive: false })
    ];

    this.endListeners = [
      this.renderer.listen('document', 'mouseup', this.endDrag.bind(this)),
      this.renderer.listen('document', 'touchend', this.endDrag.bind(this)),
      this.renderer.listen('document', 'touchcancel', this.endDrag.bind(this))
    ];
  }

  private onDrag(event: MouseEvent | TouchEvent): void {
    if (!this.isDragging || this.draggingGutterIndex === -1) return;
    event.preventDefault();

    const clientPos = this.getClientPosition(event);
    const currentPosition = this.direction === 'horizontal' ? clientPos.x : clientPos.y;
    const delta = currentPosition - this.startPosition;

    const containerSize = this.direction === 'horizontal'
      ? this.el.nativeElement.offsetWidth
      : this.el.nativeElement.offsetHeight;

    if (containerSize <= 0) return;

    const deltaPercent = (delta / containerSize) * 100;

    const leftPanel = this.visiblePanels[this.draggingGutterIndex];
    const rightPanel = this.visiblePanels[this.draggingGutterIndex + 1];

    const newLeftSize = this.startSizes[0] + deltaPercent;
    const newRightSize = this.startSizes[1] - deltaPercent;

    // Aplicar os novos tamanhos respeitando os mínimos e máximos
    if (newLeftSize >= leftPanel.minSize && newRightSize >= rightPanel.minSize &&
      newLeftSize <= (leftPanel.maxSize || 100) && newRightSize <= (rightPanel.maxSize || 100)) {
      leftPanel.size = newLeftSize;
      rightPanel.size = newRightSize;
      this.cdRef.detectChanges();
    }
  }

  private getClientPosition(event: MouseEvent | TouchEvent): {x: number, y: number} {
    if (this.isTouchEvent(event)) {
      return {
        x: event.touches[0].clientX,
        y: event.touches[0].clientY
      };
    } else {
      return {
        x: (event as MouseEvent).clientX,
        y: (event as MouseEvent).clientY
      };
    }
  }

  private isTouchEvent(event: MouseEvent | TouchEvent): event is TouchEvent {
    return (event as TouchEvent).touches !== undefined;
  }

  private endDrag(): void {
    this.isDragging = false;
    this.draggingGutterIndex = -1;
    this.removeEventListeners();
    this.cdRef.detectChanges();
  }

  private removeEventListeners(): void {
    this.moveListeners.forEach(fn => fn());
    this.endListeners.forEach(fn => fn());
    this.moveListeners = [];
    this.endListeners = [];
  }

  trackByPanel(index: number, panel: PanelComponent): any {
    return panel.id || index;
  }

  getPanelFlex(panel: PanelComponent): string {
    if (panel.hidden) return '0 0 0%';
    return panel.size !== null ? `0 0 calc(${panel.size}% + 2.5px)` : '1 1 auto';
  }

  private updateVisiblePanels(): void {
    this.visiblePanels = this.panels.filter(p => !p.hidden);
    this.normalizePanelSizes();
    this.cdRef.detectChanges();
  }

  private normalizePanelSizes(): void {
    const totalSize = this.visiblePanels.reduce((sum, panel) => sum + (panel.size || 0), 0);

    if (totalSize !== 100) {
      const equalSize = 100 / this.visiblePanels.length;
      this.visiblePanels.forEach(panel => {
        panel.size = equalSize;
      });
    }
  }
}
