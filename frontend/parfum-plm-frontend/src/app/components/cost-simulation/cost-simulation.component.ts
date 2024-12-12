import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms'; 
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-cost-simulation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './cost-simulation.component.html',
  styleUrls: ['./cost-simulation.component.css']
})
export class CostSimulationComponent {
  ingredients = [
    { name: 'Essence de Lavande', cost: 3.5, quantity: 0 },
    { name: 'Extrait de Rose', cost: 5, quantity: 0 },
    { name: 'Musc Blanc', cost: 4.2, quantity: 0 }
  ];
  totalCost: number = 0;

  updateTotal() {
    this.totalCost = this.ingredients.reduce((acc, item) => acc + item.cost * item.quantity, 0);
  }
}
