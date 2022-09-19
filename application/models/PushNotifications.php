<?php 

class PushNotifications  extends CI_model{
	function __construct(){
		parent::__construct();
		
	}
	
	public function test_notifications($push_noti){
		
		// echo API_ACCESS_KEY; die;
	
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			
			$msg = array
				(
	 			'title' => "A Push Notification",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Test",
	 			'id' => 1 ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
			
		}
		

		//iso onprogressing
	}
	public function lead_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$lead_id = $push_noti['lead_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Lead created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Lead",
	 			'id' => $lead_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Lead created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Lead",
	 			'id' => $lead_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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

		}

		//iso onprogressing
	}

	public function lead_convert_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$lead_id = $push_noti['lead_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "Lead Converted",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Lead Converted",
	 			'id' => $lead_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "Lead Converted",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Lead Converted",
	 			'id' => $lead_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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

		}

	}

	public function customer_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$customer_id = $push_noti['customer_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Customer created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Customer",
	 			'id' => $customer_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Customer created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Customer",
	 			'id' => $customer_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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

		}


	}

	public function contact_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$contact_id = $push_noti['contact_id'];
		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Contact created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Contact",
	 			'id' => $contact_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Contact created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Contact",
	 			'id' => $contact_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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

		}
	}

	public function Opportunitie_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$opportunity_id = $push_noti['opportunity_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Opportunitie created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Opportunitie",
	 			'id' => $opportunity_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Opportunitie created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Opportunitie",
	 			'id' => $opportunity_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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

		}

	}

	public function Quotation_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$Quotation_id = $push_noti['Quotation_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Quotation Created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Quotation",
	 			'id' => $Quotation_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Quotation Created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Quotation",
	 			'id' => $Quotation_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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
		}


	}

	public function Contract_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$contract_id = $push_noti['contract_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Contract Created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Contract",
	 			'id' => $contract_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Contract Created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"Contract",
	 			'id' => $contract_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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
		}

	}

	public function SalesOrder_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$sales_order_id = $push_noti['sales_order_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Sales Order  created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"SalesOrder",
	 			'id' => $sales_order_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Sales Order  created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"SalesOrder",
	 			'id' => $sales_order_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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
		}
	}
	public function SalesCalls_notifications($push_noti){
		$user_id = $push_noti['user_id'];
		$message = $push_noti['subject'];
		$fcmId_android = $push_noti['fcmId_android'];
		$fcmId_iOS = $push_noti['fcmId_iOS'];
		$fcmId_android_report_to = $push_noti['fcmId_android_report_to'];
		$fcmId_iOS_report_to = $push_noti['fcmId_iOS_report_to'];
		$sales_call_id = $push_noti['sales_call_id'];

		if($fcmId_android != "" || $fcmId_android != NULL){
			$msg = array
				(
	 			'title' => "New Sales Calls  created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"SalesCalls",
	 			'id' => $sales_call_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android),
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
		}
		if($fcmId_android_report_to != "" || $fcmId_android_report_to != NULL){
			$msg = array
				(
	 			'title' => "New Sales Calls  created",
	 			'vibrate' => 2,
	 			'sound' => 2,
	 			'message' =>"".$message."",
	 			'type' =>"SalesCalls",
	 			'id' => $sales_call_id ,
	 			'profile'=>'admin'
	 			);
			$fields = array
				(
					'registration_ids' => array($fcmId_android_report_to),
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
		}

	}
}