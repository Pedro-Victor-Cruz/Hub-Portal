import { Component, OnDestroy, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ConfirmationService, ConfirmationConfig } from './confirmation-modal.service';
import { ButtonComponent } from '../form/button/button.component';

@Component({
  selector: 'ub-confirmation-modal',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './confirmation-modal.component.html',
  styleUrl: './confirmation-modal.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class ConfirmationModalComponent implements OnDestroy {
  isVisible = false;
  message = '';
  acceptText = '';
  cancelText = '';

  private destroy$ = new Subject<void>();

  constructor(
    private confirmationService: ConfirmationService,
    private cdr: ChangeDetectorRef
  ) {
    this.initializeModal();
  }

  private initializeModal(): void {
    this.confirmationService.modalState$
      .pipe(takeUntil(this.destroy$))
      .subscribe((config: ConfirmationConfig | null) => {
        if (config) {
          this.message = config.message;
          this.acceptText = config.acceptText;
          this.cancelText = config.cancelText;
          this.isVisible = true;
        } else {
          this.isVisible = false;
        }
        // Força detecção de mudanças para garantir que o modal apareça
        this.cdr.markForCheck();
      });
  }

  onAccept(): void {
    this.confirmationService.accept();
  }

  onCancel(): void {
    this.confirmationService.cancel();
  }

  // Fecha o modal ao clicar no overlay
  onOverlayClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.onCancel();
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
