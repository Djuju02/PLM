import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms'; // <-- Import de FormsModule
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-collaboration',
  standalone: true,
  imports: [CommonModule, FormsModule], // <-- Ajouter FormsModule ici
  templateUrl: './collaboration.component.html',
  styleUrls: ['./collaboration.component.css']
})
export class CollaborationComponent {
  comments = [
    { user: 'Marie', message: 'Je propose d\'augmenter la concentration de lavande.' },
    { user: 'Paul', message: 'Bonne idée, cela pourrait mieux correspondre aux préférences du marché.' }
  ];
  newComment = { user: '', message: '' };

  addComment() {
    this.comments.push({ ...this.newComment });
    this.newComment = { user: '', message: '' };
  }
}
