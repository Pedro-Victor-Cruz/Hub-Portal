import {Component, OnInit} from '@angular/core';
import {TableComponent, TableConfig} from '../../components/table/table.component';
import {ContentComponent} from '../../components/content/content.component';
import {PermissionService} from '../../services/permission.service';
import {Pagination} from '../../components/pagination/pagination.component';
import {PaginationMixin} from '../../mixins/pagination.mixin';
import {FolderConfig, FolderItem, FolderViewComponent} from '../../components/folder-view/folder-view.component';

@Component({
  selector: 'app-services',
  imports: [
    ContentComponent,
    FolderViewComponent
  ],
  templateUrl: './permissions.page.html',
  standalone: true,
  styleUrl: './permissions.page.scss'
})
export class PermissionsPage implements OnInit{

  folderPermissionsConfig: FolderConfig = {
    groupBy: 'group',
    folderName: 'group',
    itemDescription: 'name',
    itemName: 'description',
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
  };

  data: FolderItem[] = [];

  constructor(
    private permissionService: PermissionService
  ) {}

  ngOnInit() {
    this.loadData();
  }

  async loadData(): Promise<void> {
    this.data = await this.permissionService.getPermissions();
  }

}
