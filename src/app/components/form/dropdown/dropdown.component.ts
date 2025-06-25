import {
  Component,
  ElementRef,
  EventEmitter,
  forwardRef,
  HostListener,
  Input,
  OnChanges,
  OnInit,
  Output, SimpleChanges
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseInputComponent } from '../base-input.component';
import { NgForOf, NgIf } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'ub-dropdown',
  standalone: true,
  imports: [BaseInputComponent, NgForOf, NgIf, FormsModule],
  templateUrl: './dropdown.component.html',
  styleUrls: ['../base-input.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => DropdownComponent),
      multi: true,
    },
  ],
})
export class DropdownComponent implements ControlValueAccessor, OnInit, OnChanges {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() options: any[] = [];
  @Input() optionLabel: string = 'label';
  @Input() optionDescription: string | undefined = undefined;
  @Input() optionValue: string = 'value';
  @Input() placeholder: string = 'Selecione uma opção';
  @Input() filter: boolean = true; // Habilita ou desabilita o filtro
  @Input() clearable: boolean = false; // Habilita ou desabilita a opção de limpar seleção
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() value: string | null = null;
  @Output() valueChange = new EventEmitter<string | null>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  isOpen: boolean = false;
  filterText: string = '';
  filteredOptions: any[] = [];
  selectedOptionLabel: string = '';

  constructor(private elementRef: ElementRef) {}

  onChange: any = () => {};
  onTouched: any = () => {};

  ngOnInit(): void {
    this.filteredOptions = this.options;
    this.updateSelectedOptionLabel();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['options'] && changes['options'].currentValue) {
      this.filteredOptions = changes['options'].currentValue;
      this.updateSelectedOptionLabel();
    }
    if (changes['value'] && changes['value'].currentValue) {
      this.writeValue(changes['value'].currentValue);
    }
  }

  writeValue(value: string): void {
    this.value = value;
    this.updateSelectedOptionLabel();
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  toggleDropdown(): void {
    this.isOpen = !this.isOpen;
    if (this.isOpen) {
      this.filterText = '';
      this.onFilterChange();
    }
  }

  selectOption(option: any): void {
    this.value = option[this.optionValue];
    this.selectedOptionLabel = option[this.optionLabel];
    this.onChange(this.value);
    this.valueChange.emit(this.value);
    this.isOpen = false;
  }

  onFilterChange(): void {
    this.filteredOptions = this.options.filter((option) =>
      option[this.optionLabel].toLowerCase().includes(this.filterText.toLowerCase())
    );
  }

  clearSelection(event: Event): void {
    event.stopPropagation(); // Previne a abertura do dropdown
    this.value = '';
    this.selectedOptionLabel = '';
    this.onChange(this.value);
    this.valueChange.emit(this.value);
    this.isOpen = false;
  }

  updateSelectedOptionLabel(): void {
    console.log("Novo updateSelectedOptionLabel chamado com value:", this.value);
    if ((this.value === undefined || this.value === null || this.value === '') && this.clearable) {
      this.selectedOptionLabel = '';
      return;
    }
    const selectedOption = this.options.find(
      (option) => option[this.optionValue] === this.value
    );
    this.selectedOptionLabel = selectedOption ? selectedOption[this.optionLabel] : this.placeholder;
  }

  @HostListener('document:click', ['$event'])
  onClickOutside(event: MouseEvent): void {
    if (this.isOpen && !this.elementRef.nativeElement.contains(event.target)) {
      this.isOpen = false;
    }
  }
}
