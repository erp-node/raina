<?php
error_reporting(0);
class Settings extends CI_Controller {

       function __construct() {
        parent::__construct();
        $is_login = $this->session->userdata('logged_in')['is_logged_in'];
        if($is_login == 0){
           redirect('Authentication/signout');
        }
		
		$this->load->model('ContactModel');
		$this->load->model('CustomerProjectModel');
		$this->load->model('DailyReportModel');
		
		
					
    }

	public function index(){
    //$data['settings_list']=$this->Generic_model->getSingleRecord('bf_general_settings');	
	
	
	$this->load->view('templates/header');
    $this->load->view('general_settings',$data);
    $this->load->view('templates/footer');
    }
	
	public function add_settings(){
		
		$param=$this->input->post();
		if(count($param)>0){
		$data['min_cart_value']	=$this->input->post('min_cart_value');
		//$rows=$this->Generic_model->getNumberOfRecords('bf_general_settings',array('min_cart_value'=>$data['min_cart_value']));
		if($rows >0){
			
		//$this->Generic_model->updateData('bf_general_settings',$data,array('min_cart_value'=>$data['min_cart_value']));	
		}else{
			
			//$this->Generic_model->insertData('bf_general_settings',$data);
			
		}
		
		redirect('settings');
			
		}
		
		
		
	}
	
	public function edit_settings(){
		
	$param=$this->input->post();
		if(count($param)>0){
		$data['min_cart_value']	=$this->input->post('min_cart_value');
		$settings_id=$this->input->post('settings_id');
		//$rows=$this->Generic_model->getNumberOfRecords('bf_general_settings',array('settings_id'=>$settings_id));
		if($rows >0){
			
		//$this->Generic_model->updateData('bf_general_settings',$data,array('settings_id'=>$settings_id));	
		}else{
			
			//$this->Generic_model->insertData('bf_general_settings',$data);
			
		}
		
		redirect('settings');
			
		}
	
	
		
	}

		
}