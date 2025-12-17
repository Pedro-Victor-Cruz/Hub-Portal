// unified-filters.service.ts
import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {environment} from '../../environments/environment';

export enum EntityType {
  QUERY = 'query',
  DASHBOARD = 'dashboard'
}

@Injectable({
  providedIn: 'root'
})
export class FilterService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient
  ) {}

  private getEndpointPrefix(entityType: EntityType): string {
    return entityType === EntityType.QUERY ? 'queries' : 'dashboards';
  }

  /**
   * Método público para obter filtros com base no tipo de entidade
   */
  async getFilters(entityType: EntityType, entityKey: string, onlyActive: boolean = true): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);
    const params = new URLSearchParams();

    if (!onlyActive) {
      params.set('only_active', 'false');
    }

    const queryString = params.toString() ? `?${params.toString()}` : '';

    return await firstValueFrom(
      this.http.get<any>(`${this.API_URL}/${prefix}/${entityKey}/filters${queryString}`)
    );
  }

  /**
   * Cria um novo filtro
   */
  async createFilter(entityType: EntityType, entityKey: string, filterData: any): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);

    return await firstValueFrom(
      this.http.post<any>(`${this.API_URL}/${prefix}/${entityKey}/filters/create`, filterData)
    );
  }

  /**
   * Atualiza um filtro existente
   */
  async updateFilter(entityType: EntityType, entityKey: string, varName: string, filterData: any): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);

    return await firstValueFrom(
      this.http.put<any>(`${this.API_URL}/${prefix}/${entityKey}/filters/${varName}/update`, filterData)
    );
  }

  /**
   * Remove um filtro
   */
  async deleteFilter(entityType: EntityType, entityKey: string, varName: string): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);

    return await firstValueFrom(
      this.http.delete<any>(`${this.API_URL}/${prefix}/${entityKey}/filters/${varName}/delete`)
    );
  }

  /**
   * Reordena os filtros
   */
  async reorderFilters(entityType: EntityType, entityKey: string, orderedVarNames: string[]): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);

    return await firstValueFrom(
      this.http.put<any>(`${this.API_URL}/${prefix}/${entityKey}/filters/reorder`, {
        order: orderedVarNames
      })
    );
  }

  /**
   * Obtém sugestões de variáveis
   */
  async getVariableSuggestions(entityType: EntityType, entityKey: string): Promise<any> {
    const prefix = this.getEndpointPrefix(entityType);

    return await firstValueFrom(
      this.http.get<any>(`${this.API_URL}/${prefix}/${entityKey}/filters/variable-suggestions`)
    );
  }

  /**
   * Obtém tipos de filtros disponíveis
   */
  async getFilterTypes(entityType?: EntityType): Promise<any> {
    const prefix = entityType ? this.getEndpointPrefix(entityType) : 'queries';

    return await firstValueFrom(
      this.http.get<any>(`${this.API_URL}/${prefix}/filters/types`)
    );
  }
}
