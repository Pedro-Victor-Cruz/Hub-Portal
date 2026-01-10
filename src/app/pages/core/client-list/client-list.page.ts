import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {CommonModule} from '@angular/common';
import {ClientService} from '../../../services/client.service';
import {Client} from '../../../models/user';
import {ToastService} from '../../../components/toast/toast.service';
import {Utils} from '../../../services/utils.service';
import {AuthService} from '../../../security/auth.service';
import {ContentComponent} from '../../../components/content/content.component';

@Component({
  selector: 'app-client-list',
  imports: [CommonModule, ContentComponent],
  templateUrl: './client-list.page.html',
  standalone: true,
  styleUrl: './client-list.page.scss'
})
export class ClientListPage implements OnInit {

  protected clients: Client[] = [];
  protected loading: boolean = false;
  protected error: string | null = null

  constructor(
    private clientService: ClientService,
    private router: Router,
    private toast: ToastService,
    protected auth: AuthService
  ) {
  }

  ngOnInit(): void {
    this.loadClients();
  }

  async loadClients(): Promise<void> {
    this.loading = true;
    this.error = null;

    try {
      const response = await this.clientService.getClients();
      this.clients = response.data;
    } catch (error) {
      const message = Utils.getErrorMessage(error, 'Ops! Não foi possível carregar as informações.');
      this.toast.error(message);
      this.error = message;
    } finally {
      this.loading = false;
    }
  }

  onClientSelect(client: Client): void {
    if (!client.active) return;
    this.router.navigate([`/${client.slug}/home`]);
  }

  onNewProject() {
    this.router.navigate([`/new-project`]);
  }
}
