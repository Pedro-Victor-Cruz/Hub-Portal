import {Component, ElementRef, EventEmitter, forwardRef, HostListener, Input, Output} from '@angular/core';
import {BaseInputComponent} from '../base-input.component';
import {ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR} from '@angular/forms';

@Component({
  selector: 'ub-multiselect',
  imports: [
    BaseInputComponent,
    FormsModule,
  ],
  templateUrl: './multiselect.component.html',
  standalone: true,
  styleUrl: '../base-input.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => MultiselectComponent),
      multi: true,
    },
  ],
})
export class MultiselectComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() options: any[] = [];
  @Input() optionLabel: string = 'label';
  @Input() optionValue: string = 'value';
  @Input() placeholder: string = 'Selecione uma ou mais opções';
  @Input() filter: boolean = true; // Habilita ou desabilita o filtro
  @Input() value: any[] = [];
  @Input() error: string = '';
  @Input() success: string = '';
  @Output() valueChange = new EventEmitter<any[]>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  isOpen: boolean = false;
  filterText: string = '';
  filteredOptions: any[] = [];
  selectedOptions: any[] = [];

  constructor(private elementRef: ElementRef) {}


  onChange: any = () => {
  };

  onTouched: any = () => {
  };

  ngOnInit(): void {
    this.filteredOptions = this.options;
    this.updateSelectedOptions();
  }

  writeValue(value: any[]): void {
    this.value = value || [];
    this.updateSelectedOptions();
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  toggleMultiselect(): void {
    this.isOpen = !this.isOpen;
    if (this.isOpen) {
      this.filterText = '';
      this.onFilterChange();
    }
  }

  toggleOption(option: any): void {
    const optionValue = option[this.optionValue];
    if (this.isSelected(option)) {
      this.value = this.value.filter((val) => val !== optionValue);
    } else {
      this.value = [...this.value, optionValue];
    }
    this.updateSelectedOptions();
    this.onChange(this.value);
    this.valueChange.emit(this.value);
  }

  isSelected(option: any): boolean {
    return this.value.includes(option[this.optionValue]);
  }

  onFilterChange(): void {
    this.filteredOptions = this.options.filter((option) =>
      option[this.optionLabel].toLowerCase().includes(this.filterText.toLowerCase())
    );
  }

  updateSelectedOptions(): void {
    this.selectedOptions = this.options
      .filter((option) => this.value.includes(option[this.optionValue]))
      .map((option) => option[this.optionLabel]);
  }

  // Verifica se todos os itens estão selecionados
  areAllSelected(): boolean {
    return this.filteredOptions.every((option) =>
      this.value.includes(option[this.optionValue])
    );
  }

  // Seleciona ou deseleciona todos os itens
  toggleSelectAll(event: Event): void {
    const isChecked = (event.target as HTMLInputElement).checked;
    if (isChecked) {
      // Seleciona todos os itens visíveis (filtrados)
      this.value = [
        ...new Set([
          ...this.value,
          ...this.filteredOptions.map((option) => option[this.optionValue]),
        ]),
      ];
    } else {
      // Deseleciona todos os itens visíveis (filtrados)
      this.value = this.value.filter(
        (val) =>
          !this.filteredOptions.some(
            (option) => option[this.optionValue] === val
          )
      );
    }
    this.updateSelectedOptions();
    this.onChange(this.value);
    this.valueChange.emit(this.value);
  }

  @HostListener('document:click', ['$event'])
  onClickOutside(event: MouseEvent): void {
    if (this.isOpen && !this.elementRef.nativeElement.contains(event.target)) {
      this.isOpen = false;
    }
  }
}
