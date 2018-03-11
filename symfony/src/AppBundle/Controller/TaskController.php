<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
use BackendBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class TaskController extends Controller
{
    public function newAction(Request $request, $id=null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get("authorization",null);
        $auth_check = $jwt_auth->checkToken($token);

        if($auth_check){
            $identity = $jwt_auth->checkToken($token,true);
            $json = $request->get("json",null);

            if($json != null){
                $params = json_decode($json);

                $created_at = new \datetime("now");
                $updated_at = new \datetime("now");

                $user_id = ($identity->sub !=null) ? $identity->sub : null;
                $title  = (isset($params->title)) ? $params->title : null;
                $description  = (isset($params->description)) ? $params->description : null;
                $status  = (isset($params->status)) ? $params->status : null;

                if($user_id != null && $title != null){
                    $em = $this->getDoctrine()->getManager();
                    $user = $em->getRepository("BackendBundle:User")->findOneBy(array("id"=>$user_id));

                    if($id == null){
                        $task = new Task();
                        $task->setUser($user);
                        $task->setTitle($title);
                        $task->setDescription($description);
                        $task->setStatus($status);
                        $task->setCreatedAt($created_at);
                        $task->setUpdatedAt($updated_at);

                        $em->persist($task);
                        $em->flush();

                        $data= array(
                            "status" => "success",
                            "code" => 200,
                            "data" => $task,
                        );
                    }else{
                        $task = $em->getRepository('BackendBundle:Task')->findOneBy(array("id" => $id));
                        if(isset($identity->sub) && $identity->sub == $task->getUser()->getId()){
                            $task->setUser($user);
                            $task->setTitle($title);
                            $task->setDescription($description);
                            $task->setStatus($status);
                            $task->setUpdatedAt($updated_at);

                            $em->persist($task);
                            $em->flush();

                            $data= array(
                                "status" => "success",
                                "code" => 200,
                                "data" => $task,
                            );


                        }else{
                            $data= array(
                                "status" => "error",
                                "code" => 400,
                                "msg" => "Task updated erro, you not owner",
                            );
                        }
                    }


                }else{
                    $data= array(
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Task not created, params missed!!!"
                    );
                }

            }else{
                $data= array(
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Task not created, params failed!!!"
                );
            }

        }else{
            $data= array(
                "status" => "error",
                "code" => 400,
                "msg" => "Authorization not valid"
            );
        }

        return $helpers->json($data);
    }

    public function tasksAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth =$this->get(JwtAuth::class);

        $token = $request->get('authorization',null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            $identity = $jwt_auth->checkToken($token,true);

            $em = $this->getDoctrine()->getManager();

            $dql = "SELECT t FROM BackendBundle:Task t WHERE t.user = {$identity->sub} 
            ORDER BY t.status DESC";
            $query = $em->createQuery($dql);


            $page = $request->query->getInt('page',1);
            $paginator = $this->get('knp_paginator');
            $items_per_page = 10;

            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total_items_count = $pagination->getTotalItemCount();

            $data= array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total_items_count,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total_items_count/ $items_per_page),
                'data' => $pagination
            );

        }else{
            $data= array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Authorization not valid!!!'

            );
        }

        return $helpers->json($data);
    }

    public function taskAction(Request $request, $id=null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization',null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $task = $em->getRepository('BackendBundle:Task')->findOneBy(array('id'=>$id));

            if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
                $data= array(
                    'status' => 'success',
                    'code' => 200,
                    'data' => $task

                );
            }else{
                $data= array(
                    'status' => 'error',
                    'code' => 404,
                    'msg' => 'Task not found'
                );
            }
        }else{
            $data= array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Authorization not valid!!!'

            );
        }

        return $helpers->json($data);

    }

    public function searchAction(Request $request, $search = null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get("authorization", null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            $identity = $jwt_auth->checkToken($token,true);
            $em = $this->getDoctrine()->getManager();

            //Filtro
            $filter = $request->get('filter',null);

            if(empty($filter)){
                $filter= null;
            }elseif($filter==1){
                $filter= 'new';
            }elseif($filter == 2){
                $filter = 'todo';
            }else{
                $filter = 'finished';
            }

            //Orden
            $order = $request->get('order', null);

            if(empty($order) || $order==2){
                $order = 'DESC';
            }else{
                $order = 'ASC';
            }

            //Busqueda
            if($search != null){
                $dql = "SELECT t FROM BackendBundle:Task t "
                    . "WHERE t.user = $identity->sub AND "
                    . "(t.title LIKE :search OR t.description LIKE :search)";

            }else{
                $dql = "SELECT t FROM BackendBundle:Task t "
                    ."WHERE t.user = $identity->sub";
            }

            //set filter
            if($filter != null){
                $dql .= " AND t.status = :filter";
            }

            //set order
            $dql .= " ORDER BY t.id $order";

            //create query
            $query = $em->createQuery($dql);

            //set parameter filter
            if($filter!=null){
                $query->setParameter('filter',"$filter");
            }


            //set parameter search
            if(!empty($search)){
                $query->setParameter('search',"%$search%");
            }

            $tasks = $query->getResult();
            $data = array(
                'status'=> 'success',
                'code' => 200,
                'data' => $tasks
            );

        }else{
            $data= array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Authorization not valid!!!'
            );
        }

        return $helpers->json($data);
    }

    public function removeAction(Request $request, $id=null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization',null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $task = $em->getRepository('BackendBundle:Task')->findOneBy(array('id'=>$id));

            if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
                //Borrar objeto y registro de la tabla
                $em->remove($task);
                $em->flush();

                $data= array(
                    'status' => 'success',
                    'code' => 200,
                    'data' => $task

                );
            }else{
                $data= array(
                    'status' => 'error',
                    'code' => 404,
                    'msg' => 'Task not found'
                );
            }
        }else{
            $data= array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Authorization not valid!!!'

            );
        }

        return $helpers->json($data);
    }
    
}