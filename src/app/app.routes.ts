import {Routes} from '@angular/router';
import {HomePage} from './pages/home/home.page';
import {ProfilePage} from './pages/profile/profile.page';
import {LoginPage} from './pages/auth/login/login.page';
import {RegisterPage} from './pages/auth/register/register.page';
import {AuthGuard} from './security/auth.guard';
import {NavbarComponent} from './layout/navbar/navbar.component';
import {UsersPage} from './pages/users/users-page.component';
import {ManageUserPage} from './pages/users/manage/manage-user.page';
import {PermissionGuard} from './security/permission.guard';
import {CompaniesPage} from './pages/companies/companies.page';
import {ManageCompanyPage} from './pages/companies/manage/manage-company.page';
import {PermissionsPage} from './pages/permissions/permissions.page';
import {PermissionGroupsPage} from './pages/permission-groups/permission-groups.page';
import {ManagePermissionGroupPage} from './pages/permission-groups/manage/manage-permission-group.page';
import {ParametersPage} from './pages/parameters/parameters.page';

export const routes: Routes = [
  {
    path: 'auth',
    children: [
      {
        path: 'logar',
        component: LoginPage,
      },
      {
        path: 'registrar',
        component: RegisterPage,
      },
    ],
  },
  {
    path: '',
    redirectTo: '/home',
    pathMatch: 'full',
  },
  {
    path: '',
    canActivate: [AuthGuard],
    component: NavbarComponent,
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
        path: 'companies',
        canActivate: [PermissionGuard],
        data: {
          permission: 'company.view',
        },
        children: [
          {
            path: "",
            component: CompaniesPage
          },
          {
            path: "manage",
            component: ManageCompanyPage,
            data: {
              permission: 'company.create',
            }
          },
          {
            path: "manage/:id",
            component: ManageCompanyPage,
            data: {
              permission: 'company.edit',
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
        path: 'groups',
        canActivate: [PermissionGuard],
        data: {
          permission: 'permission_group.view',
        },
        children: [
          {
            path: "",
            component: PermissionGroupsPage
          },
          {
            path: "manage",
            component: ManagePermissionGroupPage,
            data: {
              permission: 'permission_group.create',
            }
          },
          {
            path: "manage/:id",
            component: ManagePermissionGroupPage,
            data: {
              permission: 'permission_group.edit',
            }
          }
        ]
      },
      {
        path: 'parameters',
        canActivate: [PermissionGuard],
        data: {
          permission: 'parameter.view',
        },
        children: [
          {
            path: '',
            component: ParametersPage
          }
        ]
      }
    ],
  },
  // {
  //   path: '**',
  //   component: NotFoundComponent,
  // },
];
