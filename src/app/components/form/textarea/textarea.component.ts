import { Component, EventEmitter, forwardRef, Input, Output } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseInputComponent } from '../base-input.component';

@Component({
  selector: 'ub-textarea',
  imports: [BaseInputComponent],
  templateUrl: './textarea.component.html',
  standalone: true,
  styleUrls: ['../base-input.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => TextareaComponent),
      multi: true,
    },
  ],
})
export class TextareaComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() placeholder: string = '';
  @Input() value: string = '';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() maxlength: number | undefined = undefined; // Limite máximo de caracteres
  @Input() minLength: number | undefined = undefined; // Limite mínimo de caracteres
  @Input() resize: 'none' | 'both' | 'horizontal' | 'vertical' = 'vertical'; // Controle de redimensionamento
  @Input() rows: number = 3; // Número de linhas visíveis
  @Output() valueChange = new EventEmitter<string>();
  @Output() input = new EventEmitter<Event>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  characterCount: number = 0; // Contador de caracteres

  onChange: any = () => {};
  onTouched: any = () => {};

  writeValue(value: string): void {
    this.value = value || '';
    this.updateCharacterCount();
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  onInput(event: Event): void {
    const value = (event.target as HTMLTextAreaElement).value;
    this.value = value;
    this.updateCharacterCount();
    this.onChange(value);
    this.valueChange.emit(value);
    this.input.emit(event);
  }

  onChangeEvent(event: Event): void {
    this.change.emit(event);
  }

  onClick(event: Event): void {
    this.click.emit(event);
  }

  // Atualiza o contador de caracteres
  private updateCharacterCount(): void {
    this.characterCount = this.value ? this.value.length : 0;
  }

  // Verifica se o valor atende ao minLength
  get isMinLengthValid(): boolean {
    return this.minLength ? this.value.length >= this.minLength : true;
  }

  // Verifica se o valor atende ao maxlength
  get isMaxLengthValid(): boolean {
    return this.maxlength ? this.value.length <= this.maxlength : true;
  }
}
