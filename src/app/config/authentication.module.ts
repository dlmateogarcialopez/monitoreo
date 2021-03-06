import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LoginComponent } from '../views/login/login.component';
import { ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { RouterModule } from '@angular/router';
// import { MatButtonModule, MatFormFieldModule, MatInputModule } from '@angular/material';
import { AuthGuard } from './auth.guard';
import { AuthenticationService } from './authentication.service';
// import { RandomGuard } from './guards/random.guard';
import { TokenInterceptor } from './token.interceptor';

@NgModule({
  declarations: [LoginComponent],
  providers: [
    AuthGuard,
    AuthenticationService,
    // RandomGuard,
    {
      provide: HTTP_INTERCEPTORS,
      useClass: TokenInterceptor,
      multi: true
    }
  ],
  imports: [
    CommonModule,
    RouterModule,
    HttpClientModule,
    ReactiveFormsModule,
    // MatButtonModule,
    // MatFormFieldModule,
    // MatInputModule
  ]
})
export class AuthenticationModule { }