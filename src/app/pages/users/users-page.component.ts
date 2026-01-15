import {Component, OnInit} from '@angular/core';
import {ContentComponent} from '../../components/content/content.component';
import {UserService} from '../../services/user.service';
import {ActivatedRoute, Router} from '@angular/router';
import {ListConfig} from '../../components/list/list.types';
import {UbListComponent} from '../../components/list/list.component';

@Component({
  imports: [
    ContentComponent,
    UbListComponent
  ],
  templateUrl: './users-page.component.html',
  standalone: true
})
export class UsersPage implements OnInit {

  protected loading = false;

  data: any[] = [];
  listConfig: ListConfig = {
    display: {
      title: 'Lista de Usuários',
      subtitle: 'Veja todos os usuários cadastrados no sistema'
    },
    actions: [
      {
        label: 'Convidar Usuário',
        icon: 'bx bx-plus',
        action: () => this.userService.openInviteUser()
      }
    ],
    itemActions: [
      {
        label: 'Editar',
        icon: 'bx bx-edit',
        action: (item: any) => this.open(item)
      },
      {
        label: 'Excluir',
        icon: 'bx bx-trash',
        color: 'danger',
        confirm: true,
        action: (item: any) => this.delete(item)
      }
    ],
    fields: [
      {
        label: 'ID',
        key: 'id'
      },
      {
        label: 'Nome',
        key: 'name',
        isTitleCard: true
      },
      {
        label: 'Email',
        key: 'email',
        isSubtitleCard: true
      },
      {
        label: 'Dt. Criação',
        key: 'created_at',
        type: 'date'
      },
      {
        label: 'Dt. Atualização',
        key: 'updated_at',
        type: 'date'
      },
      {
        label: 'Status',
        key: 'status',
        type: 'boolean',
      }
    ]
  }

  constructor(
    private userService: UserService,
    private router: Router,
    private route: ActivatedRoute
  ) {
  }

  ngOnInit() {
    this.load();
  }

  load() {
    this.loading = true;
    this.userService.getUsers().then(result => {
      this.data = result;
    }).finally(() => this.loading = false);

  }

  delete(event: any) {
    this.loading = true;
    this.userService.deleteUser(event.id).then(response => {
      this.load();
    }).finally(() => this.loading = false);
  }

  open(item?: any) {
    const commands = item ? ['manage', item.id] : ['manage'];
    this.router.navigate(commands, {relativeTo: this.route});
  }
}
