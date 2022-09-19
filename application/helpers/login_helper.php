<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
if ( ! function_exists('is_logged_in'))
{
	function is_logged_in()
	{
		$CI =& get_instance();
		$user = $CI->session->userdata('logged_in');
		if (!isset($user)) { return false; } else { return true; }
	
	}
	function user_manager($id){
		$CI =& get_instance();
		$users_list = $CI->db->query("select * from users where user_id =".$id)->row();
		if($users_list->name==NULL ){
			return "Self";
		}else{
			return $users_list->name;
		}

	}

	
	


	
	
	function role_name($id){
		$CI =& get_instance();
		$users_list = $CI->db->query("select * from roles where role_id =".$id)->row();
		//echo $CI->db->last_query();exit;
		if($users_list->role_name==NULL ){
			return "";
		}else{
			return $users_list->role_name;
		}

	}
	
	function get_user_details_by_role($id)
	{
		$CI =& get_instance();
		$users_list = $CI->db->query("select * from users where role =".$id)->row();
		//echo $CI->db->last_query();exit;
		if(count($users_list)>0)
		{
			return $users_list->name;
		}else{
			
			return"";
		}
		
	}
	

	
	
	

	function user_details($id){
		$CI =& get_instance();
		if($id != "" || $id != NULL){
			$user_details = $CI->db->query("select * from users where user_id =".$id)->row();
			//echo $CI->db->last_query();exit;
			if($user_details->user_id!=''){
				$user_name = $user_details->name;
				if($user_name == "" || $user_name == NULL){
					return "------"; 
				}else{
					return $user_name; 
				}
			}else{
				return "------"; 
			}
		}else{
			return "------"; 
		}
		
	}
	function user_email($id){
		$CI =& get_instance();
		$user_details = $CI->db->query("select * from users where user_id =".$id)->row();

		$user_name = $user_details->email;
		if($user_name == "" || $user_name == NULL){
			return "------"; 
		}else{
			return $user_name; 
		}
		
	}



	
		function accessrole($entity_id,$field){
		$CI =& get_instance();
		$role_id = $CI->session->userdata('logged_in')['role_id'];
		$entity_list_id = $CI->db->query("select * from role_entities where user_entity_name ='".$entity_id."'")->row();


		$role_permessions = $CI->db->query("select * from role_permissions where entity_id ='".$entity_list_id->entity_id."' and role_id ='".$role_id."' and $field = 1")->row();
	
		if($role_permessions->role_id!=''){
			return true;
		}else{
			return false;
		}


	}


	




	








	





	
	

	








}

