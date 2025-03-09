import { Component, EventEmitter, forwardRef, Input, Output } from '@angular/core';
import { BaseInputComponent } from '../base-input.component';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { NgIf } from '@angular/common';

@Component({
  selector: 'ub-image',
  imports: [
    BaseInputComponent,
    NgIf,
  ],
  templateUrl: './image.component.html',
  standalone: true,
  styleUrls: ['./image.component.scss', '../base-input.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ImageComponent),
      multi: true,
    },
  ],
})
export class ImageComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() imageUrl: string | null = null; // URL da imagem existente
  @Output() removeImage = new EventEmitter<void>();
  @Output() valueChange = new EventEmitter<File | null>(); // Emite o arquivo (File) ou null
  @Output() change = new EventEmitter<Event>();
  @Output() blur = new EventEmitter<FocusEvent>();

  imageFile: File | null = null; // Arquivo da imagem selecionada

  onChange: any = () => {};
  onTouched: any = () => {};

  // Método chamado quando o valor do controle é alterado programaticamente
  writeValue(value: any): void {
    if (value) {
      this.imageFile = value;
    } else if (this.imageUrl) {
      this.imageFile = null; // Usa a imageUrl se value for nulo
    } else {
      this.imageFile = null;
    }
  }

  // Método chamado quando o valor do controle é alterado pelo usuário
  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  // Método chamado quando o controle é "tocado"
  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  // Método chamado quando um arquivo é selecionado
  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      this.imageFile = file;
      this.onChange(file); // Atualiza o valor do controle com o arquivo
      this.valueChange.emit(file); // Emite o evento de mudança com o arquivo
      this.change.emit(event); // Emite o evento de change
    }
  }

  // Método para remover a imagem
  onRemoveImage(): void {
    this.imageFile = null;
    this.imageUrl = null; // Limpa a URL da imagem existente
    this.removeImage.emit(); // Emite o evento de remoção
    this.onChange(null); // Atualiza o valor do controle
    this.valueChange.emit(null); // Emite o evento de mudança
  }

  // Método chamado quando o input perde o foco
  onBlur(event: FocusEvent): void {
    this.onTouched();
    this.blur.emit(event);
  }
}
