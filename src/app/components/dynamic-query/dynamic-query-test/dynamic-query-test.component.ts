import {Component, Input} from '@angular/core';
import {CommonModule} from '@angular/common';
import {NgxJsonViewerModule} from 'ngx-json-viewer';
import {ButtonComponent} from '../../form/button/button.component';
import {DynamicQueryService} from '../../../services/dynamic-query.service';
import {ToastService} from '../../toast/toast.service';
import {Utils} from '../../../services/utils.service';
import {DynamicQuery} from '../dynamic-query.component';

interface TestResult {
  success: boolean;
  message: string;
  metadata: any;
  data: any;
  errors: any[];
}

@Component({
  selector: 'app-dynamic-query-test',
  imports: [
    CommonModule,
    NgxJsonViewerModule,
    ButtonComponent
  ],
  templateUrl: './dynamic-query-test.component.html',
  styleUrl: './dynamic-query-test.component.scss',
  standalone: true
})
export class DynamicQueryTestComponent {

  @Input() dynamicQuery: DynamicQuery | null = null;

  testLoading: boolean = false;
  testResult: TestResult | null = null;
  testParameters: any = {};

  constructor(
    private dynamicQueryService: DynamicQueryService,
    private toast: ToastService
  ) {}

  async executeTest() {
    if (!this.dynamicQuery) {
      this.toast.error('É necessário salvar a consulta antes de testá-la.');
      return;
    }

    this.testLoading = true;
    this.testResult = null;

    try {
      const response = await this.dynamicQueryService.executeDynamicQuery(
        this.dynamicQuery,
        this.testParameters
      );

      this.testResult = response;

      if (response.success) {
        this.toast.success(response.message || 'Teste executado com sucesso!');
      } else {
        this.toast.warning(response.message || 'Teste executado com avisos.');
      }

    } catch (error: any) {
      const errorMessage = Utils.getErrorMessage(error, 'Erro ao executar teste da consulta');
      this.toast.error(errorMessage);

      // Se a resposta do erro contém a estrutura esperada
      if (error.error && error.error.success !== undefined) {
        this.testResult = error.error;
      } else {
        this.testResult = {
          success: false,
          message: errorMessage,
          metadata: {},
          data: [],
          errors: [error]
        };
      }
    } finally {
      this.testLoading = false;
    }
  }

  clearTestResult() {
    this.testResult = null;
    this.testParameters = {};
  }

  onTestParametersChange(parameters: any) {
    this.testParameters = parameters;
  }

  get hasTestResult(): boolean {
    return this.testResult !== null;
  }

  get isTestSuccessful(): boolean {
    return this.testResult?.success === true;
  }

  get hasTestData(): boolean {
    if (!this.testResult) return false;
    return !!this.testResult.data;
  }

  get hasTestErrors(): boolean {
    if (!this.testResult) return false;
    return this.testResult?.errors && this.testResult.errors.length > 0;
  }

  get hasTestMetadata(): boolean {
    return this.testResult?.metadata && Object.keys(this.testResult.metadata).length > 0;
  }

  getInfoResult(): string {
    if (this.testResult) {
      const data = this.testResult.data;
      if (typeof data === 'string') {
        return 'Dado retornado';
      } else if (Array.isArray(data)) {
        return `Dados Retornados (${data.length} registros)`;
      } else if (typeof data === 'object' && data !== null) {
        return `Objeto Retornado (${Object.keys(data).length} chaves)`;
      }
    }
    return '';
  }
}
