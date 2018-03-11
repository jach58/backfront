import { Component, OnInit } from '@angular/core';
import {Router, ActivatedRoute, Params} from '@angular/router';
import { User } from '../models/user';
import { UserService } from '../services/user.service';

@Component({
    selector: 'register',
    templateUrl: '../views/register.component.html',
    providers: [UserService]
})

export class RegisterComponent implements OnInit{
    public title: string;
    public user : User;
    public status


    constructor(private _route: ActivatedRoute, private _router: Router,
      private _userService: UserService){
        this.title = 'Registro';
        this.user = new User(1,'usuario','','','','');
    }

    ngOnInit(){
        console.log('El componente register ha sido cargado!!');
    }

    onSubmit(){
      this._userService.register(this.user).subscribe(
        response =>{
          this.status = response.status
          if(response.status != "success"){
              this.status = 'error';
          }else{
            this.user = new User(1,'usuario','','','','');
          }
        },
        error =>{
          console.log(<any> error);
        }
      );
    }


}