<?php
error_reporting(0);
class Authentication extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->library('PHPMailer');
          $this->load->library('SMTP');
	$this->load->library('email', array('mailtype'=>'html'));		 
	$this->form_validation->set_error_delimiters('<div class="error">', '</div>');	

	}

	public function index(){
	
		if(!(is_logged_in())){
			$this->load->view('login');
		}else{
			
			$profile_id=$this->session->userdata('logged_in')['role_id'];
			//$user_type = $this->session->userdata('logged_in')['user_type'];	

				if($profile_id != ""){
					redirect("Admin");
				}else{
					redirect('Authentication/signout');
				}
	
		}
		
		//$this->load->view('login');
	}

	public function signin(){
		$password = base64_encode($this->input->post('password'));		
		
		$existed_or_not = $this->db->query("select * from users where ( user_email='".$this->input->post('email')."'  
			and password = '".$password."') and status ='1' ")->row();	
		 
			if($existed_or_not->user_id!=''){
				$login_list = $this->db->query("select * from users a inner join  roles c on (a.role_id = c.role_id) where a.user_id =".$existed_or_not->user_id)->result();
					$sess_array = array();
					foreach($login_list as $row){
						
						$sess_array = array(
						'user_id' => $row->user_id,
						'user_name' =>$row->user_name,
						'is_logged_in' =>true,						
						'user_email' =>$row->user_email,						
						'role_id' =>$row->role_id
						);					
						$this->session->set_userdata('logged_in',$sess_array);
						$this->session->set_flashdata('success', 'Successfully Logged In');
						$role_id=$this->session->userdata('logged_in')['role_id'];
						
						if($role_id != ""){
							redirect("Admin");
						}else{
							redirect('Authentication/signout');
						}
					}
				
		}else{
			 $this->session->set_flashdata('error', 'login credentials is invalid');			  
			  redirect("Authentication");
		}
	}

	public function signout()
	{
		
		$this->session->sess_destroy();
		redirect("Authentication"); 	
	}
	 function user_profile() {
        $user_id=$this->session->userdata('logged_in')['user_id'];
        $data['user_details'] = $this->db->query("select *,a.status from users a inner join roles b on (a.role_id = b.role_id) where user_id =".$user_id)->row();
   
     
        $this->load->view('templates/header');
        $this->load->view('user_profile',$data);
        $this->load->view('templates/footer');
    }
    public function change_profile(){
    	$param = $this->input->post();
    	if(count($param) >0){
    		$user_id=$this->session->userdata('logged_in')['user_id'];
    		
            $ok = $this->Generic_model->updateData('users', $param, array('user_id' => $user_id));
            if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('authentication/user_profile');
            }else{
                redirect("authentication/user_profile");
            }
    	}
    }

    public function change_password(){
    	$new_password = $this->input->post("new_password");
    	$confirm_password = $this->input->post("confirm_password");
    	$employee_id=$this->input->post('user_id');

    	/*print_r($this->input->post());
    	exit;*/
    	if($new_password == $confirm_password){

    		if($employee_id==null || $employee_id==""){
    			$user_id=$this->session->userdata('logged_in')['user_id'];
    		}else{
    			$user_id=$employee_id;
    		}
    		
    		$param['password'] = base64_encode($confirm_password);
    		
    		$ok = $this->Generic_model->updateData('users', $param, array('user_id' => $user_id));
    		 if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('authentication/signout');
            }else{
                redirect("authentication/signout");
            }
    	}else{
    		redirect('authentication/signout');
    	}
    }
	
	public function profile_edit(){
		$this->load->library('upload');
            $config = array();
            $config['upload_path'] = './images/profile_image'; //give the path to upload the image in folder
            $config['allowed_types'] = 'jpg|JPG|png|PNG|csv';
            //$csvMimes = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv');
            $_FILES['file']['name'] = $_FILES['file']['name'];
            $_FILES['file']['type'] = $_FILES['file']['type'];
            $_FILES['file']['tmp_name'] = $_FILES['file']['tmp_name'];
            $_FILES['file']['error'] = $_FILES['file']['error'];
            $_FILES['file']['size'] = $_FILES['file']['size'];
            $this->upload->initialize($config);
            $this->upload->do_upload('file');
            $fname = $this->upload->data();
            $fileName = $fname['file_name'];
            $f_image = $fileName;
            $user_id=$this->session->userdata('logged_in')['id'];
         
    		$param['modified_by'] = $user_id;
            $param['modified_datetime'] = date("Y-m-d H:i:s");
    		$ok = $this->Generic_model->updateData('users', $param, array('user_id' => $user_id));
    		 if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('authentication/user_profile');
            }else{
               redirect("authentication/user_profile");
               
            }


	}

	public function forgot_password(){
		if($this->input->post('submit')){
			$email_id=$this->input->post('email');
			$user_result = $this->db->query("select * from users where email ='".$email_id."'")->row();

			if(count($user_result)>0){
				$email  = $user_result->email;

				$from = "admin@raina.com";
                $to = $email;  //$to      = $dept_email_id;
                $subject = "Forgot Password";
                $data['email'] = $user_result->email;
                $data['name'] = $user_result->name;
                $data['user_id'] = $user_result->user_id;
                //$message = $message;
                $ok = $this->mail_send->Authentication_send_forgot_password($from, $to, $subject, '', '',$data);
             
              if($ok ==1){
              	 $this->session->set_flashdata('success', 'Successfully Sent message');
              	redirect("authentication");
              }else{
              	 $this->session->set_flashdata('error', 'Check you Email and Try Again');
              	redirect("authentication");
              }
              
			}else{
				 $this->session->set_flashdata('error', 'Check you Email and Try Again');
				redirect("authentication");
			}
		}else{
				$this->session->set_flashdata('error', 'Please Enter Valid Details');
				$this->load->view('authentication');
			}

	}
	public function reset_password($id){
		$data['id']=$id;
		$this->load->view('reset_password',$data);
	}



	
	

  

}