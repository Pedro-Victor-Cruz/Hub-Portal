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
    showExportButton: true,
    columns: [
      {
        headerName: "ID",
        field: "id",
        width: 80
      },
      {
        headerName: "Nome",
        field: "name"
      },
      {
        headerName: "Email",
        field: "email"
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
