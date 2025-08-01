import {Component} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {ContentComponent} from '../../../components/content/content.component';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {InputComponent} from '../../../components/form/input/input.component';
import {Utils} from '../../../services/utils.service';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {UserService} from '../../../services/user.service';
import {EditorComponent} from '../../../components/form/editor/editor.component';
import {ImageComponent} from '../../../components/form/image/image.component';
import {ToggleSwitchComponent} from '../../../components/form/toggle-switch/toggle-switch.component';
import {CompanyService} from '../../../services/company.service';
import {ToastService} from '../../../components/toast/toast.service';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {HasPermissionDirective} from '../../../directives/has-permission.directive';
import {TabsComponent} from "../../../components/tabs/tabs.component";
import {TabComponent} from '../../../components/tabs/tab/tab.component';
import {UserAccessSettingsComponent} from '../user-access-settings/user-access-settings.component';
import {User} from '../../../models/user';

@Component({
  imports: [
    ContentComponent,
    FormsModule,
    ReactiveFormsModule,
    InputComponent,
    ButtonComponent,
    PasswordComponent,
    DropdownComponent,
    HasPermissionDirective,
    TabsComponent,
    TabComponent,
    UserAccessSettingsComponent,
  ],
  templateUrl: './manage-user.page.html',
  standalone: true,
  styleUrl: './manage-user.page.scss',
})
export class ManageUserPage {
  protected idUser: string | undefined = undefined;
  protected user: User | null = null; // Objeto do usuário, se necessário

  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;
  loadingPage: boolean = false;
  companies: any[] = []; // Lista de empresas para o campo de seleção

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private fb: FormBuilder,
    private userService: UserService,
    private companyService: CompanyService,
    private utils: Utils,
    private toastService: ToastService
  ) {
    this.idUser = this.route.snapshot.params['id'];

    this.form = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.minLength(6), Validators.maxLength(100)]],
      confirm_password: ['', [Validators.minLength(6), Validators.maxLength(100)]],
      company_id: [null],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });

    this.load();
  }

  // Carrega os dados do blog se for uma edição
  async load() {
    this.loadingPage = true;
    try {
      if (this.idUser) {
        const userResponse = await this.userService.getUser(this.idUser);
        this.user = userResponse;
        this.form.patchValue({
          ...userResponse
        });
      }

      this.companies = await this.companyService.getCompanies();

      // Observa mudanças no company_id
      this.companyIdControl?.valueChanges.subscribe(() => {
        // Isso irá disparar automaticamente a atualização no UserAccessSettingsComponent
      });

    } catch (err: any) {
      const message = err.error.message || 'Erro ao carregar os dados do usuário';
      this.toastService.error(message);
    } finally {
      this.loadingPage = false;
    }
  }

  // Envia o formulário
  onSubmit() {
    this.loading = true;

    let data = this.form.value;
    data = Object.fromEntries(Object.entries(data).filter(([_, v]) => v != null && v !== ''));

    // Define a ação (criação ou atualização)
    const action = this.idUser
      ? this.userService.updateUser(this.idUser, data)
      : this.userService.createUser(data);

    // Executa a ação
    action
      .then((response) => {
        if (this.idUser) {
          window.location.reload(); // Recarrega a página após a atualização
        } else {
          this.router.navigate([response.data.id], {relativeTo: this.route}); // Redireciona para a página de edição
        }
      })
      .catch((err) => {
        this.errors = this.utils.handleErrorsForm(err, this.form); // Trata erros do formulário
      })
      .finally(() => (this.loading = false));
  }

  // Volta para a página anterior
  back() {
    const url = this.idUser ? '../../' : '../';
    this.router.navigate([url], {relativeTo: this.route});
  }

  get companyIdControl() {
    return this.form.get('company_id');
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;

  reloadPage() {
    this.load();
  }
}
