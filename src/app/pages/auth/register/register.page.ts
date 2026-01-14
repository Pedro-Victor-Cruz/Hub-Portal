import {Component, OnInit, OnDestroy, Output, EventEmitter} from '@angular/core';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {ActivatedRoute, Router} from '@angular/router';
import {Subject, takeUntil} from 'rxjs';
import {CommonModule} from '@angular/common';
import {InputComponent} from '../../../components/form/input/input.component';
import {PasswordComponent} from '../../../components/form/password/password.component';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {FormErrorHandlerService} from "../../../components/form/form-error-handler.service";
import {ToastService} from '../../../components/toast/toast.service';
import {AuthService} from '../../../security/auth.service';
import {Utils} from '../../../services/utils.service';
import {InputmaskComponent} from '../../../components/form/inputmask/inputmask.component';

@Component({
  selector: 'app-register-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    InputComponent,
    PasswordComponent,
    ButtonComponent,
    FormsModule,
    InputmaskComponent
  ],
  template: `
    <form [formGroup]="registerForm" (ngSubmit)="onRegisterSubmit()" class="auth-form register-form">
      <div class="form-fields">
        <div class="field-row">

          <!-- Nome Completo -->
          <ub-input
            label="Nome Completo"
            type="text"
            placeholder="Digite seu nome completo"
            formControlName="name"
            [error]="FormErrorHandlerService.getError('name', errors)">
          </ub-input>

          <!-- Email -->
          <ub-input
            label="Email"
            type="email"
            placeholder="seu@email.com"
            formControlName="email"
            [disabled]="emailReadonly"
            [error]="FormErrorHandlerService.getError('email', errors)">
          </ub-input>
        </div>


        <!-- Telefone e Data de Nascimento -->
        <div class="field-row">
          <ub-inputmask
            mask="(99) 99999-9999"
            label="Telefone"
            type="tel"
            placeholder="(00) 00000-0000"
            formControlName="phone"
            [error]="FormErrorHandlerService.getError('phone', errors)">
          </ub-inputmask>

          <ub-input
            label="Data de Nascimento"
            type="date"
            formControlName="birth_date"
            [error]="FormErrorHandlerService.getError('birth_date', errors)">
          </ub-input>
        </div>

        <!-- Tipo de Pessoa - Compacto -->
        <div class="person-type-compact">
          <label class="type-label">Tipo de Pessoa</label>
          <div class="radio-group-compact">
            <label class="radio-compact" [class.active]="personType === 'fisica'">
              <input
                type="radio"
                name="personType"
                value="fisica"
                [(ngModel)]="personType"
                [ngModelOptions]="{standalone: true}"
                (change)="onPersonTypeChange()">
              <span class="radio-marker"></span>
              <i class='bx bx-user'></i>
              <span>Pessoa Física</span>
            </label>

            <label class="radio-compact" [class.active]="personType === 'juridica'">
              <input
                type="radio"
                name="personType"
                value="juridica"
                [(ngModel)]="personType"
                [ngModelOptions]="{standalone: true}"
                (change)="onPersonTypeChange()">
              <span class="radio-marker"></span>
              <i class='bx bx-building'></i>
              <span>Pessoa Jurídica</span>
            </label>
          </div>
        </div>

        <!-- CPF ou CNPJ + Nome da Empresa -->
        @if (personType === 'fisica') {
          <div class="field-full">
            <ub-inputmask
              mask="999.999.999-99"
              label="CPF"
              type="text"
              placeholder="000.000.000-00"
              formControlName="cpf"
              [error]="FormErrorHandlerService.getError('cpf', errors)">
            </ub-inputmask>
          </div>
        }

        @if (personType === 'juridica') {
          <div class="field-row">
            <ub-inputmask
              mask="99.999.999/9999-99"
              label="CNPJ"
              type="text"
              placeholder="00.000.000/0000-00"
              formControlName="cnpj"
              [error]="FormErrorHandlerService.getError('cnpj', errors)">
            </ub-inputmask>

            <ub-input
              label="Nome da Empresa"
              type="text"
              placeholder="Nome da empresa"
              formControlName="company_name"
              [error]="FormErrorHandlerService.getError('company_name', errors)">
            </ub-input>
          </div>
        }

        <!-- Senha e Confirmar Senha -->
        <div class="field-row">
          <ub-password
            label="Senha"
            placeholder="Mínimo 6 caracteres"
            formControlName="password"
            [error]="FormErrorHandlerService.getError('password', errors)">
          </ub-password>

          <ub-password
            label="Confirmar Senha"
            placeholder="Repita a senha"
            formControlName="password_confirmation"
            [error]="FormErrorHandlerService.getError('password_confirmation', errors)">
          </ub-password>
        </div>
      </div>

      <div class="form-actions">
        <ub-button
          type="submit"
          [disabled]="registerForm.invalid"
          [loading]="loading">
          <i class="bx bx-user-plus"></i>
          Criar Conta
        </ub-button>
      </div>

      <div class="auth-footer">
        <p class="switch-mode-text">
          Já tem uma conta?
          <a (click)="switchToLogin.emit()" class="switch-link">
            Fazer login
          </a>
        </p>

        <p class="help-text">
          <i class="bx bx-help-circle"></i>
          Precisa de ajuda?
          <a href="mailto:suporte@uaibits.com.br" class="support-link">Entre em contato</a>
        </p>
      </div>
    </form>
  `,
  styles: [`
    .register-form {
      .form-fields {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }

      .field-full {
        width: 100%;
      }

      .field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;

        @media (max-width: 640px) {
          grid-template-columns: 1fr;
        }
      }

      .person-type-compact {
        padding: 1rem 0;
        margin: 0.5rem 0;
        border-top: 1px solid rgba(38, 38, 38, 0.08);
        border-bottom: 1px solid rgba(38, 38, 38, 0.08);

        .type-label {
          display: block;
          font-size: 0.85rem;
          font-weight: 600;
          color: var(--primary-color);
          margin-bottom: 0.75rem;
        }

        .radio-group-compact {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 0.75rem;

          @media (max-width: 640px) {
            grid-template-columns: 1fr;
          }
        }

        .radio-compact {
          position: relative;
          display: flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.75rem 1rem;
          border: 1.5px solid rgba(38, 38, 38, 0.12);
          border-radius: 10px;
          cursor: pointer;
          transition: all 0.25s ease;
          background: #ffffff;
          font-size: 0.9rem;
          font-weight: 500;
          color: var(--text-color);

          &:hover {
            border-color: rgba(247, 194, 18, 0.4);
            background: rgba(247, 194, 18, 0.04);
          }

          &.active {
            border-color: var(--secondary-color);
            background: rgba(247, 194, 18, 0.08);
            color: var(--primary-color);
            font-weight: 600;

            .radio-marker {
              border-color: var(--secondary-color);
              background: var(--secondary-color);

              &::after {
                transform: scale(1);
              }
            }

            i {
              color: var(--secondary-color);
            }
          }

          input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
          }

          .radio-marker {
            position: relative;
            width: 16px;
            height: 16px;
            min-width: 16px;
            border: 2px solid rgba(38, 38, 38, 0.25);
            border-radius: 50%;
            transition: all 0.25s ease;
            background: #ffffff;

            &::after {
              content: '';
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) scale(0);
              width: 8px;
              height: 8px;
              border-radius: 50%;
              background: #ffffff;
              transition: transform 0.2s ease;
            }
          }

          i {
            font-size: 1.15em;
            color: var(--text-muted);
            transition: color 0.25s ease;
          }

          span:last-child {
            flex: 1;
          }
        }
      }
    }
  `],
  styleUrl: '../auth.page.scss'
})
export class RegisterFormComponent implements OnInit, OnDestroy {
  @Output() switchToLogin = new EventEmitter<void>();

  registerForm!: FormGroup;
  loading = false;
  errors: { [key: string]: string } = {};
  personType: 'fisica' | 'juridica' = 'fisica';
  emailReadonly = false; // Controla se o email é somente leitura
  private returnUrl: string = '/home';

  private destroy$ = new Subject<void>();

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private authService: AuthService,
    private utils: Utils,
    private router: Router,
    private route: ActivatedRoute
  ) {
    this.initializeForm();
  }

  ngOnInit(): void {
    this.setupFormValidation();

    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/home';

    const suggestedEmail = this.route.snapshot.queryParams['suggestedEmail'];
    if (suggestedEmail) {
      this.registerForm.get('email')?.setValue(suggestedEmail);
      this.emailReadonly = true; // Marca como readonly ao invés de disabled
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private initializeForm(): void {
    this.registerForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      phone: ['', [Validators.required]],
      birth_date: ['', [Validators.required]],
      cpf: [''],
      cnpj: [''],
      company_name: [''],
      password: ['', [Validators.required, Validators.minLength(6)]],
      password_confirmation: ['', [Validators.required]]
    });

    this.updateValidators();
  }

  onPersonTypeChange(): void {
    this.updateValidators();
    this.errors = {};
  }

  private updateValidators(): void {
    const cpfControl = this.registerForm.get('cpf');
    const cnpjControl = this.registerForm.get('cnpj');
    const companyNameControl = this.registerForm.get('company_name');

    if (this.personType === 'fisica') {
      cpfControl?.setValidators([Validators.required]);
      cnpjControl?.clearValidators();
      companyNameControl?.clearValidators();

      cnpjControl?.setValue('');
      companyNameControl?.setValue('');
    } else {
      cpfControl?.clearValidators();
      cnpjControl?.setValidators([Validators.required]);
      companyNameControl?.setValidators([Validators.required, Validators.minLength(3)]);

      cpfControl?.setValue('');
    }

    cpfControl?.updateValueAndValidity();
    cnpjControl?.updateValueAndValidity();
    companyNameControl?.updateValueAndValidity();
  }

  private setupFormValidation(): void {
    this.registerForm.valueChanges
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        if (Object.keys(this.errors).length > 0) {
          this.errors = {};
        }

        const formErrors = FormErrorHandlerService.getErrorMessages(this.registerForm);
        if (Object.keys(formErrors).length > 0) {
          this.errors = {...this.errors, ...formErrors};
        }
      });
  }

  async onRegisterSubmit(): Promise<void> {
    this.registerForm.markAllAsTouched();

    if (!this.registerForm.valid) {
      this.errors = FormErrorHandlerService.getErrorMessages(this.registerForm);
      this.toast.error('Por favor, corrija os erros no formulário.');
      return;
    }

    const password = this.registerForm.get('password')?.value;
    const passwordConfirmation = this.registerForm.get('password_confirmation')?.value;

    if (password !== passwordConfirmation) {
      this.errors = {password_confirmation: 'As senhas não coincidem.'};
      this.toast.error('As senhas não coincidem.');
      return;
    }

    if (this.loading) {
      return;
    }

    this.loading = true;
    this.errors = {};

    try {
      // Pega os valores do formulário, incluindo campos disabled
      const formData = this.registerForm.getRawValue();

      if (this.personType === 'fisica') {
        delete formData.cnpj;
        delete formData.company_name;
      } else {
        delete formData.cpf;
      }

      await this.authService.register(formData);
      this.toast.success('Conta criada com sucesso!');

      setTimeout(() => {
        this.navigateToReturnUrl();
      }, 100);

    } catch (error: any) {
      console.error('Registration error:', error);

      if (error.status === 422) {
        this.errors = this.utils.handleErrorsForm(error, this.registerForm);
        this.toast.error('Verifique os dados informados.');
      } else if (error.status === 409) {
        this.toast.error('Email ou CPF/CNPJ já cadastrado.');
      } else if (error.status === 0) {
        this.toast.error('Erro de conexão. Verifique sua internet.');
      } else {
        this.errors = this.utils.handleErrorsForm(error, this.registerForm);

        if (Object.keys(this.errors).length === 0) {
          this.toast.error('Erro ao criar conta. Tente novamente.');
        }
      }
    } finally {
      this.loading = false;
    }
  }

  private navigateToReturnUrl(): void {
    this.router.navigate([this.returnUrl]);
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
