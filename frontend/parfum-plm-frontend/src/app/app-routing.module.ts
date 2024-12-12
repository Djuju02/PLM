import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { ProductListComponent } from './components/product-list/product-list.component';
import { ProductDetailsComponent } from './components/product-details/product-details.component';
import { CostSimulationComponent } from './components/cost-simulation/cost-simulation.component';
import { CollaborationComponent } from './components/collaboration/collaboration.component';
import { LoginComponent } from './components/login/login.component';

const routes: Routes = [
  { path: '', component: ProductListComponent }, 
  { path: 'product-details/:id', component: ProductDetailsComponent },
  { path: 'cost-simulation', component: CostSimulationComponent },
  { path: 'collaboration', component: CollaborationComponent },
  { path: 'login', component: LoginComponent }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
