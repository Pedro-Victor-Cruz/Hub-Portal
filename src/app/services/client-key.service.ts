import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Router } from '@angular/router';
import {Client} from '../models/user';

@Injectable({
  providedIn: 'root',
})
export class ClientKeyService {
  private readonly CLIENT_KEY_STORAGE = 'current_client_key';
  private readonly AVAILABLE_CLIENTS_STORAGE = 'available_clients';

  private currentClientKeySubject = new BehaviorSubject<string | null>(null);
  private availableClientsSubject = new BehaviorSubject<Client[]>([]);

  public currentClientKey$ = this.currentClientKeySubject.asObservable();
  public availableClients$ = this.availableClientsSubject.asObservable();

  constructor(private router: Router) {
    this.initializeClientKey();
  }

  /**
   * Inicializa o client_key a partir do localStorage
   */
  private initializeClientKey(): void {
    try {
      const storedKey = localStorage.getItem(this.CLIENT_KEY_STORAGE);
      const storedClients = localStorage.getItem(this.AVAILABLE_CLIENTS_STORAGE);

      if (storedKey) {
        this.currentClientKeySubject.next(storedKey);
      }

      if (storedClients) {
        this.availableClientsSubject.next(JSON.parse(storedClients));
      }
    } catch (error) {
      console.error('Erro ao inicializar client_key:', error);
    }
  }

  /**
   * Define o client_key atual
   */
  setClientKey(clientKey: string): void {
    try {
      localStorage.setItem(this.CLIENT_KEY_STORAGE, clientKey);
      this.currentClientKeySubject.next(clientKey);
    } catch (error) {
      console.error('Erro ao salvar client_key:', error);
    }
  }

  /**
   * Obtém o client_key atual
   */
  getClientKey(): string | null {
    return this.currentClientKeySubject.value;
  }

  /**
   * Define a lista de clientes disponíveis para o usuário
   */
  setAvailableClients(clients: Client[]): void {
    try {
      localStorage.setItem(this.AVAILABLE_CLIENTS_STORAGE, JSON.stringify(clients));
      this.availableClientsSubject.next(clients);
    } catch (error) {
      console.error('Erro ao salvar clientes disponíveis:', error);
    }
  }

  /**
   * Obtém a lista de clientes disponíveis
   */
  getAvailableClients(): Client[] {
    return this.availableClientsSubject.value;
  }

  /**
   * Troca o client_key atual e navega para a home do novo cliente
   */
  switchClient(clientKey: string): void {
    const currentKey = this.getClientKey();

    if (currentKey === clientKey) {
      return; // Já está no cliente correto
    }

    this.setClientKey(clientKey);

    // Navega para a home do novo cliente
    this.router.navigate([clientKey, 'home']);
  }

  /**
   * Limpa o client_key (usado no logout)
   */
  clearClientKey(): void {
    try {
      localStorage.removeItem(this.CLIENT_KEY_STORAGE);
      localStorage.removeItem(this.AVAILABLE_CLIENTS_STORAGE);
      this.currentClientKeySubject.next(null);
      this.availableClientsSubject.next([]);
    } catch (error) {
      console.error('Erro ao limpar client_key:', error);
    }
  }

  /**
   * Verifica se um client_key está definido
   */
  hasClientKey(): boolean {
    return !!this.getClientKey();
  }

  /**
   * Constrói a URL completa com o client_key
   */
  buildUrl(path: string): string {
    const clientKey = this.getClientKey();

    if (!clientKey) {
      return path;
    }

    // Remove barra inicial do path se existir
    const cleanPath = path.startsWith('/') ? path.substring(1) : path;

    // Se o path já contém o client_key, retorna como está
    if (cleanPath.startsWith(clientKey + '/')) {
      return '/' + cleanPath;
    }

    // Adiciona o client_key
    return `/${clientKey}/${cleanPath}`;
  }

  /**
   * Remove o client_key de uma URL
   */
  removeClientKeyFromPath(path: string): string {
    const clientKey = this.getClientKey();

    if (!clientKey) {
      return path;
    }

    const cleanPath = path.startsWith('/') ? path.substring(1) : path;

    if (cleanPath.startsWith(clientKey + '/')) {
      return '/' + cleanPath.substring(clientKey.length + 1);
    }

    return path;
  }
}
