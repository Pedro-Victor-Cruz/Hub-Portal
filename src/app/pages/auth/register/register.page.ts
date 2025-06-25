import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {InputComponent} from '../../../components/form/input/input.component';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {Router, RouterLink} from '@angular/router';
import {ToastService} from '../../../components/toast/toast.service';
import {AuthService} from '../../../security/auth.service';
import {Utils} from '../../../services/utils.service';
import {ButtonComponent} from '../../../components/form/button/button.component';

@Component({
  selector: 'app-register',
  imports: [
    ReactiveFormsModule,
    InputComponent,
    PasswordComponent,
    RouterLink,
    ButtonComponent
  ],
  templateUrl: './register.page.html',
  standalone: true,
  styleUrl: '../auth.page.scss'
})
export class RegisterPage implements OnInit {
  registerForm: FormGroup;
  loading: boolean = false;
  errors: { [key: string]: string } = {};

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private auth: AuthService,
    private utils: Utils,
    private router: Router
  ) {
    this.registerForm = this.fb.group({
      name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
      confirm_password: ['', Validators.required],
    });

    this.registerForm.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.registerForm);
    });
  }

  ngOnInit() {
    if (this.auth.isAuthenticated()) {
      this.router.navigate(['/home']);
    }
  }

  onRegisterSubmit(): void {
    this.loading = true;
    this.auth.register(this.registerForm.value).then(response => {
      this.toast.success('Cadastro realizado com sucesso!');
    }, err => this.errors = this.utils.handleErrorsForm(err, this.registerForm))
      .finally(() => this.loading = false);
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
