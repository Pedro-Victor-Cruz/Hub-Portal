import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {User} from '../models/user';
import {Utils} from './utils.service';
import {ModalService} from '../modals/modal/modal.service';
import {UserInviteModal} from '../modals/user-invite/user-invite.modal';

@Injectable(
  {providedIn: 'root'}
)
export class UserService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService,
    private modalService: ModalService
  ) {
  }

  async getUsers(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/user`));
      return response.data;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return [];
    }
  }

  async getUser(id: string) {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/user/${id}`));
      return response.data as User;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      return null;
    }
  }

  async createUser(data: any) {
    try {
      const response = await firstValueFrom(this.http.post<any>(`${this.API_URL}/user/create`, data));
      this.toast.success('Usuário criado com sucesso');
      return response;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      throw err;
    }
  }

  async updateUser(id: string, data: any) {
    try {
      const response = await firstValueFrom(this.http.put<any>(`${this.API_URL}/user/${id}/update`, data));
      this.toast.success('Usuário atualizado com sucesso');
      return response;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      throw err;
    }
  }

  async deleteUser(id: number) {
    try {
      const response = await firstValueFrom(this.http.delete<any>(`${this.API_URL}/user/${id}/delete`));
      this.toast.success('Usuário deletado com sucesso');
      return response;
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
      throw err;
    }
  }

  openInviteUser() {
    this.modalService.open({
      component: UserInviteModal,
      title: 'Convidar Usuário',
      size: 'md'
    })
  }

  inviteUser(data: { email: string, group_id: number | null }) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/user/invite`, data));
  }

  getInvite(code: string) {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/user/invite/info?code=${code}`));
  }

  acceptInvite(code: string) {
    return firstValueFrom(this.http.post<any>(`${this.API_URL}/user/invite/accept`, {code}));
  }
}
