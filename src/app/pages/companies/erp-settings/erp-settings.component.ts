import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {ToastService} from '../../../components/toast/toast.service';
import {ErpSettingsService} from '../../../services/erp-settings.service';
import {InputComponent} from '../../../components/form/input/input.component';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {DatePipe} from '@angular/common';

@Component({
  selector: 'erp-settings',
  imports: [
    FormsModule,
    ReactiveFormsModule,
    InputComponent,
    PasswordComponent,
    DropdownComponent,
    ButtonComponent,
    DatePipe
  ],
  templateUrl: './erp-settings.component.html',
  standalone: true,
  styleUrl: './erp-settings.component.scss'
})
export class ErpSettingsComponent implements OnInit {

  @Input({required: true}) companyId!: string;

  @Input() loading: boolean = false;
  @Output() loadingChange = new EventEmitter<boolean>();

  protected showTestModal: boolean = false;
  protected testResult: any = null;
  protected testLoading: boolean = false;
  protected testStatus: 'idle' | 'testing' | 'success' | 'error' = 'idle';

  form: FormGroup;
  errors: { [key: string]: string } = {};
  idSettings: string | undefined = undefined;
  optionsAuthType: any[] = [
    { value: 'token', label: 'Token' },
    { value: 'session', label: 'Sessão' },
    { value: 'oauth', label: 'OAuth' },
  ];
  optionsErpName: any[] = [
    { value: 'SANKHYA', label: 'Sankhya' },
  ];

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private erpSettingsService: ErpSettingsService
  ) {
    this.form = this.fb.group({
      erp_name: ['', [Validators.required]],
      username: [''],
      secret_key: [''],
      token: [''],
      base_url: [''],
      auth_type: ['', [Validators.required]],
      extra_config: [''],
      active: [true],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  ngOnInit() {
    this.load();
  }


  setLoading(value: boolean) {
    this.loadingChange.emit(value);
  }

  async load() {
    this.setLoading(true);
    try {
      const setting = await this.erpSettingsService.getErpSettings(this.companyId);
      if (setting) {
        this.form.patchValue(setting);
        this.idSettings = setting.id;
      }
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as configurações ERP';
      this.toast.error(message);
    } finally {
      this.setLoading(false);
    }
  }

  onSubmit() {
    this.setLoading(true);
    const formData = this.form.value;
    formData['company_id'] = this.companyId;

    const action = this.idSettings
      ? this.erpSettingsService.updateErpSettings(this.idSettings, formData)
      : this.erpSettingsService.createErpSettings(formData);

    action.then(() => {
      this.form.reset();
      this.toast.success('Configurações ERP salvas com sucesso!');
      this.load(); // Recarrega as configurações após salvar
    }).catch((err: any) => {
      const message = err.error.message || 'Erro ao salvar as configurações ERP';
      this.toast.error(message);
    }).finally(() => {
      this.setLoading(false);
    });
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;

  testConnection() {
    this.showTestModal = true;
    this.testStatus = 'testing';
    this.testLoading = true;
    this.testResult = null;

    this.erpSettingsService.testConnection(this.companyId!)
      .then((response) => {
        this.testResult = response;
        this.testStatus = response.data.success ? 'success' : 'error';

        // Formata os dados técnicos para exibição
        if (response.data) {
          this.testResult.data = {
            ...response.data,
            timestamp: new Date(response.data.timestamp) // Converte string para Date
          };
        }

        if (response.data.success) {
          this.toast.success('Conexão testada com sucesso!');
        } else {
          const errorMsg = response.data.error || 'Falha no teste de conexão';
          this.toast.error(errorMsg);
        }
      })
      .catch((err: any) => {
        this.testStatus = 'error';
        this.testResult = {
          data: {
            success: false,
            error: err.error?.message || err.message || 'Erro ao testar conexão',
            response_time_ms: 0,
            timestamp: new Date().toISOString(),
            auth_type: null
          }
        };
        this.toast.error(this.testResult.data.error);
      })
      .finally(() => {
        this.testLoading = false;
      });
  }

  closeTestModal() {
    this.showTestModal = false;
    this.testStatus = 'idle';
    this.testResult = null;
  }

  retryTest() {
    this.testConnection();
  }
}
