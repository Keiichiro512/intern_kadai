<?php  
   class Presenter_Employee_Hello extends Presenter { 
      public function view() { 
         $this->name = Request::active()->param('name', 'World'); 
      } 
   }
?>