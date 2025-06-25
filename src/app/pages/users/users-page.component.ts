import {Component, OnInit} from '@angular/core';
import {ContentComponent} from '../../components/content/content.component';
import {TableComponent, TableConfig} from '../../components/table/table.component';
import {UserService} from '../../services/user.service';

@Component({
  imports: [
    ContentComponent,
    TableComponent
  ],
  templateUrl: './users-page.component.html',
  standalone: true,
  styleUrl: './users-page.component.scss'
})
export class UsersPage implements OnInit {

  protected loading = false;

  data: any[] = [];
  configTable: TableConfig = {
    cols: [
      {
        name: "ID",
        path: "id"
      },
      {
        name: "Nome",
        path: "name"
      },
      {
        name: "Email",
        path: "email"
      }
    ],
    showAddButton: true,
    showEditButton: true,
    showDeleteButton: true,
  };

  constructor(
    private userService: UserService
  ) {
  }

  ngOnInit() {
    this.load();
  }

  load() {
    this.loading = true;
    this.userService.getUsers().then(result => {
      this.data = result;
    }).finally(() => this.loading = false);

  }

  delete(event: any) {
    this.loading = true;
    this.userService.deleteUser(event.id).then(response => {
      this.load();
    }).finally(() => this.loading = false);
  }
}
