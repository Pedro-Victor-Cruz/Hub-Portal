import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {AuthService} from '../security/auth.service';

@Injectable(
  {providedIn: 'root'}
)
export class UserService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {
  }

  async getUsers(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/user`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar os usuários';
      this.toast.error(message);
      return [];
    }
  }

  async getUser(id: string) {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/user/${id}`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar o usuário';
      this.toast.error(message);
      return {};
    }
  }

  async createUser(data: any) {
    try {
      const response = await firstValueFrom(this.http.post<any>(`${this.API_URL}/user/create`, data));
      this.toast.success('Usuário criado com sucesso');
      return response;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao criar o usuário';
      this.toast.error(message);
      throw err;
    }
  }

  async updateUser(id: string, data: any) {
    try {
      const response = await firstValueFrom(this.http.put<any>(`${this.API_URL}/user/${id}/update`, data));
      this.toast.success('Usuário atualizado com sucesso');
      return response;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao atualizar o usuário';
      this.toast.error(message);
      throw err;
    }
  }

  async deleteUser(id: number) {
    try {
      const response = await firstValueFrom(this.http.delete<any>(`${this.API_URL}/user/${id}/delete`));
      this.toast.success('Usuário deletado com sucesso');
      return response;
    } catch (err: any) {
      const message_1 = err.error.message || 'Erro ao deletar o usuário';
      this.toast.error(message_1);
      throw err;
    }
  }

}
