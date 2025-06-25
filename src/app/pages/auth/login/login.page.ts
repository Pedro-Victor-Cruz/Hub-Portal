import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {InputComponent} from '../../../components/form/input/input.component';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {FormErrorHandlerService} from "../../../components/form/form-error-handler.service";
import {Router, RouterLink} from '@angular/router';
import {ToastService} from '../../../components/toast/toast.service';
import {AuthService} from '../../../security/auth.service';
import {Utils} from '../../../services/utils.service';
import {ButtonComponent} from '../../../components/form/button/button.component';

@Component({
  selector: 'app-login',
  imports: [
    ReactiveFormsModule,
    InputComponent,
    PasswordComponent,
    RouterLink,
    ButtonComponent
  ],
  templateUrl: './login.page.html',
  standalone: true,
  styleUrl: '../auth.page.scss'
})
export class LoginPage implements OnInit {
  loginForm: FormGroup;
  loading: boolean = false;
  errors: { [key: string]: string } = {};

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private auth: AuthService,
    private utils: Utils,
    private router: Router
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });

    this.loginForm.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.loginForm);
    });
  }

 ngOnInit() {
    if (this.auth.isAuthenticated()) {
      this.router.navigate(['/home']);
    }
 }

  onLoginSubmit(): void {
    if (this.loginForm.valid) {
      console.log('Formulário de login enviado:', this.loginForm.value);
    }

    this.loading = true;
    this.auth.login(this.loginForm.value).then(response => {
      this.toast.success('Login efetuado com sucesso!');
    }, error => this.errors = this.utils.handleErrorsForm(error, this.loginForm))
      .finally(() => this.loading = false);
  }

    protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
