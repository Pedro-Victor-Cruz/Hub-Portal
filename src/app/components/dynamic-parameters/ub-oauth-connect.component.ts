import {Component, EventEmitter, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {CommonModule} from '@angular/common';
import {ButtonComponent} from '../form/button/button.component';
import {ToastService} from '../toast/toast.service';
import {firstValueFrom} from 'rxjs';
import {HttpClient} from '@angular/common/http';
import {environment} from '../../../environments/environment';

export interface OAuthStatus {
  connected: boolean;
  user_name?: string;
  connected_at?: string;
}

@Component({
  selector: 'ub-oauth-connect',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  template: `
    <div class="oauth-connect-container">
      <div class="oauth-header">
        <div class="oauth-info">
          <label class="oauth-label">{{ label }}</label>
          @if (description) {
            <p class="oauth-description">{{ description }}</p>
          }
        </div>

        @if (status?.connected) {
          <div class="oauth-status connected">
            <i class="bx bx-check-circle"></i>
            <span>Conectado</span>
          </div>
        } @else {
          <div class="oauth-status disconnected">
            <i class="bx bx-x-circle"></i>
            <span>Não conectado</span>
          </div>
        }
      </div>

      @if (status && status.connected) {
        <div class="oauth-details">
          <div class="detail-item">
            <i class="bx bx-user"></i>
            <span>{{ status.user_name || 'Usuário conectado' }}</span>
          </div>
          @if (status.connected_at) {
            <div class="detail-item">
              <i class="bx bx-time"></i>
              <span>Conectado em {{ formatDate(status.connected_at) }}</span>
            </div>
          }
        </div>

        <div class="oauth-actions">
          <ub-button
            [loading]="loading"
            [disabled]="disabled"
            severity="info"
            icon="bx bx-refresh"
            (click)="refreshResources()"
          >
            Atualizar Recursos
          </ub-button>

          <ub-button
            [loading]="loading"
            [disabled]="disabled"
            severity="danger"
            icon="bx bx-unlink"
            (click)="disconnect()"
          >
            Desconectar
          </ub-button>
        </div>
      } @else {
        <div class="oauth-actions">
          <ub-button
            [loading]="loading"
            [disabled]="disabled"
            severity="primary"
            icon="bx bx-link"
            (click)="connect()"
          >
            Conectar com {{ providerName }}
          </ub-button>
        </div>
      }

      @if (error) {
        <div class="oauth-error">
          <i class="bx bx-error"></i>
          <span>{{ error }}</span>
        </div>
      }
    </div>
  `,
  styles: [`
    .oauth-connect-container {
      border: 2px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      background: var(--surface-color);
      transition: all 0.3s ease;
    }

    .oauth-connect-container:hover {
      border-color: var(--primary-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .oauth-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.5rem;
      gap: 1rem;
    }

    .oauth-info {
      flex: 1;
    }

    .oauth-label {
      display: block;
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }

    .oauth-description {
      font-size: 0.875rem;
      color: var(--text-secondary);
      line-height: 1.5;
      margin: 0;
    }

    .oauth-status {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .oauth-status.connected {
      background: #d1fae5;
      color: #065f46;
    }

    .oauth-status.connected i {
      color: #059669;
      font-size: 1.25rem;
    }

    .oauth-status.disconnected {
      background: #fee2e2;
      color: #991b1b;
    }

    .oauth-status.disconnected i {
      color: #dc2626;
      font-size: 1.25rem;
    }

    .oauth-details {
      background: var(--background-color);
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1rem;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem 0;
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    .detail-item i {
      font-size: 1.125rem;
      color: var(--primary-color);
    }

    .oauth-actions {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .oauth-error {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1rem;
      padding: 0.75rem;
      background: #fee2e2;
      border-radius: 8px;
      color: #991b1b;
      font-size: 0.875rem;
    }

    .oauth-error i {
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    @media (max-width: 640px) {
      .oauth-header {
        flex-direction: column;
      }

      .oauth-status {
        align-self: flex-start;
      }

      .oauth-actions {
        flex-direction: column;
      }

      .oauth-actions ub-button {
        width: 100%;
      }
    }
  `]
})
export class OauthConnectComponent implements OnInit, OnDestroy {
  @Input() label: string = 'Autenticação OAuth';
  @Input() description?: string;
  @Input() provider!: string;
  @Input() disabled: boolean = false;
  @Input() integrationName!: string;

  @Output() connected = new EventEmitter<any>();
  @Output() disconnected = new EventEmitter<void>();
  @Output() resourcesUpdated = new EventEmitter<any>();

  status: OAuthStatus | null = null;
  loading: boolean = false;
  error: string = '';
  private oauthWindow: Window | null = null;
  private messageListener: any;
  private API_URL = environment.api;

  constructor(
    private toast: ToastService,
    private http: HttpClient,
  ) {}

  ngOnInit() {
    this.loadStatus();
    this.setupMessageListener();
  }

  ngOnDestroy() {
    if (this.messageListener) {
      window.removeEventListener('message', this.messageListener);
    }
    if (this.oauthWindow && !this.oauthWindow.closed) {
      this.oauthWindow.close();
    }
  }

  get providerName(): string {
    const names: Record<string, string> = {
      'meta': 'Facebook',
      'facebook': 'Facebook',
      'google': 'Google',
      'microsoft': 'Microsoft'
    };
    return names[this.provider] || this.provider;
  }

  async loadStatus() {
    this.loading = true;
    this.error = '';

    try {
      const response = await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/integration/oauth/${this.provider}/status`)
      );
      this.status = response.data;
    } catch (error: any) {
      console.error('Erro ao carregar status OAuth:', error);
      this.error = 'Erro ao verificar status da conexão';
    } finally {
      this.loading = false;
    }
  }

  async connect() {
    this.loading = true;
    this.error = '';

    try {
      const response = await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/integration/oauth/${this.provider}/authorize`)
      );

      if (response.success && response.data.authorization_url) {
        this.openOAuthPopup(response.data.authorization_url);
      } else {
        throw new Error('URL de autorização não recebida');
      }
    } catch (error: any) {
      console.error('Erro ao iniciar OAuth:', error);
      this.error = 'Erro ao iniciar autenticação';
      this.toast.error('Erro ao iniciar autenticação');
      this.loading = false;
    }
  }

  private openOAuthPopup(url: string) {
    const width = 600;
    const height = 700;
    const left = (window.screen.width / 2) - (width / 2);
    const top = (window.screen.height / 2) - (height / 2);

    this.oauthWindow = window.open(
      url,
      'OAuth Authorization',
      `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes`
    );

    // Monitora se a janela foi fechada
    const checkClosed = setInterval(() => {
      if (this.oauthWindow && this.oauthWindow.closed) {
        clearInterval(checkClosed);
        if (this.loading) {
          this.loading = false;
          this.toast.info('Autenticação cancelada');
        }
      }
    }, 500);
  }

  private setupMessageListener() {
    this.messageListener = (event: MessageEvent) => {
      // Valida origem da mensagem
      if (event.origin !== window.location.origin) {
        return;
      }

      if (event.data.type === 'oauth_callback') {
        this.handleOAuthCallback(event.data.data);
      }
    };

    window.addEventListener('message', this.messageListener);
  }

  private async handleOAuthCallback(data: any) {
    this.loading = false;

    if (this.oauthWindow && !this.oauthWindow.closed) {
      this.oauthWindow.close();
    }

    if (data.success) {
      this.toast.success('Autenticação realizada com sucesso!');
      await this.loadStatus();

      // Emite evento de conexão com recursos disponíveis
      this.connected.emit(data.resources);

      // Se tem recursos, emite também
      if (data.resources) {
        this.resourcesUpdated.emit(data.resources);
      }
    } else {
      this.error = data.error_description || 'Erro na autenticação';
      this.toast.error(this.error);
    }
  }

  async disconnect() {
    if (!confirm('Deseja realmente desconectar? Você precisará autenticar novamente.')) {
      return;
    }

    this.loading = true;
    this.error = '';

    try {
      await firstValueFrom(
        this.http.post(`${this.API_URL}/integration/oauth/${this.provider}/disconnect`, {})
      );
      this.toast.success('Desconectado com sucesso');
      this.status = { connected: false };
      this.disconnected.emit();
    } catch (error: any) {
      console.error('Erro ao desconectar:', error);
      this.error = 'Erro ao desconectar';
      this.toast.error('Erro ao desconectar');
    } finally {
      this.loading = false;
    }
  }

  async refreshResources() {
    this.loading = true;
    this.error = '';

    try {
      const response = await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/integration/oauth/${this.provider}/resources`)
      );
      const resources = response.data;
      this.toast.success('Recursos atualizados com sucesso');
      this.resourcesUpdated.emit(resources);
    } catch (error: any) {
      console.error('Erro ao atualizar recursos:', error);
      this.error = 'Erro ao atualizar recursos';
      this.toast.error('Erro ao atualizar recursos');
    } finally {
      this.loading = false;
    }
  }

  formatDate(dateString: string): string {
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return dateString;
    }
  }
}
