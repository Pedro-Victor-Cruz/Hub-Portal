import {
  Component, Input, Output, ContentChildren,
  QueryList, AfterContentInit, ElementRef,
  HostListener, Renderer2, OnDestroy, EventEmitter,
  ChangeDetectionStrategy
} from '@angular/core';
import { PanelComponent } from './panel/panel.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'ub-panel-area',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="panel-area"
         [class.dragging]="isDragging"
         [style.flex-direction]="direction === 'vertical' ? 'column' : 'row'"
         [style.flex]="flexStyle"
         [style.gap]="gap + 'px'">
      <ng-content></ng-content>

      <div *ngFor="let gutter of gutters; trackBy: trackByIndex"
           class="panel-gutter"
           [class.vertical]="direction === 'vertical'"
           [class.horizontal]="direction === 'horizontal'"
           [style.left]="getGutterPosition(gutter)"
           [style.top]="getGutterPosition(gutter)"
           (mousedown)="startDrag($event, gutter.index)">
      </div>
    </div>
  `,
  styleUrls: ['./panel-area.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class PanelAreaComponent implements AfterContentInit, OnDestroy {
  @Input() direction: 'horizontal' | 'vertical' = 'horizontal';
  @Input() gutterSize: number = 8;
  @Input() gap: number = 8;
  @Input() size: number | null = null;
  @Input() minSize: number = 20;
  @Output() sizeChange = new EventEmitter<number>();
  @Output() dragEnd = new EventEmitter<void>();

  @ContentChildren(PanelComponent) panels!: QueryList<PanelComponent>;

  isDragging = false;
  gutters: {index: number, position: number}[] = [];
  private startPos = 0;
  private panelArray: PanelComponent[] = [];
  private destroyListeners: (() => void)[] = [];
  private activeGutterIndex = -1;
  private initialSizes: number[] = [];

  constructor(private el: ElementRef, private renderer: Renderer2) {}

  get flexStyle(): string {
    return this.size !== null ? `1 1 ${this.size}%` : '1 1 auto';
  }

  ngAfterContentInit(): void {
    this.panelArray = this.panels.toArray();
    this.updateGutters();
    this.panels.changes.subscribe(() => {
      this.panelArray = this.panels.toArray();
      this.updateGutters();
    });
  }

  ngOnDestroy(): void {
    this.cleanupListeners();
  }

  trackByIndex(index: number): number {
    return index;
  }

  getGutterPosition(gutter: {index: number, position: number}): string | null {
    return this.isDragging ? `${gutter.position}px` : null;
  }

  private updateGutters(): void {
    this.gutters = [];
    const resizablePanels = this.panelArray.filter(p => p.size !== null);

    for (let i = 0; i < resizablePanels.length - 1; i++) {
      this.gutters.push({
        index: i,
        position: 0 // Será calculado durante o drag
      });
    }
  }

  startDrag(event: MouseEvent, index: number): void {
    event.preventDefault();
    event.stopPropagation();

    this.isDragging = true;
    this.activeGutterIndex = index;
    this.startPos = this.direction === 'horizontal' ? event.clientX : event.clientY;
    this.initialSizes = this.panelArray.map(p => p.size || 0);

    this.renderer.addClass(document.body, 'panel-dragging');

    const mouseMoveListener = this.renderer.listen(
      document, 'mousemove', this.onDrag.bind(this)
    );
    const mouseUpListener = this.renderer.listen(
      document, 'mouseup', this.endDrag.bind(this)
    );

    this.destroyListeners.push(() => {
      mouseMoveListener();
      mouseUpListener();
    });
  }

  private onDrag(event: MouseEvent): void {
    if (!this.isDragging || this.activeGutterIndex === -1) return;

    const currentPos = this.direction === 'horizontal' ? event.clientX : event.clientY;
    const delta = currentPos - this.startPos;
    const containerSize = this.direction === 'horizontal'
      ? this.el.nativeElement.offsetWidth
      : this.el.nativeElement.offsetHeight;

    if (containerSize <= 0) return;

    const percentageDelta = (delta / containerSize) * 100;
    const resizablePanels = this.panelArray.filter(p => p.size !== null);

    if (this.activeGutterIndex >= 0 && this.activeGutterIndex < resizablePanels.length - 1) {
      const panel1 = resizablePanels[this.activeGutterIndex];
      const panel2 = resizablePanels[this.activeGutterIndex + 1];
      const initialSize1 = this.initialSizes[this.panelArray.indexOf(panel1)];
      const initialSize2 = this.initialSizes[this.panelArray.indexOf(panel2)];

      const newSize1 = Math.max(
        panel1.minSize,
        Math.min(100 - panel2.minSize, initialSize1 + percentageDelta)
      );
      const newSize2 = Math.max(
        panel2.minSize,
        initialSize2 - percentageDelta
      );

      // Ajuste para garantir que a soma seja 100%
      const total = newSize1 + newSize2;
      const adjustment = 100 - total;

      panel1.updateSize(newSize1 + adjustment/2);
      panel2.updateSize(newSize2 + adjustment/2);

      // Atualiza a posição do gutter durante o drag
      if (this.gutters[this.activeGutterIndex]) {
        const gutterPos = (panel1.size || 0) * containerSize / 100;
        this.gutters[this.activeGutterIndex].position = gutterPos;
      }
    }
  }

  private endDrag(): void {
    this.isDragging = false;
    this.activeGutterIndex = -1;
    this.renderer.removeClass(document.body, 'panel-dragging');
    this.dragEnd.emit();
    this.cleanupListeners();
  }

  private cleanupListeners(): void {
    this.destroyListeners.forEach(fn => fn());
    this.destroyListeners = [];
  }

  updateSize(newSize: number): void {
    if (this.size !== null) {
      this.size = Math.max(this.minSize, Math.min(100, newSize));
      this.sizeChange.emit(this.size);
    }
  }

  @HostListener('window:resize')
  onWindowResize(): void {
    this.panelArray.forEach(panel => panel.size !== null && panel.updateSize(panel.size));
    this.size !== null && this.updateSize(this.size);
  }
}
