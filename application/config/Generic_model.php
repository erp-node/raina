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
		$this->db->where($condition);
		$result=$this->db->delete($table);
		if($result)
			return true;
		else
			return false;
	}
	
	public function pushNotifications($notification_type,$page,$event_agenda_id)
	{
		if(!empty($event_agenda_id) || $event_agenda_id != NULL)
		{
			$ea_id = $event_agenda_id;
		}else{
			$ea_id=NULL;
		}
		
		
		//User Registration and Admin Approval
		
				if($notification_type=='user_registration'){
					
					$msg = array
						(
						'title' => "New Attendee Registration",
						'vibrate' => 1,
						'sound' => 1,
						'message' =>"Hi Admin New Attendee Registration Done Please Check.",
						'page_type' =>$page,
						'event_agenda_id' =>NULL,
						'role'=>'admin'
						);
						
					$user_admin = $this->db->query("Select * from user where UserType='Admin'")->row();
					
					$fields = array
						(
							'registration_ids' => array($user_admin->FcmId),
							'data' => $msg
						);
					
					$headers = array
						(
						'Authorization: key=' . API_ACCESS_KEY,
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
				}else if($notification_type=='admin_approval'){
					
					$user_info_id = $this->db->query("Select * from user where UserId='".$ea_id."' and UserType='Attendee'")->row();
					
					$msg = array
					(
					'title' => "Admin Approval",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$user_info_id->FirstName." Admin has approved Your Registration Please Login",
					'page_type' =>$page,
					'event_agenda_id' =>NULL,
					'role'=>'attendee'
					);
					
					$fields = array
						(
						'registration_ids' => array($user_info_id->FcmId),
						'data' => $msg
						);
						
						$headers = array
							(
								'Authorization: key=' . API_ACCESS_KEY,
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
		
		$user_info = $this->db->query("Select * from user where UserType='Attendee' and Status='Active'")->result_array();
		$partners =  $this->db->query("Select * from partners where UserType='Partner' and Status='Active'")->result_array();
		
		foreach($user_info as $row_value)
		{
			if($row_value['deviceId']=='Android'){
				
				if($notification_type=='feedback')
				{
					$msg = array
					(
					'title' => "Feedback",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_value['FirstName']." Please Give Your Feedback For This Event",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'attendee'
					);
				}else if($notification_type=='feedback_agenda')
				{
					$msg = array
					(
					'title' => "Feedback Agenda",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_value['FirstName']." Please Give Your Feedback For This Agenda",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'attendee'
					);
				}else if($notification_type=='quiz'){
					$msg = array
					(
					'title' => "Agenda Questions",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_value['FirstName']." Please Give Your Answers For This Question(s)",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'attendee'
					);
				}
				
				
					$fields = array
					(
					'registration_ids' => array($row_value['FcmId']),
					'data' => $msg
					);
				
				$headers = array
					(
					'Authorization: key=' . API_ACCESS_KEY,
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
				
			}
		}
		
		foreach($partners as $row_partner)
		{
			if($row_partner['deviceId']=='Android'){
				
				if($notification_type=='feedback')
				{
					$msg = array
					(
					'title' => "Feedback",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_partner['FirstName']." Please Give Your Feedback For This Event",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'partner'
					);
				}else if($notification_type=='feedback_agenda')
				{
					$msg = array
					(
					'title' => "Feedback Agenda",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_partner['FirstName']." Please Give Your Feedback For This Agenda",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'partner'
					);
				}else if($notification_type=='quiz'){
					$msg = array
					(
					'title' => "Agenda Questions",
					'vibrate' => 1,
					'sound' => 1,
					'message' =>"Hi ".$row_partner['FirstName']." Please Give Your Answers For This Question(s)",
					'page_type' =>$page,
					'event_agenda_id' =>$ea_id,
					'role'=>'partner'
					);
				}
				
				if($notification_type !='admin_approval' || $notification_type!='user_registration')
				{
					$fields = array
					(
					'registration_ids' => array($row_partner['FcmId']),
					'data' => $msg
					);
				}
				
				
				
				
				$headers = array
					(
					'Authorization: key=' . API_ACCESS_KEY,
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
				
			}
		}
	}
		
	}
	
	
}

?>