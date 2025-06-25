import { Injectable } from '@angular/core';
import { Subject, Observable, take } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ConfirmationService {
  private showModalSubject = new Subject<{
    message: string;
    acceptText: string;
    cancelText: string;
  } | null>();

  private userResponseSubject = new Subject<boolean>();
  private currentResponseSubscription: { unsubscribe: () => void } | null = null;

  showModal$ = this.showModalSubject.asObservable();

  confirm(
    message: string = 'Você deseja prosseguir com essa ação?',
    acceptText: string = 'Confirmar',
    cancelText: string = 'Cancelar'
  ): Observable<boolean> {
    // Cancela qualquer confirmação pendente
    this.clearPendingConfirmation();

    // Cria um novo observable que emite apenas uma vez
    const response$ = new Observable<boolean>(subscriber => {
      const subscription = this.userResponseSubject.pipe(take(1)).subscribe({
        next: (response) => subscriber.next(response),
        complete: () => subscriber.complete()
      });

      this.currentResponseSubscription = {
        unsubscribe: () => subscription.unsubscribe()
      };
    });

    // Mostra o modal
    this.showModalSubject.next({ message, acceptText, cancelText });

    return response$;
  }

  accept(): void {
    this.userResponseSubject.next(true);
    this.closeModal();
  }

  cancel(): void {
    this.userResponseSubject.next(false);
    this.closeModal();
  }

  private closeModal(): void {
    this.showModalSubject.next(null);
    this.clearPendingConfirmation();
  }

  private clearPendingConfirmation(): void {
    if (this.currentResponseSubscription) {
      this.currentResponseSubscription.unsubscribe();
      this.currentResponseSubscription = null;
    }
  }
}
