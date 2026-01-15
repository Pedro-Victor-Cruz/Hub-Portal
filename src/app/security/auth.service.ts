import {Injectable} from '@angular/core';
import {BehaviorSubject, firstValueFrom, Observable, throwError, EMPTY, timer} from 'rxjs';
import {Router} from '@angular/router';
import {HttpClient} from '@angular/common/http';
import {environment} from '../../environments/environment';
import {catchError, tap, switchMap} from 'rxjs/operators';
import {LayoutService} from '../layout/layout.service';
import {User} from '../models/user';
import {ClientService} from '../services/client.service';

export interface LoginResponse {
  data: {
    access_token: string;
    refresh_token: string;
    access_token_expires_in: number;
    user: User;
  };
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly TOKEN_KEY = 'auth_token';
  private readonly REFRESH_TOKEN_KEY = 'refresh_token';
  private readonly TOKEN_EXPIRATION_KEY = 'token_expiration';
  private readonly USER_KEY = 'user';

  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  private userSubject = new BehaviorSubject<User | null>(null);
  private refreshTokenInProgress = false;
  private refreshTokenPromise: Promise<void> | null = null;
  private autoRefreshTimer: any;

  // Observables públicos
  public isAuthenticated$ = this.isAuthenticatedSubject.asObservable();
  public user$ = this.userSubject.asObservable();

  constructor(
    private http: HttpClient,
    private router: Router,
    private layoutService: LayoutService,
    private clientService: ClientService
  ) {
    this.initializeAuth();
  }

  /**
   * Inicializa o estado de autenticação ao carregar o serviço
   */
  private initializeAuth(): void {
    const token = this.getStoredToken();
    const user = this.getStoredUser();

    if (token && !this.isTokenExpired() && user) {
      this.setAuthState(true, user);
      this.scheduleTokenRefresh();
    } else if (token && this.getStoredRefreshToken()) {
      // Token expirado mas temos refresh token - tenta renovar
      this.silentRefresh().catch(() => {
        this.logout();
      });
    } else {
      this.setAuthState(false, null);
    }
  }

  /**
   * Verifica se o usuário está autenticado
   */
  isAuthenticated(): boolean {
    const hasToken = !!this.getStoredToken();
    const tokenValid = !this.isTokenExpired();
    return hasToken && tokenValid;
  }

  /**
   * Faz login e armazena os tokens
   */
  async login(credentials: { email: string; password: string }): Promise<void> {
    try {
      const response = await firstValueFrom(
        this.http.post<LoginResponse>(`${environment.api}/auth`, credentials)
      );

      const {access_token, refresh_token, access_token_expires_in, user} = response.data;

      this.storeTokens(access_token, refresh_token, access_token_expires_in);
      this.storeUser(user);
      this.setAuthState(true, user);

      this.scheduleTokenRefresh();

      await this.router.navigate(['/home']);

    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  /**
   * Faz registro de um novo usuário
   */
  async register(userData: any): Promise<void> {
    try {
      const response = await firstValueFrom(
        this.http.post<LoginResponse>(`${environment.api}/auth/register`, userData)
      );

      const {access_token, refresh_token, access_token_expires_in, user} = response.data;

      this.storeTokens(access_token, refresh_token, access_token_expires_in);
      this.storeUser(user);
      this.setAuthState(true, user);

      this.scheduleTokenRefresh();

      await this.router.navigate(['/home']);
    } catch (error) {
      console.error('Registration failed:', error);
      throw error;
    }
  }

  /**
   * Atualiza os dados do usuário
   */
  refreshUserData(): Observable<void> {
    return this.http.get<{ data: User }>(`${environment.api}/profile`).pipe(
      tap((response) => {
        this.storeUser(response.data);
        this.userSubject.next(response.data);
      }),
      switchMap(() => EMPTY),
      catchError((error) => {
        console.error('Failed to refresh user data:', error);
        return throwError(() => error);
      })
    );
  }

  /**
   * Faz logout e remove os tokens
   */
  logout(redirect: boolean = true): void {
    // Cancela timer de refresh automático
    this.cancelAutoRefresh();

    // Limpa dados locais primeiro
    this.clearAuthData();

    // Limpa o cliente atual
    this.clientService.clearCurrentClient();

    // Notifica sobre logout
    this.setAuthState(false, null);

    // Faz requisição de logout no servidor (fire and forget)
    this.http.post(`${environment.api}/auth/logout`, {}).subscribe({
      error: (error) => console.warn('Logout request failed:', error)
    });

    // Navega para login
    if (redirect) this.router.navigate(['/auth/logar']);

    // Limpa as abas abertas
    this.layoutService.setOpenTabs([]);
  }

  /**
   * Renova o token usando refresh token
   */
  refreshToken(): Observable<void> {
    if (this.refreshTokenInProgress && this.refreshTokenPromise) {
      return new Observable(subscriber => {
        this.refreshTokenPromise!
          .then(() => subscriber.complete())
          .catch(error => subscriber.error(error));
      });
    }

    const refreshToken = this.getStoredRefreshToken();
    if (!refreshToken) {
      this.logout();
      return throwError(() => new Error('No refresh token available'));
    }

    this.refreshTokenInProgress = true;
    this.refreshTokenPromise = this.performTokenRefresh(refreshToken);

    return new Observable(subscriber => {
      this.refreshTokenPromise!
        .then(() => {
          subscriber.next();
          subscriber.complete();
        })
        .catch(error => subscriber.error(error));
    });
  }

  /**
   * Renovação silenciosa do token (sem interferir na UI)
   */
  private async silentRefresh(): Promise<void> {
    const refreshToken = this.getStoredRefreshToken();
    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    return this.performTokenRefresh(refreshToken);
  }

  /**
   * Executa a renovação do token
   */
  private async performTokenRefresh(refreshToken: string): Promise<void> {
    try {
      const response = await firstValueFrom(
        this.http.post<any>(
          `${environment.api}/auth/refresh`,
          {},
          {
            headers: {Token: refreshToken}
          }
        )
      );

      this.storeTokens(
        response.data.access_token,
        response.data.refresh_token,
        response.data.access_token_expires_in
      );

      this.scheduleTokenRefresh();
    } catch (error) {
      console.error('Token refresh failed:', error);
      this.logout();
      throw error;
    } finally {
      this.refreshTokenInProgress = false;
      this.refreshTokenPromise = null;
    }
  }

  /**
   * Programa a renovação automática do token
   */
  private scheduleTokenRefresh(): void {
    this.cancelAutoRefresh();

    const expiration = this.getStoredTokenExpiration();
    if (!expiration) return;

    // Programa refresh para 5 minutos antes da expiração
    const refreshTime = expiration - Date.now() - (5 * 60 * 1000);

    if (refreshTime > 0) {
      this.autoRefreshTimer = timer(refreshTime).subscribe(() => {
        this.silentRefresh().catch(() => {
          console.warn('Silent refresh failed, user will need to login again');
        });
      });
    }
  }

  /**
   * Cancela a renovação automática do token
   */
  private cancelAutoRefresh(): void {
    if (this.autoRefreshTimer) {
      this.autoRefreshTimer.unsubscribe();
      this.autoRefreshTimer = null;
    }
  }

  /**
   * Verifica se o token está expirado
   */
  private isTokenExpired(): boolean {
    const expiration = this.getStoredTokenExpiration();
    if (!expiration) return true;

    // Considera expirado se faltar menos de 1 minuto
    return Date.now() > (expiration - 60000);
  }

  /**
   * Armazena os tokens e a data de expiração
   */
  private storeTokens(token: string, refreshToken: string, expiresIn: number): void {
    const expiration = Date.now() + (expiresIn * 1000);

    try {
      localStorage.setItem(this.TOKEN_KEY, token);
      localStorage.setItem(this.REFRESH_TOKEN_KEY, refreshToken);
      localStorage.setItem(this.TOKEN_EXPIRATION_KEY, expiration.toString());
    } catch (error) {
      console.error('Failed to store tokens:', error);
      throw new Error('Failed to store authentication tokens');
    }
  }

  /**
   * Armazena o usuário logado
   */
  public storeUser(user: User): void {
    try {
      localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    } catch (error) {
      console.error('Failed to store user:', error);
    }
  }

  /**
   * Remove todos os dados de autenticação
   */
  private clearAuthData(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.REFRESH_TOKEN_KEY);
    localStorage.removeItem(this.TOKEN_EXPIRATION_KEY);
    localStorage.removeItem(this.USER_KEY);
  }

  /**
   * Define o estado de autenticação
   */
  private setAuthState(isAuthenticated: boolean, user: User | null): void {
    this.isAuthenticatedSubject.next(isAuthenticated);
    this.userSubject.next(user);
  }

  // Métodos públicos para obter dados armazenados
  getToken(): string | null {
    return this.getStoredToken();
  }

  getRefreshToken(): string | null {
    return this.getStoredRefreshToken();
  }

  getUser(): User | null {
    return this.userSubject.value || this.getStoredUser();
  }

  getUserName() {
    const user = this.getUser();
    return user ? user.name : 'Desconhecido';
  }

  getCurrentUser(): User | null {
    return this.getUser();
  }

  // Métodos privados para acessar localStorage
  private getStoredToken(): string | null {
    try {
      return localStorage.getItem(this.TOKEN_KEY);
    } catch (error) {
      console.error('Failed to get token from localStorage:', error);
      return null;
    }
  }

  private getStoredRefreshToken(): string | null {
    try {
      return localStorage.getItem(this.REFRESH_TOKEN_KEY);
    } catch (error) {
      console.error('Failed to get refresh token from localStorage:', error);
      return null;
    }
  }

  private getStoredTokenExpiration(): number | null {
    try {
      const expiration = localStorage.getItem(this.TOKEN_EXPIRATION_KEY);
      return expiration ? Number(expiration) : null;
    } catch (error) {
      console.error('Failed to get token expiration from localStorage:', error);
      return null;
    }
  }

  private getStoredUser(): User | null {
    try {
      const user = localStorage.getItem(this.USER_KEY);
      return user ? JSON.parse(user) : null;
    } catch (error) {
      console.error('Failed to get user from localStorage:', error);
      return null;
    }
  }

  /**
   * Verifica se o usuário tem determinada(s) permissão(ões)
   */
  hasPermission(permission: string | string[]): boolean {
    if ((Array.isArray(permission) && permission.length === 0) || !permission || permission === '') {
      return true;
    }

    const user = this.getUser();
    if (!user || !user.permissions) {
      return false;
    }

    const permissions = Array.isArray(permission) ? permission : [permission];
    return permissions.every(perm =>
      user.permissions.some(userPerm => userPerm.name === perm && userPerm.is_active)
    );
  }

  /**
   * Método para limpar dados corrompidos (caso necessário)
   */
  clearCorruptedData(): void {
    try {
      // Verifica se os dados estão corrompidos
      const token = this.getStoredToken();
      const user = this.getStoredUser();

      if (token && !user) {
        console.warn('Corrupted auth data detected, clearing...');
        this.clearAuthData();
        this.setAuthState(false, null);
      }
    } catch (error) {
      console.error('Error checking corrupted data:', error);
      this.clearAuthData();
      this.setAuthState(false, null);
    }
  }
}
