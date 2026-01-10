import {Component, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {AuthService} from '../../../security/auth.service';
import {PlanService} from '../../../services/plan.service';
import {ToastService} from '../../../components/toast/toast.service';
import {Utils} from '../../../services/utils.service';
import {ModalService} from '../../../modals/modal/modal.service';
import {NewProjectModal} from '../../../modals/new-project/new-project.modal';

export interface Plan {
  id: number;
  code: string;
  name: string;
  price: string | null;
  currency: string;
  billing_cycle: string;
  limits: {
    users: number | null;
    dashboards: number | null;
  };
  presentation: {
    cta: string;
    tag: string;
    icon: string;
    color: string;
    features: string[];
    highlight: boolean;
    description: string;
  };
  active: boolean;
  is_free: boolean;
  formatted_price: string;
}

@Component({
  selector: 'app-plans',
  imports: [CommonModule],
  templateUrl: './plans.page.html',
  standalone: true,
  styleUrl: './plans.page.scss'
})
export class PlansPage implements OnInit {

  protected loading: boolean = false;
  protected plans: Plan[] = [];

  constructor(
    protected auth: AuthService,
    private planService: PlanService,
    private toast: ToastService,
    private modalService: ModalService
  ) {
  }

  ngOnInit() {
    this.loadPlans();
  }

  async loadPlans() {
    this.loading = true;
    try {
      const response = await this.planService.getPlans();
      // Ordena os planos por sort_order
      this.plans = response.data.sort((a: any, b: any) => a.sort_order - b.sort_order);
    } catch (err: any) {
      const message = Utils.getErrorMessage(err, 'Ops! Não foi possível carregar os planos.');
      this.toast.error(message);
    } finally {
      this.loading = false;
    }
  }

  onSelectPlan(plan: Plan): void {
    this.modalService.open({
      title: plan.name,
      component: NewProjectModal,
      size: 'md',
      data: {
        plan: plan
      }
    })
  }

}
