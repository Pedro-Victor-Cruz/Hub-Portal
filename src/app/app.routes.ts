import {Routes} from '@angular/router';
import {HomePage} from './pages/home/home.page';
import {ProfilePage} from './pages/profile/profile.page';
import {LoginPage} from './pages/auth/login/login.page';
import {RegisterPage} from './pages/auth/register/register.page';
import {AuthGuard} from './security/auth.guard';
import {NavbarComponent} from './layout/navbar/navbar.component';
import {BlogsPage} from './pages/blogs/blogs.page';
import {ManageBlogPage} from './pages/blogs/manage/manage-blog.page';

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
        path: 'blogs',
        children: [
          {
            path: "",
            component: BlogsPage
          },
          {
            path: "gerenciar",
            component: ManageBlogPage
          },
          {
            path: "gerenciar/:id",
            component: ManageBlogPage
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
