import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {HttpClient} from '@angular/common/http';
import {BehaviorSubject, firstValueFrom} from 'rxjs';
import {Client} from '../models/user';

@Injectable({
  providedIn: 'root'
})
export class ClientService {
  private API_URL = environment.api;
  private readonly CLIENT_STORAGE_KEY = 'current_client';

  private currentClientSubject = new BehaviorSubject<Client | null>(null);
  public currentClient$ = this.currentClientSubject.asObservable();

  constructor(private http: HttpClient) {
    this.loadStoredClient();
  }

  /**
   * Carrega o cliente armazenado no localStorage
   */
  private loadStoredClient(): void {
    try {
      const stored = localStorage.getItem(this.CLIENT_STORAGE_KEY);
      if (stored) {
        const client = JSON.parse(stored);
        this.currentClientSubject.next(client);
      }
    } catch (error) {
      console.error('Erro ao carregar cliente armazenado:', error);
      this.clearStoredClient();
    }
  }

  /**
   * Armazena o cliente atual
   */
  private storeClient(client: Client): void {
    try {
      localStorage.setItem(this.CLIENT_STORAGE_KEY, JSON.stringify(client));
      this.currentClientSubject.next(client);
    } catch (error) {
      console.error('Erro ao armazenar cliente:', error);
    }
  }

  /**
   * Remove o cliente armazenado
   */
  private clearStoredClient(): void {
    localStorage.removeItem(this.CLIENT_STORAGE_KEY);
    this.currentClientSubject.next(null);
  }

  /**
   * Obtém a lista de clientes disponíveis
   */
  public getClients() {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/client`));
  }

  /**
   * Cria um novo cliente via API
   */
  public newClient(data: any) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/client`, data));
  }

  /**
   * Obtém informações do cliente atual via API
   */
  public async getClientInfo(): Promise<Client | null> {
    try {
      const response = await firstValueFrom(
        this.http.get<any>(`${this.API_URL}/client-info`)
      );

      if (response.data) {
        this.storeClient(response.data);
        return response.data;
      }

      return null;
    } catch (error) {
      console.error('Erro ao buscar informações do cliente:', error);
      return null;
    }
  }

  /**
   * Define o cliente atual
   */
  public setCurrentClient(client: Client | null): void {
    if (client) {
      this.storeClient(client);
    } else {
      this.clearStoredClient();
    }
  }

  /**
   * Obtém o cliente atual (do BehaviorSubject)
   */
  public getCurrentClient(): Client | null {
    return this.currentClientSubject.value;
  }

  /**
   * Obtém o slug do cliente atual
   */
  public getCurrentClientSlug(): string | null {
    const client = this.getCurrentClient();
    return client?.slug || null;
  }

  /**
   * Limpa o cliente atual (usado no logout)
   */
  public clearCurrentClient(): void {
    this.clearStoredClient();
  }
}
