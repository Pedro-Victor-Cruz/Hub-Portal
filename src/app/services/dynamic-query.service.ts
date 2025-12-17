import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {ModalService} from '../modals/modal/modal.service';
import {DynamicQueryModal} from '../modals/dynamic-query/dynamic-query.modal';
import {Utils} from './utils.service';
import {
  DynamicQueryFilterBuilderModal
} from '../components/dynamic-query/dynamic-query-filter-builder/dynamic-query-filter-builder.modal';
import {DynamicQuery} from '../components/dynamic-query/dynamic-query.component';


@Injectable(
  {providedIn: 'root'}
)
export class DynamicQueryService {

  private API_URL = environment.api;

  constructor(
    private toast: ToastService,
    private http: HttpClient,
    private modalService: ModalService
  ) {
  }

  getDynamicQueries(): Promise<any> {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/queries`))
  }

  async getDynamicQuery(key: string): Promise<any> {
    try {
      return await firstValueFrom(this.http.get<any>(`${this.API_URL}/queries/${key}`));
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao buscar consulta dinâmica'));
      throw error;
    }
  }

  createDynamicQuery(data: any): Promise<any> {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/queries/create`, data));
  }

  updateDynamicQuery(key: string, data: any): Promise<any> {
    return firstValueFrom(this.http.put<any>(`${this.API_URL}/queries/${key}/update`, data));
  }

  async executeDynamicQuery(dynamicQuery: DynamicQuery, params: any): Promise<any> {
    let filters = dynamicQuery.active_filters || [];
    filters = filters.filter(f => f.visible);

    if (filters.length > 0) {
      const modalRef = this.modalService.open({
        title: 'Preencher Filtros',
        component: DynamicQueryFilterBuilderModal,
        data: {
          dynamicQuery: dynamicQuery
        }
      });

      // aguarda o usuário preencher os filtros e fechar o modal com `close(result)`
      const filledFilters = await modalRef;
      if (!filledFilters) {
        return Promise.reject('Execução cancelada pelo usuário.');
      }

      // preenche os parâmetros com os valores retornados do modal
      params = { ...params, ...filledFilters };
    }

    // só executa aqui, depois do preenchimento
    return firstValueFrom(
      this.http.post<any>(
        `${this.API_URL}/queries/${dynamicQuery.key}/execute`,
        params
      )
    );
  }


  async deleteDynamicQuery(key: string): Promise<any> {
    try {
      const response = await firstValueFrom(this.http.delete<any>(`${this.API_URL}/queries/${key}/delete`));
      this.toast.success('Consulta dinâmica excluída com sucesso!');
      return response;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao excluir consulta dinâmica'));
      throw error;
    }
  }

  openDynamicQueryModal(dynamicQuery?: DynamicQuery) {
    return this.modalService.open({
      title: 'Configurar Consulta Dinâmica',
      component: DynamicQueryModal,
      data: {
        dynamicQueryKey: dynamicQuery ? dynamicQuery.key : null
      }
    });
  }
}
