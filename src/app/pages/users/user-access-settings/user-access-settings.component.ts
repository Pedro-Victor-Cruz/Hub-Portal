import {Component, EventEmitter, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {PermissionService} from '../../../services/permission.service';
import {DropdownComponent} from '../../../components/form/dropdown/dropdown.component';
import {FolderConfig, FolderViewComponent} from '../../../components/folder-view/folder-view.component';
import {BehaviorSubject, Subject, switchMap, takeUntil} from 'rxjs';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {ToastService} from '../../../components/toast/toast.service';
import {HasPermissionDirective} from '../../../directives/has-permission.directive';

@Component({
  selector: 'user-access-settings',
  imports: [
    DropdownComponent,
    FolderViewComponent,
    ButtonComponent,
    HasPermissionDirective
  ],
  templateUrl: './user-access-settings.component.html',
  standalone: true,
  styleUrl: './user-access-settings.component.scss'
})
export class UserAccessSettingsComponent implements OnInit, OnDestroy {
  @Input({required: true}) userId!: string;

  private _companyId: string | null = null;
  @Input()
  set companyId(value: string | null) {
    this._companyId = value;
    this.companyId$.next(value);
  }

  get companyId(): string | null {
    return this._companyId;
  }

  @Input() loading: boolean = false;
  @Output() loadingChange = new EventEmitter<boolean>();

  loadingAction: boolean = false;
  groups: any[] = [];
  permissions: any[] = [];
  selectedPermissions: any[] = [];
  groupId: string | null = null;

  folderPermissionsConfig: FolderConfig = {
    groupBy: 'group',
    folderName: 'group_description',
    itemDescription: 'name',
    itemName: 'description',
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
  };

  private companyId$ = new BehaviorSubject<string | null>(null);
  private destroy$ = new Subject<void>();

  constructor(
    private permissionService: PermissionService,
    private toast: ToastService
  ) {
  }

  ngOnInit() {
    this.setupCompanyIdSubscription();
    this.loadPermissions();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private setupCompanyIdSubscription(): void {
    this.companyId$.pipe(
      takeUntil(this.destroy$),
      switchMap(companyId => {
        this.setLoading(true);
        return this.permissionService.getPermissionsGroup(companyId);
      })
    ).subscribe({
      next: groups => {
        this.groups = groups;
        this.setLoading(false);
      },
      error: error => {
        console.error('Error loading permission groups:', error);
        this.setLoading(false);
      }
    });
  }

  private async loadPermissions(): Promise<void> {
    try {
      this.setLoading(true);
      this.permissions = await this.permissionService.getPermissions();
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
      await this.permissionService.assignPermissions(this.userId, this.selectedPermissions);
    } catch (error: any) {
      const message = error.error?.message || 'Erro ao salvar as permissões';
      this.toast.error(message);
    } finally {
      this.loadingAction = false;
    }
  }
}
