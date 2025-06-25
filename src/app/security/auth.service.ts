import {Injectable} from '@angular/core';
import {BehaviorSubject, firstValueFrom, Observable, throwError} from 'rxjs';
import {Router} from '@angular/router';
import {HttpClient} from '@angular/common/http';
import {environment} from '../../environments/environment';
import {catchError, tap} from 'rxjs/operators';
import {LayoutService} from '../layout/layout.service';
import {User} from '../models/user';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly TOKEN_KEY = 'auth_token';
  private readonly REFRESH_TOKEN_KEY = 'refresh_token';
  private readonly TOKEN_EXPIRATION_KEY = 'token_expiration';
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(this.hasToken());

  private user: User | null = null;
  private currentUser: User | null = null;

  constructor(
    private http: HttpClient,
    private router: Router,
    private layoutService: LayoutService
  ) {
    const user = localStorage.getItem('user');
    if (user) this.user = JSON.parse(user);
  }

  // Verifica se o usuário está autenticado
  isAuthenticated(): boolean {
    return this.hasToken() && !this.isTokenExpired();
  }

  // Faz login e armazena os tokens
  async login(credentials: { email: string; password: string }): Promise<void> {
    try {
      const response: any = await firstValueFrom(
        this.http.post(`${environment.api}/auth`, credentials)
      );
      this.storeTokens(
        response.data.access_token,
        response.data.refresh_token,
        response.data.access_token_expires_in
      );
      this.storeUser(response.data.user);
      this.isAuthenticatedSubject.next(true);
      this.router.navigate(['/home']);
    } catch (error) {
      console.error('Login failed', error);
      throw error;
    }
  }

  // Atualiza os dados do usuário
  public refreshUserData(): void {
    this.http.get(`${environment.api}/profile`).subscribe((response: any) => {
      this.storeUser(response.data);
    });
  }

  // Faz registro de um novo usuário
  async register(userData: { name: string; email: string; password: string }): Promise<void> {
    try {
      const response: any = await firstValueFrom(
        this.http.post(`${environment.api}/auth/register`, userData)
      );
      this.storeTokens(response.data.access_token, response.data.refresh_token, response.data.access_token_expires_in);
      this.isAuthenticatedSubject.next(true);
      this.router.navigate(['/home']);
    } catch (error) {
      console.error('Registration failed', error);
      throw error;
    }
  }

  // Faz logout e remove os tokens
  logout(): void {
    this.removeTokens();
    this.removeUser();
    this.isAuthenticatedSubject.next(false);
    this.http.post(`${environment.api}/auth/logout`, {}).subscribe(); // Envia a requisição de logout
    this.router.navigate(['/auth/logar']);
    this.layoutService.setOpenTabs([]); // Limpa as abas abertas ao fazer logout
  }

  // Método refreshToken retornando um Observable
  refreshToken(): Observable<void> {
    const refreshToken = this.getRefreshToken();
    if (!refreshToken) {
      this.logout();
      return throwError(() => new Error('No refresh token available'));
    }

    return this.http.post(`${environment.api}/auth/refresh`, {}, {
      headers: {
        Token: refreshToken
      }
    }).pipe(
      tap((response: any) => {
        this.storeTokens(response.access_token, response.refresh_token, response.access_token_expires_in);
      }),
      catchError((error) => {
        this.logout(); // Faz logout em caso de erro
        return throwError(() => error);
      })
    );
  }

  // Verifica se o token JWT está expirado
  private isTokenExpired(): boolean {
    const expiration = localStorage.getItem(this.TOKEN_EXPIRATION_KEY);
    if (!expiration) return true;
    return Date.now() > Number(expiration);
  }

  // Armazena os tokens e a data de expiração
  private storeTokens(token: string, refreshToken: string, expiresIn: number): void {
    const expiration = Date.now() + expiresIn * 1000; // Converte para milissegundos
    localStorage.setItem(this.TOKEN_KEY, token);
    localStorage.setItem(this.REFRESH_TOKEN_KEY, refreshToken);
    localStorage.setItem(this.TOKEN_EXPIRATION_KEY, expiration.toString());
  }

  // Remove os tokens e a data de expiração
  private removeTokens(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.REFRESH_TOKEN_KEY);
    localStorage.removeItem(this.TOKEN_EXPIRATION_KEY);
  }

  // Armazena o usuário logado
  public storeUser(user: any): void {
    this.user = user;
    localStorage.setItem('user', JSON.stringify(user));
  }

  // Retorna o usuário logado
  getUser(): User | null {
    return this.user;
  }

  private removeUser(): void {
    this.user = null;
    localStorage.removeItem('user');
  }

  // Obtém o token JWT
  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  // Obtém o refresh token
  getRefreshToken(): string | null {
    return localStorage.getItem(this.REFRESH_TOKEN_KEY);
  }

  // Verifica se há um token armazenado
  private hasToken(): boolean {
    return !!this.getToken();
  }

  getCurrentUser(): User | null {
    return this.currentUser;
  }

  hasPermission(permission: string | string[]): boolean {

    if (Array.isArray(permission) && permission.length === 0 || !permission || permission === '') return true;

    if (!this.user || !this.user.permissions) {
      return false;
    }

    const permissions = Array.isArray(permission) ? permission : [permission];
    return permissions.every(perm =>
      this.user!.permissions.some(userPerm => userPerm.name === perm && userPerm.is_active)
    );
  }
}
