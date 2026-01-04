import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {ModalService} from '../modals/modal/modal.service';
import {Utils} from './utils.service';
import {DashboardModal} from '../modals/dashboard/dashboard.modal';
import {DynamicQuery} from '../components/dynamic-query/dynamic-query.component';

export interface Dashboard {
  id?: number;
  key: string;
  name: string;
  description?: string | null;
  icon?: string | null;
  config?: any;
  visibility: 'authenticated' | 'restricted';
  permission_id?: number | null;
  active: boolean;
  is_navigable: boolean;
  is_home: boolean;
  ready?: boolean;
}

export interface DashboardSection {
  id?: number;
  dashboard_id: number;
  parent_section_id?: number | null;
  key: string;
  title?: string | null;
  description?: string | null;
  level: number;
  order: number;
  active: boolean;
  widgets?: DashboardWidget[];
  children?: DashboardSection[];
}

export interface DashboardWidget {
  id: number;
  section_id: number;
  dynamic_query_id?: number | null;
  key: string;
  title?: string | null;
  description?: string | null;
  widget_type: WidgetType;
  position_config: any;
  order: number;
  active: boolean;
  config: any;
  dynamic_query: DynamicQuery | null;
}

export type WidgetType =
  | 'chart_line'
  | 'chart_bar'
  | 'chart_pie'
  | 'chart_area'
  | 'chart_scatter'
  | 'table'
  | 'metric_card'
  | 'progress'
  | 'gauge'
  | 'heatmap'
  | 'list'
  | 'tree'
  | 'timeline'
  | 'map'
  | 'custom';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {

  private API_URL = environment.api;

  constructor(
    private toast: ToastService,
    private http: HttpClient,
    private modalService: ModalService
  ) {
  }

  // ==================== DASHBOARDS ====================

  getDashboards(activeOnly: boolean = true): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards`, {
        params: {active_only: activeOnly}
      })
    );
  }

  getDashboard(key: string, invitationToken?: string | null): Promise<any> {
    const params: any = {};
    if (invitationToken) params.invitation_token = invitationToken;

    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/${key}`, { params })
    );
  }

  getDashboardDetails(key: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/${key}/details`)
    );
  }

  getDashboardsByGroup(idGroup: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/group/${idGroup}`)
    );
  }

  getAccessibleDashboardsForUser(): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/user/accessible`)
    );
  }

  getHomeDashboard(): Promise<any> {
    return firstValueFrom(
      this.http.get(`${this.API_URL}/dashboards/home/list`)
    )
  }

  getNavigableDashboards(): Promise<any> {
    return firstValueFrom(
      this.http.get(`${this.API_URL}/dashboards/navigable/list`)
    )
  }

  createDashboard(data: any): Promise<any> {
    return firstValueFrom(
      this.http.post<any>(`${this.API_URL}/dashboards/create`, data)
    );
  }

  updateDashboard(key: string, data: any): Promise<any> {
    return firstValueFrom(
      this.http.put<any>(`${this.API_URL}/dashboards/${key}/update`, data)
    );
  }

  async deleteDashboard(key: string): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.delete<any>(`${this.API_URL}/dashboards/${key}/delete`)
      );
      this.toast.success('Dashboard excluído com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao excluir dashboard'));
      throw error;
    }
  }

  async duplicateDashboard(key: string, newKey: string, newName: string): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboards/${key}/duplicate`, {
          new_key: newKey,
          new_name: newName
        })
      );
      this.toast.success('Dashboard duplicado com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao duplicar dashboard'));
      throw error;
    }
  }

  // ==================== SECTIONS ====================

  async getSectionData(sectionId: number, params: any, invitationToken?: string | null) {
    const queryParams: any = {};
    if (invitationToken) queryParams.invitation_token = invitationToken;

    return await firstValueFrom(
      this.http.post<any>(
        `${this.API_URL}/dashboards/sections/${sectionId}/data`,
        { params: params || {} },
        { params: queryParams }
      )
    );
  }

  async createSection(dashboardKey: string, data: any): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.post<any>(
          `${this.API_URL}/dashboards/${dashboardKey}/sections/create`,
          data
        )
      );
      this.toast.success('Seção criada com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao criar seção'));
      throw error;
    }
  }

  async updateSection(sectionId: number, data: any): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.put<any>(
          `${this.API_URL}/dashboards/sections/${sectionId}/update`,
          data
        )
      );
      this.toast.success('Seção atualizada com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao atualizar seção'));
      throw error;
    }
  }

  async deleteSection(sectionId: number): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.delete<any>(
          `${this.API_URL}/dashboards/sections/${sectionId}/delete`
        )
      );
      this.toast.success('Seção removida com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao remover seção'));
      throw error;
    }
  }

  async listSectionWidgets(sectionId: number | undefined) {
    try {
      return await firstValueFrom(
        this.http.get<any>(
          `${this.API_URL}/dashboards/sections/${sectionId}/widgets`
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar widgets da seção'));
      throw error;
    }
  }

  // ==================== WIDGETS ====================

  async createWidget(sectionId: number, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(
          `${this.API_URL}/dashboards/sections/${sectionId}/widgets/create`,
          data
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao criar widget'));
      throw error;
    }
  }

  async updateWidget(widgetId: number, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.put<any>(
          `${this.API_URL}/dashboards/widgets/${widgetId}/update`,
          data
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao atualizar widget'));
      throw error;
    }
  }

  async deleteWidget(widgetId: number): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.delete<any>(
          `${this.API_URL}/dashboards/widgets/${widgetId}/delete`
        )
      );
      this.toast.success('Widget removido com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao remover widget'));
      throw error;
    }
  }

  async getWidgetData(widgetId: number, filters?: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboards/widgets/${widgetId}/data`, {
          params: filters || {}
        })
      );
    } catch (error) {
      throw error;
    }
  }

  async getParametersWidget(widgetId: number): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/dashboards/widgets/${widgetId}/parameters`)
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar parametros do widget'));
      throw error;
    }
  }

  // ==================== MODAL ====================

  openDashboardModal(dashboard?: Dashboard) {
    return this.modalService.open({
      title: dashboard ? 'Editar Dashboard' : 'Novo Dashboard',
      component: DashboardModal,
      data: {
        dashboardKey: dashboard?.key ?? null
      },
      size: 'xl'
    });
  }

  /** * Obtém opções de layout */ getLayoutTypes(): Array<{ value: string; label: string; icon: string }> {
    return [
      {value: 'grid', label: 'Grid', icon: 'pi pi-th-large'},
      {value: 'tabs', label: 'Abas', icon: 'pi pi-window-maximize'}
    ];
  }

  /**
   * Lista todos os convites de um dashboard
   */
  listInvitations(dashboardKey: string, activeOnly: boolean = false): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/${dashboardKey}/invitations`, {
        params: { active_only: activeOnly }
      })
    );
  }

  /**
   * Obtém detalhes de um convite específico
   */
  getInvitation(token: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/invitations/${token}`)
    );
  }

  /**
   * Cria um novo convite
   */
  async createInvitation(dashboardKey: string, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboards/${dashboardKey}/invitations`, data)
      );
    } catch (error) {
      throw error;
    }
  }

  /**
   * Atualiza um convite existente
   */
  async updateInvitation(token: string, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.put<any>(`${this.API_URL}/dashboards/invitations/${token}`, data)
      );
    } catch (error) {
      throw error;
    }
  }

  /**
   * Revoga um convite
   */
  async revokeInvitation(token: string): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboards/invitations/${token}/revoke`, {})
      );
    } catch (error) {
      throw error;
    }
  }

  /**
   * Deleta um convite permanentemente
   */
  async deleteInvitation(token: string): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.delete<any>(`${this.API_URL}/dashboards/invitations/${token}`)
      );
    } catch (error) {
      throw error;
    }
  }

  /**
   * Obtém estatísticas de uso de um convite
   */
  getInvitationStats(token: string): Promise<any> {
    return firstValueFrom(
      this.http.get<any>(`${this.API_URL}/dashboards/invitations/${token}/stats`)
    );
  }

  /**
   * Valida um convite
   */
  validateInvitation(token: string): Promise<any> {
    return firstValueFrom(
      this.http.post<any>(`${this.API_URL}/dashboards/invitations/${token}/validate`, {})
    );
  }

  setUserHomeDashboard(key: string) {
    return firstValueFrom(
      this.http.post<any>(`${this.API_URL}/dashboards/${key}/set-home`, {})
    );
  }
}
