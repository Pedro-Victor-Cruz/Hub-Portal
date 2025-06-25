import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {ToastService} from '../../../components/toast/toast.service';
import {ErpSettingsService} from '../../../services/erp-settings.service';
import {InputComponent} from '../../../components/form/input/input.component';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {ButtonComponent} from '../../../components/form/button/button.component';

@Component({
  selector: 'erp-settings',
  imports: [
    FormsModule,
    ReactiveFormsModule,
    InputComponent,
    PasswordComponent,
    DropdownComponent,
    ButtonComponent
  ],
  templateUrl: './erp-settings.component.html',
  standalone: true,
  styleUrl: './erp-settings.component.scss'
})
export class ErpSettingsComponent implements OnInit {

  @Input({required: true}) companyId!: string;

  @Input() loading: boolean = false;
  @Output() loadingChange = new EventEmitter<boolean>();

  form: FormGroup;
  errors: { [key: string]: string } = {};
  idSettings: string | undefined = undefined;
  optionsAuthType: any[] = [
    { value: 'token', label: 'Token' },
    { value: 'session', label: 'Sessão' },
    { value: 'oauth', label: 'OAuth' },
  ];
  optionsErpName: any[] = [
    { value: 'sankhya', label: 'SANKHYA' },
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
}
