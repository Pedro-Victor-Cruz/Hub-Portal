import { Component } from '@angular/core';
import {InputComponent} from '../../components/form/input/input.component';
import {ButtonComponent} from '../../components/form/button/button.component';
import {PasswordComponent} from '../../components/form/password/password.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {ProfileService} from '../../services/profile.service';
import {ToastService} from '../../components/toast/toast.service';
import {Utils} from '../../services/utils.service';
import {AuthService} from '../../security/auth.service';
import {ContentComponent} from '../../components/content/content.component';

@Component({
  selector: 'app-profile',
  imports: [
    InputComponent,
    ButtonComponent,
    PasswordComponent,
    ReactiveFormsModule,
    ContentComponent
  ],
  templateUrl: './profile.page.html',
  standalone: true,
  styleUrl: './profile.page.scss'
})
export class ProfilePage {

  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;

  constructor(
    private fb: FormBuilder,
    private profileService: ProfileService,
    private toast: ToastService,
    private utils: Utils,
    protected auth: AuthService
  ) {
    console.log('ProfilePage', auth.getUser());
    this.form = this.fb.group({
      name: [auth.getUser()?.name],
      email: [auth.getUser()?.email, [Validators.email]],
      password: [''],
      confirm_password: [''],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  onSubmit() {
    this.loading = true;
    this.profileService.updateProfile(this.form.value).then(response => {
      this.auth.storeUser(response.data);
      this.toast.success('Perfil atualizado com sucesso!');
    }, err => this.errors = this.utils.handleErrorsForm(err, this.form))
      .finally(() => this.loading = false);
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
