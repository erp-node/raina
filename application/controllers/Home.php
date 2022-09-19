<?php
error_reporting(0);
class Home extends CI_Controller {

       public function index(){
		   
		   
		   $this->load->view('home');
	   }
	   
	   public function about(){
		   
		$this->load->view('about_us');   
		   
	   }
	   
	   public function product(){
		   
		$this->load->view('products_view');   
		   
	   }
	   
	   public function contact_us(){
		$this->load->view('contact_us');   
		   
	   }
	   
	   public function privacy_policy(){
		$this->load->view('privacy_policy');    
		   
	   }

		
}