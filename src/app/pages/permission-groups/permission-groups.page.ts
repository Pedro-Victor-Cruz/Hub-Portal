import {Component, OnInit} from '@angular/core';
import {PermissionService} from '../../services/permission.service';
import {ContentComponent} from '../../components/content/content.component';
import {TableComponent, TableConfig} from '../../components/table/table.component';

@Component({
  selector: 'app-permission-groups',
  imports: [
    ContentComponent,
    TableComponent
  ],
  templateUrl: './permission-groups.page.html',
  standalone: true,
  styleUrl: './permission-groups.page.scss'
})
export class PermissionGroupsPage implements OnInit {

  groups: any[] = [];
  loading: boolean = true;
  configTable: TableConfig = {
    cols: [
      { name: "Descrição", path: "description" },
      { name: "Nome", path: "name" },
      { name: "Empresa", path: "company.name" }
    ],
    showAddButton: true,
    showEditButton: true,
    showDeleteButton: true,
  };

  constructor(
    private permissionService: PermissionService
  ) {}

  ngOnInit() {
    this.loadGroups();
  }

  async loadGroups() {
    this.loading = true;
    try {
      this.groups = await this.permissionService.getPermissionsGroup();
    } catch (error) {
      console.error('Erro ao carregar grupos de permissões:', error);
    } finally {
      this.loading = false;
    }
  }

  delete(event: any) {
    this.loading = true;
    this.permissionService.deletePermissionGroup(event.id).then(response => {
      this.loadGroups();
    }).finally(() => this.loading = false);
  }
}
