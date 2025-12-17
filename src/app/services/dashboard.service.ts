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
  active: boolean;
  sections_count?: number;
  widgets_count?: number;
  filters_count?: number;
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
  id?: number;
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

  async getDashboard(key: string, context?: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/dashboards/${key}`, {
          params: context ? {context: JSON.stringify(context)} : {}
        })
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao buscar dashboard'));
      throw error;
    }
  }

  async createDashboard(data: any): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.post<any>(`${this.API_URL}/dashboards/create`, data)
      );
      this.toast.success('Dashboard criado com sucesso!');
      return res;
    } catch (error) {
      throw error;
    }
  }

  async updateDashboard(key: string, data: any): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.put<any>(`${this.API_URL}/dashboards/${key}/update`, data)
      );
      this.toast.success('Dashboard atualizado com sucesso!');
      return res;
    } catch (error) {
      throw error;
    }
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

  async executeDrillDown(
    sourceSectionId: number,
    targetSectionId: number,
    sourceData?: any,
    filters?: any
  ): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(
          `${this.API_URL}/dashboards/sections/${sourceSectionId}/drill-down/${targetSectionId}`,
          {
            source_data: sourceData || {},
            filters: filters || {}
          }
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao executar drill down'));
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
        this.http.get<any>(
          `${this.API_URL}/dashboards/widgets/${widgetId}/data`,
          {params: filters || {}}
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar dados do widget'));
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

  // ==================== FILTERS ====================

  async attachFilter(dashboardKey: string, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.post<any>(
          `${this.API_URL}/dashboards/${dashboardKey}/filters/attach`,
          data
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao anexar filtro'));
      throw error;
    }
  }

  async updateFilter(dashboardFilterId: number, data: any): Promise<any> {
    try {
      return await firstValueFrom(
        this.http.put<any>(
          `${this.API_URL}/dashboards/filters/${dashboardFilterId}/update`,
          data
        )
      );
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao atualizar filtro'));
      throw error;
    }
  }

  async detachFilter(dashboardFilterId: number): Promise<any> {
    try {
      const res = await firstValueFrom(
        this.http.delete<any>(
          `${this.API_URL}/dashboards/filters/${dashboardFilterId}/detach`
        )
      );
      this.toast.success('Filtro removido com sucesso!');
      return res;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao remover filtro'));
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

}
