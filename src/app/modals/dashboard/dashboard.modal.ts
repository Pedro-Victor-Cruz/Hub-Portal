import {Component, OnInit} from '@angular/core';
import {ModalRef} from '../modal/modal.service';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {InputComponent} from '../../components/form/input/input.component';
import {DashboardService, Dashboard} from '../../services/dashboard.service';
import {ToastService} from '../../components/toast/toast.service';
import {Utils} from '../../services/utils.service';
import {ButtonComponent} from '../../components/form/button/button.component';
import {TextareaComponent} from '../../components/form/textarea/textarea.component';
import {CommonModule} from '@angular/common';
import {ToggleSwitchComponent} from '../../components/form/toggle-switch/toggle-switch.component';
import {DashboardBuilderComponent} from './dashboard-builder/dashboard-builder.component';
import {DropdownComponent} from '../../components/form/dropdown/dropdown.component';

@Component({
  selector: 'app-dashboard-modal',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    InputComponent,
    ButtonComponent,
    TextareaComponent,
    ToggleSwitchComponent,
    DashboardBuilderComponent,
    DropdownComponent
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
  showBuilder: boolean = false;
  visibilitesOptions: any[] = [
    {
      label: "Privado",
      value: "authenticated",
      description: "Apenas usuários logados podem visualizar."
    },
    {
      label: "Restrito",
      value: "restricted",
      description: "Somente usuários autorizados podem visualizar."
    }
  ];


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
      visibility: ['authenticated'],
      icon: [''],
      active: [true],
      is_navigable: [false],
      is_home: [false],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  ngOnInit() {
    this.load();
  }

  protected async load() {
    this.loading = true;
    await this.loadDashboard();
    this.loading = false;
  }

  protected async loadDashboard() {
    if (this.dashboardKey) {
      try {
        const response = await this.dashboardService.getDashboardDetails(this.dashboardKey);
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
          active: this.dashboard.active,
          visibility: this.dashboard.visibility,
          is_navigable: this.dashboard.is_navigable,
          is_home: this.dashboard.is_home,
        });

        // Se já existe, vai direto pro builder
        this.showBuilder = true;
      } catch (err: any) {
        this.toast.error(Utils.getErrorMessage(err, 'Erro ao carregar dashboard'));
      }
    }
  }

  async onSubmit() {
    if (this.form.invalid) {
      this.toast.error('Por favor, corrija os erros no formulário antes de enviar.');
      return;
    }

    this.loading = true;
    const data = this.form.value;

    try {
      if (this.dashboardKey) {
        await this.updateDashboard(data);
      } else {
        await this.createDashboard(data);
      }

    } finally {
      this.loading = false;
    }
  }

  async createDashboard(data: any) {
    try {
      const response = await this.dashboardService.createDashboard(data);
      this.dashboardKey = response.data.key;
      this.toast.success('Dashboard criado! Agora configure suas seções e widgets.');
      await this.load()
    } catch (error) {
      console.log(error);
      this.errors = this.utils.handleErrorsForm(error, this.form);
    }
  }

  async updateDashboard(data: any) {
    try {
      await this.dashboardService.updateDashboard(this.dashboardKey!, data);
      this.toast.success('Dashboard atualizado com sucesso!');
      await this.load();
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    }
  }

  generateKey(event: FocusEvent) {
    const input = event.target as HTMLInputElement;
    this.form.patchValue({key: Utils.slug(input.value)});
  }

  saveAndClose() {
    if (!this.dashboard) {
      this.toast.warning('Salve as configurações básicas antes de fechar.');
      return;
    }
    this.modalRef.close(this.dashboard);
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
