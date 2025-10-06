import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, Observable, Subject } from 'rxjs';
import { take } from 'rxjs/operators';

export interface ConfirmationConfig {
  message: string;
  acceptText: string;
  cancelText: string;
}

interface ConfirmationRequest {
  config: ConfirmationConfig;
  resolver: (value: boolean) => void;
}

@Injectable({
  providedIn: 'root',
})
export class ConfirmationService implements OnDestroy {
  private readonly defaultConfig: ConfirmationConfig = {
    message: 'Você deseja prosseguir com essa ação?',
    acceptText: 'Confirmar',
    cancelText: 'Cancelar',
  };

  // Estado do modal (BehaviorSubject para garantir que novos subscribers recebam o valor atual)
  private modalStateSubject = new BehaviorSubject<ConfirmationConfig | null>(null);
  public modalState$ = this.modalStateSubject.asObservable();

  // Fila de requisições pendentes
  private requestQueue: ConfirmationRequest[] = [];
  private isProcessing = false;

  // Subject para cleanup
  private destroy$ = new Subject<void>();

  /**
   * Exibe modal de confirmação e retorna uma Promise com a resposta do usuário
   * @param message Mensagem a ser exibida
   * @param acceptText Texto do botão de confirmação
   * @param cancelText Texto do botão de cancelamento
   * @returns Promise<boolean> que resolve com true se aceito, false se cancelado
   */
  async confirm(
    message?: string,
    acceptText?: string,
    cancelText?: string
  ): Promise<boolean> {
    const config: ConfirmationConfig = {
      message: message ?? this.defaultConfig.message,
      acceptText: acceptText ?? this.defaultConfig.acceptText,
      cancelText: cancelText ?? this.defaultConfig.cancelText,
    };

    return new Promise<boolean>((resolve) => {
      this.requestQueue.push({ config, resolver: resolve });
      this.processQueue();
    });
  }

  /**
   * Variação que retorna Observable (para compatibilidade com código existente)
   */
  confirm$(
    message?: string,
    acceptText?: string,
    cancelText?: string
  ): Observable<boolean> {
    return new Observable<boolean>((subscriber) => {
      this.confirm(message, acceptText, cancelText).then((result) => {
        subscriber.next(result);
        subscriber.complete();
      });
    });
  }

  /**
   * Processa a fila de requisições
   */
  private processQueue(): void {
    if (this.isProcessing || this.requestQueue.length === 0) {
      return;
    }

    this.isProcessing = true;
    const request = this.requestQueue[0];

    // Exibe o modal com a configuração da requisição
    this.modalStateSubject.next(request.config);
  }

  /**
   * Chamado quando o usuário aceita a confirmação
   */
  accept(): void {
    this.resolveCurrentRequest(true);
  }

  /**
   * Chamado quando o usuário cancela a confirmação
   */
  cancel(): void {
    this.resolveCurrentRequest(false);
  }

  /**
   * Resolve a requisição atual e processa a próxima da fila
   */
  private resolveCurrentRequest(result: boolean): void {
    if (this.requestQueue.length === 0) {
      return;
    }

    const request = this.requestQueue.shift();

    // Fecha o modal
    this.modalStateSubject.next(null);
    this.isProcessing = false;

    // Resolve a promise
    if (request) {
      request.resolver(result);
    }

    // Processa próxima requisição após um pequeno delay
    setTimeout(() => this.processQueue(), 100);
  }

  /**
   * Cancela todas as requisições pendentes
   */
  cancelAll(): void {
    while (this.requestQueue.length > 0) {
      const request = this.requestQueue.shift();
      if (request) {
        request.resolver(false);
      }
    }
    this.modalStateSubject.next(null);
    this.isProcessing = false;
  }

  /**
   * Retorna se há uma confirmação ativa
   */
  isActive(): boolean {
    return this.isProcessing;
  }

  /**
   * Retorna quantas requisições estão na fila
   */
  getQueueSize(): number {
    return this.requestQueue.length;
  }

  ngOnDestroy(): void {
    this.cancelAll();
    this.destroy$.next();
    this.destroy$.complete();
    this.modalStateSubject.complete();
  }
}
