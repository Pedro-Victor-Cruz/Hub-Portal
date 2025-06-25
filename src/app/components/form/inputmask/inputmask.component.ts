import { Component, EventEmitter, forwardRef, Input, Output } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseInputComponent } from '../base-input.component';
import {MaskDirective} from './inputmask.directive';

@Component({
  selector: 'ub-inputmask',
  imports: [BaseInputComponent, MaskDirective],
  templateUrl: './inputmask.component.html',
  standalone: true,
  styleUrl: '../base-input.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => InputmaskComponent),
      multi: true,
    },
  ],
})
export class InputmaskComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() placeholder: string = '';
  @Input() type: string = 'text';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() mask: string = ''; // Padrão de máscara (ex: "999.999.999-99|99-999-999/9999-99")
  @Input() returnRawValue: boolean = true; // Define se o valor retornado será com ou sem máscara
  @Output() valueChange = new EventEmitter<string>();
  @Output() input = new EventEmitter<Event>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  maskedValue: string = ''; // Valor com máscara
  rawValue: string = ''; // Valor sem máscara

  onChange: any = () => {};
  onTouched: any = () => {};

  writeValue(value: string): void {
    this.maskedValue = value;
    this.rawValue = this.removeMask(value);
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  onInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.maskedValue = value;
    this.rawValue = this.removeMask(value);

    // Emite o valor com ou sem máscara, dependendo da configuração
    const emittedValue = this.returnRawValue ? this.rawValue : this.maskedValue;
    this.onChange(emittedValue);
    this.valueChange.emit(emittedValue);
    this.input.emit(event);
  }

  onChangeEvent(event: Event): void {
    this.change.emit(event);
  }

  onClick(event: Event): void {
    this.click.emit(event);
  }

  // Remove a máscara do valor
  removeMask(value: string): string {
    return value.replace(/\D/g, ''); // Remove todos os não dígitos
  }
}
