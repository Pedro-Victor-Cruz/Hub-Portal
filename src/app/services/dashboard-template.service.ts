import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../environments/environment';
import { ToastService } from '../components/toast/toast.service';
import { Utils } from './utils.service';

export interface DashboardTemplate {
  key: string;
  name: string;
  description: string;
  category?: string;
  icon?: string;
  usage_count: number;
}

export interface TemplatePreview {
  template: {
    key: string;
    name: string;
    description: string;
    category?: string;
    icon?: string;
  };
  requirements: {
    met: boolean;
    missing: string[];
    details: any;
  };
  statistics: {
    queries: number;
    filters: number;
    sections: number;
    widgets: number;
    max_section_depth: number;
  },
  queries: any[];
  sections: any[];
  capabilities: {
    title: string;
    description: string;
    items: string[];
  }[]
}

export interface ImportTemplateRequest {
  dashboard_name: string;
  dashboard_description?: string;
  dashboard_key?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardTemplateService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {}

  /**
   * Lista todos os templates disponíveis
   */
  getTemplates(filters?: {
    [key: string]: any
  }): Promise<any> {
    const params: any = {};

    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key] !== null && filters[key] !== undefined) {
          params[key] = filters[key];
        }
      });
    }

    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboard-templates`, { params })
    );
  }

  /**
   * Obtém detalhes de um template específico
   */
  getTemplate(key: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboard-templates/${key}`)
    );
  }

  /**
   * Obtém preview de um template
   */
  getPreview(key: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboard-templates/${key}/preview`)
    );
  }

  /**
   * Importa um template
   */
  async importTemplate(key: string, data: ImportTemplateRequest): Promise<any> {
    try {
      const response = await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboard-templates/${key}/import`, data)
      );

      this.toast.success(response.message || 'Template importado com sucesso!');
      return response;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao importar template'));
      throw error;
    }
  }

  /**
   * Lista categorias disponíveis
   */
  getCategories(): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboard-templates/categories`)
    );
  }
}
