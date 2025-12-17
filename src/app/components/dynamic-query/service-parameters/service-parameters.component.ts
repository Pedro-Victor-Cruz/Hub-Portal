import {Component, forwardRef, Input, OnChanges, OnInit, SimpleChanges} from '@angular/core';
import {ControlValueAccessor, NG_VALUE_ACCESSOR} from '@angular/forms';
import {ServicesService} from '../../../services/services.service';
import {ToastService} from '../../toast/toast.service';
import {Utils} from '../../../services/utils.service';
import {DynamicQueryService} from '../../../services/dynamic-query.service';
import {DynamicParametersComponent, DynamicParams} from '../../dynamic-parameters/dynamic-parameters.component';

@Component({
  selector: 'app-service-parameters',
  imports: [
    DynamicParametersComponent
  ],
  template: `
    <ub-dynamic-parameters
      [loading]="loading"
      [disabled]="disabled"
      [dynamicParams]="serviceParams"
      [paramsValue]="currentValue"
      [submitButtonText]="submitButtonText"
      [errors]="errors"
      (save)="onSave($event)"
      (formChange)="onFormChange($event)"
      (formValid)="onFormValid($event)">
    </ub-dynamic-parameters>
  `,
  standalone: true,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ServiceParametersComponent),
      multi: true
    }
  ]
})
export class ServiceParametersComponent implements OnInit, OnChanges, ControlValueAccessor {

  @Input({required: true}) serviceSlug: string | null = null;
  @Input() disabled: boolean = false;
  @Input() dynamicQuery: any | null = null;

  serviceParams: DynamicParams = {};
  submitButtonText: string = 'Salvar Parâmetros';
  loading = false;
  errors: { [key: string]: string } = {};
  currentValue: any = {};

  // ControlValueAccessor
  private onChange = (value: any) => {};
  private onTouched = () => {};

  constructor(
    private servicesService: ServicesService,
    private toast: ToastService,
    private dynamicQueryService: DynamicQueryService,
    private utils: Utils
  ) {}

  ngOnInit() {
    this.loadServiceParams();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['serviceSlug'] && !changes['serviceSlug'].firstChange) {
      this.loadServiceParams();
    }
  }

  private async loadServiceParams() {
    if (!this.serviceSlug) {
      this.serviceParams = {};
      return;
    }

    this.loading = true;
    try {
      const response = await this.servicesService.getServiceParams(this.serviceSlug);
      this.serviceParams = response.data || {};

      // Se há um dynamicQuery com service_params, atualiza o valor atual
      if (this.dynamicQuery?.service_params) {
        this.currentValue = this.dynamicQuery.service_params;
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar parâmetros do serviço'));
      this.serviceParams = {};
    } finally {
      this.loading = false;
    }
  }

  onFormChange(value: any) {
    this.currentValue = value;
    this.onChange(value);
    this.onTouched();
  }

  onFormValid(isValid: boolean) {
    // Pode emitir evento para o componente pai se necessário
  }

  async onSave(data: any) {
    if (!this.dynamicQuery) {
      this.toast.error('Consulta dinâmica não encontrada');
      return;
    }

    this.loading = true;
    this.errors = {};

    try {
      await this.dynamicQueryService.updateDynamicQuery(
        this.dynamicQuery.key!,
        { service_params: data }
      );

      this.toast.success('Parâmetros salvos com sucesso!');
      this.currentValue = data;
      this.onChange(data);
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, null);
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao salvar parâmetros'));
    } finally {
      this.loading = false;
    }
  }

  // ControlValueAccessor implementation
  writeValue(value: any): void {
    this.currentValue = value || {};
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }
}
