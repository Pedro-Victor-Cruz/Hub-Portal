import {Component, OnInit} from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  Validators,
  ReactiveFormsModule,
} from '@angular/forms';
import {ActivatedRoute, Router} from '@angular/router';
import {CompanyService} from '../../../services/company.service';
import {Utils} from '../../../services/utils.service';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {InputComponent} from '../../../components/form/input/input.component';
import {TabComponent} from '../../../components/tabs/tab/tab.component';
import {TabsComponent} from '../../../components/tabs/tabs.component';
import {ContentComponent} from '../../../components/content/content.component';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {InputmaskComponent} from '../../../components/form/inputmask/inputmask.component';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {UserService} from '../../../services/user.service';
import {ToastService} from '../../../components/toast/toast.service';
import {ErpSettingsComponent} from '../erp-settings/erp-settings.component';

@Component({
  selector: 'app-manage',
  templateUrl: './manage-company.page.html',
  styleUrls: ['./manage-company.page.scss'],
  imports: [
    ReactiveFormsModule,
    InputComponent,
    TabComponent,
    TabsComponent,
    ContentComponent,
    ButtonComponent,
    InputmaskComponent,
    DropdownComponent,
    ErpSettingsComponent
  ],
  standalone: true
})
export class ManageCompanyPage implements OnInit {
  protected idCompany: string | undefined = undefined;
  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;
  loadingPage: boolean = false;
  users: any[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private fb: FormBuilder,
    private companyService: CompanyService,
    private utils: Utils,
    private userService: UserService,
    private toast: ToastService
  ) {
    this.idCompany = this.route.snapshot.params['id'];

    this.form = this.fb.group({
      name: ['', [Validators.required]],
      key: ['', [Validators.required]],
      email: ['', [Validators.required, Validators.email]],
      cnpj: ['', [Validators.required]],
      responsible_user_id: [null],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });

  }

  async ngOnInit() {
    await this.load();
  }

  async load() {
    this.loadingPage = true;
    try {
      if (this.idCompany) {
        const company = await this.companyService.getCompany(this.idCompany);
        this.form.patchValue(company);
        console.log(company);
        console.log(this.form.value)
      }

      // Carrega os usuários para o dropdown
      this.users = await this.userService.getUsers();
    } catch (err: any) {
      const message = err.error?.message || 'Erro ao carregar os dados da empresa';
      this.toast.error(message);
    } finally {
      this.loadingPage = false;
    }
  }

  onSubmit(): void {
    this.loading = true;

    const action = this.idCompany
      ? this.companyService.updateCompany(this.idCompany, this.form.value)
      : this.companyService.createCompany(this.form.value);

    action
      .then((response) => {
        if (this.idCompany) {
          window.location.reload();
        } else {
          this.router.navigate([response.data.id], {relativeTo: this.route});
        }
      })
      .catch((err) => {
        this.errors = this.utils.handleErrorsForm(err, this.form);
      })
      .finally(() => this.loading = false);
  }

  back(): void {
    const url = this.idCompany ? '../../' : '../';
    this.router.navigate([url], {relativeTo: this.route});
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;

  generateKey(event: FocusEvent) {
    let value = event.target ? (event.target as HTMLInputElement).value : '';
    value = value.trim().replace(" ", "");
    this.form.patchValue({
      key: Utils.slug(value).toUpperCase()
    })
  }
}
