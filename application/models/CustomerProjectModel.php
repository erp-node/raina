<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('memory_limit','256M');
ini_set('max_execution_time', 30000);
class CustomerProjectModel extends CI_Model{
	
	function __construct(){
		
		
		$this->column_order = array(null,'project_name','project_address','name','a.created_datetime');
		$this->column_search = array('project_name','project_address','name','a.created_datetime');
		$this->order=array('a.customer_project_id'=>'desc');
	}
	
	
	
	
	public function countAllCustomerProjects($final_users_id){
   $this->db->select('*')->from('cs_customerproject as a')->join('cs_users as c','a.created_by = c.user_id')->where('a.created_by IN ('.$final_users_id.')');		
    return $this->db->count_all_results();
    }
	public function countFilteredCustomerProjects($postData,$final_users_id){
        $this->_get_datatables_query_contacts($postData,$final_users_id);
        $query = $this->db->get();
        return $query->num_rows();
    }
	
	public function getRows($postData,$final_users_id){
        $this->_get_datatables_query_contacts($postData,$final_users_id);
        if($postData['length'] != -1){
            $this->db->limit($postData['length'], $postData['start']);
        }
        $query = $this->db->get('');
		//echo $this->db->last_query();
        return $query->result();
    }
	
	 private function _get_datatables_query_contacts($postData,$final_users_id){
         
         
		 $this->db->select('*')->from('cs_customerproject as a')->join('cs_users as c','a.created_by = c.user_id')->where('a.created_by IN ('.$final_users_id.')');		
		
		
 
        $i = 0;
        // loop searchable columns 
        foreach($this->column_search as $item){
            // if datatable send POST for search
            if($postData['search']['value']){
                // first loop
                if($i===0){
                    // open bracket
                    $this->db->group_start();
                    $this->db->like($item, $postData['search']['value']);
                }else{
                    $this->db->or_like($item, $postData['search']['value']);
                }
                
                // last loop
                if(count($this->column_search) - 1 == $i){
                    // close bracket
                    $this->db->group_end();
                }
            }
            $i++;
        }
         
        if(isset($postData['order'])){
            $this->db->order_by($this->column_order[$postData['order']['0']['column']], $postData['order']['0']['dir']);
        }else if(isset($this->order)){
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }
	
	
	
}

?>