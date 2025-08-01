import {
  Component,
  ElementRef,
  EventEmitter,
  forwardRef,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
  TemplateRef, ViewContainerRef
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseInputComponent } from '../base-input.component';
import { FormsModule } from '@angular/forms';
import { Overlay, OverlayRef } from '@angular/cdk/overlay';
import { TemplatePortal } from '@angular/cdk/portal';

@Component({
  selector: 'ub-dropdown',
  standalone: true,
  imports: [BaseInputComponent, FormsModule],
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
  @Input() filter: boolean = true;
  @Input() clearable: boolean = false;
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() value: any | null = null;
  @Output() valueChange = new EventEmitter<string | null>();
  @Output() change = new EventEmitter<Event>();
  @Output() click = new EventEmitter<Event>();

  @ViewChild('dropdownContent') dropdownContent!: TemplateRef<any>;

  isOpen: boolean = false;
  filterText: string = '';
  filteredOptions: any[] = [];
  selectedOptionLabel: string = '';
  private overlayRef!: OverlayRef;

  constructor(
    private elementRef: ElementRef,
    private overlay: Overlay,
    private viewContainerRef: ViewContainerRef
  ) {}

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
    if (this.isOpen) {
      this.closeDropdown();
    } else {
      this.openDropdown();
    }
  }

  openDropdown(): void {
    if (this.isOpen) return;

    const portal = new TemplatePortal(this.dropdownContent, this.viewContainerRef);

    const positionStrategy = this.overlay.position()
      .flexibleConnectedTo(this.elementRef.nativeElement.querySelector('.dropdown-header'))
      .withPositions([
        {
          originX: 'start',
          originY: 'bottom',
          overlayX: 'start',
          overlayY: 'top',
          offsetY: 4
        },
        {
          originX: 'start',
          originY: 'top',
          overlayX: 'start',
          overlayY: 'bottom',
          offsetY: -4
        }
      ]);

    this.overlayRef = this.overlay.create({
      positionStrategy,
      hasBackdrop: true,
      backdropClass: 'cdk-overlay-transparent-backdrop',
      scrollStrategy: this.overlay.scrollStrategies.reposition(),
      width: this.elementRef.nativeElement.offsetWidth
    });

    this.overlayRef.attach(portal);

    this.overlayRef.backdropClick().subscribe(() => {
      this.closeDropdown();
    });

    this.isOpen = true;
  }

  closeDropdown(): void {
    if (this.overlayRef) {
      this.overlayRef.dispose();
    }
    this.isOpen = false;
  }

  selectOption(option: any): void {
    this.value = option[this.optionValue];
    this.selectedOptionLabel = option[this.optionLabel];
    this.onChange(this.value);
    this.valueChange.emit(this.value);
    this.closeDropdown();
  }

  onFilterChange(): void {
    this.filteredOptions = this.options.filter((option) =>
      option[this.optionLabel].toLowerCase().includes(this.filterText.toLowerCase())
    );
  }

  clearSelection(event: Event): void {
    event.stopPropagation();
    this.value = '';
    this.selectedOptionLabel = '';
    this.onChange(this.value);
    this.valueChange.emit(this.value);
    this.closeDropdown();
  }

  updateSelectedOptionLabel(): void {
    if ((this.value === undefined || this.value === null || this.value === '') && this.clearable) {
      this.selectedOptionLabel = '';
      return;
    }
    const selectedOption = this.options.find(
      (option) => option[this.optionValue] === this.value
    );
    this.selectedOptionLabel = selectedOption ? selectedOption[this.optionLabel] : this.placeholder;
  }

  ngOnDestroy(): void {
    if (this.overlayRef) {
      this.overlayRef.dispose();
    }
  }
}
