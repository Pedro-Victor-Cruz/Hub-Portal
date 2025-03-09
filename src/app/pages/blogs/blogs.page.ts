import {Component, OnInit} from '@angular/core';
import {ContentComponent} from '../../components/content/content.component';
import {TableComponent, TableConfig} from '../../components/table/table.component';
import {BlogService} from '../../services/blog.service';

@Component({
  selector: 'app-departments',
  imports: [
    ContentComponent,
    TableComponent
  ],
  templateUrl: './blogs.page.html',
  standalone: true,
  styleUrl: './blogs.page.scss'
})
export class BlogsPage implements OnInit {

  protected loading = false;

  data: any[] = [];
  configTable: TableConfig = {
    cols: [
      {
        name: "ID",
        path: "id"
      },
      {
        name: "Título",
        path: "title"
      },
      {
        name: "Subtítulo",
        path: "subtitle"
      },
      {
        name: "Resumo",
        path: "excerpt",
      },
      {
        name: "Categoria",
        path: "category",
      }
    ],
    showAddButton: true,
    showEditButton: true,
    showDeleteButton: true,
  };

  constructor(
    private blogService: BlogService
  ) {}

  ngOnInit() {
    this.load();
  }

  load() {
    this.loading = true;
    this.blogService.getBlogs().then(result => {
      this.data = result;
    }).finally(() => this.loading = false);

  }

  delete(event: any) {
    this.loading = true;
    this.blogService.deleteBlog(event.id).then(response => {
      this.load();
    }).finally(() => this.loading = false);
  }
}
