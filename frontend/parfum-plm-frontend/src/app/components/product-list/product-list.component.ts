import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-product-list',
  standalone: true, // <-- Indiquer que le composant est standalone
  imports: [CommonModule, RouterModule], 
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.css']
})
export class ProductListComponent {
  products = [
    { id: 1, name: 'Parfum Lavande' },
    { id: 2, name: 'Parfum Rose' },
    { id: 3, name: 'Parfum Musc' }
  ];
}
