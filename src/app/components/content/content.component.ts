import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {FormErrorHandlerService} from '../form/form-error-handler.service';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';

@Component({
  selector: 'ub-content',
  imports: [
    FormsModule,
    ReactiveFormsModule
  ],
  templateUrl: './content.component.html',
  standalone: true,
  styleUrl: './content.component.scss'
})
export class ContentComponent implements OnInit {

  @Input() title: string = ''; // Título do conteúdo
  @Input() description: string = ''; // Descrição do conteúdo
  @Input() showBackButton: boolean = false; // Controla a visibilidade do botão de voltar
  @Input() loading: boolean = false; // Estado de carregamento
  @Input() loadingMessage: string = 'Carregando...'; // Mensagem exibida durante o carregamento

  @Output() back = new EventEmitter<void>(); // Evento emitido ao clicar no botão de voltar
  protected readonly FormErrorHandlerService = FormErrorHandlerService;

  constructor(
  ) {}

  ngOnInit() {}

  // Método chamado ao clicar no botão de voltar
  onBack(): void {
    this.back.emit();
  }

}
