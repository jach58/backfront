<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;

class UserController extends Controller
{
    public function newAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);

        $json = $request->get("json", null);

        $params = json_decode($json);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'User not created !!'
        );

        if($json != null){
            $createdAt = new  \datetime("now");
            $role = "user";
            $email = isset($params->email) ? $params->email : null;
            $name = isset($params->name) ? $params->name : null;
            $surname = isset($params->surname) ? $params->surname : null;
            $password = isset($params->password) ? $params->password : null;

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "This email is not valid!!";
            $validate_email = $this->get("validator")->validate($email,$emailConstraint);

            if($email != null && count($validate_email)== 0 && $password != null && $name != null && $surname != null){
                $user = new User();
                $user->setCreatedAt($createdAt);
                $user->setRole($role);
                $user->setEmail($email);
                $user->setSurname($surname);
                $user->setName($name);

                //cifrar contraseña
                $pwd = hash('sha256',$password);
                $user->setPassword($pwd);

                $em = $this->getDoctrine()->getManager();
                $isset_user = $em->getRepository('BackendBundle:User')->findOneBy(array(
                    "email" => $email
                ));

                if(count($isset_user) == 0) {
                    $em->persist($user);
                    $em->flush(); // Guardamos los datos en la base de datos

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => ' New user created !!',
                        'user' => $user
                    );
                }else{
                    $data = array(
                        'status' => 'success',
                        'code' => 400,
                        'msg' => 'User created, duplicated !!'
                    );
                }
            }
        }

        return $helpers->json($data);
    }

    public function editAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get("authorization",null);
        $auth_check = $jwt_auth->checkToken($token);

        if($auth_check){

            //Entity Manager
            $em = $this->getDoctrine()->getManager();

            //Conseguimos el dato del usuario identificado
            $identity = $jwt_auth->checkToken($token,true);

            //Conseguir el objeto a actualizar
            $user = $em->getRepository("BackendBundle:User")->findOneBy(array(
                'id' => $identity->sub
            ));

            //recoger datos por post
            $json = $request->get("json", null);
            $params = json_decode($json);

            //array de error por defecto
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'User not updated !!'
            );

            if($json != null){
                //$createdAt = new  \datetime("now");
                $role = "user";
                $email = isset($params->email) ? $params->email : null;
                $name = isset($params->name) ? $params->name : null;
                $surname = isset($params->surname) ? $params->surname : null;
                $password = isset($params->password) ? $params->password : null;

                $emailConstraint = new Assert\Email();
                $emailConstraint->message = "This email is not valid!!";
                $validate_email = $this->get("validator")->validate($email,$emailConstraint);

                if($email != null && count($validate_email)== 0 && $name != null && $surname != null){
                    //$user->setCreatedAt($createdAt);
                    $user->setRole($role);
                    $user->setEmail($email);
                    $user->setSurname($surname);
                    $user->setName($name);

                    if($password != null){
                        //cifrar contraseña
                        $pwd = hash('sha256',$password);
                        $user->setPassword($pwd);
                    }



                    $isset_user = $em->getRepository('BackendBundle:User')->findOneBy(array(
                        "email" => $email
                    ));

                    if(count($isset_user) == 0 || $identity->email == $email) {
                        $em->persist($user);
                        $em->flush(); // Guardamos los datos en la base de datos

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'msg' => 'User updated !!',
                            'user' => $user
                        );
                    }else{
                        $data = array(
                            'status' => 'error',
                            'code' => 400,
                            'msg' => 'User not updated, duplicated !!'
                        );
                    }
                }
            }

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Authorization not valid !!'
            );
        }

        return $helpers->json($data);

    }
}