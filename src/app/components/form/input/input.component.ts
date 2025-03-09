import { Component, EventEmitter, forwardRef, Input, Output, ViewChild, ElementRef } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseInputComponent } from '../base-input.component';
import { NgForOf, NgIf } from '@angular/common';

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
export class InputComponent implements ControlValueAccessor {
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

  showSuggestions: boolean = false; // Controla a visibilidade das sugestões
  filteredSuggestions: string[] = []; // Sugestões filtradas

  onChange: any = () => {};
  onTouched: any = () => {};

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

    // Filtra as sugestões com base no valor digitado
    this.filteredSuggestions = this.suggestions.filter((suggestion) =>
      suggestion.toLowerCase().includes(value.toLowerCase())
    );

    // Mostra as sugestões se houver correspondências
    this.showSuggestions = this.filteredSuggestions.length > 0;
  }

  onChangeEvent(event: Event): void {
    this.change.emit(event);
  }

  onClick(event: Event): void {
    this.click.emit(event);
    this.showSuggestions = true; // Mostra as sugestões ao clicar no input
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
    console.log('suggestion', suggestion);
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
