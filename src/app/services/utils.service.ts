import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {AppComponent} from "../app.component";
import {ToastService} from '../components/toast/toast.service';
import {FormErrorHandlerService} from '../components/form/form-error-handler.service';

@Injectable(
  {providedIn: 'root'}
)
export class Utils {

  constructor(
    private toast: ToastService
  ) {
  }

  public static slug(str: string): string {
    // Normaliza a string removendo acentuações e caracteres especiais
    const normalized = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // Remove caracteres indesejados, transforma espaços em hífens e remove múltiplos hífens
    let slug = normalized
      .replace(/[^a-zA-Z0-9\s-]/g, '')  // Remove caracteres não permitidos
      .trim()                           // Remove espaços em branco do início e fim
      .replace(/\s+/g, '-')             // Substitui espaços por hífens
      .replace(/-+/g, '-')              // Substitui múltiplos hífens consecutivos por um único hífen
      .toLowerCase();                   // Transforma tudo para minúsculas

    // Limita o slug a no máximo 50 caracteres, sem cortar palavras no meio
    if (slug.length > 50) {
      const trimmedSlug = slug.slice(0, 50);
      const lastHyphenIndex = trimmedSlug.lastIndexOf('-');

      // Corta o slug até o último hífen antes dos 50 caracteres, se possível
      slug = (lastHyphenIndex > 0) ? trimmedSlug.slice(0, lastHyphenIndex) : trimmedSlug;
    }

    // Remove hífens finais, caso existam
    return slug.replace(/-+$/, '');
  }

  public static isHtml(str: string) {
    return /<[a-z][\s\S]*>/i.test(str);
  }

  public static clipboard(value: string) {
    navigator.clipboard.writeText(value);
  }

  public static hexToRgba(hex: string, alpha: number): string {
    // Remove o caractere #, se existir
    const sanitizedHex = hex.replace('#', '');
    // Divide em componentes RGB
    const bigint = parseInt(sanitizedHex, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    // Retorna o valor em RGBA
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  public static isProduction() {
    return environment.production;
  }

  // Função para limpar número de telefone e deixar apenas números
  public static cleanPhone(phone: string): string {
    return phone.replace(/\D/g, '');
  }

  public handleErrorsForm(error: any, form: any) {
    if (error.error.message === 'Validation error') {
      return  FormErrorHandlerService.getErrorMessages(form, error.error.errors);
    } else {
      const message = error.error.message || 'Ops! Ocorreu um erro inesperado.';
      this.toast.error(message);
    }
    return {}
  }

}
