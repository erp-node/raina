<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
class Generic_model extends CI_Model{
	
	
	
	public function insertData($table,$data)
	{
		$result=$this->db->insert($table,$data);
		if($result)
			return true;
		else
			return false;

	}
	
	public function insertDataReturnId($table,$data)
	{
	
		$this->db->insert($table,$data);
		$insert_id = $this->db->insert_id();	
		return  $insert_id;

	}
	
	public function updateData($table,$data,$condition)
	{
		$this->db->where($condition);
		$result=$this->db->update($table,$data);	

	
		if($result)
		return true;
		else
		return false;
			
	}
		
	public function getNumberOfRecords($table,$condition)
	{
		$query=$this->db->select('*')->from($table)->where($condition)->get();
		return $query->num_rows();
	}
	
	public function getAllRecords($table,$condition='',$order='')
	{
		
	    if($condition=='' && $order=='')
		{
			return $this->db->select('*')->from($table)->get()->result();
		}
		else if($condition=='' && $order!='')
		{
			return $this->db->select('*')->from($table)->order_by($order['field'],$order['type'])->get()->result();
		}
		else if($condition!='' && $order=='')
		{
			return $this->db->select('*')->from($table)->where($condition)->get()->result();
		}
		else
		{
			return $this->db->select('*')->from($table)->where($condition)->order_by($order['field'],$order['type'])->get()->result();
		}

	}
	
	public function getgroupbyRecords($table, $condition)
	{
		return $this->db->select('*')->from($table)->group_by($condition)->get()->result();
	}
	
	public function deleteRecord($table, $condition)
	{
		try{
			$this->db->where($condition);
			$ok=$this->db->delete($table);
			
        }catch (Exception $exc) {
            $ok = $exc->getCode();
           
        }
        return $ok;
	}
	
	public function getJoinRecords($table,$jointable,$oncondition,$condition=array(),$type_join="",$select)
	{
		$this->db->select($select);
		$this->db->from($table);
		$this->db->join($jointable,$oncondition,$type_join);
        if(!empty($condition))
			$this->db->where($condition);
	    return $this->db->get()->result();
    }
	
	public function getSingleRecord($table,$condition='',$order='')
	{
	    if($condition=='' && $order=='')
		{
			return $this->db->select('*')->from($table)->get()->row();
		}
		else if($condition=='' && $order!='')
		{
			return $this->db->select('*')->from($table)->order_by($order['field'],$order['type'])->get()->row();
		}
		else if($condition!='' && $order=='')
		{
			return $this->db->select('*')->from($table)->where($condition)->get()->row();
		}
		else
		{
			return $this->db->select('*')->from($table)->where($condition)->order_by($order['field'],$order['type'])->get()->row();
		}

	}
	
	public function task_creation($table,$data)
	{
		$result=$this->db->insert($table,$data);
		if($result)
 		return true;
		else
		return false;
	}
	
	public function task_updation($table,$data,$condition='')
	{
		$this->db->where($condition);
		$result=$this->db->update($table,$data);		
		if($result)
		return true;
		else
		return false;
	}
	
	public function get_allrecords_group_having($table, $condition,$having)
	{
		return $this->db->select('*')->from($table)->group_by($condition)->having($having)->get()->result();
	}
	
	public function getJoinRecords_groupby($table,$jointable,$oncondition,$condition=array(),$type_join="",$select,$groupcondition)
	{
		$this->db->select($select);
		$this->db->from($table);
		$this->db->join($jointable,$oncondition,$type_join)->group_by($groupcondition)->having($condition);
		
	    return $this->db->get()->result();
    }
 
	public function pushNotifications($notification_type,$page,$admin_id,$customer_user_id,$user_id,$product_id,$data ="")
	 {
	 	if($notification_type=='customer_intrest_products'){
			$customer_users = $this->db->query("Select * from customer_user where customer_user_id='".$customer_user_id."'")->row();
			$product = $this->db->query("Select * from product where product_id='".$product_id."'")->row();
	 		
	 		$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_id='".SUPERADMIN."'")->result();

				$list['notiffication_type'] = "leads";
				$list['customer_user_id'] = $customer_user_id;
				$list['subject'] = $product->product_name;
				$list['created_datetime'] = date('Y-m-d h:i:s');
				$this->db->insert('notiffication',$list);
				
	 		foreach($user_admin as $values){
	 			
	 			if($values->deviceid=='Android'){
	 				$msg = array
			 			(
			 			'title' => "Product Leads",
			 			'vibrate' => 2,
			 			'sound' => 2,
			 			'message' =>"Hi ".$values->employee_name.", ".$customer_users->first_name." ".$customer_users->last_name."  is Intrest in  ".$product->product_name."",
			 			'page_type' =>$page,
			 			'id' => NULL ,
			 			'profile'=>'admin'
			 			);
					$fields = array
		 			(
		 				'registration_ids' => array($values->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);

				}else{

					$msg = array
			 			(
			 			'title' => "Product Leads",
			 			'message' =>"Hi ".$values->employee_name.", ".$customer_users->first_name." ".$customer_users->last_name." is Intrest in  ".$product->product_name."",
			 			'page_type' =>$page,
			 			'id' => NULL ,
			 			'profile'=>'admin'
			 			);


						$deviceToken = "".$values->FcmId."";
						$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
						$passphrase = 1234;

						$ctx = stream_context_create();
					
						stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
						stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
					
						$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
						
						$body['aps'] = array(
									'badge' => 1,
									'alert' => $msg,
									'sound' => 'default'
								);
								
								$payload = json_encode($body);
							$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

							$result = fwrite($fp, $msg, strlen($msg));
							fclose($fp);
				}

	 		}	
		}else if($notification_type=='customer_service_tickets'){

			$customer_user_id = $this->db->query("Select * from customer_user where customer_user_id='".$customer_user_id."'")->row();
	 		$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_id='".SUPERADMIN."'")->result();
	 		$ticket_details = $this->db->query("select * from ticket_master where ticket_master_id =".$data['ticket_master_id'])->row();

	 		foreach($user_admin as $values){
	 			
				if($values->deviceid=='Android'){
					$msg = array
		 			(
		 			'title' => "Customer Service Tickets",
		 			'vibrate' => 1,
		 			'sound' => 1,
		 			'message' =>"Hi ".$values->employee_name.", ".$customer_user_id->first_name." Raised Service Ticket ticket no #".$ticket_details->ticket_no,
		 			'page_type' =>$page,
		 			'id'=>Null,
		 			'profile'=>'admin'
		 			);
					$fields = array
		 			(
		 				'registration_ids' => array($values->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);
				}else{
						$msg = array
			 			(
			 			'title' => "Customer Service Tickets",
			 			'message' =>"Hi ".$values->employee_name.", ".$customer_user_id->first_name." Raised Service Ticket ticket no #".$ticket_details->ticket_no,
			 			'page_type' =>$page,
			 			'id'=>Null,
			 			'profile'=>'admin'
			 			);
						$deviceToken = "".$values->FcmId."";
						$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
						$passphrase = 1234;

						$ctx = stream_context_create();
					
						stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
						stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
					
						$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
						
						$body['aps'] = array(
									'badge' => 1,
									'alert' => $msg,
									'sound' => 'default'
								);
								
								$payload = json_encode($body);
							$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

							$result = fwrite($fp, $msg, strlen($msg));
							// if(!$result)
							// {
							// 	echo "Message not Delivered".PHP_EOL;
							// }else{
							// 	echo "Message Delivered".PHP_EOL;
							// }
							fclose($fp);
				}
			}
		}else if($notification_type=='admin_raise_ticket'){
			$ticket_master_id = $data['ticket_master_id'];
			$employee_id = $data['employee_id'];
			$ticket_details = $this->db->query("select * from ticket_master where 	ticket_master_id =".$ticket_master_id)->row();
			$employee_list = $this->db->query("Select * from employee a inner join login b on (a.login_id = b.login_id) where employee_id = ".$employee_id)->row();
			$customer_details = $this->db->query("select * from customer_user a inner join login b on (a.login_id = b.login_id) where  customer_user_id = ".$customer_user_id)->row();
				$notif['notiffication_type'] = "Service Ticket";
				$notif['user_id'] = $employee_id;
				$notif['customer_user_id'] = $customer_user_id;
				$notif['page'] = "assigned";
				$notif['subject'] = "Ticket_no ".$ticket_details->ticket_no; 
				$notif['created_datetime'] = date("Y-m-d H:i:s");
				$this->db->insert('notiffication',$notif);
			if($employee_list->user_type == "employee"){
				
	 			if($employee_list->deviceid=='Android'){
	 				$msg = array
	 					(
			 			'title' => "Admin Raise Ticket",
			 			'vibrate' => 1,
			 			'sound' => 1,
			 			'message' =>"Hi ".$employee_list->employee_name.", New ticket has been assigned Ticket_no :".$ticket_details->ticket_no."",
			 			'page_type' =>"service_ticket",
			 			'id'=>"".$ticket_details->ticket_master_id."",
			 			'profile'=>'employee'
			 			);
					$fields = array
		 			(
		 				'registration_ids' => array($employee_list->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);
				}else{
						$msg = array
	 					(
			 			'title' => "Admin Raise Ticket",
			 			'message' =>"Hi ".$employee_list->employee_name.", New ticket has been assigned Ticket_no :".$ticket_details->ticket_no."",
			 			'page_type' =>"service_ticket",
			 			'id'=>"".$ticket_details->ticket_master_id."",
			 			'profile'=>'employee'
			 			);
						$deviceToken = "".$values->FcmId."";
						$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
						$passphrase = 1234;

						$ctx = stream_context_create();
					
						stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
						stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
					
						$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
						
						$body['aps'] = array(
									'badge' => 1,
									'alert' => $msg,
									'sound' => 'default'
								);
								
								$payload = json_encode($body);
							$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

							$result = fwrite($fp, $msg, strlen($msg));
							// if(!$result)
							// {
							// 	echo "Message not Delivered".PHP_EOL;
							// }else{
							// 	echo "Message Delivered".PHP_EOL;
							// }
							fclose($fp);
				}
			}

			if($customer_details->user_type == "customer"){
				
	 			if($customer_details->deviceid=='Android'){
	 				$msg = array
		 			(
		 			'title' => "Admin Raise Ticket",
		 			'vibrate' => 1,
		 			'sound' => 1,
		 			'message' =>"Hi ".$customer_details->first_name.", Your Ticket has been Assigned to Engineer for futher process  Ticket_no :".$ticket_details->ticket_no."",
		 			'page_type' =>"service_ticket",
		 			'id'=>"".$ticket_details->ticket_master_id."",
		 			'profile'=>'customer'
		 			);
					$fields = array
		 			(
		 				'registration_ids' => array($customer_details->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);
				}else{
						$msg = array
		 			(
		 			'title' => "Admin Raise Ticket",
		 			'message' =>"Hi ".$customer_details->first_name.", Your Ticket has been Assigned to Engineer for futher process  Ticket_no :".$ticket_details->ticket_no."",
		 			'page_type' =>"service_ticket",
		 			'id'=>"".$ticket_details->ticket_master_id."",
		 			'profile'=>'customer'
		 			);
						$deviceToken = "".$values->FcmId."";
						$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
						$passphrase = 1234;

						$ctx = stream_context_create();
					
						stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
						stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
					
						$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
						
						$body['aps'] = array(
									'badge' => 1,
									'alert' => $msg,
									'sound' => 'default'
								);
								
								$payload = json_encode($body);
							$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

							$result = fwrite($fp, $msg, strlen($msg));
							// if(!$result)
							// {
							// 	echo "Message not Delivered".PHP_EOL;
							// }else{
							// 	echo "Message Delivered".PHP_EOL;
							// }
							fclose($fp);
				}
			}
		}else if($notification_type=='reassign_ticket'){

			$ticket_operation_log_details =  $this->db->query("select * from ticket_operation_log a inner join ticket_master b on (a.ticket_master_id = b.ticket_master_id) where a.ticket_operation_log_id =".$data['ticket_operation_log_id'])->row();
			$assignee_id = $ticket_operation_log_details->assignee_id;
			$reassigned_by = $data['employee_id'];
			$assignee_list = $this->db->query("select * from employee a inner join login b on (a.login_id = b.login_id) where a.employee_id =".$assignee_id)->row();
			$reassignee_list = $this->db->query("select * from employee a inner join login b on (a.login_id = b.login_id) where a.employee_id = ".$reassigned_by)->row();
			if(count($assignee_list)>0 && $assignee_list->deviceid=='Android' ){
				$msg = array
	 			(
	 			'title' => "Reassign Ticket",
	 			'vibrate' => 1,
	 			'sound' => 1,
	 			'message' =>"Hi ".$assignee_list->employee_name.", Your Ticket has been cancel Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
	 			$fields = array
	 			(
	 				'registration_ids' => array($assignee_list->FcmId),
	 				'data' => $msg
	 			);
			
				$headers = array
	 			(
	 			'Authorization: key=' .API_ACCESS_KEY,
	 			'Content-Type: application/json'
	 			);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
				$result = curl_exec($ch);
				curl_close($ch);

			}else{
				$msg = array
	 			(
	 			'title' => "Reassign Ticket",
	 			'message' =>"Hi ".$assignee_list->employee_name.", Your Ticket has been cancel Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
				$deviceToken = "".$values->FcmId."";
				$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
				$passphrase = 1234;

				$ctx = stream_context_create();
			
				stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
				stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
			
				$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
				
				$body['aps'] = array(
							'badge' => 1,
							'alert' => $msg,
							'sound' => 'default'
						);
						
						$payload = json_encode($body);
					$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

					$result = fwrite($fp, $msg, strlen($msg));
					// if(!$result)
					// {
					// 	echo "Message not Delivered".PHP_EOL;
					// }else{
					// 	echo "Message Delivered".PHP_EOL;
					// }
					fclose($fp);
				}
	
			if(count($reassignee_list)>0 && $reassignee_list->deviceid == "Android"){
				$msg = array
	 			(
	 			'title' => "Reassign Ticket",
	 			'vibrate' => 1,
	 			'sound' => 1,
	 			'message' =>"Hi ".$reassignee_list->employee_name.",Ticket has been Assigned  Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
	 			$fields = array
	 			(
	 				'registration_ids' => array($reassignee_list->FcmId),
	 				'data' => $msg
	 			);			
				$headers = array
	 			(
	 			'Authorization: key=' .API_ACCESS_KEY,
	 			'Content-Type: application/json'
	 			);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
				$result = curl_exec($ch);
				curl_close($ch);
			}else{
				$msg = array
	 			(
	 			'title' => "Reassign Ticket",
	 			'message' =>"Hi ".$reassignee_list->employee_name.",Ticket has been Assigned  Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
				$deviceToken = "".$values->FcmId."";
				$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
				$passphrase = 1234;

				$ctx = stream_context_create();
			
				stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
				stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
			
				$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
				
				$body['aps'] = array(
							'badge' => 1,
							'alert' => $msg,
							'sound' => 'default'
						);
						
						$payload = json_encode($body);
					$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

					$result = fwrite($fp, $msg, strlen($msg));
					// if(!$result)
					// {
					// 	echo "Message not Delivered".PHP_EOL;
					// }else{
					// 	echo "Message Delivered".PHP_EOL;
					// }
					fclose($fp);
				}
		}else if($notification_type=='admin_approval'){
			$ticket_operation_log_details = $this->db->query("select * from ticket_operation_log a inner join ticket_master b  on (a.ticket_master_id = b.ticket_master_id) where ticket_operation_log_id =".$data['ticket_operation_log_id'])->row();
			$employee_id = $ticket_operation_log_details->assignee_id;
			$employee_details = $this->db->query("select * from employee a inner join login b on (a.login_id = b.login_id) where a.employee_id = ".$employee_id)->row();

				$notif['notiffication_type'] = "Service Ticket";
				$notif['user_id'] = $employee_id;
				$notif['page'] = "Approval";
				$notif['subject'] = "Ticket_no ".$ticket_operation_log_details->ticket_no ."has been approved";
				$notif['created_datetime'] = date("Y-m-d H:i:s");
				$this->db->insert('notiffication',$notif);
			if($employee_details->deviceid == "Android"){
				$msg = array
	 			(
	 			'title' => "Approval Ticket",
	 			'vibrate' => 1,
	 			'sound' => 1,
	 			'message' =>"Hi ".$employee_details->employee_name.",Ticket has been approved   Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
	 			$fields = array
	 			(
	 				'registration_ids' => array($employee_details->FcmId),
	 				'data' => $msg
	 			);			
				$headers = array
	 			(
	 			'Authorization: key=' .API_ACCESS_KEY,
	 			'Content-Type: application/json'
	 			);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
				$result = curl_exec($ch);
				curl_close($ch);
			}else{
				$msg = array
	 			(
	 			'title' => "Approval Ticket",
	 			'message' =>"Hi ".$employee_details->employee_name.",Ticket has been approved   Ticket_no : ".$ticket_operation_log_details->ticket_no."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_operation_log_details->ticket_master_id."",
	 			'profile'=>'employee'
	 			);
				$deviceToken = "".$values->FcmId."";
				$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
				$passphrase = 1234;

				$ctx = stream_context_create();
			
				stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
				stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
			
				$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
				
				$body['aps'] = array(
							'badge' => 1,
							'alert' => $msg,
							'sound' => 'default'
						);
						
						$payload = json_encode($body);
					$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

					$result = fwrite($fp, $msg, strlen($msg));
					// if(!$result)
					// {
					// 	echo "Message not Delivered".PHP_EOL;
					// }else{
					// 	echo "Message Delivered".PHP_EOL;
					// }
					fclose($fp);
			}
		}else if($notification_type=='Hold_ticket'){
			$ticket_master_details = $this->db->query("select * from  ticket_operation_log a inner join employee b on (a.assignee_id = b.employee_id) where a.ticket_operation_log_id =".$data['ticket_operation_log_id'])->row();
			$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_id='".SUPERADMIN."'")->result();
			$reason_list = $this->db->query("select * from reasons where reason_id =".$ticket_master_details->reason_id)->row();
					$notif['notiffication_type'] = "Service Ticket";
					$notif['user_id'] = $ticket_master_details->assignee_id;
					$notif['page'] = "Hold";
					$notif['subject'] = $reason_list->title;
					$notif['created_datetime'] = date("Y-m-d H:i:s");
					$this->db->insert('notiffication',$notif);
			foreach($user_admin as $values){

	 			if($values->deviceid=='Android'){
	 				$msg = array
		 			(
		 			'title' => "Hold Ticket",
		 			'vibrate' => 2,
		 			'sound' => 2,
		 			'message' =>"Hi ".$values->employee_name.", Service Ticket #".$ticket_master_details->ticket_no." has been Put on Hold by ".$ticket_master_details->employee_name."",
		 			'page_type' =>"service_ticket",
		 			'id'=>"".$ticket_master_details->ticket_master_id."",
		 			'profile'=>'admin'
		 			);
					$fields = array
		 			(
		 				'registration_ids' => array($values->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);
					//echo $result;

				}else{
					$msg = array
			 			(
			 			'title' => "Hold Ticket",
			 			'message' =>"Hi ".$values->employee_name.", Service Ticket #".$ticket_master_details->ticket_no." has been Put on Hold by ".$ticket_master_details->employee_name."",
			 			'page_type' =>"service_ticket",
			 			'id'=>"".$ticket_master_details->ticket_master_id."",
			 			'profile'=>'admin'
			 			);
					$deviceToken = "".$values->FcmId."";
					$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
					$passphrase = 1234;

					$ctx = stream_context_create();
				
					stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
					stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
				
					$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
					
					$body['aps'] = array(
								'badge' => 1,
								'alert' => $msg,
								'sound' => 'default'
							);
							
							$payload = json_encode($body);
						$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

						$result = fwrite($fp, $msg, strlen($msg));
						// if(!$result)
						// {
						// 	echo "Message not Delivered".PHP_EOL;
						// }else{
						// 	echo "Message Delivered".PHP_EOL;
						// }
						fclose($fp);
				}

	 		}
		}else if($notification_type=='closed_ticket'){
			$ticket_master_details = $this->db->query("select * from  ticket_operation_log a inner join employee b on (a.assignee_id = b.employee_id) inner join ticket_master c on (c.ticket_master_id = a.ticket_master_id) where a.ticket_operation_log_id =".$data['ticket_operation_log_id'])->row();
			$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_id='".SUPERADMIN."'")->result();

				$notif['notiffication_type'] = "Service Ticket";
				$notif['user_id'] = $ticket_master_details->assignee_id;
				$notif['customer_user_id'] = $ticket_master_details->customer_user_id;
				$notif['page'] = "closed";
				$notif['subject'] = $ticket_master_details->remarks;
				$notif['created_datetime'] = date("Y-m-d H:i:s");
				$this->db->insert('notiffication',$notif);
			foreach($user_admin as $values){
	 			
				

	 			if($values->deviceid=='Android'){
	 				$msg = array
	 			(
	 			'title' => "Closed Ticket",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"Hi ".$values->employee_name.", Service Ticket #".$ticket_master_details->ticket_no." has been Closed by ".$ticket_master_details->employee_name."",
	 			'page_type' =>"service_ticket",
	 			'id'=>"".$ticket_master_details->ticket_master_id."",
	 			'profile'=>'admin'
	 			);

					$fields = array
		 			(
		 				'registration_ids' => array($values->FcmId),
		 				'data' => $msg
		 			);
				
					$headers = array
		 			(
		 			'Authorization: key=' .API_ACCESS_KEY,
		 			'Content-Type: application/json'
		 			);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					$result = curl_exec($ch);
					curl_close($ch);
				}else{
					$msg = array
		 			(
		 			'title' => "Closed Ticket",
		 			'message' =>"Hi ".$values->employee_name.", Service Ticket #".$ticket_master_details->ticket_no." has been Closed by ".$ticket_master_details->employee_name."",
		 			'page_type' =>"service_ticket",
		 			'id'=>"".$ticket_master_details->ticket_master_id."",
		 			'profile'=>'admin'
		 			);
					$deviceToken = "".$values->FcmId."";
					$apnsCert = dirname(__FILE__).'/Reliability_APNS_Dev.pem';
					$passphrase = 1234;

					$ctx = stream_context_create();
				
					stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
					stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
				
					$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
					
					$body['aps'] = array(
								'badge' => 1,
								'alert' => $msg,
								'sound' => 'default'
							);
							
							$payload = json_encode($body);
						$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

						$result = fwrite($fp, $msg, strlen($msg));
						// if(!$result)
						// {
						// 	echo "Message not Delivered".PHP_EOL;
						// }else{
						// 	echo "Message Delivered".PHP_EOL;
						// }
						fclose($fp);
				}

	 		}

		}
	 }
	 
	 // public function pushNotificationsService($notification_type,$page,$admin_id,$assignee_id,$ticket_master_id)
	 // {
	 // 	if($notification_type=='customer_intrest_products'){
		// 	$customer = $this->db->query("Select * from customer where customer_id='".$customer_id."'")->row();
		// 	$product = $this->db->query("Select * from product where product_id='".$product_id."'")->row();
	 // 		$msg = array
	 // 			(
	 // 			'title' => "Product Leads",
	 // 			'vibrate' => 1,
	 // 			'sound' => 1,
	 // 			'message' =>"Hi Admin, ".$customer->customer_name." Intrest about ".$product->product_name."",
	 // 			'page_type' =>$page,
	 // 			'event_agenda_id' =>NULL,
	 // 			'profile'=>'admin'
	 // 			);
				
	 // 		$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_name='SuperAdmin'")->row();
			
		// 	if($user_admin->deviceid=='Android')
		// 	{
		// 		$fields = array
	 // 			(
	 // 				'registration_ids' => array($user_admin->FcmId),
	 // 				'data' => $msg
	 // 			);
			
		// 		$headers = array
	 // 			(
	 // 			'Authorization: key=' .API_ACCESS_KEY,
	 // 			'Content-Type: application/json'
	 // 			);

		// 		$ch = curl_init();
		// 		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		// 		curl_setopt($ch, CURLOPT_POST, true);
		// 		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// 		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		// 		$result = curl_exec($ch);
		// 		curl_close($ch);
		// 		echo $result;exit();
		// 	}
		// }else if($notification_type=='customer_service_tickets'){
		// 	$customer = $this->db->query("Select * from customer where customer_id='".$customer_id."'")->row();
		// 	$product = $this->db->query("Select * from customer where product_id='".$product_id."'")->row();
	 // 		$msg = array
	 // 			(
	 // 			'title' => "Customer Service Tickets",
	 // 			'vibrate' => 1,
	 // 			'sound' => 1,
	 // 			'message' =>"Hi Admin, ".$customer->customer_name." Raised Service Ticket",
	 // 			'page_type' =>$page,
	 // 			'event_agenda_id' =>NULL,
	 // 			'profile'=>'admin'
	 // 			);
				
	 // 		$user_admin = $this->db->query("SELECT * FROM employee inner join profile on (employee.profile_id=profile.profile_id) inner join login on (employee.login_id=login.login_id) where profile.profile_name='SuperAdmin'")->row();
			
		// 	if($user_admin->deviceid=='Android')
		// 	{
		// 		$fields = array
	 // 			(
	 // 				'registration_ids' => array($user_admin->FcmId),
	 // 				'data' => $msg
	 // 			);
			
		// 		$headers = array
	 // 			(
	 // 			'Authorization: key=' .API_ACCESS_KEY,
	 // 			'Content-Type: application/json'
	 // 			);

		// 		$ch = curl_init();
		// 		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		// 		curl_setopt($ch, CURLOPT_POST, true);
		// 		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// 		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		// 		$result = curl_exec($ch);
		// 		curl_close($ch);
		// 	}
		// }
		
	 // }
}

?>