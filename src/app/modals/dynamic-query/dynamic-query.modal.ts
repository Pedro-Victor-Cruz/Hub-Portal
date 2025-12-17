import {Component, OnInit} from '@angular/core';
import {ModalRef} from '../modal/modal.service';
import {TabsComponent} from '../../components/tabs/tabs.component';
import {TabComponent} from '../../components/tabs/tab/tab.component';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../components/form/form-error-handler.service';
import {InputComponent} from '../../components/form/input/input.component';
import {DynamicQueryService} from '../../services/dynamic-query.service';
import {ToastService} from '../../components/toast/toast.service';
import {Utils} from '../../services/utils.service';
import {ServicesService} from '../../services/services.service';
import {DropdownComponent} from '../../components/form/dropdown/dropdown.component';
import {ButtonComponent} from '../../components/form/button/button.component';
import {ServiceParametersComponent} from '../../components/dynamic-query/service-parameters/service-parameters.component';
import {TextareaComponent} from '../../components/form/textarea/textarea.component';
import {CommonModule} from '@angular/common';
import {
  DynamicQueryFilter,
  FiltersComponent
} from '../../components/filters/filters.component';
import {DynamicQueryTestComponent} from '../../components/dynamic-query/dynamic-query-test/dynamic-query-test.component';
import {DynamicQueryComponent} from '../../components/dynamic-query/dynamic-query.component';

@Component({
  imports: [
    DynamicQueryComponent
  ],
  template: `
    <app-dynamic-query [dynamicQueryKey]="dynamicQueryKey"></app-dynamic-query>
  `,
  standalone: true
})
export class DynamicQueryModal {
  dynamicQueryKey: string | null = null;
  loading: boolean = false;
}
