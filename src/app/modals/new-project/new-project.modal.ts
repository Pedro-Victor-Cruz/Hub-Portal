import {Component} from '@angular/core';
import {ModalRef} from '../modal/modal.service';
import {Plan} from '../../pages/core/plans/plans.page';
import {AuthService} from '../../security/auth.service';
import {User} from '../../models/user';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {InputComponent} from '../../components/form/input/input.component';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {ClientService} from '../../services/client.service';
import {Utils} from '../../services/utils.service';
import {ToastService} from '../../components/toast/toast.service';
import {Router} from '@angular/router';
import {ButtonComponent} from '../../components/form/button/button.component';
import {ToggleSwitchComponent} from '../../components/form/toggle-switch/toggle-switch.component';

@Component({
  selector: 'app-new-project',
  imports: [
    ReactiveFormsModule,
    InputComponent,
    ButtonComponent,
    ToggleSwitchComponent
  ],
  templateUrl: './new-project.modal.html',
  standalone: true,
  styleUrl: './new-project.modal.scss'
})
export class NewProjectModal{

  modalRef!: ModalRef;
  plan!: Plan;

  protected user!: User;
  protected form: FormGroup;
  protected errors: { [key: string]: string } = {};
  protected loading: boolean = false;

  constructor(
    protected auth: AuthService,
    private fb: FormBuilder,
    private clientService: ClientService,
    private utils: Utils,
    private toast: ToastService,
    private router: Router
  ) {
    this.user = auth.getUser()!

    this.form = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
      is_resale: [false, [Validators.required]],
      client_email: ['', []],
    });

    this.form.get('is_resale')!.valueChanges.subscribe((isResale: boolean) => {
      const clientEmailControl = this.form.get('client_email');
      if (isResale) {
        clientEmailControl!.setValidators([Validators.required, Validators.email]);
      } else {
        clientEmailControl!.clearValidators();
      }
      clientEmailControl!.updateValueAndValidity();
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  async onSubmit() {
    this.loading = true;
    try {
      const data = {
        ...this.form.value,
        plan_id: this.plan.id
      }

      const response = await this.clientService.newClient(data);
      this.toast.success(response.message);

      this.modalRef.close();
      this.router.navigate(['/home']);
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loading = false;
    }
  }


  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
