import {Component, EventEmitter, Input, Output} from '@angular/core';

@Component({
  selector: 'ub-content',
  imports: [
  ],
  templateUrl: './content.component.html',
  standalone: true,
  styleUrl: './content.component.scss'
})
export class ContentComponent {

  @Input({ required: true }) title: string = ''; // Título do conteúdo
  @Input() showBackButton: boolean = false; // Controla a visibilidade do botão de voltar
  @Input() loading: boolean = false; // Estado de carregamento
  @Input() loadingMessage: string = 'Carregando...'; // Mensagem exibida durante o carregamento

  @Output() back = new EventEmitter<void>(); // Evento emitido ao clicar no botão de voltar

  // Método chamado ao clicar no botão de voltar
  onBack(): void {
    this.back.emit();
  }
}
