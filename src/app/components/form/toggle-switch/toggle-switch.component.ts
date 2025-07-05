import {Component, EventEmitter, forwardRef, Input, Output} from '@angular/core';
import {BaseInputComponent} from '../base-input.component';
import {ControlValueAccessor, NG_VALUE_ACCESSOR} from '@angular/forms';
import {NgClass} from '@angular/common';

@Component({
  selector: 'ub-toggle-switch',
  imports: [
    BaseInputComponent,
    NgClass
  ],
  templateUrl: './toggle-switch.component.html',
  standalone: true,
  styleUrl: './toggle-switch.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ToggleSwitchComponent),
      multi: true,
    },
  ],
})
export class ToggleSwitchComponent implements ControlValueAccessor {
  @Input() inline: boolean = false;
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() disabled: boolean = false;
  @Input() value: boolean = false;
  @Output() valueChange = new EventEmitter<boolean>();
  @Output() change = new EventEmitter<Event>();

  @Output() click = new EventEmitter<Event>();
  onChange: any = () => {};
  onTouched: any = () => {};

  writeValue(value: boolean): void {
    this.value = value;
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  onChangeEvent(event: Event): void {
    const isChecked = (event.target as HTMLInputElement).checked;
    this.value = isChecked;
    this.onChange(isChecked);
    this.valueChange.emit(isChecked);
    this.change.emit(event);
  }

  onClick(event: Event): void {
    this.click.emit(event);
  }
}
