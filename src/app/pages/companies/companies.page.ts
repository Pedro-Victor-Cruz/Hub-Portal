import {Component, OnInit} from '@angular/core';
import {TableComponent, TableConfig} from '../../components/table/table.component';
import {CompanyService} from '../../services/company.service';
import {ContentComponent} from '../../components/content/content.component';

@Component({
  selector: 'app-services',
  imports: [
    ContentComponent,
    TableComponent
  ],
  templateUrl: './companies.page.html',
  standalone: true,
  styleUrl: './companies.page.scss'
})
export class CompaniesPage implements OnInit {

  protected loading = false;
  data: any[] = [];
  configTable: TableConfig = {
    columns: [
      {
        headerName: "ID",
        field: "id"
      },
      {
        headerName: "Nome",
        field: "name"
      },
      {
        headerName: "E-mail",
        field: "email"
      },
      {
        headerName: "CNPJ",
        field: "cnpj",
      }
    ],
    showAddButton: true,
    showEditButton: true,
    showDeleteButton: true,
  };

  constructor(
    private companyService: CompanyService
  ) {}

  ngOnInit() {
    this.load();
  }

  load() {
    this.loading = true;
    this.companyService.getCompanies().then(result => {
      this.data = result;
    }).finally(() => this.loading = false);
  }

  delete(event: any) {
    this.loading = true;
    this.companyService.deleteCompany(event.id).then(() => {
      this.load();
    }).finally(() => this.loading = false);
  }
}
