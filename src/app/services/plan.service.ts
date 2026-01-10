import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {HttpClient} from '@angular/common/http';
import {firstValueFrom} from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class PlanService {

  private API_URL = environment.api;

  constructor(private http: HttpClient) {
  }

  /**
   * Carrega o cliente armazenado no localStorage
   */
  public getPlans() {
    return firstValueFrom(this.http.get<any>(`${this.API_URL}/plans`));
  }

}
