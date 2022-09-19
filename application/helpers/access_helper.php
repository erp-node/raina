<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
if ( ! function_exists('access'))
{
	/**
	 * Element
	 *
	 * Lets you determine whether an array index is set and whether it has a value.
	 * If the element is empty it returns NULL (or whatever you specify as the default value.)
	 *
	 * @param	string
	 * @param	array
	 * @param	mixed
	 * @return	mixed	depends on what the array contains
	 */
	function access($entity_id,$field)
	{
		$CI =& get_instance();
		$user = $CI->session->userdata('logged_in');
		$role_id = $user['role'];
        $CI->load->database();
		$sql = "SELECT * FROM role_permissions where role_id = $role_id and entity_id = $entity_id  and $field=1";
		$q = $CI->db->query($sql);
		if($q->num_rows() > 0){   
			return true;
		}
		else
		{
			return false;
		}

	
	}
	
	function loginusers(){		
		$CI =& get_instance();
		$user = $CI->session->userdata('logged_in');
		$role = $user['role'];
		$id=$user['id'];
		if($role==MO){
			$condition=array('user_id'=>$id);
			$result=$CI->Generic_model->getAllRecords('users',$condition);
			return $result;
		}
		else if ($role==ADMIN){		
			$condition=array();
			$result=$CI->Generic_model->getAllRecords('users',$condition);
			return $result;
		}	
		else{		
			$users=getChildren($id);
			array_push($users,$id);	
			$condition = implode(', ', $users);
			$result=$CI->Generic_model->getWhereIn('users',$condition);
			return $result;			
		}

	
		
	}
	
	
	
	function getOneLevel($catId){
	$CI =& get_instance();		
    $query=$CI->db->query("SELECT user_id FROM users WHERE reporting_manager_id='".$catId."' AND status=1")->result();
    $cat_id=array();
if(count( $query) >0){	
   foreach( $query as $result){	   
	 $cat_id[]=$result->user_id; 	   
   }
     return $cat_id;   
 }    
   
}

function getChildren($reporting_manager_id='', $tree_string=array(), $tree = array()) {
    $tree = getOneLevel($reporting_manager_id);		
	if(count($tree)>0 && is_array($tree)){ 	
		$tree_string = array_merge ($tree_string,$tree);
		foreach ($tree as $key => $val) {		
			$tree = getOneLevel($val);
			if(!empty($tree)){
				$tree_string = array_merge ($tree_string,$tree);
				
			}		
			
			
		}   
    }
	
	if(count($tree_string)>0) {		
		return $tree_string;		
	}else{
		return false;
	}

}
	
	
	
	
function getAdminUsers(){
	$CI =& get_instance();		
    $query=$CI->db->query("SELECT user_id FROM users WHERE status=1")->result();
    $cat_id=array();
if(count( $query) >0){	
   foreach( $query as $result){	   
	 $cat_id[]=$result->user_id; 	   
   }
     return $cat_id;   
 }    
   
}
	
	
}

?>