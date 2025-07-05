import {Component, OnInit} from '@angular/core';
import {FolderConfig, FolderViewComponent} from '../../components/folder-view/folder-view.component';
import {ParameterService} from '../../services/parameter.service';
import {PanelAreaComponent} from '../../components/content/panels/panel-area.component';
import {ContentComponent} from '../../components/content/content.component';
import {PanelAction, PanelComponent} from '../../components/content/panels/panel/panel.component';
import {AuthService} from '../../security/auth.service';
import {InputComponent} from '../../components/form/input/input.component';
import {FormsModule} from '@angular/forms';
import {Utils} from '../../services/utils.service';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {DropdownComponent} from '../../components/form/dropdown/dropdown.component';
import {NgForOf, NgIf} from '@angular/common';
import {PermissionService} from '../../services/permission.service';
import {ToggleSwitchComponent} from '../../components/form/toggle-switch/toggle-switch.component';
import {ButtonComponent} from '../../components/form/button/button.component';

@Component({
  imports: [
    PanelAreaComponent,
    ContentComponent,
    PanelComponent,
    FolderViewComponent,
    InputComponent,
    FormsModule,
    DropdownComponent,
    NgIf,
    ToggleSwitchComponent,
    ButtonComponent,
    NgForOf
  ],
  templateUrl: './parameters.page.html',
  standalone: true,
  styleUrl: './parameters.page.scss'
})
export class ParametersPage implements OnInit {

  protected errors: { [key: string]: string } = {};
  protected loading: boolean = false;
  protected loadingAction: boolean = false;
  protected showOptions: boolean = false;
  protected permissions = {
    CREATE_PARAMETER: false,
    DELETE_PARAMETER: false
  }
  protected parameters: any[] = [];
  protected categories: any[] = [];
  protected accessLevels: any[] = [];
  protected itemSelected: any = null;
  protected types: any[] = [
    {name: 'text', label: 'Texto'},
    {name: 'integer', label: 'Número Inteiro'},
    {name: 'decimal', label: 'Número Decimal'},
    //   {name: 'date', label: 'Data'},
    {name: 'list', label: 'Lista'},
    {name: 'boolean', label: 'Lógico (Bool)'}
  ]
  protected folderConfig: FolderConfig = {
    groupBy: 'category',
    folderName: 'category',
    itemDescription: 'key',
    itemName: 'description',
    folderIcon: 'bx-folder',
    itemIcon: 'bx-file',
  };
  protected actionsPanel: PanelAction[] = [
    {
      title: 'Adicionar Parâmetro',
      icon: 'bx bx-plus',
      severity: 'success',
      disabled: !this.permissions.CREATE_PARAMETER,
      action: () => {
        // Implement the logic to add a new parameter
        console.log('Adicionar Parâmetro action triggered');
      }
    },
    {
      title: 'Excluir Parâmetro',
      icon: 'bx bx-trash',
      disabled: !this.permissions.DELETE_PARAMETER || !this.itemSelected,
      severity: 'error',
      action: () => {
        // Implement the logic to delete a parameter
        console.log('Excluir Parâmetro action triggered');
      },
    }
  ];
  protected optionValue: string[] = [];
  protected form: {
    key: string;
    description: string;
    category: string;
    type: string;
    default_value?: any;
    options: string[];
    access_level: number | null;
  } = {
    key: "",
    description: "",
    category: "",
    type: "text",
    default_value: "",
    options: [],
    access_level: null
  };

  constructor(
    private parameterService: ParameterService,
    private auth: AuthService,
    private permissionService: PermissionService,
    private utils: Utils
  ) {
    this.permissions.CREATE_PARAMETER = this.auth.hasPermission('parameter.create');
    this.permissions.DELETE_PARAMETER = this.auth.hasPermission('parameter.delete');
  }

  ngOnInit() {
    this.load();
  }

  protected async load() {
    try {
      this.loading = true;

      const [parameters, accessLevels, categories] = await Promise.all([
        this.parameterService.getParameters(),
        this.permissionService.accessLevels(),
        this.parameterService.getParameterCategories()
      ]);

      this.parameters = parameters;
      this.accessLevels = accessLevels;
      this.categories = categories;
      this.form.access_level = this.accessLevels[0].value
    } catch (err: any) {
      console.error('Error loading parameters:', err);
    } finally {
      this.loading = false;
    }
  }

  protected selectItem(item: any) {
    if (item) {
      this.itemSelected = item;
      this.form = {
        key: item.key || "",
        description: item.description || "",
        category: item.category || "",
        type: item.type || "text",
        default_value: item.default_value || "",
        options: item.options || [],
        access_level: item.access_level || null
      }
      if (item && item.options) {
        this.optionValue = [...item.options];
      } else {
        this.optionValue = this.form.options?.length ? [...this.form.options] : [];
      }
    } else {
      this.itemSelected = null;
      this.resetForm();
    }
    console.log('Item selected:', item);
  }

  formatKey() {
    this.form.key = Utils.removeAccents(this.form.key)
      .replace(/[^a-zA-Z0-9_]/g, '')
      .toUpperCase();
  }

  protected updateOption(index: number) {
    this.form.options[index] = this.optionValue[index];
    console.log('Updated option at index', index, 'to', this.form.options[index], this.form.options);
  }

  protected addOption() {
    this.form.options.push('');
    this.optionValue.push('');
  }

  protected removeOption(index: number) {
    this.form.options.splice(index, 1);
    this.optionValue.splice(index, 1);
  }

  public async save() {
    this.loadingAction = true;
    try {
      if (this.itemSelected) {
        // Update existing parameter
        await this.parameterService.updateParameter(this.itemSelected.id, this.form);
      } else {
        // Create new parameter
        await this.parameterService.createParameter(this.form);
      }
      this.resetForm();
      await this.load();
    } catch (error: any) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loadingAction = false;
    }
  }

  public resetForm() {
    this.itemSelected = null;
    this.form = {
      key: "",
      description: "",
      category: "",
      type: "text",
      default_value: "",
      options: [],
      access_level: null
    };
    this.optionValue = [];
    this.errors = {};
    this.showOptions = false;
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
