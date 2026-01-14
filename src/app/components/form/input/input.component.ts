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

@Component({
  selector: 'ub-input',
  imports: [BaseInputComponent],
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
  @Input() disabled: boolean = false;
  @Input() required: boolean = false;
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() suggestions: string[] = [];
  @Output() valueChange = new EventEmitter<string>();
  @Output() blur = new EventEmitter<FocusEvent>();
  @Output() input = new EventEmitter<Event>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  @ViewChild('inputElement') inputElement!: ElementRef;

  ngOnChanges(changes: SimpleChanges) {
    if (changes['disabled']) {
      if (this.inputElement && this.inputElement.nativeElement) {
        this.inputElement.nativeElement.disabled = this.disabled;
      }
    }
  }

  showSuggestions: boolean = false;
  filteredSuggestions: string[] = [];

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

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }

  onInput(event: Event): void {
    // Se o campo está disabled, não permite alteração
    if (this.disabled) {
      event.preventDefault();
      return;
    }

    const value = (event.target as HTMLInputElement).value;
    this.value = value;
    this.onChange(value);
    this.valueChange.emit(value);
    this.input.emit(event);

    this.filterSuggestions();
  }

  onChangeEvent(event: Event): void {
    if (!this.disabled) {
      this.change.emit(event);
    }
  }

  onClick(event: Event): void {
    if (this.disabled) {
      return;
    }

    this.click.emit(event);
    this.showSuggestions = true;
    this.filterSuggestions();
  }

  filterSuggestions(): void {
    const value = this.value.toLowerCase();
    this.filteredSuggestions = this.suggestions.filter((suggestion) =>
      suggestion.toLowerCase().includes(value)
    );
    if (this.filteredSuggestions.length === 0) {
      this.filteredSuggestions = this.suggestions;
    }
  }

  onBlur(event: FocusEvent): void {
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (relatedTarget && this.inputElement.nativeElement.contains(relatedTarget)) {
      return;
    }

    this.onTouched();
    this.blur.emit(event);
    this.showSuggestions = false;
  }

  selectSuggestion(suggestion: string): void {
    if (this.disabled) {
      return;
    }

    this.value = suggestion;
    this.onChange(suggestion);
    this.valueChange.emit(suggestion);

    if (this.inputElement && this.inputElement.nativeElement) {
      this.inputElement.nativeElement.value = suggestion;
    }

    this.showSuggestions = false;
  }
}
