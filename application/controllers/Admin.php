<?php error_reporting(0);
ini_set('memory_limit','1024M');
ini_set('max_execution_time', 30000);
class Admin extends CI_Controller {
	
	 function __construct() {
        parent::__construct();
		
        $is_login = $this->session->userdata('logged_in')['is_logged_in'];
        if($is_login == 0){
           redirect('Authentication/signout');
        }
					
    }


	
	function index() {
        $this->load->view('templates/header');
        $this->load->view('dashboard',$data);
        $this->load->view('templates/footer');
    }
	
	function Dashboard() {
        $this->load->view('templates/header');
        $this->load->view('dashboard',$data);
        $this->load->view('templates/footer');
    }
	function vehical() {
        $this->load->view('templates/header');
        $this->load->view('vehical',$data);
        $this->load->view('templates/footer');
    }
	
	function vehical_create() {
        $this->load->view('templates/header');
        $this->load->view('vehical_create',$data);
        $this->load->view('templates/footer');
    }
	function damages() {
        $this->load->view('templates/header');
        $this->load->view('damages',$data);
        $this->load->view('templates/footer');
    }
	function damages_create() {
        $this->load->view('templates/header');
        $this->load->view('damages_create',$data);
        $this->load->view('templates/footer');
    }
	function hirers() {
        $this->load->view('templates/header');
        $this->load->view('hirers',$data);
        $this->load->view('templates/footer');
    }
	function hirers_create() {
        $this->load->view('templates/header');
        $this->load->view('hirers_create',$data);
        $this->load->view('templates/footer');
    }
	function approve_hirer() {
        $this->load->view('templates/header');
        $this->load->view('approve_hirer',$data);
        $this->load->view('templates/footer');
    }
	
	function active_hirer() {
        $this->load->view('templates/header');
        $this->load->view('active_hirer',$data);
        $this->load->view('templates/footer');
    }
	function remove_hirer() {
        $this->load->view('templates/header');
        $this->load->view('remove_hirer',$data);
        $this->load->view('templates/footer');
    }
	
	function doOutputListids($TreeArray,&$in_arr = array() )
  {
    foreach($TreeArray as $element) {
        if(is_object($element) || is_array($element)) { // <-- else if statement simplified
           $in_arr[] = $element['role_id']; 
            $this->doOutputListids($element['children'],$in_arr);
        } else {
            // XML is being passed, need to strip it
           // $element = strip_tags($element); 

            // Trim whitespace
            
        }
    }
    return $in_arr;
  }

          function dpusers_ids($final_roles){
          $users_list = $this->db->query("select * from cs_users  WHERE  role IN (".$final_roles.") and status !='InActive'")->result();
          $user_id_val;
          foreach($users_list as $user_val){
          $user_id_val[] = $user_val->user_id;
          }
          $users_id_final = implode(",", $user_id_val);
          return $users_id_final;

          }
	function user_list() {
        $data['user_list']  = $this->db->query("select *,a.status from users a inner join roles b on (a.role_id = b.role_id) ")->result();
		
        $this->load->view('templates/header');
        $this->load->view('user_list',$data);
        $this->load->view('templates/footer');
    }
	
	  function user_insert() {
      
        $param =$this->input->post();
		
		
        if(count($param)>0){
           

            $user_id=$this->session->userdata('logged_in')['user_id'];
          

          
			
           
            $param['first_name']= $this->input->post('first_name');
            $param['last_name']= $this->input->post('last_name');
            $param['status']= $this->input->post('status');
            $param['email']= $this->input->post('email');
            $param['mobile']= $this->input->post('mobile');
            $param['role_id']= $this->input->post('role_id');        
            $param['password'] = base64_encode($this->input->post("password"));
          
			
		   $email=trim($param['email']);

			$is_mail_exists=$this->Generic_model->getNumberOfRecords('users',array('email'=>$email));
			if($is_mail_exists <=0){
            $ok = $this->Generic_model->insertDataReturnId("users",$param);
          
			if($ok){
         $this->session->set_flashdata('success', 'User Added Successfully');       
        redirect("admin/user_list");    

            }
			
		}else{
		$this->session->set_flashdata('error', 'Email Already Exists');
        redirect("admin/user_list");	
			
		}

        }else{
           
            $data['role_list'] = $this->db->query("select * from roles")->result();
            
			
            
            $this->load->view('templates/header');
            $this->load->view('user_insert',$data);
            $this->load->view('templates/footer');
        }
      
    }
	
	
	 function user_edit($id) {
    
        $param =  $this->input->post();
       // if(accessrole('Users',P_UPDATE)){  
        if(count($param) >0){
            $param['first_name']= $this->input->post('first_name');
            $param['last_name']= $this->input->post('last_name');
            $param['status']= $this->input->post('status');
            $param['email']= $this->input->post('email');
            $param['mobile']= $this->input->post('mobile');
            $param['role_id']= $this->input->post('role_id');        
            $param['password'] = base64_encode($this->input->post("password"));
           
            $ok = $this->Generic_model->updateData('users', $param, array('user_id' => $id));
            if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('admin/user_list');
            }else{
        $this->session->set_flashdata('error', 'Error Please Check the file format');
        redirect("admin/user_list");
            }
        }else{
                $data['users_list'] = $this->db->query("select *,a.status from users a inner join roles b on (a.role_id = b.role_id)  where a.user_id = ".$id)->row();
                $role_list = $this->db->query("select * from roles where role_id =".$data['users_list']->role_id)->row();

                $data['role_list'] = $this->db->query("select * from roles")->result();

                $this->load->view('templates/header');
                $this->load->view('user_insert',$data);
                $this->load->view('templates/footer');
      }

  //}else{
  // redirect("admin/permissions_error");}
    
        
   }
   function user_view($id) {
     $data['user_list'] = $this->db->query("select *,a.status from users a inner join roles b on (a.role_id = b.role_id) 
      where a.user_id = ".$id)->row();
	 
	
        $this->load->view('templates/header');
        $this->load->view('user_view',$data);
        $this->load->view('templates/footer');
    }
    function user_delete($id){
      //if(accessrole('Users',P_DELETE)){
            $user_id=$this->session->userdata('logged_in')['id'];
            $param['archieve'] ="1";
            $param['modified_by'] = $user_id;
            $param['modified_date_time'] = date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('cs_users', $param, array('user_id' => $id));
            if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('admin/user_list');
            }else{
        $this->session->set_flashdata('error', 'Error Please Check the file format');
                redirect("admin/user_list");
            }
        //}else{
         //  redirect("admin/permissions_error");
        //}
    }
	
	  public function role_list(){
      //if(accessrole('Roles', P_READ)){
            $data['role_list'] = $this->db->query("select * from roles ")->result();
        
            $this->load->view('templates/header');
            $this->load->view('role_list',$data);
            $this->load->view('templates/footer');
       //}else{
        // redirect("admin/permissions_error");
       //}
    }
	
	 public function roles_insert(){
     // if(accessrole('Roles', P_CREATE)){
            $user_id = $this->session->userdata('logged_in')['user_id'];
            $param['role'] = $this->db->query("select * from roles")->result();
            $data = $this->input->post();
            if(count($data)>0){
       
                $data['modified_by'] = $user_id;
                $data['created_by'] = $user_id;
                $data['created_date_time']=date("Y-m-d H:i:s");
                $data['modified_date_time']=date("Y-m-d H:i:s");


                $is_role_exists=$this->Generic_model->getNumberOfRecords('roles',array('role_name'=>strtolower($data['role_name']) ));

                if($is_role_exists >0){

                    $this->session->set_flashdata('error', 'Role Already Exists');
                    redirect("Admin/roles_list/".$ok."");
                }else{


                    $ok = $this->Generic_model->insertDataReturnId("roles",$data);
               redirect("Admin/role_view/".$ok."");
                }

                
                
            }else{
            $this->load->view('templates/header');
            $this->load->view('roles_insert',$param);
            $this->load->view('templates/footer');
            }
       // }else{
        //  redirect("admin/permissions_error");
        //}
            
    }
	
	  public function roles_update($id){
      //if(accessrole('Roles', P_UPDATE)){
            $user_id = $this->session->userdata('logged_in')['user_id'];
            $data['roles_list'] = $this->db->query("select * from roles where role_id =".$id)->row();
            $data['role'] = $this->db->query("select * from roles")->result();
     
            $param = $this->input->post();
            if(count($param)>0){
           
                $param['modified_by'] = $user_id;
                $param['modified_date_time  ']=date("Y-m-d H:i:s");
                $this->Generic_model->updateData('roles',$param,array('role_id'=>$id));
        
                redirect('admin/role_list');    
            }else{
       
                $this->load->view('templates/header');
                $this->load->view('roles_insert',$data);
                $this->load->view('templates/footer');
            }
      //}else{
          //redirect("admin/permissions_error");
       // }
    }
    public function roles_delete($id){
     // if(accessrole('Roles', P_DELETE)){
            $data['archieve'] = "1";
            $this->Generic_model->updateData('cs_role',$data,array('role_id'=>$id));
            $this->session->set_flashdata('suscess', 'Your data has been successfully Deleted.');
            redirect('admin/role_list');
       // }else{
         // redirect("admin/permissions_error");
        //}
    }
	 public function role_view($id){
        $data['role_list'] = $this->db->query("select * from roles where role_id =".$id)->row();
        $data['users_list'] = $this->db->query("select * from users where role_id ='".$id."'")->result();
         $data['roll_entity'] = $this->db->query("select * from role_entities")->result();
		
        $this->load->view('templates/header');
        $this->load->view('role_view',$data);
        $this->load->view('templates/footer');
    }
	 public function Profile_list(){
            $data['profile_list'] = $this->db->query("select * from cs_profile where archieve != '1'")->result();
            $this->load->view('templates/header');
            $this->load->view('Profile_list',$data);
            $this->load->view('templates/footer');
        
    }
	
	   public function Profile_view($id){
        //$data['profile_list'] = $this->db->query("select * from profile  where profile_id =".$id)->row();
         $data['user_entity'] = $this->db->query("select * from cs_user_entities")->result();
        $data['role_list'] = $this->db->query("select * from bf_profile where profile_id =".$id)->row();
        $data['role_permissions'] = $this->db->query("select * from cs_role_permissions where  profile_id =".$id)->result();
        $this->load->view('templates/header');
        $this->load->view('Profile_view',$data);
        $this->load->view('templates/footer');
    }
    public function profile_insert(){
        if(accessrole(Profiles, P_CREATE)){
            $data = $this->input->post();
            $user_id = $this->session->userdata('logged_in')['id'];
            if(count($data)>0){
                $data['created_by']=$user_id;
                $data['modified_by']=$user_id;
                $data['created_date_time']=date("Y-m-d H:i:s");
                $data['modified_date_time']=date("Y-m-d H:i:s");            
                $ok = $this->Generic_model->insertDataReturnId("cs_profile",$data);
                if($ok){
                  $this->session->set_flashdata('suscess', 'Successfully Added');
                  redirect('admin/Profile_view/'.$ok.'');
                }else{
                  $this->db->last_query();
                }
            }else{
                $this->load->view('templates/header');
                $this->load->view('profile_insert');
                $this->load->view('templates/footer');
            }
        }else{
          redirect("admin/permissions_error");
       }
    }
    public function profile_update($id){
      if(accessrole(Profiles, P_UPDATE)){
            $data['profile_list'] = $this->db->query("select * from cs_profile where profile_id =".$id)->row();
            $user_id = $this->session->userdata('logged_in')['id'];
            $param = $this->input->post();            
            if(count($param)>0){
                $param['modified_by']=$user_id;
                $param['modified_date_time']=date("Y-m-d H:i:s");
                $this->Generic_model->updateData('cs_profile',$param,array('profile_id'=>$id));
                redirect('admin/Profile_list'); 
            }
            $this->load->view('templates/header');
            $this->load->view('profile_insert',$data);
            $this->load->view('templates/footer');
        }else{
          redirect("admin/permissions_error");
        }
    }
    public function profile_delete($id){
        if(accessrole(Profiles, P_DELETE)){
            $ok = $this->Generic_model->deleteRecord('cs_profile',array('profile_id'=>$id));
            if($ok ==1){
                $this->session->set_flashdata('suscess', 'Your data has been successfully Disabled.');
                redirect('admin/Profile_list');
            }else{
                $this->session->set_flashdata('suscess', 'This Profile has been used Disabled.');
                redirect('admin/Profile_list');
            }
        }else{
          redirect("admin/permissions_error");
        }    
    }
	
	   public function profile_permision_edit(){
        //echo "<pre/>";print_r($this->input->post());exit;
        $entity_id = $this->input->post('entity_id');
        for($i=0;$i<count($entity_id);$i++){
            $data['entity_id'] = $entity_id[$i];
            $data['profile_id'] = $this->input->post('profile_id');
                if($this->input->post('p_read_'.$entity_id[$i].'') == ""){
                    $data['p_read']=0;
                }else{
                    $data['p_read'] = $this->input->post('p_read_'.$entity_id[$i].'');
                }
                if($this->input->post('p_create_'.$entity_id[$i].'') == ""){
                    $data['p_create']=0;
                }else{
                    $data['p_create'] = $this->input->post('p_create_'.$entity_id[$i].'');
                }
                if($this->input->post('p_update_'.$entity_id[$i].'') == ""){
                    $data['p_update']=0;
                }else{
                    $data['p_update'] = $this->input->post('p_update_'.$entity_id[$i].'');
                }
                if($this->input->post('p_delete_'.$entity_id[$i].'') == ""){
                    $data['p_delete']=0;
                }else{
                    $data['p_delete'] = $this->input->post('p_delete_'.$entity_id[$i].'');
                }
            $data['created_date_time']=date('Y-m-d');
            $data['modified_date_time'] =date('y-m-d');
            $role_permissions_list = $this->db->query("select * from cs_role_permissions where entity_id ='".$data['entity_id']."' and profile_id ='".$data['profile_id']."'")->row();
            if(count($role_permissions_list)>0){
                    if($this->input->post('p_read_'.$entity_id[$i].'') == ""){
                        $param['p_read']=0;
                        }else{
                        $param['p_read'] = $this->input->post('p_read_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_create_'.$entity_id[$i].'') == ""){
                        $param['p_create']=0;
                    }else{
                        $param['p_create'] = $this->input->post('p_create_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_update_'.$entity_id[$i].'') == ""){
                        $param['p_update']=0;
                    }else{
                        $param['p_update'] = $this->input->post('p_update_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_delete_'.$entity_id[$i].'') == ""){
                        $param['p_delete']=0;
                    }else{
                        $param['p_delete'] = $this->input->post('p_delete_'.$entity_id[$i].'');
                    }
                    $param['modified_date_time'] =date('y-m-d');
                $this->Generic_model->updateData('cs_role_permissions',$param,array('permission_id'=>$role_permissions_list->permission_id));
            }else{
                $this->Generic_model->insertData("cs_role_permissions",$data);
            }
            
        }
        redirect('admin/Profile_list');
        

    }



    public function role_permision_edit(){
        //echo "<pre/>";print_r($this->input->post());exit;
        $entity_id = $this->input->post('entity_id');
        for($i=0;$i<count($entity_id);$i++){
            $data['entity_id'] = $entity_id[$i];
            $data['role_id'] = $this->input->post('role_id');
                if($this->input->post('p_read_'.$entity_id[$i].'') == ""){
                    $data['p_read']=0;
                }else{
                    $data['p_read'] = $this->input->post('p_read_'.$entity_id[$i].'');
                }
                if($this->input->post('p_create_'.$entity_id[$i].'') == ""){
                    $data['p_create']=0;
                }else{
                    $data['p_create'] = $this->input->post('p_create_'.$entity_id[$i].'');
                }
                if($this->input->post('p_update_'.$entity_id[$i].'') == ""){
                    $data['p_update']=0;
                }else{
                    $data['p_update'] = $this->input->post('p_update_'.$entity_id[$i].'');
                }
                if($this->input->post('p_delete_'.$entity_id[$i].'') == ""){
                    $data['p_delete']=0;
                }else{
                    $data['p_delete'] = $this->input->post('p_delete_'.$entity_id[$i].'');
                }
            $data['created_date_time']=date('Y-m-d');
            $data['modified_date_time'] =date('y-m-d');
            $role_permissions_list = $this->db->query("select * from role_permissions where entity_id ='".$data['entity_id']."' and role_id ='".$data['role_id']."'")->row();
            if($role_permissions_list->entity_id !=''){
                    if($this->input->post('p_read_'.$entity_id[$i].'') == ""){
                        $param['p_read']=0;
                        }else{
                        $param['p_read'] = $this->input->post('p_read_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_create_'.$entity_id[$i].'') == ""){
                        $param['p_create']=0;
                    }else{
                        $param['p_create'] = $this->input->post('p_create_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_update_'.$entity_id[$i].'') == ""){
                        $param['p_update']=0;
                    }else{
                        $param['p_update'] = $this->input->post('p_update_'.$entity_id[$i].'');
                    }
                    if($this->input->post('p_delete_'.$entity_id[$i].'') == ""){
                        $param['p_delete']=0;
                    }else{
                        $param['p_delete'] = $this->input->post('p_delete_'.$entity_id[$i].'');
                    }
                    $param['modified_date_time'] =date('y-m-d');
                $this->Generic_model->updateData('role_permissions',$param,array('permission_id'=>$role_permissions_list->permission_id,'role_id'=>$data['role_id']));
            }else{
                $this->Generic_model->insertData("role_permissions",$data);
            }
            
        }
        redirect('admin/role_list');
        

    }
	
	 function emp_role_report(){
         $role_id = $_POST['role_id'];
         $role_list = $this->db->query("select * from cs_role where role_id =".$role_id)->row();
         $role_report = $role_list->role_reports_to;
         
         if($role_report == 0 ){
            echo "<option value=''>-- Select --</option><option value='0' selected>Self</option>";
         }else{
         $data["user_list"] = $this->db->query("select * from   cs_users  where Role = '".$role_report."' ")->result();
         echo "<option value=''> -- Select --</option>";
         foreach($data["user_list"] as $values){
            echo "<option value=".$values->user_id.">".$values->name."</option>";

            }
        }         
    }
	
	public function users_filters_list(){
   $users = $this->input->post("users");
   $type = $this->input->post("type");
   $url_val_img_view = base_url("assets/images/view.png");
    $url_val_img_edit = base_url("assets/images/edit.png");
    $url_val_img_delete = base_url("assets/images/delete.png");
   

   if($type == "contacts"){
      $users = $this->input->post("users");
      $type = $this->input->post("type");
      $todate = date("Y-m-d 23:59:59",strtotime($this->input->post("todate")));
      $fromdate = date("Y-m-d 00:00:00",strtotime($this->input->post("fromdate")));
       
	   
	    if($fromdate !='1970-01-01 00:00:00'  || $fromdate !='1970-01-01 23:59:59' ){
        if($fromdate =='1970-01-01 00:00:00' || $fromdate == '' || $fromdate == "null" ){
          $fromdate = date("Y-m-d 00:00:00");
        }
        if($todate =='1970-01-01 23:59:59' ||$todate == '' || $todate == "null"){
           $todate = date("Y-m-d 23:59:59");
        }
        
        $query_param = " a.created_datetime  between '".$fromdate."' and '".$todate."'";

      } 
      if($users!='' || $users != 0){
          $query_param .= " and a.created_by in (".$users.")";
      }
    

    $list_1 = $this->db->query("select *,b.name,a.created_datetime as contact_date from cs_contact  a inner join cs_users b on (a.created_by = b.user_id) where   ".$query_param." order by  contact_id DESC")->result();
    $i=1;
    echo '<table class="table table-striped table-bordered" id="profile_dtc"><thead><tr><th>S.No</th><th>Contact No</th><th>Contact Category</th><th>Contact Owner</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
     

	 foreach($list_1 as $list_val){
		 
		 $entity_id='contacts_list'; 
		 if(accessrole($entity_id,P_UPDATE)){
          $url_admin_edit="<a href='".base_url("CustomerService/contact_edit/".$list_val->contact_id)."' data-toggle='tooltip' data-placement='bottom' title='EDIT'><img src='". base_url('assets/images/edit.png')."'/></a>&nbsp";
			}
			
			
		 if(accessrole($entity_id,P_DELETE)){
			  
			  if($list_val->category=='Contractor'){
			  
			$url_admin_delete="<a onclick='return confirm(\"Are you sure you want delete?\");' href='".base_url("CustomerService/contact_delete/".$list_val->contact_id)."' data-toggle='tooltip' data-placement='bottom' title='DELETE'><img src='". base_url('assets/images/delete.png')."'/></a>"; 
			  }
			  
			  if($list_val->category=='Project Head'){
				$url_admin_delete="<a onclick='return confirm(\"Are you sure you want delete?\");' href='".base_url("CustomerService/ph_contact_delete/".$list_val->contact_id)."' data-toggle='tooltip' data-placement='bottom' title='DELETE'><img src='". base_url('assets/images/delete.png')."'/></a>";   
				  
			  }


			
		  }	
     
        
      
		
		
        $url_admin_view = base_url("CustomerService/contact_view/".$list_val->contact_id."");
		
		
        echo '<tr><td>'.$i.'</td><td><a href="'.$url_admin_view.'"> '.$list_val->contact_number.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->category.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->name.'</a></td><td><a href="'.$url_admin_view.'"> '.date('Y-m-d',strtotime($list_val->contact_date)).'</a></td><td><a href="'.$url_admin_view.'"> <img src="'.$url_val_img_view.'"></img></a>';
		
        if(accessrole($entity_id,P_UPDATE)){ 
          echo "&nbsp;&nbsp;$url_admin_edit";
         }
       if(accessrole($entity_id,P_DELETE)){ 
        echo "&nbsp;&nbsp;$url_admin_delete";
       }
         echo '&nbsp;</td></tr>';
        $i++;
      }
      
      
   }
   
   if($type == "customer_projects"){
	   $users = $this->input->post("users");
      $type = $this->input->post("type");
      $todate = date("Y-m-d 23:59:59",strtotime($this->input->post("todate")));
      $fromdate = date("Y-m-d 00:00:00",strtotime($this->input->post("fromdate")));
       
	   
	    if($fromdate !='1970-01-01 00:00:00'  || $fromdate !='1970-01-01 23:59:59' ){
        if($fromdate =='1970-01-01 00:00:00' || $fromdate == '' || $fromdate == "null" ){
          $fromdate = date("Y-m-d 00:00:00");
        }
        if($todate =='1970-01-01 23:59:59' ||$todate == '' || $todate == "null"){
           $todate = date("Y-m-d 23:59:59");
        }
        
        $query_param = " a.created_datetime  between '".$fromdate."' and '".$todate."'";

      } 
      if($users!='' || $users != 0){
          $query_param .= " and a.created_by in (".$users.")";
      }
	  
	  
	  $list_1 = $this->db->query("select *,b.name,a.created_datetime as contact_date from cs_customerproject  a inner join cs_users b on (a.created_by = b.user_id) where   ".$query_param."")->result();
	  
	  
	  
	  /*$this->db->select('*')->from('cs_customerproject as a')->join('cs_users as c','a.created_by = c.user_id')->where('a.created_by IN ('.$final_users_id.')');	*/
	  
	  
    $i=1;
    echo '<table class="table table-striped table-bordered" id="profile_dtc"><thead>
	<tr>
	<th>S.No</th>
	<th>Project Name</th>
	<th>Project Address</th>
	<th>Project Owner</th>
	<th>Date</th>
	<th>Actions</th></tr></thead><tbody>';
	
	 foreach($list_1 as $list_val){
		 
		 
		 $entity_id='Customer_Project_list';
		  if(accessrole($entity_id,P_UPDATE)){
          $url_admin_edit="<a href='".base_url("CustomerService/customer_project_edit/".$list_val->customer_project_id)."' data-toggle='tooltip' data-placement='bottom' title='EDIT'><img src='". base_url('assets/images/edit.png')."'/></a>&nbsp";
			}
			
			 if(accessrole($entity_id,P_DELETE)){
			  
			 
			  
			$url_admin_delete="<a onclick='return confirm(\"Are you sure you want delete?\");' href='".base_url("CustomerService/customer_project_delete/".$list_val->customer_project_id)."' data-toggle='tooltip' data-placement='bottom' title='DELETE'><img src='". base_url('assets/images/delete.png')."'/></a>";  
		
			  }
			  
			  
			  
			 
			   $url_admin_view = base_url("CustomerService/customer_project_view/".$list_val->customer_project_id."");
			  
			echo '<tr><td>'.$i.'</td><td><a href="'.$url_admin_view.'"> '.$list_val->project_name.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->project_address.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->name.'</a></td><td><a href="'.$url_admin_view.'"> '.date('Y-m-d',strtotime($list_val->created_datetime)).'</a></td><td><a href="'.$url_admin_view.'"> <img src="'.$url_val_img_view.'"></img></a>';  
		 
		  if(accessrole($entity_id,P_UPDATE)){ 
          echo "&nbsp;&nbsp;$url_admin_edit";
         }
       if(accessrole($entity_id,P_DELETE)){ 
        echo "&nbsp;&nbsp;$url_admin_delete";
       }
         echo '&nbsp;</td></tr>';
        $i++;
		 
		 
	 }
	   
	   
   }
   
   if($type == "daily_report"){
	   $users = $this->input->post("users");
      $type = $this->input->post("type");
      $todate = date("Y-m-d 23:59:59",strtotime($this->input->post("todate")));
      $fromdate = date("Y-m-d 00:00:00",strtotime($this->input->post("fromdate")));
       
	   
	    if($fromdate !='1970-01-01 00:00:00'  || $fromdate !='1970-01-01 23:59:59' ){
        if($fromdate =='1970-01-01 00:00:00' || $fromdate == '' || $fromdate == "null" ){
          $fromdate = date("Y-m-d 00:00:00");
        }
        if($todate =='1970-01-01 23:59:59' ||$todate == '' || $todate == "null"){
           $todate = date("Y-m-d 23:59:59");
        }
        
        $query_param = " a.created_datetime  between '".$fromdate."' and '".$todate."'";

      } 
      if($users!='' || $users != 0){
          $query_param .= " and a.created_by in (".$users.")";
      }
	  
	  
	 
	  
	  $list_1=$this->db->query("select  * from cs_dailyreport a inner join cs_users c on a.created_by = c.user_id left join cs_customerproject as d on a.customer_project_id=d.customer_project_id left join cs_contact_contractor e on a.contact_contractor_id=e.contact_contractor_id where   ".$query_param."")->result();
	  
	  
	  
	
	  
	  
    $i=1;
    echo '<table class="table table-striped table-bordered" id="profile_dtc"><thead>
	<tr>
	<th>S.No</th>
	<th>Related To</th>
	<th>Contractor</th>
	<th>Client Project</th>
	<th>Call Date</th>
	<th>Owner</th>
	
	<th>Actions</th></tr></thead><tbody>';
	
	 foreach($list_1 as $list_val){
		 
		 
		 $entity_id='daily_report';
		  if(accessrole($entity_id,P_UPDATE)){
          $url_admin_edit="<a href='".base_url("CustomerService/daily_report_edit/".$list_val->cs_dailyreport_id)."' data-toggle='tooltip' data-placement='bottom' title='EDIT'><img src='". base_url('assets/images/edit.png')."'/></a>&nbsp";
			}
			
			 if(accessrole($entity_id,P_DELETE)){
			  
			 
			  
			$url_admin_delete="<a onclick='return confirm(\"Are you sure you want delete?\");' href='".base_url("CustomerService/daily_report_delete/".$list_val->cs_dailyreport_id)."' data-toggle='tooltip' data-placement='bottom' title='DELETE'><img src='". base_url('assets/images/delete.png')."'/></a>";  
		
			  }
			  
			  
			  
			$url_admin_view = base_url("CustomerService/daily_report_view/".$list_val->cs_dailyreport_id."");
			  
			  
			echo '<tr><td>'.$i.'</td><td><a href="'.$url_admin_view.'"> '.$list_val->related_to.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->contractor_name.'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->project_name.'</a></td><td><a href="'.$url_admin_view.'"> '.date('Y-m-d',strtotime($list_val->call_date)).'</a></td><td><a href="'.$url_admin_view.'"> '.$list_val->name.'</a></td><td><a href="'.$url_admin_view.'"> <img src="'.$url_val_img_view.'"></img></a>';  
		 
		  if(accessrole($entity_id,P_UPDATE)){ 
          echo "&nbsp;&nbsp;$url_admin_edit";
         }
       if(accessrole($entity_id,P_DELETE)){ 
        echo "&nbsp;&nbsp;$url_admin_delete";
       }
         echo '&nbsp;</td></tr>';
        $i++;
		 
		 
	 }
	   
	   
   }
   
   
   
   
   
   
   
   
	}
	
	
	public function getDivisionProducts(){

	 $division_id=$this->input->post('division_id');
	 $product_list= $this->db->query("select * from product_master  where Division='".$division_id."' group by product_name")->result();	
	
	 
	 $product_view[] = '<option value="">--Select--</option>';
              foreach($product_list as $p_val){
                $product_view[] = "<option value='".$division_id.'-'.$p_val->product_id."'>".$p_val->product_name."</option>";
              }

                $product_price_val = implode(" ",$product_view);
				$data['success']=TRUE;
                $data['message']="Success";
                $data['values']=$contact_view;
                $data['product_list'] = $product_price_val;
				 echo json_encode($data);
		
	}
	
	
	public function get_Oa_nos(){
	$customer_project_id=$this->input->post('customer_project_id');
	
	if(!empty($customer_project_id)){
	 $oa_list= $this->db->query("select * from cs_customerproject_clientproject_details  where customer_project_id='".$customer_project_id."'")->result();	
	}else{
		
	 $oa_list= $this->db->query("select * from cs_customerproject_clientproject_details")->result();	
	}
	
	 
	 $oa_view[] = '<option value="">--Select--</option>';
              foreach($oa_list as $p_val){
                $oa_view[] = "<option value='".$p_val->cs_customerproject_clientproject_detailsid."'>".$p_val->oa_number."</option>";
              }

                $oa_val = implode(" ",$oa_view);
				$data['success']=TRUE;
                $data['message']="Success";
                $data['values']=$contact_view;
                $data['oa_list'] = $oa_val;
				 echo json_encode($data);	
		
		
	}
	
		function product_list_ajax_data() {
    if(accessrole(Products,P_READ)){
       $data['entity_id']='Products';
        $this->load->view('templates/header');
        $this->load->view('product_list_ajax',$data);
        $this->load->view('templates/footer');
      }else{
        redirect("admin/permissions_error");
      }
    }
	
	
	public function getProductList(){
		
		
		
	$data = $row = array(); 
        
    $memData = $this->ProductModel->getRows($_POST);
	$entity_id='Products';        
        $i = $_POST['start'];
        foreach($memData as $member){
            $i++;    
          $view="<a href='".base_url('Admin/product_view/'.$member->product_id)."'><img src='". base_url('assets/images/view.png')."'/></a>&nbsp";
		  if(accessrole($entity_id,P_UPDATE)){
		  $edit="<a href='".base_url("Admin/product_edit/".$member->product_id)."' data-toggle='tooltip' data-placement='bottom' title='EDIT'><img src='". base_url('assets/images/edit.png')."'/></a>&nbsp";
		  }else{
			  
			  $edit='&nbsp';
		  }
		  
		  if(accessrole($entity_id,P_DELETE)){
			  
			$delete="<a href='".base_url("Admin/product_delete/".$member->product_id)."' data-toggle='tooltip' data-placement='bottom' title='DELETE'><img src='". base_url('assets/images/delete.png')."'/></a>";  
		  }else{
			  
			$delete="&nbsp";  
		  }
		  
		  $action =$view.$edit.$delete;
		  $product_name="<a href='".base_url('Admin/product_view/'.$member->product_id)."'>".$member->product_name."</a>&nbsp";		  
		  $product_code="<a href='".base_url('Admin/product_view/'.$member->product_id)."'>".$member->product_code."</a>&nbsp";
		  $MaterialDescription="<a href='".base_url('Admin/product_view/'.$member->product_id)."'>".$member->MaterialDescription."</a>&nbsp";
		  $material_name="<a href='".base_url('Admin/product_view/'.$member->product_id)."'>".$member->material_name."</a>&nbsp";
		  $product_division="<a href='".base_url('Admin/product_view/'.$member->product_id)."'>".$member->division_name."</a>&nbsp";
		  
            $data[] = array($i, $product_name,$product_code,$MaterialDescription,$material_name,$product_division,$action);
			
        }
		
		$output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->ProductModel->countAllProducts(),
            "recordsFiltered" => $this->ProductModel->countFilteredproducts($_POST),
            "data" => $data,
        );
        
        // Output to JSON format
        echo json_encode($output);
	}
	
	
   function product_insert() {
     if(accessrole(Products, P_CREATE)){
        $param = $this->input->post();

        if(count($param) >0){
            $config['upload_path'] = './images/product_images'; //give the path to upload the image in folder
            $config['allowed_types'] = 'jpg|JPG|png|PNG|csv';
            $filesCount = count($_FILES['image1']['name']);
            for ($i = 0; $i < $filesCount; $i++) {
                $_FILES['image1[]']['name'] = $_FILES['image1']['name'][$i];
                $_FILES['image1[]']['type'] = $_FILES['image1']['type'][$i];
                $_FILES['image1[]']['tmp_name'] = $_FILES['image1']['tmp_name'][$i];
                $_FILES['image1[]']['error'] = $_FILES['image1']['error'][$i];
                $_FILES['image1[]']['size'] = $_FILES['image1']['size'][$i];
                $value = $i;
                $this->load->library('upload');
                $this->upload->initialize($config);
                $this->upload->do_upload('image1[]');
                $fname = $this->upload->data();
                $fileName[$i] = $fname['file_name'];
            }
            $string_version = implode(',', $fileName);
            $user_id=$this->session->userdata('logged_in')['id'];
            $param['image'] = $string_version;
            $param['created_by'] =$user_id;
            $param['modified_by'] =$user_id;
            $param['created_date_time'] =date("Y-m-d H:i:s");
            $param['modified_date_time'] =date("Y-m-d H:i:s");
            $ok = $this->Generic_model->insertDataReturnId("product_master",$param);
            if($ok){
                $this->session->set_flashdata('suscess', 'Successfully Added');
                redirect('admin/product_view/'.$ok.'');
            }else{
        $this->session->set_flashdata('error', 'Error Please Check the file format');
          redirect('admin/product_list_ajax_data');
        //echo $this->db->last_query();
               // print_r($param);
            }
        }else{
          $data['division_list'] = $this->db->query("select * from division_master")->result();
          $data['plant_list'] = $this->db->query("select * from plant_master where archieve !=1")->result();
          $data['material_group_list'] = $this->db->query("select * from MaterialGroup")->result();
          $data['material_subgroup_list'] = $this->db->query("select * from Material_subgroup")->result();
            $this->load->view('templates/header');
            $this->load->view('product_insert',$data);
            $this->load->view('templates/footer');
        }
      }else{
        redirect("admin/permissions_error");
      }
    }
  function product_edit($id) {
      if(accessrole(Products,P_UPDATE)){
        $param = $this->input->post();
        if(count($param) >0){
             $config['upload_path'] = './images/product_images'; //give the path to upload the image in folder
            $config['allowed_types'] = 'jpg|JPG|png|PNG|csv';
            $filesCount = count($_FILES['image1']['name']);
            for ($i = 0; $i < $filesCount; $i++) {
                $_FILES['image1[]']['name'] = $_FILES['image1']['name'][$i];
                $_FILES['image1[]']['type'] = $_FILES['image1']['type'][$i];
                $_FILES['image1[]']['tmp_name'] = $_FILES['image1']['tmp_name'][$i];
                $_FILES['image1[]']['error'] = $_FILES['image1']['error'][$i];
                $_FILES['image1[]']['size'] = $_FILES['image1']['size'][$i];
                $value = $i;
                $this->load->library('upload');
                $this->upload->initialize($config);
                $this->upload->do_upload('image1[]');
                $fname = $this->upload->data();
                $fileName[$i] = $fname['file_name'];
            }
            $string_version = implode(',', $fileName);
            $user_id=$this->session->userdata('logged_in')['id'];
            if($string_version != ""){
                 $param['image'] = $string_version;
            }
            $param['modified_by'] =$user_id;
            $param['modified_date_time'] =date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('product_master', $param, array('product_id' => $id));
            if($ok == 1){
                $this->session->set_flashdata('suscess', 'Successfully Updated');
                redirect('admin/product_list');
            }else{
        $this->session->set_flashdata('error', 'Error Please Check the file format');
                redirect("admin/product_list");
            }

        }else{
            $data['product_list'] = $this->db->query("select * from product_master where product_id =".$id)->row();
             $data['plant_list'] = $this->db->query("select * from plant_master where archieve !=1")->result();
             $data['division_list'] = $this->db->query("select * from division_master")->result();
             $data['material_group_list'] = $this->db->query("select * from MaterialGroup")->result();
             $data['material_subgroup_list'] = $this->db->query("select * from Material_subgroup")->result();
            $this->load->view('templates/header');
            $this->load->view('product_insert',$data);
            $this->load->view('templates/footer');
        }
      }else{
        redirect("admin/permissions_error");
       }  
    }
   function product_view($id) {
        $data['product_list'] = $this->db->query("select * from product_master where product_id =".$id)->row();
       // $data['product_price_list'] = $this->db->query("select * from  product_price_master where Product_id =".$id)->result(); 
     
        $this->load->view('templates/header');
        $this->load->view('product_view',$data);
        $this->load->view('templates/footer');
    }
    function product_price_insert_list($id){
      $data['product_id'] = $id;
      $data['product_list'] = $this->db->query("select * from product_master where archieve != 1")->result();
        $data['customers_list'] = $this->db->query("select * from customers where archieve != 1")->result();
        $this->load->view('templates/header');
          $this->load->view('product_price_insert',$data);
          $this->load->view('templates/footer');

    }

    function product_delete($id){
      if(accessrole(Products, P_DELETE)){
        $user_id=$this->session->userdata('logged_in')['id'];
        $param['archieve'] = "1";
        $param['modified_by'] =$user_id;
        $param['modified_date_time'] =date("Y-m-d H:i:s");
        $ok = $this->Generic_model->updateData('product_master', $param, array('product_id' => $id));
        if($ok == 1){
            $this->session->set_flashdata('suscess', 'Successfully Deleted');
            redirect('admin/product_list_ajax_data');
        }else{
      $this->session->set_flashdata('error', 'Error Please Check the file format');
            redirect("admin/product_list_ajax_data");
        }
      }else{
        redirect("admin/permissions_error");
      } 
    }
	
	  public function division_list(){
      $data['division_list']=$this->db->query('select * from division_master')->result();
      $this->load->view('templates/header');
        $this->load->view('division_list',$data);
        $this->load->view('templates/footer');
    }
  public function division_insert(){
     $user_id=$this->session->userdata('logged_in')['id'];
     $param=$this->input->post();
    if(count($param)>0){
      $data['division_name']=$this->input->post('division_name');
      $data['division_sap_code']=$this->input->post('division_sap_code');
      $data['created_by']=$user_id;
      $data['modified_by']=$user_id;
      $data['created_date_time']=date('Y-m-d H:i:s');
      $data['modified_date_time']=date('Y-m-d H:i:s');
      $result = $this->Generic_model->insertData('division_master',$data);
      if($result==1){
        $this->session->set_flashdata('suscess', 'Successfully Added');
          redirect('Admin/division_list');
      }else{
        $this->session->set_flashdata('error', 'Error Please Check the file format');
          redirect("Admin/division_list");
      }
    }else{
        $this->load->view('templates/header');
          $this->load->view('division_insert');
          $this->load->view('templates/footer'); 
    }
  }
  public function division_update($id){
    $param=$this->input->post();
    if(count($param)>0){
          $data['division_name']=$this->input->post('division_name');
        $data['division_sap_code']=$this->input->post('division_sap_code');
          $data['modified_by']=$user_id;
        $data['modified_date_time']=date('Y-m-d H:i:s');
        $result = $this->Generic_model->updateData('division_master', $data, array('division_master_id'=>$id));
        if($result==1){
          $this->session->set_flashdata('suscess', 'Successfully Added');
            redirect('Admin/division_list');
        }else{
          $this->session->set_flashdata('error', 'Error Please Check the file format');
            redirect("Admin/division_list");
        }
    }else{
      $data['division_list']=$this->db->query('select * from division_master where division_master_id='.$id)->row();
          $this->load->view('templates/header');
            $this->load->view('division_insert', $data);
            $this->load->view('templates/footer'); 
    }
  }
  public function division_delete($id){
     $result = $this->Generic_model->deleteRecord('division_master', array('division_master_id'=>$id));
      if($result == 1){
          $this->session->set_flashdata('suscess', 'Successfully Deleted');
          redirect('admin/division_list');
      }else{
         $this->session->set_flashdata('error', 'Error Please Check');
          redirect("admin/division_list");
        
      }
  }
  
  public function customers_list(){
	  
	$data['customers_list']  = $this->db->query("select *,a.status from bf_customer a left join bf_customer_address b on (a.customer_id = b.customer_id) left join bf_community c on b.community_id=c.community_id ")->result();
		
    $this->load->view('templates/header');
    $this->load->view('customers_list',$data);
    $this->load->view('templates/footer');  
	  
	  
	  
  }
  
  public function csutomer_approval(){
	  
	 $data['customer_id']=$customer_id=$this->input->post('customer_id');
	 $data['status']=$status=$this->input->post('status');

	
            $ok = $this->Generic_model->updateData('bf_customer', $data, array('customer_id' => $customer_id));
			
			if($ok){
				
				echo "Customer status Updated successfully";
				
				
			}
	 
	  
  }
  
  
  public function customer_view($customer_id=''){
	  
	$data['customers_list']  = $this->db->query("select *,a.status from bf_customer a left join bf_customer_address b on (a.customer_id = b.customer_id) left join bf_community c on b.community_id=c.community_id left join districts d on c.district_id=d.district_id 
	left join states e on c.state_id=e.state_id
	where a.customer_id='".$customer_id."'")->row(); 

	$this->load->view('templates/header');
    $this->load->view('customers_view',$data);
    $this->load->view('templates/footer');  	
	  
  }
  
  
   
	
}