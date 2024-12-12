import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { RouterModule } from '@angular/router';
import { AppComponent } from './app.component';
import { ProductListComponent } from './components/product-list/product-list.component';
import { ProductDetailsComponent } from './components/product-details/product-details.component';
import { CostSimulationComponent } from './components/cost-simulation/cost-simulation.component';
import { LoginComponent } from './components/login/login.component';
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { CollaborationComponent } from './components/collaboration/collaboration.component';

@NgModule({
  imports: [
    BrowserModule,
    RouterModule.forRoot([
      { path: '', component: ProductListComponent },
      { path: 'product-details/:id', component: ProductDetailsComponent },
      { path: 'cost-simulation', component: CostSimulationComponent },
      { path: 'collaboration', component: CollaborationComponent },
      { path: 'login', component: LoginComponent }
    ]),
    AppComponent, // Standalone component
    ProductListComponent, // Standalone component
    ProductDetailsComponent, // Standalone component
    CostSimulationComponent, // Standalone component
    CollaborationComponent, // Standalone component
    LoginComponent, // Standalone component
    SidebarComponent // Standalone component
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
