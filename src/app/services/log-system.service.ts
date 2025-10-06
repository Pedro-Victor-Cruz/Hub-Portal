import { Injectable } from '@angular/core';
import { environment } from "../../environments/environment";
import { ToastService } from '../components/toast/toast.service';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { Utils } from './utils.service';

@Injectable({
  providedIn: 'root'
})
export class LogSystemService {
  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {}

  async getLogs(filters: any): Promise<any[]> {
    try {
      // Construir parâmetros da query
      let params = new HttpParams();

      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });

      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs`, { params })
      ) as any;

      return response.data || [];
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return [];
    }
  }

  async getStats(options: { start_date?: any, end_date?: any }): Promise<any> {
    try {
      let params = new HttpParams();

      if (options.start_date) {
        params = params.set('start_date', options.start_date);
      }

      if (options.end_date) {
        params = params.set('end_date', options.end_date);
      }

      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs/statistics/dashboard`, { params })
      ) as any;

      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  async getLogDetails(id: number): Promise<any> {
    try {
      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs/${id}`)
      ) as any;

      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  async getUserTimeline(userId: number, perPage: number = 50): Promise<any> {
    try {
      let params = new HttpParams().set('per_page', perPage.toString());

      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs/user/${userId}/timeline`, { params })
      ) as any;

      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  async getModelHistory(modelType: string, modelId: number): Promise<any[]> {
    try {
      let params = new HttpParams()
        .set('model_type', modelType)
        .set('model_id', modelId.toString());

      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs/model/history`, { params })
      ) as any;

      return response.data || [];
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return [];
    }
  }

  async getBatchLogs(batchId: string): Promise<any[]> {
    try {
      const response = await firstValueFrom(
        this.http.get(`${this.API_URL}/logs/batch/${batchId}`)
      ) as any;

      return response.data || [];
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return [];
    }
  }

  async cleanup(days: number): Promise<any> {
    try {
      const response = await firstValueFrom(
        this.http.delete(`${this.API_URL}/logs/cleanup`, {
          body: { days }
        })
      ) as any;

      this.toast.success(response.message || 'Logs limpos com sucesso');
      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      throw err;
    }
  }
}
