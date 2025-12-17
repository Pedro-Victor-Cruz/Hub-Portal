import {Component, OnInit} from '@angular/core';
import {ModalRef} from '../modal/modal.service';
import {TabsComponent} from '../../components/tabs/tabs.component';
import {TabComponent} from '../../components/tabs/tab/tab.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {InputComponent} from '../../components/form/input/input.component';
import {DashboardService, Dashboard} from '../../services/dashboard.service';
import {ToastService} from '../../components/toast/toast.service';
import {Utils} from '../../services/utils.service';
import {ButtonComponent} from '../../components/form/button/button.component';
import {TextareaComponent} from '../../components/form/textarea/textarea.component';
import {CommonModule} from '@angular/common';
import {DashboardSectionsComponent} from './dashboard-sections/dashboard-sections.component';
import {ToggleSwitchComponent} from '../../components/form/toggle-switch/toggle-switch.component';
import {FiltersComponent} from '../../components/filters/filters.component';

@Component({
  selector: 'app-dashboard-modal',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TabsComponent,
    InputComponent,
    ButtonComponent,
    TextareaComponent,
    DashboardSectionsComponent,
    ToggleSwitchComponent,
    FiltersComponent,
    TabComponent
  ],
  templateUrl: './dashboard.modal.html',
  standalone: true,
  styleUrl: './dashboard.modal.scss'
})
export class DashboardModal implements OnInit {

  modalRef!: ModalRef;
  dashboardKey: string | null = null;
  dashboard: Dashboard | null = null;
  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;
  layoutTypes: any[] = [];

  constructor(
    private fb: FormBuilder,
    private dashboardService: DashboardService,
    private toast: ToastService,
    private utils: Utils
  ) {
    this.form = this.fb.group({
      key: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100), Validators.pattern(/^[a-z0-9-]+$/)]],
      name: ['', [Validators.required, Validators.maxLength(255)]],
      description: [''],
      icon: [''],
      active: [true]
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  ngOnInit() {
    this.layoutTypes = this.dashboardService.getLayoutTypes();
    this.load();
  }

  /**
   * Carrega dados do dashboard (se estiver editando)
   */
  protected async load() {
    this.loading = true;
    await this.loadDashboard();
    this.loading = false;
  }

  /**
   * Carrega dashboard existente
   */
  protected async loadDashboard() {
    if (this.dashboardKey) {
      try {
        const response = await this.dashboardService.getDashboard(this.dashboardKey);
        this.dashboard = response.data?.dashboard || null;

        if (!this.dashboard) {
          this.toast.error('Dashboard não encontrado.');
          this.modalRef.close();
          return;
        }

        this.form.patchValue({
          key: this.dashboard.key,
          name: this.dashboard.name,
          description: this.dashboard.description,
          icon: this.dashboard.icon,
          active: this.dashboard.active
        });
      } catch (err: any) {
        this.toast.error(Utils.getErrorMessage(err, 'Erro ao carregar dashboard'));
      }
    }
  }

  /**
   * Submete o formulário
   */
  onSubmit() {
    if (this.form.invalid) {
      this.toast.error('Por favor, corrija os erros no formulário antes de enviar.');
      return;
    }

    this.loading = true;
    const data = this.form.value;

    if (this.dashboardKey) {
      this.updateDashboard(data);
    } else {
      this.createDashboard(data);
    }
  }

  /**
   * Cria novo dashboard
   */
  async createDashboard(data: any) {
    try {
      const response = await this.dashboardService.createDashboard(data);

      // Atualiza dados locais para permitir acesso às outras abas
      this.dashboardKey = response.data.dashboard.key;
      this.dashboard = response.data.dashboard;

    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loading = false;
    }
  }

  /**
   * Atualiza dashboard existente
   */
  async updateDashboard(data: any) {
    try {
      const response = await this.dashboardService.updateDashboard(this.dashboardKey!, data);

      // Atualiza dados locais
      this.dashboard = {...this.dashboard, ...response.data.dashboard};

      this.toast.success('Dashboard atualizado com sucesso!');
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loading = false;
    }
  }

  /**
   * Gera chave automaticamente a partir do nome
   */
  generateKey(event: FocusEvent) {
    const input = event.target as HTMLInputElement;
    const value = input.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
    this.form.patchValue({key: value});
  }

  /**
   * Valida e fecha o modal
   */
  saveAndClose() {
    if (!this.dashboard) {
      this.toast.warning('Salve as configurações básicas antes de fechar.');
      return;
    }

    this.modalRef.close(this.dashboard);
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
