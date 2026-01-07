import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  DashboardTemplate,
  DashboardTemplateService,
  ImportTemplateRequest
} from '../../../services/dashboard-template.service';
import {ToastService} from '../../../components/toast/toast.service';
import {ModalRef} from '../../modal/modal.service';


@Component({
  selector: 'app-import-template-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './import-template.modal.html',
  styleUrl: './import-template.modal.scss'
})
export class ImportTemplateModal implements OnInit {

  modalRef!: ModalRef;
  template!: DashboardTemplate;
  loading: boolean = false;

  formData: ImportTemplateRequest = {
    dashboard_name: '',
    dashboard_description: '',
    dashboard_key: ''
  };

  constructor(
    private templateService: DashboardTemplateService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    // Pré-preenche com sugestões do template
    this.formData.dashboard_name = this.template.name;
    this.formData.dashboard_description = this.template.description;
  }

  /**
   * Retorna ícone da categoria
   */
  getCategoryIcon(category: string): string {
    const icons: Record<string, string> = {
      'vendas': 'bx-line-chart',
      'financeiro': 'bx-dollar',
      'operacional': 'bx-cog',
      'marketing': 'bx-bullseye',
      'recursos-humanos': 'bx-user',
      'ti': 'bx-server'
    };
    return icons[category] || 'bx-grid-alt';
  }

  async onSubmit() {
    if (!this.formData.dashboard_name) {
      this.toast.error('O nome do dashboard é obrigatório');
      return;
    }

    this.loading = true;

    try {
      const response = await this.templateService.importTemplate(
        this.template.key,
        this.formData
      );

      // Fecha o modal com sucesso e dados do dashboard criado
      this.modalRef.close({
        success: true,
        dashboardKey: response.data.dashboard.key
      });
    } catch (error) {
      // Erro já tratado no service
    } finally {
      this.loading = false;
    }
  }

  onCancel() {
    this.modalRef.close();
  }
}
