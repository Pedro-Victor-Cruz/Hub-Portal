import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {Utils} from './utils.service';
import {ModalService} from '../modals/modal/modal.service';
import {ConfigIntegrationModal} from '../modals/config-integration/config-integration.modal';
import {DynamicParams} from '../components/dynamic-parameters/dynamic-parameters.component';
import {TestIntegrationModal} from '../modals/test-integration/test-integration.modal';

export interface Integration {
  integration_name: string;
  name: string;
  img: string;
  description: string;
  version: string;
  parameters: DynamicParams;
  is_configured: boolean
}

@Injectable(
  {providedIn: 'root'}
)
export class IntegrationService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService,
    private modalService: ModalService
  ) {
  }

  async getIntegrations(filters?: {
    [key: string]: any
  }): Promise<any> {
    const params: any = {};

    try {

      if (filters) {
        Object.keys(filters).forEach(key => {
          if (filters[key] !== null && filters[key] !== undefined) {
            params[key] = filters[key];
          }
        });
      }

      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/integration`, {params}));
      return response.data as Integration[];
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return [];
    }
  }

  async getIntegration(integration_name: string): Promise<Integration | null> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/integration/${integration_name}/info`));
      return response.data as Integration;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  async getConfigIntegration(integration_name: string) {
    try {
      const route = `${this.API_URL}/integration/${integration_name}`;
      const response = await firstValueFrom(this.http.get<any>(route));
      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  createIntegration(integration_name: string, configuration: any) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/integration/create`, {
      integration_name,
      configuration
    }));
  }

  updateIntegration(idIntegration: number, configuration: any) {
    return firstValueFrom(this.http.put<any>(`${this.API_URL}/integration/${idIntegration}/update`, {
      configuration
    }));
  }

  openConfigurationIntegrationModal(integration: Integration) {
    return this.modalService.open({
      title: `Integração ${integration.name}`,
      component: ConfigIntegrationModal,
      data: {
        integration_name: integration.integration_name
      }
    })
  }

  openTestIntegrationModal(integration: any, integration_id: number) {
    return this.modalService.open({
      title: `Testar Integração ${integration.name}`,
      component: TestIntegrationModal,
      data: {
        integration,
        integrationId: integration_id,
      }
    })
  }

  async testIntegration(integrationId: number) {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/integration/${integrationId}/test-connection`));
  }

  async deleteIntegration(id: number) {
    return firstValueFrom(this.http.delete<any>(`${this.API_URL}/integration/${id}/delete`));
  }

  async sendIntegrationSuggestion(suggestionForm: {
    integration_name: string;
    purpose: string;
    priority: string
    features: string | null;
  }) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/integration/suggestion`, suggestionForm));
  }

}
