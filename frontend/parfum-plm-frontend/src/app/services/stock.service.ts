import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class StockService {
  private apiUrl = '/api/stocks'; // Endpoint du backend

  constructor(private http: HttpClient) {}

  getStock(): Observable<any> {
    return this.http.get(`${this.apiUrl}`);
  }

  updateStock(itemId: string, quantity: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/${itemId}`, { quantity });
  }
}
