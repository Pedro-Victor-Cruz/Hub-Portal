import {Component} from '@angular/core';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {ContentComponent} from '../../../components/content/content.component';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {InputComponent} from '../../../components/form/input/input.component';
import {NgIf} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {ActivatedRoute, Router} from '@angular/router';
import {PermissionService} from '../../../services/permission.service';
import {CompanyService} from '../../../services/company.service';
import {FolderConfig, FolderViewComponent} from '../../../components/folder-view/folder-view.component';
import {Utils} from '../../../services/utils.service';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';

@Component({
  imports: [
    ContentComponent,
    DropdownComponent,
    InputComponent,
    ReactiveFormsModule,
    FormsModule,
    FolderViewComponent,
    NgIf,
    ButtonComponent
  ],
  templateUrl: './manage-permission-group.page.html',
  standalone: true,
  styleUrl: './manage-permission-group.page.scss'
})
export class ManagePermissionGroupPage {

  protected errors: { [key: string]: string } = {};
  protected idGroup: string | undefined = undefined;
  protected permissions: any[] = [];
  protected companies: any[] = [];
  protected accessLevels: any[] = [];
  protected loading: boolean = false;
  protected loadingAction: boolean = false;
  protected folderPermissionsConfig: FolderConfig = {
    groupBy: 'group',
    folderName: 'group',
    itemDescription: 'name',
    itemName: 'description',
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
    selectionType: 'checkbox'
  };
  protected form = {
    description: "",
    name: "",
    company_id: null,
    access_level: null,
    permissions: {}
  }

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private permissionService: PermissionService,
    private companyService: CompanyService,
    private utils: Utils,
  ) {
    this.idGroup = this.route.snapshot.params['id'];
    this.load();
  }

  async load() {
    this.loading = true;
    try {

      const [permissions, companies, accessLevels] = await Promise.all([
        this.permissionService.getPermissions(),
        this.companyService.getCompanies(),
        this.permissionService.accessLevels()
      ])

      this.permissions = permissions;
      this.companies = companies;
      this.accessLevels = accessLevels;

      if (this.accessLevels && this.accessLevels.length > 0) {
        this.form.access_level = this.accessLevels[0].value;
      }

      if (this.idGroup) {
        const group = await this.permissionService.getPermissionGroup(this.idGroup);
        this.form = {
          ...group,
          permissions: group.permissions.map((p: any) => p.name),
        }
      }

    } catch (err: any) {
      console.error('Error loading permission group:', err);
    } finally {
      this.loading = false;
    }

  }

  back() {
    const url = this.idGroup ? '../../' : '../';
    this.router.navigate([url], {relativeTo: this.route});
  }

  formatName(event: FocusEvent) {
    const target = event.target as HTMLInputElement;
    this.form.name = target.value
      .replace(/ /g, '-')
      .toLowerCase()
      .replace(/[^a-z0-9-]/g, '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  createName(event: FocusEvent) {
    const target = event.target as HTMLInputElement;
    if (!this.form.name || this.form.name.trim() === '') {
      this.form.name = target.value
        .replace(/ /g, '-')
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
  }

  async save() {
    const isUpdate = !!this.idGroup;

    let form = {
      ...this.form,
      permissions: Object.keys(this.form.permissions)
    }

    this.loadingAction = true;
    try {
      const response = isUpdate ?
        await this.permissionService.updatePermissionGroup(this.idGroup!, form) :
        await this.permissionService.createPermissionGroup(form);

      if (this.idGroup) window.location.reload();
      else await this.router.navigate([response.data.id], {relativeTo: this.route});
    } catch (err: any) {
      this.errors = this.utils.handleErrorsForm(err, this.form);
    } finally {
      this.loadingAction = false;
    }
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
