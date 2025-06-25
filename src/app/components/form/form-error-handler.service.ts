import { Injectable } from '@angular/core';
import {AbstractControl, FormControl, FormGroup, ValidationErrors} from "@angular/forms";

@Injectable()
export class FormErrorHandlerService {

  constructor() {}

  /**
   * Obtém mensagens de erro de validação de um FormGroup.
   *
   * @param form - O FormGroup a ser validado.
   * @param apiErrors - Erros retornados pela API (opcional).
   * @param submitted - Indica se o formulário foi submetido (opcional).
   * @returns Um objeto com as mensagens de erro.
   */
  public static getErrorMessages(
    form: FormGroup | null,
    apiErrors: { [key: string]: string[] } = {},
    submitted: boolean = false
  ): { [key: string]: string } {
    const errors: { [key: string]: string } = {};

    // Função para coletar erros de um controle
    const collectErrors = (control: AbstractControl, path: string = '') => {
      if (control instanceof FormGroup) {
        // Percorre todos os controles dentro do FormGroup
        Object.keys(control.controls).forEach((key) => {
          const controlKey = control.get(key);
          if (controlKey) {
            collectErrors(controlKey, path ? `${path}.${key}` : key);
          }
        });
      } else if (control instanceof FormControl) {
        // Verifica se o controle foi tocado ou se o formulário foi submetido
        const isTouchedOrSubmitted = control.touched || control.dirty || submitted;
        if (isTouchedOrSubmitted && control.errors) {
          errors[path] = this.getControlErrorMessage(control.errors);
        }
      }
    };

    if (form) collectErrors(form);

    // Adiciona erros da API
    if (apiErrors && Object.keys(apiErrors).length > 0) {
      Object.keys(apiErrors).forEach((key) => {
        errors[key] = apiErrors[key].join(' ');
      });
    }

    return errors;
  }

  /**
   * Obtém a mensagem de erro para um controle com base em seus erros de validação.
   *
   * @param errors - Os erros de validação do controle.
   * @returns A mensagem de erro correspondente.
   */
  private static getControlErrorMessage(errors: ValidationErrors): string {
    const errorMessages: { [key: string]: string } = {
      required: 'Este campo é obrigatório',
      email: 'E-mail inválido',
      pattern: 'Formato inválido',
      passwordMismatch: 'As senhas não conferem',
      confirmPassword: 'Confirme a senha',
    };

    // Verifica erros específicos que possuem propriedades adicionais
    if (errors['minlength']) {
      return `Mínimo de ${errors['minlength'].requiredLength} caracteres`;
    }
    if (errors['maxlength']) {
      return `Máximo de ${errors['maxlength'].requiredLength} caracteres`;
    }
    if (errors['min']) {
      return `Mínimo de ${errors['min'].min}`;
    }
    if (errors['max']) {
      return `Máximo de ${errors['max'].max}`;
    }

    // Retorna a primeira mensagem de erro encontrada no mapeamento
    for (const key of Object.keys(errors)) {
      if (errorMessages[key]) {
        return errorMessages[key];
      }
    }

    return 'Erro de validação';
  }

  public static getError(field: string, formErrors: { [key: string]: string }) {
    return formErrors[field] || '';
  }

  public static getErros(formErrors: { [key: string]: string }) {
    return Object.keys(formErrors).map(key => formErrors[key]);
  }
}
