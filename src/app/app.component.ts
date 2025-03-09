import {Component, OnInit} from '@angular/core';
import { RouterOutlet } from '@angular/router';
import {ToastComponent} from './components/toast/toast.component';
import {ConfirmationModalComponent} from './components/confirmation-modal/confirmation-modal.component';
import {AuthService} from './security/auth.service';

@Component({
  selector: 'app-root',
  imports: [
    RouterOutlet,
    ToastComponent,
    ConfirmationModalComponent
  ],
  template: `
    <router-outlet></router-outlet>
    <ub-toast></ub-toast>
    <ub-confirmation-modal></ub-confirmation-modal>
  `,
  standalone: true,
})
export class AppComponent implements OnInit {

  constructor(
    private auth: AuthService
  ) {
  }

  ngOnInit(): void {
    if (this.auth.isAuthenticated()) {
      this.auth.refreshUserData();
    }
  }

}
