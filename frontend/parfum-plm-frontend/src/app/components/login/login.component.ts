import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  standalone: true,
  imports: [CommonModule, FormsModule]
})
export class LoginComponent {
  username = '';
  password = '';
  message = '';

  constructor(private authService: AuthService, private router: Router) {}

  login() {
    this.authService.login(this.username, this.password).subscribe({
      next: () => this.router.navigate(['/products']),
      error: (err) => (this.message = err.error.message || 'Erreur de connexion'),
    });
  }

  register() {
    this.authService.register(this.username, this.password).subscribe({
      next: () => (this.message = 'Compte créé avec succès !'),
      error: (err) => (this.message = err.error.message || 'Erreur lors de la création du compte'),
    });
  }
}
