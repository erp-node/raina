<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
class ProductModel extends CI_Model{
	
	function __construct(){
		
		
		$this->column_order = array(null,'product_name','product_code','MaterialDescription','material_name','product_family','division_name');
		$this->column_search = array('product_name','product_code','MaterialDescription','material_name','product_family','division_name');
		$this->order=array('product_id'=>'desc');
		
	}
	
	
	
	
	public function countAllProducts(){
		
//$this->db->query("select * from sales_call a inner join users b on (a.Owner = b.user_id) where  a.Owner in (".$final_users_id.") and   a.archieve != 1  ".$query_param." order by a.sales_call_id DESC")->result();		
		
		
   $this->db->select('*')->from('product_master as a')->join('MaterialGroup as b','b.material_group_id=a.product_family','left')->where('a.archieve',0);		
    return $this->db->count_all_results();
    }
	public function countFilteredproducts($postData){
        $this->_get_datatables_query_calls($postData);
        $query = $this->db->get();
        return $query->num_rows();
    }
	
	public function getRows($postData){
        $this->_get_datatables_query_calls($postData);
        if($postData['length'] != -1){
            $this->db->limit($postData['length'], $postData['start']);
        }
        $query = $this->db->get('');
		//echo $this->db->last_query();
        return $query->result();
    }
	
	 private function _get_datatables_query_calls($postData){
         		 
		$this->db->select('*')->from('product_master as a')->join('MaterialGroup as b','b.material_group_id=a.product_family','left')->join('division_master c','c.division_master_id=a.Division','left')->where('a.archieve',0);		
		
 
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