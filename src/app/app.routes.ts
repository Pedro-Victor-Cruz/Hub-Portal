import {Routes} from '@angular/router';
import {HomePage} from './pages/home/home.page';
import {ProfilePage} from './pages/profile/profile.page';
import {AuthGuard} from './security/auth.guard';
import {ClientGuard} from './security/client.guard';
import {NavbarComponent} from './layout/navbar/navbar.component';
import {UsersPage} from './pages/users/users-page.component';
import {ManageUserPage} from './pages/users/manage/manage-user.page';
import {PermissionGuard} from './security/permission.guard';
import {PermissionsPage} from './pages/permissions/permissions.page';
import {PermissionGroupsPage} from './pages/permission-groups/permission-groups.page';
import {ManagePermissionGroupPage} from './pages/permission-groups/manage/manage-permission-group.page';
import {ParametersPage} from './pages/parameters/parameters.page';
import {DynamicQueriesPage} from './pages/dynamic-queries/dynamic-queries.page';
import {IntegrationsPage} from './pages/integrations/integrations.page';
import {LogsSystemPage} from './pages/logs-system/logs-system.page';
import {DashboardsPage} from './pages/dashboards/dashboards.page';
import {DashboardViewPage} from './pages/dashboards/dashboard-view/dashboard-view.page';
import {DashboardInvitePage} from './pages/dashboards/dashboard-invite/dashboard-invite.page';
import {DashboardTemplatesPage} from './pages/dashboards/dashboard-templates/dashboard-templates.page';
import {ClientListPage} from './pages/core/client-list/client-list.page';
import {AuthPage} from './pages/auth/auth.page';
import {PlansPage} from './pages/core/plans/plans.page';
import {AcceptProjectInvitationPage} from './pages/accept-project-invitation/accept-project-invitation.page';

export const routes: Routes = [
  {
    path: 'auth',
    children: [
      {
        path: 'logar',
        component: AuthPage,
      },
      {
        path: 'registrar',
        component: AuthPage,
      },
    ],
  },
  {
    path: 'invite/accept/:inviteCode',
    component: AcceptProjectInvitationPage
  },
  {
    path: '',
    redirectTo: 'home',
    pathMatch: 'full',
  },
  {
    path: 'dashboard/invite/:token',
    component: DashboardInvitePage
  },
  {
    path: '',
    canActivate: [AuthGuard],
    component: NavbarComponent,
    children: [
      {
        path: 'home',
        component: ClientListPage,
      },
      {
        path: 'new-project',
        component: PlansPage
      },
    ]
  },
  {
    path: '',
    canActivate: [AuthGuard],
    component: NavbarComponent,
    children: [
      {
        path: ':client_key',
        canActivate: [ClientGuard], // Guard aplicado aqui
        children: [
          {
            path: 'home',
            component: HomePage,
          },
          {
            path: 'perfil',
            component: ProfilePage,
          },
          {
            path: 'users',
            canActivate: [PermissionGuard],
            data: {
              permission: 'user.view',
            },
            children: [
              {
                path: "",
                component: UsersPage
              },
              {
                path: "manage",
                component: ManageUserPage,
                data: {
                  permission: 'user.create',
                }
              },
              {
                path: "manage/:id",
                component: ManageUserPage,
                data: {
                  permission: 'user.edit',
                }
              }
            ]
          },
          {
            path: 'permissions',
            canActivate: [PermissionGuard],
            data: {
              permission: 'permission.view',
            },
            children: [
              {
                path: "",
                component: PermissionsPage
              }
            ]
          },
          {
            path: 'integrations',
            canActivate: [PermissionGuard],
            data: {
              permission: 'integration.manage',
            },
            children: [
              {
                path: "",
                component: IntegrationsPage
              }
            ]
          },
          {
            path: 'groups',
            canActivate: [PermissionGuard],
            data: { permission: 'permission_group.view' },
            children: [
              {
                path: '',
                component: PermissionGroupsPage
              },
              {
                path: 'manage',
                component: ManagePermissionGroupPage,
                data: { permission: 'permission_group.create' }
              },
              {
                path: 'manage/:id',
                component: ManagePermissionGroupPage,
                data: { permission: 'permission_group.edit' }
              }
            ]
          },
          {
            path: 'parameters',
            canActivate: [PermissionGuard],
            data: {
              permission: 'parameter.view',
            },
            component: ParametersPage
          },
          {
            path: 'dynamic-queries',
            canActivate: [PermissionGuard],
            data: {
              permission: 'dynamic_query.view',
            },
            component: DynamicQueriesPage
          },
          {
            path: 'dashboards',
            canActivate: [PermissionGuard],
            component: DashboardsPage,
            data: {
              permission: 'dashboard.view',
            }
          },
          {
            path: 'dashboard/:key',
            component: DashboardViewPage
          },
          {
            path: 'dashboard-templates',
            component: DashboardTemplatesPage
          },
          {
            path: 'logs',
            canActivate: [PermissionGuard],
            data: {
              permission: 'log.view',
            },
            component: LogsSystemPage
          }
        ],
      }
    ]
  },
  // {
  //   path: '**',
  //   component: NotFoundComponent,
  // },
];
