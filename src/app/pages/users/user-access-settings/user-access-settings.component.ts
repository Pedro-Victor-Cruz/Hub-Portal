import {Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges} from '@angular/core';
import {PermissionService} from '../../../services/permission.service';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {FolderConfig, FolderViewComponent} from '../../../components/folder-view/folder-view.component';
import {BehaviorSubject, Subject, switchMap, takeUntil} from 'rxjs';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {ToastService} from '../../../components/toast/toast.service';
import {HasPermissionDirective} from '../../../directives/has-permission.directive';
import {User} from '../../../models/user';
import {Utils} from '../../../services/utils.service';

@Component({
  selector: 'user-access-settings',
  imports: [
    DropdownComponent,
    FolderViewComponent,
    ButtonComponent,
    HasPermissionDirective
  ],
  templateUrl: './user-access-settings.component.html',
  standalone: true
})
export class UserAccessSettingsComponent implements OnInit, OnDestroy, OnChanges {

  @Input({required: true}) user!: User;
  @Input() loading: boolean = false;
  @Output() loadingChange = new EventEmitter<boolean>();
  @Output() reload = new EventEmitter<void>();

  loadingAction: boolean = false;
  groups: any[] = [];
  permissions: any[] = [];
  selectedPermissions:  Record<string | number, boolean> | (string | number)[] = [];
  selectedPermissionsOriginal:  Record<string | number, boolean> = {};
  groupId: number | null = null;

  folderPermissionsConfig: FolderConfig = {
    groupBy: 'group',
    folderName: 'group',
    itemDescription: 'name',
    itemName: (item: any) => `${item.description} ${this.getSourcePermission(item.name)}`,
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
    selectionType: 'toggle'
  };

  private destroy$ = new Subject<void>();

  constructor(
    private permissionService: PermissionService,
    private toast: ToastService
  ) {}

  ngOnChanges(changes: SimpleChanges) {
    if (changes['user'] && this.user) {
      this.loadPermissions();
    }
  }

  ngOnInit() {
    this.loadPermissions();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private async loadPermissions(): Promise<void> {
    try {
      this.setLoading(true);
      this.permissions = await this.permissionService.getPermissions();
      this.groups = await this.permissionService.getPermissionsGroup();

      this.groupId = this.user.group ? this.user.group.id : null;
      this.selectedPermissions = this.user.permissions.reduce(
        (acc: Record<string, boolean>, permission) => {
          acc[permission.name] = permission.is_active;
          return acc;
        },
        {} as Record<string, boolean>
      );

      this.selectedPermissionsOriginal = {...this.selectedPermissions};

    } catch (error) {
      console.error('Error loading permissions:', error);
    } finally {
      this.setLoading(false);
    }
  }

  setLoading(value: boolean): void {
    this.loading = value;
    this.loadingChange.emit(value);
  }

  async save() {
    this.loadingAction = true;

    try {
      // Garante que selectedPermissions está no formato de Record
      const currentPermissions = this.selectedPermissions as Record<string | number, boolean>;

      const permissions: { name: string; value: boolean }[] = Object.keys(currentPermissions)
        .filter(name => this.selectedPermissionsOriginal[name] !== currentPermissions[name])
        .map(name => ({
          name,
          value: currentPermissions[name]
        }));

      // Atualiza as permissões do usuário
      if (permissions.length > 0) {
        await this.permissionService.assignPermissions(this.user.id, permissions);
      }

      // Atualiza o grupo de permissões se necessário
      if (!this.user.group || this.groupId !== this.user.group.id) {
        await this.permissionService.assignGroup(this.user.id, this.groupId);
      }


      this.toast.success('Acessos do usuário atualizados com sucesso.');

      // Atualiza o estado original
      this.selectedPermissionsOriginal = { ...currentPermissions };
      this.reload.emit();
    } catch (err: any) {
      this.toast.error(Utils.getErrorMessage(err));
    } finally {
      this.loadingAction = false;
    }
  }


  private getSourcePermission(name: string): string {
    const perm =  this.user.permissions.find(permission => permission.name === name) || null;
    if (perm) return '- ' + (perm.source === 'user' ? 'U' : 'G');
    return ''
  }
}
