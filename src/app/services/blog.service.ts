import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {ToastService} from '../components/toast/toast.service';
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';
import {AuthService} from '../security/auth.service';

@Injectable(
  {providedIn: 'root'}
)
export class BlogService {

  private API_URL = environment.api;

  constructor(
    private http: HttpClient,
    private toast: ToastService
  ) {
  }

  async getCategories(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/blog/categories/list`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as categorias';
      this.toast.error(message);
      return [];
    }
  }

  async getBlogs(): Promise<any[]> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/blog`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar as notícias';
      this.toast.error(message);
      return [];
    }
  }

  async getBlog(id: string): Promise<any> {
    try {
      const response = await firstValueFrom(this.http.get<any>(`${this.API_URL}/blog/${id}`));
      return response.data;
    } catch (err: any) {
      const message = err.error.message || 'Erro ao buscar a notícia';
      this.toast.error(message);
      return {};
    }
  }

  createBlog(data: any) {
      return firstValueFrom(this.http.post<any>(`${this.API_URL}/blog/create`, data));
  }

  updateBlog(id: string, formData: any) {
      return firstValueFrom(this.http.post<any>(`${this.API_URL}/blog/${id}/update`, formData));
  }

  async deleteBlog(id: string) {
    try {
      return await firstValueFrom(this.http.delete<any>(`${this.API_URL}/blog/${id}/delete`));
    } catch (err: any) {
      const message = err.error.message || 'Erro ao deletar o notícia';
      this.toast.error(message);
      console.log(err);
      return err;
    }
  }
}
