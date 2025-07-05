import { Component, Input, TemplateRef, ViewChild, HostBinding, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NgIf } from '@angular/common';

export interface PanelAction {
  title: string;
  icon: string;
  disabled?: boolean;
  action: () => void;
  severity?: 'info' | 'warning' | 'error' | 'success';
}

let nextId = 0;

@Component({
  selector: 'ub-panel',
  standalone: true,
  imports: [CommonModule, NgIf],
  template: `
    <ng-template #template>
      <div class="panel"
           [class.hidden]="hidden"
           [class.fullscreen]="fullscreen"
           [class.collapsed]="collapsed">
        <div class="panel-header" *ngIf="showHeader">
          <h3 class="panel-title">{{ title }}</h3>

          <div class="panel-actions">
            <button *ngIf="collapsible"
                    class="panel-action"
                    (click)="toggleCollapse()"
                    [title]="collapsed ? 'Expandir' : 'Recolher'">
              <i class="icon" [class.icon-expand]="collapsed" [class.icon-collapse]="!collapsed"></i>
            </button>

            <button *ngIf="expandable"
                    class="panel-action"
                    (click)="toggleFullscreen()"
                    [title]="fullscreen ? 'Sair de tela cheia' : 'Tela cheia'">
              <i class="icon" [class.icon-fullscreen]="!fullscreen" [class.icon-exit-fullscreen]="fullscreen"></i>
            </button>

            <button *ngIf="closable"
                    class="panel-action"
                    (click)="close()"
                    title="Fechar">
              <i class="icon icon-close"></i>
            </button>

            <ng-container *ngFor="let action of actions">
              <button class="panel-action"
                      [disabled]="action.disabled"
                      (click)="action.action()"
                      [title]="action.title"
                        [ngClass]="{
                          'panel-action-info': action.severity === 'info',
                          'panel-action-warning': action.severity === 'warning',
                          'panel-action-error': action.severity === 'error',
                          'panel-action-success': action.severity === 'success'
                        }">
                <i class="icon" [class]="action.icon"></i>
              </button>
            </ng-container>

          </div>
        </div>

        <div class="panel-content" *ngIf="!collapsed">
          <ng-content></ng-content>
        </div>
      </div>
    </ng-template>
  `,
  styleUrls: ['./panel.component.scss']
})
export class PanelComponent {
  @ViewChild('template', { static: true }) template!: TemplateRef<any>;

  // Inputs
  @Input() size: number | null = null;
  @Input() minSize: number = 20;
  @Input() maxSize: number = 100;
  @Input() title: string = '';
  @Input() hidden: boolean = false;
  @Input() collapsible: boolean = true;
  @Input() expandable: boolean = true;
  @Input() closable: boolean = false;
  @Input() showHeader: boolean = true;
  @Input() collapsed: boolean = false;
  @Input() fullscreen: boolean = false;
  @Input() actions: PanelAction[] = [];

  // Outputs
  @Output() closed = new EventEmitter<void>();
  @Output() collapsedChange = new EventEmitter<boolean>();
  @Output() fullscreenChange = new EventEmitter<boolean>();

  id = nextId++;

  toggleCollapse(): void {
    this.collapsed = !this.collapsed;
    this.collapsedChange.emit(this.collapsed);
  }

  toggleFullscreen(): void {
    this.fullscreen = !this.fullscreen;
    this.fullscreenChange.emit(this.fullscreen);

    // Adiciona/remove classe no body para estilos globais quando em tela cheia
    if (this.fullscreen) {
      document.body.classList.add('panel-fullscreen-active');
    } else {
      document.body.classList.remove('panel-fullscreen-active');
    }
  }

  close(): void {
    this.hidden = true;
    this.closed.emit();
  }
}
