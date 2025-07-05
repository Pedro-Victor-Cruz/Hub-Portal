import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';

@Injectable(
  {providedIn: 'root'}
)
export class ParameterService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {
  }

  async getParameters(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/parameter`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar os parâmetros do sistema';
      this.toast.error(message);
      return [];
    }
  }

  async getParameterCategories(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/parameter/categories`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as categorias de parâmetros';
      this.toast.error(message);
      return [];
    }
  }

  createParameter(form: any) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/parameter/create`, form))
  }

  updateParameter(id: number | string, form: any) {
    return firstValueFrom(this.http.put<any>(`${this.API_URL}/parameter/${id}/update`, form))
  }

  async deleteParameter(id: number | string) {
    try {
      const response = await firstValueFrom(this.http.delete<any>(`${this.API_URL}/parameter/${id}/delete`));
      this.toast.success('Parâmetro deletado com sucesso');
      return response;
    } catch (err: any) {
      const message_1 = err.error.message || 'Erro ao deletar o parâmetro';
      this.toast.error(message_1);
      throw err;
    }
  }
}
