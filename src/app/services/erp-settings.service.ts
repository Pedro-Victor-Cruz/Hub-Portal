import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';

@Injectable(
  {providedIn: 'root'}
)
export class ErpSettingsService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {
  }

  async getErpSettings(idCompany: string): Promise<any> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/company/erp-settings/${idCompany}`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as configurações ERP';
      this.toast.error(message);
      return [];
    }
  }


  createErpSettings(data: any) {
      return firstValueFrom(this.http.post<any>(`${this.API_URL}/company/erp-settings/create`, data));
  }

  updateErpSettings(id: string, formData: any) {
      return firstValueFrom(this.http.put<any>(`${this.API_URL}/company/erp-settings/${id}/update`, formData));
  }

  async deleteErpSettings(id: string) {
    try {
      return await firstValueFrom(this.http.delete<any>(`${this.API_URL}/company/erp-settings/${id}/delete`));
    } catch (err: any) {
      const message = err.error.message || 'Erro ao deletar as configurações ERP';
      this.toast.error(message);
      console.log(err);
      return err;
    }
  }

  testConnection(idCompany: string) {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/company/erp-settings/${idCompany}/test-connection`));
  }

}
