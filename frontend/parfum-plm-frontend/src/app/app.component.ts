import { Component } from '@angular/core';
import { RouterModule } from '@angular/router'; 

@Component({
  selector: 'app-root',
  standalone: true, // Standalone component
  imports: [RouterModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'PLM Parfumerie';

  loadParfumData(parfumKey: string) {
    console.log(`Chargement des données pour ${parfumKey}`);
  }
}
