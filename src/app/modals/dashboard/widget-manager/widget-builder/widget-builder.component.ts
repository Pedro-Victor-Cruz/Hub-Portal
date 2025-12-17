import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {CommonModule} from '@angular/common';
import {InputComponent} from '../../../../components/form/input/input.component';
import {TextareaComponent} from '../../../../components/form/textarea/textarea.component';
import {DropdownComponent} from '../../../../components/form/dropdown/dropdown.component';
import {ButtonComponent} from '../../../../components/form/button/button.component';
import {ToggleSwitchComponent} from '../../../../components/form/toggle-switch/toggle-switch.component';
import {TabsComponent} from '../../../../components/tabs/tabs.component';
import {TabComponent} from '../../../../components/tabs/tab/tab.component';
import {ModalRef} from '../../../modal/modal.service';
import {DashboardService, DashboardWidget} from '../../../../services/dashboard.service';
import {ToastService} from '../../../../components/toast/toast.service';
import {Utils} from '../../../../services/utils.service';
import {DynamicQueryService} from '../../../../services/dynamic-query.service';
import {ObjectEditorComponent} from '../../../../components/form/object-editor/object-editor.component';
import {
  DynamicParametersComponent,
  DynamicParams
} from '../../../../components/dynamic-parameters/dynamic-parameters.component';
import {DynamicQueryComponent} from "../../../../components/dynamic-query/dynamic-query.component";

@Component({
  selector: 'app-widget-builder',
    imports: [
        CommonModule,
        ReactiveFormsModule,
        InputComponent,
        TextareaComponent,
        DropdownComponent,
        ButtonComponent,
        ToggleSwitchComponent,
        TabsComponent,
        TabComponent,
        DynamicParametersComponent,
        DynamicQueryComponent
    ],
  templateUrl: './widget-builder.component.html',
  standalone: true,
  styleUrl: './widget-builder.component.scss'
})
export class WidgetBuilderComponent implements OnInit {
  modalRef!: ModalRef;
  sectionId!: number;
  widget?: DashboardWidget;

  form: FormGroup;
  loading: boolean = false;
  loadingParams: boolean = false;

  widgetTypes = [
    {label: 'Gráfico de Linha', value: 'chart_line', icon: 'bx-line-chart'},
    {label: 'Gráfico de Barras', value: 'chart_bar', icon: 'bx-bar-chart'},
    {label: 'Gráfico de Pizza', value: 'chart_pie', icon: 'bx-pie-chart-alt'},
    {label: 'Tabela', value: 'table', icon: 'bx-table'},
    {label: 'Card de Métrica', value: 'metric_card', icon: 'bx-card'},
  ];

  availableQueries: any[] = [];
  parameters: DynamicParams = {};

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private dashboardService: DashboardService,
    private dynamicQuery: DynamicQueryService
  ) {
    this.form = this.fb.group({
      key: ['', [Validators.required, Validators.pattern(/^[a-z0-9-_]+$/)]],
      title: ['', Validators.required],
      description: [''],
      widget_type: ['chart_line', Validators.required],
      dynamic_query_id: [null],
      order: [0],
      active: [true],
      config: {}
    });
  }

  ngOnInit() {
    this.load();

    if (this.widget) {
      this.form.patchValue({
        key: this.widget.key,
        title: this.widget.title,
        description: this.widget.description,
        widget_type: this.widget.widget_type,
        dynamic_query_id: this.widget.dynamic_query_id,
        order: this.widget.order,
        active: this.widget.active,
        config: this.widget.config || {}
      });
    }
  }

  async load() {
    await this.loadAvailableQueries();
    await this.loadParameters();
  }

  async loadParameters() {
    this.loadingParams = true;
    try {
      const widget_type = this.form.get('widget_type')?.value;

      if (widget_type) {
        const response = await this.dashboardService.getParametersWidget(widget_type);
        if (response.data) {
          console.log(response.data)
          this.parameters = response.data || {};
          return;
        }
      }

      this.parameters = {};
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar parâmetros do widget'));
    } finally {
      this.loadingParams = false;
    }
  }

  /**
   * Carrega queries disponíveis
   */
  async loadAvailableQueries() {
    try {
      const response = await this.dynamicQuery.getDynamicQueries();
      if (response.data) {
        this.availableQueries = response.data;
      } else {
        this.toast.error('Nenhuma query disponível encontrada');
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar queries'));
    }
  }

  async onSubmit() {
    if (this.form.invalid) {
      this.toast.error('Preencha todos os campos obrigatórios');
      return;
    }

    this.loading = true;
    try {
      const data = this.prepareData();

      if (this.widget && this.widget.id) {
        await this.dashboardService.updateWidget(this.widget.id, data);
        this.toast.success('Widget atualizado com sucesso');
      } else {
        await this.dashboardService.createWidget(this.sectionId, data);
        this.toast.success('Widget criado com sucesso');
      }

      this.modalRef.close(true);
    } catch (error: any) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao salvar widget'));
    } finally {
      this.loading = false;
    }
  }

  /**
   * Prepara dados para envio (converte JSONs)
   */
  private prepareData() {
    const formValue = this.form.value;

    return {
      ...formValue,
      position_config: this.parseJSON(formValue.position_config),
      style_config: this.parseJSON(formValue.style_config),
      chart_config: this.parseJSON(formValue.chart_config),
      data_mapping: this.parseJSON(formValue.data_mapping),
      data_transform: this.parseJSON(formValue.data_transform)
    };
  }

  /**
   * Parse seguro de JSON
   */
  private parseJSON(value: string): any {
    try {
      return JSON.parse(value);
    } catch {
      return {};
    }
  }

  /**
   * Gera chave a partir do título
   */
  generateKey() {
    const title = this.form.get('title')?.value;
    if (title && !this.widget) {
      this.form.patchValue({
        key: Utils.slug(title)
      });
    }
  }

  updateConfig(config: any) {
    this.form.patchValue({
      config: config
    });
  }

  get queryId() {
    return this.form.get('dynamic_query_id')?.value;
  }

  set queryId(value: any) {
    this.form.patchValue({
      dynamic_query_id: value
    });
  }
}
