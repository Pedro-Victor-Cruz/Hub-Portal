import {
  Component,
  EventEmitter,
  forwardRef,
  Input,
  Output,
  ViewChild,
  ElementRef,
  OnChanges,
  SimpleChanges
} from '@angular/core';
import {ControlValueAccessor, NG_VALUE_ACCESSOR} from '@angular/forms';
import {BaseInputComponent} from '../base-input.component';
import {NgForOf, NgIf} from '@angular/common';

@Component({
  selector: 'ub-input',
  imports: [BaseInputComponent, NgForOf, NgIf],
  templateUrl: './input.component.html',
  standalone: true,
  styleUrls: ['../base-input.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => InputComponent),
      multi: true,
    },
  ],
})
export class InputComponent implements ControlValueAccessor, OnChanges {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() placeholder: string = '';
  @Input() type: string = 'text';
  @Input() value: string = '';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() suggestions: string[] = []; // Lista de sugestões
  @Output() valueChange = new EventEmitter<string>();
  @Output() blur = new EventEmitter<FocusEvent>();
  @Output() input = new EventEmitter<Event>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  @ViewChild('inputElement') inputElement!: ElementRef; // Acessa o elemento input

  ngOnChanges(changes: SimpleChanges) {
    if (changes['suggestions']) {
      console.log('Sugestões atualizadas:', this.suggestions);

    }
  }

  showSuggestions: boolean = false; // Controla a visibilidade das sugestões
  filteredSuggestions: string[] = []; // Sugestões filtradas

  onChange: any = () => {
  };
  onTouched: any = () => {
  };

  writeValue(value: string): void {
    this.value = value;
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  onInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.value = value;
    this.onChange(value);
    this.valueChange.emit(value);
    this.input.emit(event);

    this.filterSuggestions();
  }

  onChangeEvent(event: Event): void {
    this.change.emit(event);
  }

  onClick(event: Event): void {
    this.click.emit(event);
    this.showSuggestions = true; // Mostra as sugestões ao clicar no input
    this.filterSuggestions();
  }

  filterSuggestions(): void {
    const value = this.value.toLowerCase();
    this.filteredSuggestions = this.suggestions.filter((suggestion) =>
      suggestion.toLowerCase().includes(value)
    );
    if (this.filteredSuggestions.length === 0) {
      this.filteredSuggestions = this.suggestions; // Se não houver sugestões filtradas, mostra todas
    }
  }

  onBlur(event: FocusEvent): void {
    // Verifica se o clique foi dentro do modal de sugestões
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (relatedTarget && this.inputElement.nativeElement.contains(relatedTarget)) {
      return; // Não fecha o modal se o clique foi dentro do modal
    }

    this.onTouched();
    this.blur.emit(event);
    this.showSuggestions = false; // Esconde as sugestões ao perder o foco
  }

  selectSuggestion(suggestion: string): void {
    this.value = suggestion;
    this.onChange(suggestion);
    this.valueChange.emit(suggestion);

    // Atualiza o valor do campo de entrada manualmente
    if (this.inputElement && this.inputElement.nativeElement) {
      this.inputElement.nativeElement.value = suggestion;
    }

    this.showSuggestions = false; // Esconde as sugestões após a seleção
  }
}
