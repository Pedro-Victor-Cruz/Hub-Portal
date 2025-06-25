import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class PaginationService {
  constructor(private http: HttpClient) {}

  async paginate<T>(url: string, page: number = 1, perPage: number = 15): Promise<{ data: T[], pagination: any }> {
    const params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    const headers = {
      'X-Paginate': perPage.toString(),
    }

    const response = await firstValueFrom(this.http.get<any>(url, { params, headers }));

    return {
      data: response.data,
      pagination: response.pagination
    };
  }
}
