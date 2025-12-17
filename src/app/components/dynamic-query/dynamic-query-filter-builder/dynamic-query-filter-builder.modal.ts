import {Component, OnInit} from '@angular/core';
import {ModalRef} from '../../../modals/modal/modal.service';
import {ButtonComponent} from '../../form/button/button.component';
import {DropdownComponent} from '../../form/dropdown/dropdown.component';
import {InputComponent} from '../../form/input/input.component';
import {FormsModule} from '@angular/forms';
import {ToggleSwitchComponent} from '../../form/toggle-switch/toggle-switch.component';
import {MultiselectComponent} from '../../form/multiselect/multiselect.component';
import {DynamicQuery} from '../dynamic-query.component';

@Component({
  imports: [
    ButtonComponent,
    DropdownComponent,
    InputComponent,
    FormsModule,
    ToggleSwitchComponent,
    MultiselectComponent
  ],
  templateUrl: './dynamic-query-filter-builder.modal.html',
  styleUrl: './dynamic-query-filter-builder.modal.scss',
  standalone: true
})
export class DynamicQueryFilterBuilderModal implements OnInit {

  modalRef!: ModalRef;
  dynamicQuery!: DynamicQuery;
  loading: boolean = false;
  values: { [key: string]: any } = {};

  ngOnInit() {
    // Inicializar valores para cada filtro
    if (this.dynamicQuery?.active_filters) {
      this.dynamicQuery.active_filters.forEach(filter => {
        // Inicializa com null ou valor vazio dependendo do tipo
        if (!this.values.hasOwnProperty(filter.var_name)) {
          switch (filter.type) {
            case 'text':
            case 'array':
              this.values[filter.var_name] = '';
              break;
            case 'number':
              this.values[filter.var_name] = null;
              break;
            case 'boolean':
              this.values[filter.var_name] = false;
              break;
            case 'select':
            case 'multiselect':
              this.values[filter.var_name] = null;
              break;
            case 'date':
              this.values[filter.var_name] = null;
              break;
            default:
              this.values[filter.var_name] = null;
          }
        }
      });
    }
  }

  getFilterOptions(options: any) {
    return Object.keys(options).map(key => ({value: key, label: options[key]}));
  }

  save() {
    // Limpar valores undefined antes de enviar
    const cleanedValues = Object.fromEntries(
      Object.entries(this.values).filter(([_, value]) => value !== undefined)
    );
    this.modalRef.close(cleanedValues);
  }
}
