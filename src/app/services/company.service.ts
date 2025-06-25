import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';

@Injectable(
  {providedIn: 'root'}
)
export class CompanyService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {
  }

  async getCompanies(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/company`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as empresas';
      this.toast.error(message);
      return [];
    }
  }

  async getCompany(id: string): Promise<any> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/company/${id}`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar a empresa';
      this.toast.error(message);
      return {};
    }
  }

  createCompany(data: any) {
      return firstValueFrom(this.http.post<any>(`${this.API_URL}/company/create`, data));
  }

  updateCompany(id: string, formData: any) {
      return firstValueFrom(this.http.put<any>(`${this.API_URL}/company/${id}/update`, formData));
  }

  async deleteCompany(id: string) {
    try {
      return await firstValueFrom(this.http.delete<any>(`${this.API_URL}/company/${id}/delete`));
    } catch (err: any) {
      const message = err.error.message || 'Erro ao deletar a empresa';
      this.toast.error(message);
      console.log(err);
      return err;
    }
  }
}
