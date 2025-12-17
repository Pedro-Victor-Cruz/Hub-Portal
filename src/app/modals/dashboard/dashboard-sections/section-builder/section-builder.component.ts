import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ModalRef } from '../../../modal/modal.service';
import {DashboardSection, DashboardService} from '../../../../services/dashboard.service';
import { InputComponent } from '../../../../components/form/input/input.component';
import { TextareaComponent } from '../../../../components/form/textarea/textarea.component';
import { ButtonComponent } from '../../../../components/form/button/button.component';
import { ToastService } from '../../../../components/toast/toast.service';
import {ToggleSwitchComponent} from '../../../../components/form/toggle-switch/toggle-switch.component';

@Component({
  selector: 'app-section-builder-modal',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    InputComponent,
    TextareaComponent,
    ButtonComponent,
    ToggleSwitchComponent
  ],
  template: `
    <form [formGroup]="form" (ngSubmit)="onSubmit()">
      <div class="p-fluid p-formgrid grid">

        <div class="col-12 md:col-6">
          <ub-input
            label="Título"
            placeholder="Ex: Visão Geral"
            formControlName="title">
          </ub-input>
        </div>


        <div class="col-12 md:col-6">
          <ub-input
            label="Chave da Seção"
            placeholder="Ex: visao-geral"
            formControlName="key">
          </ub-input>
        </div>

        <div class="col-12">
          <ub-textarea
            label="Descrição"
            placeholder="Descreva o propósito desta seção"
            formControlName="description"
            [rows]="3">
          </ub-textarea>
        </div>

        <div class="col-12 md:col-6">
          <ub-input
            label="Ordem"
            type="number"
            formControlName="order">
          </ub-input>
        </div>

        <div class="col-12 md:col-6 flex align-items-center">
          <ub-toggle-switch
            label="Seção Ativa"
            formControlName="active">
          </ub-toggle-switch>
        </div>

      </div>

      <div class="flex justify-content-end gap-2 mt-3">
        <ub-button
          type="button"
          (click)="modalRef.close()">Cancelar</ub-button>
        <ub-button
          type="submit"
          [disabled]="form.invalid"
          [loading]="loading">Salvar</ub-button>
      </div>
    </form>
  `,
  standalone: true
})
export class SectionBuilderComponent implements OnInit {
  modalRef!: ModalRef;
  dashboardKey!: string;
  parentSection?: DashboardSection;
  section?: DashboardSection;

  form: FormGroup;
  loading: boolean = false;

  constructor(
    private fb: FormBuilder,
    private toast: ToastService,
    private dashboardService: DashboardService
  ) {
    this.form = this.fb.group({
      title: ['', Validators.required],
      key: ['', Validators.required],
      description: [''],
      order: [0],
      active: [true]
    });
  }

  ngOnInit() {
    if (this.section) {
      this.form.patchValue(this.section);
    }
  }

  async onSubmit() {
    if (this.form.invalid) return;

    this.loading = true;
    try {
      const response = await this.dashboardService.createSection(this.dashboardKey, this.form.value);
      this.toast.success('Seção salva com sucesso');
      this.modalRef.close(response.data);
    } catch (error: any) {
      const message = error?.message || 'Erro ao salvar seção';
      this.toast.error(message);
    } finally {
      this.loading = false;
    }
  }
}
