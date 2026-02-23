<?php 
   use \Model\Employee; 
   class Controller_Employee extends Controller { 
      public function action_home() { 
         
         // ホームページの機能
         echo "piiiiiiii"; 
      } 
   
      public function action_index(){
         // echo "This is the method of employee controller";
         echo "test test";
      }

      public function action_show(){
         // $data = array();
         // $data['name'] = 'jon';
         // $data['job'] = 'Degigner';

         // return View::forge('employee/show', $data);

         $view = \View::forge('employee/show');
         $view -> set('name', 'jon', true);
         $view -> set('job', '<em>Designer</em>', false);
         return $view;
      }
      
      public function before(){
         echo "This message comes from <em>before()</em> method</br>";
      }

      public function after($response){
         if(!$response instanceof Response){
            $response = \Response::forge($response, $this->response_status);
         }
         return $response;
      }

      public function action_request(){
      $params = Request::active()->params();
      echo var_dump($params);
      }
      
      public function action_response(){
         $body = "Hi, FuelPHP";
         $headers = array(
            'Content-Type' => 'text/html',
         );
         $response = new Response($body, 200, $headers);
         return $response;
      }

      public function action_nestedview(){
         $data = array();
         $data['title'] = 'Home';
         $data['name'] = 'jon';
         $data['job'] = 'Designer';

         $views = array();
         $views['head'] = View::forge('head', $data)->render();
         $views['content'] = View::forge('employee/show', $data)->render();

         return View::forge('layout', $views, false)->render();

      }

      public function action_welcome() { 
      return Presenter::forge('employee/hello'); 
      }

      // public function action_index(){
      //    $employees = Employee::fetchAll();
      // }

   }


   ?>

   