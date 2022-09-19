
<?php
error_reporting(0);
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'libraries/REST_Controller.php');
class Api_1 extends REST_Controller{

function __construct()
{
	parent::__construct();
	
	$this->load->helper('file');
	$this->load->library('PHPMailer');
	$this->load->library('SMTP');
	$this->load->model('PushNotifications');

	// Multipart API Call Start
	if($this->post('requestParams') != NULL || $this->post('requestParams') != '') {
	
		$fdata = json_decode($this->post('requestParams'));
		$this->load->library('upload');
		$config = array();
		
		$user_id = $fdata->requesterid;
		
		// Create Sales Order by Customer
		if($fdata->requestname == 'sales_order_by_direct_party') {
						
			$customer_id = $orderInfo['Customer'] = $fdata->Customer;
			
			// Generate Unique Sales Order Number
			$checking_id = $this->db->query("select * from sales_order order by sales_order_id DESC")->row();
			
            if($checking_id->sales_order_number == NULL || $checking_id->sales_order_number == ""){
                $orderInfo['sales_order_number'] = "SS-00001";
            }else{
				
				$orderInfo['sales_order_number'] = $this->Generic_model->generateUniqueNumber('sales_order','sales_order_number','sales_order_id','SS-','3');
				/*
                $opp_check = trim($checking_id->sales_order_number);
                $checking_op_id =  substr($opp_check, 3);
                if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
                    $opp_id_last_inc = (++$checking_op_id);
                    $orderInfo['sales_order_number']= "SS-".$opp_id_last_inc;
                }else{
                    $orderInfo['sales_order_number'] = (++$opp_check);
                } 
				*/
            } 
			$Son_id = $orderInfo['sales_order_number'];
						
			if($customer_id != "" || $customer_id != ""){
				$customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
				$SalesOrganisation = $customer_details->SalesOrganisation;
				$DistributionChannel = $customer_details->DistributionChannel;
				
				if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
					$orderInfo['SalesOrganisation'] = "";
				}else{
					$orderInfo['SalesOrganisation'] = $SalesOrganisation;
				}
				if($DistributionChannel == ""|| $DistributionChannel == NULL){
					$orderInfo['DistributionChannel'] = "";
				}else{
					$orderInfo['DistributionChannel'] = $DistributionChannel;
				}					
            }
			
			$orderInfo['OrderType'] = $fdata->OrderType;            
			$orderInfo['sales_order_dealer_contact_id'] = $fdata->sales_order_dealer_contact_id;
			$orderInfo['orc_details'] = $fdata->orc_details;
			$orderInfo['Ponumber'] = $fdata->Ponumber; 
            $orderInfo['Division'] = $fdata->Division;
            $orderInfo['remarks'] = $fdata->remarks;
            $orderInfo['Soldtopartycode'] = $fdata->Soldtopartycode;            
            $orderInfo['Shiptopartycode'] = $fdata->Shiptopartycode;
            $orderInfo['BilltopartyCode'] = $fdata->BilltopartyCode;
			$orderInfo['expected_order_dispatch_date'] = $fdata->expected_order_dispatch_date;
            $orderInfo['CashDiscount'] = $fdata->CashDiscount;
            $orderInfo['withoutdiscountamount'] = $fdata->withoutdiscountamount;
			$orderInfo['Freight'] = $fdata->Freight;
			
			if($orderInfo['Freight'] == "other"){
				$orderInfo['freight_amount'] = $freight_amount = $fdata->freight_amount;
            }else{
				if($orderInfo['Freight'] == "" || $orderInfo['Freight'] == NULL){
					$orderInfo['freight_amount'] = $freight_amount = 0;
				}else{
					$freight_list = $this->db->query("select * from freight_tbl where freight_id ='".$orderInfo['Freight']."'")->row();
					$orderInfo['freight_amount'] = $freight_amount = $freight_list->price;
				}              
            }
			
			$orderInfo['discountAmount'] = $fdata->discountAmount;
			$orderInfo['Total'] = $fdata->Total;
            $orderInfo['created_by'] = $user_id;
            $orderInfo['modified_by'] = $user_id;
            $orderInfo['created_date_time'] = date("Y-m-d H:i:s");
            $orderInfo['modified_date_time'] = date("Y-m-d H:i:s");
			
			// Check duplicate of unique generated number before saving
			$chkUnqRes = $this->db->query("SELECT * FROM sales_order WHERE sales_order_number = '".$orderInfo['sales_order_number']."'")->result();
			
			if(count($chkUnqRes) > 0){
				$orderInfo['sales_order_number'] = $this->Generic_model->generateUniqueNumber('sales_order','sales_order_number','sales_order_id','SS-','3');
			}
			
			$sales_order_id = $this->Generic_model->insertDataReturnId("sales_order",$orderInfo);
								
			if(isset($fdata->insert_by)){
								
				if($fdata->insert_by == 'Sales Call'){
					
					$tempData = array();
					$new = 0;
					
					// Check if the sales_call_temp_id exists
					if(isset($fdata->sales_calls_temp_id)){
						echo "Sales calls temp id exists\n";
						if($fdata->sales_calls_temp_id != '' || $fdata->sales_calls_temp_id != NULL){
							echo "entered into sales calls temp id : - Updating sales calls temp table\n";
							// Update sales calls temp table with sales_order_id for the respective sales call temp id												
							$this->Generic_model->updateData('sales_call_temp_table', array('sales_order_id' => $sales_order_id), array('sales_calls_temp_id' => $fdata->sales_calls_temp_id));		
						}else{
							$new = 1;
						}						
					}else{
						$new = 1;					
					}
					
					if($new == 1){
						// Insert a new record for sales calls temp table with sales order id											
						$tempData['sales_call_id'] = 0;
						$tempData['sales_order_id'] = $sales_order_id;
						$tempData['created_by'] = $user_id;
						$tempData['modified_by']=$user_id;
						$tempData['created_datetime']=date("Y-m-d H:i:s");
						$tempData['modified_datetime']=date("Y-m-d H:i:s");
						$result=$this->Generic_model->insertDataReturnId('sales_call_temp_table',$tempData);
					}	
				}
			}
						
			// Configure required for image uploads
			// Configure the path for Sales Order Concern Images
			$config['upload_path'] = './images/SalesOrder/salesorder_images/';
			$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';	
			
			// Purchase Order Image		
			if(isset($_FILES['purchase_order_image'])){		

				if($_FILES['purchase_order_image']['name'][0] != '' || $_FILES['purchase_order_image']['name'][0] != NULL){
							
					// Get Count of images
					$count = count($_FILES['purchase_order_image']['name']);			
					
					for($ctr=0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['purchase_order_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						if($ctr == 0){
							$imgData['purchase_image'] = "SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['purchase_image'] .= ", SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}
						$_FILES['purchase_order_image[]']['name'] = "SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['purchase_order_image[]']['type'] = $_FILES['purchase_order_image']['type'][$ctr];
						$_FILES['purchase_order_image[]']['tmp_name'] = $_FILES['purchase_order_image']['tmp_name'][$ctr];
						$_FILES['purchase_order_image[]']['error'] = $_FILES['purchase_order_image']['error'][$ctr];
						$_FILES['purchase_order_image[]']['size'] = $_FILES['purchase_order_image']['size'][$ctr];							
						
						
						// upload the picture
						$this->upload->initialize($config);		
						$res = $this->upload->do_upload('purchase_order_image[]');				
						$fname = $this->upload->data();
					}
				}else{
					$imgData['purchase_image'] = "";
				}
			}
			
			// Complaints Image		
			if(isset($_FILES['complaints_image'])){	
			
				if($_FILES['complaints_image']['name'][0] != '' || $_FILES['complaints_image']['name'][0] != NULL){
					// Get Count of images
					$count = count($_FILES['complaints_image']['name']);

					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['complaints_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);	
						
						if($ctr == 0){
							$imgData['complaints_image'] = "SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['complaints_image'] .= ", SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						}
										
						$_FILES['complaints_image[]']['name'] = "SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['complaints_image[]']['type'] = $_FILES['complaints_image']['type'][$ctr];
						$_FILES['complaints_image[]']['tmp_name'] = $_FILES['complaints_image']['tmp_name'][$ctr];
						$_FILES['complaints_image[]']['error'] = $_FILES['complaints_image']['error'][$ctr];
						$_FILES['complaints_image[]']['size'] = $_FILES['complaints_image']['size'][$ctr];							
						
						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('complaints_image[]');
						$fname = $this->upload->data();
					}			
				}else{
					$imgData['complaints_image'] = "";
				}
			}	
					
			// Payment Instrument Image		
			if(isset($_FILES['payment_instrument_image'])){	
			
				if($_FILES['payment_instrument_image']['name'][0] != '' || $_FILES['payment_instrument_image']['name'][0] != NULL){				
					// Get Count of images
					$count = count($_FILES['payment_instrument_image']['name']);			
					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['payment_instrument_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						
						if($ctr == 0){
							$imgData['payment_image'] = "SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['payment_image'] .= ", SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						}
						
						$_FILES['payment_instrument_image[]']['name'] = "SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						$_FILES['payment_instrument_image[]']['type'] = $_FILES['payment_instrument_image']['type'][$ctr];
						$_FILES['payment_instrument_image[]']['tmp_name'] = $_FILES['payment_instrument_image']['tmp_name'][$ctr];
						$_FILES['payment_instrument_image[]']['error'] = $_FILES['payment_instrument_image']['error'][$ctr];
						$_FILES['payment_instrument_image[]']['size'] = $_FILES['payment_instrument_image']['size'][$ctr];

						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('payment_instrument_image[]');
						$fname = $this->upload->data();				
					}
				}else{
					$imgData['payment_image'] = "";
				}					
			}	
			
			// Transfer Receipt Image
			if(isset($_FILES['transfer_receipt_image'])){		
				
				if($_FILES['transfer_receipt_image']['name'][0] != '' || $_FILES['transfer_receipt_image']['name'][0] != NULL){					
					// Get Count of images
					$count = count($_FILES['transfer_receipt_image']['name']);			
					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['transfer_receipt_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);	
						
						if($ctr == 0){
							$imgData['transfer_image'] = $_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['transfer_image'] .= ", ".$_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;
						}
						
						$_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;				
						$_FILES['transfer_receipt_image[]']['type'] = $_FILES['transfer_receipt_image']['type'][$ctr];
						$_FILES['transfer_receipt_image[]']['tmp_name'] = $_FILES['transfer_receipt_image']['tmp_name'][$ctr];
						$_FILES['transfer_receipt_image[]']['error'] = $_FILES['transfer_receipt_image']['error'][$ctr];
						$_FILES['transfer_receipt_image[]']['size'] = $_FILES['transfer_receipt_image']['size'][$ctr];							
						
						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('transfer_receipt_image[]');
						$fname = $this->upload->data();
					}			
				}else{
					$imgData['transfer_image'] = "";
				}
			}			
			
			// Update Sales Order Record with images names
			if(isset($imgData)){
				$res = $this->Generic_model->updateData('sales_order',$imgData,array('sales_order_id'=>$sales_order_id));
			}
						
			// Send Push Notifications, create notification, push ackowledgement emails 
			if($sales_order_id != "" || $sales_order_id != NULL){
				
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				
				if(count($user_list)>0){
					$push_noti['fcmId_android'] = $user_list->fcmId_android;
					$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
				}else{
					$push_noti['fcmId_android'] ="";
					$push_noti['fcmId_iOS'] = "";   
				}
				
				if(count($user_report_to) >0){
					$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
					$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
				}else{
					$push_noti['fcmId_android_report_to'] = "";
					$push_noti['fcmId_iOS_report_to'] = "";
				}
				
				$push_noti['sales_order_id'] = $sales_order_id;
				$push_noti['user_id'] = $user_id;
				$push_noti['subject'] = "A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName." ";
				$this->PushNotifications->SalesOrder_notifications($push_noti);
				
				$latest_val['module_id'] = $sales_order_id;
				$latest_val['module_name'] = "SalesOrder";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
				
				$productCheck=0;
				$extraProducts = array();
				$pi=0;				

				// Sales Order Product information - save products for sales orders
				$products = $fdata->sales_order_prodct;
								
				foreach($products as $product){
					
					if($product->ListPrice != '' || $product->ListPrice != NULL){
						$Product_id = $product->Product;
						
						// Get Contracts Information if the contract id is provided
						if($fdata->contract_id != ''){
							$c_p_chk = $this->db->query("select * from contract_products where Contract=".$fdata->contract_id." and Product=".$Product_id)->row();				  
							if(count($c_p_chk)<=0){
								$productCheck++;
								$extraProducts[$pi]['pid'] = $Product_id;
								$extraProducts[$pi]['pdis'] = $product->Discount;
								$pi++;
							}
						}
						
						// Product Information if the product id is provided
						if($Product_id == NULL || $Product_id == ""){
							$product_code = "";
						}else{
							$product_details = $this->db->query("select * from product_master where product_id =".$Product_id)->row();
							$product_code = $product_details->product_code;
						}
						
						$weight = (int)$product_details->Weight;
						$quantity = (int)$product->Quantity;
						$weight_in_kgs = $weight * $quantity;
						$weight_in_mt = $weight_in_kgs / 1000;
						
						$productData['sales_order_id'] = $sales_order_id;
						$productData['ListPrice'] = $product->ListPrice;
						$productData['Product'] = $Product_id;
						$productData['Productcode'] = $product_code;
						$productData['plant_id'] = $product->plant_id;
						$productData['Quantity'] = $product->Quantity;
						$productData['kgs'] = $weight_in_kgs;
						$productData['mt'] = $weight_in_mt;
						$productData['Discount'] = $product->Discount;
						$productData['Subtotal'] = $product->Subtotal;
						$productData['created_by'] = $user_id;
						$productData['modified_by'] = $user_id;
						$productData['created_date_time'] = date("Y-m-d H:i:s");
						$productData['modified_date_time'] = date("Y-m-d H:i:s");	

						$ok = $this->Generic_model->insertData("sales_order_products",$productData);

					}	
				}
				
				// Send Email Acknowledgement & Notificaiton to the higher level
				// Get Sales Order Products List
				$sales_product_list = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id = ".$sales_order_id)->result();				
				
				// Get Customer List
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				
				$email = $user_list->email;
				$to = $email;
				$subject = "New Sales Order Created";
				$data['name'] = ucwords($user_list->name);
				$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
				<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
				<thead>
				<tr >
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
				</tr></thead>";

				if(count($sales_product_list) >0){
					foreach($sales_product_list as $sales_values){
						$data['message'].=  "<tbody><tr>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
					}
				}

				$data['message'].= "</table><br/>";  

				// Push email
				$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				
				// Get User's higher authority to send email notification
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				
				if(count($user_report_to) >0){
					$email = $user_report_to->email;
					$to = $email;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($user_report_to->name);
					$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);						
				
				}
				
				$nationalHead = $this->db->query("select * from users where user_id = '".$user_report_to->manager."' AND status = 'Active'")->row();
				
				// email to national head
				if(count($nationalHead) >0){
					$email = $nationalHead->email;
					$to = $email;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($nationalHead->name);
					$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);						
				
				}

				$param_noti['notiffication_type'] = "SalesOrder";
				$param_noti['notiffication_type_id'] = $sales_order_id;
				$param_noti['user_id'] = $user_id;
				$param_noti['subject'] = " A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName."";
				$this->Generic_model->insertData("notiffication",$param_noti);	
				
				// Check if the contract_id is provided or no.
				// If provided then update data accordingly
				if($fdata->contract_id != '' && $fdata->contract_id != NULL){
					// Check product
					if($prodct_chk==0){
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);						
					}else{
						if(count($extraProducts)>0){
							$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();									
							$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

							$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
							if(count($rol_discount)>0){
								$dchk=0;
								foreach($extraProducts as $result){
									//print_r($result);exit;
									if($rol_discount->dis_limit<$result['pdis'])
									$dchk++;									
								}

								if($dchk==0){
									$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
								}else{
									$data_ins['approval_type'] = 'SalesOrder';
									$data_ins['approval_type_id'] = $sales_order_id;
									$data_ins['status'] = 3;
									$data_ins['datetime'] = date('Y-m-d H:i:s');
									$data_ins['assigned_to'] = $nex_report->role_reports_to;
									$data_ins['comments'] = '';
									$data_ins['created_by'] = $user_id;
									$data_ins['modified_by'] = $user_id;
									$data_ins['created_datetime'] = date('Y-m-d H:i:s');
									$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
									$ok = $this->Generic_model->insertData("approval_process",$data_ins);
									if($ok)
										$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
								}
							}
						}
					}
				}else{
					$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
					$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

					$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					if(count($rol_discount)>0){
						$dchk=0;
						$productCount = count($fdata->products);
						for($j=0;$j<$productCount;$j++){
							if($rol_discount->dis_limit<$fdata->products[$j]->Discount)
							$dchk++;								
						}
						if($dchk==0){							
							$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						}else{							
							$data_ins['approval_type'] = 'SalesOrder';
							$data_ins['approval_type_id'] = $sales_order_id;
							$data_ins['status'] = 3;
							$data_ins['datetime'] = date('Y-m-d H:i:s');
							$data_ins['assigned_to'] = $nex_report->role_reports_to;
							$data_ins['comments'] = '';
							$data_ins['created_by'] = $user_id;
							$data_ins['modified_by'] = $user_id;
							$data_ins['created_datetime'] = date('Y-m-d H:i:s');
							$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
							$ok = $this->Generic_model->insertData("approval_process",$data_ins);
							if($ok)
								$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
						}
					}	
				}

				$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
				$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>'sales_order_by_direct_party'));
			
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}
		// Sales Order by Direct Party Closed
		
		
		// Create Sales Order by Third Party
		if($fdata->requestname == 'sales_order_by_third_party') {
			
			$customer_id = $orderInfo['Customer'] = $fdata->Customer;
			
			// Generate Unique Sales Order Number
			$checking_id = $this->db->query("select * from sales_order order by sales_order_id DESC")->row();
            if($checking_id->sales_order_number  == NULL || $checking_id->sales_order_number   == ""){
                $orderInfo['sales_order_number'] = "SS-00001";
            }else{
				$orderInfo['sales_order_number'] = $this->Generic_model->generateUniqueNumber('sales_order','sales_order_number','sales_order_id','SS-','3');
                /*
				$opp_check = trim($checking_id->sales_order_number);
                $checking_op_id =  substr($opp_check, 3);
                if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
                    $opp_id_last_inc = (++$checking_op_id);
                    $orderInfo['sales_order_number']= "SS-".$opp_id_last_inc;
                }else{
                    $orderInfo['sales_order_number'] = (++$opp_check);
                } 
				*/
            } 
			$Son_id = $orderInfo['sales_order_number'];
			
			if($customer_id != "" || $customer_id != ""){
				$customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
				$SalesOrganisation = $customer_details->SalesOrganisation;
				$DistributionChannel = $customer_details->DistributionChannel;
				
				if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
					$orderInfo['SalesOrganisation'] = "";
				}else{
					$orderInfo['SalesOrganisation'] = $SalesOrganisation;
				}
				
				if($DistributionChannel == ""|| $DistributionChannel == NULL){
					$orderInfo['DistributionChannel'] = "";
				}else{
					$orderInfo['DistributionChannel'] = $DistributionChannel;
				}					
            }
			
			$orderInfo['OrderType'] = $fdata->OrderType; 
			$orderInfo['remarks'] = $fdata->Remarks; 			
            $orderInfo['Division'] = $fdata->Division;           
            $orderInfo['Ponumber'] = $fdata->Ponumber;   
            $orderInfo['withoutdiscountamount'] = $fdata->withoutdiscountamount;
			$orderInfo['Freight'] = $fdata->Freight;
			
			if($orderInfo['Freight'] == "other"){
				$orderInfo['freight_amount'] = $freight_amount = $fdata->freight_amount;
            }else{
				if($orderInfo['Freight'] == "" || $orderInfo['Freight'] == NULL){
					$orderInfo['freight_amount'] = $freight_amount = 0;
				}else{
					$freight_list = $this->db->query("select * from freight_tbl where freight_id ='".$orderInfo['Freight']."'")->row();
					$orderInfo['freight_amount'] = $freight_amount = $freight_list->price;
				}              
            }
			
			$orderInfo['Total'] = $fdata->Total;
			$orderInfo['OrderType_form'] = 'Third Party';
			$orderInfo['order_status'] = $fdata->order_status;
			$orderInfo['order_status_comments'] = $fdata->order_status_comments;
            $orderInfo['created_by'] = $user_id;
            $orderInfo['modified_by'] = $user_id;
            $orderInfo['created_date_time'] = date("Y-m-d H:i:s");
            $orderInfo['modified_date_time'] = date("Y-m-d H:i:s");
			$orderInfo['date_of_delivery'] = $fdata->date_of_delivery;
			$orderInfo['DeliveredBy']=$fdata->delivered_by;
			$orderInfo['DeliveredBy_customer_id']=$fdata->delivered_by_customer_id;
			
			// Check duplicate of unique generated number before saving
			$chkUnqRes = $this->db->query("SELECT * FROM sales_order WHERE sales_order_number = '".$orderInfo['sales_order_number']."'")->result();
			
			if(count($chkUnqRes) > 0){
				$orderInfo['sales_order_number'] = $this->Generic_model->generateUniqueNumber('sales_order','sales_order_number','sales_order_id','SS-','3');
			}
			
            $sales_order_id = $this->Generic_model->insertDataReturnId("sales_order",$orderInfo);
			
			// Check if the Sales Order is being raised by a Sales Call
			if(isset($fdata->insert_by)){
				
				if($fdata->insert_by == 'Sales Call'){
					
					$tempData = array();
					$new = 0;
					
					// Check if the sales_call_temp_id exists
					if(isset($fdata->sales_calls_temp_id)){
						if($fdata->sales_calls_temp_id != '' || $fdata->sales_calls_temp_id != NULL){
							// Update sales calls temp table with sales_order_id for the respective sales call temp id					
							$tempData['sales_order_id'] = $sales_order_id;
							$this->Generic_model->updateData('sales_call_temp_table', $tempData, array('sales_calls_temp_id' => $fdata->sales_calls_temp_id));
						}else{
							$new = 1;
						}						
					}else{
						$new = 1;					
					}
					
					if($new == 1){
						// Insert a new record for sales calls temp table with sales order id											
						$tempData['sales_call_id'] = 0;
						$tempData['sales_order_id'] = $sales_order_id;						
						$tempData['created_by'] = $user_id;
						$tempData['modified_by']=$user_id;
						$tempData['created_datetime']=date("Y-m-d H:i:s");
						$tempData['modified_datetime']=date("Y-m-d H:i:s");
						$result=$this->Generic_model->insertDataReturnId('sales_call_temp_table',$tempData);
					}	
				}
			}
			
			// Configure required for image uploads
			// Configure the path for Sales Order Concern Images
			$config['upload_path'] = './images/SalesOrder/salesorder_images/';
			$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';		
			
			// Purchase Order Image		
			if(isset($_FILES['purchase_order_image'])){	

				if($_FILES['purchase_order_image']['name'][0] != '' || $_FILES['purchase_order_image']['name'][0] != NULL){
						
					// Get Count of images
					$count = count($_FILES['purchase_order_image']['name']);			
					
					for($ctr=0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['purchase_order_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						if($ctr == 0){
							$imgData['purchase_image'] = "SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['purchase_image'] .= ", SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}
						$_FILES['purchase_order_image[]']['name'] = "SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['purchase_order_image[]']['type'] = $_FILES['purchase_order_image']['type'][$ctr];
						$_FILES['purchase_order_image[]']['tmp_name'] = $_FILES['purchase_order_image']['tmp_name'][$ctr];
						$_FILES['purchase_order_image[]']['error'] = $_FILES['purchase_order_image']['error'][$ctr];
						$_FILES['purchase_order_image[]']['size'] = $_FILES['purchase_order_image']['size'][$ctr];							
						
						
						// upload the picture
						$this->upload->initialize($config);		
						$res = $this->upload->do_upload('purchase_order_image[]');				
						$fname = $this->upload->data();
					}
				}else{
					$imgData['purchase_image'] = "";
				}
			}
			
			if(isset($imgData)){
				// Update Sales Order Record with images names
				$res = $this->Generic_model->updateData('sales_order',$imgData,array('sales_order_id'=>$sales_order_id));
			}
					
			// Send Push Notifications, create notification, push ackowledgement emails 
			if($sales_order_id != "" || $sales_order_id != NULL){
				
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				
				if(count($user_list)>0){
					$push_noti['fcmId_android'] = $user_list->fcmId_android;
					$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
				}else{
					$push_noti['fcmId_android'] ="";
					$push_noti['fcmId_iOS'] = "";   
				}
				
				if(count($user_report_to) >0){
					$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
					$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
				}else{
					$push_noti['fcmId_android_report_to'] = "";
					$push_noti['fcmId_iOS_report_to'] = "";
				}
				
				$push_noti['sales_order_id'] = $sales_order_id;
				$push_noti['user_id'] = $user_id;
				$push_noti['subject'] = "A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName." ";
				$this->PushNotifications->SalesOrder_notifications($push_noti);

				$latest_val['module_id'] = $sales_order_id;
				$latest_val['module_name'] = "SalesOrder";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
				
				$productCheck=0;
				$extraProducts = array();
				$pi=0;

				// Sales Order Product information - save products for sales orders
				$products = $fdata->sales_order_prodct;
				
				foreach($products as $product){
					
					if($product->ListPrice != '' || $product->ListPrice != NULL){
						$Product_id = $product->Product;
						
						// Get Contracts Information if the contract id is provided
						if($fdata->contract_id != ''){
							$c_p_chk = $this->db->query("select * from contract_products where Contract=".$fdata->contract_id." and Product=".$Product_id)->row();				  
							if(count($c_p_chk)<=0){
								$productCheck++;
								$extraProducts[$pi]['pid'] = $Product_id;
								$extraProducts[$pi]['pdis'] = $product->Discount;
								$pi++;
							}
						}
						
						// Product Information if the product id is provided
						if($Product_id == NULL || $Product_id == ""){
							$product_code = "";
						}else{
							$product_details = $this->db->query("select * from product_master where product_id =".$Product_id)->row();
							$product_code = $product_details->product_code;
						}
						
						$weight = (int)$product_details->Weight;
						$quantity = (int)$product->Quantity;
						$weight_in_kgs = $weight * $quantity;
						$weight_in_mt = $weight_in_kgs / 1000;
						
						$productData['sales_order_id'] = $sales_order_id;
						$productData['ListPrice'] = $product->ListPrice;
						$productData['Subtotal'] = $product->Subtotal;
						$productData['Product'] = $Product_id;
						$productData['Productcode'] = $product_code;
						$productData['Quantity'] = $product->Quantity;
						$productData['kgs'] = $weight_in_kgs;
						$productData['mt'] = $weight_in_mt;
						$productData['created_by'] = $user_id;
						$productData['modified_by'] = $user_id;
						$productData['created_date_time'] = date("Y-m-d H:i:s");
						$productData['modified_date_time'] = date("Y-m-d H:i:s");

						$ok = $this->Generic_model->insertData("sales_order_products",$productData);
					}	
				}
				
				// Sales Persons Third Party Sales Orders Product Data
				if(count($fdata->salesPersonsProducts) > 0){
					foreach($fdata->salesPersonsProducts as $spProduct){
						$spProduct->sales_order_id = $sales_order_id;
						$spProduct->created_by = $user_id;
						$spProduct->modified_by = $user_id;
						$spProduct->created_datetime = date("Y-m-d H:i:s");
						$spProduct->modified_datetime = date("Y-m-d H:i:s");	

						unset($spProduct->primaryKey);
						unset($spProduct->saleslineItemId);
						
						// Insert a new record for Table: tp_sales_order_sales_person_distributors
						$ok = $this->Generic_model->insertData("tp_sales_order_sales_person_distributors",$spProduct);
					}
				}
				
				// Send Email Acknowledgement & Notificaiton to the higher level
				// Get Sales Order Products List
				$sales_product_list = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id = ".$sales_order_id)->result();				
				
				// Get Customer List
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				
				$email = $user_list->email;
				$to = $email;
				$subject = "New Sales Order Created";
				$data['name'] = ucwords($user_list->name);
				$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
				<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
				<thead>
				<tr >
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
				</tr></thead>";

				if(count($sales_product_list) >0){
					foreach($sales_product_list as $sales_values){
						$data['message'].=  "<tbody><tr>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
					}
				}

				$data['message'].= "</table><br/>";  

				// Push email
				$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				
				// Get User's higher authority to send email notification
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				
				if(count($user_report_to) >0){
					$email = $user_report_to->email;
					$to = $email;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($user_report_to->name);
					$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				}
				
				// Send email to the Customer
				// Get customer who is delivering the items
				$delivered_by_customer_list = $this->db->query("select * from customers where customer_id =".$fdata->delivered_by_customer_id)->row();
				
				if(count($user_report_to) >0){
					$email = $customer_list->Email;
					$to = $email;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($delivered_by_customer_list->CustomerName);
					$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> Sales Order Id:</b> ".$Son_id."<br/>, <b>Customer Name:</b> ".ucwords($customer_list->CustomerName).", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					
					// Push email
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				}

				$param_noti['notiffication_type'] = "SalesOrder";
				$param_noti['notiffication_type_id'] = $sales_order_id;
				$param_noti['user_id'] = $user_id;
				$param_noti['subject'] = " A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName."";
				$this->Generic_model->insertData("notiffication",$param_noti);	
				
				// Check if the contract_id is provided or no.
				// If provided then update data accordingly
				if($fdata->contract_id != '' && $fdata->contract_id != NULL){
					// Check product
					if($prodct_chk==0){
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);						
					}else{
						if(count($extraProducts)>0){
							$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();									
							$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

							$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
							if(count($rol_discount)>0){
								$dchk=0;
								foreach($extraProducts as $result){
									if($rol_discount->dis_limit<$result['pdis'])
									$dchk++;									
								}

								if($dchk==0){
									$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
								}else{
									$data_ins['approval_type'] = 'SalesOrder';
									$data_ins['approval_type_id'] = $sales_order_id;
									$data_ins['status'] = 3;
									$data_ins['datetime'] = date('Y-m-d H:i:s');
									$data_ins['assigned_to'] = $nex_report->role_reports_to;
									$data_ins['comments'] = '';
									$data_ins['created_by'] = $user_id;
									$data_ins['modified_by'] = $user_id;
									$data_ins['created_datetime'] = date('Y-m-d H:i:s');
									$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
									$ok = $this->Generic_model->insertData("approval_process",$data_ins);
									if($ok)
										$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
								}
							}
						}
					}
				}else{
					$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
					$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

					$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					if(count($rol_discount)>0){
						$dchk=0;
						$productCount = count($fdata->products);
						for($j=0;$j<$productCount;$j++){
							if($rol_discount->dis_limit<$fdata->products[$j]->Discount)
							$dchk++;								
						}
						if($dchk==0){							
							$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						}else{							
							$data_ins['approval_type'] = 'SalesOrder';
							$data_ins['approval_type_id'] = $sales_order_id;
							$data_ins['status'] = 3;
							$data_ins['datetime'] = date('Y-m-d H:i:s');
							$data_ins['assigned_to'] = $nex_report->role_reports_to;
							$data_ins['comments'] = '';
							$data_ins['created_by'] = $user_id;
							$data_ins['modified_by'] = $user_id;
							$data_ins['created_datetime'] = date('Y-m-d H:i:s');
							$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
							$ok = $this->Generic_model->insertData("approval_process",$data_ins);
							if($ok)
								$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
						}
					}	
				}

				$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
				$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>'sales_order_by_third_party'));
			
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}
		// Sales Order by Third Party Closed
		
		
		// Create Sales Order by Third Party - Edit - Start
		if($fdata->requestname == 'sales_order_by_third_party_edit') {
			
			// Get the old record data 
			$salesOrderRec = $this->Generic_model->getSingleRecord('sales_order', array('sales_order_id' => $fdata->sales_order_id));
			$sales_order_id = $fdata->sales_order_id;
			
			$customer_id = $orderInfo['Customer'] = $fdata->Customer;
			
			if($customer_id != "" || $customer_id != ""){
				$customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
				$SalesOrganisation = $customer_details->SalesOrganisation;
				$DistributionChannel = $customer_details->DistributionChannel;
				
				if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
					$orderInfo['SalesOrganisation'] = "";
				}else{
					$orderInfo['SalesOrganisation'] = $SalesOrganisation;
				}
				if($DistributionChannel == ""|| $DistributionChannel == NULL){
					$orderInfo['DistributionChannel'] = "";
				}else{
					$orderInfo['DistributionChannel'] = $DistributionChannel;
				}					
            }
			
			$orderInfo['OrderType'] = $fdata->OrderType;            
            $orderInfo['Division'] =$fdata->Division;           
            $orderInfo['Ponumber'] = $this->$fdata->Ponumber;   
            $orderInfo['withoutdiscountamount'] = $fdata->withoutdiscountamount;
			$orderInfo['Freight'] = $fdata->Freight;
			
			if($orderInfo['Freight'] == "other"){
				$orderInfo['freight_amount'] = $freight_amount = $fdata->other_freight;
            }else{
				if($orderInfo['Freight'] == "" || $orderInfo['Freight'] == NULL){
					$orderInfo['freight_amount'] = $freight_amount = 0;
				}else{
					$freight_list = $this->db->query("select * from freight_tbl where freight_id ='".$orderInfo['Freight']."'")->row();
					$orderInfo['freight_amount'] = $freight_amount = $freight_list->price;
				}              
            }
			
			$orderInfo['Total'] = $fdata->TotalPrice;
			$orderInfo['OrderType_form'] = 'Third Party';            
            $orderInfo['modified_by'] = $user_id;            
            $orderInfo['modified_date_time'] = date("Y-m-d H:i:s");
			$orderInfo['date_of_delivery'] = $fdata->date_of_delivery;
			$orderInfo['DeliveredBy']=$fdata->delivered_by;
			
			// Configure required for image uploads
			// Configure the path for Sales Order Concern Images
			$config['upload_path'] = './images/SalesOrder/salesorder_images/';
			$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';		
			
			// Purchase Order Image		
			if(isset($_FILES['purchase_order_image'])){	

				if($_FILES['purchase_order_image']['name'][0] != '' || $_FILES['purchase_order_image']['name'][0] != NULL){
					
					// Check if the record got an old image 
					if($salesOrderRec->purchase_image != '' || $salesOrderRec->purchase_image != NULL){
						if(file_exists("images/SalesOrder/salesorder_images/".$salesOrderRec->purchase_image)){
							// if YES - Delete it from the storage
							unlink("images/SalesOrder/salesorder_images/".$salesOrderRec->purchase_image);
						}
					}
						
					// Get Count of images
					$count = count($_FILES['purchase_order_image']['name']);			
					
					for($ctr=0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['purchase_order_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						if($ctr == 0){
							$orderInfo['purchase_image'] = "SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$orderInfo['purchase_image'] .= ", SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}
						$_FILES['purchase_order_image[]']['name'] = "SOTP-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['purchase_order_image[]']['type'] = $_FILES['purchase_order_image']['type'][$ctr];
						$_FILES['purchase_order_image[]']['tmp_name'] = $_FILES['purchase_order_image']['tmp_name'][$ctr];
						$_FILES['purchase_order_image[]']['error'] = $_FILES['purchase_order_image']['error'][$ctr];
						$_FILES['purchase_order_image[]']['size'] = $_FILES['purchase_order_image']['size'][$ctr];	
						
						// upload the picture
						$this->upload->initialize($config);		
						$res = $this->upload->do_upload('purchase_order_image[]');				
						$fname = $this->upload->data();
					}
				}
			}
			
			// Update Sales Order Record with images names
			$res = $this->Generic_model->updateData('sales_order',$orderInfo,array('sales_order_id'=>$sales_order_id));
					
			// Send Push Notifications, create notification, push ackowledgement emails 
			$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
			$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
			$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
			
			if(count($user_list)>0){
				$push_noti['fcmId_android'] = $user_list->fcmId_android;
				$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
			}else{
				$push_noti['fcmId_android'] ="";
				$push_noti['fcmId_iOS'] = "";   
			}
			
			if(count($user_report_to) >0){
				$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
				$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
			}else{
				$push_noti['fcmId_android_report_to'] = "";
				$push_noti['fcmId_iOS_report_to'] = "";
			}
			
			$push_noti['sales_order_id'] = $sales_order_id;
			$push_noti['user_id'] = $user_id;
			$push_noti['subject'] = "A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName." ";
			$this->PushNotifications->SalesOrder_notifications($push_noti);

			$latest_val['module_id'] = $sales_order_id;
			$latest_val['module_name'] = "SalesOrder";
			$latest_val['user_id'] = $user_id;
			$latest_val['created_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->insertData("update_table",$latest_val);
			
			$productCheck=0;
			$extraProducts = array();
			$pi=0;

			// Sales Order Product information - save products for sales orders
			$products = $fdata->products;
			
			foreach($products as $product){
				
				if($product->ListPrice != '' || $product->ListPrice != NULL){
					$Product_id = $product->product_id;
					
					// Get Contracts Information if the contract id is provided
					if($fdata->contract_id != ''){
						$c_p_chk = $this->db->query("select * from contract_products where Contract=".$fdata->contract_id." and Product=".$Product_id)->row();				  
						if(count($c_p_chk)<=0){
							$productCheck++;
							$extraProducts[$pi]['pid'] = $Product_id;
							$extraProducts[$pi]['pdis'] = $product->Discount;
							$pi++;
						}
					}
					
					// Product Information if the product id is provided
					if($Product_id == NULL || $Product_id == ""){
						$product_code = "";
					}else{
						$product_details = $this->db->query("select * from product_master where product_id =".$Product_id)->row();
						$product_code = $product_details->product_code;
					}
					
					$weight = (int)$product_details->Weight;
					$quantity = (int)$product->Quantity;
					$weight_in_kgs = $weight * $quantity;
					$weight_in_mt = $weight_in_kgs / 1000;
					
					$productData['kgs'] = $weight_in_kgs;
					$productData['mt'] = $weight_in_mt;
					
					$productData['sales_order_id'] = $sales_order_id;						
					$productData['Product'] = $Product_id;
					$productData['Productcode'] = $product_code;
					$productData['Quantity'] = $product->Quantity;												

					if($product->sales_order_products_id != '' || $product->sales_order_products_id != NULL){
						$productData['modified_by'] = $user_id;							
						$productData['modified_date_time'] = date("Y-m-d H:i:s");
						// if YES - then update the record
						$ok = $this->Generic_model->updateData("sales_order_products",$productData, array('sales_order_products_id' => $product->sales_order_products_id));
					}else{
						$productData['created_by'] = $user_id;
						$productData['created_date_time'] = date("Y-m-d H:i:s");
						// Insert a new record for Table: sales_order_products
						$ok = $this->Generic_model->insertData("sales_order_products",$productData);
					}						
				}	
			}
			
			// Sales Persons Third Party Sales Orders Product Data
			if(count($fdata->salesPersonsProducts) > 0){
				foreach($fdata->salesPersonsProducts as $spProduct){
					$spProduct->sales_order_id = $sales_order_id;
					if($spProduct->tp_sales_order_sales_person_distributors_id != '' || $spProduct->tp_sales_order_sales_person_distributors_id != NULL){
						$spProduct->modified_by = $user_id;
						$spProduct->modified_datetime = date("Y-m-d H:i:s");						
						// if YES - then update the record
						$ok = $this->Generic_model->updateData("tp_sales_order_sales_person_distributors",$spProduct, array('tp_sales_order_sales_person_distributors_id' => $spProduct->tp_sales_order_sales_person_distributors_id));
					}else{
						$spProduct->created_by = $user_id;
						$spProduct->created_datetime = date("Y-m-d H:i:s");
						
						// if NO - then insert a new record for Table: tp_sales_order_sales_person_distributors
						$ok = $this->Generic_model->insertData("tp_sales_order_sales_person_distributors",$spProduct);
					}						
				}
			}
			
			// Send Email Acknowledgement & Notificaiton to the higher level
			// Get Sales Order Products List
			$sales_product_list = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id = ".$sales_order_id)->result();				
			
			// Get Customer List
			$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
			
			$email = $user_list->email;
			$to = $email;
			$subject = "New Sales Order Created";
			$data['name'] = ucwords($user_list->name);
			$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
			<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
			<thead>
			<tr >
			<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
			<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
			<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
			<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
			<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
			</tr></thead>";

			if(count($sales_product_list) >0){
				foreach($sales_product_list as $sales_values){
					$data['message'].=  "<tbody><tr>
					<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
					<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
					<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
					<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
					<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
				}
			}

			$data['message'].= "</table><br/>";  

			// Push email
			$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
			
			// Get User's higher authority to send email notification
			$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
			
			if(count($user_report_to) >0){
				$email = $user_report_to->email;
				$to = $email;
				$subject = "New Sales Order created";
				$data['name'] = ucwords($user_report_to->name);
				$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
				<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
				<thead>
				<tr >
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
				</tr></thead>";

				if(count($sales_product_list) >0){
					foreach($sales_product_list as $sales_values){
						$data['message'].=  "<tbody><tr>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
					}
				}

				$data['message'].= "</table><br/>";  
				$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
			}

			$param_noti['notiffication_type'] = "SalesOrder";
			$param_noti['notiffication_type_id'] = $sales_order_id;
			$param_noti['user_id'] = $user_id;
			$param_noti['subject'] = " A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName."";
			$this->Generic_model->insertData("notiffication",$param_noti);	
			
			// Check if the contract_id is provided or no.
			// If provided then update data accordingly
			if($fdata->contract_id != '' && $fdata->contract_id != NULL){
				// Check product
				if($prodct_chk==0){
					$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);						
				}else{
					if(count($extraProducts)>0){
						$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();									
						$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

						$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
						if(count($rol_discount)>0){
							$dchk=0;
							foreach($extraProducts as $result){
								//print_r($result);exit;
								if($rol_discount->dis_limit<$result['pdis'])
								$dchk++;									
							}

							if($dchk==0){
								$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
							}else{
								$data_ins['approval_type'] = 'SalesOrder';
								$data_ins['approval_type_id'] = $sales_order_id;
								$data_ins['status'] = 3;
								$data_ins['datetime'] = date('Y-m-d H:i:s');
								$data_ins['assigned_to'] = $nex_report->role_reports_to;
								$data_ins['comments'] = '';
								$data_ins['created_by'] = $user_id;
								$data_ins['modified_by'] = $user_id;
								$data_ins['created_datetime'] = date('Y-m-d H:i:s');
								$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
								$ok = $this->Generic_model->insertData("approval_process",$data_ins);
								if($ok)
									$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
							}
						}
					}
				}
			}else{
				$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
				$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

				$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
				if(count($rol_discount)>0){
					$dchk=0;
					$productCount = count($fdata->products);
					for($j=0;$j<$productCount;$j++){
						if($rol_discount->dis_limit<$fdata->products[$j]->Discount)
						$dchk++;								
					}
					if($dchk==0){							
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
					}else{							
						$data_ins['approval_type'] = 'SalesOrder';
						$data_ins['approval_type_id'] = $sales_order_id;
						$data_ins['status'] = 3;
						$data_ins['datetime'] = date('Y-m-d H:i:s');
						$data_ins['assigned_to'] = $nex_report->role_reports_to;
						$data_ins['comments'] = '';
						$data_ins['created_by'] = $user_id;
						$data_ins['modified_by'] = $user_id;
						$data_ins['created_datetime'] = date('Y-m-d H:i:s');
						$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
						$ok = $this->Generic_model->insertData("approval_process",$data_ins);
						if($ok)
							$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
					}
				}	
			}

			$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
			$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>'sales_order_by_third_party'));
			
		}
		// Sales Order by Third Party - Edit - Closed		
		
		// Create Sales Order by Direct Customer - EDIT
		if($fdata->requestname == 'sales_order_by_direct_party_edit') {
			
			// Get the old record data 
			$salesOrderRec = $this->Generic_model->getSingleRecord('sales_order', array('sales_order_id' => $fdata->sales_order_id));
			
			$customer_id = $orderInfo['Customer'] = $fdata->Customer;
						
			if($customer_id != "" || $customer_id != ""){
				$customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
				$SalesOrganisation = $customer_details->SalesOrganisation;
				$DistributionChannel = $customer_details->DistributionChannel;
				
				if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
					$orderInfo['SalesOrganisation'] = "";
				}else{
					$orderInfo['SalesOrganisation'] = $SalesOrganisation;
				}
				if($DistributionChannel == ""|| $DistributionChannel == NULL){
					$orderInfo['DistributionChannel'] = "";
				}else{
					$orderInfo['DistributionChannel'] = $DistributionChannel;
				}					
            }
			
			$orderInfo['OrderType'] = $fdata->OrderType;
			$orderInfo['sales_order_dealer_contact_id'] = $fdata->sales_order_dealer_contact_id;
			$orderInfo['orc_details'] = $fdata->orc_details;            
            $orderInfo['Division'] =$fdata->Division;
            $orderInfo['remarks'] = $fdata->remarks;
            $orderInfo['Soldtopartycode'] = $fdata->Soldtopartycode;
            $orderInfo['Ponumber'] = $this->$fdata->Ponumber;
            $orderInfo['Shiptopartycode'] = $fdata->Shiptopartycode;
            $orderInfo['BilltopartyCode'] = $fdata->BilltopartyCode;
			$orderInfo['expected_order_dispatch_date'] = $fdata->expected_order_dispatch_date;
            $orderInfo['CashDiscount'] = $fdata->CashDiscount;
            $orderInfo['withoutdiscountamount'] = $fdata->withoutdiscountamount;
			$orderInfo['Freight'] = $fdata->Freight;
			
			if($orderInfo['Freight'] == "other"){
				$orderInfo['freight_amount'] = $freight_amount = $fdata->other_freight;
            }else{
				if($orderInfo['Freight'] == "" || $orderInfo['Freight'] == NULL){
					$orderInfo['freight_amount'] = $freight_amount = 0;
				}else{
					$freight_list = $this->db->query("select * from freight_tbl where freight_id ='".$orderInfo['Freight']."'")->row();
					$orderInfo['freight_amount'] = $freight_amount = $freight_list->price;
				}              
            }
			
			$orderInfo['discountAmount'] = $fdata->discountAmount;
			$orderInfo['Total'] = $fdata->TotalPrice;
			$orderInfo['order_status'] = $fdata->order_status;
			$orderInfo['order_status_comments'] = $fdata->order_status_comments;
            $orderInfo['created_by'] = $user_id;
            $orderInfo['modified_by'] = $user_id;
            $orderInfo['created_date_time'] = date("Y-m-d H:i:s");
            $orderInfo['modified_date_time'] = date("Y-m-d H:i:s");
	
			// update sales order record 
			$sales_order_id = $this->Generic_model->updateData("sales_order",$orderInfo,array("sales_order_id"=>$fdata->sales_order_id));
			
			// Configure required for image uploads
			// Configure the path for Sales Order Concern Images
			$config['upload_path'] = './images/SalesOrder/salesorder_images/';
			$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';		
			
			// Purchase Order Image		
			if(isset($_FILES['purchase_order_image'])){							

				if($_FILES['purchase_order_image']['name'][0] != '' || $_FILES['purchase_order_image']['name'][0] != NULL){
					
					// Check if the record got an old image 
					if($salesOrderRec->purchase_image != '' || $salesOrderRec->purchase_image != NULL){
						if(file_exists("images/SalesOrder/salesorder_images/".$salesOrderRec->purchase_image)){
							// if YES - Delete it from the storage
							unlink("images/SalesOrder/salesorder_images/".$salesOrderRec->purchase_image);
						}
					}
							
					// Get Count of images
					$count = count($_FILES['purchase_order_image']['name']);			
					
					for($ctr=0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['purchase_order_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						if($ctr == 0){
							$imgData['purchase_image'] = "SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['purchase_image'] .= ", SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						}
						$_FILES['purchase_order_image[]']['name'] = "SO-".$sales_order_id."-POI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['purchase_order_image[]']['type'] = $_FILES['purchase_order_image']['type'][$ctr];
						$_FILES['purchase_order_image[]']['tmp_name'] = $_FILES['purchase_order_image']['tmp_name'][$ctr];
						$_FILES['purchase_order_image[]']['error'] = $_FILES['purchase_order_image']['error'][$ctr];
						$_FILES['purchase_order_image[]']['size'] = $_FILES['purchase_order_image']['size'][$ctr];							
						
						
						// upload the picture
						$this->upload->initialize($config);		
						$res = $this->upload->do_upload('purchase_order_image[]');				
						$fname = $this->upload->data();
					}
				}else{
					$imgData['purchase_image'] = "";
				}
			}
			
			// Complaints Image		
			if(isset($_FILES['complaints_image'])){	
			
				if($_FILES['complaints_image']['name'][0] != '' || $_FILES['complaints_image']['name'][0] != NULL){
					
					// Check if the record got an old image 
					if($salesOrderRec->complaints_image != '' || $salesOrderRec->complaints_image != NULL){
						if(file_exists("images/SalesOrder/salesorder_images/".$salesOrderRec->complaints_image)){
							// if YES - Delete it from the storage
							unlink("images/SalesOrder/salesorder_images/".$salesOrderRec->complaints_image);
						}
					}
											
					// Get Count of images
					$count = count($_FILES['complaints_image']['name']);

					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['complaints_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);	
						
						if($ctr == 0){
							$imgData['complaints_image'] = "SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['complaints_image'] .= ", SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						}
										
						$_FILES['complaints_image[]']['name'] = "SO-".$sales_order_id."-CI-".date(YmdHis).$ctr.".".$ext;
						$_FILES['complaints_image[]']['type'] = $_FILES['complaints_image']['type'][$ctr];
						$_FILES['complaints_image[]']['tmp_name'] = $_FILES['complaints_image']['tmp_name'][$ctr];
						$_FILES['complaints_image[]']['error'] = $_FILES['complaints_image']['error'][$ctr];
						$_FILES['complaints_image[]']['size'] = $_FILES['complaints_image']['size'][$ctr];							
						
						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('complaints_image[]');
						$fname = $this->upload->data();
					}			
				}else{
					$imgData['complaints_image'] = "";
				}
			}	
					
			// Payment Instrument Image		
			if(isset($_FILES['payment_instrument_image'])){	

				if($_FILES['payment_instrument_image']['name'][0] != '' || $_FILES['payment_instrument_image']['name'][0] != NULL){				
				
					// Check if the record got an old image 
					if($salesOrderRec->payment_image != '' || $salesOrderRec->payment_image != NULL){
						if(file_exists("images/SalesOrder/salesorder_images/".$salesOrderRec->payment_image)){
							// if YES - Delete it from the storage
							unlink("images/SalesOrder/salesorder_images/".$salesOrderRec->payment_image);
						}
					}
											
					// Get Count of images
					$count = count($_FILES['payment_instrument_image']['name']);			
					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['payment_instrument_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						
						if($ctr == 0){
							$imgData['payment_image'] = "SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['payment_image'] .= ", SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						}
						
						$_FILES['payment_instrument_image[]']['name'] = "SO-".$sales_order_id."-PII-".date(YmdHis).$ctr.".".$ext;
						$_FILES['payment_instrument_image[]']['type'] = $_FILES['payment_instrument_image']['type'][$ctr];
						$_FILES['payment_instrument_image[]']['tmp_name'] = $_FILES['payment_instrument_image']['tmp_name'][$ctr];
						$_FILES['payment_instrument_image[]']['error'] = $_FILES['payment_instrument_image']['error'][$ctr];
						$_FILES['payment_instrument_image[]']['size'] = $_FILES['payment_instrument_image']['size'][$ctr];

						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('payment_instrument_image[]');
						$fname = $this->upload->data();				
					}
				}else{
					$imgData['payment_image'] = "";
				}					
			}			
			
			// Transfer Receipt Image
			if(isset($_FILES['transfer_receipt_image'])){		
			
				if($_FILES['transfer_receipt_image']['name'][0] != '' || $_FILES['transfer_receipt_image']['name'][0] != NULL){					
				
					// Check if the record got an old image 
					if($salesOrderRec->transfer_image != '' || $salesOrderRec->transfer_image != NULL){
						if(file_exists("images/SalesOrder/salesorder_images/".$salesOrderRec->transfer_image)){
							// if YES - Delete it from the storage
							unlink("images/SalesOrder/salesorder_images/".$salesOrderRec->transfer_image);
						}
					}
				
					// Get Count of images
					$count = count($_FILES['transfer_receipt_image']['name']);			
					for($ctr = 0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['transfer_receipt_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);	
						
						if($ctr == 0){
							$imgData['transfer_image'] = $_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;
						}else{
							$imgData['transfer_image'] .= ", ".$_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;
						}
						
						$_FILES['transfer_receipt_image[]']['name'] = "SO-".$sales_order_id."-TI-".date(YmdHis).$ctr.".".$ext;				
						$_FILES['transfer_receipt_image[]']['type'] = $_FILES['transfer_receipt_image']['type'][$ctr];
						$_FILES['transfer_receipt_image[]']['tmp_name'] = $_FILES['transfer_receipt_image']['tmp_name'][$ctr];
						$_FILES['transfer_receipt_image[]']['error'] = $_FILES['transfer_receipt_image']['error'][$ctr];
						$_FILES['transfer_receipt_image[]']['size'] = $_FILES['transfer_receipt_image']['size'][$ctr];							
						
						// upload the picture
						$this->upload->initialize($config);		
						$this->upload->do_upload('transfer_receipt_image[]');
						$fname = $this->upload->data();
					}			
				}else{
					$imgData['transfer_image'] = "";
				}
			}			
			
			// Update Sales Order Record with images names
			if(isset($imgData)){
				$res = $this->Generic_model->updateData('sales_order',$imgData,array('sales_order_id'=>$sales_order_id));
			}
								
			// Send Push Notifications, create notification, push ackowledgement emails 
			if($sales_order_id != "" || $sales_order_id != NULL){
				
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
								
				if(count($user_list)>0){
					$push_noti['fcmId_android'] = $user_list->fcmId_android;
					$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
				}else{
					$push_noti['fcmId_android'] ="";
					$push_noti['fcmId_iOS'] = "";   
				}
				
				if(count($user_report_to) >0){
					$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
					$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
				}else{
					$push_noti['fcmId_android_report_to'] = "";
					$push_noti['fcmId_iOS_report_to'] = "";
				}
				
				$push_noti['sales_order_id'] = $sales_order_id;
				$push_noti['user_id'] = $user_id;
				$push_noti['subject'] = "A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName." ";
				$this->PushNotifications->SalesOrder_notifications($push_noti);				
				
				$latest_val['module_id'] = $fdata->sales_order_id;
				$latest_val['module_name'] = "SalesOrder";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
				
				$productCheck=0;
				$extraProducts = array();
				$pi=0;				

				// Sales Order Product information - save products for sales orders
				$products = $fdata->products;
								
				foreach($products as $product){
										
					if($product->ListPrice != '' || $product->ListPrice != NULL){
						$Product_id = $product->product_id;
						
						// Get Contracts Information if the contract id is provided
						if($fdata->contract_id != ''){
							$c_p_chk = $this->db->query("select * from contract_products where Contract=".$fdata->contract_id." and Product=".$Product_id)->row();				  
							if(count($c_p_chk)<=0){
								$productCheck++;
								$extraProducts[$pi]['pid'] = $Product_id;
								$extraProducts[$pi]['pdis'] = $product->Discount;
								$pi++;
							}
						}
						
						// Product Information if the product id is provided
						if($Product_id == NULL || $Product_id == ""){
							$product_code = "";
						}else{
							$product_details = $this->db->query("select * from product_master where product_id =".$Product_id)->row();
							$product_code = $product_details->product_code;
						}
						
						$weight = (int)$product_details->Weight;
						$quantity = (int)$product->Quantity;
						$weight_in_kgs = $weight * $quantity;
						$weight_in_mt = $weight_in_kgs / 1000;
						
						$productData['sales_order_id'] = $sales_order_id;
						$productData['ListPrice'] = $product->ListPrice;
						$productData['Product'] = $Product_id;
						$productData['Productcode'] = $product_code;
						$productData['plant_id'] = $product->plant_id;
						$productData['Quantity'] = $product->Quantity;
						$productData['kgs'] = $weight_in_kgs;
						$productData['mt'] = $weight_in_mt;
						$productData['Discount'] = $product->Discount;
						$productData['Subtotal'] = $product->Subtotal;
						$productData['created_by'] = $user_id;
						$productData['modified_by'] = $user_id;
						$productData['created_date_time'] = date("Y-m-d H:i:s");
						$productData['modified_date_time'] = date("Y-m-d H:i:s");					

						// Check if the sales_order_product_id exists
						if($product->sales_order_products_id != '' || $product->sales_order_products_id != NULL){
							// if YES - then update the record
							$ok = $this->Generic_model->updateData("sales_order_products",$productData,array('sales_order_products_id'=>$fdata->sales_order_products_id));
						}else{
							// if NO - then insert the new record
							$ok = $this->Generic_model->insertData("sales_order_products",$productData);						
						}
					}		
				}
				
				// Send Email Acknowledgement & Notificaiton to the higher level
				// Get Sales Order Products List
				$sales_product_list = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id = ".$sales_order_id)->result();				
				
				// Get Customer List
				$customer_list = $this->db->query("select * from customers where customer_id =".$fdata->Customer)->row();
				
				$email = $user_list->email;
				$to = $email;
				$subject = "New Sales Order Created";
				$data['name'] = ucwords($user_list->name);
				$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
				<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
				<thead>
				<tr >
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
				</tr></thead>";

				if(count($sales_product_list) >0){
					foreach($sales_product_list as $sales_values){
						$data['message'].=  "<tbody><tr>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
					}
				}

				$data['message'].= "</table><br/>";  

				// Push email
				$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				
				// Get User's higher authority to send email notification
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				
				if(count($user_report_to) >0){
					$email = $user_report_to->email;
					$to = $email;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($user_report_to->name);
					$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);						
				
				}

				$param_noti['notiffication_type'] = "SalesOrder";
				$param_noti['notiffication_type_id'] = $sales_order_id;
				$param_noti['user_id'] = $user_id;
				$param_noti['subject'] = " A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName."";
				$this->Generic_model->insertData("notiffication",$param_noti);	
				
				// Check if the contract_id is provided or no.
				// If provided then update data accordingly
				if($fdata->contract_id != '' && $fdata->contract_id != NULL){
					// Check product
					if($prodct_chk==0){
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);						
					}else{
						if(count($extraProducts)>0){
							$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();									
							$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

							$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
							if(count($rol_discount)>0){
								$dchk=0;
								foreach($extraProducts as $result){
									//print_r($result);exit;
									if($rol_discount->dis_limit<$result['pdis'])
									$dchk++;									
								}

								if($dchk==0){
									$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
								}else{
									$data_ins['approval_type'] = 'SalesOrder';
									$data_ins['approval_type_id'] = $sales_order_id;
									$data_ins['status'] = 3;
									$data_ins['datetime'] = date('Y-m-d H:i:s');
									$data_ins['assigned_to'] = $nex_report->role_reports_to;
									$data_ins['comments'] = '';
									$data_ins['created_by'] = $user_id;
									$data_ins['modified_by'] = $user_id;
									$data_ins['created_datetime'] = date('Y-m-d H:i:s');
									$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
									$ok = $this->Generic_model->insertData("approval_process",$data_ins);
									if($ok)
										$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
								}
							}
						}
					}
				}else{
					$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
					$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

					$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					if(count($rol_discount)>0){
						$dchk=0;
						$productCount = count($fdata->products);
						for($j=0;$j<$productCount;$j++){
							if($rol_discount->dis_limit<$fdata->products[$j]->Discount)
							$dchk++;								
						}
						if($dchk==0){							
							$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						}else{							
							$data_ins['approval_type'] = 'SalesOrder';
							$data_ins['approval_type_id'] = $sales_order_id;
							$data_ins['status'] = 3;
							$data_ins['datetime'] = date('Y-m-d H:i:s');
							$data_ins['assigned_to'] = $nex_report->role_reports_to;
							$data_ins['comments'] = '';
							$data_ins['created_by'] = $user_id;
							$data_ins['modified_by'] = $user_id;
							$data_ins['created_datetime'] = date('Y-m-d H:i:s');
							$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
							$ok = $this->Generic_model->insertData("approval_process",$data_ins);
							if($ok)
								$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
						}
					}	
				}

				$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
				$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>'sales_order_by_direct_party'));
			
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}
		// Sales Order by Direct Party - Edit - Closed
		
		// Payment Collection Multipart - insert - START
		if($fdata->requestname == 'payment_collection_insert'){
						
			$paymentMode = $fdata->payment_mode;
			$user_id = $fdata->requesterid;
			
			$pcData = (array)$fdata;
			
			if($paymentMode == "Cash" || $paymentMode == "Cheque" || $paymentMode == "Online") {
				// common details
				$pcData['owner'] = $user_id;
				$pcData['created_by'] = $user_id;
				$pcData['modified_by'] = $user_id;
				$pcData['created_date_time'] = date("Y-m-d H:i:s");
				$pcData['modified_date_time'] = date("Y-m-d H:i:s");
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}	

			unset($pcData['requesterid']);
			unset($pcData['requestname']);
			unset($pcData['insert_by']);
			unset($pcData['sales_calls_temp_id']);
			
			$payment_collection_id = $this->Generic_model->insertDataReturnId("payment_collection",$pcData);
			
			if($payment_collection_id != "" || $payment_collection_id != null){
				
				// Check if the Sales Order is being raised by a Sales Call
				if(isset($fdata->insert_by)){
					
					if($fdata->insert_by == 'Sales Call'){
						
						$tempData = array();
						$new = 0;
						
						// Check if the sales_call_temp_id exists
						if(isset($fdata->sales_calls_temp_id)){
							if($fdata->sales_calls_temp_id != '' || $fdata->sales_calls_temp_id != NULL){
								// Update sales calls temp table with payment_collection_id for the respective sales call temp id					
								$tempData['payment_collection_id'] = $payment_collection_id;
								$tempData['modified_by']=$user_id;								
								$tempData['modified_datetime']=date("Y-m-d H:i:s");
								$this->Generic_model->updateData('sales_call_temp_table', $tempData, array('sales_calls_temp_id' => $fdata->sales_calls_temp_id));
							}else{
								$new = 1;
							}						
						}else{
							$new = 1;					
						}						
						
						if($new == 1){
							// Insert a payment collection record id in the sales_call_temp_table DB										
							$tempData['sales_call_id'] = 0;
							$tempData['payment_collection_id'] = $payment_collection_id;
							$tempData['created_by'] = $user_id;
							$tempData['modified_by']=$user_id;
							$tempData['created_datetime']=date("Y-m-d H:i:s");
							$tempData['modified_datetime']=date("Y-m-d H:i:s");
							$result=$this->Generic_model->insertDataReturnId('sales_call_temp_table',$tempData);
						}	
					}
				}
				
				// Configure required for image uploads
				// Configure the path for Sales Order Concern Images
				$config['upload_path'] = './images/Payment/';
				$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';	
				
				// Purchase Order Image		
				if(isset($_FILES['payment_image'])){	

					if($_FILES['payment_image']['name'][0] != '' || $_FILES['payment_image']['name'][0] != NULL){
							
						// Get Count of images
						$count = count($_FILES['payment_image']['name']);			
						
						for($ctr=0; $ctr<$count; $ctr++){
							
							// Get the name of the file
							$f_name = $_FILES['payment_image']['name'][$ctr];
							$ext = substr(strrchr($f_name, '.'),1);				
							
							if($ctr == 0){
								$imgData['payment_image'] = "PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
							}else{
								$imgData['payment_image'] .= ", PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
							}
							$_FILES['payment_image[]']['name'] = "PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
							$_FILES['payment_image[]']['type'] = $_FILES['payment_image']['type'][$ctr];
							$_FILES['payment_image[]']['tmp_name'] = $_FILES['payment_image']['tmp_name'][$ctr];
							$_FILES['payment_image[]']['error'] = $_FILES['payment_image']['error'][$ctr];
							$_FILES['payment_image[]']['size'] = $_FILES['payment_image']['size'][$ctr];							
							
							// upload the picture
							$this->upload->initialize($config);		
							$res = $this->upload->do_upload('payment_image[]');				
							$fname = $this->upload->data();
						}
					}else{
						$imgData['payment_image'] = "";
					}
				}
				
				if($imgData['payment_image'] != ""){
					// Update Sales Order Record with images names
					$res = $this->Generic_model->updateData('payment_collection',$imgData,array('payment_collection_id'=>$payment_collection_id));
				}
				
				$check_update_list = $this->db->query("select * from update_table where module_id ='".$payment_collection_id."' and module_name ='Payment_Collection'")->row();
				
				if(count($check_update_list)>0){
					$latest_val['user_id'] = $user_id;
					$latest_val['created_date_time'] = date("Y-m-d H:i:s");
					$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $payment_collection_id,'module_name'=>'Payment_Collection'));
				}else{
					$latest_val['module_id'] = $payment_collection_id;
					$latest_val['module_name'] = "Payment_Collection";
					$latest_val['user_id'] = $user_id;
					$latest_val['created_date_time'] = date("Y-m-d H:i:s");
					$this->Generic_model->insertData("update_table",$latest_val);
				}
				
				// Get return data
				$return_data = $this->all_tables_records_view("payment_collection",$payment_collection_id);
				
				$this->response(array('code'=>'200','message' => 'Payment Successfull inserted','result'=>$return_data,'requestname'=>'payment_collection_insert'));
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}
		// Payment Collection Multipart - insert - CLOSE
		
		// Payment Collection Multipart - edit - START
		if($fdata->requestname == 'payment_collection_edit'){
						
			// Get the old record data 
			$paymentCollectionRec = $this->Generic_model->getSingleRecord('payment_collection', array('payment_collection_id' => $fdata->payment_collection_id));
			
			$payment_collection_id = $fdata->payment_collection_id;
			
			$paymentMode = $fdata->payment_mode;
			$user_id = $fdata->requesterid;
			
			$pcData = (array)$fdata;
			
			if($paymentMode == "Cash" || $paymentMode == "Cheque" || $paymentMode == "Online") {
				// common details
				$pcData['owner'] = $user_id;
				$pcData['created_by'] = $user_id;
				$pcData['modified_by'] = $user_id;
				$pcData['created_date_time'] = date("Y-m-d H:i:s");
				$pcData['modified_date_time'] = date("Y-m-d H:i:s");
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}	

			unset($pcData['requesterid']);
			unset($pcData['requestname']);
			unset($pcData['insert_by']);
			unset($pcData['sales_calls_temp_id']);
			
			// Payment Image		
			if(isset($_FILES['payment_image'])){	
			
				// Configure required for image uploads
				// Configure the path for Sales Order Concern Images
				$config['upload_path'] = './images/Payment/';
				$config['allowed_types'] = 'jpg|JPG|png|PNG|JPEG|jpeg';	

				if($_FILES['payment_image']['name'][0] != '' || $_FILES['payment_image']['name'][0] != NULL){
					
					// Check if the record got an old image 
					if($paymentCollectionRec->payment_image != '' || $paymentCollectionRec->payment_image != NULL){
						//echo FCPATH;
						if(file_exists("images/Payment/".$paymentCollectionRec->payment_image)){							
							// if YES - Delete it from the storage
							unlink("images/Payment/".$paymentCollectionRec->payment_image);						
						}
					}
						
					// Get Count of images
					$count = count($_FILES['payment_image']['name']);			
					
					for($ctr=0; $ctr<$count; $ctr++){
						
						// Get the name of the file
						$f_name = $_FILES['payment_image']['name'][$ctr];
						$ext = substr(strrchr($f_name, '.'),1);				
						
						if($ctr == 0){
							$pcData['payment_image'] = "PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
						}else{
							$pcData['payment_image'] .= ", PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
						}
						$_FILES['payment_image[]']['name'] = "PC-".$payment_collection_id."-".date(YmdHis).$ctr.".".$ext;
						$_FILES['payment_image[]']['type'] = $_FILES['payment_image']['type'][$ctr];
						$_FILES['payment_image[]']['tmp_name'] = $_FILES['payment_image']['tmp_name'][$ctr];
						$_FILES['payment_image[]']['error'] = $_FILES['payment_image']['error'][$ctr];
						$_FILES['payment_image[]']['size'] = $_FILES['payment_image']['size'][$ctr];							
						
						// upload the picture
						$this->upload->initialize($config);		
						$res = $this->upload->do_upload('payment_image[]');				
						$fname = $this->upload->data();
					}
				}
			}
			
			// Update Sales Order Record with images names
			$res = $this->Generic_model->updateData('payment_collection',$pcData,array('payment_collection_id' => $fdata->payment_collection_id));
			
			$check_update_list = $this->db->query("select * from update_table where module_id ='".$fdata->payment_collection_id."' and module_name ='Payment_Collection'")->row();
			
			if(count($check_update_list)>0){
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $fdata->payment_collection_id,'module_name'=>'Payment_Collection'));
			}else{
				$latest_val['module_id'] = $fdata->payment_collection_id;
				$latest_val['module_name'] = "Payment_Collection";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
			}
			
			// Get return data
			$return_data = $this->all_tables_records_view("payment_collection",$fdata->payment_collection_id);
			
			$this->response(array('code'=>'200','message' => 'Payment Successfull inserted','result'=>$return_data,'requestname'=>'payment_collection_insert'));
			
		}		
	}
	// Multipart API Call Close
	

	   if(isset($_POST['requestparameters']))
	   {
		 $fdata=json_decode($_POST['requestparameters']);
    
         $requestname = $fdata->requestname;
         $user_id=$fdata->requesterid;
	      if($fdata->requestname=="profile_picture_upload")
	      {
			$this->load->library('upload');
			//$path = './uploads/';
			$config['upload_path']='./images/profile_image/';
			$config['allowed_types'] = 'jpg|JPG|png|PNG|csv|jpeg';
			$_FILES['file_i']['name'] = $_FILES['file_i']['name'];
			$_FILES['file_i']['type'] = $_FILES['file_i']['type'];
			$_FILES['file_i']['tmp_name']=$_FILES['file_i']['tmp_name'];
			$_FILES['file_i']['error'] = $_FILES['file_i']['error'];
			$_FILES['file_i']['size'] = $_FILES['file_i']['size'];

			$this->upload->initialize($config);
			$this->upload->do_upload('file_i');
			$fname=$this->upload->data();
			$fileName=$fname['file_name'];

			//move_uploaded_file($_FILES["file_i"]["tmp_name"],$path.$fileName);
			$data['profile_image']=$fileName;
			$result=$this->Generic_model->updateData('users',$data,array('user_id'=>$user_id));
			if($result){
				$this->response(array('code'=>200,'message'=>'profile_image uploaded successfully', 'result'=>$data, 'requestname'=>$requestname));
			}else{
				$this->response(array('code'=>'404','message'=>'failed to upload'),200);
			}
	      }else if($fdata->requestname=="expenses_insert"){
            $config['upload_path'] = './images/expenses'; //give the path to upload the image in folder
        	$config['allowed_types'] = 'jpg|JPG|png|PNG|csv|pdf|PDF|TXT|txt|GIF|gif|JPEG|jpeg';
            

              $checking_id = $this->db->query("select * from expenses order by expenses_id DESC")->row();
              if($checking_id->expenses_number == NULL || $checking_id->expenses_number == ""){
                  $expenses_number_id = "EXD-00001";
              }else{
                  $opp_check = trim($checking_id->expenses_number);
                  $checking_op_id =  substr($opp_check, 4);
                  if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
                      $opp_id_last_inc = (++$checking_op_id);
                      $expenses_number_id= "EXD-".$opp_id_last_inc;
                  }else{
                      $expenses_number_id = (++$opp_check);
                  } 

              }

              $param_1['expenses_number'] = $expenses_number_id;
              $param_1['expenses_type'] = $fdata->expenses_type;
              $param_1['expenses_name'] = $fdata->expenses_name;
              $param_1['ta_da_id'] = $fdata->ta_da_id;
              
              $param_1['price'] = $fdata->price;
              $param_1['expensesdate'] = date("Y-m-d",strtotime($fdata->expensesdate));
              $param_1['expenses_owner'] = $fdata->user_id;
              $param_1['created_by'] = $fdata->user_id;
              $param_1['modified_by'] = $fdata->user_id;
              $param_1['created_date_time'] = date("Y-m-d H:i:s");
              $param_1['modified_date_time'] = date("Y-m-d H:i:s");
              $expenses_id = $this->Generic_model->insertDataReturnId("expenses",$param_1);
              $filesCount = count($_FILES['file_i']['name']);
              for ($i = 0; $i < $filesCount; $i++) {
                  $_FILES['file_i[]']['name'] = $_FILES['file_i']['name'][$i];
                  $_FILES['file_i[]']['type'] = $_FILES['file_i']['type'][$i];
                  $_FILES['file_i[]']['tmp_name'] = $_FILES['file_i']['tmp_name'][$i];
                  $_FILES['file_i[]']['error'] = $_FILES['file_i']['error'][$i];
                  $_FILES['file_i[]']['size'] = $_FILES['file_i']['size'][$i];
                  $value = $i;
                  $this->upload->initialize($config);
                  $this->upload->do_upload('file_i[]');
                  $fname = $this->upload->data();
                  $fileName[$i] = $fname['file_name'];
                  if($fileName[$i] != "" || $fileName[$i] != NULL){
                  $param_4['expenses_image'] = $fileName[$i];
                  $param_4['expenses_id'] = $expenses_id;
                  $file_save = $this->Generic_model->insertData("expenses_files",$param_4);
                }
              }
              // $string_version = implode(',', $fileName);
              // if($string_version != ""|| $string_version != NULL){
              //   $param_1['files'] = $string_version;
              // }

              $return_data = $this->all_tables_records_view("expenses",$expenses_id);
              if($expenses_id != "" || $expenses_id != null){
                 $this->response(array('code'=>'200','message' => 'Expenses Successfull inserted','result'=>$return_data,'requestname'=>$fdata->requestname));
              }else{
                 $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
              }

        	}else if($fdata->requestname=="geo_check_inout"){

            if ($fdata->type != "") {
                    $apk = $this->db->query("Select * from api_key")->row();
                  $api_key = $apk->api_name;
				  if($fdata->tracking_id !=NULL || $fdata->tracking_id!='')
				  {
					  $condition = array('tracking_id' => $fdata->tracking_id);
                  $existed = $this->Generic_model->getSingleRecord("geo_tracking", $condition);
				  }					  
                  
                  if ($fdata->installed_apps != "") {
                      $installed_apps = $fdata->installed_apps;
                  } else {
                      $installed_apps = '';
                  }

                   if ($fdata->type == "check_in_lat_lon" && $fdata->latlon != "0.0,0.0") {
                    $status = "3";

                    $latlong = explode(",", $fdata->latlon);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . trim($latlong[0]) . "," . trim($latlong[1]) . "&sensor=false&key=" . $api_key . "",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                    ));

                    $geocodeFromLatLong = curl_exec($curl);
                    curl_close($curl);
                    $output = json_decode($geocodeFromLatLong);
                    $status_1 = $output->status;

                    $check_in_place = ($status_1 == "OK") ? $output->results[1]->formatted_address : '';
                    $insert_geo['visit_type'] = $fdata->visit_type;
                    $insert_geo['user_id'] = $fdata->user_id;
                    $insert_geo['check_in_lat_lon'] = $fdata->latlon;
                    $insert_geo['route_path_lat_lon'] = $fdata->latlon;
                    $insert_geo['visit_date'] = $fdata->visit_date;
                    $insert_geo['check_in_time'] = $fdata->check_in_time;
                    $insert_geo['created_datetime'] = date("Y-m-d H:m:s");
                    $insert_geo['installed_app'] = $installed_apps;
                    $insert_geo['app_version'] = $fdata->app_version;
                    // $insert_geo['checkin_comment'] = $this->post('checkin_comment');
                    $insert_geo['check_in_place'] = $check_in_place;
                }else if ($fdata->type == "check_out_lat_lon" && $fdata->latlon != "0.0,0.0") {

                if ($fdata->route_path_lat_lon == NULL || $fdata->route_path_lat_lon == '' || $fdata->route_path_lat_lon == 'null') {

                    $update_geo['route_path_lat_lon'] = $fdata->latlon;
                } else {
                    $update_geo['route_path_lat_lon'] = $fdata->route_path_lat_lon . ":" . $fdata->latlon;
                }

                $latlong = explode(",", $this->post('latlon'));

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . trim($latlong[0]) . "," . trim($latlong[1]) . "&sensor=false&key=" . $api_key . "",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                ));

                $geocodeFromLatLong = curl_exec($curl);
                curl_close($curl);
                $output = json_decode($geocodeFromLatLong);
                $status_1 = $output->status;
                $check_out_place = ($status_1 == "OK") ? $output->results[1]->formatted_address : '';

                $status = "4";
                $update_geo['check_out_lat_lon'] = $fdata->latlon;
                $update_geo['check_out_time'] = $fdata->check_out_time;
                //$update_geo['distance'] = $this->post('distance');
                //$update_geo['polyline'] = $this->post('polyline');
                //$update_geo['route_snap'] = $this->post('route_snap');
                //$update_geo['route_snap_all'] = $this->post('route_snap_all');
                //$update_geo['route_snap_failure'] = $this->post('route_snap_failure');
                //$update_geo['google_direction'] = $this->post('google_direction');
                //$update_geo['google_direction_all'] = $this->post('google_direction_all');
                //$update_geo['google_direction_failure'] = $this->post('google_direction_failure');
                $update_geo['gps_status'] = $fdata->gps_status;
                $update_geo['pause'] = $fdata->pause;
                $update_geo['resume'] = $fdata->resume;

                $update_geo['check_out_place'] = $check_out_place;
                $update_geo['check_out_by'] = $fdata->check_out_by;
                //$update_geo['personal_uses_km'] = $this->post('personal_uses_km');
              }else{
                  $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
                }

                if (count($existed) > 0) {

                $inserted = $this->Generic_model->updateData('geo_tracking', $update_geo, $condition);

                $tracking_id = $fdata->tracking_id;


                /*  if ($rlatlon->vehicle_type == '2 Wheeler' || $rlatlon->vehicle_type == '4 Wheeler') {
                  $this->load->library('upload');

                  $config['upload_path'] = './uploads/geo_tracking/check_out/';
                  $config['allowed_types'] = 'jpg|JPG|png|PNG|csv|JPEG|jpeg';

                  $new_name = $tracking_id . '_' . $user_id . '_' . $_FILES["meter_reading_checkout_image"]['name'];

                  $config['file_name'] = $new_name;

                  $this->upload->initialize($config);
                  $this->upload->do_upload('meter_reading_checkout_image');
                  $fname = $this->upload->data();
                  $fileName = $fname['file_name'];
                  $path = base_url() . 'uploads/geo_tracking/check_out/' . $fileName;
                  } else {
                  $path = NULL;
                  }


                  $update_val['meter_reading_checkout_image'] = $path;
                  $update_val['meter_reading_checkout_text'] = $this->post('meter_reading_checkout_text');

                  $this->Generic_model->updateData('geo_tracking', $update_val, array("tracking_id" => $tracking_id)); */
              }else{
                $visit_date = strtotime($fdata->visit_date);
                $date = date('Y-m-d', $visit_date);
                //$visit_date=  DATE_FORMAT(visit_date,$date);//   date('Y-m-d',$this->post('visit_date'));
                $user_id = $fdata->user_id;
                $check_existed = $this->db->query("select * from geo_tracking where user_id = $user_id and DATE_FORMAT(visit_date,'%Y-%m-%d')='$date' ")->row();

                if ($check_existed->tracking_id == '') {
                    $inserted = $this->Generic_model->insertData('geo_tracking', $insert_geo);
                    $tracking_id = $this->db->insert_id();


                    /* if ($this->post('vehicle_type') == '2 Wheeler' || $this->post('vehicle_type') == '4 Wheeler') {
                      $this->load->library('upload');

                      $config['upload_path'] = './uploads/geo_tracking/check_in/';
                      $config['allowed_types'] = 'jpg|JPG|png|PNG|csv|JPEG|jpeg';

                      $new_name = $tracking_id . '_' . $user_id . '_' . $_FILES["meter_reading_checkin_image"]['name'];

                      $config['file_name'] = $new_name;

                      $this->upload->initialize($config);
                      $this->upload->do_upload('meter_reading_checkin_image');
                      $fname = $this->upload->data();
                      $fileName = $fname['file_name'];
                      $path = base_url() . 'uploads/geo_tracking/check_in/' . $fileName;
                      } else {
                      $path = NULL;
                      }

                      $update_val['meter_reading_checkin_image'] = $path;
                      $update_val['meter_reading_checkin_text'] = $this->post('meter_reading_checkin_text');
                      $update_val['vehicle_type'] = $this->post('vehicle_type');
                      $this->Generic_model->updateData('geo_tracking', $update_val, array("tracking_id" => $tracking_id)); */
                } else {

                    $tracking_id = $check_existed->tracking_id;
                    $checkin_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
                    $data['tracking_id'] = $tracking_id;
                    $data['meter_reading_checkin_image'] = $checkin_image->meter_reading_checkin_image;
                     $this->response(array('code'=>'200','message'=>'successfully! done', 'result'=>$data,'requestname'=>"geo_check_inout"));


                    //$this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'meter_reading_checkin_image' => $checkin_image->meter_reading_checkin_image), 200);
                }
            }
            if ($inserted) {

                if ($fdata->type == "check_in_lat_lon") {
                    $checkin_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
                    $data['tracking_id'] = $tracking_id;
                    $data['meter_reading_checkin_image'] = $checkin_image->meter_reading_checkin_image;
                     $this->response(array('code'=>'200','message'=>'successfully! done', 'result'=>$data,'requestname'=>"geo_check_inout"));

                    //$this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'meter_reading_checkin_image' => $checkin_image->meter_reading_checkin_image), 200);
                } else {
                    $checkout_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
                    $data['tracking_id'] = $tracking_id;
                    $data['check_out_time'] =  $checkout_image->check_out_time;
                    $data['distance'] = $checkout_image->distance;
                    $data['polyline'] = $checkout_image->polyline;
                    $data['meter_reading_checkout_image'] = $checkout_image->meter_reading_checkout_image;
                    $data['personal_uses_km'] = $checkout_image->personal_uses_km;



                     $this->response(array('code'=>'200','message'=>'successfully! done', 'result'=>$data,'requestname'=>"geo_check_inout"));
                    //$this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'check_out_time' => $checkout_image->check_out_time, 'distance' => $checkout_image->distance, 'polyline' => $checkout_image->polyline, 'meter_reading_checkout_image' => $checkout_image->meter_reading_checkout_image, 'personal_uses_km' => $checkout_image->personal_uses_km), 200);
                }
              }

            }else{
              $this->response(array('code'=>'404','message' => 'Error occured while inserting data'), 200);
              
            }

          }else if($fdata->requestname=="expenses_edit"){
	           	$config['upload_path'] = './images/expenses'; //give the path to upload the image in folder
	        	$config['allowed_types'] = 'jpg|JPG|png|PNG|csv|pdf|PDF|TXT|txt|GIF|gif|JPEG|jpeg';
	            $filesCount = count($_FILES['file_i']['name']);
             	 
              // $string_version = implode(',', $fileName);
              // if($string_version != ""|| $string_version != NULL){
              //   $strin_val =$string_version;
              //     $param_1['files'] = $strin_val;
              // }
              $expenses_id = $fdata->expenses_id;
              $param_1['expenses_type'] = $fdata->expenses_type;
              $param_1['expenses_name'] = $fdata->expenses_name;
              $param_1['price'] = $fdata->price;
              $param_1['expensesdate'] = date("Y-m-d",strtotime($fdata->expensesdate));
              $param_1['modified_by'] = $fdata->user_id;
              $param_1['modified_date_time'] = date("Y-m-d H:i:s");
              $ok = $this->Generic_model->updateData('expenses',$param_1,array('expenses_id'=>$expenses_id));

              for ($i = 0; $i < $filesCount; $i++) {
                  $_FILES['file_i[]']['name'] = $_FILES['file_i']['name'][$i];
                  $_FILES['file_i[]']['type'] = $_FILES['file_i']['type'][$i];
                  $_FILES['file_i[]']['tmp_name'] = $_FILES['file_i']['tmp_name'][$i];
                  $_FILES['file_i[]']['error'] = $_FILES['file_i']['error'][$i];
                  $_FILES['file_i[]']['size'] = $_FILES['file_i']['size'][$i];
                  $value = $i;
                  $this->upload->initialize($config);
                  $this->upload->do_upload('file_i[]');
                  $fname = $this->upload->data();
                  $fileName[$i] = $fname['file_name'];
                  if($fileName[$i] != "" || $fileName[$i] != NULL){
                  $param_4['expenses_image'] = $fileName[$i];
                  $param_4['expenses_id'] = $expenses_id;
                  $file_save = $this->Generic_model->insertData("expenses_files",$param_4);
                }
              }
              $return_data = $this->all_tables_records_view("expenses",$expenses_id);
              if($ok ==1){
                $this->response(array('code'=>'200','message' => 'Expenses Successfull updated','result'=>$return_data,'requestname'=>$fdata->requestname));
              }else{
                $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
              }

	        
	        }

	   }else{	
		  $entityBody=file_get_contents('php://input');                        
	      $data=json_decode($entityBody,TRUE);
		  $parameters=$data['requestparameters'];
		  $method=$data['requestname'];	
		  $user_id = $data['requesterid'];	
		  $this->$method($parameters,$method,$user_id);
	   }		
	}
	
	public function CheckFunc($parameters,$method,$user_id){
		$result = $this->Generic_model->generateUniqueNumber('sales_order','sales_order_number','sales_order_id','SS-','3');
		echo $result;
		exit;
	}
	
    public function login_details($parameters,$method,$user_id){
    	$users =$parameters['users'];
		$password = base64_encode($parameters['password']);
		$existed_or_not = $this->db->query("select * from users where ( email='".$users."') and password = '".$password."' and profile != 1 and archieve != 1 AND status = 'Active'")->row();
   
		if(count($existed_or_not)>0){

		$device_type = $parameters['device_type'];
		if($device_type == "iOS"){
			$param_list['fcmId_iOS'] = $parameters['fcmId'];
			$param_list['deviceid_iOS'] = $parameters['deviceid'];
			$param_list['modified_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->updateData('users',$param_list,array('user_id'=>$existed_or_not->user_id));
		}else if($device_type == "ANDROID"){
			$param_list['fcmId_android'] = $parameters['fcmId'];
			$param_list['deviceid_android'] = $parameters['deviceid'];
			$param_list['modified_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->updateData('users',$param_list,array('user_id'=>$existed_or_not->user_id));
		}

			//  $checking_device_id = $this->db->query("select * from users where fcmId = '".$parameters['fcmId']."'")->row();
			//   
			//	if(count($checking_device_id)>0){
			//   	$param_list_1['fcmId'] = "";
			//   	$param_list_1['deviceid'] = "";
			//   	$param_list_1['modified_date_time'] = date("Y-m-d H:i:s");
			//   	$this->Generic_model->updateData('users',$param_list_1,array('user_id'=>$checking_device_id->user_id));
			//   }
			//
			//   $param_list['device_type'] = $parameters['device_type'];
			//   $param_list['fcmId'] = $parameters['fcmId'];
			//   $param_list['deviceid'] = $parameters['deviceid'];
			//   $param_list['modified_date_time'] = date("Y-m-d H:i:s");
			//   $this->Generic_model->updateData('users',$param_list,array('user_id'=>$existed_or_not->user_id)); 
		}
		
		if(count($existed_or_not)>0){ 
			$login_list = $this->db->query("select *,PPM.Area from users a inner join profile b on (a.profile = b.profile_id) inner join role c on (c.role_id = a.role) inner join department d on (d.department_id = a.department) left Join product_price_master PPM ON a.Product_price_master_id = PPM.Product_price_master_id where a.user_id =".$existed_or_not->user_id)->row();

			$data['user_id'] = $login_list->user_id;
			$data['name'] = $login_list->name;
			$data['phone'] = $login_list->phone;
			$data['email'] = $login_list->email;
			$data['price_list_id'] = $login_list->Product_price_master_id;
			$data['price_list_area'] = $login_list->Area;
			$data['profile_img'] = base_url("images/profile_image/".$login_list->profile_image."");
			$data['profile_id'] = $login_list->profile_id;
			$data['profile_name'] = $login_list->profile_name;
			$data['role_id'] = $login_list->role_id;
			$data['role_name'] = $login_list->role_name;
			$data['department_id'] = $login_list->department_id;
			$data['department_name'] = $login_list->department_name;
			
			// Get Divisions
			if($login_list->division != NULL || $login_list->division != ''){
				$divisions = explode(",",$login_list->division);
				$x = 0;
				foreach($divisions as $division){
					$division_list = $this->db->query("select division_master_id, division_name from division_master where division_master_id = ".$division)->row();
					$data['divisions'][$x]['division_master_id'] = $division_list->division_master_id;
					$data['divisions'][$x]['division_name'] = $division_list->division_name;
					$x++;
				}
			}else{
				$data['divisions'] = array();
			}

        $role_id = $login_list->role_id;
        $role_query = $this->db->query("select * from role where role_id=".$role_id)->row();
        $roles = $this->db->query("select * from role where archieve!=1")->result_array();
        if($role_id==1){
            $users_id_role = get_below_userids($roles);
          }else{
          $users_id_role = get_below_userids($roles,$role_id);
        }
        $users = $this->doOutputListids($users_id_role);
        $login_role[]= $role_id;
        $last_second_roles_id = (array_merge($users,$login_role));
        $final_roles = implode(",", $last_second_roles_id);
        
        $users_list = $this->db->query("select * from users  WHERE  role IN (".$final_roles.") AND status = 'Active'")->result();
        $am=0;
        foreach($users_list as $users_val){
          $data['users_team'][$am]['user_id'] = $users_val->user_id;
          $data['users_team'][$am]['name'] = $users_val->name;
          $am++;
        }

      $access_permessions = $this->db->query("select * from role_permissions a inner join user_entities b on (a.entity_id = b.entity_id) where a.profile_id = '".$login_list->profile_id."' and b.is_mobile_module =1 and a.p_read != 0")->result();
      if(count($access_permessions) >0){
        $i=1;
              $data['left_nav'][0]['id'] = "0";
              $data['left_nav'][0]['name']="dashboard";
              $data['left_nav'][0]['method_name'] = 'dashboard';
              $data['left_nav'][0]['left_icon'] = base_url("assets/mobile_icons/Dashboard.png");
              $data['left_nav'][0]['read'] = "1";
              $data['left_nav'][0]['create'] = "1";
              $data['left_nav'][0]['update'] = "1";
              $data['left_nav'][0]['delete'] = "1";
            foreach($access_permessions as $access_val){
              $url_img = base_url("assets/mobile_icons/");
              $data['left_nav'][$i]['id'] = $access_val->entity_id;
              $data['left_nav'][$i]['method_name'] = $access_val->method_name;
              $data['left_nav'][$i]['name']=$access_val->user_entity_name;
              $data['left_nav'][$i]['left_icon'] = $url_img."".$access_val->phone_icons;
              $data['left_nav'][$i]['read'] = $access_val->p_read;
              $data['left_nav'][$i]['create'] = $access_val->p_create;
              $data['left_nav'][$i]['update'] = $access_val->p_update;
              $data['left_nav'][$i]['delete'] = $access_val->p_delete;
              $i++;
            }
            $data['left_nav'][$i]['id'] = "24";
              $data['left_nav'][$i]['name']="Notification";
               $data['left_nav'][$i]['method_name'] = 'Notification';
              $data['left_nav'][$i]['left_icon'] = base_url("assets/mobile_icons/NotificationsIcon.png");
              $data['left_nav'][$i]['read'] = "1";
              $data['left_nav'][$i]['create'] = "1";
              $data['left_nav'][$i]['update'] = "1";
              $data['left_nav'][$i]['delete'] = "1";
      }
			if($login_list != ""){
			$this->response(array('code'=>'200','message' => 'successfully Login ','result'=>$data,'requestname'=>$method));
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}	
			
		}else{
			$this->response(array('code'=>'404','message' => 'You are not authorized to login'), 200);
		}
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

  public function left_nav($parameters,$method,$user_id){
    $profile_id = $parameters['profile_id'];

    if($profile_id != ""){
		$access_permessions = $this->db->query("select * from role_permissions a inner join user_entities b on (a.entity_id = b.entity_id) where a.profile_id = '".$profile_id."'  and  b.is_mobile_module =1 and a.p_read != 0")->result();
		if(count($access_permessions) >0){
			$i=1;
                $data['left_nav'][0]['id'] = 0;
                $data['left_nav'][0]['name']="dashboard";
                $data['left_nav'][0]['method_name'] = 'dashboard';
                $data['left_nav'][0]['left_icon'] = base_url("assets/mobile_icons/Dashboard.png");
                $data['left_nav'][0]['read'] = "1";
                $data['left_nav'][0]['create'] = "1";
                $data['left_nav'][0]['update'] = "1";
                $data['left_nav'][0]['delete'] = "1";
				foreach($access_permessions as $access_val){
					$url_img = base_url("assets/mobile_icons/");
					$data['left_nav'][$i]['id'] = $access_val->entity_id;
					$data['left_nav'][$i]['name']=$access_val->user_entity_name;
					$data['left_nav'][$i]['method_name'] = $access_val->method_name;
					$data['left_nav'][$i]['left_icon'] = $url_img."".$access_val->phone_icons;
					$data['left_nav'][$i]['read'] = $access_val->p_read;
					$data['left_nav'][$i]['create'] = $access_val->p_create;
					$data['left_nav'][$i]['update'] = $access_val->p_update;
					$data['left_nav'][$i]['delete'] = $access_val->p_delete;
					$i++;
				}
				$data['left_nav'][$i]['id'] = "24";
                $data['left_nav'][$i]['name']="Notification";
                $data['left_nav'][$i]['method_name'] = "Notification";
                $data['left_nav'][$i]['left_icon'] = base_url("assets/mobile_icons/NotificationsIcon.png");
                $data['left_nav'][$i]['read'] = "1";
                $data['left_nav'][$i]['create'] = "1";
                $data['left_nav'][$i]['update'] = "1";
                $data['left_nav'][$i]['delete'] = "1";
                $this->response(array('code'=>'200','message' => 'success ','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
        }
	}else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	}
  }


	public function change_password($parameters,$method,$user_id){
		$password = $parameters['password'];
		if($password != ""){
			$param['password'] = base64_encode($password);
			$param['modified_date_time'] = date("Y-m-d");
			$param['modified_by'] = $user_id;
			$ok = $this->Generic_model->updateData('users', $param, array('user_id'=>$user_id));
			if($ok == 1){
				$this->response(array('code'=>'200','message' => 'successfully Password Changed ','result'=>$data,'requestname'=>$method));
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);

			}

		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
	public function profile_update($parameters,$method,$user_id){
      $users=$this->Generic_model->getSingleRecord('users', array('user_id'=>$user_id),$order='');
		$data['userName']=$parameters['username'];
		$data['name']=$parameters['name'];
		$data['phone']=$parameters['phone'];
		$data['email']=$parameters['email'];
		$data['modified_by'] = $user_id;
        $data['modified_date_time'] = date('Y-m-d h:i:s');
		$ok=$this->Generic_model->updateData('users',$data, array('user_id'=>$user_id));
		if($ok==1){
			$this->response(array('code'=>'200', 'message'=>'profile updated successfully','requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message'=>'Authentication failed'),200);
		}		
	}

  public function forgot_password($parameters,$method,$user_id){
    $email=$parameters['email_id'];
    $existed_or_not=$this->db->query('select * from users where email="'.$email.'" AND status = "Active"')->row();
    if(count($existed_or_not)>0){
      $email_id=$existed_or_not->email;
          $from = "amani.guduru@suprasoft.com";
          $to = $email;  
          $subject = "Forgot Password";
           $data['user_id'] = $existed_or_not->user_id;
          $data['email'] = $existed_or_not->email;
          $data['name'] = $existed_or_not->name;
        $result = $this->mail_send->Authentication_send_forgot_password($from, $to, $subject, '', '',$data);
          if($result==1){         
          $this->response(array('code'=>'200', 'message'=>'email has been sent','requestname'=>$method));
          }else{
            $this->response(array('code'=>'404','message'=>'failed to send'),200);
          }
    }
  }
  
	public function getProductList($parameters,$method,$user_id){
		
		$product_list = $this->db->query("select * FROM product_master PM INNER JOIN price_list_line_item PL ON PM.product_id=PL.product INNER JOIN price_master_divisions PMD ON PL.price_master_division_id = PMD.price_master_division_id WHERE PMD.Product_price_master_id = '".$parameters['Product_price_master_id']."' AND PMD.division_id = '".$parameters['division_id']."'")->result();
		
		$data['product_list'] = $product_list;
	
		if(count($product_list) > 0){         
			$this->response(array('code'=>'200', 'message'=>'Product List','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message'=>'Failed'),200);
		}
	}

	public function dropdownlist($parameters,$method,$user_id){
		
		$final_users_id = $parameters['team_id'];

		if($parameters['customer_list'] == "customer_list"){
			$role_id= $parameters['role_id'];
			$type = $parameters['type'];

			if($type == "parent_customers"){
				$customers = $this->db->query("select a.customer_id, a.CustomerName from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.user_id in (".$final_users_id.") and a.Type ='Institutional' and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
				
				$contacts = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id INNER JOIN customer_price_list CPL ON CON.Company = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 AND CUST.Type ='Institutional' AND CUST.archieve != 1 ORDER BY CON.contact_id DESC")->result(); 

				$associate_contacts = $this->db->query("SELECT CUST.CustomerName, CUST.customer_id FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id INNER JOIN customer_price_list CPL ON CUST.customer_id = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 AND CUST.Type ='Institutional' AND CUST.archieve != 1 GROUP BY CON.contact_id")->result();
			}else{
				$customers = $this->db->query("select a.customer_id, a.CustomerName from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
				
				$contacts = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id INNER JOIN customer_price_list CPL ON CON.Company = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 AND CUST.archieve != 1 ORDER BY CON.contact_id DESC")->result(); 

				$associate_contacts = $this->db->query("SELECT CUST.CustomerName, CUST.customer_id FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id INNER JOIN customer_price_list CPL ON CUST.customer_id = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 GROUP BY CON.contact_id")->result();				
			}
			
			$customer_list = array_merge($customers, $contacts, $associate_contacts);
   
			if(count($customer_list)>0){
				$i=0;
				foreach($customer_list as $customer_val){
					$data['Customer_list'][$i]['Customer_id']=$customer_val->customer_id;
					$data['Customer_list'][$i]['CustomerName']=$customer_val->CustomerName;
					$i++;
				}
			}else{
				$data['Customer_list'] = Null;
			}
		}
		
		if($parameters['contacts_list'] == "contacts_list" ){
						
			$contacts_list = $this->db->query("select C.contact_id, C.FirstName, C.LastName, C.Company_text, CUST.customer_id, C.Mobile, U.name from contacts C LEFT JOIN users U ON (C.ContactOwner = U.user_id) LEFT JOIN customers CUST ON (C.Company = CUST.customer_id) where C.ContactOwner in (".$final_users_id.") and C.archieve != 1 ORDER BY C.contact_id DESC")->result();
				
			if(count($contacts_list)>0){
				$j=0;
				foreach($contacts_list as $contact_val){
					$data['Contact_list'][$j]['Contact_id'] = $contact_val->contact_id;
					$data['Contact_list'][$j]['FirstName'] = $contact_val->FirstName." ".$contact_val->LastName;
					$data['Contact_list'][$j]['Mobile'] = $contact_val->Mobile;
					$data['Contact_list'][$j]['Company_id'] = $contact_val->customer_id;
					$data['Contact_list'][$j]['Company'] = $contact_val->Company_text;
					$j++;
				}
			}else{
				$data['contacts_list'] = NULL;
			}
		}
		
		if($parameters['associative_contacts'] == "associative_contacts" ){
			
			$teamUsers = $final_users_id.", 1";
						
			//$associative_contacts = $this->db->query('select C.Contact_id, C.FirstName, C.LastName, C.Title_Designation, C.Mobile, C.Phone, C.Email, C.Category, C.isAccountTagged, C.ContactOwner, CUST.customer_id, CUST.CustomerName from contacts C LEFT JOIN Customers CUST on (C.Company = CUST.customer_id) WHERE (C.Company is NULL OR C.Company = 0) AND C.ContactOwner in ('.$teamUsers.') and C.category != "Primary" and C.Category != "Other" and C.archieve != 1')->result();
			
			//$associative_contacts = $this->db->query('select C.Contact_id, C.FirstName, C.LastName, C.Title_Designation, C.Mobile, C.Phone, C.Email, C.Category, C.isAccountTagged, C.ContactOwner, CUST.customer_id, CUST.CustomerName from contacts C LEFT JOIN Customers CUST on (C.Company = CUST.customer_id) WHERE (C.Company is NULL OR C.Company = 0) AND C.ContactOwner in ('.$user_id.') and C.archieve != 1')->result();
			// "select * from contacts where   ContactOwner in (".$user_id.")  and archieve != 1"
			
			$associative_contacts = $this->db->query('select C.Contact_id, C.FirstName, C.LastName, C.Title_Designation, C.Mobile, C.Phone, C.Email, C.Category, C.isAccountTagged, C.ContactOwner, C.Company, C.Company_text from contacts C WHERE C.ContactOwner in ('.$user_id.') and C.archieve != 1')->result();
			
			// Get Customer and respective contact list belongs to the teamusers
			//$customer_list = $this->db->query("select a.customer_id, a.CustomerName, a.customer_number, a.CustomerSAPCode, a.approve_status, C.contact_id, C.FirstName, C.LastName, C.Phone, C.Mobile, C.Email, C.Category, C.Title_Designation, C.ContactOwner, C.isAccountTagged from customers a inner join customer_users_maping b on (a.customer_id = b.customer_id) LEFT JOIN contacts C ON (C.company = a.customer_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by c.contact_id order by b.customer_id ASC")->result();
			
			$customer_list = $this->db->query("select a.customer_id, a.CustomerName, a.customer_number, a.CustomerSAPCode, a.approve_status, C.contact_id, C.FirstName, C.LastName, C.Phone, C.Mobile, C.Email, C.Category, C.Title_Designation, C.ContactOwner, C.isAccountTagged from customers a inner join customer_users_maping b on (a.customer_id = b.customer_id) LEFT JOIN contacts C ON (C.company = a.customer_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id")->result();
			
			if(count($customer_list)>0){
				
				foreach($customer_list as $custRec){
					
					$data['Customer_list'][$custRec->customer_id]['Customer_id'] = $custRec->customer_id;
					$data['Customer_list'][$custRec->customer_id]['CustomerName'] = $custRec->CustomerName;
					$data['Customer_list'][$custRec->customer_id]['customer_number'] = $custRec->customer_number;
					$data['Customer_list'][$custRec->customer_id]['CustomerSAPCode'] = $custRec->CustomerSAPCode;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Contact_id'] = $custRec->contact_id;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Name'] = $custRec->FirstName." ".$custRec->LastName;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['designation'] = $custRec->Title_Designation;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Mobile'] = $custRec->Mobile;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Phone'] = $custRec->Phone;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Email'] = $custRec->Email;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Category'] = $custRec->Category;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['isAccountTagged'] = $custRec->isAccountTagged;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['ContactOwner'] = $custRec->ContactOwner;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Customer_id'] = $custRec->Company;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'][$custRec->contact_id]['Customer'] = $custRec->Compnay_text;
					
					// Reset the array keys starting from 0;
					$data['Customer_list'][$custRec->customer_id]['Contact_list'] = array_values($data['Customer_list'][$custRec->customer_id]['Contact_list']);
					
				}
			}
			
			// Reset the array keys starting from 0;
			$data['Customer_list'] = array_values($data['Customer_list']);
			
			$countCustRec = count($data['Customer_list']);
			
			// Get distinct customer results
			$customersRes = $this->db->query("SELECT DISTINCT(Company_text) FROM contacts WHERE created_by = '".$user_id."' and (Company IS NULL OR Company = '0' OR Company = '') and (Company_text != '0' AND Company_text IS NOT NULL AND Company_text != '')")->result();
			
			if(count($customersRes) > 0){
				
				$x = $countCustRec;
				
				foreach($customersRes as $customerRec){
					
					$data['Customer_list'][$x]['Customer_id'] = '0';
					$data['Customer_list'][$x]['CustomerName'] = $customerRec->Company_text;
					$data['Customer_list'][$x]['customer_number'] = '';
					$data['Customer_list'][$x]['CustomerSAPCode'] = '';
					
					$contactRes = $this->db->query('SELECT * FROM contacts WHERE Company_text = "'.$customerRec->Company_text.'" AND created_by = "'.$user_id.'" and (Company IS NULL OR Company = "0" OR Company = "") and archieve = 0')->result();
					
					if(count($contactRes) > 0){
						
						$y = 0;
						
						$contactCount = count($contactRes);
						
						foreach($contactRes as $custRec){
							
							// Get Customer Information
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Contact_id'] = $custRec->contact_id;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Name'] = $custRec->FirstName." ".$custRec->LastName;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['designation'] = $custRec->Title_Designation;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Mobile'] = $custRec->Mobile;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Phone'] = $custRec->Phone;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Email'] = $custRec->Email;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Category'] = $custRec->Category;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['isAccountTagged'] = $custRec->isAccountTagged;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['ContactOwner'] = $custRec->ContactOwner;
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Customer_id'] = '0';
							$data['Customer_list'][$x]['Contact_list'][$custRec->contact_id]['Customer'] = $custRec->Company_text;
							
							// Reset the array keys starting from 0;
							$data['Customer_list'][$x]['Contact_list'] = array_values($data['Customer_list'][$x]['Contact_list']);
							
							//print_r($custRec);
							$y++;
						}
					}
					$x++;
				}
			}
			
			// Get Customer data of the contact created by the user id
			
			
			
			//echo "SELECT * FROM contacts WHERE created_by = '".$user_id."' and (Company IS NULL OR Company = '0' OR Company = '') and (Company_text != '0' AND Company_text IS NOT NULL AND Company_text != '')";
			//echo '<pre>';
			//print_r($contactRes);
			//exit();
			
			//echo "SELECT * FROM contacts WHERE created_by = '".$user_id."' and (Company IS NULL OR Company = '0' OR COmpany = '')";
			//exit();
			
			//print_r($contactRes);
			//exit();
			
			
			//echo "Count of Customers: ".$countCustRec;
			//exit();
			
			/*
			if(count($contactRes) > 0){
				$x = $countCustRec;
				foreach($contactRes as $custRec){
				
					$sum = 0;

					// Get ASCII value of Company text
					$word = str_split($custRec->Company_text);
					foreach($word as $char){
					   $sum = $sum + ord($char);
					}
					
					// Get Customer Information
					$data['Customer_list'][$sum]['Customer_id'] = '0';
					$data['Customer_list'][$sum]['CustomerName'] = $custRec->Company_text;
					$data['Customer_list'][$sum]['customer_number'] = '';
					$data['Customer_list'][$sum]['CustomerSAPCode'] = '';
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Contact_id'] = $custRec->contact_id;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Name'] = $custRec->FirstName." ".$custRec->LastName;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['designation'] = $custRec->Title_Designation;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Mobile'] = $custRec->Mobile;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Phone'] = $custRec->Phone;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Email'] = $custRec->Email;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Category'] = $custRec->Category;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['isAccountTagged'] = $custRec->isAccountTagged;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['ContactOwner'] = $custRec->ContactOwner;
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Customer_id'] = '0';
					$data['Customer_list'][$sum]['Contact_list'][$custRec->contact_id]['Customer'] = $custRec->Company_text;
					
					// Reset the array keys starting from 0;
					$data['Customer_list'][$sum]['Contact_list'] = array_values($data['Customer_list'][$sum]['Contact_list']);
				}
			}
			*/
			
			//print_r($data['Customer_list']);
			//exit();
			
			if(count($associative_contacts)>0){				
				$l=0;
				foreach($associative_contacts as $ass_val){					
					$data['associate_contacts'][$l]['Contact_id'] = $ass_val->Contact_id;
					$data['associate_contacts'][$l]['Name'] = $ass_val->FirstName." ".$ass_val->LastName; 
					$data['associate_contacts'][$l]['designation'] = $ass_val->Title_Designation;
					$data['associate_contacts'][$l]['Mobile'] = $ass_val->Mobile;
					$data['associate_contacts'][$l]['Phone'] = $ass_val->Phone;
					$data['associate_contacts'][$l]['Email'] = $ass_val->Email;
					$data['associate_contacts'][$l]['Category'] = $ass_val->Category;
					$data['associate_contacts'][$l]['isAccountTagged'] = $ass_val->isAccountTagged;
					$data['associate_contacts'][$l]['ContactOwner'] = $ass_val->ContactOwner;
					$data['associate_contacts'][$l]['Customer_id'] = $ass_val->Company;
					$data['associate_contacts'][$l]['Customer'] = $ass_val->Company_text;
					$l++;
				}
			}else{
				$data['associate_contacts'] = array();
			}
			
		}
		
		//print_r($customer_list);
		//exit();
		
		// Get Sales Organisations Master
		if($parameters['sales_organisation'] == "sales_organisation"){
			$sales_organisation_list = $this->db->query("select * from sales_organisation where status = 1")->result();
			if(count($sales_organisation_list)>0){
				$a=0;
				foreach($sales_organisation_list as $val){
					$data['sales_organisation_list'][$a]['sales_organisation_id'] = $val->sales_organisation_id;
					$data['sales_organisation_list'][$a]['organistation_name'] = $val->organistation_name;
					$a++;
				}
			}else{
				$data['sales_organisation_list'] = array();
			}
		}
				
		// Get Divison Master
		if($parameters['divisions'] == "divisions"){
			$division_list = $this->db->query("select * from division_master")->result();
			if(count($division_list)>0){
				$a=0;
				foreach($division_list as $val){
					$data['division_list'][$a]['division_master_id'] = $val->division_master_id;
					$data['division_list'][$a]['division_name'] = $val->division_name;
					$a++;
				}
			}else{
				$data['division_list'] = array();
			}
		}
		
		if($parameters['users_list'] == "users_list"){
			$users_list = $this->db->query("select * from users where archieve != 1 AND status = 'Active'")->result();
			if(count($users_list)>0){
				$a=0;
				foreach($users_list as $users_val){
					$data['users_list'][$a]['user_id'] = $users_val->user_id;
					$data['users_list'][$a]['user_name'] = $users_val->name;
					$a++;
				}
			}else{
				$data['users_list'] = array();
			}
		}
		
		if($parameters['price_list']  == "price_list"){
			$date_list = date("Y-m-d");
			$area_list = $this->db->query("SELECT * FROM `product_price_master` where (Valid_to >= '".$date_list."' or Valid_to is NULL)")->result();
			$p  =0;
			foreach($area_list as $price_val){
				$data['price_list'][$p]['Product_price_master_id'] = $price_val->Product_price_master_id;
				$data['price_list'][$p]['Area'] = $price_val->Area; 
				$p++;
			}
		}
		
		if($parameters['IncoTerms'] == "IncoTerms"){
			$incoterm_list = $this->db->query("select * from Incoterm")->result();
			$e=0;
			foreach($incoterm_list as $inco_val){
				$data['incoterm_list'][$e]['Incoterm_id'] = $inco_val->Incoterm_id;
				$data['incoterm_list'][$e]['Incoterm_name'] = $inco_val->Incoterm_name; 
				$e++;
			}
		}

		if($parameters['Payment_terms'] == "Payment_terms"){
			$Payment_terms_list = $this->db->query("select * from Payment_terms")->result();
			$d=0;
			foreach($Payment_terms_list as $pay_val){
				$data['payment_list'][$d]['Payment_term_id'] = $pay_val->Payment_term_id;
				$data['payment_list'][$d]['Payment_name'] = $pay_val->Payment_name; 
				$d++;
			}
		}

		if($parameters['all_product_list'] == "all_product_list"){
			//$product_list = $this->db->query("select * from product_master where archieve !=1 group by product_code")->result();
			//print_r($product_list);
			$uinfo=$this->db->query("select Product_price_master_id,GROUP_CONCAT(division) as division from users where user_id='".$user_id."'")->row();
			
			$product_list = $this->db->query("select * from product_master d inner join price_list_line_item a on d.product_id=a.product inner join price_master_divisions b on a.price_master_division_id=b.price_master_division_id where b.Product_price_master_id='".$uinfo->Product_price_master_id."' and b.division_id in(".$uinfo->division.")")->result();
			
			//print_r($product_list);
			//exit();
			
			$f=0;
			foreach($product_list as $product_val){
				// Get product_price_master_id w.r.to logged in user
				$userRes = $this->db->query("select Product_price_master_id from users where user_id='".$user_id."'")->row();
				$product_price_master_id = $userRes->Product_price_master_id;	

				// Get the price w.r.to 
				$priceRec = $this->db->query("select * from product_master a inner join  Price_list_line_Item b on (a.product_id = b.product) where a.product_code ='".$product_val->product_code."' and b.Price_list_id ='".$product_price_master_id."'")->row();
				
				$data['product_list'][$f]['product_id'] = $product_val->product_id;
				$data['product_list'][$f]['product_code'] = $product_val->product_code;
				$data['product_list'][$f]['product_name'] = $product_val->product_name; 
				$data['product_list'][$f]['rate_per_sft'] = $priceRec->price;
				$f++;
			}
		}

		if($parameters['leads_list'] == "leads_list"){
			$lead_list = $this->db->query("select * from leads where LeadOwner in (".$final_users_id.") and status != 'convert' and archieve !=1")->result();
			$am =0;
			if(count($lead_list)>0){
				foreach($lead_list as $leads_val){
					$data['lead_list'][$am]['leads_id'] = $leads_val->leads_id;
					$data['lead_list'][$am]['leads_name'] = $leads_val->FirstName." ".$leads_val->LastName; 

					$am++;
				}
			}else{
				$data['lead_list'] = array();
			}
		}

		if($parameters['assigment_recommend']){
			$ass_recval = $this->db->query("select * from assigment_recommend_tbl where status = '1'")->result();	
			if(count($ass_recval)>0){
				$aaj=0;
				$rec=0;
				foreach($ass_recval as $ass_val){
					if($ass_val->type == "Assessment"){
						$data['assigment'][$aaj]['assigment_recommend_tbl_id'] = $ass_val->assigment_recommend_tbl_id;
						$data['assigment'][$aaj]['complaints_name'] = $ass_val->complaints_name;
						$aaj++;
					}else{
						$data['recommend'][$rec]['assigment_recommend_tbl_id'] = $ass_val->assigment_recommend_tbl_id;
						$data['recommend'][$rec]['s'] = $ass_val->complaints_name;
						$rec++;
					}
				}
			}else{
				$data['assigment'] = array();
				$data['recommend'] = array();
			}
		}

		$states_list = $this->db->query("select * from states ")->result();
		$project_type = $this->db->query("select * from leads_project_type ")->result();
		$size_class_project = $this->db->query("select * from leads_size_class_project ")->result();
		$status_project = $this->db->query("select * from leads_Status_project ")->result();

		$division_master = $this->db->query("select * from division_master ")->result();
		$DistributionChannel_list = $this->db->query("select * from DistributionChannel  where status ='1'")->result();
		$sales_organisation_list = $this->db->query("select * from sales_organisation where status ='1'")->result();

		$mc=0;
		foreach($division_master as $div_list){
			$data['division_list'][$mc]['division_master_id']=$div_list->division_master_id;
			$data['division_list'][$mc]['division_name']=$div_list->division_name;
			$mc++;
		}

		$mc1=0;
		foreach($DistributionChannel_list as $Distri_list){
			$data['DistributionChannel_list'][$mc1]['DistributionChannel_id']=$Distri_list->sap_code;
			$data['DistributionChannel_list'][$mc1]['ditribution_name']=$Distri_list->ditribution_name;
			$mc1++;
		}
		$mc2=0;
		foreach($sales_organisation_list as $salesorg_list){
			$data['sales_organisation_list'][$mc2]['sales_organisation_id']=$salesorg_list->sap_code;
			$data['sales_organisation_list'][$mc2]['organistation_name']=$salesorg_list->organistation_name;
			$mc2++;
		}
		$k=0;
		foreach($states_list as $states_list){
			$data['states_list'][$k]['state_id']=$states_list->state_id;
			$data['states_list'][$k]['state_name']=$states_list->state_name;
			$k++;
		}

		$m=0;
		foreach($project_type as $pro_type){
			$data['project_type'][$m]['project_type_id'] = $pro_type->project_type_id;
			$data['project_type'][$m]['project_type_name'] = $pro_type->project_type_name;
			$m++;
		}

		$a1=0;
		foreach($size_class_project as $pro_size_class){
			$data['project_class_size'][$a1]['size_class_id'] = $pro_size_class->size_class_id;
			$data['project_class_size'][$a1]['type'] = $pro_size_class->type;
			$data['project_class_size'][$a1]['size_class_name'] = $pro_size_class->size_class_name;
			$a1++;
		}

		$r=0;
		foreach($status_project as $pro_status){
			$data['project_status'][$r]['status_project_id'] = $pro_status->lead_status_project_id;
			$data['project_status'][$r]['status_project_name'] = $pro_status->leads_status_project_name;
			$r++;
		}
		
		$deliveryRes = $this->db->query("select CUST.customer_id, CUST.CustomerName, CUST.CustomerCategory from customers CUST INNER JOIN customer_users_maping CUM ON (CUM.customer_id = CUST.customer_id) WHERE CUM.user_id in (".$final_users_id.") AND CustomerCategory in ('Dealer','Sub Dealer','Distributor') and CUST.archieve != 1 GROUP BY CUM.customer_id")->result();
		

		if(count($deliveryRes) > 0){
			$x=$y=$z=0;
			foreach($deliveryRes as $rec){
				if($rec->CustomerCategory == "Dealer"){
					$data['Dealer'][$x]['customer_id'] = $rec->customer_id;
					$data['Dealer'][$x]['CustomerName'] = $rec->CustomerName;
					$x++;
				}
				if($rec->CustomerCategory == "Sub Dealer"){
					$data['Sub_Dealer'][$y]['customer_id'] = $rec->customer_id;
					$data['Sub_Dealer'][$y]['CustomerName'] = $rec->CustomerName;
					$y++;
				}
				if($rec->CustomerCategory == "Distributor"){
					$data['Distributor'][$z]['customer_id'] = $rec->customer_id;
					$data['Distributor'][$z]['CustomerName'] = $rec->CustomerName;
					$z++;
				}
			}
		}
		if(count($data)>0){
			$this->response(array('code'=>'200','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}

	public function lead_insert_old($parameters,$method,$user_id){
  	
		$Associate_contact_id = $parameters['Associate_contact_id'];
		unset($parameters['Associate_contact_id']);

		$parameters['LeadOwner'] = $user_id;
		$parameters['Created_by'] = $user_id;
		$parameters['modified_by']=$user_id;
		$parameters['modified_date_time']=date('Y-m-d H:i:s');
		$parameters['created_date_time']=date('Y-m-d H:i:s');
  	
		$result=$this->Generic_model->insertDataReturnId('leads',$parameters);

		if($result != "" || $result != NULL){
			$associate_cont_array = explode(",", $Associate_contact_id);
			if(count($associate_cont_array) >0){
				for($i=0;$i<count($associate_cont_array);$i++){
					$param_2['lead_id'] = $result;
					$param_2['user_id'] = $associate_cont_array[$i];
					$param_2['created_by'] = $user_id;
					$param_2['created_date_time'] = date("Y-m-d H:i:s");
					$this->Generic_model->insertData("lead_associate_contacts",$param_2);
				}
			}

			$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
			//$customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Company'])->row();

			$email = $user_list->email;
			$to = $email;  //$to      = $dept_email_id;
			$subject = "New Lead created";
			$data['name'] = $user_list->name;
			$data['message'] = "<p> A new lead has been created successfully <br/><br/><b> LeadName </b> : ". $parameters['FirstName']." ".$parameters['LastName']." <br/> <b>CustomerName </b> : ".$parameters['Company'].", <br/><b>Email</b> : ".$parameters['Email']." ,<br/> <b>MobileNumber</b> : ".$parameters['Mobile']." and <br/><b>Rating </b> : ".$parameters['Rating']."</p> ";  
			//$message = $message;
			$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

			$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
            
			if(count($user_report_to) >0){
                $email = $user_report_to->email;
                $to = $email;  //$to      = $dept_email_id;
                $subject = "New Lead created";
                $data['name'] = $user_report_to->name;
                $data['message'] = "<p> A new lead has been created successfully  By <b>".$user_list->name."</b> <br/><br/><b> LeadName </b> : ".$parameters['FirstName']." ".$parameters['LastName']." <br/> <b>CustomerName </b> : ".$parameters['Company'].", <br/><b>Email</b> : ".$parameters['Email']." ,<br/> <b>MobileNumber</b> : ".$parameters['Mobile']." and <br/><b>Rating </b> : ".$parameters['Rating']."</p> ";  
                //$message = $message;
                $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
			}

			$param_noti['notiffication_type'] = "Lead";
			$param_noti['notiffication_type_id'] = $result;
			$param_noti['user_id'] = $user_id;
			$param_noti['subject'] = " A new lead has been created successfully  By ".$user_list->name." LeadName  : ".$parameters['FirstName']." ".$parameters['LastName']." CustomerName  : ".$parameters['Company'].", Email : ".$parameters['Email']." ,MobileNumber : ".$parameters['Mobile']." and Rating  : ".$parameters['Rating']. " ";
			$this->Generic_model->insertData("notiffication",$param_noti);

			$latest_val['module_id'] = $result;
			$latest_val['module_name'] = "Lead";
			$latest_val['user_id'] = $user_id;
			$latest_val['created_date_time'] = date("Y-m-d H:i:s");
			$latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->insertData("update_table",$latest_val);

			if(count($user_list)>0){
				$push_noti['fcmId_android'] = $user_list->fcmId_android;
				$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
			}else{
				$push_noti['fcmId_android'] ="";
				$push_noti['fcmId_iOS'] = "";   
			}
			
			if(count($user_report_to) >0){
				$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
				$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
			}else{
				$push_noti['fcmId_android_report_to'] = "";
				$push_noti['fcmId_iOS_report_to'] = "";
			}

			$push_noti['lead_id'] = $result;
			$push_noti['user_id'] = $user_id;
			$push_noti['subject'] = " A new lead has been created successfully  By ".$user_list->name." LeadName  : ".$parameters['FirstName']." ".$parameters['LastName']." CustomerName  : ".$parameters['Company'].", Email : ".$parameters['Email']." ,MobileNumber : ".$parameters['Mobile']." and Rating  : ".$parameters['Rating']. " ";

			$this->PushNotifications->lead_notifications($push_noti);

			$data_1['leads_id'] = $result;

			$return_data = $this->all_tables_records_view("lead",$result);
			$this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data, 'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}	
  
	/**
	* Function delAllCustomer
	*/
	public function delAllCustomer($parameters, $method, $user_id){
		$users = explode(",",$parameters['users']);
		foreach($users as $user){
			// Get all customers mapped to the users
			$customers = $this->db->query("SELECT customer_user_id, customer_id, user_id FROM customer_users_maping WHERE user_id = '".$user."'")->result();
			if(count($customers) > 0){				
				foreach($customers as $customer){
					//Delete Price List for Customer
					$PriceListRes = $this->db->delete('customer_price_list', array('customer_id' => $customer->customer_id));
					echo "PriceListRes: ".$PriceListRes."...";
					
					// Delete Customer
					$customerRes = $this->db->delete('customers', array('customer_id' => $customer->customer_id));
					echo "CustomerRes: ".$CustomerRes."...";
				}
			}
			
			// Delete Customer user mapping record for the customer
			$CustomerUserMapRes = $this->db->delete('customer_users_maping', array('user_id' => $user));
			echo "CustomerUserMapRes: ".$CustomerUserMapRes."...";
			
			// Delete all contacts of the user
			$ContactsRes = $this->db->delete('contacts', array('created_by' => $user));
			echo "ContactsRes: ".$ContactsRes."...";
		}
		exit();
	}
  
	/**
	Function importing data from production table to development table
	*/
	public function importUserCustomersDB($parameters, $method, $user_id){
	
		//$users = explode(",", $parameters['users']);
		$users = $this->db->query("SELECT user_id FROM users")->result();
	
		if(count($users) > 0){

			foreach($users as $userval){
				$user = $userval->user_id;
				// Get customer list for the user
				$customers = $this->db->query("SELECT customer_id FROM customer_users_maping_production WHERE user_id = ".$user)->result();
				echo "count of customers mapped to the user ".$user." is : ".count($customers)."***********\n";
				// Get User Info
				$userInfo = $this->Generic_model->getSingleRecord('users',array('user_id'=>$user));
				
				if(count($customers) > 0) {
					$i = 0;
					$custIns = $custDup = $mapRec = $mapDup = $plRec = $plDup = 0;
					foreach($customers as $customer){

						// Get customer info
						$customerRec = $this->Generic_model->getSingleRecord('customers_production', array('customer_id'=>$customer->customer_id));
						
						$customerRec->customer_production_id = $customerRec->customer_id;
						unset($customerRec->customer_id);
						
						$customerRec->approve_status = 1;
						$customerRec->CustomerType = "Direct Customer";
						
						// Get user's manager id
						if($userInfo->manager != '' || $userInfo->manager != NULL){
							$customerRec->manager_user_id = $userInfo->manager;
						}
						
						// Check for duplicate record
						$custInfo = $this->Generic_model->getSingleRecord('customers', array('customer_production_id'=>$customerRec->customer_production_id));
						
						$customer_id = 0;
						$genCustNo = 0;
						
						if(count($custInfo) == 0){						
							echo "New Record\n";
							$customer_id = $this->Generic_model->insertDataReturnId("customers",$customerRec);							
							$genCustNo	= 1; // Generate Customer no.
							echo "Inserted ... Customer id: ".$customer_id."\n\n";
							$custIns++;
						}else{
							//echo "Duplicate Record\n";
							$customer_id = $custInfo->customer_id;
							$genCustNo	= 0; // Don't generate Customer no.
							$custDup++;
						}
						
						if($customer_id > 0){

							if($genCustNo == 1){
								// Generate the customer no.
								$number = str_pad($customer_id,6,'0',STR_PAD_LEFT);                
								$prefix = 'DC';
								$cs_id= $prefix."-".$number;				
								$this->db->query("update customers set customer_number='".$cs_id."' where customer_id='".$customer_id."'");
							}
							
							// Check for duplicate record
							//$UCMapCount = $this->Generic_model->getNumberOfRecords('customer_users_maping', array('customer_id'=>$customer_id, 'user_id'=>$user));
							$UCMapCount = $this->Generic_model->getSingleRecord('customer_users_maping', array('customer_id'=>$customer_id, 'user_id'=>$user));
							if(count($UCMapCount) == 0){
								// Map Customer and user in customer_users_maping DB
								$mappingInfo['customer_id'] = $customer_id;
								$mappingInfo['user_id'] = $mappingInfo['created_by'] = $mappingInfo['modified_by'] = $user;
								$mappingInfo['created_date_time'] = date("Y-m-d H:i:s");
								$mappingInfo['modified_date_time'] = date("Y-m-d H:i:s");
								
								// Insert Record
								$res = $this->Generic_model->insertData("customer_users_maping",$mappingInfo);
								$mapRec++;
							}else{
								echo "Duplicate mapping : ".$mapDup."\n";
								print_r($UCMapCount).'\n';
								$mapDup++;
							}
							
							// Check for duplicate record
							$PLCount = $this->Generic_model->getNumberOfRecords('customer_price_list', array('customer_id'=>$customer_id, 'price_list_id'=>$userInfo->Product_price_master_id));
							
							if($PLCount == 0){						
								// Insert record for customer Price List
								if($userInfo->Product_price_master_id != '' || $userInfo->Product_price_master_id != NULL){
									$customerPriceList['customer_id'] = $customer_id;
									$customerPriceList['price_list_id'] = $userInfo->Product_price_master_id;
									$customerPriceList['status'] = 'Active';
									$customerPriceList['created_by'] = $user;
									$customerPriceList['modified_by'] = $user;
									$customerPriceList['created_date_time'] = date("Y-m-d H:i:s");
									$customerPriceList['modified_date_time'] = date("Y-m-d H:i:s");
									
									// Insert record
									$res = $this->Generic_model->insertData("customer_price_list",$customerPriceList);
									$plRec++;
								}
							}else{
								$plDup++;
							}
							$i++;
						}
					}
					echo "Inserted Customers : ".$custIns."*************Duplicate Customers: ".$custDup;
					echo "Mapped Customers : ".$mapRec."*************Duplicate Mapping: ".$mapDup;
					echo "PriceList Records : ".$plRec."*************Duplicate Price List: ".$plDup;
				}
				
				// Get Contacts list for user and insert into contacts
				$contactInfo = $this->db->query("SELECT * FROM contacts_production WHERE created_by = ".$user)->result();
				echo "count of contacts for user ".$user." is : ".count($contactInfo)."***********";
				
				if(count($contactInfo) > 0) {
					$j = 0;
					foreach($contactInfo as $contactRec){			
						$contactRec->contact_production_id = $contactRec->contact_id;
						unset($contactRec->contact_id);
						
						// Get Company Text and Save into db
						if($contactRec->Company != '' || $contactRec->Company != NULL){
							
							$companyInfo = $this->db->query('SELECT customer_id, CustomerName FROM customers WHERE customer_id = '.$contactRec->Company.' OR customer_production_id = '.$contactRec->Company)->row();
							
							if(count($companyInfo) > 0){
								$contactRec->Company = $companyInfo->customer_id;
								$contactRec->Company_text = $companyInfo->CustomerName;
							}
						}
						
						// Check for duplication
						$countactCount = $this->Generic_model->getNumberOfRecords('contacts', array('contact_production_id'=>$contactRec->contact_production_id));
						
						if($contactCount == 0){
							$contact_id = $this->Generic_model->insertDataReturnId("contacts",$contactRec);
							$j++;
						}
						
					}
					echo "Inserted Contacts: ".$j."**********";
				}
			}
		}
		exit();
	}
	
	/**
	Function importing data from production table to development table
	public function importUserContactsDB($parameters, $method, $user_id){
		$contactInfo = $this->db->query("SELECT * FROM contacts_production WHERE created_by = ".$parameters['created_by'])->result();
		
		if(count($contactInfo) > 0) {
			$i = 0;
			foreach($contactInfo as $contactRec){			
				$contactRec->contact_production_id = $contactRec->contact_id;
				unset($contactRec->contact_id);
				
				// Get company Text and save into db
				if($contactRec->Company != '' || $contactRec->Company != NULL){
					
					$companyInfo = $this->db->query('SELECT customer_id, CustomerName FROM customers WHERE customer_id = '.$contactRec->Company.' OR customer_production_id = '.$contactRec->Company)->row();
					
					if(count($companyInfo) > 0){
						$contactRec->Company = $companyInfo->customer_id;
						$contactRec->Company_text = $companyInfo->CustomerName;
					}
				}
				
				$contact_id = $this->Generic_model->insertDataReturnId("contacts",$contactRec);
				
				echo "Customer Id: ".$contact_id."....";
				$i++;
				echo $i.")...";
			}
		}
		exit();
	}
	*/
  
	/*
	* function lead_insert will insert new lead	
	*/
	public function lead_insert($parameters,$method,$user_id){
		
		$associateContacts = $parameters['associate_contact'];
		$actionWork = $parameters['action_work_done'];
		
		$param = $parameters;
		
		// unset below elements from leadParams
		// associate_contact
		// action_work_done
		unset($param['associate_contact']);
		unset($param['action_work_done']);
		
		// Add required elements to the leadParams
		$param['LeadOwner'] = $user_id;
		$param['created_by']= $user_id;
		$param['modified_by'] = $user_id;
		$param['modified_date_time'] = date("Y-m-d H:i:s");
		$param['created_date_time'] = date("Y-m-d H:i:s");
		
		// Check if the company specified is existing in the company DB
		$isCompanyexists=$this->db->query("SELECT * FROM customers where UPPER(CustomerName)=UPPER('".$param['Company_Text']."')")->row();

		if($isCompanyexists->customer_id){
			$param['Company'] = $isCompanyexists->customer_id;
			$param['isAccountTagged'] = 1;
			$param['Company_text'] = $param['Company_Text'] = $isCompanyexists->CustomerName;
			
			$company_list = $this->db->query("select * from customers where customer_id =".$isCompanyexists->customer_id)->row();	   
		}else{	  
			$param['isAccountTagged'] = 0;
			$param['Company_text'] = $param['Company_Text'];
		}
		
		unset($param['Company_Text']);
		
		// insert lead and get inserted id
		$leads_id = $this->Generic_model->insertDataReturnId("leads",$param);
		
		if($leads_id != "" || $leads_id != NULL){
			
			// update lead_number for lead_id : LD-lead_id
			$lead_no = str_pad($leads_id, 6, '0', STR_PAD_LEFT);
			$leadInfo['lead_number'] = "LD-".$lead_no;
			$this->Generic_model->updateData('leads', $leadInfo, array('leads_id' => $leads_id));
			
			// insert Associate Contacts concern to the current lead
			// check if any associate contacts
			$count = count($associateContacts);
			if($count > 0){
				foreach($associateContacts as $contact){
					$contact['leads_id'] = $leads_id;
					$contact['created_by'] = $user_id;
					$contact['modified_by'] = $modified_by;
                    $contact['created_date_time'] = date("Y-m-d H:i:s");
					$contact['modified_date_time'] = date("Y-m-d H:i:s");
					$this->Generic_model->insertData("lead_associate_contacts",$contact);	
				}
			}
			
			// insert Action work done concern to the current lead
			// check if any action work done records data available
			$count = count($actionWork);
			if($count > 0){
				foreach($actionWork as $action){
					$action['leads_id'] = $leads_id;
					$action['created_by'] = $user_id;
					$action['modified_by'] = $modified_by;
                    $action['created_date_time'] = date("Y-m-d H:i:s");
					$action['modified_date_time'] = date("Y-m-d H:i:s");
					$action['action_work_done_remarks'] = trim($action['action_work_done_remarks']);
					
					if($action['action_work_done_remarks'] != '' && $action['action_work_done_remarks'] != NULL){
						$this->Generic_model->insertData("lead_action_work_done",$action);
					}					
				}
			}
		}

		/* Temporarily commented code for email notifications
		// Get users
		$user_list = $this->db->query("select * from users where user_id = '".$user_id."'")->row();
             
		// Lead created email notification data
		$email = $user_list->email;
		$to = $email;  
		$subject = "New Lead created";
		$data['name'] = $user_list->name;
		$data['message'] = "<p> A new lead has been created successfully <br/><br/><b> LeadName </b> : ".$this->input->post('FirstName')." ".$this->input->post('LastName')." <br/> <b>CustomerName </b> : ".$this->input->post('Company').", <br/><b>Email</b> : ".$this->input->post('Email')." ,<br/> <b>MobileNumber</b> : ".$this->input->post('Mobile')." and <br/><b>Rating </b> : ".$this->input->post('Rating')."</p> ";  
		$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

		$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."'")->row();
        
		if(count($user_report_to) >0){
			$email = $user_report_to->email;
			$to = $email; 
			$subject = "New Lead created";
			$data['name'] = $user_report_to->name;
			$data['message'] = "<p> A new lead has been created successfully  By <b>".$user_list->name."</b> <br/><br/><b> LeadName </b> : ".$this->input->post('FirstName')." ".$this->input->post('LastName')." <br/> <b>CustomerName </b> : ".$this->input->post('Company').", <br/><b>Email</b> : ".$this->input->post('Email')." ,<br/> <b>MobileNumber</b> : ".$this->input->post('Mobile')." and <br/><b>Rating </b> : ".$this->input->post('Rating')."</p> ";  

			// $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
		}
		*/
		
		/* Temporarily commented code for push notifications
		$param_noti['notiffication_type'] = "Lead";
		$param_noti['notiffication_type_id'] = $leads_id;
		$param_noti['user_id'] = $user_id;
		$param_noti['subject'] = " A new lead has been created successfully   LeadName : ".$this->input->post('FirstName')." ".$this->input->post('LastName')."  CustomerName  : ".$parameters['Company'].", Email : ".$parameters['lead_email']." , MobileNumber : ".$parameters['lead_Mobile')." and Rating  : ".$this->input->post('Rating')."";
		//$this->Generic_model->insertData("notiffication",$param_noti);

		$latest_val['module_id'] = $leads_id;
		$latest_val['module_name'] = "Lead";
		$latest_val['user_id'] = $user_id;
		$latest_val['created_date_time'] = date("Y-m-d H:i:s");
		$latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
		//$this->Generic_model->insertData("update_table",$latest_val);
		
		if(count($user_list)>0){
			$push_noti['fcmId_android'] = $user_list->fcmId_android;
			$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
		}else{
			$push_noti['fcmId_android'] ="";
			$push_noti['fcmId_iOS'] = "";   
		}
		
		if(count($user_report_to) >0){
			$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
			$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
		}else{
			$push_noti['fcmId_android_report_to'] = "";
			$push_noti['fcmId_iOS_report_to'] = "";
		}

		
		$push_noti['lead_id'] = $leads_id;
		$push_noti['user_id'] = $user_id;
		$push_noti['subject'] = " A new lead has been created successfully   LeadName : ".$this->input->post('FirstName')." ".$this->input->post('LastName')."  CustomerName  : ".$this->input->post('Company').", Email : ".$this->input->post('Email')." , MobileNumber : ".$this->input->post('Mobile')." and Rating  : ".$this->input->post('Rating')."";
		//$this->PushNotifications->lead_notifications($push_noti);
		*/
		$return_data = $this->all_tables_records_view("lead",$leads_id);
		//print_r($return_data);
		
		$this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data, 'requestname'=>$method));		
	}
	
	public function lead_edit($parameters,$method,$user_id){			
		
		$associate_contacts_list = $parameters['associate_contact'];
		$action_work_done_list = $parameters['action_work_done'];
		
		$lead_data = $parameters;
		
		// remove associative_contacts_list & action_work_done_list from lead_data
		unset($lead_data['associate_contact']);
		unset($lead_data['action_work_done']);
		unset($lead_data['LeadOwner']);
		
		// add extra required items to the lead_data
		$lead_data['modified_by'] = $user_id;
		$lead_data['modified_date_time'] = date('Y-m-d H:i:s');
		
		// Changes ot the company text
		// check if the company text is in the customers db, if yes get the customer id and update company & company_text accordingly
		// if its not existing then change the company to '0' and isAccountTagged to '0'
		$isCompanyexists=$this->db->query("SELECT * FROM customers where UPPER(CustomerName)=UPPER('".$lead_data['Company_text']."')")->row();
		
		if($isCompanyexists->customer_id){
			$lead_data['Company'] = $isCompanyexists->customer_id;
			$lead_data['isAccountTagged'] = 1;
		}else{	  
			$lead_data['isAccountTagged'] = 0;
			$lead_data['Company'] = 0;
		}
		
		//Get Lead Owner for the name
		if($parameters['LeadOwner'] != '' || $parameters['LeadOwner'] != NULL){
			
		}
		
		// Update the lead data
		$result = $this->Generic_model->updateData('leads', $lead_data, array('leads_id'=>$lead_data['leads_id']));

		//$lead_list=$this->Generic_model->getSingleRecord('leads',array('leads_id'=>$parameters['lead_id']),$order='');
		
		//$Associate_contact_id = $parameters['Associate_contact_id'];
		//unset($parameters['Associate_contact_id']);
		
		//$parameters['modified_by']=$user_id;
		//$parameters['modified_date_time']=date('Y-m-d H:i:s');
		
		
		if($result == 1){

			$check_update_list = $this->db->query("select * from update_table where module_id ='".$parameters['leads_id']."' and module_name ='Lead'")->row();
		
			if(count($check_update_list)>0){
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['leads_id'],'module_name'=>'Lead'));
			}else{
				$latest_val['module_id'] = $parameters['leads_id'];
				$latest_val['module_name'] = "Lead";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				// $latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
			}		
			
			// Associate Contacts
			if(count($associate_contacts_list) > 0){
				
				// Get existing associate contacts available in db with concern leads_id
				$assContacts = $this->db->query('select lead_associate_contact_id from lead_associate_contacts where leads_id ="'.$lead_data["leads_id"].'"')->result();
				
				if(count($assContacts) > 0){
					foreach($assContacts as $assContact){
						$assContactId[] = $assContact->lead_associate_contact_id;
					}
				}
				
				$new = 0;
				//print_r($assContactId);
				//print_r($associate_contacts_list);
				foreach($associate_contacts_list as $contact){

					// If the record exists with leads_id and contact_id
					// if Yes then update
					// if No then Insert new
					$chkRec = $this->db->query('select * from lead_associate_contacts where  lead_associate_contact_id ="'.$contact["lead_associate_contact_id"].'"')->row();
					
					// Check and remove the existing id from the $assContacts
					if(in_array($contact["lead_associate_contact_id"], $assContactId)){
						$removeVal[] = $contact["lead_associate_contact_id"];
					}
					
					if(count($chkRec) > 0){
						$new = 0;
					}else{
						$new = 1;
					}
					
					if($new){ // Insert new associate contact
						$contact['leads_id'] = $lead_data['leads_id'];
						$contact['created_by'] = $user_id;
						$contact['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("lead_associate_contacts",$contact);
					}else{ // Update existing associate contact
						$contact['modified_by'] = $user_id;
						$contact['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->updateData("lead_associate_contacts",$contact,array('lead_associate_contact_id'=>$contact['lead_associate_contact_id']));
					}
				}
			}
			
			// Remove existing lead associate contact id from the db associate contact id
			$assContactId = array_diff($assContactId, $removeVal);
			
			// if still any id exists then remove those records from the lead associate contact db
			if(count($assContactId) > 0){
				foreach($assContactId as $LAC_id){
					$this->db->delete('lead_associate_contacts', array("lead_associate_contact_id" => $LAC_id));
				}
			}
			
			// Action Work Done
			if(count($action_work_done_list) > 0){
				
				// Get existing lead action work done records available in db with concern leads_id
				$actionWorkDone = $this->db->query('select action_work_done_id from lead_action_work_done where leads_id ="'.$lead_data["leads_id"].'"')->result();
				
				if(count($actionWorkDone) > 0){
					foreach($actionWorkDone as $workDoneRec){
						$workDoneId[] = $workDoneRec->action_work_done_id;
					}
				}
				
				$new = 0;
				foreach($action_work_done_list as $action){
					
					// Check and remove the existing id from the $assContacts
					if(in_array($action["action_work_done_id"], $workDoneId)){
						$removeWorkDoneVal[] = $action["action_work_done_id"];
					}
					
					// Check if the action_work_done_remarks is not empty / NULL
					if($action['action_work_done_remarks'] != '' || $action['action_work_done_remarks'] != NULL){
					
						// Check if the record exists with leads_id and action_work_done_date exists
						$chkActionRec = $this->db->query('select * from lead_action_work_done where leads_id ="'.$lead_data["leads_id"].'" and action_work_done_date = "'.$action["action_work_done_date"].'" and action_work_done_remarks = "'.$action['action_work_done_remarks'].'"')->row();
						
						if(count($chkActionRec) > 0){
							$new = 0;
						}else{
							$new = 1;
						}
						
						if($new == 1){ // Insert new action work done
							$action['leads_id'] = $lead_data['leads_id'];
							$action['created_by'] = $user_id;
							$action['created_date_time'] = date("Y-m-d H:i:s");
							$this->Generic_model->insertData("lead_action_work_done",$action);
						}else{ // Update existing action work done
							$action['modified_by'] = $user_id;
							$action['modified_date_time'] = date("Y-m-d H:i:s");
							$this->Generic_model->updateData("lead_action_work_done",$action,array('action_work_done_id'=>$action['action_work_done_id']));
						}
					}
				}
			}
			
			// Remove existing work done records id from the db
			$workDoneId = array_diff($workDoneId, $removeWorkDoneVal);
			
			// If still any id exists then remove those records from the action work done db
			if(count($workDoneId) > 0){
				foreach($workDoneId as $AWD_id){
					$this->db->delete('lead_action_work_done', array("action_work_done_id" => $AWD_id));
				}
			}
			
			/* Old code commented 12 Sept 2020 : 11:14 am
			/*
			if(count($associate_cont_array) >0){
				for($i=0;$i<count($associate_cont_array);$i++){
					$checking_lead_contact = $this->db->query("select * from lead_associate_contacts where leads_id = '".$parameters['leads_id']."' and user_id = '".$associate_cont_array[$i]."'")->row();
					if(count($checking_lead_contact) >0){
						$param_2['leads_id'] = $parameters['leads_id'];
						$param_2['user_id'] = $associate_cont_array[$i];
						$param_2['created_by'] = $user_id;
						$param_2['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->updateData("lead_associate_contacts",$param_2,array('lead_associate_contact_id'=>$checking_lead_contact->lead_associate_contact_id));
					}else{
						$param_2['leads_id'] = $parameters['leads_id'];
						$param_2['user_id'] = $associate_cont_array[$i];
						$param_2['created_by'] = $user_id;
						$param_2['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("lead_associate_contacts",$param_2);
					}
				}
			}*/
			
			$return_data = $this->all_tables_records_view("lead",$parameters['leads_id']);
			$this->response(array('code'=>'200','message'=>'Updated successfully','result'=>$return_data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
  
	public function lead_list($parameters,$method,$user_id){
	   
		// Get all associated users mapped under to the user_id
		$final_users_id = $parameters['team_id'];	
		$lead_id = '';
		
		// Get all the list of leads belongs to the final users
		$data = $this->leadInfo($lead_id, $final_users_id);
		
		if(count($data['lead_list'])>0){
			$this->response(array('code'=>'200','message'=>'lead_list', 'result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'200','message' => 'Leads are empty','result'=>null), 200);
		}      	
	}

	public function lead_view($parameters,$method,$user_id){
		/* Old code commented 11 Sept 2020 11:24pm	
		$leads_id  = $parameters['leads_id'];
		
		//$data['lead_list']=$this->Generic_model->getAllRecords('leads',$condition='',$order='');
		$leads_val= $this->db->query("select * from leads a inner join users b on (a.LeadOwner = b.user_id) where a.archieve != 1 and a.status != 'convert' and a.leads_id =".$leads_id)->row();  
		
		$Associate_contact_list = $this->db->query("select * from contacts where contact_id = '".$leads_val->Associate_contact_id ."'")->row();
		
		$data['leads_id'] = $leads_val->leads_id; 
		$data['FirstName'] = $leads_val->FirstName;
		$data['LastName'] = $leads_val->LastName;
		$data['Company'] = $leads_val->Company;
		$data['Associate_contact_id'] = $Associate_contact_list->FirstName ." ".$Associate_contact_list->LastName;
		$data['Address'] = $leads_val->Address;
		$data['AnnualRevenue'] = $leads_val->AnnualRevenue;
		$data['Description'] = $leads_val->Description;
		$data['DoNotCall'] = $leads_val->DoNotCall;
		$data['Email'] = $leads_val->Email;
		$data['Fax'] = $leads_val->Fax;
		$data['Industry'] = $leads_val->Industry;
		$data['LeadOwner'] = $leads_val->name;
		$data['LeadSource'] = $leads_val->LeadSource;
		$data['LeadStatus'] = $leads_val->LeadStatus;
		$data['Mobile'] = $leads_val->Mobile;
		$data['No_Employees'] = $leads_val->No_Employees;
		$data['Phone'] = $leads_val->Phone;
		$data['Rating'] = $leads_val->Rating;
		$data['Title'] = $leads_val->Title;
		$data['Website'] = $leads_val->Website;
		$data['status'] = $leads_val->status;
		$data['BillingStreet1'] = $leads_val->BillingStreet1;
		$data['Billingstreet2'] = $leads_val->Billingstreet2;
		$data['BillingCountry'] = $leads_val->BillingCountry;
		$data['StateProvince'] = $leads_val->StateProvince;
		$data['BillingCity'] = $leads_val->BillingCity;
		//$data['Billing'] = $leads_val->Billing;
		$data['BillingZipPostal'] = $leads_val->BillingZipPostal;
		$data['ShippingStreet1'] = $leads_val->ShippingStreet1;
		$data['Shippingstreet2'] = $leads_val->Shippingstreet2;
		$data['ShippingCountry'] = $leads_val->ShippingCountry;
		$data['ShippingStateProvince'] = $leads_val->ShippingStateProvince;
		$data['ShippingCity'] = $leads_val->ShippingCity;
		//$data['Shipping'] = $leads_val->Shipping;
		$data['ShippingZipPostal'] = $leads_val->ShippingZipPostal;
		
		if(count($leads_val)>0){
			$this->response(array('code'=>'200','message'=>'lead_view', 'result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
		* comment end
		*/
		
		// New code implementation for 'function lead_view' 11 Sept 2020 11:35pm		
		$leads_id  = $parameters['leads_id'];
		
		$data = $this->leadInfo($leads_id);
		
		/*
		$lead_data = $this->db->query("select * from leads a inner join users b on (a.LeadOwner = b.user_id) where a.leads_id = '".$leads_id."'")->row();
		
		if(count($lead_data)>0){
						
			// Gathering lead data        							
			$data['leads_id'] =  $leads_val->leads_id;  				
			$data['Company'] = $leads_val->Company_text;
			$data['Associate_contact_id'] = $associative_contacts_val_list;
			
			// Concatinate lead & Billing Address
			$address = ($lead_data->lead_street1 != '')? $lead_data->lead_street1.', ' : '';
			$address .= ($lead_data->lead_street2 != '')? $lead_data->lead_street2.', ' : '';
			$address .= ($lead_data->lead_plotno != '')? 'No. #'.$lead_data->lead_plotno.', ' : '';
			$address .= ($lead_data->lead_area != '')? ucwords($lead_data->lead_area).', ' : '';
			$address .= ($lead_data->lead_City != '')? ucwords($lead_data->lead_City).', ' : '';
			$address .= ($lead_data->lead_state != '')? ucwords($lead_data->lead_state).', ' : '';
			$address .= ($lead_data->lead_country != '')? ucwords($lead_data->lead_country).', ' : '';
			$address .= ($lead_data->lead_pin_zip_code != '')? 'Pincode: '.$lead_data->lead_pin_zip_code : '';
			
			$data['Address'] = $address;				
			$data['Email'] = $lead_data->lead_email;
			$data['LeadOwner'] = ucwords($lead_data->name);
			$data['LeadSource'] = $lead_data->LeadSource;
			$data['LeadStatus'] = $lead_data->lead_status;				
			$data['Phone'] = $lead_data->lead_phone;
			$data['Website'] = $lead_data->lead_website;
			$data['status'] = $lead_data->lead_status;
			
			// lead Project Data
			$data['project_name'] = $lead_data->lead_project_name;
			$data['project_type'] = $lead_data->lead_project_type;
			$data['size_class_project'] = $lead_data->lead_size_class_of_project;
			$data['status_project'] = $lead_data->lead_project_status;
			
			// Billing Address : Lead Address				
			$data['BillingStreet1'] = $lead_data->lead_street1;
			$data['Billingstreet2'] = $lead_data->lead_street2;								
			$data['BillingCity'] = $lead_data->lead_city;
			$data['BillingStateProvince'] = $lead_data->lead_state;
			$data['BillingCountry'] = $lead_data->lead_country;				
			$data['BillingZipPostal'] = $lead_data->lead_pin_zip_code;
			$data['BillingAddress'] = $address;
			
			// Concatinating Shipping Address : Lead Project Address
			$shippingAddress = ($lead_data->lead_project_street1 != '')? $lead_data->lead_project_street1.', ' : '';
			$shippingAddress .= ($lead_data->lead_project_street2 != '')? $lead_data->lead_project_street2.', ' : '';
			$shippingAddress .= ($lead_data->lead_project_plot_no != '')? 'No. #'.$lead_data->lead_project_plot_no.', ' : '';
			$shippingAddress .= ($lead_data->lead_project_land_mark != '')? ucwords($lead_data->lead_project_land_mark).', ' : '';
			$shippingAddress .= ($lead_data->lead_project_city != '')? ucwords($lead_data->lead_project_city).', ' : '';
			$shippingAddress .= ($lead_data->lead_project_state != '')? ucwords($lead_data->lead_project_state).', ' : '';
			$shippingAddress .= ($lead_data->lead_project_pin_zip_code != '')? 'Pincode: '.$lead_data->lead_project_pin_zip_code : '';
			
			$data['ShippingStreet1'] = $lead_data->lead_project_street1;
			$data['Shippingstreet2'] = $lead_data->lead_project_street2;				
			$data['ShippingStateProvince'] = $lead_data->lead_project_state;
			$data['ShippingCity'] = $lead_data->lead_project_city;				
			$data['ShippingZipPostal'] = $lead_data->lead_project_pin_zip_code;
			$data['ShippingAddress'] = $shippingAddress;
			
			// Gather Associsative Contact List for this lead
			$Associate_contact_list = $this->db->query("select * from lead_associate_contacts a inner join contacts b on (a.contact_id = b.contact_id) where a.leads_id = '".$lead_data->leads_id."'")->result();

			if(count($Associate_contact_list) > 0){
				$acli = 0;
				foreach($Associate_contact_list as $ass_val){						
					$data['associative_contacts_list'][$acli] = $ass_val;
					$acli++;
				} 				
			}else{
				$data['associative_contacts_list'] = array();				
			}
			
			// Gather Action Work Data of this Lead
			$Action_work_list = $this->db->query("select action_work_done_date, action_work_done_remarks from lead_action_work_done where leads_id = '".$lead_data->leads_id."'")->result();

			if(count($Action_work_list) > 0){
				$awli = 0;
				foreach($Action_work_list as $awl_val){						
					$data['action_work_list'][$awli] = $awl_val;
					$awli++;
				} 					
			}else{
				$data['action_work_list'] = array();					
			}
			
			$li++;
		}else{
			$data = array();
		}
		*/
		
		if(count($data)>0){
			$this->response(array('code'=>'200','message'=>'lead_view','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}	
	}

	public function lead_delete($parameters,$method,$user_id){
		$leads_id  = $parameters['leads_id'];

		if($leads_id != "" || $leads_id  != NULL){
			$param['archieve'] = "1";
			$param['modified_by'] = $user_id;
			$param['modified_date_time'] = date("Y-m-d H:i:s");
			$result=$this->Generic_model->updateData('leads',$param,array('leads_id'=>$leads_id));
			if($result){
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$latest_val['delete_status'] = "1";
				$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $leads_id,'module_name'=>'Lead'));

				$this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
	/**
	* Last Updated: 17 Nov.'20 2107hrs
	* Funciton 'delete_record' is a generic delete funciton to delete the records from the specified table name
	* @Param 'tbl' carrying name of the table
	* @param 'field' carrying the name of the comparative field in the table
	* @param 'val' carrying the value of the comparative field in the table
	*/
	public function delete_record($parameters, $method, $user_id){
		$table = $parameters['tbl'];		
		$field = $parameters['field'];
		$val = $parameters['val'];
				
		$this->db->delete($table, array($field => $val));
		
		if($this->db->affected_rows()){
			$this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Record not found'), 200);
		}
	}
	
	/*
	* Last Updated: 07 Oct.'20 0800hrs
	* Funciton 'Lead Conversion' is called when a convert button in the lead detail page is invoked
	* Updates concern lead record status to 'In-Conversion' - is_lead_converted : 1	
	* @Param integer leads_id
	*/
	public function lead_conversion($parameters, $method, $user_id){
		$leads_id = $parameters['leads_id'];
		$param['is_lead_converted'] = 1;
		
		// Update leads record is_lead_converted to 1 "In-Conversion"
		$result = $this->Generic_model->updateData('leads',$param, array('leads_id'=>$leads_id));
		if($result){
			$this->response(array('code'=>'200','message'=>'Lead conversion in progress','requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Lead conversion failed'), 200);
		}
	}
  
  
  
	public function contact_insert($parameters,$method,$user_id){
		
		$parameters['Birthdate'] = date("Y-m-d H:i:s",strtotime($parameters['Birthdate']));
		$parameters['ContactOwner'] = $user_id;
		$parameters['Created_by']=$user_id;
		$parameters['created_date_time']=date('Y-m-d H:i:s');
		$parameters['modified_by']=$user_id;
		$parameters['modified_date_time']=date('Y-m-d H:i:s');

		$contact_id=$this->Generic_model->insertDataReturnId('contacts',$parameters);

		$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
		
		if($parameters['Company'] != '' || $parameters['Company'] != '0' || $parameters['Company'] != NULL){
			$company_list = $this->db->query("select * from customers where customer_id =".$parameters['Company'])->row();
		}else{
			$company_list->CustomerName = $parameters['Company_text'];
		}

		$email = $user_list->email;
		$to = $email;
		$subject = "New Contact created";
		$data['name'] = $user_list->name;
		$data['message'] = "<p> A new Contact has been created successfully <br/><br/><b> ContactName </b> : ".$parameters['FirstName']." ".$parameters['LastName']." <br/> <b>CustomerName </b> : ".$company_list->CustomerName.", <br/><b>Email</b> : ".$parameters['Email']." ,<br/> <b>MobileNumber</b> : ".$parameters['Mobile']." and <br/><b>Fax </b> : ".$parameters['Fax']."</p> ";  
		
		$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

		$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
		
		if(count($user_report_to) >0){
			$email = $user_report_to->email;
			$to = $email;  //$to      = $dept_email_id;
			$subject = "New Contact created";
			$data['name'] = $user_report_to->name;
			$data['message'] = "<p> A new Contact has been created successfully By <b>".$user_list->name."</b><br/><br/><b> ContactName </b> : ".$parameters['FirstName']." ".$parameters['LastName']." <br/> <b>CustomerName </b> : ".$company_list->CustomerName.", <br/><b>Email</b> : ".$parameters['Email']." ,<br/> <b>MobileNumber</b> : ".$parameters['Mobile']." and <br/><b>Fax </b> : ".$parameters['Fax']."</p> ";    
			//$message = $message;
			$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
		}

		$param_noti['notiffication_type'] = "Contact";
		$param_noti['notiffication_type_id'] = $contact_id;
		$param_noti['user_id'] = $user_id;
		$param_noti['subject'] = " A new Contact has been created successfully  ContactName  : ".$parameters['FirstName']." ".$parameters['LastName']." ,CustomerName  : ".$company_list->CustomerName.", Email : ".$parameters['Email']." , <b>MobileNumber : ".$parameters['Mobile']." and Fax  : ".$parameters['Fax']."";
		$this->Generic_model->insertData("notiffication",$param_noti);


		$latest_val['module_id'] = $contact_id;
		$latest_val['module_name'] = "Contact";
		$latest_val['user_id'] = $user_id;
		$latest_val['created_date_time'] = date("Y-m-d H:i:s");
		// $latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
		$this->Generic_model->insertData("update_table",$latest_val);


		if(count($user_list)>0){
		$push_noti['fcmId_android'] = $user_list->fcmId_android;
		$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
		}else{
		$push_noti['fcmId_android'] ="";
		$push_noti['fcmId_iOS'] = "";   
		}
		if(count($user_report_to) >0){
		$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
		$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
		}else{
		$push_noti['fcmId_android_report_to'] = "";
		$push_noti['fcmId_iOS_report_to'] = "";
		}
		$push_noti['contact_id'] = $contact_id;
		$push_noti['user_id'] = $user_id;
		$push_noti['subject'] = " A new Contact has been created successfully  ContactName  : ".$parameters['FirstName']." ".$parameters['LastName']." ,CustomerName  : ".$company_list->CustomerName.", Email : ".$parameters['Email']." , <b>MobileNumber : ".$parameters['Mobile']." and Fax  : ".$parameters['Fax']."";
		$this->PushNotifications->contact_notifications($push_noti);

		$data_1['contact_id'] = $contact_id;

		$return_data = $this->all_tables_records_view("Contact",$contact_id);

	   if($contact_id != "" || $contact_id != NULL){
	   	$this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data,'requestname'=>$method));
	   }else{
	   	$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	   }
  }
  public function contact_edit($parameters,$method,$user_id){
  	//$contact_list=$this->Generic_model->getSingleRecord('contacts',array('contact_id'=>$parameters['contact_id']),$order='');
   // unset($parameters["Birthdate"]);

    $birthdate = $parameters['Birthdate'] ;
    $parameters['Birthdate'] = date("Y-m-d",strtotime($birthdate));
    $parameters['modified_by']=$user_id;
    $parameters['modified_date_time']=date('Y-m-d H:i:s');
	if($parameters['isAccountTagged']==0){
		
		$parameters['Company_text']=$parameters['Company'];
		$parameters['Company']=0;
	}
	
    $result=$this->Generic_model->updateData('contacts',$parameters,array('contact_id'=>$parameters['contact_id']));
	  if($result == 1){
       $check_update_list = $this->db->query("select * from update_table where module_id ='".$parameters['contact_id']."' and module_name ='Contact'")->row();
          if(count($check_update_list)>0){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['contact_id'],'module_name'=>'Contact'));
          }else{
            $latest_val['module_id'] = $parameters['contact_id'];
            $latest_val['module_name'] = "Contact";
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $this->Generic_model->insertData("update_table",$latest_val);
          }

          $return_data = $this->all_tables_records_view("Contact",$parameters['contact_id']);
	   	$this->response(array('code'=>'200','message'=>'Updated successfully','result'=>$return_data,'requestname'=>$method));
	  }else{
	   	$this->response(array('code'=>'404','message' => 'Authentication failed'), 200);
	  }    
  }
  public function contact_list($parameters,$method,$user_id){
  	//$data['contact_list']=$this->Generic_model->getAllRecords('contacts',$condition='',$order='');
    $final_users_id = $parameters['team_id'];
  	$contacts_list = $this->db->query("select a.*,a.created_date_time as createdDateTime, b.*, c.CustomerName from contacts a left join users b on (a.ContactOwner = b.user_id) left join customers  c on (a.Company = c.customer_id) where a.ContactOwner in (".$final_users_id.") and  a.archieve != 1 order by a.contact_id DESC")->result();

    $i=0;
    foreach($contacts_list as $contact_val){
		
		if($contact_val->ReportsTo == "" || $contact_val->ReportsTo == NULL ||$contact_val->ReportsTo == 0){
			$data['contact_list'][$i]['ReportsTo_name'] = "";
			$data['contact_list'][$i]['ReportsTo'] = "";
      }else{
         $report_detatis = $this->db->query("select * from contacts where contact_id =".$contact_val->ReportsTo)->row();
        
         if(count($report_detatis)>0){
            $data['contact_list'][$i]['ReportsTo_name'] = $report_detatis->FirstName ." ". $report_detatis->LastName;
          $data['contact_list'][$i]['ReportsTo'] = $contact_val->ReportsTo;
         }else{
           $data['contact_list'][$i]['ReportsTo_name'] = "";
        $data['contact_list'][$i]['ReportsTo'] = "";
         }
         
      }
      $data['contact_list'][$i]['contact_id'] = $contact_val->contact_id;
      $data['contact_list'][$i]['Salutation'] = $contact_val->Salutation;
      $data['contact_list'][$i]['FirstName'] = $contact_val->FirstName;
      $data['contact_list'][$i]['LastName'] = $contact_val->LastName;
      $data['contact_list'][$i]['Email'] = $contact_val->Email;
      $data['contact_list'][$i]['Fax'] = $contact_val->Fax;
      $data['contact_list'][$i]['Mobile'] = $contact_val->Mobile;
      $data['contact_list'][$i]['Phone'] = $contact_val->Phone;
      
	  
		if($contact_val->isAccountTagged == 0){
			$data['contact_list'][$i]['customer_id'] = 0;
			$data['contact_list'][$i]['Company'] = 0;
			$data['contact_list'][$i]['Company_text'] = $contact_val->Company_text;
		}else{				
			$data['contact_list'][$i]['customer_id'] = $contact_val->customer_id;
			$data['contact_list'][$i]['Company'] = $contact_val->customer_id;
			$data['contact_list'][$i]['Company_text'] = $contact_val->CustomerName;
		}
	  
	  
      
      $data['contact_list'][$i]['Department'] = $contact_val->Department;
      $data['contact_list'][$i]['Title_Designation'] = $contact_val->Title_Designation;
     // $data['contact_list'][$i]['MailingAddress'] = $contact_val->MailingAddress;
      //$data['contact_list'][$i]['OtherAddress'] = $contact_val->OtherAddress;
      $data['contact_list'][$i]['OtherPhone'] = $contact_val->OtherPhone;
      $data['contact_list'][$i]['HomePhone'] = $contact_val->HomePhone;
      $Birthdate = date("d-m-Y",strtotime($contact_val->Birthdate));
      if($Birthdate == "30-11-0001" || $Birthdate == "01-01-1970" || $Birthdate == NULL){
        $data['contact_list'][$i]['Birthdate'] = "";
      }else{
        $data['contact_list'][$i]['Birthdate'] = $Birthdate;
      }
      
      $data['contact_list'][$i]['Description'] = $contact_val->Description;
      $data['contact_list'][$i]['LeadSource'] = $contact_val->LeadSource;
      $data['contact_list'][$i]['ContactOwner'] = $contact_val->ContactOwner;
       $data['contact_list'][$i]['ContactOwner_name'] = $contact_val->name;
     // $data['contact_list'][$i]['ReportsTo'] = $contact_val->ReportsTo;
      $data['contact_list'][$i]['Category'] = $contact_val->Category;
      $data['contact_list'][$i]['MallingStreet1'] = $contact_val->MallingStreet1;
      $data['contact_list'][$i]['Mallingstreet2'] = $contact_val->Mallingstreet2;
      $data['contact_list'][$i]['MallingCountry'] = $contact_val->MallingCountry;
      $data['contact_list'][$i]['MallingStateProvince'] = $contact_val->MallingStateProvince;
      $data['contact_list'][$i]['MallingCity'] = $contact_val->MallingCity;
      $data['contact_list'][$i]['MallingZipPostal'] = $contact_val->MallingZipPostal;
      $data['contact_list'][$i]['OtherStreet1'] = $contact_val->OtherStreet1;
      $data['contact_list'][$i]['Otherstreet2'] = $contact_val->Otherstreet2;
      $data['contact_list'][$i]['OtherCountry'] = $contact_val->OtherCountry;
      $data['contact_list'][$i]['OtherStateProvince'] = $contact_val->OtherStateProvince;
      $data['contact_list'][$i]['OtherCity'] = $contact_val->OtherCity;
      $data['contact_list'][$i]['OtherZipPostal'] = $contact_val->OtherZipPostal;
	  $data['contact_list'][$i]['created_date_time'] = $contact_val->createdDateTime;
      $i++;
      
    }	
   if(count($data)>0){
   	$this->response(array('code'=>'200','message'=>'contact_list', 'result'=>$data,'requestname'=>$method));
   }else{
   	$this->response(array('code'=>'200','message' => 'Contacts are Empty','result'=>null), 200);
   }  
  }

  public function contact_view($parameters,$method,$user_id){
    $contact_id = $parameters['contact_id'];
    $contact_val = $this->db->query("select * from contacts a inner join users b on (a.ContactOwner = b.user_id) inner join   customers  c on (a.Company = c.customer_id) where a.archieve != 1 and contact_id =".$contact_id)->row();

      $data['contact_id'] = $contact_val->contact_id;
      $data['Salutation'] = $contact_val->Salutation;
      $data['FirstName'] = $contact_val->FirstName;
      $data['LastName'] = $contact_val->LastName;
      $data['Email'] = $contact_val->Email;
      $data['Fax'] = $contact_val->Fax;
      $data['Mobile'] = $contact_val->Mobile;
      $data['Phone'] = $contact_val->Phone;
      $data['Company'] = $contact_val->CustomerName;
      $data['Department'] = $contact_val->Department;
      $data['Title_Designation'] = $contact_val->Title_Designation;
      $data['MailingAddress'] = $contact_val->MailingAddress;
      $data['OtherAddress'] = $contact_val->OtherAddress;
      $data['OtherPhone'] = $contact_val->OtherPhone;
      $data['HomePhone'] = $contact_val->HomePhone;
      $Birthdate = date("d-m-Y",strtotime($contact_val->Birthdate));
      if($Birthdate == "30-11-0001" || $Birthdate == "01-01-1970" || $Birthdate == NULL){
        $data['Birthdate'] = $Birthdate;
      }else{
        $data['Birthdate'] = "";
      }
      $data['Description'] = $contact_val->Description;
      $data['LeadSource'] = $contact_val->LeadSource;
      $data['ContactOwner'] = $contact_val->name;
      $data['ReportsTo'] = $contact_val->ReportsTo;
      $data['Category'] = $contact_val->Category;
      if(count($data)>0){
        $this->response(array('code'=>'200','message'=>'contact_list', 'result'=>$data,'requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }  

  }
  public function contact_delete($parameters,$method,$user_id){
   $contact_id  = $parameters['contact_id'];

    if($contact_id != "" || $contact_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
        $result=$this->Generic_model->updateData('contacts',$param,array('contact_id'=>$contact_id));
        if($result ==1){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $latest_val['delete_status'] = "1";
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $contact_id,'module_name'=>'Contact'));

          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }


}

	/** Commenting old code
	* customer_insert function
	public function customer_insert($parameters,$method,$user_id){

    $price_list = $parameters['price_list'];
    $user_list = $parameters['user_list'];
    $user_id =$user_id;

   // print_r($parameters);   
   
    unset($parameters['user_list']);
    unset($parameters['price_list']);
    unset($parameters['primaryKey']);

    $param_c['CustomerName']  = $parameters['CustomerName'];    
    $param_c['Description']  = $parameters['Description'];
    $param_c['Phone']  = $parameters['Phone'];
    $param_c['Website']  = $parameters['Website'];
    $param_c['AccountSource']  = $parameters['AccountSource'];
    $param_c['AnnualRevenue']  = $parameters['AnnualRevenue'];
    $param_c['GSTINNumber']  = $parameters['GSTINNumber'];
    $param_c['Employees']  = $parameters['Employees'];
    $param_c['Fax']  = $parameters['Fax'];
    $param_c['Industry']  = $parameters['Industry'];   
    $param_c['Type']  = $parameters['Type'];
    $param_c['PaymentTerms']  = $parameters['PaymentTerms'];
    $param_c['pancard']  = $parameters['pancard'];
    $param_c['IncoTerms1']  = $parameters['IncoTerms1'];
    $param_c['IncoTerms2']  = $parameters['IncoTerms2'];
    $param_c['ParentAccount']  = $parameters['ParentAccount'];
    $param_c['BillingStreet1']  = $parameters['BillingStreet1'];
    $param_c['Billingstreet2']  = $parameters['Billingstreet2'];
    $param_c['BillingCountry']  = $parameters['BillingCountry'];
    $param_c['StateProvince']  = $parameters['StateProvince'];
    $param_c['BillingCity']  = $parameters['BillingCity'];
    $param_c['BillingZipPostal']  = $parameters['BillingZipPostal'];
    $param_c['ShippingStreet1']  = $parameters['ShippingStreet1'];
    $param_c['Shippingstreet2']  = $parameters['Shippingstreet2'];
    $param_c['ShippingCountry']  = $parameters['ShippingCountry'];
    $param_c['ShippingStateProvince']  = $parameters['ShippingStateProvince'];
    $param_c['ShippingCity']  = $parameters['ShippingCity'];
    $param_c['ShippingZipPostal']  = $parameters['ShippingZipPostal'];
    $param_c['SalesOrganisation']  = $parameters['SalesOrganisation'];
    $param_c['DistributionChannel']  = $parameters['DistributionChannel'];
   // $param_c['Division']  = $parameters['Division']; 
    //$param_c['price_list'] = $parameters['price_list'];
    $param_c['CustomerOwner'] = $user_id;
  	$param_c['created_by']=$user_id;
  	$param_c['created_date_time']=date('Y-m-d H:i:s');
    $param_c['modified_by']=$user_id;
    $param_c['modified_date_time']=date('Y-m-d H:i:s');
  	$result=$this->Generic_model->insertDataReturnId('customers',$param_c);


	    if($result != "" || $result != NULL){


        $ship_to_party = count($parameters['ship_to_party']);
        if($ship_to_party > 0){
          for($js=0;$js<$ship_to_party;$js++){
             $param_add['title'] = $parameters['ship_to_party'][$js]['ship_title'];
              $param_add['street'] =  $parameters['ship_to_party'][$js]['ship_street'];
              $param_add['city'] =  $parameters['ship_to_party'][$js]['ship_city'];
              $param_add['state'] =  $parameters['ship_to_party'][$js]['ship_state'];
              $param_add['country'] =  $parameters['ship_to_party'][$js]['ship_counter'];
              $param_add['pin_code'] =  $parameters['ship_to_party'][$js]['ship_pin_code'];
              $param_add['type'] = 'Ship';
              $param_add['customer_id'] = $result;
              $param_add['created_by'] = $user_id;
              $param_add['modified_by'] = $user_id;
              $param_add['created_date_time'] = date("Y-m-d H:i:s");
              $param_add['modified_date_time'] = date("Y-m-d H:i:s");
              $this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add);
                 // echo "<pre>";print_r($param_add);
            }

        }

        $sold_to_party = count($parameters['sold_to_party']);
        if($sold_to_party > 0){
          for($jso=0;$jso<$sold_to_party;$jso++){
             $param_add_1['title'] = $parameters['sold_to_party'][$jso]['sold_title'];
              $param_add_1['street'] =  $parameters['sold_to_party'][$jso]['sold_street'];
              $param_add_1['city'] =  $parameters['sold_to_party'][$jso]['sold_city'];
              $param_add_1['state'] =  $parameters['sold_to_party'][$jso]['sold_state'];
              $param_add_1['country'] =  $parameters['sold_to_party'][$jso]['sold_counter'];
              $param_add_1['pin_code'] =  $parameters['sold_to_party'][$jso]['sold_pin_code'];
              $param_add_1['type'] = 'Sold';
              $param_add_1['customer_id'] = $result;
              $param_add_1['created_by'] = $user_id;
              $param_add_1['modified_by'] = $user_id;
              $param_add_1['created_date_time'] = date("Y-m-d H:i:s");
              $param_add_1['modified_date_time'] = date("Y-m-d H:i:s");
              $this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_1);
            }

        }


        $bill_to_party = count($parameters['bill_to_party']);
        if($bill_to_party > 0){
          for($bs=0;$bs<$bill_to_party;$bs++){
             $param_add_2['title'] = $parameters['bill_to_party'][$bs]['bill_title'];
              $param_add_2['street'] =  $parameters['bill_to_party'][$bs]['bill_street'];
              $param_add_2['city'] =  $parameters['bill_to_party'][$bs]['bill_city'];
              $param_add_2['state'] =  $parameters['bill_to_party'][$bs]['bill_state'];
              $param_add_2['country'] =  $parameters['bill_to_party'][$bs]['bill_counter'];
              $param_add_2['pin_code'] =  $parameters['bill_to_party'][$bs]['bill_pin_code'];
              $param_add_2['type'] = 'Bill';
              $param_add_2['customer_id'] = $result;
              $param_add_2['created_by'] = $user_id;
              $param_add_2['modified_by'] = $user_id;
              $param_add_2['created_date_time'] = date("Y-m-d H:i:s");
              $param_add_2['modified_date_time'] = date("Y-m-d H:i:s");
              $this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_2);                 
            }

        }

        if($price_list != "" || $price_list != NULL){
          $param_price['customer_id'] = $result;
          $param_price['price_list_id'] = $price_list;
          $param_price['status'] = "Active";
          $param_price['created_by']=$user_id;
          $param_price['created_date_time']=date('Y-m-d H:i:s');
          $param_price['modified_by']=$user_id;
          $param_price['modified_date_time']=date('Y-m-d H:i:s');
          $this->Generic_model->insertData('customer_price_list',$param_price);
        }

          $param_2['customer_id'] = $result;
          $param_2['user_id'] = $user_id;
          $param_2["created_by"] = $user_id;
          $param_2['modified_by'] = $user_id;
          $param_2['created_date_time'] = date('Y-m-d H:i:s');
          $param_2['modified_date_time']=date('Y-m-d H:i:s');
          $customer_user_map = $this->Generic_model->insertData('customer_users_maping',$param_2);

            $latest_val['module_id'] = $result;
            $latest_val['module_name'] = "Customer";
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $this->Generic_model->insertData("update_table",$latest_val);



          $user_list = $this->db->query("select * from users where user_id = '".$user_id."'")->row();

              $email = $user_list->email;
              $to = $email;  //$to      = $dept_email_id;
              $subject = "New Customer created";
              $data['name'] = $user_list->name;
              $data['message'] = "<p> A new Customer has been created successfully <br/><br/><b> CustomerName </b> : ".$parameters['CustomerName']." <br/><b>Website</b> : ".$parameters['Website']." ,<br/> <b>MobileNumber</b> : ".$parameters['Phone']."  <br/><b>AnnualRevenue </b> : ".$parameters['AnnualRevenue']."  <br/><b>Shipping Address :</b>".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ".$parameters['ShippingZipPostal']."<br/><b>Billing Address</b>".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."</p> ";  
              //$message = $message;
              $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

              $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."'")->row();
              if(count($user_report_to) >0){
                $email = $user_report_to->email;
                $to = $email;  //$to      = $dept_email_id;
                $subject = "New Customer created";
                $data['name'] = $user_report_to->name; 

                $data['message'] = "<p> A new Customer has been created successfully By <b>".$user_list->name."</b> <br/><br/<b> CustomerName </b> : ".$parameters['CustomerName']." <br/><b>Website</b> : ".$parameters['Website']." ,<br/> <b>MobileNumber</b> : ".$parameters['Phone']."  <br/><b>AnnualRevenue </b> : ".$parameters['AnnualRevenue']."  <br/><b>Shipping Address :</b>".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal']."<br/><b>Billing Address</b>".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."</p> "; 
                //$message = $message;
                $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

              }

               $param_noti['notiffication_type'] = "Customer";
              $param_noti['notiffication_type_id'] = $result;
              $param_noti['user_id'] = $user_id;
              $param_noti['subject'] =  "A new Customer has been created successfully  CustomerName  : ".$parameters['CustomerName']." Website : ".$parameters['Website']." ,MobileNumber : ".$parameters['Phone']." AnnualRevenue : ".$parameters['AnnualRevenue']."  Shipping Address :".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal'].", Billing Address".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."";
              $this->Generic_model->insertData("notiffication",$param_noti);


              if(count($user_list)>0){
                $push_noti['fcmId_android'] = $user_list->fcmId_android;
                $push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
              }else{
                $push_noti['fcmId_android'] ="";
                $push_noti['fcmId_iOS'] = "";   
              }
              if(count($user_report_to) >0){
                $push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
                $push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
              }else{
                $push_noti['fcmId_android_report_to'] = "";
                $push_noti['fcmId_iOS_report_to'] = "";
              }
              $push_noti['customer_id'] = $result;
              $push_noti['user_id'] = $user_id;
              $push_noti['subject'] = " A new Customer has been created successfully  CustomerName  : ".$parameters['CustomerName']." Website : ".$parameters['Website']." ,MobileNumber : ".$parameters['Phone']." AnnualRevenue : ".$parameters['AnnualRevenue']."  Shipping Address :".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal'].", Billing Address".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."";
              $this->PushNotifications->customer_notifications($push_noti);
       
      $data_1['customer_id'] = $result;

       $return_data = $this->all_tables_records_view("Customer",$result);
	   	$this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data,'requestname'=>$method));
	   }else{
	   	$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	   }
  }
  * commenting end */  
	
	public function customer_edit($parameters,$method,$user_id){
		
		$customer_data = $parameters;
		
		// unset unwanted params 
		unset($customer_data['sold_to_party']);
		unset($customer_data['bill_to_party']);
		unset($customer_data['ship_to_party']);    	
		
		$customer_list = $this->Generic_model->getSingleRecord('customers',array('customer_id'=>$parameters['Customer_id']),$order='');
		$price_list_id = $parameters['price_list'];
		
		$customer_data['modified_by']=$user_id;
		$customer_data['modified_date_time']=date('Y-m-d H:i:s');
		
		$result = $this->Generic_model->updateData('customers',$customer_data,array('customer_id'=>$parameters['Customer_id']));
		
		// if Update successfull
		if($result == 1){
			
			// Update Ship to Pavtrty details
			$ship_to_party = count($parameters['ship_to_party']);
			if($ship_to_party > 0){
				for($js=0;$js<$ship_to_party;$js++){
					
					$param_add['title'] = $parameters['ship_to_party'][$js]['title'];
					$param_add['street'] =  $parameters['ship_to_party'][$js]['street'];
					$param_add['city'] =  $parameters['ship_to_party'][$js]['city'];
					$param_add['state'] =  $parameters['ship_to_party'][$js]['state'];
					$param_add['country'] =  $parameters['ship_to_party'][$js]['country'];
					$param_add['pin_code'] =  $parameters['ship_to_party'][$js]['pin_code'];
					$param_add['type'] = 'Ship';
					$param_add['customer_id'] = $parameters['Customer_id'];
					$param_add['modified_by'] = $user_id;
					$param_add['modified_date_time'] = date("Y-m-d H:i:s");

					if($parameters['ship_to_party'][$js]['customer_address_sold_bill_ship_id'] != "" || $parameters['ship_to_party'][$js]['customer_address_sold_bill_ship_id'] != null){
						$this->Generic_model->updateData('customer_address_sold_bill_ship', $param_add, array('customer_address_sold_bill_ship_id' => $parameters['ship_to_party'][$js]['customer_address_sold_bill_ship_id']));
					}else{
						$param_add_2['created_by'] = $user_id;
						$param_add_2['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add);              
					}   
				}
			}

			// update Sold to Party details
			$sold_to_party = count($parameters['sold_to_party']);
			if($sold_to_party > 0){
				for($jso=0;$jso<$sold_to_party;$jso++){
					$param_add_1['title'] = $parameters['sold_to_party'][$jso]['title'];
					$param_add_1['street'] =  $parameters['sold_to_party'][$jso]['street'];
					$param_add_1['city'] =  $parameters['sold_to_party'][$jso]['city'];
					$param_add_1['state'] =  $parameters['sold_to_party'][$jso]['state'];
					$param_add_1['country'] =  $parameters['sold_to_party'][$jso]['country'];
					$param_add_1['pin_code'] =  $parameters['sold_to_party'][$jso]['pin_code'];
					$param_add_1['type'] = 'Sold';
					$param_add_1['customer_id'] = $parameters['Customer_id'];
					//$param_add_1['created_by'] = $user_id;
					$param_add_1['modified_by'] = $user_id;
					//$param_add_1['created_date_time'] = date("Y-m-d H:i:s");
					$param_add_1['modified_date_time'] = date("Y-m-d H:i:s");

					if($parameters['sold_to_party'][$jso]['customer_address_sold_bill_ship_id'] != "" || $parameters['sold_to_party'][$jso]['customer_address_sold_bill_ship_id'] != null){
						$this->Generic_model->updateData('customer_address_sold_bill_ship', $param_add_1, array('customer_address_sold_bill_ship_id' => $parameters['sold_to_party'][$jso]['customer_address_sold_bill_ship_id']));
					}else{
						$param_add_2['created_by'] = $user_id;
						$param_add_2['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_1);
					}   
				}
			}

			// Update Bill to party details
			$bill_to_party = count($parameters['bill_to_party']);
			if($bill_to_party > 0){
				for($bs=0;$bs<$bill_to_party;$bs++){
					$param_add_2['title'] = $parameters['bill_to_party'][$bs]['title'];
					$param_add_2['street'] =  $parameters['bill_to_party'][$bs]['street'];
					$param_add_2['city'] =  $parameters['bill_to_party'][$bs]['city'];
					$param_add_2['state'] =  $parameters['bill_to_party'][$bs]['state'];
					$param_add_2['country'] =  $parameters['bill_to_party'][$bs]['country'];
					$param_add_2['pin_code'] =  $parameters['bill_to_party'][$bs]['pin_code'];
					$param_add_2['type'] = 'Bill';
					$param_add_2['customer_id'] = $parameters['Customer_id'];
					$param_add_2['modified_by'] = $user_id;
					$param_add_2['modified_date_time'] = date("Y-m-d H:i:s");
					if($parameters['bill_to_party'][$bs]['customer_address_sold_bill_ship_id'] != "" || $parameters['bill_to_party'][$bs]['customer_address_sold_bill_ship_id'] != null){
						$this->Generic_model->updateData('customer_address_sold_bill_ship', $param_add_2, array('customer_address_sold_bill_ship_id' => $parameters['bill_to_party'][$bs]['customer_address_sold_bill_ship_id']));
					}else{
						$param_add_2['created_by'] = $user_id;
						$param_add_2['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_2);
					} 
				}
			}

			// Check update list db
			$check_update_list = $this->db->query("select * from update_table where module_id ='".$parameters['Customer_id']."' and module_name ='Customer'")->row();
			if(count($check_update_list)>0){
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['Customer_id'],'module_name'=>'Customer'));
			}

			// get price list for the particular customer
			if($price_list_id != "" || $price_list_id != NULL){
				$checking_price_list = $this->db->query("select * from customer_price_list where status='Active' and customer_id ='".$parameters['Customer_id']."'")->row();
				if($price_list_id != $checking_price_list->price_list_id){
					$param_1['customer_id'] = $parameters['Customer_id'];
					$param_1['price_list_id'] = $price_list_id;
					$param_1['status'] = "Active";
					$param_1["created_by"] = $user_id;
					$param_1['modified_by'] = $user_id;
					$param_1['created_date_time'] = date('Y-m-d H:i:s');
					$param_1['modified_date_time']=date('Y-m-d H:i:s');
					$ok1 = $this->Generic_model->insertData("customer_price_list",$param_1);
					if($ok1 == 1){
						$param_3['status'] = "Inactive";
						$this->Generic_model->updateData('customer_price_list', $param_3, array('customer_price_list_id' => $checking_price_list->customer_price_list_id));
					}
				}
			}
			
			$return_data = $this->all_tables_records_view("Customer",$parameters['Customer_id']);
			$this->response(array('code'=>'200','message'=>'Updated successfully','result'=>$return_data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}    
	}
	
	/**
	Function getCustomerInfo will retrieve data belongs to customer
	*/
	public function getCustomerInfo($parameters,$method,$user_id){
		extract($parameters);
		$compare = "";
		if($pancard != '' || $pancard != NULL){
			$compare .= "pancard = '".$pancard."'";
		}
		
		if($GSTINNumber != '' || $GSTINNumber != NULL){
			$compare .= $compare != "" ? " OR GSTINNumber = '".$GSTINNumber."'" : "GSTINNumber = '".$GSTINNumber."'";
		}
		
		if($compare != ""){
			// check the customer existence with provided GST / Pan 
			$customerInfo = $this->db->query("SELECT customer_id, pancard, GSTINNumber FROM customers WHERE ".$compare)->row();
		}
		
		if(count($customerInfo) > 0){
			$return_data = $this->all_tables_records_view("Customer",$customerInfo->customer_id);
			$this->response(array('code'=>'200','message'=>'Customer found', 'result'=>$return_data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'200','message' => 'No Customer Found','result' => NULL,'requestname' => $method), 200);			
		}		
	}
	
	/**
	Function mapCustomerUser will map concern customer and user respectively
	*/
	public function mapCustomerUser($parameters,$method,$user_id){
		
		$param = $parameters;
		
		// Check record with customer_id and user_id for to avoid duplicate record entries
		$res = $this->db->query("SELECT * FROM customer_users_maping WHERE customer_id = '".$param['customer_id']."' AND user_id = '".$param['user_id']."'")->result();
		
		if(count($res) == 0){			
			$param["created_by"] = $user_id;
			$param['modified_by'] = $user_id;
			$param['created_date_time'] = date('Y-m-d H:i:s');
			$param['modified_date_time'] = date('Y-m-d H:i:s');
			
			$okay = $this->Generic_model->insertData("customer_users_maping",$param);
			
			if($okay){
				$return_data = $this->all_tables_records_view("Customer",$param['customer_id']);
				$this->response(array('code'=>'200','message'=>'Customer & User both mapped successfully', 'result'=>$return_data,'requestname'=>$method));
			}else{
				$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
			}
		}else{
			$this->response(array('code'=>'200','message' => 'Mapping already exists', 'result' => NULL, 'requestname' => $method), 200);
		}		
		
	}
  
  
	public function customer_list($parameters,$method,$user_id){
	  
		$final_users_id = $parameters['team_id'];
		$role_id= $parameters['role_id'];        
	
		$customers = $this->db->query("select a.*, a.created_date_time as createdDateTime from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
			
		$contacts = $this->db->query("SELECT CUST.*, CUST.created_date_time as createdDateTime from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 ORDER BY CON.contact_id DESC")->result(); 
		
		$associate_contacts = $this->db->query("SELECT CUST.*, CUST.created_date_time as createdDateTime FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 GROUP BY CON.contact_id")->result();
		
		$customer_list = array_merge($customers, $contacts, $associate_contacts);
	
		//$customer_list = $this->db->query("select *, a.created_date_time as createdDateTime from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
		
		if(count($customer_list)>0){			
			$ic=0;
			foreach ($customer_list as $customer_val) {
				$customer_user_list = $this->db->query("select * from customer_users_maping a inner join users b on (a.user_id = b.user_id) where customer_id =".$customer_val->customer_id)->result();               
				$data['customer_list'][$ic]['customer_id']=$customer_val->customer_id;
				$data['customer_list'][$ic]['CustomerName']=$customer_val->CustomerName;
				$data['customer_list'][$ic]['CustomerSAPCode']=$customer_val->CustomerSAPCode;
				$data['customer_list'][$ic]['customer_number']=$customer_val->customer_number;
				$data['customer_list'][$ic]['approve_status']=$customer_val->approve_status;
				$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;				
				$data['customer_list'][$ic]['Description']=$customer_val->Description;
				$data['customer_list'][$ic]['Phone']=$customer_val->Phone;
				$data['customer_list'][$ic]['Website'] = $customer_val->Website;
				$data['customer_list'][$ic]['AccountSource']=$customer_val->AccountSource;
				$data['customer_list'][$ic]['AnnualRevenue']=$customer_val->AnnualRevenue;
				$data['customer_list'][$ic]['GSTINNumber']=$customer_val->GSTINNumber;
				$data['customer_list'][$ic]['Employees']=$customer_val->Employees;
				$data['customer_list'][$ic]['contact_id']=$customer_val->contact_id;
				$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
				
				if($customer_val->PaymentTerms != 0 || $customer_val->PaymentTerms != "" || $customer_val->PaymentTerms != NULL){
					$PaymentTerms_list = $this->db->query("select * from Payment_terms where Payment_term_id =".$customer_val->PaymentTerms)->row();
					$data['customer_list'][$ic]['PaymentTerms']=$PaymentTerms_list->Payment_name;
				}
				
				$data['customer_list'][$ic]['pancard']=$customer_val->pancard;
				
				// Billing Street Info
				$data['customer_list'][$ic]['BillingStreet1']=$customer_val->BillingStreet1;
				$data['customer_list'][$ic]['Billingstreet2']=$customer_val->Billingstreet2;
				$data['customer_list'][$ic]['BillingCountry']=$customer_val->BillingCountry;
				$data['customer_list'][$ic]['StateProvince']=$customer_val->StateProvince;
				$data['customer_list'][$ic]['BillingCity']=$customer_val->BillingCity;
				$data['customer_list'][$ic]['BillingZipPostal']=$customer_val->BillingZipPostal;
				
				// Shipping Street Info
				$data['customer_list'][$ic]['ShippingStreet1']=$customer_val->ShippingStreet1;
				$data['customer_list'][$ic]['Shippingstreet2']=$customer_val->Shippingstreet2;
				$data['customer_list'][$ic]['ShippingCountry']=$customer_val->ShippingCountry;
				$data['customer_list'][$ic]['ShippingStateProvince']=$customer_val->ShippingStateProvince;
				$data['customer_list'][$ic]['ShippingCity']=$customer_val->ShippingCity;				
				$data['customer_list'][$ic]['ShippingZipPostal']=$customer_val->ShippingZipPostal;
				
				// Sales Organization				
				if($customer_val->SalesOrganisation != "" || $customer_val->SalesOrganisation != NULL){
					$SalesOrganisation_list = $this->db->query("select * from sales_organisation where sap_code= '".$customer_val->SalesOrganisation."'")->row();
					if(count($SalesOrganisation_list)>0){
						$data['customer_list'][$ic]['SalesOrganisation_id']=$SalesOrganisation_list->sap_code;
						$data['customer_list'][$ic]['SalesOrganisation']=$SalesOrganisation_list->organistation_name;
					}else{
						$data['customer_list'][$ic]['SalesOrganisation_id']="";
						$data['customer_list'][$ic]['SalesOrganisation']="";
					}
				}else{
					$data['customer_list'][$ic]['SalesOrganisation_id']="";
					$data['customer_list'][$ic]['SalesOrganisation']="";
				}
				
				// Distribution Channel
				if($customer_val->DistributionChannel != "" || $customer_val->DistributionChannel != NULL){
					$DistributionChannel_list = $this->db->query("select * from DistributionChannel where sap_code= '".$customer_val->DistributionChannel."'")->row();
					if(count($DistributionChannel_list)>0){
						$data['customer_list'][$ic]['DistributionChannel_id']=$DistributionChannel_list->sap_code;
						$data['customer_list'][$ic]['DistributionChannel']=$DistributionChannel_list->ditribution_name;
					}else{
						$data['customer_list'][$ic]['DistributionChannel_id']="";
						$data['customer_list'][$ic]['DistributionChannel']="";
					}
				}else{
					$data['customer_list'][$ic]['DistributionChannel_id']="";
					$data['customer_list'][$ic]['DistributionChannel']="";
				}
				
				// Division Info
				/*
				if($customer_val->Division != "" || $customer_val->Division != NULL){
					$Division_list = $this->db->query("select * from division_master where division_master_id = '".$customer_val->Division."'")->row();
					if(count($Division_list)>0){
						$data['customer_list'][$ic]['division_master_id']=$Division_list->division_master_id;
						$data['customer_list'][$ic]['Division']=$Division_list->division_name;
					}else{
						$data['customer_list'][$ic]['division_master_id']="";
						$data['customer_list'][$ic]['Division']="";
					}
				}else{
					$data['customer_list'][$ic]['division_master_id']="";
					$data['customer_list'][$ic]['Division']="";
				}
				*/

				$data['customer_list'][$ic]['Division']=$customer_val->Division;
				$data['customer_list'][$ic]['CustomerType']=$customer_val->CustomerType;
				$data['customer_list'][$ic]['Email']=$customer_val->Email;
				$data['customer_list'][$ic]['CustomerCategory']=$customer_val->CustomerCategory;
				$data['customer_list'][$ic]['CreditLimit']=$customer_val->CreditLimit;
				$data['customer_list'][$ic]['SecurityInstruments']=$customer_val->SecurityInstruments;
				$data['customer_list'][$ic]['Pdc_Check_number']=$customer_val->Pdc_Check_number;
				$data['customer_list'][$ic]['Bank']=$customer_val->Bank;
				$data['customer_list'][$ic]['Bank_guarntee_amount_Rs']=$customer_val->Bank_guarntee_amount_Rs;
				$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;

				if($customer_val->IncoTerms1 != 0 || $customer_val->IncoTerms1 != "" || $customer_val->IncoTerms1 != NULL){
					$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms1)->row();
					$data['customer_list'][$ic]['IncoTerms1']=$IncoTerms_list->Incoterm_name;
				}
        
				if($customer_val->IncoTerms2 != 0 || $customer_val->IncoTerms2 != "" || $customer_val->IncoTerms2 != NULL){
					$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms2)->row();
					$data['customer_list'][$ic]['IncoTerms2']=$IncoTerms_list->Incoterm_name;
				}

				$data['customer_list'][$ic]['Fax']=$customer_val->Fax;
				$data['customer_list'][$ic]['Industry']=$customer_val->Industry;
				$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;
				
				$customer_price_list = $this->db->query("select * from customer_price_list a inner join product_price_master b on (a.price_list_id = b.Product_price_master_id) where a.status='Active' and a.customer_id = ".$customer_val->customer_id)->row();		
				
				if($customer_price_list->price_list_id != 0 || $customer_price_list->price_list_id != "" || $customer_price_list->price_list_id != NULL){					
					$data['customer_list'][$ic]['price_list_id']=$customer_price_list->price_list_id;
				}else{					
					$data['customer_list'][$ic]['price_list_id']="";
				}

				$data['customer_list'][$ic]['ParentAccount']=$customer_val->ParentAccount;
				$data['customer_list'][$ic]['created_by']=$customer_val->created_by;
				$data['customer_list'][$ic]['created_date_time']=$customer_val->createdDateTime;
				$data['customer_list'][$ic]['manager_user_id']=$customer_val->manager_user_id;
					
				// Get Sales, Bill & Ship to Party details
				$sbs_list = $this->db->query("SELECT * FROM customer_address_sold_bill_ship WHERE customer_id = ".$customer_val->customer_id)->result();
				if(count($sbs_list) > 0){
					$x = 0;
					foreach($sbs_list as $record){
						$data['customer_list'][$ic][$record->type.'_to_party'][] = $record;
					}
				}
        
				if(count($customer_user_list)>0){
					$jc=0;
					foreach($customer_user_list as $customer_user_val){
						$data['customer_list'][$ic]['user_details'][$jc]["customer_user_id"]=$customer_user_val->customer_user_id;
						$data['customer_list'][$ic]['user_details'][$jc]["user_name"]=$customer_user_val->name;
						$jc++;
					}
				}else{
					$data['customer_list'][$ic]['user_details'] = array();
				}				
				$ic++; 
			}
			$this->response(array('code'=>'200','message'=>'customer_list', 'result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'200','message' => 'Customers are Empty','result'=>null), 200);
		}
	}

  public function customer_user_delete($parameters,$method,$user_id){
    $customer_user_id = $parameters['customer_user_id'];
     $ok = $this->Generic_model->deleteRecord('customer_users_maping',array('customer_user_id'=>$customer_user_id));
     if($ok ==1){
      $this->response(array('code'=>'200','message'=>'sucessfully deleted', 'result'=>$data,'requestname'=>$method)); 
     }else{
       $this->response(array('code'=>'200','message' => 'Customers are Empty'), 200);
     }
  }
  // public function customer_user_delete($parameters,$method,$user_id){
  //   $customer_user_id = $parameters['customer_user_id'];
  //    $ok = $this->Generic_model->deleteRecord('customer_users_maping',array('customer_user_id'=>$customer_user_id));
  //    if($ok ==1){
  //     $this->response(array('code'=>'200','message'=>'sucessfully deleted', 'result'=>$data,'requestname'=>$method)); 
  //    }else{
  //      $this->response(array('code'=>'404','message' => 'Customers are Empty'), 200);
  //    }
  // }

  public function customer_user_list($parameters,$method,$user_id){
    $customer_id = $parameters['customer_id'];
    $customer_list = $this->db->query("SELECT * FROM users where user_id not in (select user_id from customer_users_maping where customer_id =".$customer_id.") AND status = 'Active'")->result();
    $i=0;
    foreach($customer_list as $customer_val){
      $data['customer_user_list'][$i]['user_id'] = $customer_val->user_id;
      $data['customer_user_list'][$i]['user_name'] = $customer_val->name;
      $i++;
    }
     $this->response(array('code'=>'200','message'=>'sucessfully deleted', 'result'=>$data,'requestname'=>$method)); 
  }

  public function customer_user_insert($parameters,$method,$user_id){
    $users_list = count($parameters['user_list']);
    for($i=0;$i<$users_list;$i++){
      $param_1['customer_id'] = $parameters['customer_id'];
      $param_1['user_id'] = $parameters['user_list'][$i]['user_id'];
      $param_1["created_by"] = $user_id;
      $param_1['modified_by'] = $user_id;
      $param_1['created_date_time'] = date('Y-m-d H:i:s');
      $param_1['modified_date_time']=date('Y-m-d H:i:s');
      $ok1 = $this->Generic_model->insertData("customer_users_maping",$param_1);
    }
    if($ok1 == 1){
      $this->response(array('code'=>'200','message'=>'sucessfully Inserted', 'result'=>$data,'requestname'=>$method));
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }

  }


  public function customer_view($parameters,$method,$user_id){
    $customer=$this->db->query('select * from customers c inner join users u on c.CustomerOwner = u.user_id where customer_id='.$parameters['customer_id'])->row();
	
    if(count($customer)>0){
        $data['customer_id']=$customer->customer_id;
      $data['CustomerName']=$customer->CustomerName;
      $data['CustomerSAPCode']=$customer->CustomerSAPCode;
      $data['CustomerOwner']=$customer->name;
      $data['Description']=$customer->Description;
      $data['Phone']=$customer->Phone;
      $data['AccountSource']=$customer->AccountSource;
      $data['AnnualRevenue']=$customer->AnnualRevenue;
      $data['GSTINNumber']=$customer->GSTINNumber;
      $data['Employees']=$customer->Employees;
      $data['Fax']=$customer->Fax;
      $data['Industry']=$customer->Industry;
      $data['Type']=$customer->Type;
      $data['PaymentTerms']=$customer->PaymentTerms;
      $data['IncoTerms']=$customer->IncoTerms;
      $data['Ownership']=$customer->Ownership;
      $data['ParentAccount']=$customer->ParentAccount;
      $data['BillingStreet1']=$customer->BillingStreet1;
      $data['Billingstreet2']=$customer->Billingstreet2;
      $data['BillingCountry']=$customer->BillingCountry;
      $data['StateProvince']=$customer->StateProvince;
      $data['BillingCity']=$customer_val->BillingCity;
     // $data['Billing']=$customer->Billing;
      $data['BillingZipPostal']=$customer->BillingZipPostal;
      $data['ShippingStreet1']=$customer->ShippingStreet1;
      $data['Shippingstreet2']=$customer->Shippingstreet2;
      $data['ShippingCountry']=$customer->ShippingCountry;
      $data['ShippingStateProvince']=$customer->ShippingStateProvince;
      $data['ShippingCity']=$customer->ShippingCity;
      //$data['Shipping']=$customer->Shipping;
      $data['ShippingZipPostal']=$customer->ShippingZipPostal;
      $data['ShippingCity']=$customer->ShippingCity;
      $data['ShippingCity']=$customer->ShippingCity;
      $data['ShippingCity']=$customer->ShippingCity;


      //$customer_user_list = $this->db->query("select * from ")
       $this->response(array('code'=>'200', 'message'=>'customer_view','result'=>$data,'requestname'=>$method)); 
  }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
   }  
  
}
//function customer_view($id) {
public function customer_delete($parameters,$method,$user_id){
   $customer_id  = $parameters['customer_id'];

    if($customer_id != "" || $customer_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
	  $param['pancard'] =NULL;
		$param['GSTINNumber'] =NULL;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
       $result=$this->Generic_model->updateData('customers',$param,array('customer_id'=>$parameters['customer_id']));
        if($result ==1){

            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $latest_val['delete_status'] = "1";
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['customer_id'],'module_name'=>'Customer'));

          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }


}

  public function leads_convert_check($parameters,$method,$user_id){
   	$lead_id = $parameters['leads_id'];
  	//$customer_check = $this->db->query("select * from leads a inner join  customers b on (a.Company = b.CustomerName) where b.archieve != 1 and a.leads_id =".$lead_id)->row();
    $lead_list = $this->db->query("select * from leads where leads_id = ".$lead_id)->row();
    $customer_check = $this->db->query("select * from customers  where CustomerName ='".$lead_list->Company."'")->result();

    $i=0;
        foreach($customer_check as $values){
          $customer_user = $this->db->query("select * from customers a inner join customer_users_maping b on (a.customer_id = b.customer_id) where b.customer_id ='".$values->customer_id."' and user_id = '".$user_id."' group by b.customer_id")->row();
            if(count($customer_user)>0){
              $data['checking_customer'][$i]['customer_id'] = $customer_user->customer_id;
              $data['checking_customer'][$i]['customer_name'] = $customer_user->CustomerName;
              $i++;
            }
        }
          $data['checking_customer'][$i]['customer_id'] = "0";
          $data['checking_customer'][$i]['customer_name'] = "Create New ".$lead_list->Company."";

          $data['contact_name']  = $lead_list->FirstName." ".$lead_list->LastName;
      
  	if(count($data)>0){
  		$this->response(array('code'=>'200','message'=>'customer_list', 'result'=>$data,'requestname'=>$method));
  	}else{
  		$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  	}


  }
  public function lead_convert($parameters,$method,$user_id) {
    $leads_id = $parameters['leads_id'];
    $customer_check = $parameters['customer_check'];
    $opportunity_check = $parameters['opportunity_check'];  
    $leads_list = $this->db->query("select * from  leads where  leads_id ='".$leads_id."' and status !='convert'")->row();
    if(count($leads_list)>0){

        if($opportunity_check == "0"){
          if($customer_check == "0" || $customer_check == "" || $customer_check == NULL ) {
            $param_1['CustomerName'] = $leads_list->Company;
            $param_1['CustomerOwner'] = $user_id;
            $param_1['Description'] = $leads_list->Description;
            $param_1['Phone'] = $leads_list->Phone;
            $param_1['Website'] = $leads_list->Website;
            $param_1['AccountSource'] = $leads_list->LeadSource;
            $param_1['AnnualRevenue'] = $leads_list->AnnualRevenue;
            $param_1['Employees'] = $leads_list->No_Employees;
            $param_1['Fax'] = $leads_list->Fax;
            $param_1['Industry'] = $leads_list->Industry;
            $param_1['BillingStreet1'] = $leads_list->BillingStreet1;
            $param_1['Billingstreet2'] = $leads_list->Billingstreet2;
            $param_1['BillingCountry'] = $leads_list->BillingCountry;
            $param_1['StateProvince'] = $leads_list->StateProvince;
            $param_1['BillingCity'] = $leads_list->BillingCity;
            //$param_1['Billing'] = $leads_list->Billing;
            $param_1['BillingZipPostal'] = $leads_list->BillingZipPostal;
            $param_1['ShippingStreet1'] = $leads_list->ShippingStreet1;
            $param_1['Shippingstreet2'] = $leads_list->Shippingstreet2;
            $param_1['ShippingCountry'] = $leads_list->ShippingCountry;
            $param_1['ShippingStateProvince'] = $leads_list->ShippingStateProvince;
            $param_1['ShippingCity'] = $leads_list->ShippingCity;
            //$param_1['Shipping'] = $leads_list->Shipping;
            $param_1['ShippingZipPostal'] = $leads_list->ShippingZipPostal;
            $param_1['Created_by'] = $user_id;
            $param_1['modified_by'] = $user_id;
            $param_1['created_date_time'] = date("Y-m-d H:i:s");
            $param_1['modified_date_time'] = date("Y-m-d H:i:s");
            $customer_id = $this->Generic_model->insertDataReturnId("customers",$param_1);
          }else{
            //$customer_check = $this->db->query("select * from customers where CustomerName = '".$leads_list->Company."'")->row();
            
              $customer_id = $customer_check;
            
            
          }
          if($customer_id != "" || $customer_id != NULL){
            $check_update_list = $this->db->query("select * from update_table where module_id ='".$customer_id."' and module_name ='Customer'")->row();
                  if(count($check_update_list)>0){
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $customer_id,'module_name'=>'Customer'));
                  }else{
                    $latest_val['module_id'] = $customer_id;
                    $latest_val['module_name'] = "Customer";
                    $latest_val['user_id'] = $user_id;
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val);
                  }
          }
          if($customer_id != ""){
                $param['FirstName'] = $leads_list->FirstName;
                $param['LastName'] =  $leads_list->LastName;
                $param['Email'] =  $leads_list->Email;
                $param['Fax'] = $leads_list->Fax;
                $param['Company'] = $customer_id;
                $param['Mobile'] = $leads_list->Mobile;
                $param['Phone'] = $leads_list->Phone;
                $param['Title_Designation'] = $leads_list->Title;
                $param['Description'] = $leads_list->Description;
                $param['DoNotCall'] = $leads_list->DoNotCall;
                $param['Category'] = $leads_list->Category;
                $param['LeadSource'] = $leads_list->LeadSource;
                $param['Created_by'] = $user_id;
                $param['modified_by'] = $user_id;
                $param['ContactOwner'] = $user_id;
                $param['created_date_time'] = date("Y-m-d H:i:s");
                $param['modified_date_time'] = date("Y-m-d H:i:s");
                $contact_id = $this->Generic_model->insertDataReturnId('contacts',$param);
                if($contact_id != "" || $contact_id != NULL){
                    $latest_val_1['module_id'] = $contact_id;
                    $latest_val_1['module_name'] = "Contact";
                    $latest_val_1['user_id'] = $user_id;
                    $latest_val_1['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val_1);

                  }



                $param_3['status'] = "convert";
                $param_3['modified_by'] = $user_id;
                $param_3['modified_date_time'] = date("Y-m-d H:i:s");
                $ok1 = $this->Generic_model->updateData('leads',$param_3,array('leads_id'=>$leads_id));
                 $check_update_list_lead = $this->db->query("select * from update_table where module_id ='".$leads_id."' and module_name ='Lead'")->row();
                  if(count($check_update_list_lead)>0){
                    $latest_val_2['user_id'] = $user_id;
                    $latest_val_2['created_date_time'] = date("Y-m-d H:i:s");
                    $latest_val_2['delete_status'] = "1";
                    $ok = $this->Generic_model->updateData('update_table', $latest_val_2, array('module_id' => $leads_id,'module_name'=>'Lead'));
                  }else{
                    $latest_val_2['module_id'] = $leads_id;
                    $latest_val_2['module_name'] = "Lead";
                    $latest_val_2['user_id'] = $user_id;
                    $latest_val_2['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val_2);
                  }
                  $param_21['customer_id'] = $customer_id;
                  $param_21['user_id'] = $user_id;
                  $param_21["created_by"] = $user_id;
                  $param_21['modified_by'] = $user_id;
                  $param_21['created_date_time'] = date('Y-m-d H:i:s');
                  $param_21['modified_date_time']=date('Y-m-d H:i:s');

                  $customer_user_check = $this->db->query("select * from customer_users_maping where customer_id='".$customer_id."' and user_id ='".$user_id."'")->row();
                  if(count($customer_user_check) >0){
                    $customer_user_map = $this->Generic_model->updateData('customer_users_maping', $param_21, array('customer_user_id' => $customer_user_check->customer_user_id));
                  }else{
                    $customer_user_map = $this->Generic_model->insertData('customer_users_maping',$param_21);
                  }
                  

                  }else{
                    $this->response(array('code'=>'404','message' => 'Authentication4 Failed'), 200);
                  }

                  if($ok1 == 1){

            $user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
                          $email = $user_list->email;
                          $to = $email;  //$to      = $dept_email_id;
                          $subject = "Lead Converted";
                          $param_24['name'] = $user_list->name;
                          $param_24['message'] = "<p> A lead has been converted successfully into customers and contact  <br/><br/><b> LeadName </b> : ".$leads_list->FirstName." ".$leads_list->LastName." <br/> <b>CustomerName </b> : ".$leads_list->Company.", <br/><b>Email</b> : ".$leads_list->Email." ,<br/> <b>MobileNumber</b> : ".$leads_list->Mobile." and <br/><b>Website </b> : ".$leads_list->Website."</p> ";  
                          //$message = $message;
                          $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$param_24);

                          $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
                          if(count($user_report_to) >0){
                            $email = $user_report_to->email;
                            $to = $email;  //$to      = $dept_email_id;
                            $subject = "Lead Converted";
                            $param_24['name'] = $user_report_to->name;
                            $param_24['message'] = "<p> A lead has been converted successfully into customers and contact by <b>".$user_list->name."</b>  <br/><br/><b> LeadName </b> : ".$leads_list->FirstName." ".$leads_list->LastName." <br/> <b>CustomerName </b> : ".$leads_list->Company.", <br/><b>Email</b> : ".$leads_list->Email." ,<br/> <b>MobileNumber</b> : ".$leads_list->Mobile." and <br/><b>Website </b> : ".$leads_list->Website."</p> ";  
                            //$message = $message;
                            $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$param_24);

                          }
                       $param_noti['notiffication_type'] = "Lead Converted";
                        $param_noti['notiffication_type_id'] = $leads_id;
                        $param_noti['user_id'] = $user_id;
                        $param_noti['subject'] = " A lead has been converted successfully into customers and contact  LeadName : ".$leads_list->FirstName." ".$leads_list->LastName." CustomerName  : ".$leads_list->Company.", Email : ".$leads_list->Email." ,MobileNumber : ".$leads_list->Mobile." and Website : ".$leads_list->Website."";
                        $this->Generic_model->insertData("notiffication",$param_noti);
                $this->response(array('code'=>'200','message'=>'customer_list', 'result'=>$data,'requestname'=>$method));
              }else{
                $this->response(array('code'=>'404','message' => 'Authentication1 Failed'), 200);
              }

        }else if($opportunity_check == "1"){

          if($customer_check == "0" || $customer_check == "" || $customer_check == NULL ) {
            $param_1['CustomerName'] = $leads_list->Company;
            $param_1['CustomerOwner'] = $user_id;
            $param_1['Description'] = $leads_list->Description;
            $param_1['Phone'] = $leads_list->Phone;
            $param_1['Website'] = $leads_list->Website;
            $param_1['AccountSource'] = $leads_list->LeadSource;
            $param_1['AnnualRevenue'] = $leads_list->AnnualRevenue;
            $param_1['Employees'] = $leads_list->No_Employees;
            $param_1['Fax'] = $leads_list->Fax;
            $param_1['Industry'] = $leads_list->Industry;
            $param_1['BillingStreet1'] = $leads_list->BillingStreet1;
            $param_1['Billingstreet2'] = $leads_list->Billingstreet2;
            $param_1['BillingCountry'] = $leads_list->BillingCountry;
            $param_1['StateProvince'] = $leads_list->StateProvince;
            $param_1['BillingCity'] = $leads_list->BillingCity;
            //$param_1['Billing'] = $leads_list->Billing;
            $param_1['BillingZipPostal'] = $leads_list->BillingZipPostal;
            $param_1['ShippingStreet1'] = $leads_list->ShippingStreet1;
            $param_1['Shippingstreet2'] = $leads_list->Shippingstreet2;
            $param_1['ShippingCountry'] = $leads_list->ShippingCountry;
            $param_1['ShippingStateProvince'] = $leads_list->ShippingStateProvince;
            $param_1['ShippingCity'] = $leads_list->ShippingCity;
            //$param_1['Shipping'] = $leads_list->Shipping;
            $param_1['ShippingZipPostal'] = $leads_list->ShippingZipPostal;
            $param_1['Created_by'] = $user_id;
            $param_1['modified_by'] = $user_id;
            $param_1['created_date_time'] = date("Y-m-d H:i:s");
            $param_1['modified_date_time'] = date("Y-m-d H:i:s");
            $customer_id = $this->Generic_model->insertDataReturnId("customers",$param_1);
          }else{
            // $customer_check = $this->db->query("select * from customers where CustomerName = '".$leads_list->Company."'")->row();
            // if(count($customer_check) >0){
              $customer_id = $customer_check;
            // }else{
            //   $this->response(array('code'=>'404','message' => 'customer does not exist please try again later'), 200);
            // }
            
          }
          if($customer_id != "" || $customer_id != NULL){
            $check_update_list = $this->db->query("select * from update_table where module_id ='".$customer_id."' and module_name ='Customer'")->row();
                  if(count($check_update_list)>0){
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $customer_id,'module_name'=>'Customer'));
                  }else{
                    $latest_val['module_id'] = $customer_id;
                    $latest_val['module_name'] = "Customer";
                    $latest_val['user_id'] = $user_id;
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val);
                  }
          }
          if($customer_id != ""){
                $param['FirstName'] = $leads_list->FirstName;
                $param['LastName'] =  $leads_list->LastName;
                $param['Email'] =  $leads_list->Email;
                $param['Fax'] = $leads_list->Fax;
                $param['Company'] = $customer_id;
                $param['Mobile'] = $leads_list->Mobile;
                $param['Phone'] = $leads_list->Phone;
                $param['Title_Designation'] = $leads_list->Title;
                $param['Description'] = $leads_list->Description;
                $param['DoNotCall'] = $leads_list->DoNotCall;
                $param['Category'] = $leads_list->Category;
                $param['LeadSource'] = $leads_list->LeadSource;
                $param['Created_by'] = $user_id;
                $param['modified_by'] = $user_id;
                $param['ContactOwner'] = $user_id;
                $param['created_date_time'] = date("Y-m-d H:i:s");
                $param['modified_date_time'] = date("Y-m-d H:i:s");
                $contact_id = $this->Generic_model->insertDataReturnId('contacts',$param);
                if($contact_id != "" || $contact_id != NULL){
                    $latest_val_1['module_id'] = $contact_id;
                    $latest_val_1['module_name'] = "Contact";
                    $latest_val_1['user_id'] = $user_id;
                    $latest_val_1['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val_1);

                  }
                  //Opportunity insert 

                  /* START OPPORTUNITY INSERT */

                  $checking_id = $this->db->query("select * from opportunities order by opportunity_id DESC")->row();
                  if($checking_id->opp_id == NULL || $checking_id->opp_id == ""){
                      $opp_id = "OP-00001";
                  }else{
                      $opp_check = trim($checking_id->opp_id);
                      $checking_op_id =  substr($opp_check, 3);
                      if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
                          $opp_id_last_inc = (++$checking_op_id);
                          $opp_id= "OP-".$opp_id_last_inc;
                      }else{
                          $opp_id = (++$opp_check);
                      } 
                  }

                  $param_opp['opp_id'] = $opp_id;
                  $param_opp['OpportunityName'] = $parameters['OpportunityName'];
                  $param_opp['Customer'] = $customer_id;
                  $param_opp['GSTIN'] = $parameters['GSTIN'];
                  $param_opp['CloseDate'] = date("Y-m-d H:i:s",strtotime($parameters['CloseDate']));
                  $param_opp['Description'] = $parameters['Description'];
                  $param_opp['ExpectedRevenue'] = $parameters['ExpectedRevenue'];
                  $param_opp['NextStep'] = $parameters['NextStep'];
                  $param_opp['Stage'] = $parameters['Stage'];
                  $param_opp['sampling'] = $parameters['sampling'];
                  $param_opp['Probability'] = $parameters['Probability'];
                  //$param['Email'] = $parameters['Email'];
                  //$param['Fax'] = $parameters['Fax'];
                  $param_opp['Rating'] = $parameters['Rating'];
                 // $param['Industry'] = $parameters['Industry'];
                  //$param['Mobile'] = $parameters['Mobile'];
                  $param_opp['project_name'] = $parameters['project_name'];
                  $param_opp['project_type'] = $parameters['project_type'];
                  $param_opp['size_class_project'] = $parameters['size_class_project'];
                  $param_opp['status_project'] = $parameters['status_project'];
                  //$param['BillingStreet1'] = $parameters['BillingStreet1'];
                  //$param['Billingstreet2'] = $parameters['BillingStreet2'];
                  //$param['BillingCountry'] = $parameters['BillingCountry'];
                  //$param['BillingCity'] = $parameters['BillingCity'];
                 // $param['BillingZipPostal'] = $parameters['BillingZipPostal'];
                  //$param['ShippingStreet1'] = $parameters['ShippingStreet1'];
              //param['Shippingstreet2'] = $parameters['Shippingstreet2'];
                  //param['ShippingCountry'] = $parameters['ShippingCountry'];
                  //$param['ShippingStateProvince'] = $parameters['ShippingStateProvince'];
                  //$param['ShippingCity'] = $parameters['ShippingCity'];
                 // $param['ShippingZipPostal'] = $parameters['ShippingZipPostal'];
                  $param_opp['DoNotCall'] = $parameters['DoNotCall'];
                  $param_opp['OpportunityOwner'] = $user_id;
                  $param_opp['created_by'] = $user_id;
                  $param_opp['modified_by'] = $user_id;
                  $param_opp['created_date_time'] = date("Y-m-d H:i:s");
                  $param_opp['modified_date_time'] = date("Y-m-d H:i:s");
                  //$param['TotalPrice'] = $parameters['TotalPrice'];
                  $opportunity_id = $this->Generic_model->insertDataReturnId("opportunities",$param_opp);


                  if($opportunity_id != "" ||$opportunity_id != NULL ){
          $user_list_opp = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
          $user_report_to_opp = $this->db->query("select * from users where user_id = '".$user_list_opp->manager."' AND status = 'Active'")->row();


                  if(count($user_list_opp)>0){
                    $push_noti_opp['fcmId_android'] = $user_list_opp->fcmId_android;
                    $push_noti_opp['fcmId_iOS'] = $user_list_opp->fcmId_iOS;                
                  }else{
                    $push_noti_opp['fcmId_android'] ="";
                    $push_noti_opp['fcmId_iOS'] = "";   
                  }
                  if(count($user_report_to_opp) >0){
                    $push_noti_opp['fcmId_android_report_to'] = $user_report_to_opp->fcmId_android;
                    $push_noti_opp['fcmId_iOS_report_to'] = $user_report_to_opp->fcmId_iOS;
                  }else{
                    $push_noti_opp['fcmId_android_report_to'] = "";
                    $push_noti_opp['fcmId_iOS_report_to'] = "";
                  }
                  $push_noti_opp['opportunity_id'] = $opportunity_id;
                  $push_noti_opp['user_id'] = $user_id;
                  $push_noti_opp['subject'] = "A new Opportunitie has been created successfully  OpportunityName  : ".$parameters['OpportunityName']." CustomerName : ". $customer_list->CustomerName.", Stage  : ".$parameters['Stage']." Probability  : ".$parameters['Probability']."";
                  $this->PushNotifications->Opportunitie_notifications($push_noti_opp);

                  $latest_val_opp['module_id'] = $opportunity_id;
                  $latest_val_opp['module_name'] = "Opportunitie";
                  $latest_val_opp['user_id'] = $user_id;
                  $latest_val_opp['created_date_time'] = date("Y-m-d H:i:s");
                  $this->Generic_model->insertData("update_table",$latest_val_opp);



                  $param_noti_opp['notiffication_type'] = "Opportunitie";
                  $param_noti_opp['notiffication_type_id'] = $opportunity_id;
                  $param_noti_opp['user_id'] = $user_id;
                  $param_noti_opp['subject'] = " A new Opportunitie has been created successfully  OpportunityName  : ".$parameters['OpportunityName']." CustomerName : ". $customer_list->CustomerName.", Stage  : ".$parameters['Stage']." Probability  : ".$parameters['Probability']."";
                  $this->Generic_model->insertData("notiffication",$param_noti_opp);

                  $products_price = count($parameters['products_price']);
                    if($parameters["products_price"][0]['Product'] != "" || $parameters["products_price"][0]['Product'] != NULL){
                      for($k=0;$k<$products_price;$k++){
                        $Product_id = $parameters["products_price"][$k]['Product'];
                        //$product_details = $this->db->query("select * from product_master where    product_id =".$Product_id)->row();

                            $param_2opp['Opportunity'] = $opportunity_id;
                            $param_2opp['Probability'] = $parameters["products_price"][$k]['Probability'];
                            $param_2opp['Product'] = $parameters["products_price"][$k]['Product'];
                           // $param_2opp['Productcode'] = $product_details->product_code;
                            $param_2opp['Quantity'] = $parameters["products_price"][$k]['Quantity'];
                            $param_2opp['schedule_date_from'] = date("Y-m-d",strtotime($parameters["products_price"][$k]['schedule_date_from']));
                            $param_2opp['schedule_date_upto'] = date("Y-m-d",strtotime($parameters["products_price"][$k]['schedule_date_upto']));
                            $param_2opp['created_by'] =$user_id;
                            $param_2opp['modified_by'] =$user_id;
                            $param_2opp['created_date_time'] =date("Y-m-d H:i:s");
                            $param_2opp['modified_date_time'] =date("Y-m-d H:i:s");
                            $ok = $this->Generic_model->insertData("product_opportunities",$param_2opp);
                        }
                      }

                      $Associate_contact_id_str = $parameters["Associate_contact_id"];
                      $Associate_contact_id = explode(",",$Associate_contact_id_str);
                      if(count($Associate_contact_id) >0){
                        for($ia=0;$ia<count($Associate_contact_id);$ia++){
                          $param_21opp['Opportunity'] = $opportunity_id;
                          $param_21opp['user_id'] = $Associate_contact_id[$ia];
                          $param_21opp['created_by'] = $user_id;
                          $param_21opp['created_date_time'] = date("Y-m-d H:i:s");
                          $this->Generic_model->insertData("opportunity_associate_contacts",$param_21opp);
                        }
                      }

                      if($parameters["Brands_list"][0]['Brands_Product'] != "" || $parameters["Brands_list"][0]['Brands_Product'] != NULL){
                      $Brands_Product_list =  count($parameters['Brands_list']);
                       for($a=0;$a<$Brands_Product_list;$a++){
                          $param_3opp['Opportunity'] = $opportunity_id;
                          $param_3opp['Brands_Product'] = $parameters["Brands_list"][$a]['Brands_Product'];
                          $param_3opp['Brands_Units'] = $parameters["Brands_list"][$a]['Brands_Units'];
                          $param_3opp['Brands_Quantity'] = $parameters["Brands_list"][$a]['Brands_Quantity'];
                          $param_3opp['Brands_Price'] = $parameters["Brands_list"][$a]['Brands_Price'];
                          $ok = $this->Generic_model->insertData("Products_Brands_targeted_opp",$param_3opp);
                      
                        }

                     }

                     if($parameters["Competition_insert"][0]['Competition_Product'] != "" || $parameters["Competition_insert"][0]['Competition_Product'] != NULL){
                      $Competition_Units_list =  count($parameters['Competition_insert']);
                       //echo "hii Competition_insert";
                       for($m=0;$m<$Competition_Units_list;$m++){
                          $param_4opp['Opportunity'] = $opportunity_id;
                          $param_4opp['Competition_Units'] = $parameters["Competition_insert"][$m]['customer'];
                          $param_4opp['Competition_Product'] = $parameters["Competition_insert"][$m]['Competition_Product'];
                          //$param_4opp['Competition_Quantity'] = $parameters["Competition_insert"][$m]['Competition'];
                          $param_4opp['Competition_Price'] = $parameters["Competition_insert"][$m]['Competition_Price'];
                          $ok1 = $this->Generic_model->insertData("Competition_targeted_opp",$param_4opp);
                        }
                     }

                     $opp_product_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_id) where a.Opportunity = ".$opportunity_id)->result();
                      $customer_list = $this->db->query("select * from customers where customer_id =".$customer_id)->row();
                        $email = $user_list_opp->email;
                        $to = $email;  //$to      = $dept_email_id;
                        $subject = "New Opportunitie created";
                        $data_1opp['name'] = $user_list_opp->name;

                        $data_1opp['message'] = "<p> A new Opportunitie has been created successfully <br/><br/><b> OpportunityName </b> : ".$parameters['OpportunityName']."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><b>Stage </b> : ".$parameters['Stage']."<br/><b>Probability </b> : ".$parameters['Probability']."</p> <br/><br/>
                          <table width='100%'  aliparameters[='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                          <thead>
                           <tr >
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                            </tr></thead>";

                           if(count($opp_product_list) >0){
                              foreach($opp_product_list as $opp_values){
                          $data_1opp['message'].=  "<tbody><tr>

                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
                          }

                            }

                            $data_1opp['message'].= "</table><br/>";  


                  $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data_1opp);

                        if(count($user_report_to_opp) >0){
                            $email = $user_report_to_opp->email;
                            $to = $email;  //$to      = $dept_email_id;
                            $subject = "New Opportunitie created";
                            $data_2opp['name'] = $user_report_to_opp->name;
                            $data_2opp['message'] = "<p> A new Opportunitie has been created successfully By <b>".$user_list_opp->name."</b> <br/><br/><b> OpportunityName </b> : ".$parameters['OpportunityName']."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName.", <br/><b>Stage </b> : ".$parameters['Stage']."<br/><b>Probability </b> : ".$parameters['Probability']."</p> <br/><br/>
                          <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                          <thead>
                           <tr >
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                            <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                            </tr></thead>";

                           if(count($opp_product_list) >0){
                              foreach($opp_product_list as $opp_values){
                          $data_2opp['message'].=  "<tbody><tr>

                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
                            <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
                          }

                            }

                            $data_2opp['message'].= "</table><br/>";  


                            $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data_2opp);

                  }
                }
                  /*OPPORTUNITY INSERT CLOSED */

                $param_3['status'] = "convert";
                $param_3['modified_by'] = $user_id;
                $param_3['modified_date_time'] = date("Y-m-d H:i:s");
                $ok1 = $this->Generic_model->updateData('leads',$param_3,array('leads_id'=>$leads_id));
                 $check_update_list_lead = $this->db->query("select * from update_table where module_id ='".$leads_id."' and module_name ='Lead'")->row();
                  if(count($check_update_list_lead)>0){
                    $latest_val_2['user_id'] = $user_id;
                    $latest_val_2['created_date_time'] = date("Y-m-d H:i:s");
                    $latest_val_2['delete_status'] = "1";
                    $ok = $this->Generic_model->updateData('update_table', $latest_val_2, array('module_id' => $leads_id,'module_name'=>'Lead'));
                  }else{
                    $latest_val_2['module_id'] = $leads_id;
                    $latest_val_2['module_name'] = "Lead";
                    $latest_val_2['user_id'] = $user_id;
                    $latest_val_2['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val_2);
                  }
                  $param_21['customer_id'] = $customer_id;
                  $param_21['user_id'] = $user_id;
                  $param_21["created_by"] = $user_id;
                  $param_21['modified_by'] = $user_id;
                  $param_21['created_date_time'] = date('Y-m-d H:i:s');
                  $param_21['modified_date_time']=date('Y-m-d H:i:s');
                   $customer_user_check = $this->db->query("select * from customer_users_maping where customer_id='".$customer_id."' and user_id ='".$user_id."'")->row();
                    if(count($customer_user_check) >0){
                      $customer_user_map = $this->Generic_model->updateData('customer_users_maping', $param_21, array('customer_user_id' => $customer_user_check->customer_user_id));
                    }else{
                      $customer_user_map = $this->Generic_model->insertData('customer_users_maping',$param_21);
                    }
                  }else{
                    $this->response(array('code'=>'404','message' => 'Authentication4 Failed'), 200);
                  }

                  if($ok1 == 1){

            $user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
                          $email = $user_list->email;
                          $to = $email;  //$to      = $dept_email_id;
                          $subject = "Lead Converted";
                          $param_24['name'] = $user_list->name;
                          $param_24['message'] = "<p> A lead has been converted successfully into customers and contact  <br/><br/><b> LeadName </b> : ".$leads_list->FirstName." ".$leads_list->LastName." <br/> <b>CustomerName </b> : ".$leads_list->Company.", <br/><b>Email</b> : ".$leads_list->Email." ,<br/> <b>MobileNumber</b> : ".$leads_list->Mobile." and <br/><b>Website </b> : ".$leads_list->Website."</p> ";  
                          //$message = $message;
                          $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$param_24);

                          $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
                          if(count($user_report_to) >0){
                            $email = $user_report_to->email;
                            $to = $email;  //$to      = $dept_email_id;
                            $subject = "Lead Converted";
                            $param_24['name'] = $user_report_to->name;
                            $param_24['message'] = "<p> A lead has been converted successfully into customers and contact by <b>".$user_list->name."</b>  <br/><br/><b> LeadName </b> : ".$leads_list->FirstName." ".$leads_list->LastName." <br/> <b>CustomerName </b> : ".$leads_list->Company.", <br/><b>Email</b> : ".$leads_list->Email." ,<br/> <b>MobileNumber</b> : ".$leads_list->Mobile." and <br/><b>Website </b> : ".$leads_list->Website."</p> ";  
                            //$message = $message;
                            $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$param_24);

                          }
                       $param_noti['notiffication_type'] = "Lead Converted";
                        $param_noti['notiffication_type_id'] = $leads_id;
                        $param_noti['user_id'] = $user_id;
                        $param_noti['subject'] = " A lead has been converted successfully into customers and contact  LeadName : ".$leads_list->FirstName." ".$leads_list->LastName." CustomerName  : ".$leads_list->Company.", Email : ".$leads_list->Email." ,MobileNumber : ".$leads_list->Mobile." and Website : ".$leads_list->Website."";
                        $this->Generic_model->insertData("notiffication",$param_noti);



                        $return_data = $this->all_tables_records_view("opportunitie",$opportunity_id);
                $this->response(array('code'=>'200','message'=>'customer_list', 'result'=>$return_data,'requestname'=>$method));
              }else{
                $this->response(array('code'=>'404','message' => 'Authentication1 Failed','result'=>$return_data,), 200);
              }

        }else{
          $this->response(array('code'=>'404','message' => 'Authentication1 Failed','result'=>$return_data,), 200);
        }
      }else{
          $this->response(array('code'=>'404','message' => 'Authentication1 Failed'), 200);
        }

  }


	public function sales_related_to($parameters,$method,$user_id){
		
		$role_id = $parameters['role_id'];
		$final_users_id = $parameters['team_id'];
    
		//$customers = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName, CUST.CustomerType, CUST.Phone, CUST.CustomerSAPCode, CUST.approve_status, CPL.price_list_id, PPM.Area FROM customers CUST INNER JOIN customer_users_maping CUM ON (CUST.customer_id = CUM.customer_id) INNER JOIN customer_price_list CPL ON CUST.customer_id = CUM.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id INNER JOIN users U ON (CUM.user_id = U.user_id) WHERE CUM.user_id IN (".$final_users_id.") AND CUST.archieve != 1 GROUP BY CUM.customer_id")->result();
    
		//"select a.CustomerName as total_name,a.customer_id as id from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id"
		$customers = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName, CUST.CustomerType, CUST.Phone, CUST.CustomerSAPCode, CUST.approve_status, U.Product_price_master_id, PPM.Area from customers CUST inner join customer_users_maping CUM on (CUST.customer_id = CUM.customer_id) INNER JOIN users U on (CUM.User_id = U.user_id) left Join product_price_master PPM ON (U.Product_price_master_id = PPM.Product_price_master_id) where CUM.user_id in (".$final_users_id.") and CUST.archieve != 1 group by CUM.customer_id")->result();
	
		$contacts = $this->db->query("select C.FirstName, C.LastName, C.contact_id, C.Mobile, C.Company, C.Company_text, U.Product_price_master_id, PPM.Area from contacts C INNER JOIN users U on (C.ContactOwner = U.user_id) left Join product_price_master PPM ON (U.Product_price_master_id = PPM.Product_price_master_id) where ContactOwner in (".$final_users_id.") and C.archieve != 1")->result();
		
		//print_r($contacts);
		//exit();
		//echo "select C.FirstName, C.LastName, C.contact_id, C.Mobile, C.Company, C.Company_text, U.Product_price_master_id, PPM.Area from contacts C INNER JOIN users U on (C.ContactOwner = U.user_id) left Join product_price_master PPM ON (U.Product_price_master_id = PPM.Product_price_master_id) where ContactOwner in (".$final_users_id.") and C.archieve != 1";
		//exit();
		//$contacts = $this->db->query("SELECT CON.contact_id, CON.FirstName, CON.LastName, CON.Mobile, CUST.customer_id, CUST.CustomerName, CUST.CustomerSAPCode, CUST.Phone, CUST.approve_status, CUST.CustomerType, CPL.price_list_id, PPM.Area from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id INNER JOIN customer_price_list CPL ON CON.Company = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 ORDER BY CON.contact_id DESC")->result(); 

		//$associate_contacts = $this->db->query("SELECT CONCAT_WS(' ',CON.FirstName,CON.LastName) AS total_name, OPP.contact_id AS id, CON.Mobile, CUST.CustomerName, CUST.CustomerType, CUST.customer_id, CUST.Phone, CUST.CustomerSAPCode, CUST.approve_status, CPL.price_list_id, PPM.Area FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id INNER JOIN customer_price_list CPL ON CUST.customer_id = CPL.customer_id INNER JOIN product_price_master PPM ON CPL.price_list_id = PPM.Product_price_master_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 GROUP BY CON.contact_id")->result();
		
		//"select CONCAT_WS(' ',FirstName,LastName) as total_name , a.contact_id as id from opportunity_associate_contacts a inner join contacts b  on a.contact_id=b.contact_id where  archieve != 1 group by a.contact_id"
		$associate_contacts = $this->db->query("SELECT C.FirstName, C.LastName, AC.contact_id, C.Mobile, C.Company, C.Company_text, U.Product_price_master_id, PPM.Area from opportunity_associate_contacts AC INNER JOIN contacts C ON (AC.contact_id = C.contact_id) INNER JOIN users U on (C.ContactOwner = U.user_id) left Join product_price_master PPM ON (U.Product_price_master_id = PPM.Product_price_master_id) where C.archieve != 1 group by AC.contact_id")->result();
			
		/*
		echo "SELECT CONCAT_WS(' ',CON.FirstName,CON.LastName) AS total_name, OPP.contact_id AS id, CON.Mobile,
		CUST.CustomerName, CUST.customer_id, CUST.CustomerSAPCode, CUST.approve_status, CPL.price_list_id FROM
		opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN
		Customers CUST ON CON.Company = CUST.customer_id INNER JOIN customer_price_list CPL ON CUST.customer_id = CPL.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 GROUP BY CON.contact_id\n\n";
		exit();
		*/
		
		//$leads = $this->db->query("select * from leads where LeadOwner in (".$final_users_id.") and archieve != 1 and is_lead_converted != 2 order by leads_id DESC")->result();
		
		//$opportunities = $this->db->query("select * from opportunities where OpportunityOwner in (".$final_users_id.") and  archieve !=1 order by opportunity_id DESC")->result();		

		//$contract = $this->db->query("select * from contract where ContractOwner  in (".$final_users_id.") and archieve !=1 order by contract_id DESC")->result();
		
		$data['related_to'][0]['related_name'] = "Customers";
		if(count($customers) >0){
			$j= 0;
			foreach($customers as $customer_val){				
				$data['related_to'][0]['related_list'][$j]['id'] =  $customer_val->customer_id;
				$data['related_to'][0]['related_list'][$j]['name'] =  $customer_val->CustomerName;
				$data['related_to'][0]['related_list'][$j]['phone'] =  $customer_val->Phone;
				$data['related_to'][0]['related_list'][$j]['Company'] =  $customer_val->customer_id;
				$data['related_to'][0]['related_list'][$j]['Company_Text'] =  $customer_val->CustomerName;
				$data['related_to'][0]['related_list'][$j]['customer_type'] =  $customer_val->CustomerType;
				
				if($customer_val->CustomerType == NULL || $customer_val->CustomerType = ''){
					$data['related_to'][0]['related_list'][$j]['customer_type'] =  'Direct Customer';
				}
				
				$data['related_to'][0]['related_list'][$j]['SAP_code'] =  $customer_val->CustomerSAPCode;
				$data['related_to'][0]['related_list'][$j]['price_list_id'] =  $customer_val->Product_price_master_id;
				$data['related_to'][0]['related_list'][$j]['price_list_area'] =  $customer_val->Area;
				$data['related_to'][0]['related_list'][$j]['approve_status'] =  $customer_val->approve_status;
				// Get Connected Company				
				// $companies = $this->db->query("select customer_id, CustomerName from customers a where customer_id='".$customer_val->customer_id."'")->row();
				/*
				if(count($companies) > 0){					
					$data['related_to'][0]['related_list'][$j]['Company'] =  $companies->customer_id;
					$data['related_to'][0]['related_list'][$j]['Company_Text'] =  $companies->CustomerName;						
				}else{
					$data['related_to'][0]['related_list'][$j]['Company'] =  NULL;
					$data['related_to'][0]['related_list'][$j]['Company_Text'] =  NULL;						
				}
				*/
				$j++;
			}
		}else{
			$data['related_to'][0]['related_list'] = array();
		}
		
		$data['related_to'][1]['related_name'] = "Contacts";
		if(count($contacts) > 0){
			$j= 0;
			foreach($contacts as $contact_val){				
				$data['related_to'][1]['related_list'][$j]['id'] =  $contact_val->contact_id;
				$data['related_to'][1]['related_list'][$j]['name'] =  $contact_val->FirstName.' '.$contact_val->LastName;
				$data['related_to'][1]['related_list'][$j]['phone'] =  $contact_val->Mobile;
				$data['related_to'][1]['related_list'][$j]['Company'] =  $contact_val->Company;
				$data['related_to'][1]['related_list'][$j]['Company_Text'] =  $contact_val->Company_text;
				$data['related_to'][1]['related_list'][$j]['Company_Phone'] =  '';
				$data['related_to'][1]['related_list'][$j]['SAP_code'] =  '';
				$data['related_to'][1]['related_list'][$j]['price_list_id'] =  $contact_val->Product_price_master_id;
				$data['related_to'][1]['related_list'][$j]['price_list_area'] =  $contact_val->Area;
				$data['related_to'][1]['related_list'][$j]['approve_status'] =  '';
				// Get Connected Company
				/*
				$companies = $this->db->query("select Company, Company_text from contacts where contact_id='".$contact_val->contact_id."'")->row();
				if(count($companies) > 0){					
					$data['related_to'][1]['related_list'][$j]['Company'] =  $companies->Company;
					$data['related_to'][1]['related_list'][$j]['Company_Text'] =  $companies->Company_text;						
				}else{
					$data['related_to'][1]['related_list'][$j]['Company'] =  NULL;
					$data['related_to'][1]['related_list'][$j]['Company_Text'] =  NULL;						
				}		
				*/				
				$j++;
			}			
		}else{
			$data['related_to'][1]['related_list'] = array();
		}
		
		$data['related_to'][2]['related_name'] = "Associate Contact";
		if(count($associate_contacts) >0){
			$j= 0;
			foreach($associate_contacts as $associate_contact_val){
				$data['related_to'][2]['related_list'][$j]['id'] =  $associate_contact_val->contact_id;
				$data['related_to'][2]['related_list'][$j]['name'] =  $associate_contact_val->FirstName.' '.$associate_contact_val->LastName;;
				$data['related_to'][2]['related_list'][$j]['phone'] =  $associate_contact_val->Mobile;
				$data['related_to'][2]['related_list'][$j]['Company'] =  $associate_contact_val->Company;
				$data['related_to'][2]['related_list'][$j]['Company_Text'] =  $associate_contact_val->Company_text;
				$data['related_to'][2]['related_list'][$j]['Company_Phone'] =  '';
				$data['related_to'][2]['related_list'][$j]['SAP_code'] =  '';
				$data['related_to'][2]['related_list'][$j]['price_list_id'] =  $associate_contact_val->Product_price_master_id;
				$data['related_to'][2]['related_list'][$j]['price_list_area'] =  $associate_contact_val->Area;
				$data['related_to'][2]['related_list'][$j]['approve_status'] =  '';
				// Get Connected Company				
				/*
				$companies = $this->db->query("select b.Company, b.Company_text from opportunity_associate_contacts a left join contacts b on a.contact_id = b.contact_id where a.contact_id='".$associate_contact_val->id."' and b.archieve != 1 group by a.contact_id")->row();
				if(count($companies) > 0){					
					$data['related_to'][2]['related_list'][$j]['Company'] =  $companies->Company;
					$data['related_to'][2]['related_list'][$j]['Company_Text'] =  $companies->Company_text;						
				}else{
					$data['related_to'][2]['related_list'][$j]['Company'] =  NULL;
					$data['related_to'][2]['related_list'][$j]['Company_Text'] =  NULL;						
				}
				*/
				$j++;
			}
		}else{
			$data['related_to'][1]['related_list'] = array();
		}

		/* Commented as not required
		$data['related_to'][1]['related_name'] = "leads";
		$k= 0;
		if(count($leads) >0){
			foreach($leads as $lead_val){
				$data['related_to'][1]['related_list'][$k]['id'] =  $lead_val->leads_id;
				$data['related_to'][1]['related_list'][$k]['name'] =  $lead_val->FirstName." ".$lead_val->LastName;
				$k++;
			}
		}else{
			$data['related_to'][1]['related_list'] = array();
		}

		$data['related_to'][2]['related_name'] = "opportunities";
		if(count($opportunities) >0){
			$l= 0;
			foreach($opportunities as $opportunity_val){
				$data['related_to'][2]['related_list'][$l]['id'] =  $opportunity_val->opportunity_id;
				$data['related_to'][2]['related_list'][$l]['name'] =  $opportunity_val->opp_id;
				$l++;
			}
		}else{
			$data['related_to'][2]['related_list'] = array();
		}
		
		
		$data['related_to'][3]['related_name'] = "Contracts";
		if(count($contract) >0){
			$k=0;
			foreach($contract as $contract_val){
				$data['related_to'][3]['related_list'][$k]['id'] =  $contract_val->contract_id;
				$data['related_to'][3]['related_list'][$k]['name'] =  $contract_val->ContractNumber;
				$k++;
			}
		}else{
			$data['related_to'][3]['related_list'] = array();
		}
		*/		
   
		$this->response(array('code'=>'200', 'result'=>$data,'requestname'=>$method));
	}
  
  
	public function sales_calls_insert($parameters,$method,$user_id){	
	
		$salesCallsTempId = '';
		
		// Check if any sales_calls_temp_id exists
		if($parameters['sales_calls_temp_id'] != '' || $parameters['sales_calls_temp_id'] != NULL){
			$salesCallsTempId = $parameters['sales_calls_temp_id'];
		}
		
		//  unset element sales_calls_temp_id from parameters
		unset($parameters['sales_calls_temp_id']);
		
		// Get Status
		$status = $parameters['Status'];
		if($status == "" || $status == NULL){
		  $parameters['Status'] = "Open";
		}
		
		$parameters['Call_Date']=date("Y-m-d H:i:s");
		$parameters['NextVisitDate']=date("Y-m-d", strtotime($parameters['NextVisitDate']));    		
		//$workAttended = $parameters['work_attended'];
		//if($workAttended == 'A/C Recon'){
		//	$parameters['work_attended_name'] = $parameters['work_attended_name_1'];
		//}
		
		$parameters['Assigned_To'] = $user_id;
		$parameters['Owner'] = $user_id;
		$parameters['created_by'] = $user_id;
		$parameters['created_date_time'] = date("Y-m-d H:i:s");
		$parameters['modified_by'] = $user_id;
		$parameters['modified_date_time'] = date("Y-m-d H:i:s");
		
		$paramData = $parameters;
		unset($paramData['Call_Time']);
				
		$sales_call_id = $this->Generic_model->insertDataReturnId('sales_call',$paramData);
		
		if($sales_call_id != "" || $sales_call_id != NULL){
			
			// Check if there is sales_calls_temp_id parameter is exist
			if($salesCallsTempId != ''){
				$tempId['sales_call_id'] = $sales_call_id;
				$tempId['modified_by'] = $user_id;
				$tempId['modified_datetime'] = date("Y-m-d H:i:s");
				$this->Generic_model->updateData('sales_call_temp_table',$tempId,array('sales_calls_temp_id'=>$sales_calls_temp_id));
			}

			if($parameters['Status'] == "Completed"){
				$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
				$email = $user_list->email;
				$to = $email;  //$to      = $dept_email_id;
				$subject = "Sales Calls Completed";
				$data['name'] = $user_list->name;
				$data['message'] = "<p> Sales Calls has been Completed successfully <br/><br/><b> Subject </b> : ". $subject." <br/> <b>Type </b> : ".$parameters['releted_to'].", <br/><b>Call Type</b> : ".$parameters['Call_Type']." ,<br/> <b>Priority</b> : ".$parameters['Priority']." and <br/><b>Status </b> : ".$parameters['Status']."</p> ";  
				//$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
				if(count($user_report_to) >0){
					$email = $user_report_to->email;
					$to = $email;  //$to      = $dept_email_id;
					$subject = "Sales Calls Completed";
					$data['name'] = $user_report_to->name;
					$data['message'] = "<p>Sales Calls has been Completed successfully  By <b>".$user_list->name."</b> <br/><br/><b>  Subject </b> : ". $subject." <br/> <b>Type </b> : ".$parameters['releted_to'].", <br/><b>Call Type</b> : ".$parameters['Call_Type']." ,<br/> <b>Priority</b> : ".$parameters['Priority']." and <br/><b>Status </b> : ".$parameters['Status']."</p> ";  
					$message = $message;
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				}
			}

			$param_noti['notiffication_type'] = "SalesCalls";
			$param_noti['notiffication_type_id'] = $sales_call_id;
			$param_noti['user_id'] = $user_id;
			$param_noti['subject'] = " Sales Calls has been created successfully    Subject  : ". $parameters['Subject']." Type  : ".$parameters['releted_to'].", Call Type : ".$parameters['Call_Type']." ,Priority : ".$parameters['Priority']." and Status  : ".$parameters['Status']."";
			$this->Generic_model->insertData("notiffication",$param_noti);

			$latest_val['module_id'] = $sales_call_id;
			$latest_val['module_name'] = "SalesCalls";
			$latest_val['user_id'] = $user_id;
			$latest_val['created_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->insertData("update_table",$latest_val);

			if(count($user_list)>0){
				$push_noti['fcmId_android'] = $user_list->fcmId_android;
				$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
			}else{
				$push_noti['fcmId_android'] ="";
				$push_noti['fcmId_iOS'] = "";   
			}
			
			if(count($user_report_to) >0){
				$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
				$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
			}else{
				$push_noti['fcmId_android_report_to'] = "";
				$push_noti['fcmId_iOS_report_to'] = "";
			}
			
			$push_noti['sales_call_id'] = $sales_call_id;
			$push_noti['user_id'] = $user_id;
			$push_noti['subject'] = "Sales Calls has been created successfully    Subject  : ". $parameters['Subject']." Type  : ".$parameters['releted_to'].", Call Type : ".$parameters['Call_Type']." ,Priority : ".$parameters['Priority']." and Status  : ".$parameters['Status']."";
			$this->PushNotifications->SalesCalls_notifications($push_noti);

			$data_1['sales_call_id'] = $sales_call_id;

			$return_data = $this->all_tables_records_view("sales_call",$sales_call_id);
			$this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
  
  
  public function sales_calls_edit($parameters,$method,$user_id){
    $sales_call_id = $parameters['sales_call_id'];
    $parameters['Call_Date']=date("Y-m-d",strtotime($parameters['Call_Date']));
    $parameters['NextVisitDate']=date("Y-m-d", strtotime($parameters['NextVisitDate']));
    //$parameters['MinutesOfMeeting']=date("H:i:s", strtotime($parameters['MinutesOfMeeting']));
    $parameters['Owner'] = $user_id;
    $parameters['modified_by'] = $user_id;
    $parameters['modified_date_time'] = date("Y-m-d H:i:s");
	
	$paramData = $parameters;
	
	unset($paramData['Call_Time']);
	unset($paramData['sales_calls_temp_id']);
	
    $result=$this->Generic_model->updateData('sales_call',$paramData, array('sales_call_id'=>$sales_call_id));
        if($result ==1){
           if($parameters['Status'] == "Completed"){
            $user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
             //$customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Company'])->row();

              $email = $user_list->email;
              $to = $email;  //$to      = $dept_email_id;
              $subject = "Sales Calls Completed";
              $data['name'] = $user_list->name;
              $data['message'] = "<p> Sales Calls has been Completed successfully <br/><br/><b> Subject </b> : ". $parameters['Subject']." <br/> <b>Type </b> : ".$parameters['releted_to'].", <br/><b>Call Type</b> : ".$parameters['Call_Type']." ,<br/> <b>Priority</b> : ".$parameters['Priority']." and <br/><b>Status </b> : ".$parameters['Status']."</p> ";  
              //$message = $message;
              $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

              $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
              if(count($user_report_to) >0){
                $email = $user_report_to->email;
                $to = $email;  //$to      = $dept_email_id;
                $subject = "Sales Calls Completed";
                $data['name'] = $user_report_to->name;
                $data['message'] = "<p>Sales Calls has been Completed successfully  By <b>".$user_list->name."</b> <br/><br/><b>  Subject </b> : ". $parameters['Subject']." <br/> <b>Type </b> : ".$parameters['releted_to'].", <br/><b>Call Type</b> : ".$parameters['Call_Type']." ,<br/> <b>Priority</b> : ".$parameters['Priority']." and <br/><b>Status </b> : ".$parameters['Status']."</p> ";  
                //$message = $message;
                $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
              }

              $param_noti['notiffication_type'] = "SalesCalls";
                $param_noti['notiffication_type_id'] = $sales_call_id;
                $param_noti['user_id'] = $user_id;
                $param_noti['subject'] = " Sales Calls has been Completed successfully By ".$user_list->name."  Subject  : ". $parameters['Subject']." Type  : ".$parameters['releted_to'].", Call Type : ".$parameters['Call_Type']." ,Priority : ".$parameters['Priority']." and Status  : ".$parameters['Status']."";
                $this->Generic_model->insertData("notiffication",$param_noti);

                $check_update_list = $this->db->query("select * from update_table where module_id ='".$sales_call_id."' and module_name ='SalesCalls'")->row();
                  if(count($check_update_list)>0){
                    $latest_val['user_id'] = $user_id;
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $sales_call_id,'module_name'=>'SalesCalls'));
                  }else{
                    $latest_val['module_id'] = $sales_call_id;
                    $latest_val['module_name'] = "SalesCalls";
                    $latest_val['user_id'] = $user_id;
                    $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                    $this->Generic_model->insertData("update_table",$latest_val);
                  }

              
            }
             $return_data = $this->all_tables_records_view("sales_call",$sales_call_id);
          $this->response(array('code'=>'200','message'=>'updated successfully','result'=>$return_data,'requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }

  }

  public function sales_calls_list($parameters,$method,$user_id){
    $final_users_id = $parameters['team_id'];
    $sales_call_list = $this->db->query("select *,a.Phone, a.created_date_time as CreatedDateTime from sales_call a inner join users b on (a.Owner = b.user_id) where  a.Owner in (".$final_users_id.") and a.archieve != 1 order by a.sales_call_id desc")->result();

    $i=0;
    foreach($sales_call_list as $sc_list){
		$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;
		$data['sales_call_list'][$i]['releted_to'] = $sc_list->releted_to;
		$data['sales_call_list'][$i]['sales_call_customer_contact_type'] = $sc_list->sales_call_customer_contact_type;
		$data['sales_call_list'][$i]['id'] = $sc_list->id;
		$data['sales_call_list'][$i]['Company'] = $sc_list->Company;
		$data['sales_call_list'][$i]['releted_to_new_contact_customer'] = $sc_list->releted_to_new_contact_customer;
		$data['sales_call_list'][$i]['new_contact_customer_person_name'] = $sc_list->new_contact_customer_person_name;
		$data['sales_call_list'][$i]['new_contact_customer_company_name'] = $sc_list->new_contact_customer_company_name;
		$data['sales_call_list'][$i]['new_contact_customer_other_person_name'] = $sc_list->new_contact_customer_other_person_name;
		$data['sales_call_list'][$i]['created_date_time'] = $sc_list->Call_Date;
		$data['sales_call_list'][$i]['Call_Date'] = $sc_list->Call_Date;
		$data['sales_call_list'][$i]['Call_Time'] = date("H:i:s",strtotime($sc_list->CreatedDateTime));		
		$data['sales_call_list'][$i]['Phone'] = $sc_list->Phone;
		$data['sales_call_list'][$i]['Call_Type'] = $sc_list->Call_Type;
		$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
		$data['sales_call_list'][$i]['call_report'] = $sc_list->call_report;
		$data['sales_call_list'][$i]['MinutesOfMeeting'] = $sc_list->MinutesOfMeeting;
		$data['sales_call_list'][$i]['CommentsByManager'] = $sc_list->CommentsByManager;
		$data['sales_call_list'][$i]['Status'] = $sc_list->Status;
		$data['sales_call_list'][$i]['sales_calls_temp_id'] = $sc_list->sales_calls_temp_id;
		$data['sales_call_list'][$i]['Owner'] = $sc_list->Owner;
		$data['sales_call_list'][$i]['Owner_name'] = $sc_list->name;
		$data['sales_call_list'][$i]['tracking_id'] = $sc_list->tracking_id;
		$data['sales_call_list'][$i]['lat_lon_val'] = $sc_list->lat_lon_val;
		$data['sales_call_list'][$i]['geo_status'] = $sc_list->geo_status;
		$data['sales_call_list'][$i]['Assigned_To'] = user_details($sc_list->Assigned_To);
		$data['sales_call_list'][$i]['Assigned_To_id'] = $sc_list->Assigned_To;

		if($sc_list->contacts_id != ""||$sc_list->contacts_id != NULL || $sc_list->contacts_id != 0){
			$contacts_list = $this->db->query("select * from contacts where contact_id =".$sc_list->contacts_id)->row();
			if(count($contacts_list)>0){
				$data['sales_call_list'][$i]['contact_id'] = $sc_list->contacts_id;
				$data['sales_call_list'][$i]['contact_name'] = $contacts_list->FirstName." ".$contacts_list->LastName;
			}else{
				$data['sales_call_list'][$i]['contact_id'] = 0;
				$data['sales_call_list'][$i]['contact_name'] = "";
			}
		}else{
			$data['sales_call_list'][$i]['contact_id'] = 0;
			$data['sales_call_list'][$i]['contact_name'] = "";
		}
      
		$data['sales_call_list'][$i]['Description'] = $sc_list->Description;
		$call_date = date("d-m-Y",strtotime($sc_list->Call_Date));
      
		$data['sales_call_list'][$i]['Email'] = $sc_list->Email;
		$data['sales_call_list'][$i]['Comments'] = $sc_list->Comments;
		$NextVisitDate = date("d-m-Y",strtotime($sc_list->NextVisitDate));
      
		if($NextVisitDate == "00-00-0000" || $NextVisitDate == "01-01-1970" || $NextVisitDate == NULL){
			$data['sales_call_list'][$i]['NextVisitDate'] = "";
		}else{
			$data['sales_call_list'][$i]['NextVisitDate'] = date("Y-m-d",strtotime($sc_list->NextVisitDate));
		}
		
		$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
		$data['sales_call_list'][$i]['MinutesOfMeeting'] =  $sc_list->MinutesOfMeeting;
		$data['sales_call_list'][$i]['CommentsByManager '] = $sc_list->CommentsByManager;

		$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;

		$i++;
    }

    if(count($data)>0){
        $this->response(array('code'=>'200','message'=>'sales_call_list', 'result'=>$data,'requestname'=>$method));
      }else{
        $this->response(array('code'=>'200','message' => 'Sales Calls are Empty','result'=>null), 200);
      }
  }

  public function sales_calls_delete($parameters,$method,$user_id){
    $sales_call_id = $parameters['sales_call_id'];
    //$leads_id  = $parameters['leads_id'];

    if($sales_call_id != "" || $sales_call_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
       $result=$this->Generic_model->updateData('sales_call',$param,array('sales_call_id'=>$sales_call_id));
        if($result ==1){
          
              $latest_val['user_id'] = $user_id;
              $latest_val['created_date_time'] = date("Y-m-d H:i:s");
              $latest_val['delete_status'] = "1";
              $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $sales_call_id,'module_name'=>'SalesCalls'));

          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }
  }

	public function opportunities_list($parameters,$method,$user_id){
	  
		$final_users_id = $parameters['team_id'];
	
		$opportunities_list_val = $this->db->query("select *,a.remarks as Opp_remarks, a.created_date_time as createdDateTime, c.name as OwnerName from opportunities a left join customers b on (b.customer_id = a.Company) inner join users c on (c.user_id = a.OpportunityOwner) where a.OpportunityOwner in (".$final_users_id.") and a.archieve != 1 order by a.opportunity_id DESC")->result();
	
		//$opportunities_list_val = $this->db->query("select *,a.remarks as Opp_remarks, a.created_date_time as createdDateTime from opportunities a left join customers b on (b.customer_id = a.Company) inner join users c on (c.user_id = a.OpportunityOwner) where a.OpportunityOwner in (".$final_users_id.") and a.archieve != 1 order by a.opportunity_id DESC")->result();
		
		if(count($opportunities_list_val)>0){
			$i=0;
            foreach($opportunities_list_val as $opp_list){
				
				$contactName = '';
				
				if($opp_list->opportunity_main_contact_id != '' || $opp_list->opportunity_main_contact_id != NULL){					
					$main_contact = $this->db->query("SELECT FirstName, LastName from contacts WHERE contact_id = '".$opp_list->opportunity_main_contact_id."'")->row();
					$contactName = $main_contact->FirstName." ".$main_contact->LastName; 
				}									
				
				$leadInfo = $this->db->query("SELECT leads_id,lead_size_class_of_project FROM leads WHERE lead_number = '".$opp_list->Leadno."'")->row();

				$data['opportunities_list'][$i]['opportunity_id'] = $opp_list->opportunity_id;
				$data['opportunities_list'][$i]['opp_id'] = $opp_list->opp_id;
				$data['opportunities_list'][$i]['OwnerName'] = $opp_list->OwnerName;
				$data['opportunities_list'][$i]['leads_id'] = $leadInfo->leads_id;
				$data['opportunities_list'][$i]['Leadno'] = $opp_list->Leadno;
				$data['opportunities_list'][$i]['Company'] = $opp_list->Company;
				$data['opportunities_list'][$i]['Company_Text'] = $opp_list->Company_text;
				$data['opportunities_list'][$i]['sampling'] = $opp_list->sampling;
				$data['opportunities_list'][$i]['mockup'] = $opp_list->mockup;
				$data['opportunities_list'][$i]['Rating'] = $opp_list->Rating;
				$data['opportunities_list'][$i]['project_name'] = $opp_list->project_name;
				$data['opportunities_list'][$i]['project_type'] = $opp_list->project_type;
				$data['opportunities_list'][$i]['size_calss_unit'] = $opp_list->size_calss_unit;
				$data['opportunities_list'][$i]['size_class_project'] = $opp_list->size_class_project;
				$data['opportunities_list'][$i]['lead_class_of_project'] = $opp_list->lead_class_of_project;
				$data['opportunities_list'][$i]['lead_size_class_of_project'] = $leadInfo->lead_size_class_of_project;
				$data['opportunities_list'][$i]['size_calss_unit_no_of_blocks'] = $opp_list->size_calss_unit_no_of_blocks;
				$data['opportunities_list'][$i]['size_calss_unit_no_of_floor_per_block'] = $opp_list->size_calss_unit_no_of_floor_per_block;				
				
				// Billing Details
				$data['opportunities_list'][$i]['status_project'] = $opp_list->status_project;
				$data['opportunities_list'][$i]['BillingStreet1'] = $opp_list->BillingStreet1;
				$data['opportunities_list'][$i]['BillingStreet2'] = $opp_list->Billingstreet2;
				$data['opportunities_list'][$i]['BillingCountry'] = $opp_list->BillingCountry;
				$data['opportunities_list'][$i]['BillingState'] = $opp_list->BillingState;
				$data['opportunities_list'][$i]['BillingCity'] = $opp_list->BillingCity;
				$data['opportunities_list'][$i]['BillingZipPostal'] = $opp_list->BillingZipPostal;
				$data['opportunities_list'][$i]['BillingArea'] = $opp_list->BillingArea;
				$data['opportunities_list'][$i]['BillingPlotno'] = $opp_list->BillingPlotno;
				$data['opportunities_list'][$i]['BillingWebsite'] = $opp_list->BillingWebsite;
				$data['opportunities_list'][$i]['BillingEmail'] = $opp_list->BillingEmail;
				$data['opportunities_list'][$i]['BillingPhone'] = $opp_list->BillingPhone;
				
				// Shipping Details
				$data['opportunities_list'][$i]['ShippingStreet1'] = $opp_list->ShippingStreet1;
				$data['opportunities_list'][$i]['Shippingstreet2'] = $opp_list->Shippingstreet2;
				$data['opportunities_list'][$i]['ShippingLandmark'] = $opp_list->ShippingLandmark;
				$data['opportunities_list'][$i]['Shippingplotno'] = $opp_list->Shippingplotno;
				$data['opportunities_list'][$i]['ShippingCountry'] = $opp_list->ShippingCountry;
				$data['opportunities_list'][$i]['ShippingStateProvince'] = $opp_list->ShippingStateProvince;
				$data['opportunities_list'][$i]['ShippingCity'] = $opp_list->ShippingCity;
				$data['opportunities_list'][$i]['ShippingZipPostal'] = $opp_list->ShippingZipPostal;
				
				$data['opportunities_list'][$i]['opportunity_main_contact_id'] = $opp_list->opportunity_main_contact_id;
				$data['opportunities_list'][$i]['opportunity_main_contact_name'] = $contactName;
				$data['opportunities_list'][$i]['opportunity_main_contact_designation'] = $opp_list->opportunity_main_contact_designation;
				$data['opportunities_list'][$i]['opportunity_main_contact_email'] = $opp_list->opportunity_main_contact_email;
				$data['opportunities_list'][$i]['opportunity_main_contact_mobile'] = $opp_list->opportunity_main_contact_mobile;
				$data['opportunities_list'][$i]['opportunity_main_contact_category'] = $opp_list->opportunity_main_contact_category;
				$data['opportunities_list'][$i]['opportunity_main_contact_phone'] = $opp_list->opportunity_main_contact_phone;
				$data['opportunities_list'][$i]['opportunity_main_contact_company'] = $opp_list->opportunity_main_contact_company;
				
				$data['opportunities_list'][$i]['no_of_flats'] = $opp_list->no_of_flats;
				$data['opportunities_list'][$i]['cubic_meters'] = $opp_list->cubic_meters;
				$data['opportunities_list'][$i]['sft'] = $opp_list->sft;
				$data['opportunities_list'][$i]['remarks'] = $opp_list->Opp_remarks;
				$data['opportunities_list'][$i]['Finalizationdate'] = $opp_list->Finalizationdate;
				$data['opportunities_list'][$i]['requirement_details_collected'] = $opp_list->requirement_details_collected;
				$data['opportunities_list'][$i]['business_status'] = $opp_list->business_status;
				$data['opportunities_list'][$i]['business_status_delayed_value'] = $opp_list->business_status_delayed_value;
				$data['opportunities_list'][$i]['business_status_pending_value'] = $opp_list->business_status_pending_value;
				$data['opportunities_list'][$i]['business_status_lost_value'] = $opp_list->business_status_lost_value;
				$data['opportunities_list'][$i]['business_status_lost_other_value'] = $opp_list->business_status_lost_other_value;
				
				$data['opportunities_list'][$i]['created_date_time'] = $opp_list->createdDateTime;
				
				$Associate_contact_id = $opp_list->opportunity_main_contact_id;
				if($Associate_contact_id != "" || $Associate_contact_id != NULL){
					$contact_list_a = $this->db->query("select OAC.contact_id, OAC.opportunity_associate_contacts_id, OAC.designation, C.FirstName, C.LastName from opportunity_associate_contacts OAC inner join contacts C on (OAC.contact_id = C.contact_id) where opportunity = ".$opp_list->opportunity_id)->result();
					$c=0;
					foreach($contact_list_a as $assoc_val){
						$data['opportunities_list'][$i]['associate_contact'][$c]["opportunity_associate_contacts_id"] = $assoc_val->opportunity_associate_contacts_id;
						$data['opportunities_list'][$i]['associate_contact'][$c]["contact_id"] = $assoc_val->contact_id;
						$data['opportunities_list'][$i]['associate_contact'][$c]["contact_name"] = $assoc_val->FirstName." ".$assoc_val->LastName;
						$data['opportunities_list'][$i]['associate_contact'][$c]["designation"] = $assoc_val->designation;
						$c++;
					}
				}else{
					$data['opportunities_list'][$i]['associate_contact'] = array();
				}
				
				$checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$opp_list->customer_id."'")->row();

				$product_opportunitie_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity ='".$opp_list->opportunity_id."' group by b.product_code")->result();
              
				if(count($product_opportunitie_list) >0){
					$j=0;
					foreach($product_opportunitie_list as $popp_list){
						$data['opportunities_list'][$i]['final_product'][$j]['Product_opportunities_id'] = $popp_list->Product_opportunities_id;
						$data['opportunities_list'][$i]['final_product'][$j]['product_id'] = $popp_list->Product;
						$data['opportunities_list'][$i]['final_product'][$j]['product_name'] = $popp_list->product_name;
						$data['opportunities_list'][$i]['final_product'][$j]['probability'] = $popp_list->Probability;
						$data['opportunities_list'][$i]['final_product'][$j]['quantity'] = $popp_list->Quantity;
						$data['opportunities_list'][$i]['final_product'][$j]['rate_per_sft'] = $popp_list->final_product_price;
						$data['opportunities_list'][$i]['final_product'][$j]['value'] = $popp_list->final_product_value;
						$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_from'] = date("d-m-Y",strtotime($popp_list->schedule_date_from));
						$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_upto'] = date("d-m-Y",strtotime($popp_list->schedule_date_upto));
						$j++;
					}
				}else{
					$data['opportunities_list'][$i]['final_product'] = array();
				}

				$brand_producta_list = $this->db->query("select * from Products_Brands_targeted_opp where Opportunity =".$opp_list->opportunity_id)->result();
              
				if(count($brand_producta_list)>0){
					$k=0;
					foreach($brand_producta_list as $brand_product_val){
						$data['opportunities_list'][$i]['brands_product'][$k]['brands_opp_id'] = $brand_product_val->brands_opp_id;
						$data['opportunities_list'][$i]['brands_product'][$k]['product'] = $brand_product_val->Brands_Product;
						$data['opportunities_list'][$i]['brands_product'][$k]['units'] = $brand_product_val->Brands_Units;
						$data['opportunities_list'][$i]['brands_product'][$k]['quantity'] = $brand_product_val->Brands_Quantity;
						$data['opportunities_list'][$i]['brands_product'][$k]['price'] = $brand_product_val->Brands_Price;
						$k++;
					}
				}else{
					$data['opportunities_list'][$i]['brands_product'] = array();
				}

				$Competition_targeted_list = $this->db->query("select * from Competition_targeted_opp where Opportunity = ".$opp_list->opportunity_id)->result();
              
				if(count($Competition_targeted_list) >0){
					$l=0;
					foreach($Competition_targeted_list as $competition_val){
						$data['opportunities_list'][$i]['competition_product'][$l]['competitions_opp_id'] = $competition_val->competitions_opp_id;                 
						$data['opportunities_list'][$i]['competition_product'][$l]['product'] = $competition_val->Competition_Product;
						$data['opportunities_list'][$i]['competition_product'][$l]['units'] = $competition_val->Competition_Units;                 
						$data['opportunities_list'][$i]['competition_product'][$l]['price'] = $competition_val->Competition_Price;
						$l++;
					}
				}else{
					$data['opportunities_list'][$i]['competition_product'] = array();
				}
				
				$remarks_list = $this->db->query("select * from opportunities_remarks where opportunity_id = ".$opp_list->opportunity_id." ORDER BY remark_date ASC")->result();
				if(count($remarks_list) > 0){
					$l = 0;
					foreach($remarks_list as $remark){
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_id'] = $remark->remark_id;     						
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark'] = $remark->remark;
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_date'] = $remark->remark_date;             
						$l++;
					}
				}else{
					$data['opportunities_list'][$i]['opp_remarks'] = [];
				}
				
				$i++;
				
            }
		}else{
			$data['opportunities_list'] = array();
		}	
	
		if(count($data)>0){
			$this->response(array('code'=>'200','message'=>'opportunities_list', 'result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'200','message' => 'Opportunities are Empty','result'=>null,'requestname'=>$method));
		}
	}

  public function product_price_list($parameters,$method,$user_id){
	  
	$product_price_master_info = $this->db->query("select Product_price_master_id from users where user_id = ".$user_id)->row();

	if(count($product_price_master_info) > 0){
		$price_list_id = $product_price_master_info->Product_price_master_id;
	}

    if($price_list_id != "" || $price_list_id != null || $price_list_id != 0 ){
      //$product_list = $this->db->query("select * from product_master a inner join Price_list_line_Item b on (a.product_id = b.product) where b.Price_list_id = '".$parameters['price_list_id']."'")->result();
	  
	  //$product_list = $this->db->query("select * FROM product_master PM INNER JOIN price_list_line_item PL ON PM.product_id=PL.product INNER JOIN price_master_divisions PMD ON PL.price_master_division_id = PMD.price_master_division_id WHERE PMD.Product_price_master_id = '".$parameters['price_list_id']."' AND PMD.division_id = '".$parameters['division_id']."'")->result();
	  
	  $product_list = $this->db->query("select * FROM product_master PM INNER JOIN price_list_line_item PL ON PM.product_id=PL.product INNER JOIN price_master_divisions PMD ON PL.price_master_division_id = PMD.price_master_division_id WHERE PMD.Product_price_master_id = '".$price_list_id."' AND PMD.division_id = '".$parameters['division_id']."'")->result();
	  
      $i=0;
      foreach($product_list as $product_val){
        $data['product_drop'][$i]['product_id'] = $product_val->product_id;
        $data['product_drop'][$i]['product_code'] = $product_val->product_code;
        $data['product_drop'][$i]['product_name'] = $product_val->product_name;
        $data['product_drop'][$i]['price'] = $product_val->price;
		$data['product_drop'][$i]['weight'] = $product_val->Weight;
        $i++;
      }
       if(count($data)>0){
        $this->response(array('code'=>'200','message'=>'product_price_list', 'result'=>$data,'requestname'=>$method));
      }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
      }
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }
   
  }

	/* Commenting old code for opportunity insert
	* Dated: 29 Sep 2020 12:40am
	public function opportunities_insert($parameters,$method,$user_id){
	  
    $checking_id = $this->db->query("select * from opportunities order by opportunity_id DESC")->row();
	
    if($checking_id->opp_id == NULL || $checking_id->opp_id == ""){
        $opp_id = "OP-00001";
    }else{
        $opp_check = trim($checking_id->opp_id);
        $checking_op_id =  substr($opp_check, 3);
        if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
            $opp_id_last_inc = (++$checking_op_id);
            $opp_id= "OP-".$opp_id_last_inc;
        }else{
            $opp_id = (++$opp_check);
        } 
    }
	
    $param['opp_id'] = $opp_id;
    $param['OpportunityName'] = $parameters['OpportunityName'];
    $param['Customer'] = $parameters['Customer'];
    $param['GSTIN'] = $parameters['GSTIN'];
    $param['CloseDate'] = date("Y-m-d H:i:s",strtotime($parameters['CloseDate']));
    $param['Description'] = $parameters['Description'];
    $param['ExpectedRevenue'] = $parameters['ExpectedRevenue'];
    $param['NextStep'] = $parameters['NextStep'];
    $param['Stage'] = $parameters['Stage'];
    $param['sampling'] = $parameters['sampling'];
    $param['Probability'] = $parameters['Probability'];
    //$param['Email'] = $parameters['Email'];
    //$param['Fax'] = $parameters['Fax'];
    $param['Rating'] = $parameters['Rating'];
	// $param['Industry'] = $parameters['Industry'];
    //$param['Mobile'] = $parameters['Mobile'];
    $param['project_name'] = $parameters['project_name'];
    $param['project_type'] = $parameters['project_type'];
    $param['size_class_project'] = $parameters['size_class_project'];
    $param['status_project'] = $parameters['status_project'];
    //$param['BillingStreet1'] = $parameters['BillingStreet1'];
    //$param['Billingstreet2'] = $parameters['BillingStreet2'];
    //$param['BillingCountry'] = $parameters['BillingCountry'];
    //$param['BillingCity'] = $parameters['BillingCity'];
	//$param['BillingZipPostal'] = $parameters['BillingZipPostal'];
    //$param['ShippingStreet1'] = $parameters['ShippingStreet1'];
	//param['Shippingstreet2'] = $parameters['Shippingstreet2'];
    //param['ShippingCountry'] = $parameters['ShippingCountry'];
    //$param['ShippingStateProvince'] = $parameters['ShippingStateProvince'];
    //$param['ShippingCity'] = $parameters['ShippingCity'];
	//$param['ShippingZipPostal'] = $parameters['ShippingZipPostal'];
    $param['DoNotCall'] = $parameters['DoNotCall'];
    $param['OpportunityOwner'] = $user_id;
    $param['created_by'] = $user_id;
    $param['modified_by'] = $user_id;
    $param['created_date_time'] = date("Y-m-d H:i:s");
    $param['modified_date_time'] = date("Y-m-d H:i:s");
    //$param['TotalPrice'] = $parameters['TotalPrice'];
    $opportunity_id = $this->Generic_model->insertDataReturnId("opportunities",$param);
    $data_1['opportunity_id'] = $opportunity_id;
    if($opportunity_id != "" ||$opportunity_id != NULL ){
      $user_list = $this->db->query("select * from users where user_id = '".$user_id."'")->row();
      $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."'")->row();


              if(count($user_list)>0){
                $push_noti['fcmId_android'] = $user_list->fcmId_android;
                $push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
              }else{
                $push_noti['fcmId_android'] ="";
                $push_noti['fcmId_iOS'] = "";   
              }
              if(count($user_report_to) >0){
                $push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
                $push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
              }else{
                $push_noti['fcmId_android_report_to'] = "";
                $push_noti['fcmId_iOS_report_to'] = "";
              }
              $push_noti['opportunity_id'] = $opportunity_id;
              $push_noti['user_id'] = $user_id;
              $push_noti['subject'] = "A new Opportunitie has been created successfully  OpportunityName  : ".$parameters['OpportunityName']." CustomerName : ". $customer_list->CustomerName.", Stage  : ".$parameters['Stage']." Probability  : ".$parameters['Probability']."";
              $this->PushNotifications->Opportunitie_notifications($push_noti);

              $latest_val['module_id'] = $opportunity_id;
              $latest_val['module_name'] = "Opportunitie";
              $latest_val['user_id'] = $user_id;
              $latest_val['created_date_time'] = date("Y-m-d H:i:s");
              $this->Generic_model->insertData("update_table",$latest_val);



        $param_noti['notiffication_type'] = "Opportunitie";
                $param_noti['notiffication_type_id'] = $opportunity_id;
                $param_noti['user_id'] = $user_id;
                $param_noti['subject'] = " A new Opportunitie has been created successfully  OpportunityName  : ".$parameters['OpportunityName']." CustomerName : ". $customer_list->CustomerName.", Stage  : ".$parameters['Stage']." Probability  : ".$parameters['Probability']."";
              $this->Generic_model->insertData("notiffication",$param_noti);



      $products_price = count($parameters['products_price']);
      if($parameters["products_price"][0]['Product'] != "" || $parameters["products_price"][0]['Product'] != NULL){
        for($k=0;$k<$products_price;$k++){
          $Product_id = $parameters["products_price"][$k]['Product'];
          //$product_details = $this->db->query("select * from product_master where     product_id =".$Product_id)->row();

              $param_2['Opportunity'] = $opportunity_id;
              $param_2['Probability'] = $parameters["products_price"][$k]['Probability'];
              $param_2['Product'] = $parameters["products_price"][$k]['Product'];
              //$param_2['Productcode'] = $product_details->product_code;
              $param_2['Quantity'] = $parameters["products_price"][$k]['Quantity'];
              $param_2['schedule_date_from'] = date("Y-m-d",strtotime($parameters["products_price"][$k]['schedule_date_from']));
              $param_2['schedule_date_upto'] = date("Y-m-d",strtotime($parameters["products_price"][$k]['schedule_date_upto']));
              $param_2['created_by'] =$user_id;
              $param_2['modified_by'] =$user_id;
              $param_2['created_date_time'] =date("Y-m-d H:i:s");
              $param_2['modified_date_time'] =date("Y-m-d H:i:s");
              $ok = $this->Generic_model->insertData("product_opportunities",$param_2);
           // echo $this->db->last_query();
          }
        }
          $Associate_contact_id_str = $parameters["Associate_contact_id"];
          $Associate_contact_id = explode(",",$Associate_contact_id_str);
              if(count($Associate_contact_id) >0){
                for($ia=0;$ia<count($Associate_contact_id);$ia++){
                  $param_21['Opportunity'] = $opportunity_id;
                  $param_21['user_id'] = $Associate_contact_id[$ia];
                  $param_21['created_by'] = $user_id;
                  $param_21['created_date_time'] = date("Y-m-d H:i:s");
                  $this->Generic_model->insertData("opportunity_associate_contacts",$param_21);
                }
              }

         if($parameters["Brands_list"][0]['Brands_Product'] != "" || $parameters["Brands_list"][0]['Brands_Product'] != NULL){
         // echo "hii Brands_list";
          $Brands_Product_list =  count($parameters['Brands_list']);

           for($a=0;$a<$Brands_Product_list;$a++){
          
              $param_3['Opportunity'] = $opportunity_id;
              $param_3['Brands_Product'] = $parameters["Brands_list"][$a]['Brands_Product'];
              $param_3['Brands_Units'] = $parameters["Brands_list"][$a]['Brands_Units'];
              $param_3['Brands_Quantity'] = $parameters["Brands_list"][$a]['Brands_Quantity'];
              $param_3['Brands_Price'] = $parameters["Brands_list"][$a]['Brands_Price'];
              $ok = $this->Generic_model->insertData("Products_Brands_targeted_opp",$param_3);
          
            }

         }

         if($parameters["Competition_insert"][0]['Competition_Product'] != "" || $parameters["Competition_insert"][0]['Competition_Product'] != NULL){
          $Competition_Units_list =  count($parameters['Competition_insert']);
           //echo "hii Competition_insert";
           for($m=0;$m<$Competition_Units_list;$m++){
              $param_4['Opportunity'] = $opportunity_id;
              $param_4['Competition_Units'] = $parameters["Competition_insert"][$m]['customer'];
              $param_4['Competition_Product'] = $parameters["Competition_insert"][$m]['Competition_Product'];
              //$param_4['Competition_Quantity'] = $parameters["Competition_insert"][$m]['Competition'];
              $param_4['Competition_Price'] = $parameters["Competition_insert"][$m]['Competition_Price'];
              $ok1 = $this->Generic_model->insertData("Competition_targeted_opp",$param_4);

            }

         }
         
                    $opp_product_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity = '".$opportunity_id."' group by b.product_code")->result();

                   

                    $customer_list = $this->db->query("select * from customers where customer_id =".$parameters["Customer"])->row();
                    $email = $user_list->email;
                    $to = $email;  //$to      = $dept_email_id;
                    $subject = "New Opportunitie created";
                    $data['name'] = $user_list->name;

                    $data['message'] = "<p> A new Opportunitie has been created successfully <br/><br/><b> OpportunityName </b> : ".$parameters['OpportunityName']."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><b>Stage </b> : ".$parameters['Stage']."<br/><b>Probability </b> : ".$parameters['Probability']."</p> <br/><br/>
                      <table width='100%'  aliparameters[='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                        </tr></thead>";

                       if(count($opp_product_list) >0){
                          foreach($opp_product_list as $opp_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  


              $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

                     
                      if(count($user_report_to) >0){
                        $email = $user_report_to->email;
                        $to = $email;  //$to      = $dept_email_id;
                        $subject = "New Opportunitie created";
                        $data['name'] = $user_report_to->name;
                        $data['message'] = "<p> A new Opportunitie has been created successfully By <b>".$user_list->name."</b> <br/><br/><b> OpportunityName </b> : ".$parameters['OpportunityName']."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName.", <br/><b>Stage </b> : ".$parameters['Stage']."<br/><b>Probability </b> : ".$parameters['Probability']."</p> <br/><br/>
                      <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                        </tr></thead>";

                       if(count($opp_product_list) >0){
                          foreach($opp_product_list as $opp_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  


                        $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

              }
              




              $return_data = $this->all_tables_records_view("opportunitie",$opportunity_id);

        if($ok == 1){
            $this->response(array('code'=>'200','message'=>'opportunities insert successfully', 'result'=>$return_data,'requestname'=>$method));
        }else{
          $return_data = $this->all_tables_records_view("Customer",$opportunity_id);

          $this->response(array('code'=>'200','message'=>'opportunities insert successfully', 'result'=>$return_data,'requestname'=>$method));
        }
    }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
      }

  }
	*/
	
	/** 
	* Function opportunity_insert creates an opportunity record when the lead is converted
	* Last Updated: 07 Oct. '20
	*/
	function opportunity_insert($parameters,$method,$user_id) {
		
		$param = $parameters;		
		
		if(count($param)>0){
			// Generate unique id for an opportunity record
            $checking_id = $this->db->query("select * from opportunities order by opportunity_id DESC")->row();
            if($checking_id->opp_id == NULL || $checking_id->opp_id == ""){
                $opp_id = "OP-00001";
            }else{
                $opp_check = trim($checking_id->opp_id);
                $checking_op_id =  substr($opp_check, 3);
                if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
                    $opp_id_last_inc = (++$checking_op_id);
                    $opp_id= "OP-".$opp_id_last_inc;
                }else{
                    $opp_id = (++$opp_check);
                } 
            }
			
			// Check if the account is tagged 
			if($param['Company'] != '' || $param['Company'] != NULL){
				$param_1['isAccountTagged']=1;
			}else{
				$param_1['isAccountTagged']=0;
			}
			$param_1['Company'] = $param['Company'];
			$param_1['Company_text'] = $param['Company_Text'];
						
			$param_1['opp_id'] = $opp_id;
			$param_1['Leadno'] = $param["Leadno"];  

			$param_1['sampling'] = $param["sampling"];  
			$param_1['mockup'] = $param["mockup"];			
			$param_1['Rating'] = $param["Rating"];
			$param_1['project_name'] = $param["project_name"];
			$param_1['project_type'] = $param["project_type"];
			$param_1['size_calss_unit'] = $param["size_calss_unit"];
			$param_1['size_class_project'] = $param["size_class_project"];
			$param_1['lead_class_of_project'] = $param["lead_class_of_project"];
			$param_1['size_calss_unit_no_of_blocks'] = $param["size_calss_unit_no_of_blocks"];
			$param_1['size_calss_unit_no_of_floor_per_block'] = $param["size_calss_unit_no_of_floor_per_block"];
			$param_1['status_project'] = $param["status_project"];
			
			/* billing address parameters */
			$param_1['BillingStreet1'] = $param["BillingStreet1"];
			$param_1['Billingstreet2'] = $param["BillingStreet2"];
			$param_1['BillingPlotno'] = $param["BillingPlotno"];
			$param_1['BillingArea'] = $param["BillingArea"];			
			$param_1['BillingCity'] = $param["BillingCity"];
			$param_1['BillingZipPostal'] = $param["BillingZipPostal"];
			$param_1['BillingState'] = $param["BillingState"];						
			$param_1['BillingCountry'] = $param["BillingCountry"];
			$param_1['BillingWebsite'] = $param["BillingWebsite"];
			$param_1['BillingEmail'] = $param["BillingEmail"];
			$param_1['BillingPhone'] = $param["BillingPhone"];			
			$param_1['sft'] = $param["sft"];			
			$param_1['cubic_meters'] = $param["cubic_meters"];			
			$param_1['no_of_flats'] = $param["no_of_flats"];			
			
			/*  Shipping parameters */
			$param_1['ShippingStreet1'] = $param["ShippingStreet1"];
			$param_1['Shippingstreet2'] = $param["Shippingstreet2"];
			$param_1['ShippingLandmark'] = $param["ShippingLandmark"];
			$param_1['Shippingplotno'] = $param["Shippingplotno"];
			$param_1['ShippingCity'] = $param["ShippingCity"];
			$param_1['ShippingStateProvince'] = $param["ShippingStateProvince"];
			$param_1['ShippingZipPostal'] = $param["ShippingZipPostal"];			 
			
			/* main contact parameters */			
			$param_1['opportunity_main_contact_id']=$param["opportunity_main_contact_id"];
			$param_1['opportunity_main_contact_designation']=$param["opportunity_main_contact_designation"];
			$param_1['opportunity_main_contact_email']=$param["opportunity_main_contact_email"];
			$param_1['opportunity_main_contact_mobile']=$param["opportunity_main_contact_mobile"];
			$param_1['opportunity_main_contact_category']=$param["opportunity_main_contact_category"];
			$param_1['opportunity_main_contact_phone']=$param["opportunity_main_contact_phone"];             
			$param_1['opportunity_main_contact_company']=$param["opportunity_main_contact_company"];             
			
			$param_1['requirement_details_collected']=$param["requirement_details_collected"];             
			$param_1['Finalizationdate']=$param["Finalizationdate"];             
			$param_1['remarks']=$param["remarks"];             
			
			$param_1['business_status']=$param["business_status"];             
			
			$param_1['OpportunityOwner'] = $user_id;
			$param_1['created_by'] = $user_id;
			$param_1['modified_by'] = $user_id;
			$param_1['created_date_time'] = date("Y-m-d H:i:s");
			$param_1['modified_date_time'] = date("Y-m-d H:i:s");		
			
			$opportunity_id = $this->Generic_model->insertDataReturnId("opportunities",$param_1);
			
			if($opportunity_id != "") { 
			
				$isleadconverted = $this->Generic_model->updateData('leads', array('is_lead_converted'=>'2'), array('lead_number' => $param_1['Leadno']));
			
				$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
				$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();

				if(count($user_list)>0){
					$push_noti['fcmId_android'] = $user_list->fcmId_android;
					$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
				}else{
					$push_noti['fcmId_android'] ="";
					$push_noti['fcmId_iOS'] = "";   
				}
			  
				if(count($user_report_to) >0){
					$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
					$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
				}else{
					$push_noti['fcmId_android_report_to'] = "";
					$push_noti['fcmId_iOS_report_to'] = "";
				}
							  
				$push_noti['opportunity_id'] = $opportunity_id;
				$push_noti['user_id'] = $user_id;
				$push_noti['subject'] = " A new Opportunitie has been created successfully  CustomerName : ".$param['Company_text'].", Stage  : ".$param['Stage']." , Probability < : ".$param['Probability']."";
				$this->PushNotifications->Opportunitie_notifications($push_noti);
		
				$param_noti['notiffication_type'] = "Opportunitie";
                $param_noti['notiffication_type_id'] = $opportunity_id;
                $param_noti['user_id'] = $user_id;
                $param_noti['subject'] = "A new Opportunitie has been created successfully  CustomerName : ".$param['Company_text'].", Stage  : ".$this->input->post('Stage')." , Probability < : ".$param['Probability']."";
				$this->Generic_model->insertData("notiffication",$param_noti);
				
				// inserting a log
				$latest_val['module_id'] = $opportunity_id;
				$latest_val['module_name'] = "Opportunitie";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
				
				// Creating & mapping brand products
				$Brands_Product = $param['brands_product'];
				for($a=0; $a<count($Brands_Product); $a++){
					if($Brands_Product[$a]['product'] != "" || $Brands_Product[$a]['product'] != NULL){
						$param_a['Opportunity'] = $opportunity_id;
						$param_a['Brands_Product'] = $Brands_Product[$a]['product'];
						$param_a['Brands_Units'] = $Brands_Product[$a]['units'];
						$param_a['Brands_Quantity'] = $Brands_Product[$a]['quantity'];
						$param_a['Brands_Price'] = $Brands_Product[$a]['price'];
						$ok2 = $this->Generic_model->insertData("Products_Brands_targeted_opp",$param_a);						
					}
				}
				
				// Creating & mapping associate contacts
				$associate_contact = $param['associate_contact'];	
				if(count($associate_contact) >0){
					for($ia=0;$ia<count($associate_contact);$ia++){
						$param_21['Opportunity'] = $opportunity_id;
						$param_21['contact_id'] = $associate_contact[$ia]['contact_id'];
						$param_21['designation'] = $associate_contact[$ia]['designation'];
						$param_21['user_id'] = $user_id;
						$param_21['created_by'] = $user_id;
						$param_21['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("opportunity_associate_contacts",$param_21);
					}
				}
				
				// Creating & mapping competitor product
				$Competition_Product = $param['competition_product'];
				for($m=0;$m<count($Competition_Product);$m++){
					if($Competition_Product[$m]['product'] != "" || $Competition_Product[$m]['product'] != NULL){
						$param_m['Opportunity'] = $opportunity_id;
						$param_m['Competition_Product'] = $Competition_Product[$m]['product'];						
						$param_m['Competition_Units'] = $Competition_Product[$m]['units'];						
						$param_m['Competition_Price'] = $Competition_Product[$m]['price'];

						$ok3 = $this->Generic_model->insertData("Competition_targeted_opp",$param_m);
					}
				}
				
				// Creating Remarks
				$remarks = $param['opp_remarks'];
				if(count($remarks) > 0){
					foreach($remarks as $remark){	
						$opp_remark['opportunity_id'] = $opportunity_id;
						$opp_remark['remark'] = $remark['remark'];
						$opp_remark['remark_date'] = $remark['remark_date'];
						
						// Insert remark
						$this->Generic_model->insertData("opportunities_remarks",$opp_remark);  
						
					}
				}
				
				// Creating & mapping final product
				$Final_product = $param['final_product'];	
				if($Final_product[0]['product_id'] != "" || $Final_product[0]['product_id'] != NULL){
					$ok = 0;
					for($j=0;$j<count($Final_product);$j++){						
						$param_2['Opportunity'] = $opportunity_id;
						$param_2['Product'] = $Final_product[$j]['product_id'];
						$param_2['Quantity'] = $Final_product[$j]['quantity'];
						$param_2['final_product_price'] = $Final_product[$j]['rate_per_sft'];
						$param_2['final_product_value'] = $Final_product[$j]['value'];
						$param_2['Probability'] = $Final_product[$j]['probability'];
						$param_2['schedule_date_from'] = date("Y-m-d",strtotime($Final_product[$j]['schedule_date_from'])); // This is treated as the required date
						$param_2['created_by'] =$user_id;
						$param_2['modified_by'] =$user_id;
						$param_2['created_date_time'] =date("Y-m-d H:i:s");
						$param_2['modified_date_time'] =date("Y-m-d H:i:s");
						
						$ok = $this->Generic_model->insertData("product_opportunities",$param_2); 
					}				
					
					if($ok == 1){
						$opp_product_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity = '".$opportunity_id ."'  group by b.product_code")->result();
						$email = $user_list->email;
						$to = $email;  //$to      = $dept_email_id;
						$subject = "New Opportunitie created";
						$data['name'] = $user_list->name;

						$data['message'] = "<p> A new Opportunitie has been created successfully <br/><br/><b>  <b>CustomerName </b> : ". $param_1['Company_text'].",<br/><br/>
						<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
						<thead>
						<tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                        </tr></thead>";

						if(count($opp_product_list) >0){
							foreach($opp_product_list as $opp_values){
								$data['message'].=  "<tbody><tr>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
							}
                        }

                        $data['message'].= "</table><br/>";  
						$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
                     
						if(count($user_report_to) >0){
							$email = $user_report_to->email;
							$to = $email;
							$subject = "New Opportunitie created";
							$data['name'] = $user_report_to->name;
							$data['message'] = "<p> A new Opportunitie has been created successfully By <b>".$user_list->name."</b> <br/><br/><b> Rating </b> : ".$param['Rating']."<br/> <b>CustomerName </b> : ".$param_1['Company_text'].", <br/><b>Email</b> : ".$param['Email']." ,<br/> <b>MobileNumber</b> : ".$param['Mobile']." <br/><br/><br/>
							<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
							<thead>
							<tr >
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
							</tr></thead>";

							if(count($opp_product_list) >0){
								foreach($opp_product_list as $opp_values){
									$data['message'].=  "<tbody><tr>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
								}
							}
							$data['message'].= "</table><br/>";  
						}
					}
				}
				$return_data = $this->all_tables_records_view("opportunitie",$opportunity_id);				
				$this->response(array('code'=>'200','message'=>'opportunity created successfully', 'result'=>$return_data,'requestname'=>$method));
            }else{
				$this->response(array('code'=>'404','message' => 'Opportunity Insert Failed'), 200);
            }
		}
	}
		
	
	/** Function opportunity edit
	* This function renders below information 
	* Opportunity record chages, 
	* Associative contacts: existing with id + New, 
	* Opportunity progress: existing records with id + new,
	* Competition records: existing with id + new,
	* Final Product records: exiting with id + new
	* This function output woudl be saving changes and adding new records to the db
	*/
	/*function opportunity_edit($parameters,$method,$user_id) {
		
		$param = $parameters;	

		$opportunity_id = $param['opportunity_id'];	
		
		if(count($param)>0){			
			
			// Get Customer company info checking with company name
			$isCompanyexists = $this->db->query("SELECT * FROM customers where UPPER(CustomerName)=UPPER('".$param['Company']."')")->row();
			
			if($isCompanyexists->customer_id){ 
				$param_1['Company']=$isCompanyexists->customer_id;
				$param_1['isAccountTagged']=1;
				$param_1['Company_text']=$isCompanyexists->CustomerName;				
				$company_info = $isCompanyexists->customer_id;
				
				if(!empty($company_info)){
					$company_list = $this->db->query("select * from customers where customer_id =".$isCompanyexists->customer_id)->row();
				}
			}else{ // if company provided is doesn't exist
				$param_1['Company'] = NULL;
				$param_1['Company_text']=$param['Company'];
				$param_1['isAccountTagged']=0;
			}			
			
			$param_1['opp_id'] = $opp_id;
			$param_1['Leadno'] = $param["Leadno"];  

			$param_1['sampling'] = $param["sampling"];  
			$param_1['mockup'] = $param["mockup"];			
			$param_1['Rating'] = $param["Rating"];
			$param_1['project_name'] = $param["project_name"];
			$param_1['project_type'] = $param["project_type"];
			$param_1['size_class_project'] = $param["size_class_project"];
			$param_1['status_project'] = $param["status_project"];
			
			// billing address parameters
			$param_1['BillingStreet1'] = $param["BillingStreet1"];
			$param_1['Billingstreet2'] = $param["Billingstreet2"];
			$param_1['BillingCountry'] = $param["BillingCountry"];
			$param_1['BillingState'] = $param["BillingState"];
			$param_1['BillingCity'] = $param["BillingCity"];
			$param_1['BillingZipPostal'] = $param["BillingZipPostal"];
			$param_1['BillingArea'] = $param["BillingArea"];
			$param_1['BillingWebsite'] = $param["BillingWebsite"];
			$param_1['BillingEmail'] = $param["BillingEmail"];
			$param_1['BillingPhone'] = $param["BillingPhone"];			
			
			// Shipping parameters
			$param_1['ShippingStreet1'] = $param["ShippingStreet1"];
			$param_1['Shippingstreet2'] = $param["Shippingstreet2"];
			$param_1['ShippingLandmark'] = $param["ShippingLandmark"];
			$param_1['Shippingplotno'] = $param["Shippingplotno"];
			$param_1['ShippingCity'] = $param["ShippingCity"];
			$param_1['ShippingStateProvince'] = $param["ShippingStateProvince"];
			$param_1['ShippingZipPostal'] = $param["ShippingZipPostal"];			 
			
			// main contact parameters 
			$param_1['opportunity_main_contact_id']=$param["opportunity_main_contact_id"];
			$param_1['opportunity_main_contact_designation']=$param["opportunity_main_contact_designation"];
			$param_1['opportunity_main_contact_email']=$param["opportunity_main_contact_email"];
			$param_1['opportunity_main_contact_mobile']=$param["opportunity_main_contact_mobile"];
			$param_1['opportunity_main_contact_category']=$param["opportunity_main_contact_category"];
			$param_1['opportunity_main_contact_phone']=$param["opportunity_main_contact_phone"];             
			$param_1['OpportunityOwner'] = $user_id;
			$param_1['created_by'] = $user_id;
			$param_1['modified_by'] = $user_id;
			$param_1['created_date_time'] = date("Y-m-d H:i:s");
			$param_1['modified_date_time'] = date("Y-m-d H:i:s");		
			
			// Update Opportunity DB
			$ok = $this->Generic_model->updateData("opportunities",$param_1, array('opportunity_id' => $opportunity_id));	
			
			if($ok == 1){
				
				$check_update_list = $this->db->query("select * from update_table where module_id ='".$opportunity_id."' and module_name ='Opportunitie'")->row();
				if(count($check_update_list)>0){
					$latest_val['user_id'] = $user_id;
					$latest_val['created_date_time'] = date("Y-m-d H:i:s");
					$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $opportunity_id,'module_name'=>'Opportunitie'));
				}else{
					$latest_val['module_id'] = $id;
					$latest_val['module_name'] = "Opportunitie";
					$latest_val['user_id'] = $user_id;
					$latest_val['created_date_time'] = date("Y-m-d H:i:s");
					$this->Generic_model->insertData("update_table",$latest_val);
				}
				
				// Update Brand Products
				// If new contacts added then insert records & Map
				$Brands_Product = $param['brands_product'];
				for($a=0; $a<count($Brands_Product); $a++){					
					$brands_opp_id = $Brands_Product[$a]['brands_opp_id'];
					$param_a['Opportunity'] = $opportunity_id;
					$param_a['Brands_Product'] = $Brands_Product[$a]['product'];
					$param_a['Brands_Units'] = $Brands_Product[$a]['units'];
					$param_a['Brands_Quantity'] = $Brands_Product[$a]['quantity'];
					$param_a['Brands_Price'] = $Brands_Product[$a]['price'];
					
					if($brands_opp_id == "" || $brands_opp_id == NULL){ // New Brand Products						
						$this->Generic_model->insertData("Products_Brands_targeted_opp",$param_a);
					}else{
						$this->Generic_model->updateData('Products_Brands_targeted_opp', $param_a, array('brands_opp_id' => $brands_opp_id));						
					}
				}
				
				// Update Associative Contacts
				// If new contacts added then insert records & Map
				$associate_contact = $param['associate_contact'];	
				if(count($associate_contact) >0){
					for($ia=0; $ia<count($associate_contact); $ia++){
						$opportunity_associate_contacts_id = $associate_contact[$ia]['opportunity_associate_contacts_id'];
						$param_21['Opportunity'] = $opportunity_id;
						$param_21['contact_id'] = $associate_contact[$ia]['contact_id'];
						$param_21['designation'] = $associate_contact[$ia]['designation'];
						$param_21['user_id'] = $user_id;							
						if($opportunity_associate_contacts_id == "" || $opportunity_associate_contacts_id == NULL){ // New Ass. Contact
							$param_21['created_by'] = $user_id;
							$param_21['created_date_time'] = date("Y-m-d H:i:s");
							$this->Generic_model->insertData("opportunity_associate_contacts",$param_21);
						}else{ // Existing Ass. Contact : Update Record							
							$this->Generic_model->updateData('opportunity_associate_contacts', $param_21, array('opportunity_associate_contacts_id' => $opportunity_associate_contacts_id));
						}
					}
				}
				
				// Update Competitors Products
				// If new contacts added then insert records & Map
				$Competition_Product = $param['competition_Product'];
				for($m=0;$m<count($Competition_Product);$m++){
					$competitions_opp_id = $Competition_Units[$m]['competitions_opp_id'];
					$param_m['Opportunity'] = $opportunity_id;
					$param_m['Competition_Product'] = $Competition_Product[$m]['product'];
					$param_m['Competition_Units'] = $Competition_Units[$m]['units'];						
					$param_m['Competition_Price'] = $Competition_Units[$m]['price'];
					
					if($competitions_opp_id == "" || $competitions_opp_id == NULL){ // New Product records : Insert
						$this->Generic_model->insertData("competition_targeted_opp",$param_m);
					}else{ // Existing Ass. Contact : Update Record						
						$this->Generic_model->updateData('competition_targeted_opp', $param_m, array('competitions_opp_id' => $competitions_opp_id));
					}					
				}
				
				// Update Product Opportunities
				// If new products choosen then insert records & Map
				$Final_product = $param['final_product'];	
				for($j=0;$j<count($Final_product);$j++){						
					$Product_opportunities_id = $Final_product[$j]['Product_opportunities_id'];
					$param_2['Opportunity'] = $opportunity_id;
					$param_2['Product'] = $Final_product[$j]['product_id'];
					$param_2['Quantity'] = $Final_product[$j]['quantity'];
					$param_2['Probability'] = $Final_product[$j]['probability'];
					$param_2['schedule_date_from'] = date("Y-m-d",strtotime($Final_product[$j]['schedule_date_from']));
					$param_2['schedule_date_upto'] = date("Y-m-d",strtotime($Final_product[$j]['schedule_date_upto']));
					$param_2['created_by'] =$user_id;
					$param_2['modified_by'] =$user_id;
					$param_2['created_date_time'] =date("Y-m-d H:i:s");
					$param_2['modified_date_time'] =date("Y-m-d H:i:s");
					
					if($Product_opportunities_id == "" || $Product_opportunities_id == NULL){ // New Product records : Insert
						$this->Generic_model->insertData("product_opportunities",$param_2);
					}else{ // Existing Ass. Contact : Update Record						
						$this->Generic_model->updateData('product_opportunities', $param_2, array('Product_opportunities_id' => $Product_opportunities_id));
					}
				}				
				
				$return_data = $this->all_tables_records_view("opportunitie",$opportunity_id);				
				$this->response(array('code'=>'200','message'=>'opportunity changes saved successfully', 'result'=>$return_data,'requestname'=>$method));
				
			}else{
				$return_data = [];				
				$this->response(array('code'=>'200','message'=>'Failed saving changes to the opportunity', 'result'=>$return_data,'requestname'=>$method));
			}		
		}
	} */
	
	
	/** 
	* Function opportunity_edit save changes to an opportunity record
	* Last Updated: 06 May. '2022
	* Uday Kanth Rapalli
	*/
	function opportunity_edit($parameters,$method,$user_id) {
		
		$param = $parameters;	
			
		if(count($param)>0){
			
			$opportunity_id = $param['opportunity_id'];
			
			// Check if the account is tagged 
			if($param['Company'] != '' || $param['Company'] != NULL){
				$param_1['isAccountTagged']=1;
			}else{
				$param_1['isAccountTagged']=0;
			}
			$param_1['Company'] = $param['Company'];
			$param_1['Company_text'] = $param['Company_Text'];
						
			$param_1['opp_id'] = $opp_id; 

			$param_1['sampling'] = $param["sampling"];  
			$param_1['mockup'] = $param["mockup"];			
			$param_1['Rating'] = $param["Rating"];
			$param_1['project_name'] = $param["project_name"];
			$param_1['project_type'] = $param["project_type"];
			$param_1['size_calss_unit'] = $param["size_calss_unit"];
			$param_1['size_class_project'] = $param["size_class_project"];
			$param_1['lead_class_of_project'] = $param["lead_class_of_project"];
			$param_1['size_calss_unit_no_of_blocks'] = $param["size_calss_unit_no_of_blocks"];
			$param_1['size_calss_unit_no_of_floor_per_block'] = $param["size_calss_unit_no_of_floor_per_block"];
			$param_1['status_project'] = $param["status_project"];
			
			/* billing address parameters */
			$param_1['BillingStreet1'] = $param["BillingStreet1"];
			$param_1['Billingstreet2'] = $param["BillingStreet2"];
			$param_1['BillingPlotno'] = $param["BillingPlotno"];
			$param_1['BillingArea'] = $param["BillingArea"];			
			$param_1['BillingCity'] = $param["BillingCity"];
			$param_1['BillingZipPostal'] = $param["BillingZipPostal"];
			$param_1['BillingState'] = $param["BillingState"];						
			$param_1['BillingCountry'] = $param["BillingCountry"];
			$param_1['BillingWebsite'] = $param["BillingWebsite"];
			$param_1['BillingEmail'] = $param["BillingEmail"];
			$param_1['BillingPhone'] = $param["BillingPhone"];			
			$param_1['sft'] = $param["sft"];			
			$param_1['cubic_meters'] = $param["cubic_meters"];			
			$param_1['no_of_flats'] = $param["no_of_flats"];			
			
			/*  Shipping parameters */
			$param_1['ShippingStreet1'] = $param["ShippingStreet1"];
			$param_1['Shippingstreet2'] = $param["Shippingstreet2"];
			$param_1['ShippingLandmark'] = $param["ShippingLandmark"];
			$param_1['Shippingplotno'] = $param["Shippingplotno"];
			$param_1['ShippingCity'] = $param["ShippingCity"];
			$param_1['ShippingStateProvince'] = $param["ShippingStateProvince"];
			$param_1['ShippingZipPostal'] = $param["ShippingZipPostal"];			 
			
			/* main contact parameters */			
			$param_1['opportunity_main_contact_id']=$param["opportunity_main_contact_id"];
			$param_1['opportunity_main_contact_designation']=$param["opportunity_main_contact_designation"];
			$param_1['opportunity_main_contact_email']=$param["opportunity_main_contact_email"];
			$param_1['opportunity_main_contact_mobile']=$param["opportunity_main_contact_mobile"];
			$param_1['opportunity_main_contact_category']=$param["opportunity_main_contact_category"];
			$param_1['opportunity_main_contact_phone']=$param["opportunity_main_contact_phone"];             
			$param_1['opportunity_main_contact_company']=$param["opportunity_main_contact_company"];             
			
			$param_1['requirement_details_collected']=$param["requirement_details_collected"];             
			$param_1['Finalizationdate']=$param["Finalizationdate"];             
			$param_1['remarks']=$param["remarks"];             
			
			$param_1['business_status']=$param["business_status"];             
			
			$param_1['OpportunityOwner'] = $user_id;
			
			$param_1['modified_by'] = $user_id;
			$param_1['modified_date_time'] = date("Y-m-d H:i:s");	

			// Update Opportunity DB
			$ok = $this->Generic_model->updateData("opportunities",$param_1, array('opportunity_id' => $opportunity_id));	
			
			if($ok > 0) { 
				
				// inserting a log
				$latest_val['module_id'] = $opportunity_id;
				$latest_val['module_name'] = "Opportunitie";
				$latest_val['user_id'] = $user_id;
				$latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
				
				// Creating & mapping brand products
				$Brands_Product = $param['brands_product'];
				
				// Deleting existing brand products 
				$res = $this->Generic_model->deleteRecord("Products_Brands_targeted_opp",array("Opportunity" => $opportunity_id));
				
				// Insert all brand products
				for($a=0; $a<count($Brands_Product); $a++){		
					if($Brands_Product[$a]['product'] != "" || $Brands_Product[$a]['product'] != NULL){
						$param_a['Opportunity'] = $opportunity_id;
						$param_a['Brands_Product'] = $Brands_Product[$a]['product'];
						$param_a['Brands_Units'] = $Brands_Product[$a]['units'];
						$param_a['Brands_Quantity'] = $Brands_Product[$a]['quantity'];
						$param_a['Brands_Price'] = $Brands_Product[$a]['price'];
						
						$ok2 = $this->Generic_model->insertData("Products_Brands_targeted_opp",$param_a);
					}
				}
				
				// Creating & mapping associate contacts
				$associate_contact = $param['associate_contact'];	
				
				// Deleting existing associative contacts 
				$res = $this->Generic_model->deleteRecord("opportunity_associate_contacts",array("Opportunity" => $opportunity_id));
				
				// insert new associative contacts
				if(count($associate_contact) >0){
					for($ia=0;$ia<count($associate_contact);$ia++){
						$param_21['Opportunity'] = $opportunity_id;
						$param_21['contact_id'] = $associate_contact[$ia]['contact_id'];
						$param_21['designation'] = $associate_contact[$ia]['designation'];
						$param_21['user_id'] = $user_id;
						$param_21['created_by'] = $user_id;
						$param_21['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("opportunity_associate_contacts",$param_21);
					}
				}
				
				// Creating & mapping competitor product
				$Competition_Product = $param['competition_product'];
				
				// Deleting existing competitor product 
				$res = $this->Generic_model->deleteRecord("Competition_targeted_opp",array("Opportunity" => $opportunity_id));
				
				// insert new competitor products
				for($m=0; $m<count($Competition_Product); $m++){
					if($Competition_Product[$m]['product'] != "" || $Competition_Product[$m]['product'] != NULL){
						$param_m['Opportunity'] = $opportunity_id;
						$param_m['Competition_Product'] = $Competition_Product[$m]['product'];						
						$param_m['Competition_Units'] = $Competition_Product[$m]['units'];						
						$param_m['Competition_Price'] = $Competition_Product[$m]['price'];
						
						$ok3 = $this->Generic_model->insertData("Competition_targeted_opp",$param_m);
					}
				}
				
				// Creating Remarks
				$remarks = $param['opp_remarks'];
				
				// Deleting existing remarks
				$res = $this->Generic_model->deleteRecord("opportunities_remarks",array("opportunity_id" => $opportunity_id));
				
				if(count($remarks) > 0){
					foreach($remarks as $remark){	
						$opp_remark['opportunity_id'] = $opportunity_id;
						$opp_remark['remark'] = $remark['remark'];
						$opp_remark['remark_date'] = $remark['remark_date'];
						
						// Insert remark
						$this->Generic_model->insertData("opportunities_remarks",$opp_remark);  
						
					}
				}
				
				// Creating & mapping final product
				$Final_product = $param['final_product'];	
				
				// Deleting existing Final Products
				$res = $this->Generic_model->deleteRecord("product_opportunities",array("Opportunity" => $opportunity_id));
				
				// insert new final products
				if($Final_product[0]['product_id'] != "" || $Final_product[0]['product_id'] != NULL){
					$ok = 0;
					for($j=0;$j<count($Final_product);$j++){						
						$param_2['Opportunity'] = $opportunity_id;
						$param_2['Product'] = $Final_product[$j]['product_id'];
						$param_2['Quantity'] = $Final_product[$j]['quantity'];
						$param_2['final_product_price'] = $Final_product[$j]['rate_per_sft'];
						$param_2['final_product_value'] = $Final_product[$j]['value'];
						$param_2['Probability'] = $Final_product[$j]['probability'];
						$param_2['schedule_date_from'] = date("Y-m-d",strtotime($Final_product[$j]['schedule_date_from'])); // This is treated as the required date
						$param_2['created_by'] =$user_id;
						$param_2['modified_by'] =$user_id;
						$param_2['created_date_time'] =date("Y-m-d H:i:s");
						$param_2['modified_date_time'] =date("Y-m-d H:i:s");
						
						$ok = $this->Generic_model->insertData("product_opportunities",$param_2);
					}				
					
					/*
					if($ok == 1){
						$opp_product_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity = '".$opportunity_id ."'  group by b.product_code")->result();
						$email = $user_list->email;
						$to = $email;  //$to      = $dept_email_id;
						$subject = "New Opportunitie created";
						$data['name'] = $user_list->name;

						$data['message'] = "<p> A new Opportunitie has been created successfully <br/><br/><b>  <b>CustomerName </b> : ". $param_1['Company_text'].",<br/><br/>
						<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
						<thead>
						<tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
                        </tr></thead>";

						if(count($opp_product_list) >0){
							foreach($opp_product_list as $opp_values){
								$data['message'].=  "<tbody><tr>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
								<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
							}
                        }

                        $data['message'].= "</table><br/>";  
						$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
                     
						if(count($user_report_to) >0){
							$email = $user_report_to->email;
							$to = $email;
							$subject = "New Opportunitie created";
							$data['name'] = $user_report_to->name;
							$data['message'] = "<p> A new Opportunitie has been created successfully By <b>".$user_list->name."</b> <br/><br/><b> Rating </b> : ".$param['Rating']."<br/> <b>CustomerName </b> : ".$param_1['Company_text'].", <br/><b>Email</b> : ".$param['Email']." ,<br/> <b>MobileNumber</b> : ".$param['Mobile']." <br/><br/><br/>
							<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
							<thead>
							<tr >
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Probulity(%)</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule from Date</th>
							<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>schedule Upto Date</th>
							</tr></thead>";

							if(count($opp_product_list) >0){
								foreach($opp_product_list as $opp_values){
									$data['message'].=  "<tbody><tr>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->product_name."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Quantity."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$opp_values->Probability."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_from))."</td>
									<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".date('d-m-Y',strtotime($opp_values->schedule_date_upto))."</td></tr></tbody>";
								}
							}
							$data['message'].= "</table><br/>";  
						}
					}*/
				}
				
				$return_data = $this->all_tables_records_view("opportunitie",$opportunity_id);				
				$this->response(array('code'=>'200','message'=>'opportunity created successfully', 'result'=>$return_data,'requestname'=>$method));
            }else{
				$this->response(array('code'=>'404','message' => 'Opportunity Insert Failed'), 200);
            }
		}
	}
		
	
	
  public function opportunities_delete($parameters,$method,$user_id){
    $opportunity_id = $parameters['opportunity_id'];
    if($opportunity_id != "" || $opportunity_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
        $result=$this->Generic_model->updateData('opportunities',$param,array('opportunity_id'=>$opportunity_id));
        if($result ==1){

          $latest_val['user_id'] = $user_id;
          $latest_val['delete_status'] = "1";
          $latest_val['created_date_time'] = date("Y-m-d H:i:s");
          $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $opportunity_id,'module_name'=>'Opportunitie'));

          $param_1['archieve'] = "1";
          $param_1['modified_by'] = $user_id;
          $param_1['modified_date_time'] = date("Y-m-d H:i:s");
          $ok = $this->Generic_model->updateData('product_opportunities',$param_1,array('Opportunity'=>$opportunity_id));
          if($ok == 1){
          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
          }else{
            $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
          }
       }else{
         $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication1 Failed'), 200);
    }
  }
  
	/**
	* Customer Creation
	* 
	*/
	function customer_insert($parameters,$method,$user_id) {
		
		$param = $parameters;
		
		
		if(count($param) >0){  			
			unset($param['users_details']);
			unset($param['price_list']);	
			
			if($param['CustomerType'] == 'Direct Customer'){
				$param_c['CustomerName']  = $param['CustomerName'];
				$param_c['CustomerSAPCode']  = $param['CustomerSAPCode'];				
				$param_c['Description']  = $param['Description'];
				$param_c['Phone']  = $param['Phone'];
				$param_c['Website']  = $param['Website'];
				$param_c['AccountSource']  = $param['AccountSource'];
				$param_c['AnnualRevenue']  = $param['AnnualRevenue'];
				$param_c['GSTINNumber']  = $param['GSTINNumber'];
				$param_c['Employees']  = $param['Employees'];       
				$param_c['PaymentTerms']  = $param['PaymentTerms'];
				$param_c['pancard']  = $param['pancard'];
				$param_c['BillingStreet1']  = $param['BillingStreet1'];
				$param_c['Billingstreet2']  = $param['BillingStreet2'];
				$param_c['BillingCountry']  = $param['BillingCountry'];
				$param_c['StateProvince']  = $param['StateProvince'];
				$param_c['BillingCity']  = $param['BillingCity'];
				$param_c['BillingZipPostal']  = $param['BillingZipPostal'];
				$param_c['ShippingStreet1']  = $param['ShippingStreet1'];
				$param_c['Shippingstreet2']  = $param['Shippingstreet2'];
				$param_c['ShippingCountry']  = $param['ShippingCountry'];
				$param_c['ShippingStateProvince']  = $param['ShippingStateProvince'];
				$param_c['ShippingCity']  = $param['ShippingCity'];
				$param_c['ShippingZipPostal']  = $param['ShippingZipPostal'];
				$param_c['SalesOrganisation']  = $param['SalesOrganisation'];
				$param_c['DistributionChannel']  = $param['DistributionChannel'];
				$param_c['Division']  = $param['Division'];		  
				$param_c['CustomerType'] = $param['CustomerType'];
				$param_c['CustomerContactName'] = $param['CustomerContactName'];
				$param_c['Email'] = $param['Email'];
				$param_c['CustomerCategory'] = $param['CustomerCategory'];
				$param_c['CreditLimit'] = $param['CreditLimit'];
				$param_c['SecurityInstruments'] = $param['SecurityInstruments'];
				$param_c['Pdc_Check_number'] = $param['Pdc_Check_number'];
				$param_c['Bank'] = $param['Bank'];
				$param_c['Bank_guarntee_amount_Rs'] = $param['Bank_guarntee_amount_Rs'];
				$param_c['LC_amount_Rs'] = $param['LC_amount_Rs'];
				$param_c['IncoTerms1']  = $param['IncoTerms1'];
				$param_c['IncoTerms2']  = $param['IncoTerms2'];
				$param_c['Fax']  = $param['Fax'];
				$param_c['Industry']  = $param['Industry'];
				$param_c['remarks'] = $param['remarks'];	
				$param_c['approve_status'] = '0';
			}else if($param['CustomerType'] == 'Third party Customer'){
				$param_c['CustomerName']  = $param['CustomerName'];
				$param_c['CustomerType'] = $param['CustomerType'];
				$param_c['Phone']  = $param['Phone'];
				$param_c['CustomerContactName']  = $param['CustomerContactName'];
				$param_c['contact_id']  = $param['contact_id'];
				$param_c['Customer_location']  = $param['Customer_location'];
				$param_c['pancard']  = $param['pancard'];
				$param_c['GSTINNumber']  = $param['GSTINNumber'];
				$param_c['CustomerCategory'] = $param['CustomerCategory'];
				$param_c['Division']  = $param['Division'];
				$param_c['BillingStreet1']  = $param['BillingStreet1'];  
			}
			
			$param_c['CustomerOwner'] = $user_id;				
			$param_c['created_by'] = $user_id;
			$param_c['modified_by'] = $user_id;
			$param_c['created_date_time'] = date("Y-m-d H:i:s");
			$param_c['modified_date_time'] = date("Y-m-d H:i:s");
			
			// Get user's manager id
			$userInfo = $this->Generic_model->getSingleRecord('users',array('user_id'=>$user_id));
			if(count($userInfo) > 0){
				$param_c['manager_user_id'] = $userInfo->manager;
			}else{
				$param_c['manager_user_id'] = '0';
			}
			
			$customer_id = $this->Generic_model->insertDataReturnId("customers",$param_c);	
			
			// Generate the customer number
			$number = str_pad($customer_id,6,'0',STR_PAD_LEFT);                
			if($param_c['CustomerType'] == 'Direct Customer'){
				$prefix = 'DC';
			}elseif($param_c['CustomerType'] == 'Third party Customer'){
				$prefix = 'TC';
			}
			$cs_id= $prefix."-".$number;				
			$this->db->query("update customers set customer_number='".$cs_id."' where customer_id='".$customer_id."'");
			
			// Tag price list of the user to the customer in customer_price_list
			$plInfo['price_list_id'] = $userInfo->Product_price_master_id;
			$plInfo['customer_id'] = $customer_id;
			$plInfo['status'] = 'Active';
			$plInfo['created_by'] = $user_id;
			$plInfo['modified_by'] = $user_id;
			$plInfo['created_date_time'] = date("Y-m-d H:i:s");
			$plInfo['modified_date_time'] = date("Y-m-d H:i:s");			
			
			$customer_price_list_id = $this->Generic_model->insertDataReturnId("customer_price_list",$plInfo);			
			
			if($customer_id != "" || $customer_id != NULL){
				$contact_id=$param['contact_id'];
				$opportunity_id=$param['opportunity_id'];

				if(!empty($contact_id)){
					$this->Generic_model->updateData('contacts', array('Company'=>$customer_id,'Company_text'=>$param['CustomerName']), array('contact_id' => $contact_id));
				}
				if(!empty($opportunity_id)){
					$this->Generic_model->updateData('opportunities', array('Company'=>$customer_id,'Company_text'=>$param['CustomerName'],'is_opportunity_converted_customer'=>1), array('opportunity_id' => $opportunity_id));
				}

				// Sold to party details
				$soldParam = $param['sold_to_party'];
			
				if($soldParam[0]['title'] != "" || $soldParam[0]['title'] != NULL){
					for($js=0; $js<count($soldParam); $js++){
						$param_add['title'] = $soldParam[$js]['title'];
						$param_add['street'] = $soldParam[$js]['street'];
						$param_add['city'] = $soldParam[$js]['city'];
						$param_add['state'] = $soldParam[$js]['state'];
						$param_add['country'] = $soldParam[$js]['country'];
						$param_add['pin_code'] = $soldParam[$js]['pin_code'];
						$param_add['type'] = 'Sold';
						$param_add['customer_id'] = $customer_id;
						$param_add['created_by'] = $user_id;
						$param_add['modified_by'] = $user_id;
						$param_add['created_date_time'] = date("Y-m-d H:i:s");
						$param_add['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add);
					}
				}
				
				// Bill to Party details
				$billParam = $param['bill_to_party'];

				if($billParam[0]['title'] != "" || $billParam[0]['title'] != NULL){
					for($jb=0;$jb<count($billParam); $jb++){
						$param_add_1['title'] = $billParam[$jb]['title'];
						$param_add_1['street'] = $billParam[$jb]['street'];
						$param_add_1['city'] = $billParam[$jb]['city'];
						$param_add_1['state'] = $billParam[$jb]['state'];
						$param_add_1['country'] = $billParam[$jb]['country'];
						$param_add_1['pin_code'] = $billParam[$jb]['pin_code'];
						$param_add_1['type'] = 'Bill';
						$param_add_1['customer_id'] = $customer_id;
						$param_add_1['created_by'] =  $user_id;
						$param_add_1['modified_by'] = $user_id;
						$param_add_1['created_date_time'] = date("Y-m-d H:i:s");
						$param_add_1['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_1);
					}
				}

				// Ship to Party details
				$shipParam = $param['ship_to_party'];
				
				if($shipParam[0]['title'] != "" || $shipParam[0]['title'] != NULL){
					for($jsh=0;$jsh<count($shipParam);$jsh++){
						$param_add_2['title'] = $shipParam[$jsh]['title'];
						$param_add_2['street'] = $shipParam[$jsh]['street'];
						$param_add_2['city'] = $shipParam[$jsh]['city'];
						$param_add_2['state'] = $shipParam[$jsh]['state'];
						$param_add_2['country'] = $shipParam[$jsh]['country'];
						$param_add_2['pin_code'] = $shipParam[$jsh]['pin_code'];
						$param_add_2['type'] = 'Ship';
						$param_add_2['customer_id'] = $customer_id;
						$param_add_2['created_by'] =  $user_id;
						$param_add_2['modified_by'] = $user_id;
						$param_add_2['created_date_time'] = date("Y-m-d H:i:s");
						$param_add_2['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("customer_address_sold_bill_ship",$param_add_2);
					}
				}

				$record_val = $this->Generic_model->getAllRecords("customers",array("customer_id"=>$customer_id));
				//$this->createXMLfile($record_val);

				if($param['price_list'] != "" || $param['price_list'] != null){
					$param_1['price_list_id']  = $param['price_list'];
					$param_1['customer_id'] = $customer_id;
					$param_1['status'] = "Active";
					$param_1['created_by'] = $user_id;
					$param_1['modified_by'] = $user_id;
					$param_1['created_date_time'] = date("Y-m-d H:i:s");
					$param_1['modified_date_time'] = date("Y-m-d H:i:s");
					$ok1 = $this->Generic_model->insertData("customer_price_list",$param_1);
				}
				
				$users_details = $param['users_details'];
				
				for($i=0; $i<count($users_details); $i++){
					if($user_id != $users_details[$i]){
						$param_2['user_id'] = $users_details[$i];
						$param_2['customer_id'] = $customer_id;
						$param_2['created_by'] = $user_id;
						$param_2['modified_by'] = $user_id;
						$param_2['created_date_time'] = date("Y-m-d H:i:s");
						$param_2['modified_date_time'] = date("Y-m-d H:i:s");
						$ok = $this->Generic_model->insertData("customer_users_maping",$param_2);

						$latest_val['module_id'] = $customer_id;
						$latest_val['module_name'] = "Customer";
						$latest_val['user_id'] = $users_details[$i];
						$latest_val['created_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("update_table",$latest_val);
					}
				}

				// Map logged in user to the customer
				$param_2['user_id'] = $user_id;
				$param_2['customer_id'] = $customer_id;
				$param_2['created_by'] = $user_id;
				$param_2['modified_by'] = $user_id;
				$param_2['created_date_time'] = date("Y-m-d H:i:s");
				$param_2['modified_date_time'] = date("Y-m-d H:i:s");
				$ok = $this->Generic_model->insertData("customer_users_maping",$param_2);

				$latest_val['module_id'] = $customer_id;
				$latest_val['module_name'] = "Customer";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);

				if($ok == 1){
					$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
					$email = $user_list->email;
					$to = $email;  
					$subject = "New Customer created";
					$data['name'] = $user_list->name;
					$data['message'] = "<p> A new Customer has been created successfully <br/><br/><b> CustomerName </b> : ".$param['CustomerName']." <br/><b>Website</b> : ".$param['Website']." ,<br/> <b>MobileNumber</b> : ".$param['Phone']."  <br/><b>AnnualRevenue </b> : ".$param['AnnualRevenue']."  <br/><b>Shipping Address :</b>".$param['ShippingStreet1']." ".$param['Shippingstreet2']." ".$param['ShippingCity']." ".$param['ShippingStateProvince']." ".$param['ShippingCountry']." ". $param['ShippingZipPostal']."<br/><b>Billing Address</b>".$param['BillingStreet1']." ".$param['Billingstreet2']." ".$param['BillingCity']." ".$param['StateProvince']." ".$param['BillingCountry']." ". $param['BillingZipPostal']."</p> ";  
					
					$message = $message;
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

					$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
					if(count($user_report_to) >0){
						$email = $user_report_to->email;
						$to = $email;  						
						$subject = "New Customer created";
						$data['name'] = $user_report_to->name; 

						$data['message'] = "<p> A new Customer has been created successfully By <b>".$user_list->name."</b> <br/><br/<b> CustomerName </b> : ".$param['CustomerName']." <br/><b>Website</b> : ".$param['Website']." ,<br/> <b>MobileNumber</b> : ".$param['Phone']."  <br/><b>AnnualRevenue </b> : ".$param['AnnualRevenue']."  <br/><b>Shipping Address :</b>".$param['ShippingStreet1']." ".$param['Shippingstreet2']." ".$param['ShippingCity']." ".$param['ShippingStateProvince']." ".$param['ShippingCountry']." ". $param['ShippingZipPostal']."<br/><b>Billing Address</b>".$param['BillingStreet1']." ".$param['Billingstreet2']." ".$param['BillingCity']." ".$param['StateProvince']." ".$param['BillingCountry']." ". $param['BillingZipPostal']."</p> "; 
						$message = $message;
						$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
					}

					$param_noti['notiffication_type'] = "Customer";
					$param_noti['notiffication_type_id'] = $customer_id;
					$param_noti['user_id'] = $user_id;
					$param_noti['subject'] = "A new Customer has been created successfully  CustomerName : ".$param['CustomerName']." ,Website  : ".$param['Website']." ,MobileNumber : ".$param['Phone']." , AnnualRevenue  : ".$param['AnnualRevenue'].", Shipping Address :".$param['ShippingStreet1']." ".$param['Shippingstreet2']." ".$param['ShippingCity']." ".$param['ShippingStateProvince']." ".$param['ShippingCountry']." ". $param['ShippingZipPostal']." ,Billing Address ".$param['BillingStreet1']." ".$param['Billingstreet2']." ".$param['BillingCity']." ".$param['StateProvince']." ".$param['BillingCountry']." ". $param['BillingZipPostal']."";
					$this->Generic_model->insertData("notiffication",$param_noti);

					if(count($user_list)>0){
						$push_noti['fcmId_android'] = $user_list->fcmId_android;
						$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
					}else{
						$push_noti['fcmId_android'] ="";
						$push_noti['fcmId_iOS'] = "";   
					}
					
					if(count($user_report_to) >0){
						$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
						$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
					}else{
						$push_noti['fcmId_android_report_to'] = "";
						$push_noti['fcmId_iOS_report_to'] = "";
					}
					$push_noti['customer_id'] = $customer_id;
					$push_noti['user_id'] = $user_id;
					$push_noti['subject'] = " A new Customer has been created successfully  CustomerName : ".$param['CustomerName']." ,Website  : ".$param['Website']." ,MobileNumber : ".$param['Phone']." , AnnualRevenue  : ".$param['AnnualRevenue'].", Shipping Address :".$param['ShippingStreet1']." ".$param['Shippingstreet2']." ".$param['ShippingCity']." ".$param['ShippingStateProvince']." ".$param['ShippingCountry']." ". $param['ShippingZipPostal']." ,Billing Address ".$param['BillingStreet1']." ".$param['Billingstreet2']." ".$param['BillingCity']." ".$param['StateProvince']." ".$param['BillingCountry']." ". $param['BillingZipPostal']."";
					$this->PushNotifications->customer_notifications($push_noti);
					
					$return_data = $this->all_tables_records_view("Customer",$customer_id);
					$this->response(array('code'=>'200','message'=>'Customer inserted successfully', 'result'=>$return_data,'requestname'=>$method));
				}else{
					$this->response(array('code'=>'404','message' => 'Could not save Customer, Result failed!'), 200);
				}
			}else{
				$this->response(array('code'=>'404','message' => 'Could not save Customer, Result failed!'), 200);
			}
		}	      
	}
	
	/**
	* Function customer_approval works on Customer approval/Rejection by the customer's created User's Manager
	* Updates table customers with suitable values fromt the following params
	* @customer_id
	* @manager_user_id
	* @approve_status (1 - Accept / 2- Reject)
	* @approval_comments
	*/
	public function customer_approval($parameters,$method,$user_id){
		
		$statusData = $parameters;
		$manager_user_id = $user_id;
		$customer_id = $statusData['customer_id'];
		
		// Remove unwanted params
		unset($statusData['customer_id']);
		
		// Add required params
		$statusData['modified_by'] = $user_id;
		$statusData['modified_date_time'] = date("Y-m-d H:i:s");
		
		// Update Customer record with the relevant values
		$ok = $this->Generic_model->updateData('customers',$statusData,array('customer_id' => $customer_id, 'manager_user_id' => $manager_user_id));
		
		if($ok){
			$return_data = $this->all_tables_records_view("Customer",$customer_id);
			if($parameters['approve_status'] == 1){
				$msg = 'Customer Approved Successfully';
			}else if($parameters['approve_status'] == 2){
				$msg = 'Customer Rejected Successfully';
			}
			$this->response(array('code'=>'200','message'=>$msg, 'result'=>$return_data, 'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Could not process Customer approval, Result failed!'), 200);
		}
	}
 
 
	 public function complaint_insert($parameters,$method,$user_id){
		$checking_id = $this->db->query("select * from Complaints order by Complaint_id DESC")->row();
		
		if($checking_id->ComplaintNumber == NULL || $checking_id->ComplaintNumber == ""){
			$ComplaintNumber_id = "CO-00001";
		}else{
			$opp_check = trim($checking_id->ComplaintNumber);
			$checking_op_id =  substr($opp_check, 3);
			if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
				$opp_id_last_inc = (++$checking_op_id);
				$ComplaintNumber_id= "CO-".$opp_id_last_inc;
			}else{
				$ComplaintNumber_id = (++$opp_check);
			} 
		}
		$profile_id = $parameters['profile_id'];
		$data['CustomerName']=$parameters['customer_id'];
		$data['salesorderdate']=date("Y-m-d H:i:s", strtotime($parameters['salesorderdate'])); 
		$data['salesordernumber']=$parameters['salesordernumber'];
		$data['ComplaintNumber'] = $ComplaintNumber_id;
		$data['feedback']=$parameters['feedback'];
		$data['applicationdate']=date("Y-m-d H:i:s", strtotime($parameters['applicationdate']));
		$data['feedbackother']=$parameters['feedbackother'];
		$data['ComplaintOwner'] = $user_id;
		$data['invoicedate']=date("Y-m-d H:i:s", strtotime($parameters['invoicedate']));
		$data['invoicenumber']=$parameters['invoicenumber'];
		$data['batchnumber']=$parameters['batchnumber'];
		$data['defectivesample']=$parameters['defectivesample'];
		$data['sampleplantlab']=$parameters['sampleplantlab'];
		$data['sales_sitevisit']=$parameters['sales_sitevisit'];

		//$data['sales_recommendedsolution']=$parameters['sales_recommendedsolution'];
		$data['sales_status']=$parameters['sales_status'];
		$data['area_sitevisit']=$parameters['area_sitevisit'];
		//$data['area_assessment']=$parameters['area_assessment'];
		//$data['area_recommendedsolution']=$parameters['area_recommendedsolution'];
		$data['area_status']=$parameters['area_status'];
		$data['regional_sitevisit']=$parameters['regional_sitevisit'];
		//$data['regional_assessment']=$parameters['regional_assessment'];
		//$data['regional_recommendedsolution']=$parameters['regional_recommendedsolution'];
		$data['regional_status']=$parameters['regional_status'];
		$data['national_sitevisit']=$parameters['national_sitevisit'];
		//$data['national_assessment']=$parameters['national_assessment'];
		//$data['national_recommendedsolution']=$parameters['national_recommendedsolution'];
		

		//exit;
		$data['national_status']=$parameters['national_status'];
		$data['credit_note_given']=$parameters['credit_note_given'];
		$data['material_replaced']=$parameters['material_replaced'];
		$data['comercial_remarks']=$parameters['comercial_remarks'];
		$data['qualitytestsdone']=$parameters['qualitytestsdone'];

		//$data['qualityassessment']=$parameters['qualityassessment'];
		//$data['qualityrecommendation']=$parameters['qualityrecommendation'];
		
		
		//$data['manufacturingassessment']=$parameters['manufacturingassessment'];
		//$data['managementassessment']=$parameters['managementassessment'];
		//$data['managementRecommendation']=$parameters['managementRecommendation'];
		//$data['Type']=$parameters['Type'];
		$data['created_by']=$user_id;
		$data['modified_by']=$user_id;
		$data['created_date_time']=date("Y-m-d H:i:s");
		$data['modified_date_time']=date("Y-m-d H:i:s");
		$result=$this->Generic_model->insertDataReturnId('Complaints',$data);
		
		$Complaint_id = $result;
		
		/*
		// check if the complaints is being raised by a sales call
		if(isset($parameters['insert_by'])){
			
			// Check if the record exists with the compaint id
			$chkCount = $this->Generic_model->getNumberOfRecords('sales_call_temp_table',array('Complaint_id' => $Complaint_id));
			
			if($chkCount == 0){
				// Insert a complaints record id in the sales_call_temp_table DB
				if($parameters['insert_by'] == "Sales Call"){					
					$tempData['sales_call_id'] = 0;
					$tempData['call_report_type'] = "Complaint Discussion";
					$tempData['Complaint_id'] = $Complaint_id;
					$tempData['created_by'] = $user_id;
					$tempData['modified_by']=$user_id;
					$tempData['created_datetime']=date("Y-m-d H:i:s");
					$tempData['modified_datetime']=date("Y-m-d H:i:s");
					$result=$this->Generic_model->insertDataReturnId('sales_call_temp_table',$tempData);
				}
			}
			
			
		}
		*/		
		
		// Check if the Sales Order is being raised by a Sales Call
		if(isset($fdata->insert_by)){
			
			if($fdata->insert_by == 'Sales Call'){
				
				$tempData = array();
				$new = 0;
				
				// Check if the sales_call_temp_id exists
				if(isset($fdata->sales_calls_temp_id)){
					if($fdata->sales_calls_temp_id != '' || $fdata->sales_calls_temp_id != NULL){
						// Update sales calls temp table with Complaint_id for the respective sales call temp id					
						$tempData['Complaint_id'] = $Complaint_id;
						$tempData['modified_by']=$user_id;					
						$tempData['modified_datetime']=date("Y-m-d H:i:s");
						$this->Generic_model->updateData('sales_call_temp_table', $tempData, array('sales_calls_temp_id' => $fdata->sales_calls_temp_id));
					}else{
						$new = 1;
					}						
				}else{
					$new = 1;					
				}
				
				if($new == 1){
					// Insert a payment collection record id in the sales_call_temp_table DB										
					$tempData['sales_call_id'] = 0;
					$tempData['Complaint_id'] = $Complaint_id;
					$tempData['created_by'] = $user_id;
					$tempData['modified_by']=$user_id;
					$tempData['created_datetime']=date("Y-m-d H:i:s");
					$tempData['modified_datetime']=date("Y-m-d H:i:s");
					$result=$this->Generic_model->insertDataReturnId('sales_call_temp_table',$tempData);
				}	
			}
		}

		if($result != "" || $result != null){
			if($profile_id == SALESOFFICER || $profile_id == SalesExecutive){
				$sales_assessment=$parameters['sales_assessment'];
				$sales_recommendedsolution=$parameters['sales_recommendedsolution'];
				if(count($sales_assessment) >0){
					for($isa=0;$isa<count($sales_assessment);$isa++){
						$data_isa['Complaint_id'] = $Complaint_id;
						$data_isa['profile_id'] = $profile_id;
						$data_isa['type'] = "assessment";
						$data_isa['complent_recomendation'] =$sales_assessment[$isa]["complaints_name"];
						$data_isa['profile_name_type'] = "SALESOFFICER";
						$data_isa['created_by'] = $user_id;
						$data_isa['modified_by'] =$user_id;
						$data_isa['created_date_time'] = date("Y-m-d H:i:s");
						$data_isa['modified_date_time'] = date("Y-m-d H:i:s");
						 //print_r($data_isa);
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_isa);
					}
				}
				if(count($sales_recommendedsolution) >0){
					for($isr=0;$isr<count($sales_recommendedsolution);$isr++){
						$data_irs['Complaint_id'] = $Complaint_id;
						$data_irs['profile_id'] = $profile_id;
						$data_irs['type'] = "recommendation";
						$data_irs['complent_recomendation'] =$sales_recommendedsolution[$isr]["complaints_name"];
						$data_irs['profile_name_type'] = "SALESOFFICER";
						$data_irs['created_by'] = $user_id;
						$data_irs['modified_by'] =$user_id;
						$data_irs['created_date_time'] = date("Y-m-d H:i:s");
						$data_irs['modified_date_time'] = date("Y-m-d H:i:s");
					   // print_r($data_irs);
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irs);
					}
				}
			}

			if($profile_id == AreaManager){
				$area_assessment=$parameters['area_assessment'];
				$area_recommendedsolution=$parameters['area_recommendedsolution'];
				/* area assessment and area recomendation saving Stateing poing */
				if(count($area_assessment) >0){
					for($iaa=0;$iaa<count($area_assessment);$iaa++){
						$data_iaa['Complaint_id'] = $Complaint_id;
						$data_iaa['profile_id'] = $profile_id;
						$data_iaa['type'] = "assessment";
						$data_iaa['complent_recomendation'] =$area_assessment[$iaa]['complaints_name'];
						$data_iaa['profile_name_type'] = "AreaManager";
						$data_iaa['created_by'] = $user_id;
						$data_iaa['modified_by'] =$user_id;
						$data_iaa['created_date_time'] = date("Y-m-d H:i:s");
						$data_iaa['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iaa);
					}
				}
				if(count($area_recommendedsolution) >0){
					for($iar=0;$iar<count($area_recommendedsolution);$iar++){
						$data_irar['Complaint_id'] = $Complaint_id;
						$data_irar['profile_id'] = $profile_id;
						$data_irar['type'] = "recommendation";
						$data_irar['complent_recomendation'] =$area_recommendedsolution[$iar]['complaints_name'];
						$data_irar['profile_name_type'] = "AreaManager";
						$data_irar['created_by'] = $user_id;
						$data_irar['modified_by'] =$user_id;
						$data_irar['created_date_time'] = date("Y-m-d H:i:s");
						$data_irar['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irar);
					}
				}
			}
			if($profile_id == Regionalmanager){
				$regional_assessment=$parameters['regional_assessment'];
				$regional_recommendedsolution=$parameters['regional_recommendedsolution'];
				if(count($regional_assessment) >0){
					for($ira=0;$ira<count($regional_assessment);$ira++){
						$data_ira['Complaint_id'] = $Complaint_id;
						$data_ira['profile_id'] = $profile_id;
						$data_ira['type'] = "assessment";
						$data_ira['complent_recomendation'] =$regional_assessment[$ira]['complaints_name'];
						$data_ira['profile_name_type'] = "Regionalmanager";
						$data_ira['created_by'] = $user_id;
						$data_ira['modified_by'] =$user_id;
						$data_ira['created_date_time'] = date("Y-m-d H:i:s");
						$data_ira['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ira);
					}
				}
				if(count($regional_recommendedsolution) >0){
					for($irr=0;$irr<count($regional_recommendedsolution);$irr++){
						$data_irr['Complaint_id'] = $Complaint_id;
						$data_irr['profile_id'] = $profile_id;
						$data_irr['type'] = "recommendation";
						$data_irr['complent_recomendation'] =$regional_recommendedsolution[$irr]['complaints_name'];
						$data_irr['profile_name_type'] = "Regionalmanager";
						$data_irr['created_by'] = $user_id;
						$data_irr['modified_by'] =$user_id;
						$data_irr['created_date_time'] = date("Y-m-d H:i:s");
						$data_irr['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irr);
					}
				}
			}

			if($profile_id == NationalHead){
				$national_assessment=$parameters['national_assessment'];
				$national_recommendedsolution=$parameters['national_recommendedsolution'];
				if(count($national_assessment) >0){
					for($ina=0;$ina<count($national_assessment);$ina++){
						$data_ina['Complaint_id'] = $Complaint_id;
						$data_ina['profile_id'] = $profile_id;
						$data_ina['type'] = "assessment";
						$data_ina['complent_recomendation'] =$national_assessment[$ina]['complaints_name'];
						$data_ina['profile_name_type'] = "NationalHead";
						$data_ina['created_by'] = $user_id;
						$data_ina['modified_by'] =$user_id;
						$data_ina['created_date_time'] = date("Y-m-d H:i:s");
						$data_ina['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ina);
					}
				}
				if(count($national_recommendedsolution) >0){
					for($inr=0;$inr<count($national_recommendedsolution);$inr++){
						$data_inr['Complaint_id'] = $Complaint_id;
						$data_inr['profile_id'] = $profile_id;
						$data_inr['type'] = "recommendation";
						$data_inr['complent_recomendation'] =$national_recommendedsolution[$inr]['complaints_name'];
						$data_inr['profile_name_type'] = "NationalHead";
						$data_inr['created_by'] = $user_id;
						$data_inr['modified_by'] =$user_id;
						$data_inr['created_date_time'] = date("Y-m-d H:i:s");
						$data_inr['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_inr);
					}
				}
			}

			if($profile_id == QualityDepartment){
				$qualityassessment=$parameters['qualityassessment'];
				$qualityrecommendation=$parameters['qualityrecommendation'];
				if(count($qualityassessment) >0){
					for($iqa=0;$iqa<count($qualityassessment);$iqa++){
						$data_iqa['Complaint_id'] = $Complaint_id;
						$data_iqa['profile_id'] = $profile_id;
						$data_iqa['type'] = "assessment";
						$data_iqa['complent_recomendation'] =$qualityassessment[$iqa]['complaints_name'];
						$data_iqa['profile_name_type'] = "QualityDepartment";
						$data_iqa['created_by'] = $user_id;
						$data_iqa['modified_by'] =$user_id;
						$data_iqa['created_date_time'] = date("Y-m-d H:i:s");
						$data_iqa['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqa);
					}
				  }
				if(count($qualityrecommendation) >0){
					for($iqr=0;$iqr<count($qualityrecommendation);$iqr++){
						$data_iqr['Complaint_id'] = $Complaint_id;
						$data_iqr['profile_id'] = $profile_id;
						$data_iqr['type'] = "recommendation";
						$data_iqr['complent_recomendation'] =$qualityrecommendation[$iqr]['complaints_name'];
						$data_iqr['profile_name_type'] = "QualityDepartment";
						$data_iqr['created_by'] = $user_id;
						$data_iqr['modified_by'] =$user_id;
						$data_iqr['created_date_time'] = date("Y-m-d H:i:s");
						$data_iqr['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqr);
					}
				}
			}
			if($profile_id == Manufacturing){
				$manufacturingassessment=$parameters['manufacturingassessment'];
				if(count($manufacturingassessment) >0){
					for($ima=0;$ima<count($manufacturingassessment);$ima++){
						$data_ima['Complaint_id'] = $Complaint_id;
						$data_ima['profile_id'] = $profile_id;
						$data_ima['type'] = "assessment";
						$data_ima['complent_recomendation'] =$manufacturingassessment[$ima]['complaints_name'];
						$data_ima['profile_name_type'] = "Manufacturing";
						$data_ima['created_by'] = $user_id;
						$data_ima['modified_by'] =$user_id;
						$data_ima['created_date_time'] = date("Y-m-d H:i:s");
						$data_ima['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ima);
					}
				}
			}

			if($profile_id == SUPERADMIN){
				$managementassessment=$parameters['managementassessment'];
				$managementRecommendation=$parameters['managementRecommendation'];
				if(count($managementassessment) >0){
					for($ia=0;$ia<count($managementassessment);$ia++){
						$data_1['Complaint_id'] = $Complaint_id;
						$data_1['profile_id'] = $profile_id;
						$data_1['type'] = "assessment";
						$data_1['complent_recomendation'] =$managementassessment[$ia]['complaints_name'];
						$data_1['profile_name_type'] = "SUPERADMIN";
						$data_1['created_by'] = $user_id;
						$data_1['modified_by'] =$user_id;
						$data_1['created_date_time'] = date("Y-m-d H:i:s");
						$data_1['modified_date_time'] = date("Y-m-d H:i:s");						
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_1);
					}
				}

				if(count($managementRecommendation) >0){
					for($ir=0;$ir<count($managementassessment);$ir++){
						$data_irm['Complaint_id'] = $Complaint_id;
						$data_irm['profile_id'] = $profile_id;
						$data_irm['type'] = "recommendation";
						$data_irm['complent_recomendation'] =$managementRecommendation[$ir]['complaints_name'];
						$data_irm['profile_name_type'] = "SUPERADMIN";
						$data_irm['created_by'] = $user_id;
						$data_irm['modified_by'] =$user_id;
						$data_irm['created_date_time'] = date("Y-m-d H:i:s");
						$data_irm['modified_date_time'] = date("Y-m-d H:i:s");
						$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irm);
						
					}
				}
			}

			$latest_val['module_id'] = $result;
			$latest_val['module_name'] = "Complaints";
			$latest_val['user_id'] = $user_id;
			$latest_val['created_date_time'] = date("Y-m-d H:i:s");			
			$this->Generic_model->insertData("update_table",$latest_val);

		  $data_1['Complaint_id'] = $result;
		  $return_data = $this->all_tables_records_view("Complaints",$result);
			$this->response(array('code'=>'200','result'=>$return_data,'message'=>'Inserted successfully','requestname'=>$method));
		}else{
		  $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}   
	}

/*public function complaint_edit($parameters,$method,$user_id){
   $data['CustomerName']=$parameters['CustomerName'];
        $data['salesorderdate']=date("Y-m-d H:i:s", strtotime($parameters['salesorderdate'])); 
        $data['salesordernumber']=$parameters['salesordernumber'];
        $data['feedback']=$parameters['feedback'];
        $data['applicationdate']=date("Y-m-d H:i:s", strtotime($parameters['applicationdate']));
        $data['feedbackother']=$parameters['feedbackother'];
        $data['invoicedate']=date("Y-m-d H:i:s", strtotime($parameters['invoicedate']));
        $data['invoicenumber']=$parameters['invoicenumber'];
        $data['batchnumber']=$parameters['batchnumber'];
        $data['defectivesample']=$parameters['defectivesample'];
        $data['sampleplantlab']=$parameters['sampleplantlab'];
        $data['sitevisit']=$parameters['sitevisit'];
        $data['otherassessment']=$parameters['otherassessment'];
        $data['otherrecommendedsolution']=$parameters['otherrecommendedsolution'];

         $data['sales_sitevisit']=$parameters['sales_sitevisit'];
        $data['sales_assessment']=$parameters['sales_assessment'];
        $data['sales_recommendedsolution']=$parameters['sales_recommendedsolution'];
        $data['sales_status']=$parameters['sales_status'];
        $data['area_sitevisit']=$parameters['area_sitevisit'];
        $data['area_assessment']=$parameters['area_assessment'];
        $data['area_recommendedsolution']=$parameters['area_recommendedsolution'];
        $data['area_status']=$parameters['area_status'];
        $data['regional_sitevisit']=$parameters['regional_sitevisit'];
        $data['regional_assessment']=$parameters['regional_assessment'];
        $data['regional_recommendedsolution']=$parameters['regional_recommendedsolution'];
        $data['regional_status']=$parameters['regional_status'];
        $data['national_sitevisit']=$parameters['national_sitevisit'];
        $data['national_assessment']=$parameters['national_assessment'];
        $data['national_recommendedsolution']=$parameters['national_recommendedsolution'];
        $data['national_status']=$parameters['national_status'];
         $data['credit_note_given']=$parameters['credit_note_given'];
        $data['material_replaced']=$parameters['material_replaced'];
        $data['comercial_remarks']=$parameters['comercial_remarks'];

        $data['qualitytestsdone']=$parameters['qualitytestsdone'];
        $data['qualityassessment']=$parameters['qualityassessment'];
        $data['qualityrecommendation']=$parameters['qualityrecommendation'];
        $data['manufacturingassessment']=$parameters['manufacturingassessment'];
        $data['managementassessment']=$parameters['managementassessment'];
        $data['managementRecommendation']=$parameters['managementRecommendation'];
  $data['modified_by']=$user_id;
  $data['modified_date_time']=date("Y-m-d H:i:s");
  $result=$this->Generic_model->updateData('Complaints',$data, array('Complaint_id'=>$parameters['complaint_id']));

  $return_data = $this->all_tables_records_view("Complaints",$parameters['complaint_id']);
    if($result==1){
       $check_update_list = $this->db->query("select * from update_table where module_id ='".$parameters['complaint_id']."' and module_name ='Complaints'")->row();

        if(count($check_update_list)>0){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['complaint_id'],'module_name'=>'Complaints'));
          }else{
            $latest_val['module_id'] = $parameters['complaint_id'];
            $latest_val['module_name'] = "Complaints";
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $this->Generic_model->insertData("update_table",$latest_val);
          }


    $this->response(array('code'=>'200','message'=>'Updated successfully','result'=>$return_data,'requestname'=>$method));
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
      }   
 }*/
 public function complaint_edit($parameters,$method,$user_id){  
    $profile_id = $parameters['profile_id'];
    $Complaint_id = $parameters['complaint_id'];
  $data['CustomerName']=$parameters['customer_id'];
  $data['salesorderdate']=date("Y-m-d H:i:s", strtotime($parameters['salesorderdate'])); 
  $data['salesordernumber']=$parameters['salesordernumber'];
  $data['ComplaintNumber'] = $ComplaintNumber_id;
  $data['feedback']=$parameters['feedback'];
  $data['applicationdate']=date("Y-m-d H:i:s", strtotime($parameters['applicationdate']));
  $data['feedbackother']=$parameters['feedbackother'];
  $data['ComplaintOwner'] = $user_id;
  $data['invoicedate']=date("Y-m-d H:i:s", strtotime($parameters['invoicedate']));
  $data['invoicenumber']=$parameters['invoicenumber'];
  $data['batchnumber']=$parameters['batchnumber'];
  $data['defectivesample']=$parameters['defectivesample'];
  $data['sampleplantlab']=$parameters['sampleplantlab'];
    $data['sales_sitevisit']=$parameters['sales_sitevisit'];

    //$data['sales_recommendedsolution']=$parameters['sales_recommendedsolution'];
    $data['sales_status']=$parameters['sales_status'];
    $data['area_sitevisit']=$parameters['area_sitevisit'];
    //$data['area_assessment']=$parameters['area_assessment'];
    //$data['area_recommendedsolution']=$parameters['area_recommendedsolution'];
    $data['area_status']=$parameters['area_status'];
    $data['regional_sitevisit']=$parameters['regional_sitevisit'];
    //$data['regional_assessment']=$parameters['regional_assessment'];
    //$data['regional_recommendedsolution']=$parameters['regional_recommendedsolution'];
    $data['regional_status']=$parameters['regional_status'];
  $data['national_sitevisit']=$parameters['national_sitevisit'];
  //$data['national_assessment']=$parameters['national_assessment'];
  //$data['national_recommendedsolution']=$parameters['national_recommendedsolution'];
  

  //exit;
    $data['national_status']=$parameters['national_status'];
    $data['credit_note_given']=$parameters['credit_note_given'];
    $data['material_replaced']=$parameters['material_replaced'];
    $data['comercial_remarks']=$parameters['comercial_remarks'];
  $data['qualitytestsdone']=$parameters['qualitytestsdone'];

  //$data['qualityassessment']=$parameters['qualityassessment'];
  //$data['qualityrecommendation']=$parameters['qualityrecommendation'];
  
  
  //$data['manufacturingassessment']=$parameters['manufacturingassessment'];
  //$data['managementassessment']=$parameters['managementassessment'];
  //$data['managementRecommendation']=$parameters['managementRecommendation'];
  //$data['Type']=$parameters['Type'];
  //$data['created_by']=$user_id;
  $data['modified_by']=$user_id;
  //$data['created_date_time']=date("Y-m-d H:i:s");
  $data['modified_date_time']=date("Y-m-d H:i:s");
  /*print_r($data);
  exit;
  $result=$this->Generic_model->insertDataReturnId('Complaints',$data);*/
  $result=$this->Generic_model->updateData('Complaints',$data, array('Complaint_id'=>$Complaint_id));

   // echo $this->db->last_query();
    /*exit;*/
    //$Complaint_id = $result;
   

  if($result == 1){
    /*echo "hii";
    exit;*/
    if($profile_id == SALESOFFICER || $profile_id == SalesExecutive){
          $sales_assessment=$parameters['sales_assessment'];
          $sales_recommendedsolution=$parameters['sales_recommendedsolution'];
          if(count($sales_assessment) >0){
              for($isa=0;$isa<count($sales_assessment);$isa++){
                $data_isa['Complaint_id'] = $Complaint_id;
                $data_isa['profile_id'] = $profile_id;
                $data_isa['type'] = "assessment";
                $data_isa['complent_recomendation'] =$sales_assessment[$isa]["complaints_name"];
                $data_isa['profile_name_type'] = "SALESOFFICER";
                //$data_isa['created_by'] = $user_id;
                $data_isa['modified_by'] =$user_id;
                //$data_isa['created_date_time'] = date("Y-m-d H:i:s");
                $data_isa['modified_date_time'] = date("Y-m-d H:i:s");
                 //print_r($data_isa);
                $checking_details_sa = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$sales_assessment[$isa]["complaints_name"]."'")->row();
                    if(count($checking_details_sa)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_isa, array('complaints_aeeigment_recommendation_id'=>$checking_details_sa->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_isa);

                    }
                    //echo $this->db->last_query();
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_isa);
            }
            //exit;
          }
          if(count($sales_recommendedsolution) >0){
            for($isr=0;$isr<count($sales_recommendedsolution);$isr++){
                $data_irs['Complaint_id'] = $Complaint_id;
                $data_irs['profile_id'] = $profile_id;
                $data_irs['type'] = "recommendation";
                $data_irs['complent_recomendation'] =$sales_recommendedsolution[$isr]["complaints_name"];
                $data_irs['profile_name_type'] = "SALESOFFICER";
                //$data_irs['created_by'] = $user_id;
                $data_irs['modified_by'] =$user_id;
                //$data_irs['created_date_time'] = date("Y-m-d H:i:s");
                $data_irs['modified_date_time'] = date("Y-m-d H:i:s");
               // print_r($data_irs);
                $checking_details_re_sal = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$sales_recommendedsolution[$isr]["complaints_name"]."'")->row();
                    if(count($checking_details_re_sal)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_irs, array('complaints_aeeigment_recommendation_id'=>$checking_details_re_sal->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irs);

                    }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irs);
            }
          }
      }

      if($profile_id == AreaManager){
          $area_assessment=$parameters['area_assessment'];
          $area_recommendedsolution=$parameters['area_recommendedsolution'];
          /*area  assessment and area recomendation saving
            Stateing poing 
          */
          if(count($area_assessment) >0){
            for($iaa=0;$iaa<count($area_assessment);$iaa++){
                $data_iaa['Complaint_id'] = $Complaint_id;
                $data_iaa['profile_id'] = $profile_id;
                $data_iaa['type'] = "assessment";
                $data_iaa['complent_recomendation'] =$area_assessment[$iaa]['complaints_name'];
                $data_iaa['profile_name_type'] = "AreaManager";
               // $data_iaa['created_by'] = $user_id;
                $data_iaa['modified_by'] =$user_id;
                //$data_iaa['created_date_time'] = date("Y-m-d H:i:s");
                $data_iaa['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_iaa);
                 $checking_details_aa = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$area_assessment[$iaa]['complaints_name']."'")->row();
                    if(count($checking_details_aa)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_iaa, array('complaints_aeeigment_recommendation_id'=>$checking_details_aa->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iaa);

                    }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iaa);
            }
          }
          if(count($area_recommendedsolution) >0){
              for($iar=0;$iar<count($area_recommendedsolution);$iar++){
                $data_irar['Complaint_id'] = $Complaint_id;
                $data_irar['profile_id'] = $profile_id;
                $data_irar['type'] = "recommendation";
                $data_irar['complent_recomendation'] =$area_recommendedsolution[$iar]['complaints_name'];
                $data_irar['profile_name_type'] = "AreaManager";
                //$data_irar['created_by'] = $user_id;
                $data_irar['modified_by'] =$user_id;
                //$data_irar['created_date_time'] = date("Y-m-d H:i:s");
                $data_irar['modified_date_time'] = date("Y-m-d H:i:s");
                //print_R($data_irar);
                $checking_details_ar = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$area_recommendedsolution[$iar]['complaints_name']."'")->row();
                      if(count($checking_details_ar)>0){
                        $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_irar, array('complaints_aeeigment_recommendation_id'=>$checking_details_ar->complaints_aeeigment_recommendation_id));
                      }else{
                        $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irar);

                      }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irar);
            }
          }
      }
      if($profile_id == Regionalmanager){
          $regional_assessment=$parameters['regional_assessment'];
          $regional_recommendedsolution=$parameters['regional_recommendedsolution'];
          if(count($regional_assessment) >0){
            for($ira=0;$ira<count($regional_assessment);$ira++){
                $data_ira['Complaint_id'] = $Complaint_id;
                $data_ira['profile_id'] = $profile_id;
                $data_ira['type'] = "assessment";
                $data_ira['complent_recomendation'] =$regional_assessment[$ira]['complaints_name'];
                $data_ira['profile_name_type'] = "Regionalmanager";
                //$data_ira['created_by'] = $user_id;
                $data_ira['modified_by'] =$user_id;
                //$data_ira['created_date_time'] = date("Y-m-d H:i:s");
                $data_ira['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_ira);
                $checking_details_ra = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$regional_assessment[$ira]['complaints_name']."'")->row();
                    if(count($checking_details_ra)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_ira, array('complaints_aeeigment_recommendation_id'=>$checking_details_ra->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ira);

                    }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ira);
              }
          }
          if(count($regional_recommendedsolution) >0){
              for($irr=0;$irr<count($regional_recommendedsolution);$irr++){
                $data_irr['Complaint_id'] = $Complaint_id;
                $data_irr['profile_id'] = $profile_id;
                $data_irr['type'] = "recommendation";
                $data_irr['complent_recomendation'] =$regional_recommendedsolution[$irr]['complaints_name'];
                $data_irr['profile_name_type'] = "Regionalmanager";
                //$data_irr['created_by'] = $user_id;
                $data_irr['modified_by'] =$user_id;
                //$data_irr['created_date_time'] = date("Y-m-d H:i:s");
                $data_irr['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_irr);
                 $checking_details_rr = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$regional_recommendedsolution[$irr]['complaints_name']."'")->row();
                    if(count($checking_details_rr)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_irr, array('complaints_aeeigment_recommendation_id'=>$checking_details_rr->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irr);

                    }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irr);
              }
          }
      }

      if($profile_id == NationalHead){
          $national_assessment=$parameters['national_assessment'];
          $national_recommendedsolution=$parameters['national_recommendedsolution'];
          if(count($national_assessment) >0){
            for($ina=0;$ina<count($national_assessment);$ina++){
              $data_ina['Complaint_id'] = $Complaint_id;
                $data_ina['profile_id'] = $profile_id;
                $data_ina['type'] = "assessment";
                $data_ina['complent_recomendation'] =$national_assessment[$ina]['complaints_name'];
                $data_ina['profile_name_type'] = "NationalHead";
               // $data_ina['created_by'] = $user_id;
                $data_ina['modified_by'] =$user_id;
               // $data_ina['created_date_time'] = date("Y-m-d H:i:s");
                $data_ina['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_ina);
                 $checking_details_na = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$national_assessment[$ina]['complaints_name']."'")->row();
                    if(count($checking_details_na)>0){
                      $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_ina, array('complaints_aeeigment_recommendation_id'=>$checking_details_na->complaints_aeeigment_recommendation_id));
                    }else{
                      $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ina);

                    }
               // $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ina);
            }
          }
          if(count($national_recommendedsolution) >0){
            for($inr=0;$inr<count($national_recommendedsolution);$inr++){
                $data_inr['Complaint_id'] = $Complaint_id;
                $data_inr['profile_id'] = $profile_id;
                $data_inr['type'] = "recommendation";
                $data_inr['complent_recomendation'] =$national_recommendedsolution[$inr]['complaints_name'];
                $data_inr['profile_name_type'] = "NationalHead";
                //$data_inr['created_by'] = $user_id;
                $data_inr['modified_by'] =$user_id;
                //$data_inr['created_date_time'] = date("Y-m-d H:i:s");
                $data_inr['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_inr);
                $checking_details_nr = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$national_recommendedsolution[$inr]['complaints_name']."'")->row();
                  if(count($checking_details_nr)>0){
                    $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_inr, array('complaints_aeeigment_recommendation_id'=>$checking_details_nr->complaints_aeeigment_recommendation_id));
                  }else{
                    $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_inr);

                  }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_inr);
            }
          }
      }

      if($profile_id == QualityDepartment){
          $qualityassessment=$parameters['qualityassessment'];
          $qualityrecommendation=$parameters['qualityrecommendation'];
        if(count($qualityassessment) >0){
            for($iqa=0;$iqa<count($qualityassessment);$iqa++){
              $data_iqa['Complaint_id'] = $Complaint_id;
              $data_iqa['profile_id'] = $profile_id;
              $data_iqa['type'] = "assessment";
              $data_iqa['complent_recomendation'] =$qualityassessment[$iqa]['complaints_name'];
              $data_iqa['profile_name_type'] = "QualityDepartment";
              //$data_iqa['created_by'] = $user_id;
              $data_iqa['modified_by'] =$user_id;
              //$data_iqa['created_date_time'] = date("Y-m-d H:i:s");
              $data_iqa['modified_date_time'] = date("Y-m-d H:i:s");
              //print_r($data_iqa);
              $checking_details_qa = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$qualityassessment[$iqa]['complaints_name']."'")->row();
                if(count($checking_details_qa)>0){
                  $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_iqa, array('complaints_aeeigment_recommendation_id'=>$checking_details_qa->complaints_aeeigment_recommendation_id));
                }else{
                  $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqa);

                }
              //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqa);
            }
          }
        if(count($qualityrecommendation) >0){
            for($iqr=0;$iqr<count($qualityrecommendation);$iqr++){
              $data_iqr['Complaint_id'] = $Complaint_id;
              $data_iqr['profile_id'] = $profile_id;
              $data_iqr['type'] = "recommendation";
              $data_iqr['complent_recomendation'] =$qualityrecommendation[$iqr]['complaints_name'];
              $data_iqr['profile_name_type'] = "QualityDepartment";
             // $data_iqr['created_by'] = $user_id;
              $data_iqr['modified_by'] =$user_id;
             // $data_iqr['created_date_time'] = date("Y-m-d H:i:s");
              $data_iqr['modified_date_time'] = date("Y-m-d H:i:s");
              //print_r($data_iqr);
              $checking_details_qr = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$qualityrecommendation[$iqr]['complaints_name']."'")->row();
                if(count($checking_details_qr)>0){
                  $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_iqr, array('complaints_aeeigment_recommendation_id'=>$checking_details_qr->complaints_aeeigment_recommendation_id));
                }else{
                  $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqr);

                }
              //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_iqr);
          }
        }
    }
    if($profile_id == Manufacturing){
          $manufacturingassessment=$parameters['manufacturingassessment'];
          if(count($manufacturingassessment) >0){
            for($ima=0;$ima<count($manufacturingassessment);$ima++){
                $data_ima['Complaint_id'] = $Complaint_id;
                $data_ima['profile_id'] = $profile_id;
                $data_ima['type'] = "assessment";
                $data_ima['complent_recomendation'] =$manufacturingassessment[$ima]['complaints_name'];
                $data_ima['profile_name_type'] = "Manufacturing";
                //$data_ima['created_by'] = $user_id;
                $data_ima['modified_by'] =$user_id;
                //$data_ima['created_date_time'] = date("Y-m-d H:i:s");
                $data_ima['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_ima);
                $checking_details_ma = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$manufacturingassessment[$ima]['complaints_name']."'")->row();
                  if(count($checking_details_ma)>0){
                    $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_ima, array('complaints_aeeigment_recommendation_id'=>$checking_details_ma->complaints_aeeigment_recommendation_id));
                  }else{
                    $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ima);

                  }
                  echo $this->db->last_query();
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_ima);
            }
            exit;
          }
      }

      if($profile_id == SUPERADMIN){
          $managementassessment=$parameters['managementassessment'];
          $managementRecommendation=$parameters['managementRecommendation'];
          if(count($managementassessment) >0){
              for($ia=0;$ia<count($managementassessment);$ia++){
                $data_1['Complaint_id'] = $Complaint_id;
                $data_1['profile_id'] = $profile_id;
                $data_1['type'] = "assessment";
                $data_1['complent_recomendation'] =$managementassessment[$ia]['complaints_name'];
                $data_1['profile_name_type'] = "SUPERADMIN";
                //$data_1['created_by'] = $user_id;
                $data_1['modified_by'] =$user_id;
               // $data_1['created_date_time'] = date("Y-m-d H:i:s");
                $data_1['modified_date_time'] = date("Y-m-d H:i:s");
                //print_r($data_1);
                $checking_details_man = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'assessment' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$managementassessment[$ia]['complaints_name']."'")->row();
                  if(count($checking_details_man)>0){
                    $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_1, array('complaints_aeeigment_recommendation_id'=>$checking_details_man->complaints_aeeigment_recommendation_id));
                  }else{
                    $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_1);

                  }
                  //echo $this->db->last_query();
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_1);
              }
              //exit;
          }

          if(count($managementRecommendation) >0){
            for($ir=0;$ir<count($managementassessment);$ir++){
                $data_irm['Complaint_id'] = $Complaint_id;
                $data_irm['profile_id'] = $profile_id;
                $data_irm['type'] = "recommendation";
                $data_irm['complent_recomendation'] =$managementRecommendation[$ir]['complaints_name'];
                $data_irm['profile_name_type'] = "SUPERADMIN";
                //$data_irm['created_by'] = $user_id;
                $data_irm['modified_by'] =$user_id;
                //$data_irm['created_date_time'] = date("Y-m-d H:i:s");
                $data_irm['modified_date_time'] = date("Y-m-d H:i:s");
                $checking_details_mrn = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where profile_id ='".$profile_id."' and type = 'recommendation' and Complaint_id = '".$Complaint_id."' and complent_recomendation = '".$managementRecommendation[$ir]['complaints_name']."'")->row();
                  if(count($checking_details_mrn)>0){
                    $this->Generic_model->updateData('complaints_aeeigment_recommendation_tbl', $data_irm, array('complaints_aeeigment_recommendation_id'=>$checking_details_mrn->complaints_aeeigment_recommendation_id));
                  }else{
                    $this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irm);

                  }
                //$this->Generic_model->insertData("complaints_aeeigment_recommendation_tbl",$data_irm);
                //print_r($data_irm);
            }
        }
      }


        $latest_val['module_id'] = $result;
        $latest_val['module_name'] = "Complaints";
        $latest_val['user_id'] = $user_id;
        $latest_val['created_date_time'] = date("Y-m-d H:i:s");
        // $latest_val['modefied_date_time'] = date("Y-m-d H:i:s");
        $this->Generic_model->insertData("update_table",$latest_val);

      $data_1['Complaint_id'] = $Complaint_id;
      $return_data = $this->all_tables_records_view("Complaints",$Complaint_id);

      $this->response(array('code'=>'200','result'=>$return_data,'message'=>'Updated successfully','requestname'=>$method));
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    } 
  }
 public function complaint_delete($parameters,$method,$user_id){
   $complaint_id=$parameters['complaint_id'];
   $complaint_record=$this->db->query('select * from Complaints where Complaint_id='.$complaint_id)->row();
   if($complaint_record->Complaint_id==$complaint_id){ 	
 	if($complaint_id!="" || $complaint_id!=NULL){
 	   $param['archieve']=1;
 	   $param['modified_by']=$user_id;
 	   $param['modified_date_time']=date("Y-m-d H:i:S");
 	  $result=$this->Generic_model->updateData('Complaints',$param, array('Complaint_id'=>$complaint_id));
 	  if($result==1){
          $latest_val['user_id'] = $user_id;
          $latest_val['created_date_time'] = date("Y-m-d H:i:s");
          $latest_val['delete_status'] = "1";
          $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $complaint_id,'module_name'=>'Complaints'));

 	     $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
	  }else{
	 	 $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	   }
	}
   }else{
       $this->response(array('code'=>'404','message' => 'there is no record with the given ID'), 200);
    }
 }
/* public function complaint_list($parameters,$method,$user_id){
  $final_users_id = $parameters['team_id'];
   $complaint_list=$this->db->query("select * from Complaints a inner join users b on (a.ComplaintOwner = b.user_id) where  a.ComplaintOwner in (".$final_users_id.") and  a.archieve !=1 order by a.Complaint_id DESC")->result();
   if(count($complaint_list)>0){
    $i=0;
    foreach ($complaint_list as $value) {
      $data['complaint_list'][$i]['Complaint_id']=$value->Complaint_id;
      $data['complaint_list'][$i]['CustomerName']=$value->CustomerName;
      $data['complaint_list'][$i]['ComplaintNumber'] = $value->ComplaintNumber;
      $data['complaint_list'][$i]['BusinessHours']=$value->BusinessHours;
      $data['complaint_list'][$i]['Description'] = $value->Description;
      $data['complaint_list'][$i]['InvoiceNumber']=$value->InvoiceNumber;
      $data['complaint_list'][$i]['ComplaintOwner']=$value->name;
      $data['complaint_list'][$i]['ComplaintOrigin']=$value->ComplaintOrigin;
      $data['complaint_list'][$i]['ComplaintReason']=$value->ComplaintReason;
      $data['complaint_list'][$i]['ComplaintSource']=$value->ComplaintSource;
      $data['complaint_list'][$i]['ContactFax']=$value->ContactFax;
      $data['complaint_list'][$i]['ContactEmail']=$value->ContactEmail;
      $data['complaint_list'][$i]['ContactMobile']=$value->ContactMobile;
      $data['complaint_list'][$i]['ContactName']=$value->ContactName;
      $data['complaint_list'][$i]['ContactPhone']=$value->ContactPhone;
      $data['complaint_list'][$i]['Priority']=$value->Priority;
      $data['complaint_list'][$i]['Date_TimeClosed']=$value->Date_TimeClosed;
      $data['complaint_list'][$i]['Date_TimeOpened']=$value->Date_TimeOpened;
      $data['complaint_list'][$i]['Escalated']=$value->Escalated;
      $data['complaint_list'][$i]['InternalComments']=$value->InternalComments;
      $data['complaint_list'][$i]['ParentCase']=$value->ParentCase;
      $data['complaint_list'][$i]['Subject']=$value->Subject;
      $data['complaint_list'][$i]['Status']=$value->Status;
      $data['complaint_list'][$i]['Type']=$value->Type;
     
      $i++;
    }
    $this->response(array('code'=>'200','result'=>$data, 'message'=>'Complaint list','requestname'=>$method));
   }else{
     $this->response(array('code'=>'200','result'=>$data, 'message'=>'Complaint list','requestname'=>$method));
   }
 }*/


 public function complaint_list($parameters,$method,$user_id){
  $final_users_id = $parameters['team_id'];
     $complaint_list=$this->db->query("select * from Complaints a inner join users b on (a.ComplaintOwner = b.user_id) where  a.ComplaintOwner in (".$final_users_id.") and  a.archieve !=1 order by a.Complaint_id DESC")->result();
     if(count($complaint_list)>0){
      $i=0;
      foreach ($complaint_list as $value) {
      $complanints_ass_recc_list = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where Complaint_id = '".$value->Complaint_id."'")->result();

        $data['complaint_list'][$i]['sales_assessment'] =array();
        $data['complaint_list'][$i]['sales_recommendedsolution'] = array();
        $data['complaint_list'][$i]['area_assessment'] = array();
        $data['complaint_list'][$i]['area_recommendedsolution'] = array();
        $data['complaint_list'][$i]['regional_assessment'] =array();
        $data['complaint_list'][$i]['regional_recommendedsolution'] = array();
        $data['complaint_list'][$i]['national_assessment'] = array();
        $data['complaint_list'][$i]['national_recommendedsolution'] = array();
        $data['complaint_list'][$i]['quality_assessment'] = array();
        $data['complaint_list'][$i]['quality_recommendation'] = array();
        $data['complaint_list'][$i]['manufacturing_assessment'] = array();
        $data['complaint_list'][$i]['management_assessment'] = array();
        $data['complaint_list'][$i]['management_recommendation'] = array();
        $ik=$j=$k=$l=$m=$n=$o=$p=$q=$r=$s=$oi=$pi=0;
        foreach($complanints_ass_recc_list as $comp_ass_recc_val){
          $profile_id = $comp_ass_recc_val->profile_id;
          $type = $comp_ass_recc_val->type;
          $complent_recomendation = $comp_ass_recc_val->complent_recomendation;
          $com_ass_rec_id = $comp_ass_recc_val->complaints_aeeigment_recommendation_id;
         // $profile_id = $this->session->userdata('logged_in')['profile_id'];
          if($profile_id == SALESOFFICER && $type == "assessment" || $profile_id == SalesExecutive && $type == "assessment"){
            $data['complaint_list'][$i]['sales_assessment'][$ik]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['sales_assessment'][$ik]['complement_ass_name'] = $complent_recomendation;
            $ik++;
          }else if($profile_id == SALESOFFICER && $type == "recommendation" || $profile_id == SalesExecutive && $type == "recommendation" ){
            $data['complaint_list'][$i]['sales_recommendedsolution'][$j]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['sales_recommendedsolution'][$j]['complement_ass_name'] = $complent_recomendation;
            $j++;
          }else if($profile_id == AreaManager && $type == "assessment"){
            $data['complaint_list'][$i]['area_assessment'][$k]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['area_assessment'][$k]['complement_ass_name'] = $complent_recomendation;
            $k++;
          }else if($profile_id == AreaManager && $type == "recommendation" ){
            $data['complaint_list'][$i]['area_recommendedsolution'][$l]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['area_recommendedsolution'][$l]['complement_ass_name'] = $complent_recomendation;
            $l++;
          }else if($profile_id == Regionalmanager && $type == "assessment"){
            $data['complaint_list'][$i]['regional_assessment'][$m]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['regional_assessment'][$m]['complement_ass_name'] = $complent_recomendation;
            $m++;
          }else if($profile_id == Regionalmanager && $type == "recommendation" ){
            $data['complaint_list'][$i]['regional_recommendedsolution'][$n]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['regional_recommendedsolution'][$n]['complement_ass_name'] = $complent_recomendation;
            $n++;
          }else if($profile_id == NationalHead && $type == "assessment"){
            $data['complaint_list'][$i]['national_assessment'][$o]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['national_assessment'][$o]['complement_ass_name'] = $complent_recomendation;
            $o++;
          }else if($profile_id == NationalHead && $type == "recommendation" ){
            $data['complaint_list'][$i]['national_recommendedsolution'][$p]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['national_recommendedsolution'][$p]['complement_ass_name'] = $complent_recomendation;
            $p++;
          }else if($profile_id == QualityDepartment && $type == "assessment"){
            $data['complaint_list'][$i]['quality_assessment'][$oi]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['quality_assessment'][$oi]['complement_ass_name'] = $complent_recomendation;
            $oi++;
          }else if($profile_id == QualityDepartment && $type == "recommendation" ){
            $data['complaint_list'][$i]['quality_recommendation'][$pi]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['quality_recommendation'][$pi]['complement_ass_name'] = $complent_recomendation;
            $pi++;
          }else if($profile_id == Manufacturing && $type == "assessment"){
            $data['complaint_list'][$i]['manufacturing_assessment'][$q]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['manufacturing_assessment'][$q]['complement_ass_name'] = $complent_recomendation;
            $q++;
          }else if($profile_id == SUPERADMIN && $type == "assessment"){
            $data['complaint_list'][$i]['management_assessment'][$r]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['management_assessment'][$r]['complement_ass_name'] = $complent_recomendation;
            $r++;
          }else if($profile_id == SUPERADMIN && $type == "recommendation" ){
            $data['complaint_list'][$i]['management_recommendation'][$s]['com_ass_rec_id'] = $com_ass_rec_id;
            $data['complaint_list'][$i]['management_recommendation'][$s]['complement_ass_name'] = $complent_recomendation;
            $s++;
          }

        }
       

        $data['complaint_list'][$i]['complaint_id']=$value->Complaint_id;
        $customer_list = $this->db->query("select * from customers where customer_id ='".$value->CustomerName."'")->row();
         if(($customer_list) >0){
          $data['complaint_list'][$i]['customer_id']=$customer_list->customer_id;
          $data['complaint_list'][$i]['CustomerName']=$customer_list->CustomerName;
        }else{
          $data['complaint_list'][$i]['customer_id']='';
          $data['complaint_list'][$i]['CustomerName']='';
        }
        //$data['complaint_list'][$i]['CustomerName']=$value->CustomerName;
        $data['complaint_list'][$i]['ComplaintNumber'] = $value->ComplaintNumber;
        $data['complaint_list'][$i]['salesorderdate']=$value->salesorderdate;

        $data['complaint_list'][$i]['salesordernumber'] = $value->salesordernumber;
        $data['complaint_list'][$i]['feedback']=$value->feedback;
        $data['complaint_list'][$i]['applicationdate']=$value->applicationdate;

        $data['complaint_list'][$i]['feedbackother']=$value->feedbackother;
        $data['complaint_list'][$i]['invoicedate']=$value->invoicedate;

        $data['complaint_list'][$i]['invoicenumber']=$value->invoicenumber;
        $data['complaint_list'][$i]['batchnumber']=$value->batchnumber;
        $data['complaint_list'][$i]['defectivesample']=$value->defectivesample;
        $data['complaint_list'][$i]['sampleplantlab']=$value->sampleplantlab;

       /* $data['complaint_list'][$i]['sitevisit']=$value->sitevisit;
        $data['complaint_list'][$i]['otherassessment']=$value->otherassessment;
        $data['complaint_list'][$i]['otherrecommendedsolution']=$value->otherrecommendedsolution;*/
         $data['complaint_list'][$i]['sales_sitevisit']=$value->sales_sitevisit;
        //$data['complaint_list'][$i]['sales_assessment']=$value->sales_assessment;
        //$data['complaint_list'][$i]['sales_recommendedsolution']=$value->sales_recommendedsolution;
        $data['complaint_list'][$i]['sales_status']=$value->sales_status;

         $data['complaint_list'][$i]['area_sitevisit']=$value->area_sitevisit;
        //$data['complaint_list'][$i]['area_assessment']=$value->area_assessment;
        //$data['complaint_list'][$i]['area_recommendedsolution']=$value->area_recommendedsolution;
        $data['complaint_list'][$i]['area_status']=$value->area_status;

         $data['complaint_list'][$i]['regional_sitevisit']=$value->regional_sitevisit;
        //$data['complaint_list'][$i]['regional_assessment']=$value->regional_assessment;
        //$data['complaint_list'][$i]['regional_recommendedsolution']=$value->regional_recommendedsolution;
        $data['complaint_list'][$i]['regional_status']=$value->regional_status;

         $data['complaint_list'][$i]['national_sitevisit']=$value->national_sitevisit;
        //$data['complaint_list'][$i]['national_assessment']=$value->national_assessment;
        //$data['complaint_list'][$i]['national_recommendedsolution']=$value->national_recommendedsolution;
         $data['complaint_list'][$i]['national_status']=$value->national_status;

         $data['complaint_list'][$i]['credit_note_given']=$value->credit_note_given;
        $data['complaint_list'][$i]['material_replaced']=$value->material_replaced;
        $data['complaint_list'][$i]['comercial_remarks']=$value->comercial_remarks;


        $data['complaint_list'][$i]['qualitytestsdone']=$value->qualitytestsdone;
        //$data['complaint_list'][$i]['qualityassessment']=$value->qualityassessment;
        //$data['complaint_list'][$i]['qualityrecommendation']=$value->qualityrecommendation;
        //$data['complaint_list'][$i]['manufacturingassessment']=$value->manufacturingassessment;
        //$data['complaint_list'][$i]['managementassessment']=$value->managementassessment;
        //$data['complaint_list'][$i]['managementRecommendation']=$value->managementRecommendation;
        /*$data['complaint_list'][$i]['Status']=$value->Status;
        $data['complaint_list'][$i]['Type']=$value->Type;*/
       
        $i++;
      }
      $this->response(array('code'=>'200','result'=>$data, 'message'=>'Complaint list','requestname'=>$method));
     }else{
       $this->response(array('code'=>'200','result'=>$data, 'message'=>'Complaint list','requestname'=>$method));
     }
   }

  // public function contract_insert($parameters,$method,$user_id){
  // 	print_r($parameters);
  // 	exit;
  //   $parameters['ActivatedDate']=date("Y-m-d H:i:s", strtotime($parameters['ActivatedDate']));
  //   $parameters['CompanySignedDate']=date("Y-m-d H:i:s", strtotime($parameters['CompanySignedDate']));
  //   $parameters['ContractStartDate']=date("Y-m-d H:i:s", strtotime($parameters['ContractStartDate']));
  //   $parameters['ContractEndDate']=date("Y-m-d H:i:s", strtotime($parameters['ContractEndDate']));
  //   $parameters['CustomerSignedDate']=date("Y-m-d H:i:s", strtotime($parameters['CustomerSignedDate']));
  //   $parameters['created_by']=$user_id;
  //   $parameters['modified_by']=$user_id;
  //   $parameters['created_date_time']=date("Y-m-d H:i:s");
  //   $parameters['modified_date_time']=date("Y-m-d H:i:s");
  //   $result=$this->Generic_model->insertData('contract',$parameters);

  //     if($result==1){
  //     $this->response(array('code'=>'200','result'=>$parameters,'message'=>'Inserted successfully','requestname'=>$method));
  //     }else{
  //       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  //       }   

  // }
  // public function contract_edit($parameters,$method,$user_id){
  //   $parameters['ActivatedDate']=date("Y-m-d H:i:s", strtotime($parameters['ActivatedDate']));
  //   $parameters['CompanySignedDate']=date("Y-m-d H:i:s", strtotime($parameters['CompanySignedDate']));
  //   $parameters['ContractStartDate']=date("Y-m-d H:i:s", strtotime($parameters['ContractStartDate']));
  //   $parameters['ContractEndDate']=date("Y-m-d H:i:s", strtotime($parameters['ContractEndDate']));
  //   $parameters['CustomerSignedDate']=date("Y-m-d H:i:s", strtotime($parameters['CustomerSignedDate']));
  //   $parameters['created_by']=$user_id;
  //   $parameters['modified_by']=$user_id;
  //   $parameters['created_date_time']=date("Y-m-d H:i:s");
  //   $parameters['modified_date_time']=date("Y-m-d H:i:s");
  //   $result=$this->Generic_model->updateData('contract',$parameters,array('contract_id'=>$parameters['contract_id']));
  //     if($result==1){
  //     $this->response(array('code'=>'200','result'=>$parameters,'message'=>'Updated successfully','requestname'=>$method));
  //     }else{
  //       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  //     }   

  // }
  // public function contract_list($parameters,$method,$user_id){
  //  $contract_list=$this->Generic_model->getAllRecords('contract',array('archieve'=>!1),$order='');
  //  if(count($contract_list)>0){
  //   $i=0;
  //  foreach ($(contract_list) as $value) {
  //    $data['contract_list'][$i]['contract_id']=$value->contract_id;
  //    $data['contract_list'][$i]['Customer']=$value->Customer;
  //    $data['contract_list'][$i]['ActivatedBy']=$value->ActivatedBy;
  //    $data['contract_list'][$i]['ActivatedDate']=$value->ActivatedDate;
  //    $data['contract_list'][$i]['BillingAddress']=$value->BillingAddress;
  //    $data['contract_list'][$i]['CompanySignedBy']=$value->CompanySignedBy;
  //    $data['contract_list'][$i]['CompanySignedDate']=$value->CompanySignedDate;
  //    $data['contract_list'][$i]['ContractEndDate']=$value->ContractEndDate;
  //    $data['contract_list'][$i]['ContractName']=$value->ContractName;
  //    $data['contract_list'][$i]['ContractNumber']=$value->ContractNumber;
  //    $data['contract_list'][$i]['ContractOwner']=$value->ContractOwner;
  //    $data['contract_list'][$i]['ContractStartDate']=$value->ContractStartDate;
  //    $data['contract_list'][$i]['ContractTerm']=$value->ContractTerm ;
  //    $data['contract_list'][$i]['CustomerSignedBy']=$value->CustomerSignedBy;
  //    $data['contract_list'][$i]['CustomerSignedDate ']=$value->CustomerSignedDate;
  //    $data['contract_list'][$i]['CustomerSignedTitle']=$value->CustomerSignedTitle;
  //    $data['contract_list'][$i]['Description']=$value->Description;
  //    $data['contract_list'][$i]['OwnerExpirationNotice']=$value->OwnerExpirationNotice;
  //    $data['contract_list'][$i]['ShippingAddress']=$value->ShippingAddress;
  //    $data['contract_list'][$i]['SpecialTerms']=$value->SpecialTerms;
  //    $data['contract_list'][$i]['Status']=$value->Status;
  //    $data['contract_list'][$i]['created_by']=$value->created_by;
  //    $data['contract_list'][$i]['modified_by']=$value->modified_by;
  //    $data['contract_list'][$i]['created_date_time']=$value->created_date_time;
  //    $data['contract_list'][$i]['modified_date_time']=$value->modified_date_time;
  //   $i++;
  // }
  //  $this->response(array('code'=>'200','result'=>$data, 'message'=>'Contract_list','requestname'=>$method));
  // }else{
  //       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  //     }
  // }

  // public function contract_delete($parameters,$method,$user_id){
  //   $contract_id=$parameters['contract_id'];
  //    $contract_record=$this->db->query('select * from contract where contract_id='.$contract_id)->row();
  //    if($contract_record->contract_id==$contract_id){
  //  	if($contract_id!="" || $contract_id!=NULL){
  //  	   $param['archieve']=1;
  //  	   $param['modified_by']=$user_id;
  //  	   $param['modified_date_time']=date("Y-m-d H:i:S");
  //  	  $result=$this->Generic_model->updateData('contract',$param, array('contract_id'=>$contract_id));
  //  	  if($result==1){
  //  	     $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
  // 	  }else{
  // 	 	 $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  // 	   }
  // 	}
  //    }else{
  //        $this->response(array('code'=>'404','message' => 'there is no record with the given ID'), 200);
  //    }
  // }

  public function quotation_opportunities_list($parameters,$method,$user_id){
    $opportunity_id = $parameters['opportunity_id'];
    $opportunity_list = $this->db->query("select * from opportunities a inner join customers b on (a.Customer = b.customer_id) where a.opportunity_id = '".$opportunity_id ."'")->row();
    $customer_val_id =$opportunity_list->Customer;
	
	$price_list_info = $this->db->query("select Product_price_master_id from users where user_id = ".$user_id)->row();
	$price_list_id = $price_list_info->Product_price_master_id;

    //$checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$customer_val_id."' and status ='Active'")->row();
    //if(count($checking_price_list)>0){
	if($price_list_id != 0 || $price_list_id != NULL || $price_list_id != ''){
		//$product_list = $this->db->query("select * from product_master a inner join Price_list_line_Item b on (a.product_id = b.product) where b.Price_list_id ='".$checking_price_list->price_list_id."'")->result();
		$product_list = $this->db->query("select * from product_master a inner join Price_list_line_Item b on (a.product_id = b.product) where b.Price_list_id ='".$price_list_id."'")->result();

		$contacts_list = $this->db->query("select * from contacts where Company =".$customer_val_id)->result();
		//$product_price_list = $this->db->query("select * from product_opportunities where  Opportunity =".$opportunity_id)->result();

		//$product_price_list = $this->db->query("select * from product_opportunities  a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where  Opportunity = '".$opportunity_id."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result();
		$product_price_list = $this->db->query("select * from product_opportunities  a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where  Opportunity = '".$opportunity_id."' and c.Price_list_id ='".$price_list_id."'")->result();


        $data['Customer'] = $opportunity_list->CustomerName;
        $data['Customer_id'] = $opportunity_list->Customer;
        $data['BillingStreet1'] = $opportunity_list->BillingStreet1;
        $data['Billingstreet2'] = $opportunity_list->Billingstreet2;
        $data['BillingCountry'] = $opportunity_list->BillingCountry;
        $data['StateProvince'] = $opportunity_list->StateProvince;
        $data['BillingCity'] = $opportunity_list->BillingCity;
        $data['BillingZipPostal'] = $opportunity_list->BillingZipPostal;
        $data['ShippingStreet1'] = $opportunity_list->ShippingStreet1;
        $data['Shippingstreet2'] = $opportunity_list->Shippingstreet2;
        $data['ShippingCountry'] = $opportunity_list->ShippingCountry;
        $data['ShippingStateProvince'] = $opportunity_list->ShippingStateProvince;
        $data['ShippingCity'] = $opportunity_list->ShippingCity;
        $data['ShippingZipPostal'] = $opportunity_list->ShippingZipPostal;
       
		$i=0;
		$total_final_val =0;
        foreach($product_price_list as $pp_values){
			//$price_list_product = $this->db->query("select * from Price_list_line_Item where product ='".$pp_values->Product."' and Price_list_id ='".$checking_price_list->price_list_id."'")->row();
			$price_list_product = $this->db->query("select * from Price_list_line_Item where product ='".$pp_values->Product."' and Price_list_id ='".$price_list_id."'")->row();

			$price_val = $pp_values->price;
			$qty_blc = $pp_values->Quantity;
			$total_amount = $price_val*$qty_blc;
			$total_final_val = $total_final_val+$total_amount;

			$data["product_price_list"][$i]["Product"] = $pp_values->product_name;
			$data["product_price_list"][$i]["Product_id"] = $pp_values->Product;
			$data["product_price_list"][$i]["ListPrice"] = $pp_values->price;
			$data["product_price_list"][$i]["Quantity"] = $qty_blc;
			$data["product_price_list"][$i]["Discount"] = "0";
			$data["product_price_list"][$i]["Subtotal"] = $total_amount;
			$i++;
        }
		
		$data['total_amount'] = $total_final_val;
        
        $j=0;
        foreach($contacts_list as $c_val){
          $data['contact_list'][$j]["contact_id"] = $c_val->contact_id;
          $data['contact_list'][$j]["contact_name"] = $c_val->FirstName ." ".$c_val->LastName;
          $j++;
        }
        if(count($data)>0){
          $this->response(array('code'=>'200','result'=>$data, 'message'=>'quotation_opportunities_list','requestname'=>$method));
        }else{
          $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
        }  
    }else{
		//$this->response(array('code'=>'200','message'=>"There is no Price List tagged for selected Customer, Please assign Price List to Customer then create Sales Order",'requestname'=>$method));
		$this->response(array('code'=>'200','message'=>"No Price Tag",'requestname'=>$method));
    }
  }
  
  public function quotation_approval($parameters,$method,$user_id){
		//$user_id=$this->session->userdata('logged_in')['id'];		
		$id = $parameters['quatation_id'];
		$data_ins_chk = $this->db->query("select * from approval_process where approval_type_id=".$id." and approval_type='Quotation' and status=3 order by ap_id desc")->result_array();
		//echo $this->db->last_query();exit;
		if(count($data_ins_chk)>0){
			
			$role_id = $parameters['role_id'];
			$comments = $parameters['comm'];
			$type = $parameters['type'];
			if($type=='Accept'){
				$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$role_id)->row();
				if(count($rol_discount)>0)
				{
					$products_info = $this->db->query("select * from Quotation_Product where Quotation_id=".$id)->result_array();
					$dchk=0;
					foreach($products_info as $result)
					{
						if($rol_discount->dis_limit<$result['Discount'])
						{
							$dchk++;
						}
					}
					
					if($dchk==0)
					{
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=1,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update Quotation set status='Approved' where Quotation_id=".$id);
					}
					else{
						$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$user_id)->row();
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=2,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update Quotation set status='Approved but Pending' where Quotation_id=".$id);
						$data_ins['approval_type'] = 'Contract';
						$data_ins['approval_type_id'] = $id;
						$data_ins['status'] = 3;
						$data_ins['datetime'] = date('Y-m-d H:i:s');
						$data_ins['assigned_to'] = $nex_report->role_reports_to;
						$data_ins['comments'] = '';
						$data_ins['created_by'] = $user_id;
						$data_ins['modified_by'] = $user_id;
						$data_ins['created_datetime'] = date('Y-m-d H:i:s');
						$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
						$ok = $this->Generic_model->insertData("approval_process",$data_ins);
					}
				}
			}
			else{
				$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=0,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
				//echo $this->db->last_query();exit;
				$this->db->query("update Quotation set status='Rejected' where Quotation_id=".$id);
			}
		}else
		{
			
			$data['sales_order_list'] = $this->db->query("select *,a.created_by as se from Quotation a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.Quotation_id =".$id)->row();
		
			$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$data['sales_order_list']->se)->row();
			//echo $this->db->last_query();exit;
			$data_ins['approval_type'] = 'Quotation';
			$data_ins['approval_type_id'] = $id;
			$data_ins['status'] = 3;
			$data_ins['datetime'] = date('Y-m-d H:i:s');
			$data_ins['assigned_to'] = $nex_report->role_reports_to;
			$data_ins['comments'] = '';
			$data_ins['created_by'] = $user_id;
			$data_ins['modified_by'] = $user_id;
			$data_ins['created_datetime'] = date('Y-m-d H:i:s');
			$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
			$ok = $this->Generic_model->insertData("approval_process",$data_ins);
			if($ok)
				$this->db->query("update Quotation set status='Pending' where Quotation_id=".$id);
		//echo $this->db->last_query();
		}
		$this->response(array('code'=>'200','message'=>'quotation_approval','result'=>$data,'requestname'=>$method));	
	}
  public function qutation_insert($parameters,$method,$user_id) {
    $checking_id = $this->db->query("select * from Quotation order by QuotationversionID DESC")->row();
          if($checking_id->QuotationversionID == NULL || $checking_id->QuotationversionID == ""){
              $QuotationversionID = "QV-00001";
          }else{
              $qv_check = trim($checking_id->QuotationversionID);
              $checking_qv_id =  substr($qv_check, 3);
              if($checking_qv_id == "99999"||$checking_qv_id == "999999"||$checking_qv_id =="9999999" || $checking_qv_id == "99999999" || $checking_qv_id == "999999999" || $checking_qv_id == "9999999999" ){
                  $QuotationversionID_last_inc = (++$checking_qv_id);
                  $QuotationversionID= "QV-".$QuotationversionID_last_inc;
              }else{
                  $QuotationversionID = (++$qv_check);
              } 

          }
        $param_1['QuotationversionID']  = $QuotationversionID;
        $param_1['Opportunity'] = $parameters['Opportunity'];
        $param_1['Customer'] = $parameters['Customer'];
        $param_1['Contact'] = $parameters['Contact'];
        $param_1['BillingStreet1'] = $parameters['BillingStreet1'];
        $param_1['Billingstreet2'] = $parameters['Billingstreet2'];
        $param_1['BillingCountry'] = $parameters['BillingCountry'];
        $param_1['StateProvince'] = $parameters['StateProvince'];
        $param_1['BillingCity'] = $parameters['BillingCity'];
        $param_1['BillingZipPostal'] = $parameters['BillingZipPostal'];
        $param_1['ShippingStreet1'] = $parameters['ShippingStreet1'];
        $param_1['Shippingstreet2'] = $parameters['Shippingstreet2'];
        $param_1['ShippingCountry'] = $parameters['ShippingCountry'];
        $param_1['ShippingStateProvince'] = $parameters['ShippingStateProvince'];
        $param_1['ShippingCity'] = $parameters['ShippingCity'];
        $param_1['ShippingZipPostal'] = $parameters['ShippingZipPostal'];
        $param_1['ShippingCity'] = $parameters['ShippingCity'];
        $param_1['TotalPrice'] = $parameters['TotalPrice'];
        $param_1['Remarks'] = $parameters['Remarks'];
        $param_1['QuotationDate'] = date("Y-m-d H:i:s",strtotime($parameters['QuotationDate']));
        $param_1['ExpiryDate'] = date("Y-m-d H:i:s",strtotime($parameters['ExpiryDate']));
        $param_1['created_by'] = $user_id;
        $param_1['modified_by'] = $user_id;
        $param_1['created_date_time'] = date("Y-m-d H:i:s");
        $param_1['modified_date_time'] = date("Y-m-d H:i:s");
        $Quotation_id = $this->Generic_model->insertDataReturnId("Quotation",$param_1);

        if($Quotation_id == "" || $Quotation_id == NULL){

          $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
        }else{

           $products_price = count($parameters['products_price']);
              for($k=0;$k<$products_price;$k++){
                $Product_id = $parameters["products_price"][$k]['Product'];
                $product_details = $this->db->query("select * from product_master where   product_id =".$Product_id)->row();
                  $param_2['Quotation_id'] = $Quotation_id;
                  $param_2['ListPrice'] = $parameters["products_price"][$k]['ListPrice'];
                  $param_2['Product'] = $parameters["products_price"][$k]['Product'];
                  $param_2['Productcode'] = $product_details->product_code;
                  $param_2['Quantity'] = $parameters["products_price"][$k]['Quantity'];
                  $param_2['Discount'] = $parameters["products_price"][$k]['Discount'];
                  $param_2['Subtotal'] = $parameters["products_price"][$k]['Subtotal'];
                  $param_2['created_by'] =$user_id;
                  $param_2['modified_by'] =$user_id;
                  $param_2['created_date_time'] =date("Y-m-d H:i:s");
                  $param_2['modified_date_time'] =date("Y-m-d H:i:s");

                  $ok = $this->Generic_model->insertData("Quotation_Product",$param_2);
              }

              $user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
              $Quotation_list = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_id) where a.Quotation_id = ".$Quotation_id)->result();
              $customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Customer'])->row();
              $email = $user_list->email;
              $to = $email;  //$to      = $dept_email_id;
              $subject = "New Quotation Created";
              $data['name'] = ucwords($user_list->name);

               $data['message'] = "<p> A new Quotation has been created successfully <br/><br/><b> QuotationversionID </b> : ".$QuotationversionID."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName.", <br/><b>Shipping Address :</b>".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal']."<br/><b>Billing Address</b>".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."</p> <br/><br/>
                      <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
                        </tr></thead>";

                       if(count($Quotation_list) >0){
                          foreach($Quotation_list as $quo_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->ListPrice."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Discount."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Subtotal."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  


              $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);


              $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();
              if(count($user_report_to) >0){
                $email = $user_report_to->email;
                $to = $email;  //$to      = $dept_email_id;
                $subject = "New Quotation Created";
                $data['name'] = ucwords($user_report_to->name);
                $data['message'] = "<p> A new Quotation has been created successfully By ".ucwords($user_list->name)."<br/><br/><b> QuotationversionID </b> : ".$QuotationversionID."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName.", <br/><b>Shipping Address :</b>".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal']."<br/><b>Billing Address</b>".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."</p> <br/><br/>
                      <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
                        </tr></thead>";

                       if(count($Quotation_list) >0){
                          foreach($Quotation_list as $quo_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->ListPrice."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Discount."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$quo_values->Subtotal."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  
                $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

              }



              $param_noti['notiffication_type'] = "Quotation";
                $param_noti['notiffication_type_id'] = $Quotation_id;
                $param_noti['user_id'] = $user_id;
                $param_noti['subject'] = " A new Quotation has been created successfully  QuotationversionID  : ".$QuotationversionID." CustomerName  : ".$customer_list->CustomerName.", Shipping Address :".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal'].", Billing Address".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."";
                $this->Generic_model->insertData("notiffication",$param_noti);



                $latest_val['module_id'] = $Quotation_id;
                $latest_val['module_name'] = "Quotation";
                $latest_val['user_id'] = $user_id;
                $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                $this->Generic_model->insertData("update_table",$latest_val);


                if(count($user_list)>0){
                  $push_noti['fcmId_android'] = $user_list->fcmId_android;
                  $push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
                  }else{
                  $push_noti['fcmId_android'] ="";
                  $push_noti['fcmId_iOS'] = "";   
                  }
                  if(count($user_report_to) >0){
                  $push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
                  $push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
                  }else{
                  $push_noti['fcmId_android_report_to'] = "";
                  $push_noti['fcmId_iOS_report_to'] = "";
                  }
                $push_noti['Quotation_id'] = $Quotation_id;
                $push_noti['user_id'] = $user_id;
                $push_noti['subject'] = "A new Quotation has been created successfully  QuotationversionID  : ".$QuotationversionID." CustomerName  : ".$customer_list->CustomerName.", Shipping Address :".$parameters['ShippingStreet1']." ".$parameters['Shippingstreet2']." ".$parameters['ShippingCity']." ".$parameters['ShippingStateProvince']." ".$parameters['ShippingCountry']." ". $parameters['ShippingZipPostal'].", Billing Address".$parameters['BillingStreet1']." ".$parameters['Billingstreet2']." ".$parameters['BillingCity']." ".$parameters['StateProvince']." ".$parameters['BillingCountry']." ". $parameters['BillingZipPostal']."";
                $this->PushNotifications->Quotation_notifications($push_noti);



			if($ok == 1){
				   $quotation_list = $this->db->query("select *,a.created_by as se from Quotation a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.Quotation_id =".$Quotation_id)->row();							
				  $nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$quotation_list->se)->row();
					
				  $rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
				  if(count($rol_discount)>0)
				  {
					  $dchk=0;
						$product_id = count($parameters['products_price']);
						for($j=0;$j<$product_id;$j++)
						{
							if($rol_discount->dis_limit<$parameters["products_price"][$k]['Discount'])
								$dchk++;								
						}
						if($dchk==0)
						{
							//echo $sales_order_id;exit;
							$this->db->query("update Quotation set a_status='Approved' where Quotation_id=".$Quotation_id);
						}
						else{
							//echo "false".$sales_order_id;exit;
							$data_ins['approval_type'] = 'Quotation';
							$data_ins['approval_type_id'] = $Quotation_id;
							$data_ins['status'] = 3;
							$data_ins['datetime'] = date('Y-m-d H:i:s');
							$data_ins['assigned_to'] = $nex_report->role_reports_to;
							$data_ins['comments'] = '';
							$data_ins['created_by'] = $user_id;
							$data_ins['modified_by'] = $user_id;
							$data_ins['created_datetime'] = date('Y-m-d H:i:s');
							$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
							$ok = $this->Generic_model->insertData("approval_process",$data_ins);
							if($ok)
								$this->db->query("update Quotation set a_status='Pending' where Quotation_id=".$contract_id);
						}
				  }
                    //$this->session->set_flashdata('suscess', 'Successfully Added');
                    //redirect("admin/quotation_list");
                }else{
                 //redirect("admin/quotation_list");
                }
        }

        $data_1['Quotation_id'] = $Quotation_id;

        $return_data = $this->all_tables_records_view("Quotation",$Quotation_id);
       if($ok ==1){
        $this->response(array('code'=>'200','message'=>'Inserted successfully', 'result'=>$return_data,'requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       
       }

  }
  public function qutation_edit($parameters,$method,$user_id){
        $param_1['Opportunity'] = $parameters['Opportunity'];
        $param_1['Customer'] = $parameters['Customer'];
        $param_1['Contact'] = $parameters['Contact'];
        $param_1['BillingStreet1'] = $parameters['BillingStreet1'];
        $param_1['Billingstreet2'] = $parameters['Billingstreet2'];
        $param_1['BillingCountry'] = $parameters['BillingCountry'];
        $param_1['StateProvince'] = $parameters['StateProvince'];
        $param_1['BillingCity'] = $parameters['BillingCity'];
        $param_1['BillingZipPostal'] = $parameters['BillingZipPostal'];
        $param_1['ShippingStreet1'] = $parameters['ShippingStreet1'];
        $param_1['Shippingstreet2'] = $parameters['Shippingstreet2'];
        $param_1['ShippingCountry'] = $parameters['ShippingCountry'];
        $param_1['ShippingStateProvince'] = $parameters['ShippingStateProvince'];
        $param_1['ShippingCity'] = $parameters['ShippingCity'];
        $param_1['ShippingZipPostal'] = $parameters['ShippingZipPostal'];
        $param_1['ShippingCity'] = $parameters['ShippingCity'];
        $param_1['TotalPrice'] = $parameters['TotalPrice'];
        $param_1['Remarks'] = $parameters['Remarks'];
        $param_1['QuotationDate'] = date("Y-m-d H:i:s",strtotime($parameters['QuotationDate']));
        $param_1['ExpiryDate'] = date("Y-m-d H:i:s",strtotime($parameters['ExpiryDate']));
        $param_1['modified_by'] = $user_id;
        $param_1['modified_date_time'] = date("Y-m-d H:i:s");
    $result=$this->Generic_model->updateData('Quotation',$param_1,array('Quotation_id'=>$parameters['Quotation_id']));
    if($result == 1){

        $check_update_list = $this->db->query("select * from update_table where module_id ='".$parameters['Quotation_id']."' and module_name ='Quotation'")->row();
          if(count($check_update_list)>0){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $parameters['Quotation_id'],'module_name'=>'Quotation'));
          }else{
            $latest_val['module_id'] = $parameters['Quotation_id'];
            $latest_val['module_name'] = "Quotation";
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $this->Generic_model->insertData("update_table",$latest_val);
          }



      $products_price = count($parameters['products_price']);
      for($k=0;$k<$products_price;$k++){
          $Product_id = $parameters["products_price"][$k]['Product'];
          $product_details = $this->db->query("select * from product_master where  product_id =".$Product_id)->row();
            $Quotation_Product_id = $parameters["products_price"][$k]['Quotation_Product_id'];
            $param_2['Quotation_id'] =$parameters['Quotation_id'];
            $param_2['ListPrice'] = $parameters["products_price"][$k]['ListPrice'];
            $param_2['Product'] = $parameters["products_price"][$k]['Product'];
            $param_2['Productcode'] = $product_details->product_code;
            $param_2['Quantity'] = $parameters["products_price"][$k]['Quantity'];
            $param_2['Discount'] = $parameters["products_price"][$k]['Discount'];
            $param_2['Subtotal'] = $parameters["products_price"][$k]['Subtotal'];
            $param_2['created_by'] =$user_id;
            $param_2['modified_by'] =$user_id;
            $param_2['created_date_time'] =date("Y-m-d H:i:s");
            $param_2['modified_date_time'] =date("Y-m-d H:i:s");
            if($Quotation_Product_id == NULL || $Quotation_Product_id == ""){
              $ok = $this->Generic_model->insertData("Quotation_Product",$param_2);
            }else{
              $ok = $this->Generic_model->updateData('Quotation_Product',$param_2,array('Quotation_Product_id'=>$Quotation_Product_id));
            }
          
        }
         if($ok == 1){
			 $quotation_list = $this->db->query("select *,a.created_by as se from Quotation a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.Quotation_id =".$parameters['Quotation_id'])->row();							
		  $nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$quotation_list->se)->row();
			
		  $rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
		  if(count($rol_discount)>0)
		  {
			  $dchk=0;
				$product_id = count($this->input->post("product_id[]"));
				for($j=0;$j<$product_id;$j++)
				{
					if($rol_discount->dis_limit<$parameters["products_price"][$j]['Discount'])
						$dchk++;								
				}
				if($dchk==0)
				{
					//echo $sales_order_id;exit;
					$this->db->query("update Quotation set status='Approved' where Quotation_id=".$parameters['Quotation_id']);
				}
				else{
					//echo "false".$sales_order_id;exit;
					$data_ins['approval_type'] = 'Quotation';
					$data_ins['approval_type_id'] = $parameters['Quotation_id'];
					$data_ins['status'] = 3;
					$data_ins['datetime'] = date('Y-m-d H:i:s');
					$data_ins['assigned_to'] = $nex_report->role_reports_to;
					$data_ins['comments'] = '';
					$data_ins['created_by'] = $user_id;
					$data_ins['modified_by'] = $user_id;
					$data_ins['created_datetime'] = date('Y-m-d H:i:s');
					$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
					$ok = $this->Generic_model->insertData("approval_process",$data_ins);
					if($ok)
						$this->db->query("update Quotation set a_status='Pending' where Quotation_id=".$parameters['Quotation_id']);
				}
		  }

         $return_data = $this->all_tables_records_view("Quotation",$parameters['Quotation_id']);
            $this->response(array('code'=>'200','message'=>'Quotation updated successfully', 'result'=>$return_data,'requestname'=>$method));
        }else{
          $this->response(array('code'=>'404','message' => 'Authentication Failed2'), 200);
        }
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed1'), 200);
    }
  }

  function qutation_delete($parameters,$method,$user_id){
    $Quotation_id = $parameters['Quotation_id'];
    $user_id=$this->session->userdata('logged_in')['id'];
        $param['archieve'] = "1";
        $param['modified_by'] =$user_id;
        $param['modified_date_time'] =date("Y-m-d H:i:s");
        $ok = $this->Generic_model->updateData('Quotation', $param, array('Quotation_id' => $Quotation_id));  
        if($ok == 1){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $latest_val['delete_status'] = "1";
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $Quotation_id,'module_name'=>'Quotation'));

            $this->response(array('code'=>'200','message'=>'Quotation Deleted successfully', 'result'=>"",'requestname'=>$method));
        }else{
            $this->response(array('code'=>'404','message' => 'Authentication Failed2'), 200);
        }
  }

  function qutation_list_all($parameters,$method,$user_id){
  	$team_id = $parameters['team_id'];

    $qutation_list = $this->db->query("select * from Quotation  where created_by in (".$team_id.") and  archieve != 1")->result();

    $i=0;
     foreach($qutation_list as $qutation_val){
      $customer_details = $this->db->query("select * from customers where customer_id ='".$qutation_val->Customer."'")->row();
      $contact_list = $this->db->query("select * from contacts where contact_id ='".$qutation_val->Contact."'")->row();
      $data["qutation_list"][$i]['Quotation_id'] = $qutation_val->Quotation_id;
      $data["qutation_list"][$i]['QuotationversionID'] = $qutation_val->QuotationversionID;
      $data["qutation_list"][$i]['Opportunity'] = $qutation_val->Opportunity;
      $data["qutation_list"][$i]['QuotationDate'] = date("Y-m-d",strtotime($qutation_val->QuotationDate));
      $data["qutation_list"][$i]['ExpiryDate'] = date("Y-m-d",strtotime($qutation_val->ExpiryDate));
      $data["qutation_list"][$i]['Customer'] = $customer_details->CustomerName;
       $data["qutation_list"][$i]['Customer_id'] = $qutation_val->Customer;
      if(count($contact_list)>0){
        $data["qutation_list"][$i]['Contact_id'] = $contact_list->contact_id;
        $data["qutation_list"][$i]['Contact'] = $contact_list->FirstName." ".$contact_list->LastName;
       }else{
        $data["qutation_list"][$i]['Contact_id'] = "";
        $data["qutation_list"][$i]['Contact'] = "";
       }
       
      $data["qutation_list"][$i]['BillingStreet1'] = $qutation_val->BillingStreet1;
      $data["qutation_list"][$i]['Billingstreet2'] = $qutation_val->Billingstreet2;
      $data["qutation_list"][$i]['BillingCountry'] = $qutation_val->BillingCountry;
      $data["qutation_list"][$i]['StateProvince'] = $qutation_val->StateProvince;
      $data["qutation_list"][$i]['BillingCity'] = $qutation_val->BillingCity;
      $data["qutation_list"][$i]['BillingZipPostal'] = $qutation_val->BillingZipPostal;
      $data["qutation_list"][$i]['ShippingStreet1'] = $qutation_val->ShippingStreet1;
      $data["qutation_list"][$i]['Shippingstreet2'] = $qutation_val->Shippingstreet2;
      $data["qutation_list"][$i]['ShippingCountry'] = $qutation_val->ShippingCountry;
      $data["qutation_list"][$i]['ShippingStateProvince'] = $qutation_val->ShippingStateProvince;
      $data["qutation_list"][$i]['ShippingCity'] = $qutation_val->ShippingCity;
      $data["qutation_list"][$i]['ShippingZipPostal'] = $qutation_val->ShippingZipPostal;
      $data["qutation_list"][$i]['TotalPrice'] = $qutation_val->TotalPrice;
      $data["qutation_list"][$i]['Remarks'] = $qutation_val->Remarks;
    

      $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$qutation_val->Customer."'")->row();
      $qutation_product = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$data["qutation_list"][$i]['Quotation_id']."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result(); 


      //$qutation_product = $this->db->query("select * from Quotation_Product a inner join  product_master b on (a.Product = b.product_id) where  Quotation_id = '".$qutation_val->Quotation_id."'")->result();
      $j=0;
      foreach($qutation_product as $qpp_list){
        $product_master_list = $this->db->query("select * from product_master where product_id =".$qpp_list->Product)->row();
        $data["qutation_list"][$i]['qutation_product_list'][$j]['Quotation_Product_id'] = $qpp_list->Quotation_Product_id;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['ListPrice'] = $qpp_list->ListPrice;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Product'] = $qpp_list->product_name;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Product_id'] = $qpp_list->Product;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Quantity'] = $qpp_list->Quantity;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Subtotal'] = $qpp_list->Subtotal;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Discount'] = $qpp_list->Discount;
       $j++;
      }
      $i++;
    }

    $this->response(array('code'=>'200','message'=>'Quotation List', 'result'=>$data,'requestname'=>$method));
  }
  function qutation_list($parameters,$method,$user_id){
    $Opportunity = $parameters['opportunity_id'] ;

     $qutation_list = $this->db->query("select * from Quotation where archieve != 1 and  Opportunity = ".$Opportunity)->result();
    
     $i=0;
     foreach($qutation_list as $qutation_val){
      $customer_details = $this->db->query("select * from customers where customer_id ='".$qutation_val->Customer."'")->row();
      $contact_list = $this->db->query("select * from contacts where contact_id ='".$qutation_val->Contact."'")->row();

      $data["qutation_list"][$i]['Quotation_id'] = $qutation_val->Quotation_id;
      $data["qutation_list"][$i]['QuotationversionID'] = $qutation_val->QuotationversionID;
      $data["qutation_list"][$i]['Opportunity'] = $qutation_val->Opportunity;
      $data["qutation_list"][$i]['QuotationDate'] = date("Y-m-d",strtotime($qutation_val->QuotationDate));
      $data["qutation_list"][$i]['ExpiryDate'] = date("Y-m-d",strtotime($qutation_val->ExpiryDate));
      $data["qutation_list"][$i]['Customer'] = $customer_details->CustomerName;
       $data["qutation_list"][$i]['Customer_id'] = $qutation_val->Customer;
       if(count($contact_list)>0){
        $data["qutation_list"][$i]['Contact_id'] = $contact_list->contact_id;
        $data["qutation_list"][$i]['Contact'] = $contact_list->FirstName." ".$contact_list->LastName;
       }else{
        $data["qutation_list"][$i]['Contact_id'] = "";
        $data["qutation_list"][$i]['Contact'] = "";
       }
       
      $data["qutation_list"][$i]['BillingStreet1'] = $qutation_val->BillingStreet1;
      $data["qutation_list"][$i]['Billingstreet2'] = $qutation_val->Billingstreet2;
      $data["qutation_list"][$i]['BillingCountry'] = $qutation_val->BillingCountry;
      $data["qutation_list"][$i]['StateProvince'] = $qutation_val->StateProvince;
      $data["qutation_list"][$i]['BillingCity'] = $qutation_val->BillingCity;
      $data["qutation_list"][$i]['BillingZipPostal'] = $qutation_val->BillingZipPostal;
      $data["qutation_list"][$i]['ShippingStreet1'] = $qutation_val->ShippingStreet1;
      $data["qutation_list"][$i]['Shippingstreet2'] = $qutation_val->Shippingstreet2;
      $data["qutation_list"][$i]['ShippingCountry'] = $qutation_val->ShippingCountry;
      $data["qutation_list"][$i]['ShippingStateProvince'] = $qutation_val->ShippingStateProvince;
      $data["qutation_list"][$i]['ShippingCity'] = $qutation_val->ShippingCity;
      $data["qutation_list"][$i]['ShippingZipPostal'] = $qutation_val->ShippingZipPostal;
      $data["qutation_list"][$i]['TotalPrice'] = $qutation_val->TotalPrice;
      $data["qutation_list"][$i]['Remarks'] = $qutation_val->Remarks;

      $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$qutation_val->Customer."'")->row();
      $qutation_product = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$data["qutation_list"][$i]['Quotation_id']."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result(); 



      //$qutation_product = $this->db->query("select * from Quotation_Product a inner join  product_master b on (a.Product = b.product_id) where  Quotation_id =".$data["qutation_list"][$i]['Quotation_id'])->result();
      $j=0;
      foreach($qutation_product as $qpp_list){
        $product_master_list = $this->db->query("select * from product_master where product_id =".$qpp_list->Product)->row();
        $data["qutation_list"][$i]['qutation_product_list'][$j]['Quotation_Product_id'] = $qpp_list->Quotation_Product_id;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['ListPrice'] = $qpp_list->ListPrice;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Product'] = $qpp_list->product_name;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Product_id'] = $qpp_list->Product;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Quantity'] = $qpp_list->Quantity;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Subtotal'] = $qpp_list->Subtotal;
       $data["qutation_list"][$i]['qutation_product_list'][$j]['Discount'] = $qpp_list->Discount;
       $j++;
      }
	  $aproval_chk = $this->db->query("select * from approval_process where approval_type='Quotation' and approval_type_id=". $qutation_val->Quotation_id)->result_array();
		if(count($aproval_chk)>0)
		{
			$cn = count($aproval_chk);
			$ai=0;
			$data["qutation_list"][$i]['approval_process'][$ai]['Step'] = "Step - 1";
			$data["qutation_list"][$i]['approval_process'][$ai]['Action'] = "--------";
			$data["qutation_list"][$i]['approval_process'][$ai]['date_time'] = $aproval_chk[$cn-1]['datetime'];
			$data["qutation_list"][$i]['approval_process'][$ai]['Status'] = "Submitted";
			$data["qutation_list"][$i]['approval_process'][$ai]['Assigned_to_name'] = role_name($aproval_chk[$cn-1]['assigned_to']);
			$data["qutation_list"][$i]['approval_process'][0]['Assigned_to'] = $aproval_chk[$cn-1]['assigned_to'];
			$data["qutation_list"][$i]['approval_process'][$ai]['Comments'] = "NA";
			$ai=1;
			foreach($aproval_chk as $aresult)
			{
				$data["qutation_list"][$i]['approval_process'][$ai]['Step'] = "Step - ".$ai+1;
				//if(($parameters['logged_user_role_id']==$aresult['assigned_to'])&&($aresult['comments']=='')){
						$data["qutation_list"][$i]['approval_process'][$ai]['Action'] = "Approve/Reject";
					//}					
					$data["qutation_list"][$i]['approval_process'][$ai]['date_time'] = $aresult['datetime'];
					if($aresult['status']=='1') {$data["qutation_list"][$i]['approval_process'][$ai]['Status'] ="Accepted";} else if($aresult['status']=='0'){$data["qutation_list"][$i]['approval_process'][$ai]['Status'] ="Rejected";}else if($aresult['status']=='2'){$data["qutation_list"][$i]['approval_process'][$ai]['Status'] ="Approved but Pending";}else{$data["qutation_list"][$i]['approval_process'][$ai]['Status'] ="Pending";}
					//if($parameters['logged_user_role_id']!=$aresult['assigned_to'])
						$data["qutation_list"][$i]['approval_process'][$ai]['Assigned_to_name'] = role_name($aresult['assigned_to']);
					$data["qutation_list"][$i]['approval_process'][$ai]['Assigned_to'] = $aresult['assigned_to'];
					$data["qutation_list"][$i]['approval_process'][$ai]['Comments'] = $aresult['comments'];
				
				
				$ai++;
			}
		}
		else
		{
			$data["qutation_list"][$i]['approval_process'] = array();
		}
	  
	  
      $i++;
     
     }
	 
      $this->response(array('code'=>'200','message'=>'Quotation List', 'result'=>$data,'requestname'=>$method));
     

  }

  public function qutation_pdf_mail($parameters,$method,$user_id){

    $id=$parameters['Quotation_id'];
    $type=$parameters['type'];


    $data_list['qutation_list'] = $this->db->query("select *,c.Mobile,c.Email,a.BillingStreet1,a.Billingstreet2,a.BillingCountry,a.StateProvince,a.BillingCity,a.BillingZipPostal,a.ShippingStreet1,a.Shippingstreet2,a.ShippingCountry,a.ShippingStateProvince,a.ShippingCity,a.ShippingZipPostal,c.Fax from Quotation a inner join customers b on (a.Customer = b.customer_id) inner join contacts c on (a.Contact = c.contact_id) where a.Quotation_id =".$id)->row();

     $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$data_list['qutation_list']->Customer."'")->row();
           $data_list['Quotation_Product_list'] = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$id."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result();


       // $data_list['Quotation_Product_list'] = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_id) where a.Quotation_id =".$id)->result();
         $this->load->library('M_pdf');
        $html = $this->load->view('qutation_pdf',$data_list, true);
        $pdfFilePath = "Quotations".time().".pdf";
        $pdfFiledownload = $pdfFilePath;
        $pdf = $this->m_pdf->load();
        $pdf->WriteHTML($html);
        if($id != NULL || $id !=""){
               $user_id=$this->session->userdata('logged_in')['id'];
              $param_val['qutation_pdf'] = $pdfFilePath;
              $param_val['modified_by'] = $user_id;
              $param_val['modified_date_time'] = date("Y-m-d H:i:s");
              $ok = $this->Generic_model->updateData('Quotation', $param_val, array('Quotation_id' => $id));
            }
      $pdf->Output("./images/Quotations/".$pdfFilePath,"F");  
        if($type == "pdf_generate"){
          
           $data['pdf_url'] = base_url("images/Quotations/".$pdfFiledownload);
            $this->response(array('code'=>'200','message'=>'pdf File Sent Successfully', 'result'=>$data,'requestname'=>$method));

        }else if($type == "mail_generate"){

        $customer_mail = $data_list['qutation_list']->Email;
            $from = "amar.palle@suprasoft.com";
            $to = $customer_mail;  //$to      = $dept_email_id;
            $subject = "qutations pdf";
            $message = "hello".$data_list['qutation_list']->FirstName." ".$data_list['qutation_list']->LastName;
           // $file_pa = base_url("images/Quotations/".$pdfFiledownload);
           // $fa ="helo";
            $file_pa = $pdfFiledownload;
            $result = $this->mail_send->quotation_send_pdf($from, $to, $subject,'', '',$file_pa,$message);

            
            if($result ==1){
                
                 $this->response(array('code'=>'200','message'=>'successfully sent mail', 'result'=>"",'requestname'=>$method));
                //echo"sent mail";                 
            }else{
                  $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
            }

        } 
  }

  public function tada_list($parameters,$method,$user_id){

    $final_users_id = $parameters['team_id'];
    $tada_list = $this->db->query("select * from  ta_da_allowances a inner join users b on (a.Name = b.user_id) where a.created_by in (".$final_users_id.") and a.archieve !=1 order by a.ta_da_id DESC")->result();


    if(count($tada_list)>0){
    $i=0;
    foreach($tada_list as $tada_val){

      $expenses_list = $this->db->query("select * from expenses where ta_da_id = '".$tada_val->ta_da_id."'")->result();
      $ta_amount = 0;
      $da_amount = 0;
      
      //$data['tada_list'][$i]['Towards_conveyance'] = $tada_val->Towards_conveyance;$boarding = 0;
      $phone = 0;
      $conveyance = 0;
      foreach($expenses_list as $values_exp){
        $expenses_type = $values_exp->expenses_type;
            if($expenses_type == "TA"){
              $ta_amount = $ta_amount+$values_exp->price;
            }else if($expenses_type == "DA"){
              $da_amount = $da_amount+$values_exp->price;
            }else if($expenses_type == "BOARDING"){
              $boarding = $boarding+$values_exp->price;
            }else if($expenses_type == "PHONE"){
                  $phone = $phone+$values_exp->price;
            }else if($expenses_type == "CONVEYANCE"){
              $conveyance = $conveyance+$values_exp->price;
            }else{  
              $value="0";
            }
      }

      $total_amount = $ta_amount+$da_amount+$boarding+$phone+$conveyance;
      $clamed = $total_amount -  $tada_val->AdvanceTakenon;
      if($tada_val->VerifiedBY != ""||$tada_val->VerifiedBY != null){
        $verfied_list = $this->db->query("select * from users where user_id=".$tada_val->VerifiedBY." AND status = 'Active'")->row();
      }
      $data['tada_list'][$i]['ta_da_id'] = $tada_val->ta_da_id;
      $data['tada_list'][$i]['ta_da_number'] = $tada_val->ta_da_number;
      $data['tada_list'][$i]['Fromdate'] = date("d-m-Y",strtotime($tada_val->Fromdate));
      $data['tada_list'][$i]['Todate'] = date("d-m-Y",strtotime($tada_val->Todate));
      $data['tada_list'][$i]['Name'] = $tada_val->name;
     // $data['tada_list'][$i]['Designation'] = $tada_val->Designation;
      $data['tada_list'][$i]['Amountclaimed'] = $clamed;
      $data['tada_list'][$i]['TowardsTA'] = $ta_amount;
      $data['tada_list'][$i]['Hotel_accommodation'] = $boarding;
      $data['tada_list'][$i]['TowardsDA'] = $da_amount;
       $data['tada_list'][$i]['Phone'] = $phone;
      $data['tada_list'][$i]['Conveyance'] = $conveyance;
      //$data['tada_list'][$i]['Others'] = $tada_val->Others;
      $data['tada_list'][$i]['Total'] = $total_amount;
      $data['tada_list'][$i]['AdvanceTakenon'] = $tada_val->AdvanceTakenon;
      $data['tada_list'][$i]['TABillPassedfor'] = $tada_val->TABillPassedfor;
      $data['tada_list'][$i]['BalanceDue'] = $tada_val->BalanceDue;
      $data['tada_list'][$i]['Verified'] = $tada_val->Verified;
       $data['tada_list'][$i]['VerifiedBY'] = $verfied_list->name;
      $i++;

    }
    $this->response(array('code'=>'200','message'=>'Ta Da Allownace', 'result'=>$data,'requestname'=>$method));
  }else{
   $this->response(array('code'=>'200','message'=>'Ta Da Allownace', 'result'=>$data,'requestname'=>$method));
  }

  }

  public function tada_dropdown($parameters,$method,$user_id){
   $users_list= $this->db->query("select * from users where profile !='".SUPERADMIN."' and archieve !=1 AND status = 'Active'")->result();
   $verfied_list = $this->db->query("select * from users where archieve != 1 AND status = 'Active'")->result();
     $i=0;
     foreach($users_list as $user_val){
      $data['users_list'][$i]['user_id'] = $user_val->user_id;
      $data['users_list'][$i]['name'] = $user_val->name;
      $i++;
     }
     $j=0;
     foreach($verfied_list as $verfied_val){
      $data['verfied_list'][$j]['user_id'] = $verfied_val->user_id;
      $data['verfied_list'][$j]['name'] = $verfied_val->name;
      $j++;
     }
     if(count($data)>0){
      $this->response(array('code'=>'200','message'=>'Ta Da dropdown', 'result'=>$data,'requestname'=>$method));
     }else{
        $this->response(array('code'=>'200','message'=>'Ta Da dropdown', 'result'=>$data,'requestname'=>$method));
     }

  }
  public function tada_insert($parameters,$method,$user_id){
     $checking_id = $this->db->query("select * from  ta_da_allowances order by ta_da_id DESC")->row();
            if($checking_id->ta_da_number == NULL || $checking_id->ta_da_number == ""){
                $ta_da_number_id = "EX-00001";
            }else{
                $qv_check = trim($checking_id->ta_da_number);
                $checking_qv_id =  substr($qv_check, 3);
                if($checking_qv_id == "99999"||$checking_qv_id == "999999"||$checking_qv_id =="9999999" || $checking_qv_id == "99999999" || $checking_qv_id == "999999999" || $checking_qv_id == "9999999999" ){
                    $QuotationversionID_last_inc = (++$checking_qv_id);
                    $ta_da_number_id= "EX-".$QuotationversionID_last_inc;
                }else{
                    $ta_da_number_id = (++$qv_check);
                } 

            }
            $parameters['Fromdate'];

            $param_1['ta_da_number'] = $ta_da_number_id;
            $param_1['Fromdate'] = date("Y-m-d",strtotime($parameters['Fromdate']));
            $param_1['Todate'] = date("Y-m-d",strtotime($parameters['Todate']));
            $param_1['Name'] = $user_id;
            //$param_1['Designation'] = $parameters['Designation'];
            //$param_1['Amountclaimed'] = $parameters['Amountclaimed'];
            //$param_1['TowardsTA'] =  $parameters['TowardsTA'];
            //$param_1['Hotel_accommodation'] =  $parameters['Hotel_accommodation'];
            //$param_1['TowardsDA'] =  $parameters['TowardsDA'];
            //$param_1['Towards_conveyance'] =  $parameters['Towards_conveyance'];
            //$param_1['Others'] =  $parameters['Others'];
            //$param_1['Total'] =  $parameters['Total'];
            // $param_1['AdvanceTakenon'] =  $parameters['AdvanceTakenon'];
            // $param_1['TABillPassedfor'] =  $parameters['TABillPassedfor'];
            // $param_1['BalanceDue'] =  $parameters['BalanceDue'];
            // $param_1['Verified'] = $parameters['Verified'];
            // $param_1['VerifiedBY'] =  $parameters['VerifiedBY'];
            $param_1['created_by'] = $user_id;
            $param_1['modified_by'] = $user_id;;
            $param_1['created_date_time'] = date("Y-m-d H:i:s");
            $param_1['modified_date_time'] = date("Y-m-d H:i:s");
            $ta_da_id = $this->Generic_model->insertDataReturnId("ta_da_allowances",$param_1);
            $data_1['ta_da_id'] = $ta_da_id;

            $return_data = $this->all_tables_records_view("tada",$ta_da_id);
            if($ta_da_id != "" || $ta_da_id != null){
              $this->response(array('code'=>'200','message'=>'TA DA Insert successfully', 'result'=>$return_data,'requestname'=>$method));
            }else{
              $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
            }

  }
  public function tada_edit($parameters,$method,$user_id){

    if(count($parameters)>0){
            $param_1['Fromdate'] = date("Y-m-d",strtotime($parameters['Fromdate']));
            $param_1['Todate'] = date("Y-m-d",strtotime($parameters['Todate']));
            $param_1['Name'] = $user_id;
            //$param_1['Designation'] = $parameters['Designation'];
            //$param_1['Amountclaimed'] = $parameters['Amountclaimed'];
            //$param_1['TowardsTA'] =  $parameters['TowardsTA'];
            //$param_1['Hotel_accommodation'] =  $parameters['Hotel_accommodation'];
            //$param_1['TowardsDA'] =  $parameters['TowardsDA'];
            //$param_1['Towards_conveyance'] =  $parameters['Towards_conveyance'];
            //$param_1['Others'] =  $parameters['Others'];
            //$param_1['Total'] =  $parameters['Total'];
            // $param_1['AdvanceTakenon'] =  $parameters['AdvanceTakenon'];
            // $param_1['TABillPassedfor'] =  $parameters['TABillPassedfor'];
            // $param_1['BalanceDue'] =  $parameters['BalanceDue'];
            // $param_1['Verified'] = $parameters['Verified'];
            // $param_1['VerifiedBY'] =  $parameters['VerifiedBY'];
            $param_1['modified_by'] = $user_id;
            $param_1['modified_date_time'] = date("Y-m-d H:i:s");
            $result=$this->Generic_model->updateData('ta_da_allowances',$param_1,array('ta_da_id'=>  $parameters['ta_da_id']));
             $return_data = $this->all_tables_records_view("tada",$parameters['ta_da_id']);
           if($result){
            $this->response(array('code'=>'200','message'=>'TA DA Updated Successfully', 'result'=>$return_data, 'requestname'=>$method));
           }else{
            $this->response(array('code'=>'404','message'=>'failed to upload'),200);
           }

    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }
  }
public function tada_delete($parameters,$method,$user_id){
  $ta_da_id  = $parameters['ta_da_id'];

    if($ta_da_id != "" || $ta_da_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
        $result=$this->Generic_model->updateData('ta_da_allowances',$param,array('ta_da_id'=>$ta_da_id));
        if($result ==1){
          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }
}

  public function expenses_list($parameters,$method,$user_id){
    $ta_da_id  = $parameters['ta_da_id'];
    if($ta_da_id != "" || $ta_da_id  != NULL){
      $expenses_list = $this->db->query("select * from expenses a inner join users b on (a.expenses_owner = b.user_id) where a.archieve != 1 and a.ta_da_id =".$ta_da_id)->result();
      $i=0;
      foreach($expenses_list as $exp_val){
        $image_list = $this->db->query("select * from expenses_files where expenses_id ='".$exp_val->expenses_id."'")->result();
        
        $data['expenses_list'][$i]['expenses_id'] = $exp_val->expenses_id;
        $data['expenses_list'][$i]['expenses_number'] = $exp_val->expenses_number;
        $data['expenses_list'][$i]['expenses_type'] = $exp_val->expenses_type;
        $data['expenses_list'][$i]['expenses_name'] = $exp_val->expenses_name;
        $data['expenses_list'][$i]['ta_da_id'] = $exp_val->ta_da_id;
        $data['expenses_list'][$i]['price'] = $exp_val->price;

        $data['expenses_list'][$i]['expensesdate'] = date("d-m-Y",strtotime($exp_val->expensesdate));
        $data['expenses_list'][$i]['expenses_owner'] = $exp_val->name;
        // $image_list = $exp_val->files;
        // $img_val = explode(",",$image_list);
        $j=0;
        foreach($image_list as $val){ 
          $url=  base_url('images/expenses/');
          $data['expenses_list'][$i]['files'][$j] =  $url."".$val->expenses_image;
          $j++;
        }
        $i++;
      }
      if(count($expenses_list)>0){
        $this->response(array('code'=>'200','message'=>'TA DA Insert successfully', 'result'=>$data,'requestname'=>$method));
      }else{
        $this->response(array('code'=>'200','message'=>'No Data in Database', 'result'=>$data,'requestname'=>$method));
      }
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }

  }
  public function expenses_delete($parameters,$method,$user_id){
     $expenses_id  = $parameters['expenses_id'];

    if($expenses_id != "" || $expenses_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
        $result=$this->Generic_model->updateData('expenses',$param,array('expenses_id'=>$expenses_id));
        if($result == 1){
          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }

  }

  public function contract_dropdown($parameters,$method,$user_id){

  	$final_users_id = $parameters['team_id'];
    $role_id= $parameters['role_id'];
   
     
      $customer_list = $this->db->query("select * from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
    
  	
  	$i=0;
  	foreach($customer_list as $customer_val){
  		$contact_list = $this->db->query("select * from contacts where archieve != 1 and Company =".$customer_val->customer_id)->result();
  		$data['customer_list'][$i]['customer_id']=$customer_val->customer_id;
  		$data['customer_list'][$i]['CustomerName']=$customer_val->CustomerName;
  		$j=0;
  		foreach($contact_list as $contact_val){
  			$data['customer_list'][$i]['contact_list'][$j]['contact_id'] = $contact_val->contact_id;
  			$data['customer_list'][$i]['contact_list'][$j]['contact_name'] = $contact_val->FirstName." ".$contact_val->LastName;
  			$j++;
  		}
  		$i++;	
  	}

         $this->response(array('code'=>'200','message'=>'No Data in Database', 'result'=>$data,'requestname'=>$method));
       
  }

  	public function contract_insert($parameters,$method,$user_id){
  		$checking_id = $this->db->query("select * from contract order by contract_id DESC")->row();
	    if($checking_id->ContractNumber == NULL || $checking_id->ContractNumber == ""){
	        $ContractNumber_id = "CT-00001";
	    }else{
	        $opp_check = trim($checking_id->ContractNumber);
	        $checking_op_id =  substr($opp_check, 3);
	        if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
	            $opp_id_last_inc = (++$checking_op_id);
	            $ContractNumber_id= "CT-".$opp_id_last_inc;
	        }else{
	            $ContractNumber_id = (++$opp_check);
	        } 
	    }
	    $Customer_id = $parameters['Customer'];

	      if($Customer_id != "" || $Customer_id != NULL){
              $customer_details = $this->db->query("select * from customers where  customer_id = ".$Customer_id)->row();
              if(count($customer_details)>0){
                $param_1['BillingAddress'] = $customer_details->BillingStreet1.",".$customer_details->Billingstreet2.",".$customer_details->BillingCountry.",".$customer_details->StateProvince.",".$customer_details->BillingCity.",".$customer_details->BillingZipPostal;
                $param_1['ShippingAddress'] = $customer_details->ShippingStreet1.",".$customer_details->Shippingstreet2.",".$customer_details->ShippingCountry.",".$customer_details->ShippingStateProvince.",".$customer_details->ShippingCity.",".$customer_details->ShippingZipPostal;
              }
            }

	  	$param_1['Customer'] = $parameters['Customer'];
	  	$param_1['ActivatedBy'] = $parameters['ActivatedBy'];
	  	$param_1['ActivatedDate'] = $parameters['ActivatedDate'];
	  	$param_1['CompanySignedBy'] = $parameters['CompanySignedBy'];
	  	$param_1['CompanySignedDate'] = date("y-m-d",strtotime($parameters['CompanySignedDate']));
	  	$param_1['ContractEndDate'] = date("y-m-d",strtotime($parameters['ContractEndDate']));
	  	$param_1['ContractName'] = $parameters['ContractName'];
	  	$param_1['ContractNumber'] = $ContractNumber_id;
	  	$param_1['ContractOwner'] = $user_id;
	  	$param_1['ContractStartDate'] = date("y-m-d",strtotime($parameters['ContractStartDate']));
	  	$param_1['ContractTerm'] = $parameters['ContractTerm'];
	  	$param_1['CustomerSignedBy'] = $parameters['CustomerSignedBy'];
	  	$param_1['CustomerSignedDate'] = date("y-m-d",strtotime($parameters['CustomerSignedDate'])); 
	  	$param_1['Description'] = $parameters['Description'];
	  	$param_1['total_amount'] = $parameters['total_amount'];
	  	$param_1['OwnerExpirationNotice'] = $parameters['OwnerExpirationNotice'];
	  	$param_1['SpecialTerms'] = $parameters['SpecialTerms'];
	  	$param_1['Status'] = $parameters['Status'];
	  	$param_1['created_by'] = $user_id;
	  	$param_1['modified_by'] = $user_id;
	  	$param_1['created_date_time'] = date("Y-m-d");
	  	$param_1['modified_date_time'] =date("Y-m-d");
	  	
	  	
	  	$contract_id = $this->Generic_model->insertDataReturnId("contract",$param_1);
	  	if($contract_id != "" || $contract_id != NULL){
        $user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
        $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();

        $latest_val['module_id'] = $contract_id;
        $latest_val['module_name'] = "Contract";
        $latest_val['user_id'] = $user_id;
        $latest_val['created_date_time'] = date("Y-m-d H:i:s");
        $this->Generic_model->insertData("update_table",$latest_val);



        if(count($user_list)>0){
            $push_noti['fcmId_android'] = $user_list->fcmId_android;
            $push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
            }else{
            $push_noti['fcmId_android'] ="";
            $push_noti['fcmId_iOS'] = "";   
            }
            if(count($user_report_to) >0){
            $push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
            $push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
            }else{
            $push_noti['fcmId_android_report_to'] = "";
            $push_noti['fcmId_iOS_report_to'] = "";
            }
          $push_noti['contract_id'] = $contract_id;
          $push_noti['user_id'] = $user_id;
          $push_noti['subject'] = "A new Contract has been created successfully  ContractID  : ".$ContractNumber_id." ContractName  : ".$parameters['ContractName']." Contract Start Date  : ".date("d-m-Y",strtotime($parameters["ContractStartDate"]))." Contract End Date : ".date("d-m-Y",strtotime($parameters["ContractEndDate"]))." CustomerName : ".$customer_list->CustomerName."";
          $this->PushNotifications->Contract_notifications($push_noti);
                



                $contract_product = count($parameters['contract_product']);
			    if($parameters["contract_product"][0]['ListPrice'] != "" || $parameters["contract_product"][0]['ListPrice'] != NULL){

			        for($k=0;$k<$contract_product;$k++){
			          $Product_id = $parameters["contract_product"][$k]['Product'];
				          if($Product_id == NULL || $Product_id == ""){
				          	$product_code="";
				          }else{

				          	$product_details = $this->db->query("select * from product_master where     product_id =".$Product_id)->row();
				          	 $product_code=$product_details->product_code;

				          }
			              $param_2['Contract'] = $contract_id;
			              $param_2['ListPrice'] = $parameters["contract_product"][$k]['ListPrice'];
			              $param_2['Product'] = $parameters["contract_product"][$k]['Product'];
			              $param_2['Productcode'] = $product_code;
			              $param_2['Quantity'] = $parameters["contract_product"][$k]['Quantity'];
			              $param_2['Discount'] = $parameters["contract_product"][$k]['Discount'];
			              $param_2['Subtotal'] = $parameters["contract_product"][$k]['Subtotal'];
			              $param_2['created_by'] =$user_id;
			              $param_2['modified_by'] =$user_id;
			              $param_2['created_date_time'] =date("Y-m-d H:i:s");
			              $param_2['modified_date_time'] =date("Y-m-d H:i:s");
			              
			              $ok = $this->Generic_model->insertData("contract_products",$param_2);
			          
			        }

              
                  $contract_list = $this->db->query("select * from contract_products a inner join product_master b on (a.Product = b.product_id) where a.Contract = ".$contract_id)->result();
                  $customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Customer'])->row();
                  $email = $user_list->email;
                  $to = $email;  //$to      = $dept_email_id;
                  $subject = "New Contract Created";
                  $data['name'] = ucwords($user_list->name);

               $data['message'] = "<p> A new Contract has been created successfully <br/><br/><b> ContractID </b> : ".$ContractNumber_id." <br/> <b>ContractName </b> : ".$parameters['ContractName']."<br/> <b>Contract Start Date </b> : ".date("d-m-Y",strtotime($parameters["ContractStartDate"]))."<br/> <b>Contract End Date</b> : ".date("d-m-Y",strtotime($parameters["ContractEndDate"]))."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName."</p> <br/><br/> <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
                        </tr></thead>";

                       if(count($contract_list) >0){
                          foreach($contract_list as $con_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->ListPrice."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Discount."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Subtotal."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  
                        $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

                       
                    if(count($user_report_to) >0){
                      $email = $user_report_to->email;
                      $to = $email;  //$to      = $dept_email_id;
                      $subject = "New Contract Created";
                      $data['name'] = ucwords($user_report_to->name);
                      $data['message'] = "<p> A new Contract has been created successfully by ".ucwords($user_list->name)."<br/><br/><b> ContractID </b> : ".$ContractNumber_id." <br/> <b>ContractName </b> : ".$parameters['ContractName']."<br/> <b>Contract Start Date </b> : ".date("d-m-Y",strtotime($parameters["ContractStartDate"]))."<br/> <b>Contract End Date</b> : ".date("d-m-Y",strtotime($parameters["ContractEndDate"]))."<br/> <b>CustomerName </b> : ".$customer_list->CustomerName."</p> <br/><br/> <table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
                      <thead>
                       <tr >
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
                        <th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
                        </tr></thead>";

                       if(count($contract_list) >0){
                          foreach($contract_list as $con_values){
                      $data['message'].=  "<tbody><tr>

                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->product_name."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->ListPrice."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Quantity."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Discount."</td>
                        <td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$con_values->Subtotal."</td></tr></tbody>";
                      }

                        }

                        $data['message'].= "</table><br/>";  

                       $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
                    }

                    $param_noti['notiffication_type'] = "Contract";
                    $param_noti['notiffication_type_id'] = $contract_id;
                    $param_noti['user_id'] = $user_id;
                    $param_noti['subject'] = " A new Contract has been created successfully  ContractID  : ".$ContractNumber_id." ContractName  : ".$parameters['ContractName']." Contract Start Date  : ".date("d-m-Y",strtotime($parameters["ContractStartDate"]))." Contract End Date : ".date("d-m-Y",strtotime($parameters["ContractEndDate"]))." CustomerName : ".$customer_list->CustomerName."";
                    $this->Generic_model->insertData("notiffication",$param_noti);








					$contract_list = $this->db->query("select *,a.created_by as se from contract a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.contract_id =".$contract_id)->row();							
					  $nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$contract_list->se)->row();
						
					  $rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					  if(count($rol_discount)>0)
					  {
						  $dchk=0;
							$product_id = count($parameters["contract_product"]);
							for($j=0;$j<$product_id;$j++)
							{
								if($rol_discount->dis_limit<$parameters["contract_product"][$j]['Discount'])
									$dchk++;								
							}
							if($dchk==0)
							{
								//echo $sales_order_id;exit;
								$this->db->query("update contract set a_status='Approved' where contract_id=".$contract_id);
							}
							else{
								//echo "false".$sales_order_id;exit;
								$data_ins['approval_type'] = 'Contract';
								$data_ins['approval_type_id'] = $contract_id;
								$data_ins['status'] = 3;
								$data_ins['datetime'] = date('Y-m-d H:i:s');
								$data_ins['assigned_to'] = $nex_report->role_reports_to;
								$data_ins['comments'] = '';
								$data_ins['created_by'] = $user_id;
								$data_ins['modified_by'] = $user_id;
								$data_ins['created_datetime'] = date('Y-m-d H:i:s');
								$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
								$ok = $this->Generic_model->insertData("approval_process",$data_ins);
								if($ok)
									$this->db->query("update contract set a_status='Pending' where contract_id=".$contract_id);
							}
					  }


					 $data_val['contract_id'] = $contract_id;

           $return_data = $this->all_tables_records_view("contract",$contract_id);
			        $this->response(array('code'=>'200','message'=>'contract successfully inserted','result'=>$return_data,'requestname'=>$method));
			  	}else{
             $return_data = $this->all_tables_records_view("contract",$contract_id);
			    	 $this->response(array('code'=>'200','message'=>'contract successfully inserted','result'=>$return_data,'requestname'=>$method));
		    	}
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
	public function contract_edit($parameters,$method,$user_id){
		$contract_id = $parameters['contract_id'];
		$Customer_id = $parameters['Customer'];

	      if($Customer_id != "" || $Customer_id != NULL){
              $customer_details = $this->db->query("select * from customers where  customer_id = ".$Customer_id)->row();
              if(count($customer_details)>0){
                $param_1['BillingAddress'] = $customer_details->BillingStreet1.",".$customer_details->Billingstreet2.",".$customer_details->BillingCountry.",".$customer_details->StateProvince.",".$customer_details->BillingCity.",".$customer_details->BillingZipPostal;
                $param_1['ShippingAddress'] = $customer_details->ShippingStreet1.",".$customer_details->Shippingstreet2.",".$customer_details->ShippingCountry.",".$customer_details->ShippingStateProvince.",".$customer_details->ShippingCity.",".$customer_details->ShippingZipPostal;
              }
            }

	  	$param_1['Customer'] = $parameters['Customer'];
	  	$param_1['ActivatedBy'] = $parameters['ActivatedBy'];
	  	$param_1['ActivatedDate'] = $parameters['ActivatedDate'];
	  	$param_1['CompanySignedBy'] = $parameters['CompanySignedBy'];
	  	$param_1['CompanySignedDate'] = date("y-m-d",strtotime($parameters['CompanySignedDate']));
	  	$param_1['ContractEndDate'] = date("y-m-d",strtotime($parameters['ContractEndDate']));
	  	$param_1['ContractName'] = $parameters['ContractName'];
	  	$param_1['ContractStartDate'] = date("y-m-d",strtotime($parameters['ContractStartDate']));
	  	$param_1['ContractTerm'] = $parameters['ContractTerm'];
	  	$param_1['CustomerSignedBy'] = $parameters['CustomerSignedBy'];
	  	$param_1['CustomerSignedDate'] = date("y-m-d",strtotime($parameters['CustomerSignedDate'])); 
	  	$param_1['Description'] = $parameters['Description'];
	  	$param_1['total_amount'] = $parameters['total_amount'];
	  	$param_1['OwnerExpirationNotice'] = $parameters['OwnerExpirationNotice'];
	  	$param_1['SpecialTerms'] = $parameters['SpecialTerms'];
	  	$param_1['Status'] = $parameters['Status'];
	  	$param_1['modified_by'] = $user_id;
	  	$param_1['modified_date_time'] =date("Y-m-d");
	  	 $result=$this->Generic_model->updateData('contract',$param_1,array('contract_id'=>$contract_id));      
	  	 if($result == 1){

        $check_update_list = $this->db->query("select * from update_table where module_id ='".$contract_id."' and module_name ='Contract'")->row();
          if(count($check_update_list)>0){
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $contract_id,'module_name'=>'Contract'));
          }else{
            $latest_val['module_id'] = $contract_id;
            $latest_val['module_name'] = "Contract";
            $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $this->Generic_model->insertData("update_table",$latest_val);
          }


	  	 	if($parameters["contract_product"][0]['ListPrice'] != "" || $parameters["contract_product"][0]['ListPrice'] != NULL){
	  	 		$contract_product = count($parameters['contract_product']);
			        for($k=0;$k<$contract_product;$k++){
			          $Product_id = $parameters["contract_product"][$k]['Product'];
			          	if($Product_id == NULL || $Product_id == ""){
			          		$product_code="";
		         		}else{

			          		$product_details = $this->db->query("select * from product_master where     product_id =".$Product_id)->row();
			          		 $product_code=$product_details->product_code;

			          	}

				  	 	      $param_2['Contract'] = $contract_id;
			              $param_2['ListPrice'] = $parameters["contract_product"][$k]['ListPrice'];
			              $param_2['Product'] = $parameters["contract_product"][$k]['Product'];
			              $param_2['Productcode'] = $product_code;
			              $param_2['Quantity'] = $parameters["contract_product"][$k]['Quantity'];
			              $param_2['Discount'] = $parameters["contract_product"][$k]['Discount'];
			              $param_2['Subtotal'] = $parameters["contract_product"][$k]['Subtotal'];
			              $param_2['modified_by'] =$user_id;
			              $param_2['modified_date_time'] =date("Y-m-d H:i:s");
			              $checking_id = $parameters["contract_product"][$k]['product_contract_id'];
			            if($checking_id == "" || $checking_id == NULL){
		                      $param_2['created_by'] =$user_id;
		                      $param_2['created_date_time'] =date("Y-m-d H:i:s");
		                        $this->Generic_model->insertData("contract_products",$param_2);
	                    }else{
	                       // $param_2['id'] = $checking_id;
	                        $this->Generic_model->updateData('contract_products', $param_2, array(' product_contract_id' => $checking_id));
	                    }
	                    
			        }
					$contract_list = $this->db->query("select *,a.created_by as se from contract a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.contract_id =".$contract_id)->row();							
					  $nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$contract_list->se)->row();
						
					  $rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					  if(count($rol_discount)>0)
					  {
						  $dchk=0;
							$product_id = count($this->input->post("product_id[]"));
							for($j=0;$j<$product_id;$j++)
							{
								if($rol_discount->dis_limit<$this->input->post("Discount[$j]"))
									$dchk++;								
							}
							if($dchk==0)
							{
								//echo $sales_order_id;exit;
								$this->db->query("update contract set a_status='Approved' where contract_id=".$contract_id);
							}
							else{
								//echo "false".$sales_order_id;exit;
								$data_ins['approval_type'] = 'Contract';
								$data_ins['approval_type_id'] = $contract_id;
								$data_ins['status'] = 3;
								$data_ins['datetime'] = date('Y-m-d H:i:s');
								$data_ins['assigned_to'] = $nex_report->role_reports_to;
								$data_ins['comments'] = '';
								$data_ins['created_by'] = $user_id;
								$data_ins['modified_by'] = $user_id;
								$data_ins['created_datetime'] = date('Y-m-d H:i:s');
								$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
								$ok = $this->Generic_model->insertData("approval_process",$data_ins);
								if($ok)
									$this->db->query("update contract set a_status='Pending' where contract_id=".$contract_id);
							}
					  }
             $return_data = $this->all_tables_records_view("contract",$contract_id);
              $this->response(array('code'=>'200','message'=>'contract successfully updated','result'=>$return_data,'requestname'=>$method));
			    }else{
             $return_data = $this->all_tables_records_view("contract",$contract_id);
			    	$this->response(array('code'=>'200','message'=>'contract successfully updated','result'=>$return_data,'requestname'=>$method));
			    }
	  	}else{
	  		$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	  		
	  	}
	  	
	}

	public function contract_list($parameters,$method,$user_id){

    $final_users_id = $parameters['team_id'];
		
		$contract_list = $this->db->query("select *,a.Description,a.created_date_time as createdDateTime from contract a inner join customers b on (a.Company = b.customer_id)  where a.ContractOwner  in (".$final_users_id.") and a.archieve != 1 order by a.contract_id DESC")->result();

		$i=0;
		foreach($contract_list as $contract_val){
			$CompanySignedBy_list = $this->db->query("select * from  users where user_id =".$contract_val->CompanySignedBy)->row();

			
			$ContractOwner_list = $this->db->query("select * from users where user_id =".$contract_val->ContractOwner." AND status = 'Active'")->row();
			if(count($CompanySignedBy_list)>0){
				$CompanySignedBy = $CompanySignedBy_list->name;
        $CompanySignedBy_id = $CompanySignedBy_list->user_id;
			}else{
				$CompanySignedBy = "";
        $CompanySignedBy_id  = "";
			}
			if(count($ContractOwner_list)>0){
				$ContractOwner = $ContractOwner_list->name;
			}else{
				$ContractOwner ="";
			}
			$data['contract_list'][$i]['contract_id'] =$contract_val->contract_id;
			$data['contract_list'][$i]['Customer'] =$contract_val->CustomerName;
      $data['contract_list'][$i]['customer_id'] =$contract_val->customer_id;
			$data['contract_list'][$i]['ActivatedBy'] =$contract_val->ActivatedBy;
			$data['contract_list'][$i]['ActivatedDate'] =date("d-m-Y",strtotime($contract_val->ActivatedDate));
			$data['contract_list'][$i]['BillingAddress'] =$contract_val->BillingAddress;
			$data['contract_list'][$i]['ShippingAddress'] =$contract_val->ShippingAddress;
      if($contract_val->CustomerSignedBy == ""||$contract_val->CustomerSignedBy == null ){
         $data['contract_list'][$i]['CustomerSignedBy'] ="";
          $data['contract_list'][$i]['CustomerSignedBy_id'] =" ";
      }else{
        $contact_list =  $this->db->query("select * from contacts where contact_id =".$contract_val->CustomerSignedBy)->row();
        if(count($contact_list) >0){
          $data['contract_list'][$i]['CustomerSignedBy'] =$contact_list->FirstName." ".$contact_list->LastName;
          $data['contract_list'][$i]['CustomerSignedBy_id'] =$contact_list->contact_id;
        }else{
          $data['contract_list'][$i]['CustomerSignedBy'] ="";
          $data['contract_list'][$i]['CustomerSignedBy_id'] =" ";
        }

      }
      $data['contract_list'][$i]['CustomerSignedDate'] =date("d-m-Y",strtotime($contract_val->CustomerSignedDate));
      
			$data['contract_list'][$i]['CompanySignedBy'] = $CompanySignedBy;
      $data['contract_list'][$i]['CompanySignedBy_id'] = $CompanySignedBy_id;
			$data['contract_list'][$i]['CompanySignedDate'] =date("d-m-Y",strtotime($contract_val->CompanySignedDate));
			$data['contract_list'][$i]['ContractName'] =$contract_val->ContractName;
			$data['contract_list'][$i]['ContractNumber'] =$contract_val->ContractNumber;
			$data['contract_list'][$i]['ContractStartDate'] =date("d-m-Y",strtotime($contract_val->ContractStartDate));
			$data['contract_list'][$i]['ContractEndDate'] =date("d-m-Y",strtotime($contract_val->ContractEndDate));
			$data['contract_list'][$i]['ContractOwner'] =$ContractOwner;
      $data['contract_list'][$i]['ContractTerm'] =$contract_val->ContractTerm;
			
			$data['contract_list'][$i]['Description'] =$contract_val->Description;
			$data['contract_list'][$i]['total_amount'] =$contract_val->total_amount;
			$data['contract_list'][$i]['OwnerExpirationNotice'] =$contract_val->OwnerExpirationNotice;
			$data['contract_list'][$i]['SpecialTerms'] =$contract_val->SpecialTerms;
			$data['contract_list'][$i]['created_date_time'] =$contract_val->createdDateTime;
			$data['contract_list'][$i]['Status'] =$contract_val->Status;

			$contract_product_list = $this->db->query("select * from contract_products a inner join product_master b on (a.Product = b.product_id) where a.Contract =".$contract_val->contract_id)->result();
			if(count($contract_product_list)>0){
				$j=0;
				foreach($contract_product_list as $cp_list){
					$data['contract_list'][$i]['contract_product'][$j]['product_contract_id'] = $cp_list->product_contract_id;
					$data['contract_list'][$i]['contract_product'][$j]['Product'] = $cp_list->product_name;
          $data['contract_list'][$i]['contract_product'][$j]['Product_id'] = $cp_list->Product;
					$data['contract_list'][$i]['contract_product'][$j]['ListPrice'] = $cp_list->ListPrice;
					$data['contract_list'][$i]['contract_product'][$j]['Quantity'] = $cp_list->Quantity;
					$data['contract_list'][$i]['contract_product'][$j]['Discount'] = $cp_list->Discount;
					$data['contract_list'][$i]['contract_product'][$j]['Subtotal'] = $cp_list->Subtotal;
					$j++;
				}
			}else{
				$data['contract_list'][$i]['contract_product'] = array();
			}
			$aproval_chk = $this->db->query("select * from approval_process where approval_type='Contract' and approval_type_id=". $contract_val->contract_id)->result_array();
		if(count($aproval_chk)>0)
		{
			$cn = count($aproval_chk);
			$ai=0;
			$data["contract_list"][$i]['approval_process'][$ai]['Step'] = "Step - 1";
			$data["contract_list"][$i]['approval_process'][$ai]['Action'] = "--------";
			$data["contract_list"][$i]['approval_process'][$ai]['date_time'] = $aproval_chk[$cn-1]['datetime'];
			$data["contract_list"][$i]['approval_process'][$ai]['Status'] = "Submitted";
			$data["contract_list"][$i]['approval_process'][$ai]['Assigned_to_name'] = role_name($aproval_chk[$cn-1]['assigned_to']);
			$data["contract_list"][$i]['approval_process'][0]['Assigned_to'] = $aproval_chk[$cn-1]['assigned_to'];
			$data["contract_list"][$i]['approval_process'][$ai]['Comments'] = "NA";
			$ai=1;
			foreach($aproval_chk as $aresult)
			{
				$data["contract_list"][$i]['approval_process'][$ai]['Step'] = "Step - ".$ai+1;
				//if(($parameters['logged_user_role_id']==$aresult['assigned_to'])&&($aresult['comments']=='')){
						$data["contract_list"][$i]['approval_process'][$ai]['Action'] = "Approve/Reject";
					//}
					
					$data["contract_list"][$i]['approval_process'][$ai]['date_time'] = $aresult['datetime'];
					if($aresult['status']=='1') {$data["contract_list"][$i]['approval_process'][$ai]['Status'] ="Accepted";} else if($aresult['status']=='0'){$data["contract_list"][$i]['approval_process'][$ai]['Status'] ="Rejected";}else if($aresult['status']=='2'){$data["contract_list"][$i]['approval_process'][$ai]['Status'] ="Approved but Pending";}else{$data["contract_list"][$i]['approval_process'][$ai]['Status'] ="Pending";}
					//if($parameters['logged_user_role_id']!=$aresult['assigned_to'])
						$data["contract_list"][$i]['approval_process'][$ai]['Assigned_to_name'] = role_name($aresult['assigned_to']);
					$data["contract_list"][$i]['approval_process'][$ai]['Assigned_to'] = $aresult['assigned_to'];
					$data["contract_list"][$i]['approval_process'][$ai]['Comments'] = $aresult['comments'];
				
				
				$ai++;
			}
		}
		else
		{
			$data["contract_list"][$i]['approval_process'] = array();
		}
			$i++;
		}
		
    if(count($data)>0){
		$this->response(array('code'=>'200','message'=>'contract List','result'=>$data,'requestname'=>$method));
    }else{
      $this->response(array('code'=>'200','message'=>'contract are Empty','result'=>null,'requestname'=>$method));
    }

	}

	 public function contract_delete($parameters,$method,$user_id){
    $contract_id=$parameters['contract_id'];
     $contract_record=$this->db->query('select * from contract where contract_id='.$contract_id)->row();
   	if($contract_id!="" || $contract_id!=NULL){
   	   $param['archieve']=1;
   	   $param['modified_by']=$user_id;
   	   $param['modified_date_time']=date("Y-m-d H:i:S");
   	  $result=$this->Generic_model->updateData('contract',$param, array('contract_id'=>$contract_id));
   	  if($result==1){

         $latest_val['user_id'] = $user_id;
          $latest_val['created_date_time'] = date("Y-m-d H:i:s");
          $latest_val['delete_status'] = "1";
          $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $contract_id,'module_name'=>'Contract'));

   	     $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
  	  }else{
  	 	 $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
  	   }
     }else{
         $this->response(array('code'=>'404','message' => 'There is no record with the given ID'), 200);
     }
  }

	public function contract_approval($parameters,$method,$user_id){
		//$user_id=$this->session->userdata('logged_in')['id'];
    $user_id = $user_id;		
		$id = $parameters['contract_id'];	
		$data_ins_chk = $this->db->query("select * from approval_process where approval_type_id=".$id." and approval_type='Contract' and status=3 order by ap_id desc")->result_array();
		//echo $this->db->last_query();exit;
		if(count($data_ins_chk)>0){
			
			$role_id = $parameters['logged_user_role_id'];
			$comments = $parameters['comm'];
			$type = $parameters['type'];
			if($type=='Accept'){
				$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$role_id)->row();
				if(count($rol_discount)>0)
				{
					$products_info = $this->db->query("select * from contract_products where Contract=".$id)->result_array();
					$dchk=0;
					foreach($products_info as $result)
					{
						if($rol_discount->dis_limit<$result['Discount'])
						{
							$dchk++;
						}
					}
					
					if($dchk==0)
					{
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=1,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update contract set a_status='Approved' where contract_id=".$id);
					}
					else{
						$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$user_id)->row();
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=2,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update contract set status='Approved but Pending' where contract_id=".$id);
						$data_ins['approval_type'] = 'Contract';
						$data_ins['approval_type_id'] = $id;
						$data_ins['status'] = 3;
						$data_ins['datetime'] = date('Y-m-d H:i:s');
						$data_ins['assigned_to'] = $nex_report->role_reports_to;
						$data_ins['comments'] = '';
						$data_ins['created_by'] = $user_id;
						$data_ins['modified_by'] = $user_id;
						$data_ins['created_datetime'] = date('Y-m-d H:i:s');
						$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
						$ok = $this->Generic_model->insertData("approval_process",$data_ins);
					}
				}
			}
			else{
				$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=0,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
				//echo $this->db->last_query();exit;
				$this->db->query("update contract set status='Rejected' where contract_id=".$id);
			}
		}else
		{
			
			$data['sales_order_list'] = $this->db->query("select *,a.created_by as se from contract a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.contract_id =".$id)->row();
		
			$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$data['sales_order_list']->se)->row();
			//echo $this->db->last_query();exit;
			$data_ins['approval_type'] = 'Contract';
			$data_ins['approval_type_id'] = $id;
			$data_ins['status'] = 3;
			$data_ins['datetime'] = date('Y-m-d H:i:s');
			$data_ins['assigned_to'] = $nex_report->role_reports_to;
			$data_ins['comments'] = '';
			$data_ins['created_by'] = $user_id;
			$data_ins['modified_by'] = $user_id;
			$data_ins['created_datetime'] = date('Y-m-d H:i:s');
			$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
			$ok = $this->Generic_model->insertData("approval_process",$data_ins);
			if($ok)
				$this->db->query("update contract set status='Pending' where contract_id=".$id);
		//echo $this->db->last_query();
		}
		$this->response(array('code'=>'200','message'=>'contract_approval','result'=>$data,'requestname'=>$method));
	}
	
	
	public function salesorder_list($parameters,$method,$user_id){
	
		$final_users_id = $parameters['team_id'];
	
		$sales_order_list = $this->db->query("select a.*,b.*,c.CustomerName as delivered_by_customer_name, d.division_name, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks, u.name as OwnerName from sales_order a inner join customers b on (a.Customer = b.customer_id) left join customers c on (a.DeliveredBy_customer_id = c.customer_id) left join division_master d on (a.Division = d.division_master_id) left join users u on (a.created_by = u.user_id) where a.created_by in (".$final_users_id.") and a.archieve !=1 order by a.sales_order_id DESC")->result();
		
		$i=0;
		foreach($sales_order_list as $so_list){
			
			$Soldtopartycode = $so_list->Soldtopartycode;
			if($Soldtopartycode == "" || $Soldtopartycode == NULL){
				$Soldtopartycode_val = "";
			}else{
				$Soldtopartycode_list = $this->db->query("select * from contacts where contact_id =".$Soldtopartycode)->row();
				$Soldtopartycode_val= $Soldtopartycode_list->FirstName." ".$Soldtopartycode_list->LastName;
			}
			
			$Shiptopartycode = $so_list->Shiptopartycode;
			if($Shiptopartycode == "" || $Shiptopartycode == NULL){
				$Shiptopartycode_val = "";
			}else{
				$Shiptopartycode_list = $this->db->query("select * from contacts where contact_id =".$Shiptopartycode)->row();
				$Shiptopartycode_val= $Shiptopartycode_list->FirstName." ".$Shiptopartycode_list->LastName;				
			}
			
			$BilltopartyCode = $so_list->BilltopartyCode;
			if($BilltopartyCode == "" || $BilltopartyCode == NULL){
				$BilltopartyCode_val = "";
			}else{
				$BilltopartyCode_list = $this->db->query("select * from contacts where contact_id =".$BilltopartyCode)->row();
				$BilltopartyCode_val= $BilltopartyCode_list->FirstName." ".$BilltopartyCode_list->LastName;
			}

			if($so_list->contract_id != "" || $so_list->contract_id != NULL || $so_list->contract_id != "0" ){
				$contract_list = $this->db->query("select * from contract where contract_id ='".$so_list->contract_id."'")->row();
				if(count($contract_list)>0){
					$data["sales_order_list"][$i]['contract_Name'] = $contract_list->ContractName;
					$data["sales_order_list"][$i]['contracts_id'] = $contract_list->contract_id;
				}else{
					$data["sales_order_list"][$i]['contract_Name'] = "";
					$data["sales_order_list"][$i]['contracts_id'] = "";
				}
			}else{
				$data["sales_order_list"][$i]['contract_Name'] = "";
				$data["sales_order_list"][$i]['contracts_id'] = "";
			}
		
			$data["sales_order_list"][$i]['sales_order_id'] = $so_list->sales_order_id;
			$data["sales_order_list"][$i]['sales_order_number'] = $so_list->sales_order_number;
			$data["sales_order_list"][$i]['OwnerName'] = $so_list->OwnerName;
			$data["sales_order_list"][$i]['Customer'] = $so_list->CustomerName;
			$data["sales_order_list"][$i]['customer_id'] = $so_list->Customer;
			$data["sales_order_list"][$i]['OrderType'] = $so_list->OrderType;
			$data["sales_order_list"][$i]['sales_order_dealer_contact_id'] = $so_list->sales_order_dealer_contact_id;
			$data["sales_order_list"][$i]['orc_details'] = $so_list->orc_details;
			$data["sales_order_list"][$i]['OrderType_form'] = $so_list->OrderType_form;
			$data["sales_order_list"][$i]['SalesOrganisation'] = $so_list->SalesOrganisation;
			$data["sales_order_list"][$i]['DistributionChannel'] = $so_list->DistributionChannel;
			$data["sales_order_list"][$i]['date_of_delivery'] = $so_list->date_of_delivery;
			$data["sales_order_list"][$i]['delivered_by'] = $so_list->DeliveredBy;
			$data["sales_order_list"][$i]['delivered_by_customer_id'] = $so_list->DeliveredBy_customer_id;
			$data["sales_order_list"][$i]['delivered_by_customer_name'] = $so_list->delivered_by_customer_name;
			$data["sales_order_list"][$i]['Division'] = $so_list->Division;
			$data["sales_order_list"][$i]['division_name'] = $so_list->division_name;
			$data["sales_order_list"][$i]['Remarks'] = $so_list->SalesOrderRemarks;
			$data["sales_order_list"][$i]['Soldtopartycode'] = $Soldtopartycode_val;
			$data["sales_order_list"][$i]['Soldtopartycode_id'] = $so_list->Soldtopartycode;
			$data["sales_order_list"][$i]['Shiptopartycode'] = $Shiptopartycode_val;
			$data["sales_order_list"][$i]['Shiptopartycode_id'] = $so_list->Shiptopartycode;
			$data["sales_order_list"][$i]['BilltopartyCode'] = $BilltopartyCode_val;
			$data["sales_order_list"][$i]['BilltopartyCode_id'] = $so_list->BilltopartyCode;
			$data["sales_order_list"][$i]['expected_order_dispatch_date'] = $so_list->expected_order_dispatch_date;
			$data["sales_order_list"][$i]['Ponumber'] = $so_list->Ponumber;
			$data["sales_order_list"][$i]['CashDiscount'] = $so_list->CashDiscount;
			$data["sales_order_list"][$i]['SchemeDiscount'] = $so_list->SchemeDiscount;
			$data["sales_order_list"][$i]['QuntityDiscount'] = $so_list->QuntityDiscount;
			$data["sales_order_list"][$i]['withoutdiscountamount'] = $so_list->withoutdiscountamount;
			$data["sales_order_list"][$i]['Freight'] = $so_list->Freight;
			$data['sales_order_list'][$i]['freight_amount'] = $so_list->freight_amount;
			$data["sales_order_list"][$i]['discountAmount'] = $so_list->discountAmount;
			$data["sales_order_list"][$i]['order_status'] = $so_list->order_status;
			$data["sales_order_list"][$i]['order_status_comments'] = $so_list->order_status_comments;
			$data["sales_order_list"][$i]['created_date_time'] = $so_list->CreatedDateTime;
			$data["sales_order_list"][$i]['purchase_image'] = $so_list->purchase_image;
			$data["sales_order_list"][$i]['complaints_image'] = $so_list->complaints_image;
			$data["sales_order_list"][$i]['payment_image'] = $so_list->payment_image;
			$data["sales_order_list"][$i]['transfer_image'] = $so_list->transfer_image;
			$data["sales_order_list"][$i]['Total'] = $so_list->Total;
			
			$sales_order_product = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
			
			if(count($sales_order_product) >0){
				$j=0;
				foreach($sales_order_product as $sop_list){
					$plant_list = $this->db->query("select * from plant_master where plantid = '".$sop_list->plant_id."'")->row();
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['sales_order_products_id'] = $sop_list->sales_order_products_id;
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['Product'] = $sop_list->product_name;
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['Product_id'] = $sop_list->Product;
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['ListPrice'] = $sop_list->ListPrice;
					if($sop_list->plant_id == "" || $sop_list->plant_id == NULL){
						$data['sales_order_list'][$i]['sales_order_product_list'][$j]['plant_id'] = "";
						$data['sales_order_list'][$i]['sales_order_product_list'][$j]['plant_name'] = "";
					}else{
						$data['sales_order_list'][$i]['sales_order_product_list'][$j]['plant_id'] = $sop_list->plant_id;
						$data['sales_order_list'][$i]['sales_order_product_list'][$j]['plant_name'] = $plant_list->plantName;
					}
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['Quantity'] = $sop_list->Quantity;
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['Discount'] = $sop_list->Discount;
					$data["sales_order_list"][$i]['sales_order_product_list'][$j]['Subtotal'] = $sop_list->Subtotal;
					$j++;
				}
			}else{
				$data["sales_order_list"][$i]['sales_order_product_list'] = array();
			}
			
			// Get Sales Persons Product List
			$tpProducts = $this->db->query("select * from tp_sales_order_sales_person_distributors a inner join product_master b on (a.product_id = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
			
			if(count($tpProducts) >0){
				$cntr=0;
				foreach($tpProducts as $tpp_list){								
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['tp_sales_order_sales_person_distributors_id'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['saleslineItemId'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['product'] = $tpp_list->product_name;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['product_id'] = $tpp_list->product_id;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['product_code'] = $tpp_list->product_code;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['plan_quantity'] = $tpp_list->plan_quantity;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['ordered_quantity'] = $tpp_list->ordered_quantity;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['supplied_quantity'] = $tpp_list->supplied_quantity;
					$data["sales_order_list"][$i]['salesPersonsProducts'][$cntr]['supplied_date'] = $tpp_list->supplied_date;
					$cntr++;
				}
			}else{
				$data["sales_order_list"][$i]['salesPersonsProducts'] = array();
			}
		
			$aproval_chk = $this->db->query("select * from approval_process where approval_type='SalesOrder' and approval_type_id=". $so_list->sales_order_id)->result_array();
			if(count($aproval_chk)>0)
			{
				$cn = count($aproval_chk);
				$data["sales_order_list"][$i]['approval_process'][0]['Step'] = "Step - 1";
				$data["sales_order_list"][$i]['approval_process'][0]['Action'] = "--------";
				$data["sales_order_list"][$i]['approval_process'][0]['date_time'] = $aproval_chk[$cn-1]['datetime'];
				$data["sales_order_list"][$i]['approval_process'][0]['Status'] = "Submitted";
				$data["sales_order_list"][$i]['approval_process'][0]['Assigned_to_name'] = getrole_name_byuid($aproval_chk[$cn-1]['created_by']);
				$data["sales_order_list"][$i]['approval_process'][0]['Assigned_to'] = getrole_id_byuid($aproval_chk[$cn-1]['created_by']);
				$data["sales_order_list"][$i]['approval_process'][0]['Comments'] = "NA";
				$ai=1;
				foreach($aproval_chk as $aresult)
				{
					$data["sales_order_list"][$i]['approval_process'][$ai]['Step'] = "Step - ".$ai+1;
					$data["sales_order_list"][$i]['approval_process'][$ai]['Action'] = "Approve/Reject";
				
					$data["sales_order_list"][$i]['approval_process'][$ai]['date_time'] = $aresult['datetime'];
					if($aresult['status']=='1') {
						$data["sales_order_list"][$i]['approval_process'][$ai]['Status'] ="Approved";
					}else if($aresult['status']=='0'){
						$data["sales_order_list"][$i]['approval_process'][$ai]['Status'] ="Rejected";
					}else if($aresult['status']=='2'){
						$data["sales_order_list"][$i]['approval_process'][$ai]['Status'] ="Pending";
					}else{
						$data["sales_order_list"][$i]['approval_process'][$ai]['Status'] ="Pending";
					}
					
					$data["sales_order_list"][$i]['approval_process'][$ai]['Assigned_to_name'] = role_name($aresult['assigned_to']);
					$data["sales_order_list"][$i]['approval_process'][$ai]['Assigned_to'] = $aresult['assigned_to'];
					$data["sales_order_list"][$i]['approval_process'][$ai]['Comments'] = $aresult['comments'];
					$ai++;
				}
			}
			else
			{
				$data["sales_order_list"][$i]['approval_process'] = array();
			}
			$i++;
		}

		if(count($data) >0){
			$this->response(array('code'=>'200','message'=>'Sales Order List','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'200','message'=>'Sales Orders are Empty','result'=>null,'requestname'=>$method));
		}
	}

  public function salesorder_approve($parameters,$method,$user_id)
  {
	$id = $parameters['salesorder_id'];
	$data_ins_chk = $this->db->query("select * from approval_process where approval_type_id=".$id." and approval_type='SalesOrder' and status=3 order by ap_id desc")->result_array();
		//echo $this->db->last_query();exit;
		if(count($data_ins_chk)>0){
			
			$role_id = $parameters['logged_user_role_id'];
			$comments = $parameters['comm'];
			$type = $parameters['type'];
			if($type=='Accept'){
				$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$role_id)->row();
				if(count($rol_discount)>0)
				{
					$products_info = $this->db->query("select * from sales_order_products where sales_order_id	=".$id)->result_array();
					$dchk=0;
					foreach($products_info as $result)
					{
						if($rol_discount->dis_limit<$result['Discount'])
						{
							$dchk++;
						}
					}
					
					if($dchk==0)
					{
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=1,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$id);
					}
					else{
						$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$user_id)->row();
						$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=2,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
						$this->db->query("update sales_order set status='Approved but Pending' where sales_order_id=".$id);
						$data_ins['approval_type'] = 'SalesOrder';
						$data_ins['approval_type_id'] = $id;
						$data_ins['status'] = 3;
						$data_ins['datetime'] = date('Y-m-d H:i:s');
						$data_ins['assigned_to'] = $nex_report->role_reports_to;
						$data_ins['comments'] = '';
						$data_ins['created_by'] = $user_id;
						$data_ins['modified_by'] = $user_id;
						$data_ins['created_datetime'] = date('Y-m-d H:i:s');
						$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
						$ok = $this->Generic_model->insertData("approval_process",$data_ins);
					}
				}
			}
			else{
				$this->db->query("update approval_process set datetime='".date('Y-m-d H:i:s')."',status=0,comments='".$comments."',modified_by=".$user_id." where ap_id=".$data_ins_chk[0]['ap_id']);
				//echo $this->db->last_query();exit;
				$this->db->query("update sales_order set status='Rejected' where sales_order_id=".$id);
			}
		}else
		{
			
			$data['sales_order_list'] = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$id)->row();
		
			$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$data['sales_order_list']->se)->row();
			//echo $this->db->last_query();exit;
			$data_ins['approval_type'] = 'SalesOrder';
			$data_ins['approval_type_id'] = $id;
			$data_ins['status'] = 3;
			$data_ins['datetime'] = date('Y-m-d H:i:s');
			$data_ins['assigned_to'] = $nex_report->role_reports_to;
			$data_ins['comments'] = '';
			$data_ins['created_by'] = $user_id;
			$data_ins['modified_by'] = $user_id;
			$data_ins['created_datetime'] = date('Y-m-d H:i:s');
			$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
			$ok = $this->Generic_model->insertData("approval_process",$data_ins);
			if($ok)
				$this->db->query("update sales_order set status='Pending' where sales_order_id=".$id);
		//echo $this->db->last_query();
		}
	$this->response(array('code'=>'200','message'=>'salesorder_approve','result'=>$data,'requestname'=>$method));	
  }
  
  
	public function sales_order_insert($parameters,$method,$user_id){

		$checking_id = $this->db->query("select * from sales_order order by sales_order_id DESC")->row();
	 
		if($checking_id->sales_order_number  == NULL || $checking_id->sales_order_number   == ""){
			$Son_id = "SS-00001";
		}else{
			$opp_check = trim($checking_id->sales_order_number);
			$checking_op_id =  substr($opp_check, 3);
			if($checking_op_id == "99999"||$checking_op_id == "999999"||$checking_op_id =="9999999" || $checking_op_id == "99999999" || $checking_op_id == "999999999" || $checking_op_id == "9999999999" ){
				$opp_id_last_inc = (++$checking_op_id);
				$Son_id= "SS-".$opp_id_last_inc;
			}else{
				$Son_id = (++$opp_check);
			} 
		}

		$param_1['sales_order_number'] = $Son_id;		
		$customer_id = $parameters["Customer"];
		if($customer_id != "" || $customer_id != ""){
			$customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
			$SalesOrganisation = $customer_details->SalesOrganisation;
			$DistributionChannel = $customer_details->DistributionChannel;
			$Division = $customer_details->Division;
			if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
				$SalesOrganisation = "";
			}
			if($DistributionChannel == ""|| $DistributionChannel == NULL){
				$DistributionChannel = "";
			}
			if($Division == ""|| $Division == NULL){
				$Division = "";
			}
		}
			
		$contracts_id = $parameters["contracts_id"];
		if($contracts_id != "" || $contracts_id != null){
			$param_1['contract_id'] = $contracts_id;
		}

		$param_1['Customer'] = $parameters['Customer'];
		$param_1['OrderType'] = $parameters['OrderType'];
		$param_1['SalesOrganisation'] = $SalesOrganisation;
		$param_1['DistributionChannel'] = $DistributionChannel;
		$param_1['Division'] = $Division;
		$param_1['remarks'] = $parameters['remarks'];
		$param_1['Soldtopartycode'] = $parameters['Soldtopartycode'];
		$param_1['Ponumber'] = $parameters['Ponumber'];
		$param_1['Shiptopartycode'] = $parameters['Shiptopartycode'];
		$param_1['BilltopartyCode'] = $parameters['BilltopartyCode'];
		$param_1['CashDiscount'] = $parameters['CashDiscount'];
		$param_1['withoutdiscountamount'] = $parameters['withoutdiscountamount'];
		//$param_1['SchemeDiscount'] = $parameters['SchemeDiscount'];
		//$param_1['QuntityDiscount'] = $parameters['QuntityDiscount'];
		$param_1['Freight'] = $parameters['Freight'];
		$param_1['freight_amount'] = $parameters['freight_amount'];
		//$param_1['IGST'] = $parameters['IGST'];
		//$param_1['CGST'] = $parameters['CGST'];
		//$param_1['SGST'] = $parameters['SGST'];
		$param_1['discountAmount'] = $parameters['discountAmount'];
		$param_1['Total'] = $parameters['Total'];
		$param_1['created_by'] = $user_id;
		$param_1['modified_by'] = $user_id;
		$param_1['created_date_time'] = date("Y-m-d H:i:s");
		$param_1['modified_date_time'] = date("Y-m-d H:i:s");
		$sales_order_id = $this->Generic_model->insertDataReturnId("sales_order",$param_1);
		
		$data_1['sales_order_id'] = $sales_order_id;
		
		$prodct_chk=0;
		$extraprodcuts = array();
		$pi=0;
		
		if($sales_order_id != "" || $sales_order_id != NULL){
			$customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Customer'])->row();
			$user_list = $this->db->query("select * from users where user_id = '".$user_id."' AND status = 'Active'")->row();
			$user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."' AND status = 'Active'")->row();

			if(count($user_list)>0){
				$push_noti['fcmId_android'] = $user_list->fcmId_android;
				$push_noti['fcmId_iOS'] = $user_list->fcmId_iOS;                
			}else{
				$push_noti['fcmId_android'] ="";
				$push_noti['fcmId_iOS'] = "";   
			}
			
			if(count($user_report_to) >0){
				$push_noti['fcmId_android_report_to'] = $user_report_to->fcmId_android;
				$push_noti['fcmId_iOS_report_to'] = $user_report_to->fcmId_iOS;
			}else{
				$push_noti['fcmId_android_report_to'] = "";
				$push_noti['fcmId_iOS_report_to'] = "";
			}
			
			$push_noti['sales_order_id'] = $sales_order_id;
			$push_noti['user_id'] = $user_id;
			$push_noti['subject'] = "A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName." ";
			// $this->PushNotifications->SalesOrder_notifications($push_noti);

			$latest_val['module_id'] = $sales_order_id;
			$latest_val['module_name'] = "SalesOrder";
			$latest_val['user_id'] = $user_id;
			$latest_val['created_date_time'] = date("Y-m-d H:i:s");
			$this->Generic_model->insertData("update_table",$latest_val);

			$sales_order_prodct = count($parameters['sales_order_prodct']);
			
			if($parameters["sales_order_prodct"][0]['ListPrice'] != "" || $parameters["sales_order_prodct"][0]['ListPrice'] != NULL){

				for($k=0;$k<$sales_order_prodct;$k++){
					$Product_id = $parameters["sales_order_prodct"][$k]['Product'];
					if($parameters['contracts_id']!=''){
						$c_p_chk = $this->db->query("select * from contract_products where Contract=".$parameters['contracts_id']." and Product=".$Product_id)->row();				  
						if(count($c_p_chk)<=0){
							$prodct_chk++;
							$extraprodcuts[$pi]['pid'] = $Product_id;
							$extraprodcuts[$pi]['pdis'] = $parameters["sales_order_prodct"][$k]["Discount"];
							$pi++;
						}
					}
					  
					if($Product_id == NULL || $Product_id == ""){
						$product_code="";
					}else{
						$product_details = $this->db->query("select * from product_master where     product_id =".$Product_id)->row();
						$product_code=$product_details->product_code;
					}
					
					$param_2['sales_order_id'] = $sales_order_id;
					$param_2['ListPrice'] = $parameters["sales_order_prodct"][$k]['ListPrice'];
					$param_2['Product'] = $parameters["sales_order_prodct"][$k]['Product'];
					$param_2['Productcode'] = $product_code;
                    $param_2['plant_id'] = $parameters["sales_order_prodct"][$k]['plant_id'];
					$param_2['Quantity'] = $parameters["sales_order_prodct"][$k]['Quantity'];
					$param_2['Discount'] = $parameters["sales_order_prodct"][$k]['Discount'];
					$param_2['Subtotal'] = $parameters["sales_order_prodct"][$k]['Subtotal'];
					$param_2['created_by'] =$user_id;
					$param_2['modified_by'] =$user_id;
					$param_2['created_date_time'] =date("Y-m-d H:i:s");
					$param_2['modified_date_time'] =date("Y-m-d H:i:s");

					$ok = $this->Generic_model->insertData("sales_order_products",$param_2);
				}

				//$user_list = $this->db->query("select * from users where user_id = '".$user_id."'")->row();
				$sales_product_list = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id = ".$sales_order_id)->result();
				$customer_list = $this->db->query("select * from customers where customer_id =".$parameters['Customer'])->row();
				$email = $user_list->email;
				$to = $email;  //$to      = $dept_email_id;
				$subject = "New Sales Order Created";
				$data['name'] = ucwords($user_list->name);
				$data['message'] = "<p> A new Sales Order has been created successfully <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
				<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
				<thead>
				<tr >
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
				<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
				</tr></thead>";

				if(count($sales_product_list) >0){
					foreach($sales_product_list as $sales_values){
						$data['message'].=  "<tbody><tr>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
						<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
					}
				}

				$data['message'].= "</table><br/>";  

				// $ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);

				// $user_report_to = $this->db->query("select * from users where user_id = '".$user_list->manager."'")->row();
				if(count($user_report_to) >0){
					$email = $user_report_to->email;
					$to = $email;  //$to      = $dept_email_id;
					$subject = "New Sales Order created";
					$data['name'] = ucwords($user_report_to->name);
					$data['message'] = "<p> A new Sales Order has been created successfully By ".ucwords($user_list->name)." <br/><br/><b> SalesOrderId </b> : ".$Son_id."<br/> <b>CustomerName </b> : ". $customer_list->CustomerName.", <br/><br/>
					<table width='100%'  align='center'  style='border-collapse:collapse;margin-top:16px; border:0px solid #eee;align:left;width:100%;font-size: 15px;padding: 2px;vertical-align: middle;'>
					<thead>
					<tr >
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Product</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Price</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Quantity</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'> Discount</th>
					<th rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>Amount</th>
					</tr></thead>";

					if(count($sales_product_list) >0){
						foreach($sales_product_list as $sales_values){
							$data['message'].=  "<tbody><tr>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->product_name."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->ListPrice."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Quantity."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Discount."</td>
							<td rowspan='2' colspan='3' style='text-align:center;border:1px solid black;border-collapse:collapse;'>".$sales_values->Subtotal."</td></tr></tbody>";
						}
					}

					$data['message'].= "</table><br/>";  
					$ok = $this->mail_send->content_mail_ncl_all($from, $to, $subject, '', '',$data);
				}

				$param_noti['notiffication_type'] = "SalesOrder";
				$param_noti['notiffication_type_id'] = $sales_order_id;
				$param_noti['user_id'] = $user_id;
				$param_noti['subject'] = " A new Sales Order has been created successfully  SalesOrderId  : ".$Son_id." CustomerName  : ". $customer_list->CustomerName."";
				$this->Generic_model->insertData("notiffication",$param_noti);

				if($parameters['contracts_id']!=''&&$parameters['contracts_id']!=NULL){
					//echo $prodct_chk;exit;
					if($prodct_chk==0){
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						//redirect('admin/sales_oders_list');
					}else{
						if(count($extraprodcuts)>0){
							$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();		
							//echo $this->db->last_query();exit;
							$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

							$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
							if(count($rol_discount)>0){
								$dchk=0;
								foreach($extraprodcuts as $result){
									//print_r($result);exit;
									if($rol_discount->dis_limit<$result['pdis'])
									$dchk++;									
								}

								if($dchk==0){
									$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
								}else{
									$data_ins['approval_type'] = 'SalesOrder';
									$data_ins['approval_type_id'] = $sales_order_id;
									$data_ins['status'] = 3;
									$data_ins['datetime'] = date('Y-m-d H:i:s');
									$data_ins['assigned_to'] = $nex_report->role_reports_to;
									$data_ins['comments'] = '';
									$data_ins['created_by'] = $user_id;
									$data_ins['modified_by'] = $user_id;
									$data_ins['created_datetime'] = date('Y-m-d H:i:s');
									$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
									$ok = $this->Generic_model->insertData("approval_process",$data_ins);
									if($ok)
										$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
								}
							}
							//echo $this->db->last_query();exit;							
							//redirect('admin/sales_oders_list');
						}
					}
				}else{
					//echo "No contract";exit;
					$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
					$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();

					$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
					if(count($rol_discount)>0){
						$dchk=0;
						$product_id = count($parameters["sales_order_prodct"]);
						for($j=0;$j<$product_id;$j++){
							if($rol_discount->dis_limit<$parameters["sales_order_prodct"][$j]['Discount'])
							$dchk++;								
						}
						if($dchk==0){
							//echo $sales_order_id;exit;
							$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						}else{
							//echo "false".$sales_order_id;exit;
							$data_ins['approval_type'] = 'SalesOrder';
							$data_ins['approval_type_id'] = $sales_order_id;
							$data_ins['status'] = 3;
							$data_ins['datetime'] = date('Y-m-d H:i:s');
							$data_ins['assigned_to'] = $nex_report->role_reports_to;
							$data_ins['comments'] = '';
							$data_ins['created_by'] = $user_id;
							$data_ins['modified_by'] = $user_id;
							$data_ins['created_datetime'] = date('Y-m-d H:i:s');
							$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
							$ok = $this->Generic_model->insertData("approval_process",$data_ins);
							if($ok)
								$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
						}
					}
					//echo $this->db->last_query();exit;							
					//redirect('admin/sales_oders_list');
				}

				$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
				$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>$method));
			}else{
				$return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
				$this->response(array('code'=>'200','message'=>'sales order successfully inserted','result'=>$return_data,'requestname'=>$method));
			}
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}


	public function sales_order_edit($parameters,$method,$user_id){
		$sales_order_id = $parameters['sales_order_id'];
		$customer_id = $parameters["Customer"];
            if($customer_id != "" || $customer_id != ""){
              $customer_details = $this->db->query("select * from customers where customer_id = ".$customer_id)->row();
              $SalesOrganisation = $customer_details->SalesOrganisation;
              $DistributionChannel = $customer_details->DistributionChannel;
              $Division = $customer_details->Division;
              if($SalesOrganisation == ""|| $SalesOrganisation == NULL){
                $SalesOrganisation = "";
              }
              if($DistributionChannel == ""|| $DistributionChannel == NULL){
                $DistributionChannel = "";
              }
              if($Division == ""|| $Division == NULL){
                $Division = "";
              }
            }
             $contracts_id = $parameters["contracts_id"];
            if($contracts_id != "" || $contracts_id != null){
              $param_1['contract_id'] = $contracts_id;
            }

            $param_1['Customer'] = $parameters['Customer'];
            $param_1['OrderType'] = $parameters['OrderType'];
            $param_1['SalesOrganisation'] = $SalesOrganisation;
            $param_1['DistributionChannel'] = $DistributionChannel;
            $param_1['Division'] = $Division;
            $param_1['remarks'] = $parameters['remarks'];
            $param_1['Soldtopartycode'] = $parameters['Soldtopartycode'];
            $param_1['Ponumber'] = $parameters['Ponumber'];
            $param_1['Shiptopartycode'] = $parameters['Shiptopartycode'];
            $param_1['BilltopartyCode'] = $parameters['BilltopartyCode'];
            $param_1['CashDiscount'] = $parameters['CashDiscount'];
            $param_1['withoutdiscountamount'] = $parameters['withoutdiscountamount'];
            //$param_1['SchemeDiscount'] = $parameters['SchemeDiscount'];
            //$param_1['QuntityDiscount'] = $parameters['QuntityDiscount'];
            $param_1['Freight'] = $parameters['Freight'];
            $param_1['freight_amount'] = $parameters['freight_amount'];
            //$param_1['IGST'] = $parameters['IGST'];
            //$param_1['CGST'] = $parameters['CGST'];
            //$param_1['SGST'] = $parameters['SGST'];
            $param_1['discountAmount'] = $parameters['discountAmount'];
            $param_1['Total'] = $parameters['Total'];
            $param_1['modified_by'] = $user_id;
            $param_1['modified_date_time'] = date("Y-m-d H:i:s");

            $result=$this->Generic_model->updateData('sales_order',$param_1,array('sales_order_id'=>$sales_order_id));

	  	 if($result == 1){

        $check_update_list = $this->db->query("select * from update_table where module_id ='".$sales_order_id."' and module_name ='SalesOrder'")->row();
              if(count($check_update_list)>0){
                $latest_val['user_id'] = $user_id;
                $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $sales_order_id,'module_name'=>'SalesOrder'));
              }else{
                $latest_val['module_id'] = $sales_order_id;
                $latest_val['module_name'] = "SalesOrder";
                $latest_val['user_id'] = $user_id;
                $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                $this->Generic_model->insertData("update_table",$latest_val);
              }


	  	 	if($parameters["sales_order_prodct"][0]['ListPrice'] != "" || $parameters["sales_order_prodct"][0]['ListPrice'] != NULL){
	  	 		 $sales_order_prodct = count($parameters['sales_order_prodct']);
          
			        for($k=0;$k<$sales_order_prodct;$k++){
			          $Product_id = $parameters["sales_order_prodct"][$k]['Product'];
					  if($parameters['contracts_id']!=''){
					  $c_p_chk = $this->db->query("select * from contract_products where Contract='".$parameters['contracts_id']."' and Product='".$Product_id."'")->row();			  
  					  if(count($c_p_chk)<=0){
  						  $prodct_chk++;
  						  $extraprodcuts[$pi]['pid'] = $product_id_val;
  						  $extraprodcuts[$pi]['pdis'] = $this->input->post("Discount[$j]");
  					  }
						}
			          	if($Product_id == NULL || $Product_id == ""){
			          		$product_code="";
		         		}else{

			          		$product_details = $this->db->query("select * from product_master where     product_id =".$Product_id)->row();
			          		 $product_code=$product_details->product_code;

			          	}


				  	 	     $param_2['sales_order_id'] = $sales_order_id;
			              $param_2['ListPrice'] = $parameters["sales_order_prodct"][$k]['ListPrice'];
			              $param_2['Product'] = $parameters["sales_order_prodct"][$k]['Product'];
			              $param_2['Productcode'] = $product_code;
			              $param_2['Quantity'] = $parameters["sales_order_prodct"][$k]['Quantity'];
                    $param_2['plant_id'] = $parameters["sales_order_prodct"][$k]['plant_id'];
			              $param_2['Discount'] = $parameters["sales_order_prodct"][$k]['Discount'];
			              $param_2['Subtotal'] = $parameters["sales_order_prodct"][$k]['Subtotal'];
			              $param_2['modified_by'] =$user_id;
			              $param_2['modified_date_time'] =date("Y-m-d H:i:s");
			            $checking_id = $parameters["sales_order_prodct"][$k]['sales_order_products_id'];
			            if($checking_id == "" || $checking_id == NULL){
		                      $param_2['created_by'] =$user_id;
		                      $param_2['created_date_time'] =date("Y-m-d H:i:s");
		                        $this->Generic_model->insertData("sales_order_products",$param_2);

	                    }else{
	                        $this->Generic_model->updateData('sales_order_products', $param_2, array(' sales_order_products_id' => $checking_id));
	                    }
	                  
			        }
					if($parameters['contracts_id']!=''&&$parameters['contracts_id']!=NULL)
				  {
					  //echo "with contract";exit;
					  if($prodct_chk==0)
					  {
						$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
						//redirect('admin/sales_oders_list');
					  }
					  else
					  {
						if(count($extraprodcuts)>0)
						{
							$sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
							$nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();
							
							$rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
							if(count($rol_discount)>0)
							{
								$dchk=0;
								foreach($extraprodcuts as $result)
								{
									if($rol_discount->dis_limit<$result['pdis'])
										$dchk++;
									if($dchk==0)
									{
										$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
									}
									else{
										$data_ins['approval_type'] = 'SalesOrder';
										$data_ins['approval_type_id'] = $sales_order_id;
										$data_ins['status'] = 3;
										$data_ins['datetime'] = date('Y-m-d H:i:s');
										$data_ins['assigned_to'] = $nex_report->role_reports_to;
										$data_ins['comments'] = '';
										$data_ins['created_by'] = $user_id;
										$data_ins['modified_by'] = $user_id;
										$data_ins['created_datetime'] = date('Y-m-d H:i:s');
										$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
										$ok = $this->Generic_model->insertData("approval_process",$data_ins);
										if($ok)
											$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
									}
								}
							}
							//echo $this->db->last_query();exit;							
							//redirect('admin/sales_oders_list');
						}
					  }
				  }
				  else
				  {
					   //echo "No contract";exit;
					  $sales_order_list = $this->db->query("select *,a.created_by as se from sales_order a inner join  customers b on (a.Customer= b.customer_id) where a.archieve != 1 and a.sales_order_id =".$sales_order_id)->row();							
					  $nex_report = $this->db->query("select * from role a inner join users b on a.role_id=b.role where user_id=".$sales_order_list->se)->row();
						
					  $rol_discount = $this->db->query("select * from role_with_discount where role_id=".$nex_report->role_id)->row();
						if(count($rol_discount)>0)
						{
							$dchk=0;
							$product_id = count($parameters['sales_order_prodct']);
							for($j=0;$j<$product_id;$j++)
							{
								if($rol_discount->dis_limit<$parameters["sales_order_prodct"][$j]['Discount'])
									$dchk++;								
							}
							if($dchk==0)
							{
								//echo $sales_order_id;exit;
								$this->db->query("update sales_order set status='Approved' where sales_order_id=".$sales_order_id);
							}
							else{
								//echo "false".$sales_order_id;exit;
								$data_ins['approval_type'] = 'SalesOrder';
								$data_ins['approval_type_id'] = $sales_order_id;
								$data_ins['status'] = 3;
								$data_ins['datetime'] = date('Y-m-d H:i:s');
								$data_ins['assigned_to'] = $nex_report->role_reports_to;
								$data_ins['comments'] = '';
								$data_ins['created_by'] = $user_id;
								$data_ins['modified_by'] = $user_id;
								$data_ins['created_datetime'] = date('Y-m-d H:i:s');
								$data_ins['modifed_datetime'] = date('Y-m-d H:i:s');
								$ok = $this->Generic_model->insertData("approval_process",$data_ins);
								if($ok)
									$this->db->query("update sales_order set status='Pending' where sales_order_id=".$sales_order_id);
							}
						}
						//echo $this->db->last_query();exit;							
						//redirect('admin/sales_oders_list');
				  }
           $return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
			        $this->response(array('code'=>'200','message'=>'sales Order successfully updated','result'=>$return_data,'requestname'=>$method));
			    }else{
             $return_data = $this->all_tables_records_view("salesorder",$sales_order_id);
			    	$this->response(array('code'=>'200','message'=>'contract successfully updated1','result'=>$return_data,'requestname'=>$method));
			    }
	  	}else{
	  		$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
	  		
	  	}

	}

  public function sales_order_delete($parameters,$method,$user_id){
    $sales_order_id = $parameters['sales_order_id'];

    if($sales_order_id != "" || $sales_order_id  != NULL){
      $param['archieve'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d H:i:s");
       $result=$this->Generic_model->updateData('sales_order',$param,array('sales_order_id'=>$sales_order_id));
        if($result){

          $latest_val['user_id'] = $user_id;
            $latest_val['created_date_time'] = date("Y-m-d H:i:s");
            $latest_val['delete_status'] = "1";
            $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $sales_order_id,'module_name'=>'SalesOrder'));

          $this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
       }else{
        $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
       }
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }

  }

  public function sales_order_line_item_delete($parameters,$method,$user_id){
    $sales_order_id = $parameters["sales_order_id"];
    $param_1['withoutdiscountamount'] = '0';
    $param_1['Freight'] = " ";
    $param_1['freight_amount'] = " ";
    $param_1['discountAmount'] = "0";
    $param_1['Total'] = "0";
    $param_1['CashDiscount'] = "0";
    $param_1['contract_id'] = "0";
    $ok = $this->Generic_model->updateData('sales_order', $param_1, array('sales_order_id' => $sales_order_id));
    if($ok == 1){
      $ok1 = $this->Generic_model->deleteRecord('sales_order_products',array('sales_order_id'=>$sales_order_id));
      if($ok1 == 1){
        $this->response(array('code'=>'200','message'=>'sales Order successfully deleted','result'=>"",'requestname'=>$method));
      }else{
        $this->response(array('code'=>'404','message'=>'sales Order not deleted','result'=>"",'requestname'=>$method));
      }
     
    }
  }

  public function sales_order_contract_list($parameters,$method,$user_id){
    // $customer_list = $this->db->query("select * from customers where archieve !='1' group by CustomerName")->result();
	$final_users_id = $parameters['team_id'];
	$role_id= $parameters['role_id'];	
	
	if(isset($parameters['customerType'])){
		if($parameters['customerType'] != '' || $parameters['customerType'] != NULL){
			if($parameters['customerType'] == "Third party Customer"){
				$whereCustomerType = "AND CUST.CustomerType = '".$parameters['customerType']."'";
			}else{
				$whereCustomerType = "AND (CUST.CustomerType = '".$parameters['customerType']."' OR CUST.CustomerType is NULL)";
			}
		}else{
			$whereCustomerType = "";
		}
	}else{
		$whereCustomerType = "";
	}
    
	$customers = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName, CUST.CustomerSAPCode FROM customers CUST INNER JOIN customer_users_maping CUM ON (CUST.customer_id = CUM.customer_id) WHERE CUM.user_id IN (".$final_users_id.") AND CUST.archieve != 1 ".$whereCustomerType." GROUP BY CUM.customer_id")->result();
	
	//$customers = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName FROM customers CUST INNER JOIN customer_users_maping CUM ON (CUST.customer_id = CUM.customer_id) INNER JOIN users U ON (CUM.user_id = U.user_id) WHERE CUM.user_id IN (".$final_users_id.") AND CUST.archieve != 1 ".$whereCustomerType." GROUP BY CUM.customer_id")->result();
	
	//echo "SELECT CUST.customer_id, CUST.CustomerName FROM customers CUST INNER JOIN customer_users_maping CUM ON (CUST.customer_id = CUM.customer_id) WHERE CUM.user_id IN (".$final_users_id.") AND CUST.archieve != 1 ".$whereCustomerType." GROUP BY CUM.customer_id";
	//exit();
	
	//$contacts = $this->db->query("SELECT CUST.customer_id, CUST.CustomerName from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 ".$whereCustomerType." ORDER BY CON.contact_id DESC")->result(); 

	//$associate_contacts = $this->db->query("SELECT CUST.CustomerName, CUST.customer_id FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 ".$whereCustomerType." GROUP BY CON.contact_id")->result();
	
	//print_r($customers);
	//print_r($contacts);
	//print_r($associate_contacts);
	//exit();
	
	//$customer_list = array_merge($customers, $contacts, $associate_contacts);
	$customer_list = $customers;
		
	//$customer_list = $this->db->query("select * from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
	
	/*
	print_r($customer_list);
	exit();
	*/
    
    $i=0;
    foreach($customer_list as $cust_values){	
	
		$contract_list_val = $this->db->query("select contract_id, ContractName from contract where Company =".$cust_values->customer_id)->result();
	
		$data['customers_list'][$i]['customer_id'] = $cust_values->customer_id;
		$data['customers_list'][$i]['customer_name'] = $cust_values->CustomerName;
		$data['customers_list'][$i]['customer_SAP_code'] = $cust_values->CustomerSAPCode;
		
		if(count($contract_list_val)>0){
			$j=0;
			foreach($contract_list_val as $val_contratc){
				$data['customers_list'][$i]['contract_list'][$j]['contract_id'] = $val_contratc->contract_id;
				$data['customers_list'][$i]['contract_list'][$j]['contract_Name'] = $val_contratc->ContractName;
				$j++;
			}
		}else{
			$data['customers_list'][$i]['contract_list'] = array();
		}

		$contact_list = $this->db->query("select * from contacts where archieve != 1 and Company =".$cust_values->customer_id)->result();
      
		if(count($contact_list) >0){
			$k=0;
			foreach($contact_list as $contact_val){
				$data['customers_list'][$i]['contact_list'][$k]['contact_id'] = $contact_val->contact_id;
				$data['customers_list'][$i]['contact_list'][$k]['contact_name'] = $contact_val->FirstName." ".$contact_val->LastName;
				$k++;
			}
		}else{
			$data['customers_list'][$i]['contact_list'] = array();
		}

		$i++;
    }

    $plant_list_val = $this->db->query("select * from plant_master  where archieve != 1")->result();
    if(count($plant_list_val) >0){
      $l=0;
      foreach ($plant_list_val as $plant_val){
        $data['plant_list'][$l]['plantid'] = $plant_val->plantid;
        $data['plant_list'][$l]['plantName'] = $plant_val->plantName."(".$plant_val->plantcode.")";
        $l++;
      }
    }else{
      $data['plant_list'] = array();
    }

    $freight_tbl_val = $this->db->query("select * from freight_tbl")->result();
    
    if(count($freight_tbl_val) >0){
      $m=0;
      foreach ($freight_tbl_val as $freight_val){
        $data['freight_list'][$m]['freight_id'] = $freight_val->freight_id;
        $data['freight_list'][$m]['price'] = $freight_val->price;

        $data['freight_list'][$m]['location'] = $freight_val->location."(".$freight_val->price.")";
        $m++;
      }
    }else{
      $data['freight_list'] = array();
    }

    $this->response(array('code'=>'200','message'=>'SalesOrder Contract_list','result'=>$data,'requestname'=>$method));

  }

  public function  list_view_all($parameters,$method,$user_id){
    $type =$parameters['type'];
    $value = $parameters['value'];
    $id = $parameters['id'];
    if($value=="sales_call" && $type == "Leads"){
      $leads_list = $this->db->query("select * from sales_call a inner join leads b on (a.id = b.leads_id)  where a.archieve != '1' and releted_to='Leads' and id=".$id)->result();
      $i=0;
      foreach($leads_list as $values_lead){

        $data['sales_call'][$i]['sales_call_id'] = $values_lead->sales_call_id ;
        $data['sales_call'][$i]['Subject'] = $values_lead->Subject ;
        $data['sales_call'][$i]['name'] = $values_lead->FirstName." ".$values_lead->LastName ;
        $data['sales_call'][$i]['Status'] = $values_lead->Status ;
        $data['sales_call'][$i]['Call_Type'] = $values_lead->Call_Type ;
        $data['sales_call'][$i]['Assigned_To'] = user_details($values_lead->Assigned_To) ;
        $data['sales_call'][$i]['Priority'] = $values_lead->Priority ;
        $data['sales_call'][$i]['Owner'] = user_details($values_lead->Owner) ;
        $data['sales_call'][$i]['type'] = $type ;
          $i++;
      }
    }
    if($value=="sales_call" && $type=="Customers"){
      $customer_val_id = $this->db->query("select * from sales_call a inner join customers b on (a.id = b.customer_id)  where a.archieve != '1' and releted_to='Customers' and id=".$id)->result();

      $j=0;
      foreach($customer_val_id as $values_customer){

        $data['sales_call'][$j]['sales_call_id'] = $values_customer->sales_call_id ;
        $data['sales_call'][$j]['Subject'] = $values_customer->Subject ;
        $data['sales_call'][$j]['name'] = $values_customer->CustomerName;
        $data['sales_call'][$j]['Status'] = $values_customer->Status ;
        $data['sales_call'][$j]['Call_Type'] = $values_customer->Call_Type ;
        $data['sales_call'][$j]['Assigned_To'] = user_details($values_customer->Assigned_To) ;
        $data['sales_call'][$j]['Priority'] = $values_customer->Priority ;
        $data['sales_call'][$j]['Owner'] = user_details($values_customer->Owner) ;
        $data['sales_call'][$j]['type'] = $type ;
          $j++;
      }
     
    }
    if($value=="sales_call" && $type=="Opportunities"){
      $Opportunities_list = $this->db->query("select * from sales_call a inner join opportunities b on (a.id = b.opportunity_id)  where a.archieve != '1' and  releted_to='Opportunities' and id=".$id)->result();

      $j=0;
      foreach($Opportunities_list as $values_opportunities){

        $data['sales_call_opportunity'][$j]['sales_call_id'] = $values_opportunities->sales_call_id ;
        $data['sales_call'][$j]['Subject'] = $values_opportunities->Subject ;
        $data['sales_call'][$j]['name'] = $values_opportunities->opp_id;
        $data['sales_call'][$j]['Status'] = $values_opportunities->Status ;
        $data['sales_call'][$j]['Call_Type'] = $values_opportunities->Call_Type ;
        $data['sales_call'][$j]['Assigned_To'] = user_details($values_opportunities->Assigned_To) ;
        $data['sales_call'][$j]['Priority'] = $values_opportunities->Priority ;
        $data['sales_call'][$j]['Owner'] = user_details($values_opportunities->Owner) ;
        $data['sales_call'][$j]['type'] = $type ;
         $j++;
      }
    }
     if($value=="sales_call" && $type=="contact"){
      $contact_list = $this->db->query("select * from sales_call a inner join contacts b on (a.contacts_id = b.contact_id)  where  a.archieve != '1' and  a.contacts_id=".$id)->result();
     
      $j=0;
      foreach($contact_list as $values_contact){
        $data['sales_call'][$j]['sales_call_id'] = $values_contact->sales_call_id ;
        $data['sales_call'][$j]['Subject'] = $values_contact->Subject ;
        $data['sales_call'][$j]['name'] = $values_contact->FirstName." ".$values_contact->LastName;
        $data['sales_call'][$j]['Status'] = $values_contact->Status ;
        $data['sales_call'][$j]['Call_Type'] = $values_contact->Call_Type ;
        $data['sales_call'][$j]['Assigned_To'] = user_details($values_contact->Assigned_To) ;
        $data['sales_call'][$j]['Priority'] = $values_contact->Priority ;
        $data['sales_call'][$j]['Owner'] = user_details($values_contact->Owner) ;
        $data['sales_call'][$j]['type'] = $type ;
        $j++;
      }
      
    }

    if($value=="contact_list" && $type=="Customers"){
      $contact_list_list = $this->db->query("select * from contacts a inner join customers b on (a.Company = b.customer_id)  where  a.archieve != '1' and  a.Company=".$id)->result();
     
      $j=0;
      foreach($contact_list_list as $values_contact){
        $data['contact_list'][$j]['contact_id'] = $values_contact->contact_id ;
        $data['contact_list'][$j]['Email'] = $values_contact->Email ;
        $data['contact_list'][$j]['name'] = $values_contact->FirstName." ".$values_contact->LastName;
        $data['contact_list'][$j]['Mobile'] = $values_contact->Mobile ;
        $data['contact_list'][$j]['Title'] = $values_contact->Title_Designation ;
        $data['contact_list'][$j]['ContactOwner'] = user_details($values_contact->ContactOwner) ;
        $data['contact_list'][$j]['type'] = $type ;
        $j++;
      }
    }
     if($value=="opportunity_list" && $type=="Customers"){
      $opp_list = $this->db->query("select * from opportunities a inner join customers b on (a.Customer = b.customer_id)  where  a.archieve != '1' and  a.Customer=".$id)->result();
      $j=0;
      foreach($opp_list as $values_opp){
        $data['opportunities'][$j]['opportunity_id'] = $values_opp->opportunity_id ;
        $data['opportunities'][$j]['opp_id'] = $values_opp->opp_id ;
        $data['opportunities'][$j]['Customer'] = $values_opp->CustomerName;
        $data['opportunities'][$j]['TotalPrice'] = $values_opp->TotalPrice ;
        $data['opportunities'][$j]['CloseDate'] = date("d-m-Y",strtotime($values_opp->CloseDate));
        $data['opportunities'][$j]['Stage'] = $values_opp->Stage ;
        $data['opportunities'][$j]['OpportunityOwner'] = user_details($values_opp->OpportunityOwner) ;
        $data['opportunities'][$j]['type'] = $type ;
        $j++;
      }
      
    }
    if($value=="qutation_list" && $type=="Opportunities"){
      $qutation_list = $this->db->query("select * from opportunities a inner join Quotation b on (a.opportunity_id = b.Opportunity)  where b.archieve != '1' and b.Opportunity=".$id)->result();

      $j=0;
      foreach($qutation_list as $values_qut){
        $data['opportunities'][$j]['Quotation_id'] = $values_qut->Quotation_id ;
        $data['opportunities'][$j]['QuotationversionID'] = $values_qut->opp_id ;
        $data['opportunities'][$j]['Customer'] = customer_id_name($values_qut->Customer);
        $data['opportunities'][$j]['Contact'] = contact_id_name($values_qut->Contact) ;
        $data['opportunities'][$j]['QuotationDate'] = date("d-m-Y",strtotime($values_qut->QuotationDate));
        $data['opportunities'][$j]['ExpiryDate'] = date("d-m-Y",strtotime($values_qut->ExpiryDate));
        $data['opportunities'][$j]['TotalPrice'] = $values_qut->TotalPrice ;
         $data['opportunities'][$j]['type'] = $type ;
        $j++;
      }  
     
    }
    if(count($data)>0){
        $this->response(array('code'=>'200','message'=>'List Value','result'=>$data,'requestname'=>$method));
    }else{
      $this->response(array('code'=>'200','message'=>'No data in the database','result'=>$data,'requestname'=>$method));
    }

  }
  
	/**
	* Function leadInfo retrieve data of the particular lead or all leads
	*/
	public function leadInfo($lead_id = '', $final_users_id = ''){
		if($lead_id != ''){
			$lead_list = $this->db->query("select *,a.created_date_time as createdDateTime from leads a inner join users b on (a.LeadOwner = b.user_id) where a.leads_id in (".$lead_id.")")->result();
		}else if($final_users_id != ''){
			$lead_list = $this->db->query("select *,a.created_date_time as createdDateTime from leads a inner join users b on (a.LeadOwner = b.user_id) where a.LeadOwner in (".$final_users_id.") and a.archieve != 1 and a.lead_status != 'convert' order by a.leads_id DESC")->result(); 
		}else{
			$lead_list = $this->db->query("select *,a.created_date_time as createdDateTime from leads a inner join users b on (a.LeadOwner = b.user_id)")->result();
		}

		if(count($lead_list)>0){
			$li=0;
			foreach($lead_list as $leads_val){			
			
				// Gathering lead data        							
				$data['lead_list'][$li]['leads_id'] =  $leads_val->leads_id;  				
				$data['lead_list'][$li]['lead_number'] =  $leads_val->lead_number;  	
				$data['lead_list'][$li]['Company'] = $leads_val->Company;
				$data['lead_list'][$li]['Company_Text'] = $leads_val->Company_text;
				$data['lead_list'][$li]['isAccountTagged'] = $leads_val->isAccountTagged;
				$data['lead_list'][$li]['is_lead_converted'] = $leads_val->is_lead_converted;
								
				$data['lead_list'][$li]['lead_email'] = $leads_val->lead_email;
				$data['lead_list'][$li]['LeadOwner'] = ucwords($leads_val->name);
				$data['lead_list'][$li]['LeadSource'] = $leads_val->LeadSource;				
				$data['lead_list'][$li]['lead_phone'] = $leads_val->lead_phone;
				$data['lead_list'][$li]['lead_website'] = $leads_val->lead_website;
				$data['lead_list'][$li]['lead_status'] = $leads_val->lead_status;
				$data['lead_list'][$li]['is_lead_converted'] = $leads_val->is_lead_converted;						
				
				// lead Project Data
				$data['lead_list'][$li]['lead_project_name'] = $leads_val->lead_project_name;
				$data['lead_list'][$li]['lead_project_type'] = $leads_val->lead_project_type;
				$data['lead_list'][$li]['lead_class_of_project'] = $leads_val->lead_class_of_project;
				$data['lead_list'][$li]['lead_size_class_of_project'] = $leads_val->lead_size_class_of_project;
				$data['lead_list'][$li]['size_calss_unit_no_of_floor_per_block'] = $leads_val->size_calss_unit_no_of_floor_per_block;
				$data['lead_list'][$li]['size_calss_unit_no_of_blocks'] = $leads_val->size_calss_unit_no_of_blocks;
				$data['lead_list'][$li]['size_calss_unit'] = $leads_val->size_calss_unit;
				$data['lead_list'][$li]['lead_project_status'] = $leads_val->lead_project_status;
				$data['lead_list'][$li]['no_of_flats'] = $leads_val->no_of_flats;
				$data['lead_list'][$li]['sft'] = $leads_val->sft;
				$data['lead_list'][$li]['cubic_meters'] = $leads_val->cubic_meters;
				
				// Billing Address : Lead Address				
				$data['lead_list'][$li]['lead_street1'] = $leads_val->lead_street1;
				$data['lead_list'][$li]['lead_street2'] = $leads_val->lead_street2;								
				$data['lead_list'][$li]['lead_plotno'] = $leads_val->lead_plotno;
				$data['lead_list'][$li]['lead_area'] = $leads_val->lead_area;
				$data['lead_list'][$li]['lead_state'] = $leads_val->lead_state;
				$data['lead_list'][$li]['lead_country'] = $leads_val->lead_country;				
				$data['lead_list'][$li]['lead_City'] = $leads_val->lead_City;
				$data['lead_list'][$li]['lead_pin_zip_code'] = $leads_val->lead_pin_zip_code;

				
				// Concatinating Shipping Address : Lead Project Address
				$data['lead_list'][$li]['lead_project_street1'] = $leads_val->lead_project_street1;
				$data['lead_list'][$li]['lead_project_street2'] = $leads_val->lead_project_street2;				
				$data['lead_list'][$li]['lead_project_plot_no'] = $leads_val->lead_project_plot_no;
				$data['lead_list'][$li]['lead_project_land_mark'] = $leads_val->lead_project_land_mark;				
				$data['lead_list'][$li]['lead_project_city'] = $leads_val->lead_project_city;
				$data['lead_list'][$li]['lead_project_state'] = $leads_val->lead_project_state;
				$data['lead_list'][$li]['lead_project_pin_zip_code'] = $leads_val->lead_project_pin_zip_code;				
				
				$data['lead_list'][$li]['lead_main_contact_id'] = $leads_val->lead_main_contact_id;
				$data['lead_list'][$li]['lead_main_contact_name'] = $leads_val->lead_main_contact_name;				
				$data['lead_list'][$li]['lead_main_contact_designation'] = $leads_val->lead_main_contact_designation;
				$data['lead_list'][$li]['lead_main_contact_email'] = $leads_val->lead_main_contact_email;				
				$data['lead_list'][$li]['lead_main_contact_mobile'] = $leads_val->lead_main_contact_mobile;
				$data['lead_list'][$li]['lead_main_contact_category'] = $leads_val->lead_main_contact_category;
				$data['lead_list'][$li]['lead_main_contact_phone'] = $leads_val->lead_main_contact_phone;
				$data['lead_list'][$li]['lead_main_contact_company'] = $leads_val->lead_main_contact_company;				
				$data['lead_list'][$li]['created_date_time'] = $leads_val->createdDateTime;						
				
				// Gather Associsative Contact List for this lead			
				$Associate_contact_list = $this->db->query("select lead_associate_contact_id, a.contact_id, assoc_contact_company, associate_contact_designation, associate_contact_mobile, associate_contact_category, b.FirstName, b.LastName from lead_associate_contacts a inner join contacts b on (a.contact_id = b.contact_id) where a.leads_id = '".$leads_val->leads_id."'")->result();

				if(count($Associate_contact_list) > 0){
					$acli = 0;
					foreach($Associate_contact_list as $ass_val){						
						$data['lead_list'][$li]['associate_contact'][$acli] = $ass_val;
						$acli++;
					} 					
				}else{
					$data['lead_list'][$li]['associate_contact'] = array();					
				}
				
				// Gather Action Work Data of this Lead
				$Action_work_list = $this->db->query("select action_work_done_id, action_work_done_date, action_work_done_remarks from lead_action_work_done where leads_id = '".$leads_val->leads_id."'")->result();

				if(count($Action_work_list) > 0){
					$awli = 0;
					foreach($Action_work_list as $awl_val){						
						$data['lead_list'][$li]['action_work_done'][$awli] = $awl_val;
						$awli++;
					} 					
				}else{
					$data['lead_list'][$li]['action_work_done'] = array();					
				}				
				$li++;
			}
		}else{
			$data['lead_list'] = array();
		}

		return $data; 
	}

	/**
	* Function masters_list will retrieve data depending on the table_name specified
	*/
	public function masters_list($parameters,$method,$user_id){
		
		$final_users_id = $parameters['team_id'];
				
		$table_name=$parameters['table_name'];
		// Get Lead Master Records
		if($table_name=='leads'){
			$data = $this->leadInfo('',$final_users_id);			
		}
		
		$role_id= $parameters['role_id'];
     
		// Get Customer Master Records
		if($table_name=='customers'){		
			
			$customers = $this->db->query("select a.*, a.created_date_time as createdDateTime from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.user_id in (".$final_users_id.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();
			
			$contacts = $this->db->query("SELECT CUST.*, CUST.created_date_time as createdDateTime from contacts CON INNER JOIN customers CUST ON CON.company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 ORDER BY CON.contact_id DESC")->result(); 
			
			$associate_contacts = $this->db->query("SELECT CUST.*, CUST.created_date_time as createdDateTime FROM opportunity_associate_contacts OPP INNER JOIN contacts CON ON OPP.contact_id = CON.contact_id INNER JOIN Customers CUST ON CON.Company = CUST.customer_id WHERE CON.ContactOwner IN (".$final_users_id.") AND CON.archieve != 1 GROUP BY CON.contact_id")->result();
			
			$customer_list = array_merge($customers, $contacts, $associate_contacts);
		
			if(count($customer_list)>0){		
				$ic=0;
				foreach ($customer_list as $customer_val) { 
					$customer_user_list = $this->db->query("select * from customer_users_maping a inner join users b on (a.user_id = b.user_id) where customer_id =".$customer_val->customer_id)->result();               
					$data['customer_list'][$ic]['customer_id']=$customer_val->customer_id;
					$data['customer_list'][$ic]['CustomerName']=$customer_val->CustomerName;
					$data['customer_list'][$ic]['CustomerSAPCode']=$customer_val->CustomerSAPCode;
					$data['customer_list'][$ic]['customer_number']=$customer_val->customer_number;
					$data['customer_list'][$ic]['approve_status']=$customer_val->approve_status;
					$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;
					$data['customer_list'][$ic]['Description']=$customer_val->Description;
					$data['customer_list'][$ic]['Phone']=$customer_val->Phone;
					$data['customer_list'][$ic]['Website'] = $customer_val->Website;
					$data['customer_list'][$ic]['AccountSource']=$customer_val->AccountSource;
					$data['customer_list'][$ic]['AnnualRevenue']=$customer_val->AnnualRevenue;
					$data['customer_list'][$ic]['GSTINNumber']=$customer_val->GSTINNumber;
					$data['customer_list'][$ic]['Employees']=$customer_val->Employees;
					$data['customer_list'][$ic]['contact_id']=$customer_val->contact_id;
					$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
					
					if($customer_val->PaymentTerms != 0 || $customer_val->PaymentTerms != "" || $customer_val->PaymentTerms != NULL){
						$PaymentTerms_list = $this->db->query("select * from Payment_terms where Payment_term_id =".$customer_val->PaymentTerms)->row();
						$data['customer_list'][$ic]['PaymentTerms']=$PaymentTerms_list->Payment_name;
					}
					
					$data['customer_list'][$ic]['pancard']=$customer_val->pancard;
					
					// Billing Street Info
					$data['customer_list'][$ic]['BillingStreet1']=$customer_val->BillingStreet1;
					$data['customer_list'][$ic]['Billingstreet2']=$customer_val->Billingstreet2;
					$data['customer_list'][$ic]['BillingCountry']=$customer_val->BillingCountry;
					$data['customer_list'][$ic]['StateProvince']=$customer_val->StateProvince;
					$data['customer_list'][$ic]['BillingCity']=$customer_val->BillingCity;
					$data['customer_list'][$ic]['BillingZipPostal']=$customer_val->BillingZipPostal;
					
					// Shipping Street Info
					$data['customer_list'][$ic]['ShippingStreet1']=$customer_val->ShippingStreet1;
					$data['customer_list'][$ic]['Shippingstreet2']=$customer_val->Shippingstreet2;
					$data['customer_list'][$ic]['ShippingCountry']=$customer_val->ShippingCountry;
					$data['customer_list'][$ic]['ShippingStateProvince']=$customer_val->ShippingStateProvince;
					$data['customer_list'][$ic]['ShippingCity']=$customer_val->ShippingCity;				
					$data['customer_list'][$ic]['ShippingZipPostal']=$customer_val->ShippingZipPostal;
					
					// Sales Organization				
					if($customer_val->SalesOrganisation != "" || $customer_val->SalesOrganisation != NULL){
						$SalesOrganisation_list = $this->db->query("select * from sales_organisation where sap_code= '".$customer_val->SalesOrganisation."'")->row();
						if(count($SalesOrganisation_list)>0){
							$data['customer_list'][$ic]['SalesOrganisation_id']=$SalesOrganisation_list->sap_code;
							$data['customer_list'][$ic]['SalesOrganisation']=$SalesOrganisation_list->organistation_name;
						}else{
							$data['customer_list'][$ic]['SalesOrganisation_id']="";
							$data['customer_list'][$ic]['SalesOrganisation']="";
						}
					}else{
						$data['customer_list'][$ic]['SalesOrganisation_id']="";
						$data['customer_list'][$ic]['SalesOrganisation']="";
					}
					
					// Distribution Channel
					if($customer_val->DistributionChannel != "" || $customer_val->DistributionChannel != NULL){
						$DistributionChannel_list = $this->db->query("select * from DistributionChannel where sap_code= '".$customer_val->DistributionChannel."'")->row();
						if(count($DistributionChannel_list)>0){
							$data['customer_list'][$ic]['DistributionChannel_id']=$DistributionChannel_list->sap_code;
							$data['customer_list'][$ic]['DistributionChannel']=$DistributionChannel_list->ditribution_name;
						}else{
							$data['customer_list'][$ic]['DistributionChannel_id']="";
							$data['customer_list'][$ic]['DistributionChannel']="";
						}
					}else{
						$data['customer_list'][$ic]['DistributionChannel_id']="";
						$data['customer_list'][$ic]['DistributionChannel']="";
					}
					
					// Division Info
					/*
					if($customer_val->Division != "" || $customer_val->Division != NULL){
						$Division_list = $this->db->query("select * from division_master where division_master_id = '".$customer_val->Division."'")->row();
						if(count($Division_list)>0){
							$data['customer_list'][$ic]['division_master_id']=$Division_list->division_master_id;
							$data['customer_list'][$ic]['Division']=$Division_list->division_name;
						}else{
							$data['customer_list'][$ic]['division_master_id']="";
							$data['customer_list'][$ic]['Division']="";
						}
					}else{
						$data['customer_list'][$ic]['division_master_id']="";
						$data['customer_list'][$ic]['Division']="";
					}
					*/

					$data['customer_list'][$ic]['Division']=$customer_val->Division;
					$data['customer_list'][$ic]['CustomerType']=$customer_val->CustomerType;
					$data['customer_list'][$ic]['Email']=$customer_val->Email;
					$data['customer_list'][$ic]['CustomerCategory']=$customer_val->CustomerCategory;
					$data['customer_list'][$ic]['CreditLimit']=$customer_val->CreditLimit;
					$data['customer_list'][$ic]['SecurityInstruments']=$customer_val->SecurityInstruments;
					$data['customer_list'][$ic]['Pdc_Check_number']=$customer_val->Pdc_Check_number;
					$data['customer_list'][$ic]['Bank']=$customer_val->Bank;
					$data['customer_list'][$ic]['Bank_guarntee_amount_Rs']=$customer_val->Bank_guarntee_amount_Rs;
					$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;

					if($customer_val->IncoTerms1 != 0 || $customer_val->IncoTerms1 != "" || $customer_val->IncoTerms1 != NULL){
						$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms1)->row();
						$data['customer_list'][$ic]['IncoTerms1']=$IncoTerms_list->Incoterm_name;
					}
			
					if($customer_val->IncoTerms2 != 0 || $customer_val->IncoTerms2 != "" || $customer_val->IncoTerms2 != NULL){
						$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms2)->row();
						$data['customer_list'][$ic]['IncoTerms2']=$IncoTerms_list->Incoterm_name;
					}

					$data['customer_list'][$ic]['Fax']=$customer_val->Fax;
					$data['customer_list'][$ic]['Industry']=$customer_val->Industry;
					$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;
					
					$customer_price_list = $this->db->query("select * from customer_price_list a inner join product_price_master b on (a.price_list_id = b.Product_price_master_id) where  a.customer_id ='".$customer_val->customer_id."' and a.status ='Active'")->row();
					
					if($customer_price_list->price_list_id != 0 || $customer_price_list->price_list_id != "" || $customer_price_list->price_list_id != NULL){
						$data['customer_list'][$ic]['price_list_id']=$customer_price_list->price_list_id;
					}else{
						$data['customer_list'][$ic]['price_list_id']="";
					}

					$data['customer_list'][$ic]['ParentAccount']=$customer_val->ParentAccount;
					$data['customer_list'][$ic]['created_by']=$customer_val->created_by;
					$data['customer_list'][$ic]['manager_user_id']=$customer_val->manager_user_id;
					$data['customer_list'][$ic]['created_date_time']=$customer_val->createdDateTime;
					
					// Get Sales, Bill & Ship to Party details
					$sbs_list = $this->db->query("SELECT * FROM customer_address_sold_bill_ship WHERE customer_id = ".$customer_val->customer_id)->result();
					$sbs_list = $this->db->query("SELECT * FROM customer_address_sold_bill_ship WHERE customer_id = ".$customer_val->customer_id)->result();
					if(count($sbs_list) > 0){
						$x = 0;
						foreach($sbs_list as $record){
							$data['customer_list'][$ic][$record->type.'_to_party'][] = $record;
						}
					}
			
					if(count($customer_user_list)>0){
						$jc=0;
						foreach($customer_user_list as $customer_user_val){
							$data['customer_list'][$ic]['user_details'][$jc]["customer_user_id"]=$customer_user_val->customer_user_id;
							$data['customer_list'][$ic]['user_details'][$jc]["user_name"]=$customer_user_val->name;
							$jc++;
						}
					}else{
						$data['customer_list'][$ic]['user_details'] = array();
					}				
					$ic++; 
				}
			}else{
				$data['customer_list'] = array();
			}
			
			
		
		}

		// Get Contacts Master Records
		if($table_name=='contacts'){
		
			$contacts_list = $this->db->query("select a.*,a.created_date_time as createdDateTime, b.*, c.CustomerName, c.customer_id, a.isAccountTagged from contacts a inner join users b on (a.ContactOwner = b.user_id) left join customers c on (a.Company = c.customer_id) where a.ContactOwner in (".$final_users_id.") and  a.archieve != 1 order by a.contact_id DESC")->result();

			if(count($contacts_list) > 0){				
				$ic=0;
				foreach($contacts_list as $contact_val){
					if($contact_val->ReportsTo == "" || $contact_val->ReportsTo == NULL ||$contact_val->ReportsTo == 0){
						$data['contact_list'][$ic]['ReportsTo_name'] = "";
						$data['contact_list'][$ic]['ReportsTo'] = "";
					}else{
						$report_detatis = $this->db->query("select * from contacts where contact_id =".$contact_val->ReportsTo)->row();
					  
						if(count($report_detatis)>0){
							$data['contact_list'][$ic]['ReportsTo_name'] = $report_detatis->FirstName ." ". $report_detatis->LastName;
							$data['contact_list'][$ic]['ReportsTo'] = $contact_val->ReportsTo;
						}else{
							$data['contact_list'][$ic]['ReportsTo_name'] = "";
							$data['contact_list'][$ic]['ReportsTo'] = "";
						}
					}
					
					$data['contact_list'][$ic]['isAccountTagged'] = $contact_val->isAccountTagged;
					
					if($contact_val->isAccountTagged == 0){						
						$data['contact_list'][$ic]['customer_id'] = 0;
						$data['contact_list'][$ic]['Company'] = 0;
						$data['contact_list'][$ic]['Company_text'] = $contact_val->Company_text;
					}else{						
						$data['contact_list'][$ic]['customer_id'] = $contact_val->customer_id;
						$data['contact_list'][$ic]['Company'] = $contact_val->customer_id;
						$data['contact_list'][$ic]['Company_text'] = $contact_val->CustomerName;
					}
					
					$data['contact_list'][$ic]['contact_id'] = $contact_val->contact_id;
					$data['contact_list'][$ic]['Salutation'] = $contact_val->Salutation;
					$data['contact_list'][$ic]['FirstName'] = $contact_val->FirstName;
					$data['contact_list'][$ic]['LastName'] = $contact_val->LastName;
					$data['contact_list'][$ic]['Email'] = $contact_val->Email;
					$data['contact_list'][$ic]['Fax'] = $contact_val->Fax;
					$data['contact_list'][$ic]['Mobile'] = $contact_val->Mobile;
					$data['contact_list'][$ic]['Phone'] = $contact_val->Phone;
					$data['contact_list'][$ic]['Department'] = $contact_val->Department;
					$data['contact_list'][$ic]['Title_Designation'] = $contact_val->Title_Designation;
					$data['contact_list'][$ic]['OtherPhone'] = $contact_val->OtherPhone;
					$data['contact_list'][$ic]['HomePhone'] = $contact_val->HomePhone;
					$Birthdate = date("d-m-Y",strtotime($contact_val->Birthdate));
					
					if($Birthdate == "30-11-0001" || $Birthdate == "01-01-1970" || $Birthdate == NULL){
						$data['contact_list'][$ic]['Birthdate'] = "";
					}else{
						$data['contact_list'][$ic]['Birthdate'] = $Birthdate;
					}
					
					$data['contact_list'][$ic]['Description'] = $contact_val->Description;
					$data['contact_list'][$ic]['LeadSource'] = $contact_val->LeadSource;
					$data['contact_list'][$ic]['ContactOwner'] = $contact_val->ContactOwner;
					$data['contact_list'][$ic]['ContactOwner_name'] = $contact_val->name;
					$data['contact_list'][$ic]['Category'] = $contact_val->Category;
					$data['contact_list'][$ic]['MallingStreet1'] = $contact_val->MallingStreet1;
					$data['contact_list'][$ic]['Mallingstreet2'] = $contact_val->Mallingstreet2;
					$data['contact_list'][$ic]['MallingCountry'] = $contact_val->MallingCountry;
					$data['contact_list'][$ic]['MallingStateProvince'] = $contact_val->MallingStateProvince;
					$data['contact_list'][$ic]['MallingCity'] = $contact_val->MallingCity;
					$data['contact_list'][$ic]['MallingZipPostal'] = $contact_val->MallingZipPostal;
					$data['contact_list'][$ic]['OtherStreet1'] = $contact_val->OtherStreet1;
					$data['contact_list'][$ic]['Otherstreet2'] = $contact_val->Otherstreet2;
					$data['contact_list'][$ic]['OtherCountry'] = $contact_val->OtherCountry;
					$data['contact_list'][$ic]['OtherStateProvince'] = $contact_val->OtherStateProvince;
					$data['contact_list'][$ic]['OtherCity'] = $contact_val->OtherCity;
					$data['contact_list'][$ic]['OtherZipPostal'] = $contact_val->OtherZipPostal;
					$data['contact_list'][$ic]['created_date_time'] = $contact_val->createdDateTime;
					$ic++;
				} 
			}
		}
				
		// Get Sales Calls Master Records		
		if($table_name=='sales_call'){
			
			$sales_call_list = $this->db->query("select *,a.Phone, a.created_date_time as CreatedDateTime from sales_call a inner join users b on (a.Owner = b.user_id) where  a.Owner in (".$final_users_id.") and a.archieve != 1 order by a.sales_call_id desc")->result();
			
			if(count($sales_call_list) > 0) {
				$i=0;
				foreach($sales_call_list as $sc_list){
					$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;
					$data['sales_call_list'][$i]['releted_to'] = $sc_list->releted_to;
					$data['sales_call_list'][$i]['sales_call_customer_contact_type'] = $sc_list->sales_call_customer_contact_type;
					$data['sales_call_list'][$i]['id'] = $sc_list->id;
					$data['sales_call_list'][$i]['Company'] = $sc_list->Company;
					$data['sales_call_list'][$i]['releted_to_new_contact_customer'] = $sc_list->releted_to_new_contact_customer;
					$data['sales_call_list'][$i]['new_contact_customer_person_name'] = $sc_list->new_contact_customer_person_name;
					$data['sales_call_list'][$i]['new_contact_customer_company_name'] = $sc_list->new_contact_customer_company_name;
					$data['sales_call_list'][$i]['new_contact_customer_other_person_name'] = $sc_list->new_contact_customer_other_person_name;
					$data['sales_call_list'][$i]['Call_Date'] = $sc_list->Call_Date;

					if($call_date == "00-00-0000" || $call_date == "01-01-1970" || $call_date == NULL){
						$data['sales_call_list'][$i]['Call_Date'] = "";
					}else{
						$data['sales_call_list'][$i]['Call_Date'] = date("Y-m-d",strtotime($sc_list->Call_Date));
					}
					
					$data['sales_call_list'][$i]['Phone'] = $sc_list->Phone;
					$data['sales_call_list'][$i]['Call_Type'] = $sc_list->Call_Type;
					$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
					$data['sales_call_list'][$i]['call_report'] = $sc_list->call_report;
					$data['sales_call_list'][$i]['MinutesOfMeeting'] = $sc_list->MinutesOfMeeting;
					$data['sales_call_list'][$i]['CommentsByManager'] = $sc_list->CommentsByManager;
					
					$data['sales_call_list'][$i]['Status'] = $sc_list->Status;
					$data['sales_call_list'][$i]['sales_calls_temp_id'] = $sc_list->sales_calls_temp_id;
					$data['sales_call_list'][$i]['Owner'] = $sc_list->Owner;
					$data['sales_call_list'][$i]['Owner_name'] = $sc_list->name;
					$data['sales_call_list'][$i]['tracking_id'] = $sc_list->tracking_id;
					$data['sales_call_list'][$i]['lat_lon_val'] = $sc_list->lat_lon_val;
					$data['sales_call_list'][$i]['geo_status'] = $sc_list->geo_status;
					$data['sales_call_list'][$i]['Assigned_To'] = user_details($sc_list->Assigned_To);
					$data['sales_call_list'][$i]['Assigned_To_id'] = $sc_list->Assigned_To;

					if($sc_list->contacts_id != ""||$sc_list->contacts_id != NULL || $sc_list->contacts_id != 0){
						$contacts_list = $this->db->query("select * from contacts where contact_id =".$sc_list->contacts_id)->row();
						if(count($contacts_list)>0){
							$data['sales_call_list'][$i]['contact_id'] = $sc_list->contacts_id;
							$data['sales_call_list'][$i]['contact_name'] = $contacts_list->FirstName." ".$contacts_list->LastName;
						}else{
							$data['sales_call_list'][$i]['contact_id'] = 0;
							$data['sales_call_list'][$i]['contact_name'] = "";
						}
					}else{
						$data['sales_call_list'][$i]['contact_id'] = 0;
						$data['sales_call_list'][$i]['contact_name'] = "";
					}
				  
					$data['sales_call_list'][$i]['Description'] = $sc_list->Description;
					$call_date = date("d-m-Y",strtotime($sc_list->Call_Date));
				  
					$data['sales_call_list'][$i]['Email'] = $sc_list->Email;
					$data['sales_call_list'][$i]['Comments'] = $sc_list->Comments;
					$NextVisitDate = date("d-m-Y",strtotime($sc_list->NextVisitDate));
				  
					if($NextVisitDate == "00-00-0000" || $NextVisitDate == "01-01-1970" || $NextVisitDate == NULL){
						$data['sales_call_list'][$i]['NextVisitDate'] = "";
					}else{
						$data['sales_call_list'][$i]['NextVisitDate'] = date("Y-m-d",strtotime($sc_list->NextVisitDate));
					}
					
					$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
					$data['sales_call_list'][$i]['MinutesOfMeeting'] =  $sc_list->MinutesOfMeeting;
					$data['sales_call_list'][$i]['CommentsByManager '] = $sc_list->CommentsByManager;
					$data['sales_call_list'][$i]['created_date_time'] = $sc_list->CreatedDateTime;

					$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;

					$i++;
				}
			}
		}

		// Get TADA Allowances Master Records
		if($table_name=='ta_da_allowances'){
			$tada_list = $this->db->query("select * from  ta_da_allowances a inner join users b on (a.Name = b.user_id) where  a.created_by in (".$final_users_id.") and a.archieve !=1")->result();
			if(count($tada_list) > 0){
				$i = 0;
				foreach($tada_list as $rec){
					unset($rec->password);
					$data['tada_list'][$i] = $rec;
					$i++;
				}
			}			
		}
		
		// Get Complaints Master Records
		if($table_name=='complaints'){
			
			$complaint_list=$this->db->query("select * from Complaints a inner join users b on (a.ComplaintOwner = b.user_id) where  a.ComplaintOwner in (".$final_users_id.") and  a.archieve !=1 order by a.Complaint_id DESC")->result();
			
			if(count($complaint_list)>0){
				$i=0;
				foreach ($complaint_list as $value) {
					$complanints_ass_recc_list = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where Complaint_id = '".$value->Complaint_id."'")->result();

					$data['complaint_list'][$i]['sales_assessment'] =array();
					$data['complaint_list'][$i]['sales_recommendedsolution'] = array();
					$data['complaint_list'][$i]['area_assessment'] = array();
					$data['complaint_list'][$i]['area_recommendedsolution'] = array();
					$data['complaint_list'][$i]['regional_assessment'] =array();
					$data['complaint_list'][$i]['regional_recommendedsolution'] = array();
					$data['complaint_list'][$i]['national_assessment'] = array();
					$data['complaint_list'][$i]['national_recommendedsolution'] = array();
					$data['complaint_list'][$i]['quality_assessment'] = array();
					$data['complaint_list'][$i]['quality_recommendation'] = array();
					$data['complaint_list'][$i]['manufacturing_assessment'] = array();
					$data['complaint_list'][$i]['management_assessment'] = array();
					$data['complaint_list'][$i]['management_recommendation'] = array();
					$ik=$j=$k=$l=$m=$n=$o=$p=$q=$r=$s=$oi=$pi=0;
					foreach($complanints_ass_recc_list as $comp_ass_recc_val){
						$profile_id = $comp_ass_recc_val->profile_id;
						$type = $comp_ass_recc_val->type;
						$complent_recomendation = $comp_ass_recc_val->complent_recomendation;
						$com_ass_rec_id = $comp_ass_recc_val->complaints_aeeigment_recommendation_id;						
						if($profile_id == SALESOFFICER && $type == "assessment" || $profile_id == SalesExecutive && $type == "assessment"){
							$data['complaint_list'][$i]['sales_assessment'][$ik]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['sales_assessment'][$ik]['complement_ass_name'] = $complent_recomendation;
							$ik++;
						}else if($profile_id == SALESOFFICER && $type == "recommendation" || $profile_id == SalesExecutive && $type == "recommendation" ){
							$data['complaint_list'][$i]['sales_recommendedsolution'][$j]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['sales_recommendedsolution'][$j]['complement_ass_name'] = $complent_recomendation;
							$j++;
						}else if($profile_id == AreaManager && $type == "assessment"){
							$data['complaint_list'][$i]['area_assessment'][$k]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['area_assessment'][$k]['complement_ass_name'] = $complent_recomendation;
							$k++;
						}else if($profile_id == AreaManager && $type == "recommendation" ){
							$data['complaint_list'][$i]['area_recommendedsolution'][$l]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['area_recommendedsolution'][$l]['complement_ass_name'] = $complent_recomendation;
							$l++;
						}else if($profile_id == Regionalmanager && $type == "assessment"){
							$data['complaint_list'][$i]['regional_assessment'][$m]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['regional_assessment'][$m]['complement_ass_name'] = $complent_recomendation;
							$m++;
						}else if($profile_id == Regionalmanager && $type == "recommendation" ){
							$data['complaint_list'][$i]['regional_recommendedsolution'][$n]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['regional_recommendedsolution'][$n]['complement_ass_name'] = $complent_recomendation;
							$n++;
						}else if($profile_id == NationalHead && $type == "assessment"){
							$data['complaint_list'][$i]['national_assessment'][$o]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['national_assessment'][$o]['complement_ass_name'] = $complent_recomendation;
							$o++;
						}else if($profile_id == NationalHead && $type == "recommendation" ){
							$data['complaint_list'][$i]['national_recommendedsolution'][$p]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['national_recommendedsolution'][$p]['complement_ass_name'] = $complent_recomendation;
							$p++;
						}else if($profile_id == QualityDepartment && $type == "assessment"){
							$data['complaint_list'][$i]['quality_assessment'][$oi]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['quality_assessment'][$oi]['complement_ass_name'] = $complent_recomendation;
							$oi++;
						}else if($profile_id == QualityDepartment && $type == "recommendation" ){
							$data['complaint_list'][$i]['quality_recommendation'][$pi]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['quality_recommendation'][$pi]['complement_ass_name'] = $complent_recomendation;
							$pi++;
						}else if($profile_id == Manufacturing && $type == "assessment"){
							$data['complaint_list'][$i]['manufacturing_assessment'][$q]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['manufacturing_assessment'][$q]['complement_ass_name'] = $complent_recomendation;
							$q++;
						}else if($profile_id == SUPERADMIN && $type == "assessment"){
							$data['complaint_list'][$i]['management_assessment'][$r]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['management_assessment'][$r]['complement_ass_name'] = $complent_recomendation;
							$r++;
						}else if($profile_id == SUPERADMIN && $type == "recommendation" ){
							$data['complaint_list'][$i]['management_recommendation'][$s]['com_ass_rec_id'] = $com_ass_rec_id;
							$data['complaint_list'][$i]['management_recommendation'][$s]['complement_ass_name'] = $complent_recomendation;
							$s++;
						}
					}

					$data['complaint_list'][$i]['complaint_id']=$value->Complaint_id;
					$customer_list = $this->db->query("select * from customers where customer_id ='".$value->CustomerName."'")->row();
					if(($customer_list) >0){
						$data['complaint_list'][$i]['customer_id']=$customer_list->customer_id;
						$data['complaint_list'][$i]['CustomerName']=$customer_list->CustomerName;
					}else{
						$data['complaint_list'][$i]['customer_id']='';
						$data['complaint_list'][$i]['CustomerName']='';
					}
					$data['complaint_list'][$i]['ComplaintNumber'] = $value->ComplaintNumber;
					$data['complaint_list'][$i]['salesorderdate']=$value->salesorderdate;
					$data['complaint_list'][$i]['salesordernumber'] = $value->salesordernumber;
					$data['complaint_list'][$i]['feedback']=$value->feedback;
					$data['complaint_list'][$i]['applicationdate']=$value->applicationdate;
					$data['complaint_list'][$i]['feedbackother']=$value->feedbackother;
					$data['complaint_list'][$i]['invoicedate']=$value->invoicedate;
					$data['complaint_list'][$i]['invoicenumber']=$value->invoicenumber;
					$data['complaint_list'][$i]['batchnumber']=$value->batchnumber;
					$data['complaint_list'][$i]['defectivesample']=$value->defectivesample;
					$data['complaint_list'][$i]['sampleplantlab']=$value->sampleplantlab;
					$data['complaint_list'][$i]['sales_sitevisit']=$value->sales_sitevisit;
					$data['complaint_list'][$i]['sales_status']=$value->sales_status;
					$data['complaint_list'][$i]['area_sitevisit']=$value->area_sitevisit;					
					$data['complaint_list'][$i]['area_status']=$value->area_status;
					$data['complaint_list'][$i]['regional_sitevisit']=$value->regional_sitevisit;
					$data['complaint_list'][$i]['regional_status']=$value->regional_status;
					$data['complaint_list'][$i]['national_sitevisit']=$value->national_sitevisit;
					$data['complaint_list'][$i]['national_status']=$value->national_status;
					$data['complaint_list'][$i]['credit_note_given']=$value->credit_note_given;
					$data['complaint_list'][$i]['material_replaced']=$value->material_replaced;
					$data['complaint_list'][$i]['comercial_remarks']=$value->comercial_remarks;
					$data['complaint_list'][$i]['qualitytestsdone']=$value->qualitytestsdone;
					$data['complaint_list'][$i]['created_date_time']=$value->created_date_time;					
					$i++;
				}
			}else{
				$data['complaint_list'] = array();
			}
		}
		
		// Get Opportunities Master Records
		if($table_name=='opportunities'){
			
			$opportunities_list_val = $this->db->query("select *, a.remarks as Opp_remarks, c.name as OwnerName from opportunities a left join customers b on (b.customer_id = a.Company) inner join users c on (c.user_id = a.OpportunityOwner) where a.OpportunityOwner in (".$final_users_id.") and a.archieve != 1")->result();
			
			if(count($opportunities_list_val)>0){
				$i=0;
				foreach($opportunities_list_val as $opp_list){
					
					if($opp_list->opportunity_main_contact_id != NULL || $opp_list->opportunity_main_contact_id != ''){
						$main_contact = $this->db->query("SELECT FirstName, LastName from contacts WHERE contact_id = ".$opp_list->opportunity_main_contact_id)->row();
						$main_contact_name = $main_contact->FirstName." ".$main_contact->LastName;
					}else{
						$main_contact_name = NULL;
					}
							
					$leadInfo = $this->db->query("SELECT leads_id,lead_size_class_of_project FROM leads WHERE lead_number = '".$opp_list->Leadno."'")->row();

					$data['opportunities_list'][$i]['opportunity_id'] = $opp_list->opportunity_id;
					$data['opportunities_list'][$i]['opp_id'] = $opp_list->opp_id;
					$data['opportunities_list'][$i]['OwnerName'] = $opp_list->OwnerName;
					$data['opportunities_list'][$i]['leads_id'] = $leadInfo->leads_id;
					$data['opportunities_list'][$i]['Leadno'] = $opp_list->Leadno;
					$data['opportunities_list'][$i]['Company'] = $opp_list->Company;
					$data['opportunities_list'][$i]['Company_Text'] = $opp_list->Company_text;
					$data['opportunities_list'][$i]['sampling'] = $opp_list->sampling;
					$data['opportunities_list'][$i]['mockup'] = $opp_list->mockup;
					$data['opportunities_list'][$i]['Rating'] = $opp_list->Rating;
					$data['opportunities_list'][$i]['project_name'] = $opp_list->project_name;
					$data['opportunities_list'][$i]['project_type'] = $opp_list->project_type;
					$data['opportunities_list'][$i]['size_calss_unit'] = $opp_list->size_calss_unit;
					$data['opportunities_list'][$i]['size_class_project'] = $opp_list->size_class_project;
					$data['opportunities_list'][$i]['lead_class_of_project'] = $opp_list->lead_class_of_project;
					$data['opportunities_list'][$i]['lead_size_class_of_project'] = $leadInfo->lead_size_class_of_project;
					$data['opportunities_list'][$i]['size_calss_unit_no_of_blocks'] = $opp_list->size_calss_unit_no_of_blocks;
					$data['opportunities_list'][$i]['size_calss_unit_no_of_floor_per_block'] = $opp_list->size_calss_unit_no_of_floor_per_block;
					
					// Billing Details
					$data['opportunities_list'][$i]['status_project'] = $opp_list->status_project;
					$data['opportunities_list'][$i]['BillingStreet1'] = $opp_list->BillingStreet1;
					$data['opportunities_list'][$i]['BillingStreet2'] = $opp_list->Billingstreet2;
					$data['opportunities_list'][$i]['BillingCountry'] = $opp_list->BillingCountry;
					$data['opportunities_list'][$i]['BillingState'] = $opp_list->BillingState;
					$data['opportunities_list'][$i]['BillingCity'] = $opp_list->BillingCity;
					$data['opportunities_list'][$i]['BillingZipPostal'] = $opp_list->BillingZipPostal;
					$data['opportunities_list'][$i]['BillingArea'] = $opp_list->BillingArea;
					$data['opportunities_list'][$i]['BillingPlotno'] = $opp_list->BillingPlotno;
					$data['opportunities_list'][$i]['BillingWebsite'] = $opp_list->BillingWebsite;
					$data['opportunities_list'][$i]['BillingEmail'] = $opp_list->BillingEmail;
					$data['opportunities_list'][$i]['BillingPhone'] = $opp_list->BillingPhone;
					
					// Shipping Details
					$data['opportunities_list'][$i]['ShippingStreet1'] = $opp_list->ShippingStreet1;
					$data['opportunities_list'][$i]['Shippingstreet2'] = $opp_list->Shippingstreet2;
					$data['opportunities_list'][$i]['ShippingLandmark'] = $opp_list->ShippingLandmark;
					$data['opportunities_list'][$i]['Shippingplotno'] = $opp_list->Shippingplotno;
					$data['opportunities_list'][$i]['ShippingCountry'] = $opp_list->ShippingCountry;
					$data['opportunities_list'][$i]['ShippingStateProvince'] = $opp_list->ShippingStateProvince;
					$data['opportunities_list'][$i]['ShippingCity'] = $opp_list->ShippingCity;
					$data['opportunities_list'][$i]['ShippingZipPostal'] = $opp_list->ShippingZipPostal;
					
					$data['opportunities_list'][$i]['opportunity_main_contact_id'] = $opp_list->opportunity_main_contact_id;
					$data['opportunities_list'][$i]['opportunity_main_contact_name'] = $main_contact_name;
					$data['opportunities_list'][$i]['opportunity_main_contact_designation'] = $opp_list->opportunity_main_contact_designation;
					$data['opportunities_list'][$i]['opportunity_main_contact_email'] = $opp_list->opportunity_main_contact_email;
					$data['opportunities_list'][$i]['opportunity_main_contact_mobile'] = $opp_list->opportunity_main_contact_mobile;
					$data['opportunities_list'][$i]['opportunity_main_contact_category'] = $opp_list->opportunity_main_contact_category;
					$data['opportunities_list'][$i]['opportunity_main_contact_phone'] = $opp_list->opportunity_main_contact_phone;
					$data['opportunities_list'][$i]['opportunity_main_contact_company'] = $opp_list->opportunity_main_contact_company;
					
					$data['opportunities_list'][$i]['no_of_flats'] = $opp_list->no_of_flats;
					$data['opportunities_list'][$i]['cubic_meters'] = $opp_list->cubic_meters;
					$data['opportunities_list'][$i]['sft'] = $opp_list->sft;
					$data['opportunities_list'][$i]['remarks'] = $opp_list->Opp_remarks;
					$data['opportunities_list'][$i]['Finalizationdate'] = $opp_list->Finalizationdate;
					$data['opportunities_list'][$i]['requirement_details_collected'] = $opp_list->requirement_details_collected;
					$data['opportunities_list'][$i]['business_status'] = $opp_list->business_status;
					$data['opportunities_list'][$i]['business_status_delayed_value'] = $opp_list->business_status_delayed_value;
					$data['opportunities_list'][$i]['business_status_pending_value'] = $opp_list->business_status_pending_value;
					$data['opportunities_list'][$i]['business_status_lost_value'] = $opp_list->business_status_lost_value;
					$data['opportunities_list'][$i]['business_status_lost_other_value'] = $opp_list->business_status_lost_other_value; 
					
					$data['opportunities_list'][$i]['created_date_time'] = $opp_list->created_date_time;

					$Associate_contact_id = $opp_list->opportunity_main_contact_id;
					if($Associate_contact_id != "" || $Associate_contact_id != NULL){
						$contact_list_a = $this->db->query("select OAC.contact_id, OAC.designation, C.FirstName, C.LastName from opportunity_associate_contacts OAC inner join contacts C on (OAC.contact_id = C.contact_id) where opportunity = ".$opp_list->opportunity_id)->result();
						$c=0;
						foreach($contact_list_a as $assoc_val){
							$data['opportunities_list'][$i]['associate_contact'][$c]["contact_id"] = $assoc_val->contact_id;
							$data['opportunities_list'][$i]['associate_contact'][$c]["contact_name"] = $assoc_val->FirstName." ".$assoc_val->LastName;
							$data['opportunities_list'][$i]['associate_contact'][$c]["designation"] = $assoc_val->designation;
							$c++;
						}
					}else{
						$data['opportunities_list'][$i]['associate_contact'] = array();
					}

					$checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$opp_list->customer_id."'")->row();
				   
					$product_opportunitie_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity ='".$opp_list->opportunity_id."' group by b.product_code")->result();
				  
					if(count($product_opportunitie_list) >0){
						$j=0;
						foreach($product_opportunitie_list as $popp_list){
							$data['opportunities_list'][$i]['final_product'][$j]['Product_opportunities_id'] = $popp_list->Product_opportunities_id;
							$data['opportunities_list'][$i]['final_product'][$j]['product_id'] = $popp_list->Product;
							$data['opportunities_list'][$i]['final_product'][$j]['product_name'] = $popp_list->product_name;
							$data['opportunities_list'][$i]['final_product'][$j]['probability'] = $popp_list->Probability;
							$data['opportunities_list'][$i]['final_product'][$j]['quantity'] = $popp_list->Quantity;
							$data['opportunities_list'][$i]['final_product'][$j]['rate_per_sft'] = $popp_list->final_product_price;
							$data['opportunities_list'][$i]['final_product'][$j]['value'] = $popp_list->final_product_value;
							$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_from'] = date("d-m-Y",strtotime($popp_list->schedule_date_from));
							$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_upto'] = date("d-m-Y",strtotime($popp_list->schedule_date_upto));
							$j++;
						}
					}else{
						$data['opportunities_list'][$i]['final_product'] = array();
					}

					$brand_producta_list = $this->db->query("select * from Products_Brands_targeted_opp where Opportunity =".$opp_list->opportunity_id)->result();
				  
					if(count($brand_producta_list)>0){
						$k=0;
						foreach($brand_producta_list as $brand_product_val){
							$data['opportunities_list'][$i]['brands_product'][$k]['brands_opp_id'] = $brand_product_val->brands_opp_id;
							$data['opportunities_list'][$i]['brands_product'][$k]['product'] = $brand_product_val->Brands_Product;
							$data['opportunities_list'][$i]['brands_product'][$k]['units'] = $brand_product_val->Brands_Units;
							$data['opportunities_list'][$i]['brands_product'][$k]['quantity'] = $brand_product_val->Brands_Quantity;
							$data['opportunities_list'][$i]['brands_product'][$k]['price'] = $brand_product_val->Brands_Price;
							$k++;
						}
					}else{
						$data['opportunities_list'][$i]['brands_product'] = array();
					}

					$Competition_targeted_list = $this->db->query("select * from Competition_targeted_opp where Opportunity = ".$opp_list->opportunity_id)->result();
					if(count($Competition_targeted_list) >0){
						$l=0;
						foreach($Competition_targeted_list as $competition_val){
							$data['opportunities_list'][$i]['competition_product'][$l]['competitions_opp_id'] = $competition_val->competitions_opp_id;                 
							$data['opportunities_list'][$i]['competition_product'][$l]['product'] = $competition_val->Competition_Product;
							$data['opportunities_list'][$i]['competition_product'][$l]['units'] = $competition_val->Competition_Units;                 
							$data['opportunities_list'][$i]['competition_product'][$l]['price'] = $competition_val->Competition_Price;
							$l++;
						}
					}else{
						$data['opportunities_list'][$i]['competition_product'] = array();
					}
					$i++;
				}
			}else{
				$data['opportunities_list'] = array();
			}			  
		}

		// Get Contract Master Records
		if($table_name == 'contract'){
			
			$contract_list = $this->db->query("select *,a.Description from contract a inner join customers b on (a.Company = b.customer_id)  where a.ContractOwner  in (".$final_users_id.") and a.archieve != 1 order by a.contract_id DESC")->result();			
			
			if(count($contract_list) >0){
				$i_c=0;
				foreach($contract_list as $contract_val){
					$CompanySignedBy_list = $this->db->query("select * from  users where user_id =".$contract_val->CompanySignedBy)->row();			  
					$ContractOwner_list = $this->db->query("select * from users where user_id =".$contract_val->ContractOwner." AND status = 'Active'")->row();
					
					if(count($CompanySignedBy_list)>0){
						$CompanySignedBy = $CompanySignedBy_list->name;
						$CompanySignedBy_id = $CompanySignedBy_list->user_id;
					}else{
						$CompanySignedBy = "";
						$CompanySignedBy_id  = "";
					}
					
					if(count($ContractOwner_list)>0){
						$ContractOwner = $ContractOwner_list->name;
					}else{
						$ContractOwner ="";
					}

					$data['contract_list'][$i_c]['contract_id'] =$contract_val->contract_id;
					$data['contract_list'][$i_c]['Customer'] =$contract_val->CustomerName;
					$data['contract_list'][$i_c]['customer_id'] =$contract_val->customer_id;
					$data['contract_list'][$i_c]['ActivatedBy'] =$contract_val->ActivatedBy;
					$data['contract_list'][$i_c]['ActivatedDate'] =date("d-m-Y",strtotime($contract_val->ActivatedDate));
					$data['contract_list'][$i_c]['BillingAddress'] =$contract_val->BillingAddress;
					$data['contract_list'][$i_c]['ShippingAddress'] =$contract_val->ShippingAddress;
					if($contract_val->CustomerSignedBy == ""||$contract_val->CustomerSignedBy == null ){
						$data['contract_list'][$i_c]['CustomerSignedBy'] ="";
						$data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
					}else{
						$contact_list =  $this->db->query("select * from contacts where contact_id =".$contract_val->CustomerSignedBy)->row();
						if(count($contact_list) >0){
							$data['contract_list'][$i_c]['CustomerSignedBy'] =$contact_list->FirstName." ".$contact_list->LastName;
							$data['contract_list'][$i_c]['CustomerSignedBy_id'] =$contact_list->contact_id;
						}else{
							$data['contract_list'][$i_c]['CustomerSignedBy'] ="";
							$data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
						}
					}
				  
					$data['contract_list'][$i_c]['CompanySignedDate'] =date("d-m-Y",strtotime($contract_val->CompanySignedDate));
					$data['contract_list'][$i_c]['ContractName'] =$contract_val->ContractName;
					$data['contract_list'][$i_c]['ContractNumber'] =$contract_val->ContractNumber;
					$data['contract_list'][$i_c]['ContractStartDate'] =date("d-m-Y",strtotime($contract_val->ContractStartDate));
					$data['contract_list'][$i_c]['ContractEndDate'] =date("d-m-Y",strtotime($contract_val->ContractEndDate));
					$data['contract_list'][$i_c]['ContractOwner'] =$ContractOwner;
					$data['contract_list'][$i_c]['ContractTerm'] =$contract_val->ContractTerm;
					$data['contract_list'][$i_c]['CustomerSignedBy'] =$contract_val->FirstName." ".$contract_val->LastName;
					$data['contract_list'][$i_c]['CompanySignedBy_id'] = $CompanySignedBy_id;
					$data['contract_list'][$i_c]['CompanySignedBy'] = $CompanySignedBy;
					$data['contract_list'][$i_c]['CustomerSignedDate'] =date("d-m-Y",strtotime($contract_val->CustomerSignedDate));
					$data['contract_list'][$i_c]['Description'] =$contract_val->Description;
					$data['contract_list'][$i_c]['total_amount'] =$contract_val->total_amount;
					$data['contract_list'][$i_c]['OwnerExpirationNotice'] =$contract_val->OwnerExpirationNotice;
					$data['contract_list'][$i_c]['SpecialTerms'] =$contract_val->SpecialTerms;
					$data['contract_list'][$i_c]['Status'] =$contract_val->Status;
					$data['contract_list'][$i_c]['created_date_time'] =$contract_val->created_date_time;
					$contract_product_list = $this->db->query("select * from contract_products a inner join product_master b on (a.Product = b.product_id) where a.Contract =".$contract_val->contract_id)->result();
				  
					if(count($contract_product_list)>0){
						$j_c=0;
						foreach($contract_product_list as $cp_list){
							$data['contract_list'][$i_c]['contract_product'][$j_c]['product_contract_id'] = $cp_list->product_contract_id;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['Product'] = $cp_list->product_name;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['Product_id'] = $cp_list->Product;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['ListPrice'] = $cp_list->ListPrice;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['Quantity'] = $cp_list->Quantity;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['Discount'] = $cp_list->Discount;
							$data['contract_list'][$i_c]['contract_product'][$j_c]['Subtotal'] = $cp_list->Subtotal;
							$j++;
						}
					}else{
						$data['contract_list'][$i_c]['contract_product'] = array();
					}

					$i_c++;
				}
			}else{
				$data['contract_list'] =array();
			}
		}
	
		// Get Sales Order Master Records
		if($table_name=='sales_order'){
			
			//$sales_order_list = $this->db->query("select *, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks from sales_order a inner join customers b on (a.Customer = b.customer_id)   where a.created_by in (".$final_users_id.") and a.archieve !=1")->result();
			$sales_order_list = $this->db->query("select a.*,b.*,c.CustomerName as delivered_by_customer_name, d.division_name, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks, u.name as OwnerName from sales_order a inner join customers b on (a.Customer = b.customer_id) left join customers c on (a.DeliveredBy_customer_id = c.customer_id) left join division_master d on (a.Division = d.division_master_id) left join users u on (a.created_by = u.user_id) where a.created_by in (".$final_users_id.") and a.archieve !=1 order by a.sales_order_id DESC")->result();
					
			if(count($sales_order_list) >0){
				$i_s=0;
				foreach($sales_order_list as $so_list){
					
					$Soldtopartycode = $so_list->Soldtopartycode;
					
					if($Soldtopartycode == "" || $Soldtopartycode == NULL){
						$Soldtopartycode_val = "";
					}else{
						$Soldtopartycode_list = $this->db->query("select * from contacts where contact_id =".$Soldtopartycode)->row();
						$Soldtopartycode_val= $Soldtopartycode_list->FirstName." ".$Soldtopartycode_list->LastName;
					}
					
					$Shiptopartycode = $so_list->Shiptopartycode;
					
					if($Shiptopartycode == "" || $Shiptopartycode == NULL){
						$Shiptopartycode_val = "";
					}else{
						$Shiptopartycode_list = $this->db->query("select * from contacts where contact_id =".$Shiptopartycode)->row();
						$Shiptopartycode_val= $Shiptopartycode_list->FirstName." ".$Shiptopartycode_list->LastName;
					}
					
					$BilltopartyCode = $so_list->BilltopartyCode;
					
					if($BilltopartyCode == "" || $BilltopartyCode == NULL){
						$BilltopartyCode_val = "";
					}else{
						$BilltopartyCode_list = $this->db->query("select * from contacts where contact_id =".$BilltopartyCode)->row();
						$BilltopartyCode_val= $BilltopartyCode_list->FirstName." ".$BilltopartyCode_list->LastName;
					}
					
					$data["sales_order_list"][$i_s]['sales_order_id'] = $so_list->sales_order_id;
					$data["sales_order_list"][$i_s]['sales_order_number'] = $so_list->sales_order_number;
					$data["sales_order_list"][$i_s]['OwnerName'] = $so_list->OwnerName;
					$data["sales_order_list"][$i_s]['contracts_id'] = $so_list->contract_id;
					$data["sales_order_list"][$i_s]['Customer'] = $so_list->CustomerName;
					$data["sales_order_list"][$i_s]['customer_id'] = $so_list->Customer;
					$data["sales_order_list"][$i_s]['OrderType'] = $so_list->OrderType;
					$data["sales_order_list"][$i_s]['OrderType_form'] = $so_list->OrderType_form;
					$data["sales_order_list"][$i_s]['SalesOrganisation'] = $so_list->SalesOrganisation;
					$data["sales_order_list"][$i_s]['DistributionChannel'] = $so_list->DistributionChannel;
					$data["sales_order_list"][$i_s]['date_of_delivery'] = $so_list->date_of_delivery;
					$data["sales_order_list"][$i_s]['delivered_by'] = $so_list->DeliveredBy;
					$data["sales_order_list"][$i_s]['delivered_by_customer_id'] = $so_list->DeliveredBy_customer_id;
					$data["sales_order_list"][$i_s]['delivered_by_customer_name'] = $so_list->delivered_by_customer_name;
					$data["sales_order_list"][$i_s]['Division'] = $so_list->Division;
					$data["sales_order_list"][$i_s]['division_name'] = $so_list->division_name;
					$data["sales_order_list"][$i_s]['Remarks'] = $so_list->SalesOrderRemarks;
					$data["sales_order_list"][$i_s]['Soldtopartycode'] = $Soldtopartycode_val;
					$data["sales_order_list"][$i_s]['Soldtopartycode_id'] = $so_list->Soldtopartycode;
					$data["sales_order_list"][$i_s]['Shiptopartycode'] = $Shiptopartycode_val;
					$data["sales_order_list"][$i_s]['Shiptopartycode_id'] = $so_list->Shiptopartycode;
					$data["sales_order_list"][$i_s]['BilltopartyCode'] = $BilltopartyCode_val;
					$data["sales_order_list"][$i_s]['BilltopartyCode_id'] = $so_list->BilltopartyCode;
					$data["sales_order_list"][$i_s]['Ponumber'] = $so_list->Ponumber;
					$data["sales_order_list"][$i_s]['CashDiscount'] = $so_list->CashDiscount;
					$data["sales_order_list"][$i_s]['SchemeDiscount'] = $so_list->SchemeDiscount;
					$data["sales_order_list"][$i_s]['QuntityDiscount'] = $so_list->QuntityDiscount;
					$data["sales_order_list"][$i_s]['withoutdiscountamount'] = $so_list->withoutdiscountamount;
					$data["sales_order_list"][$i_s]['Freight'] = $so_list->Freight;
					$data['sales_order_list'][$i_s]['freight_amount'] = $so_list->freight_amount;
					$data["sales_order_list"][$i_s]['discountAmount'] = $so_list->discountAmount;
					$data["sales_order_list"][$i_s]['Total'] = $so_list->Total;
					$data["sales_order_list"][$i_s]['order_status'] = $so_list->order_status;
					$data["sales_order_list"][$i_s]['order_status_comments'] = $so_list->order_status_comments;
					$data["sales_order_list"][$i_s]['created_date_time'] = $so_list->CreatedDateTime;
					$data["sales_order_list"][$i_s]['purchase_image'] = $so_list->purchase_image;
					$data["sales_order_list"][$i_s]['complaints_image'] = $so_list->complaints_image;
					$data["sales_order_list"][$i_s]['payment_image'] = $so_list->payment_image;
					$data["sales_order_list"][$i_s]['transfer_image'] = $so_list->transfer_image;
					
					$sales_order_product = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
					
					if(count($sales_order_product) >0){
						$j_s=0;
						foreach($sales_order_product as $sop_list){
							$plant_list = $this->db->query("select * from plant_master where plantid = '".$sop_list->plant_id."'")->row();
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['sales_order_products_id'] = $sop_list->sales_order_products_id;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['saleslineItemId'] = $sop_list->sales_order_products_id;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product'] = $sop_list->product_name;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product_id'] = $sop_list->Product;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['product_code'] = $sop_list->Productcode;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['ListPrice'] = $sop_list->ListPrice;
							$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_id'] = $sop_list->plant_id;
							$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_name'] = $plant_list->plantName;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Quantity'] = $sop_list->Quantity;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Discount'] = $sop_list->Discount;
							$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Subtotal'] = $sop_list->Subtotal;
							$j_s++;
						}
					}else{
						$data["sales_order_list"][$i_s]['sales_order_product_list'] = array();
					}
					
					// Get Sales Persons Product List
					$tpProducts = $this->db->query("select * from tp_sales_order_sales_person_distributors a inner join product_master b on (a.product_id = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
					
					if(count($tpProducts) >0){
						$cntr=0;
						foreach($tpProducts as $tpp_list){								
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['tp_sales_order_sales_person_distributors_id'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['saleslineItemId'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product'] = $tpp_list->product_name;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_id'] = $tpp_list->product_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_code'] = $tpp_list->product_code;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['plan_quantity'] = $tpp_list->plan_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['ordered_quantity'] = $tpp_list->ordered_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_quantity'] = $tpp_list->supplied_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_date'] = $tpp_list->supplied_date;
							$cntr++;
						}
					}else{
						$data["sales_order_list"][$i_s]['salesPersonsProducts'] = array();
					}

					$i_s++;
				}
			}else{
				$data["sales_order_list"] = array();
			}
			
		}
		
		// Get Quotation Master Records
		if($table_name=='Quotation'){
			 
			$qutation_list = $this->db->query("select * from Quotation  where created_by in (".$final_users_id.") and  archieve != 1")->result();
			
			if(count($qutation_list) >0){

				$iQ=0;
				foreach($qutation_list as $qutation_val){
					$customer_details = $this->db->query("select * from customers where customer_id ='".$qutation_val->Customer."'")->row();
					$contact_list = $this->db->query("select * from contacts where contact_id ='".$qutation_val->Contact."'")->row();
					$data["qutation_list"][$iQ]['Quotation_id'] = $qutation_val->Quotation_id;
					$data["qutation_list"][$iQ]['QuotationversionID'] = $qutation_val->QuotationversionID;
					$data["qutation_list"][$iQ]['Opportunity'] = $qutation_val->Opportunity;
					$data["qutation_list"][$iQ]['QuotationDate'] = date("Y-m-d",strtotime($qutation_val->QuotationDate));
					$data["qutation_list"][$iQ]['ExpiryDate'] = date("Y-m-d",strtotime($qutation_val->ExpiryDate));
					$data["qutation_list"][$iQ]['Customer'] = $customer_details->CustomerName;
					$data["qutation_list"][$iQ]['Customer_id'] = $qutation_val->Customer;
				  if(count($contact_list)>0){
				  $data["qutation_list"][$iQ]['Contact_id'] = $contact_list->contact_id;
				  $data["qutation_list"][$iQ]['Contact'] = $contact_list->FirstName." ".$contact_list->LastName;
				 }else{
				  $data["qutation_list"][$iQ]['Contact_id'] = "";
				  $data["qutation_list"][$iQ]['Contact'] = "";
				 }
				  $data["qutation_list"][$iQ]['BillingStreet1'] = $qutation_val->BillingStreet1;
				  $data["qutation_list"][$iQ]['Billingstreet2'] = $qutation_val->Billingstreet2;
				  $data["qutation_list"][$iQ]['BillingCountry'] = $qutation_val->BillingCountry;
				  $data["qutation_list"][$iQ]['StateProvince'] = $qutation_val->StateProvince;
				  $data["qutation_list"][$iQ]['BillingCity'] = $qutation_val->BillingCity;
				  $data["qutation_list"][$iQ]['BillingZipPostal'] = $qutation_val->BillingZipPostal;
				  $data["qutation_list"][$iQ]['ShippingStreet1'] = $qutation_val->ShippingStreet1;
				  $data["qutation_list"][$iQ]['Shippingstreet2'] = $qutation_val->Shippingstreet2;
				  $data["qutation_list"][$iQ]['ShippingCountry'] = $qutation_val->ShippingCountry;
				  $data["qutation_list"][$iQ]['ShippingStateProvince'] = $qutation_val->ShippingStateProvince;
				  $data["qutation_list"][$iQ]['ShippingCity'] = $qutation_val->ShippingCity;
				  $data["qutation_list"][$iQ]['ShippingZipPostal'] = $qutation_val->ShippingZipPostal;
				  $data["qutation_list"][$iQ]['TotalPrice'] = $qutation_val->TotalPrice;
				  $data["qutation_list"][$iQ]['Remarks'] = $qutation_val->Remarks;
				  $data["qutation_list"][$iQ]['created_date_time'] = $qutation_val->created_date_time;
				

			  $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$qutation_val->Customer."'")->row();
			  $qutation_product = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$data["qutation_list"][$iQ]['Quotation_id']."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result(); 

				  //$qutation_product = $this->db->query("select * from Quotation_Product a inner join  product_master b on (a.Product = b.product_id) where  Quotation_id = '".$qutation_val->Quotation_id."'")->result();
				  $jQ=0;
				  foreach($qutation_product as $qpp_list){
					$product_master_list = $this->db->query("select * from product_master where product_id =".$qpp_list->Product)->row();
					$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quotation_Product_id'] = $qpp_list->Quotation_Product_id;
				   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['ListPrice'] = $qpp_list->ListPrice;
				   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product'] = $qpp_list->product_name;
			   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product_id'] = $qpp_list->Product;
				   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quantity'] = $qpp_list->Quantity;
				   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Subtotal'] = $qpp_list->Subtotal;
				   $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Discount'] = $qpp_list->Discount;
				   $jQ++;
				  }
				  $iQ++;
				}
			}else{
				$data["qutation_list"] = array();
			}
		}

		// Get Payment Collection Master Records
		if($table_name=='payment_collection'){
			$payment_collection_list = $this->db->query("select * from payment_collection where owner  in (".$final_users_id.") and archieve != '1'")->result();
			if(count($payment_collection_list)>0){
				$pay_i= "0";
				foreach($payment_collection_list as $payment_val){
					
					$divisions = explode(",",$payment_val->Division);
					
					if(count($divisions) > 0){
						$x = 0;
						foreach($divisions as $division){
							$division_list = $this->db->query("select division_master_id, division_name from division_master where division_master_id = '".$division."'")->row();
							
							if($x > 0){
								$data['payment_collection_list'][$pay_i]['Division'] .= ", ";
							}
							$data['payment_collection_list'][$pay_i]['Division'] .= $division_list->division_name;
							$x++;
						}
					}else{
						$data['payment_collection_list'][$pay_i]['Division'] = $payment_val->Division;
					}	
					
					$customer_list = $this->db->query("select * from customers where customer_id ='".$payment_val->customer_id."'")->row();
					$contact_list = $this->db->query("select * from contacts where contact_id = '".$payment_val->contact_id."'")->row();
					$userInfo = $this->db->query("select user_id, name from users where user_id = '".$payment_val->created_by."'")->row();
					
					$data['payment_collection_list'][$pay_i]['payment_collection_id'] = $payment_val->payment_collection_id;
					$data['payment_collection_list'][$pay_i]['customer_name'] = $customer_list->CustomerName;
					$data['payment_collection_list'][$pay_i]['customer_id'] = $payment_val->customer_id;
					$data['payment_collection_list'][$pay_i]['contact_id'] = $payment_val->contact_id;
					$data['payment_collection_list'][$pay_i]['contact_name'] = $contact_list->FirstName." ".$contact_list->LastName;
					
					$data['payment_collection_list'][$pay_i]['invoice_number'] = $payment_val->invoice_number;
					$data['payment_collection_list'][$pay_i]['payment_mode'] = $payment_val->payment_mode;
					
					$data['payment_collection_list'][$pay_i]['customer_location'] = $payment_val->customer_location;
					$data['payment_collection_list'][$pay_i]['CustomerSAPCode'] = $customer_list->CustomerSAPCode;
					$data['payment_collection_list'][$pay_i]['comments_by_commercial_team'] = $payment_val->comments_by_commercial_team;
					$data['payment_collection_list'][$pay_i]['sales_owner_id'] = $userInfo->user_id;
					$data['payment_collection_list'][$pay_i]['sales_owner_name'] = $userInfo->name;
					$data['payment_collection_list'][$pay_i]['created_date_time'] = $payment_val->created_date_time;
										
					if($payment_val->payment_image != '' || $payment_val->payment_image != NULL){
						$data['payment_collection_list'][$pay_i]['payment_image'] = "/images/Payment/".$payment_val->payment_image;
					}else{
						$data['payment_collection_list'][$pay_i]['payment_image'] = NULL;
					}
					
					if($payment_val->payment_mode == "Cash"){
						$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
						$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
					}else if($payment_val->payment_mode == "Cheque"){
						$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
						$data['payment_collection_list'][$pay_i]['cheque_no'] = $payment_val->cheque_no;
						$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
						$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
						$data['payment_collection_list'][$pay_i]['cheque_date'] = date("Y-m-d",strtotime($payment_val->cheque_date));
						$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
						$data['payment_collection_list'][$pay_i]['status'] = $payment_val->status;      
					}else if($payment_val->payment_mode == "Online"){
						$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
						$data['payment_collection_list'][$pay_i]['transfer_type'] = $payment_val->transfer_type;
						$data['payment_collection_list'][$pay_i]['transaction_ref_no'] = $payment_val->transaction_ref_no;
						$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
						$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));  
					}        
					$pay_i++;
				}
			}else{
				$data['payment_collection_list'] = array();       
			}
		}   
		$this->response(array('code'=>'200','message'=>'List Value','result'=>$data,'requestname'=>$method));
	}

    public function isCount($mod) {
        return $mod % 95;
    }

    public function distanceCalculate($lat1, $long1, $lat2, $long2) {
        $earthRadius = 6372.795477598;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($long1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($long2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $distance = ($angle * $earthRadius) * 1000;
    }

    public function constructURL($lLR1) {
        for ($l = 0; $l < count($lLR1[0]); $l++) {
            $roadlatlon[] = $lLR1[0][$l];
        }
        return $rlatlon = implode("|", $roadlatlon);
    }

    public function callDirectionService($points, $api2, $id3) {
        $this->load->library('PolylineEncoder');
        $position = 0;
        for ($gd = $position; $gd < count($points) - 1; $gd++) {
            list($lat, $lon) = explode(",", $points[$gd]);
            list($lat1, $lon1) = explode(",", $points[$gd + 1]);
            $distanceW = $this->distanceCalculate($lat, $lon, $lat1, $lon1);

            if ($distanceW > 700) {
                if ($distanceW > 1500) {
                    $mode = 'driving';
                } else {
                    $mode = 'walking';
                }
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=" . $points[$gd] . "&destination=" . $points[$gd + 1] . "&mode=" . $mode . "&sensor=true&alternatives=false&key=" . $api2,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                ));
                $roadsnapLatLong = curl_exec($curl);
                curl_close($curl);
                $output = json_decode($roadsnapLatLong);
                $routeCount = count($output->routes);
                if ($routeCount > 0) {
                    for ($lg = 0; $lg < count($output->routes[0]->legs); $lg++) {
                        for ($k = 0; $k < count($output->routes[0]->legs[$lg]->steps); $k++) {
                            $directions = PolylineEncoder::decodeValue($output->routes[0]->legs[$lg]->steps[$k]->polyline->points);
                            for ($dd = 0; $dd < count($directions); $dd++) {
                                $mainArray1[] = $directions[$dd]['lat'] . "," . $directions[$dd]['lng'];
                            }
                        }
                    }
                }
                $position = $gd;
                $position = $position + 1;
                array_splice($points, $position, 0, $mainArray1);


                $position = $position + count($mainArray1);
                $gd = $position - 1;

                unset($mainArray1);
            }
        }
        $dis = 0;
        for ($e = 0; $e < count($points); $e++) {
            $polyArray[] = explode(",", $points[$e]);
            if ($e > 0) {
                list($lat, $lon) = explode(",", $points[$e - 1]);
                list($lat1, $lon1) = explode(",", $points[$e]);
                $ds = $this->distanceCalculate($lat, $lon, $lat1, $lon1);

                $dis = $dis + $ds;
                unset($ds);
            }
        }
        $direc = Polyline::encode($polyArray);
        $condition1 = array('tracking_id' => $id3);
        $update_track['polyline'] = $direc;
        $update_track['distance'] = $dis;
        $update_track['check_out_by'] = 1;
        $this->Generic_model->updateData('geo_tracking', $update_track, $condition1);
        // echo $this->db->last_query();
        // exit;
    }

    public function callRoadSnaps($lLR2, $url1, $num1, $finalLatLongList2, $api1, $tracking_id2) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://roads.googleapis.com/v1/snapToRoads?path=" . $url1 . "&interpolate=true&key=" . $api1,

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ));


        $roadsnapLatLong = curl_exec($curl);
        curl_close($curl);
        $output = json_decode($roadsnapLatLong);
        
        for ($rd = 0; $rd < count($output->snappedPoints); $rd++) {
            $finalLatLongList2[] = $output->snappedPoints[$rd]->location->latitude . "," . $output->snappedPoints[$rd]->location->longitude;
        }

        if (count($lLR2) > 0) {
            array_shift($lLR2);

            if (count($lLR2) > 0) {
                $this->hitGoogleServices($lLR2, $num1 + 1, $finalLatLongList2, $api1, $tracking_id2);
            } else {
                $this->callDirectionService($finalLatLongList2, $api1, $tracking_id2);
            }
        } else {
            $this->callDirectionService($finalLatLongList2, $api1, $tracking_id2);
        }
    }

    public function hitGoogleServices($lLR, $num, $finalLatLongList1, $api, $tracking_id1) {

        if (count($lLR) > 0) {

            for ($hgs = 0; $hgs < count($lLR); $hgs++) {

                if (count($lLR[$hgs]) < 5) {
                    array_splice($finalLatLongList1, count($finalLatLongList1), 0, $lLR[$hgs]);
                    array_splice($lLR, 0, 1);
                    $hgs--;
                } else {
                    goto end;
                }
            }
            end:

            if (count($lLR) > 0) {

                $url = $this->constructURL($lLR);
                $this->callRoadSnaps($lLR, $url, $num, $finalLatLongList1, $api, $tracking_id1);
            } else {

                $this->callDirectionService($finalLatLongList1, $api, $tracking_id1);
            }
        } else {
            
        }
    }



    public function geo_polyline($parameters,$method,$user_id) {
      $tracking_id = $parameters['tracking_id'];

        if ($tracking_id!= "") {
            $apk = $this->db->query("Select * from api_key")->row();
            $api_key = $apk->api_name;
            $condition = array('tracking_id' => $tracking_id);
            $existed = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
            if (count($existed) > 0) {

                //$tracking_id = $this->post('tracking_id');
                $rlatlon = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
                $string = $rlatlon->route_path_lat_lon;
                $string_0 = ltrim($string, 'null');
                $string_1 = ltrim($string_0, ':');
                $string_2 = rtrim($string_1, 'null');
                $string_3 = rtrim($string_2, ':');

                $lat_long = explode(':', $string_3);

                $this->load->library('PolylineEncoder');

                $finalLatLongList = array();
                $roadLatlonsanps = array();

                for ($i = 0; $i < count($lat_long); $i++) {
                    $lat_long1[] = explode(",", $lat_long[$i]);
                }


                if (count($lat_long1) == 0) {
                    $checkout_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();

                    $data_1['tracking_id'] = $tracking_id;
                    $data_1['check_out_time'] = $checkout_image->check_out_time;
                    $data_1['distance'] = $checkout_image->distance;
                    $data_1['polyline'] = $checkout_image->polyline;
                    $data_1['meter_reading_checkout_image'] = $checkout_image->meter_reading_checkout_image;
                    $data_1['personal_uses_km'] = $checkout_image->personal_uses_km;
                    
                    $this->response(array('code'=>200,'message'=>'successfully! done','result'=>$data_1,'requestname'=>$method));
                   // $//this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'check_out_time' => $checkout_image->check_out_time, 'distance' => $checkout_image->distance, 'polyline' => $checkout_image->polyline, 'meter_reading_checkout_image' => $checkout_image->meter_reading_checkout_image, 'personal_uses_km' => $checkout_image->personal_uses_km), 200);
                } else if (count($lat_long1) != 0 && count($lat_long1) == 1) {
                    $singleLatlong = $lat_long1[0][0] . "," . $lat_long1[0][1];
                    $polyLine = PolylineEncoder::encodeValue($singleLatlong);
                    $update_track['polyline'] = $polyLine;
                    $this->Generic_model->updateData('geo_tracking', $update_track, $condition);
                    $checkout_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row(); 

                      $data_1['tracking_id'] = $tracking_id;
                      $data_1['check_out_time'] = $checkout_image->check_out_time;
                      $data_1['distance'] = $checkout_image->distance;
                      $data_1['polyline'] = $checkout_image->polyline;
                      $data_1['meter_reading_checkout_image'] = $checkout_image->meter_reading_checkout_image;
                      $data_1['personal_uses_km'] = $checkout_image->personal_uses_km;
                      
                      $this->response(array('code'=>200,'message'=>'successfully! done','result'=>$data_1,'requestname'=>$method));

                    //$this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'check_out_time' => $checkout_image->check_out_time, 'distance' => $checkout_image->distance, 'polyline' => $checkout_image->polyline, 'meter_reading_checkout_image' => $checkout_image->meter_reading_checkout_image, 'personal_uses_km' => $checkout_image->personal_uses_km), 200);
                } else if (count($lat_long1) >= 2) {

                    
                    if (count($lat_long1) < 10) {
                        $lat_long2 = $lat_long1;
                    } else {
                        for ($k = 0; $k < count($lat_long1); $k++) {
                            $var = 0;
                            if (count($lat_long1[$k]) == 6) {
                                if ($lat_long1[$k][5] == 1 || $lat_long1[$k][5] == 2) {
                                    $var = 1;
                                }
                            }
                            if ($k == 0 || $lat_long1[$k][2] < 15 || $k == (count($lat_long1) - 1) || $var == 1) {
                                $lat_long2[] = $lat_long1[$k];
                            }
                        }
                    }
                    

                    for ($j = 0; $j < count($lat_long2); $j++) {
                        $latLng[] = $lat_long2[$j][0] . "," . $lat_long2[$j][1];

                        if ($j != 0) {
                            $latlCount = count($latLng);

                            if ($this->isCount($latlCount) == 0) {

                                $latLongRoadsnaps[] = $latLng;
                                unset($latLng);

                                $latLng[] = $lat_long2[$j][0] . "," . $lat_long2[$j][1];
                            } else if ($this->distanceCalculate($lat_long2[$j - 1][0], $lat_long2[$j - 1][1], $lat_long2[$j][0], $lat_long2[$j][1]) > 400) {

                                array_pop($latLng);
                                if (count($latLng) > 0) {
                                    $latLongRoadsnaps[] = $latLng;
                                }

                                unset($latLng);

                                $latLng[] = $lat_long2[$j][0] . "," . $lat_long2[$j][1];
                            }
                        }
                        $cc = count($lat_long2) - 1;

                        if ($this->isCount(count($latLng)) != 0 && $j == $cc) {

                            $latLongRoadsnaps[] = $latLng;
                        }
                    }
                    $startNum = 1;

                    $this->hitGoogleServices($latLongRoadsnaps, $startNum, $finalLatLongList, $api_key, $tracking_id);
                }
                $checkout_image = $this->db->query("Select * from geo_tracking where tracking_id='" . $tracking_id . "'")->row();
                
                $data['tracking_id'] = $tracking_id;
                $data['check_out_time'] = $checkout_image->check_out_time;
                $data['distance'] = $checkout_image->distance;
                $data['polyline'] = $checkout_image->polyline;
                $data['meter_reading_checkout_image'] = $checkout_image->meter_reading_checkout_image;
                $data['personal_uses_km'] = $checkout_image->personal_uses_km;
                
                $this->response(array('code'=>200,'message'=>'successfully! done','result'=>$data,'requestname'=>$method));




               // $this->response(array('status' => 'success', 'msg' => 'successfully! done', 'tracking_id' => $tracking_id, 'check_out_time' => $checkout_image->check_out_time, 'distance' => $checkout_image->distance, 'polyline' => $checkout_image->polyline, 'meter_reading_checkout_image' => $checkout_image->meter_reading_checkout_image, 'personal_uses_km' => $checkout_image->personal_uses_km), 200);
            } else {
                //$this->response(array('status' => 'error', 'msg' => 'Error occured while Update data'), 404);
                $this->response(array('code'=>404,'message' => 'Authentication Failed'), 200);
            }
        } else {
          $this->response(array('code'=>404,'message' => 'Please send Tracking ID'), 200);
            //$this->response(array('status' => 'error', 'msg' => 'Please send Tracking ID'), 404);
        }
    }

  public function geo_updatepath($parameters,$method,$user_id) {

      $tracking_id = $parameters['tracking_id'];
      $latlon = $parameters['latlon'];
      $pause = $parameters['pause'];
      $resume = $parameters['resume'];

        if ($tracking_id != "") {
            $condition = array('tracking_id' => $tracking_id);
            $existed_data = $this->Generic_model->getSingleRecord("geo_tracking", $condition);
           
            if ($existed_data->route_path_lat_lon != "" && $latlon != "0.0,0.0") {
                if ($pause != '' || $pause == NULL) {
                    $update_path['pause'] = $pause;
                }
                if ($resume != '' || $resume != NULL) {
                    $update_path['resume'] = $resume;
                }

                // $update_path['route_path_lat_lon']=$existed_data->route_path_lat_lon.":".$this->post('latlon');
                if ($latlon != NULL || $latlon != '' || $latlon != 'null') {
                    $update_path['route_path_lat_lon'] = $latlon;
                }else{
                    
                }

                $update_path['updated_datetime'] = date('Y-m-d H:i:s');

                $updated = $this->Generic_model->updateData('geo_tracking', $update_path, $condition);
            } else {
                log_message('debug', 'Some variable was correctly set'); 
            }

            //$update_path['route_path_lat_lon']=($existed_data['route_path_lat_lon']!="")?$existed_data['route_path_lat_lon'].":".$this->post('latlon'):$this->post('latlon');
            if ($updated) {
              $data['tracking_id'] = $tracking_id;
              $this->response(array('code'=>200,'message'=>'path  updated successfully!','result'=>$data,'requestname'=>$method));

                //$this->response(array('status' => 'success', 'msg' => 'path  updated successfully!', 'tracking_id' => $tracking_id), 200);
            }
        } else {
           $this->response(array('code'=>404,'message' => 'Please send Tracking ID'), 200);
            //$this->response(array('status' => 'error', 'msg' => 'Error occured while updateing data'), 404);
        }
    }

    public function geo_tracking_list($parameters,$method,$user_id){

      $final_users_id = $parameters['team_id'];
      $date_send = $parameters['days'];
       //$checking_latest_date = $this->db->query("select * from geo_tracking where user_id in (".$final_users_id.") order by visit_date DESC limit 1")->row();
       $latest_date  = date("Y-m-d 00:00:00");
      if($date_send == "" || $date_send == NULL){
        $old_days = date('Y-m-d 00:00:00', strtotime($latest_date . "-60 day") );        
      }else{
        $old_days = date('Y-m-d 00:00:00', strtotime($latest_date . "-".$date_send." day") );    
      }

      //start_date_time between '".$to_date."' and '".$from_date."'

      //$data['geo_tracking_list'] = $this->db->query("select * from geo_tracking where user_id in (".$final_users_id.") and  visit_date between '".$old_days."' and '".$latest_date."' ")->result();
      $geo_tracking_list_val = $this->db->query("select * from geo_tracking where user_id in (".$final_users_id.") and  visit_date between '".$old_days."' and '".$latest_date."' ")->result();
      $i=0;
      foreach($geo_tracking_list_val as $geo_val){
        $data['geo_tracking_list'][$i]['tracking_id'] = $geo_val->tracking_id;
        $data['geo_tracking_list'][$i]['visit_type'] = $geo_val->visit_type;
        $data['geo_tracking_list'][$i]['user_id'] = $geo_val->user_id;
        $data['geo_tracking_list'][$i]['check_in_lat_lon'] = $geo_val->check_in_lat_lon;
        $data['geo_tracking_list'][$i]['check_out_lat_lon'] = $geo_val->check_out_lat_lon;
        $data['geo_tracking_list'][$i]['route_path_lat_lon'] = $geo_val->route_path_lat_lon;
        $data['geo_tracking_list'][$i]['distance'] = $geo_val->distance;
        $data['geo_tracking_list'][$i]['visit_date'] = $geo_val->visit_date;
        $data['geo_tracking_list'][$i]['check_in_time'] = $geo_val->check_in_time;
        $data['geo_tracking_list'][$i]['check_out_time'] = $geo_val->check_out_time;
        $data['geo_tracking_list'][$i]['created_datetime'] = $geo_val->created_datetime;
        $data['geo_tracking_list'][$i]['updated_datetime'] = $geo_val->updated_datetime;
        $data['geo_tracking_list'][$i]['status'] = $geo_val->status;
        $data['geo_tracking_list'][$i]['installed_app'] = $geo_val->installed_app;
        $data['geo_tracking_list'][$i]['route_snap'] = $geo_val->route_snap;
        $data['geo_tracking_list'][$i]['google_direction'] = $geo_val->google_direction;
        $data['geo_tracking_list'][$i]['gps_status'] = $geo_val->gps_status;
        $data['geo_tracking_list'][$i]['pause'] = $geo_val->pause;
        $data['geo_tracking_list'][$i]['resume'] = $geo_val->resume;
        $data['geo_tracking_list'][$i]['app_version'] = $geo_val->app_version;
        $data['geo_tracking_list'][$i]['polyline'] = $geo_val->polyline;
        $data['geo_tracking_list'][$i]['route_snap_all'] = $geo_val->route_snap_all;
        $data['geo_tracking_list'][$i]['route_snap_failure'] = $geo_val->route_snap_failure;
        $data['geo_tracking_list'][$i]['google_direction_all'] = $geo_val->google_direction_all;
        $data['geo_tracking_list'][$i]['google_direction_failure'] = $geo_val->google_direction_failure;
        $data['geo_tracking_list'][$i]['check_in_place'] = $geo_val->check_in_place;
        $data['geo_tracking_list'][$i]['check_out_place'] = $geo_val->check_out_place;
        $data['geo_tracking_list'][$i]['check_out_by'] = $geo_val->check_out_by;
        $data['geo_tracking_list'][$i]['meter_reading_checkin_image'] = $geo_val->meter_reading_checkin_image;
        $data['geo_tracking_list'][$i]['meter_reading_checkin_text'] = $geo_val->meter_reading_checkin_text;
        $data['geo_tracking_list'][$i]['meter_reading_checkout_image'] = $geo_val->meter_reading_checkout_image;
        $data['geo_tracking_list'][$i]['meter_reading_checkout_text'] = $geo_val->meter_reading_checkout_text;
        $data['geo_tracking_list'][$i]['vehicle_type'] = $geo_val->vehicle_type;
        $data['geo_tracking_list'][$i]['personal_uses_km'] = $geo_val->personal_uses_km;
        $data['geo_tracking_list'][$i]['checkin_comment'] = $geo_val->checkin_comment;

        $call_date = date("Y-m-d",strtotime($geo_val->created_datetime));
       // $sales_call_val = $this->db->query("select * from sales_call where Owner in (".$final_users_id.") and archieve != 1 and Call_Date ='".$call_date."'")->result();



        //$final_users_id = $parameters['team_id'];
              $sales_call_list_val = $this->db->query("select *,a.Phone from sales_call a inner join users b on (a.Owner = b.user_id) where  a.Owner in (".$final_users_id.") and a.archieve != 1 and Call_Date ='".$call_date."' order by a.sales_call_id desc")->result();
              if(count($sales_call_list_val)>0){

              $ia =0;
              foreach($sales_call_list_val as $sc_list){
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['sales_call_id'] = $sc_list->sales_call_id;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Subject'] = $sc_list->Subject;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['releted_to'] = $sc_list->releted_to;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['releted_name'] = call_releted_to_id($sc_list->releted_to,$sc_list->id);
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['related_to_id'] = $sc_list->id;
                if($sc_list->contacts_id != ""||$sc_list->contacts_id != NULL || $sc_list->contacts_id != 0){
                  $contacts_list = $this->db->query("select * from contacts where contact_id =".$sc_list->contacts_id)->row();
                  if(count($contacts_list)>0){
                    $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_id'] = $sc_list->contacts_id;
                    $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_name'] = $contacts_list->FirstName." ".$contacts_list->LastName;
                  }else{
                     $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_id'] = 0;
                    $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_name'] = "";
                  }
                 }else{
                    $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_id'] = 0;
                    $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['contact_name'] = "";
                 }
                // $data['sales_call_list'][$i][]
                // $data['sales_cal_list'][$i]['']
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Status'] = $sc_list->Status;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Call_Type'] = $sc_list->Call_Type;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Description'] = $sc_list->Description;
                $call_date = date("d-m-Y",strtotime($sc_list->Call_Date));
                if($call_date == "00-00-0000" || $call_date == "01-01-1970" || $call_date == NULL){
                  $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Call_Date'] = "";
                }else{
                  $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Call_Date'] = date("Y-m-d",strtotime($sc_list->Call_Date));
                }
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Assigned_To'] = user_details($sc_list->Assigned_To);
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Assigned_To_id'] = $sc_list->Assigned_To;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Email'] = $sc_list->Email;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Phone'] = $sc_list->Phone;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Comments'] = $sc_list->Comments;
                $NextVisitDate = date("d-m-Y",strtotime($sc_list->NextVisitDate));
                if($NextVisitDate == "00-00-0000" || $NextVisitDate == "01-01-1970" || $NextVisitDate == NULL){
                  $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['NextVisitDate'] = "";
                }else{
                  $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['NextVisitDate'] = date("Y-m-d",strtotime($sc_list->NextVisitDate));
                }
                //$data['sales_call_list'][$i]['NextVisitDate'] = $sc_list->NextVisitDate;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Priority'] = $sc_list->Priority;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['MinutesOfMeeting'] =  $sc_list->MinutesOfMeeting;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['CommentsByManager '] = $sc_list->CommentsByManager;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Owner'] = $sc_list->Owner;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['Owner_name'] = $sc_list->name;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['sales_call_id'] = $sc_list->sales_call_id;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['tracking_id'] = $sc_list->tracking_id;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['lat_lon_val'] = $sc_list->lat_lon_val;
                $data['geo_tracking_list'][$i]['sales_call_list'][$ia]['geo_status'] = $sc_list->geo_status;
                $ia++;
              }
       
         
        }else{
          $data['geo_tracking_list'][$i]['sales_call_list'] = array();
        }


        $i++;
      }

      if (count($data)>0) {
              $this->response(array('code'=>200,'message'=>'path  updated successfully!','result'=>$data,'requestname'=>$method));
        } else {
           $this->response(array('code'=>200,'message'=>'Geo Tracking Table Empty','result'=>$data,'requestname'=>$method));
            
        }

    }

    public function sales_call_lat_lon($parameters,$method,$user_id){

      $sales_call_id = $parameters['sales_call_id'];
      $param['tracking_id'] = $parameters['tracking_id'];
      $param['lat_lon_val'] = $parameters['lat_lon_val'];
      $param['geo_status'] = "1";
      $param['modified_by'] = $user_id;
      $param['modified_date_time'] = date("Y-m-d");
       $ok=$this->Generic_model->updateData('sales_call',$param,array('sales_call_id'=>$sales_call_id));

       $check_update_list = $this->db->query("select * from update_table where module_id ='".$sales_call_id."' and module_name ='SalesCalls'")->row();
        if(count($check_update_list)>0){
          $latest_val['user_id'] = $user_id;
          $latest_val['created_date_time'] = date("Y-m-d H:i:s");
          $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $sales_call_id,'module_name'=>'SalesCalls'));
        }else{
          $latest_val['module_id'] = $sales_call_id;
          $latest_val['module_name'] = "SalesCalls";
          $latest_val['user_id'] = $user_id;
          $latest_val['created_date_time'] = date("Y-m-d H:i:s");
          $this->Generic_model->insertData("update_table",$latest_val);
        }
       if($ok ==1){
          $this->response(array('code'=>'200','message' => 'Successfull updated','result'=>"",'requestname'=>$method));
        }else{
          $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
        }
    }


    public function notification_list($parameters,$method,$user_id) {
        $user_id_val = $user_id;
        $profile_id = $parameters['profile_id'];
        if($profile_id == SUPERADMIN){
            $notification_list = $this->db->query("select * from notiffication  a inner join users b on (a.user_id = b.user_id) where a.archieve != 1 order by a.notiffication_id DESC")->result();
          }else{            
            $role_report_id = $this->db->query("select * from users where user_id ='".$user_id_val."' AND status = 'Active'")->row();
           $role_manager_id = $role_report_id->manager; 
          $notification_list = $this->db->query("select * from notiffication  a inner join users b on (a.user_id = b.user_id) where a.user_id in (".$user_id.",".$role_manager_id.") and a.archieve != 1 order by a.notiffication_id DESC")->result();          
          }
          $i=0;
          foreach($notification_list as $not_val){


            $data["notification_list"][$i]['notiffication_id'] = $not_val->notiffication_id;
            $data["notification_list"][$i]['notiffication_type'] = $not_val->notiffication_type;
            $data["notification_list"][$i]['notiffication_type_id'] = $not_val->notiffication_type_id;
             if($not_val->notiffication_type == "Quotation"){
              $quotation_list = $this->db->query("select * from Quotation where Quotation_id ='".$not_val->notiffication_type_id."'")->row();
               $data["notification_list"][$i]['opportunity_id'] = $quotation_list->Opportunity;
            }
            $data["notification_list"][$i]['created_by'] = $not_val->name;
            $data["notification_list"][$i]['subject'] = $not_val->subject;
            $i++;
          }
        if (count($data)>0) {
              $this->response(array('code'=>200,'message'=>'Notiffication List','result'=>$data,'requestname'=>$method));
        }else{
           $this->response(array('code'=>200,'message'=>'notiffication Table Empty','result'=>$data,'requestname'=>$method));
            
        }
    }

	/**
	* This function update_table_list retrieve all the information regarding the following
	* leads, contacts, customers, quotations, salesorder, contracts, salescalls, payment_collection, complaints
	*/
    public function update_table_list($parameters,$method,$user_id){
		
		$user_id = $user_id;
		$team_id = $parameters['team_id'];
		$type = $parameters['type'];
		
		if($user_id != "" || $user_id != null){
			
			$user_list = $this->db->query("select * from users where user_id ='".$user_id."' AND status = 'Active'")->row();
			
			if($type == "Android"){
			  $last_data_updated  = date("Y-m-d H:i:s",strtotime($user_list->android_last_data_updated));
			}else if($type == "iOS"){
			  $last_data_updated  = date("Y-m-d H:i:s",strtotime($user_list->iOS_last_data_updated));
			}else{
			  $last_data_updated = date("Y-m-d H:i:s");
			}
			
			$current_date = date("Y-m-d H:i:s");
			
			$list_val = $this->db->query("select * from update_table where user_id in (".$team_id.") and created_date_time between '".$last_data_updated."' and '".$current_date."' group by module_name , module_id")->result();
			
			$Lead_id = array();
			$Contact_id = array();
			$Customer_id = array();
			$Opportunitie_id = array();
			$Quotation_id = array();
			$SalesOrder_id = array();
			$Contract_id = array();
			$SalesCalls_id = array();
			$payment_collection = array();
			$Complaints_id = array();
			
			foreach($list_val as $update_val){			
				if($update_val->module_name == "Lead"){          
					$Lead_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Contact"){
					$Contact_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Customer"){
					$Customer_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Opportunitie"){
					$Opportunitie_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Quotation"){
					$Quotation_id[] = $update_val->module_id;
				}else if($update_val->module_name == "SalesOrder"){
					$SalesOrder_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Contract"){
					$Contract_id[] = $update_val->module_id;
				}else if($update_val->module_name == "SalesCalls"){
					$SalesCalls_id[] = $update_val->module_id;
				}else if($update_val->module_name == "Payment_Collection"){
					$payment_collection[] = $update_val->module_id;
				}else if($update_val->module_name == "Complaints"){
					$Complaints_id[] = $update_val->module_id;
				}else{
					$test_val = $update_val->module_id;
				}
			}

			// Store all values in variables from array to CSV
			$Lead_ids =  implode(",", $Lead_id);
			$Contact_ids =  implode(",", $Contact_id);
			$Customer_ids =  implode(",", $Customer_id);
			$Opportunitie_ids =  implode(",", $Opportunitie_id);
			$Quotation_ids =  implode(",", $Quotation_id);
			$SalesOrder_ids =  implode(",", $SalesOrder_id);
			$Contract_ids =  implode(",", $Contract_id);
			$SalesCalls_ids =  implode(",", $SalesCalls_id);
			$payment_collection_ids = implode(",", $payment_collection);
			$Complaints_ids = implode(",",$Complaints_id);		

			// Get Leads Info
			if($Lead_ids != "" || $Lead_ids != NULL){
				$data = $this->leadInfo($Lead_ids);
			}	  

			// Get Contacts Info
			if($Contact_ids != "" || $Contact_ids != NULL){
				
				$contacts_list = $this->db->query("select *, a.created_date_time as createdDateTime, c.CustomerName from contacts a inner join users b on (a.ContactOwner = b.user_id) inner join customers c on (a.Company = c.customer_id) where a.contact_id in (".$Contact_ids.") and a.archieve != 1 order by a.contact_id DESC")->result();
				
				$ic=0;
				foreach($contacts_list as $contact_val){
					if($contact_val->ReportsTo == "" || $contact_val->ReportsTo == NULL || $contact_val->ReportsTo == 0){
						$data['contact_list'][$ic]['ReportsTo_name'] = "";
						$data['contact_list'][$ic]['ReportsTo'] = "";
					}else{
						$report_detatis = $this->db->query("select * from contacts where contact_id =".$contact_val->ReportsTo)->row();
						if(count($report_detatis)>0){
							$data['contact_list'][$ic]['ReportsTo_name'] = $report_detatis->FirstName ." ". $report_detatis->LastName;
							$data['contact_list'][$ic]['ReportsTo'] = $contact_val->ReportsTo;
						}else{
							$data['contact_list'][$ic]['ReportsTo_name'] = "";
							$data['contact_list'][$ic]['ReportsTo'] = "";
						} 
					}
				
					$data['contact_list'][$ic]['contact_id'] = $contact_val->contact_id;
					$data['contact_list'][$ic]['Salutation'] = $contact_val->Salutation;
					$data['contact_list'][$ic]['FirstName'] = $contact_val->FirstName;
					$data['contact_list'][$ic]['LastName'] = $contact_val->LastName;
					$data['contact_list'][$ic]['Email'] = $contact_val->Email;
					$data['contact_list'][$ic]['Fax'] = $contact_val->Fax;
					$data['contact_list'][$ic]['Mobile'] = $contact_val->Mobile;
					$data['contact_list'][$ic]['Phone'] = $contact_val->Phone;
					$data['contact_list'][$ic]['Company'] = $contact_val->CustomerName;
					$data['contact_list'][$ic]['customer_id'] = $contact_val->customer_id;
					$data['contact_list'][$ic]['Department'] = $contact_val->Department;
					$data['contact_list'][$ic]['Title_Designation'] = $contact_val->Title_Designation;
					$data['contact_list'][$ic]['OtherPhone'] = $contact_val->OtherPhone;
					$data['contact_list'][$ic]['HomePhone'] = $contact_val->HomePhone;
					$Birthdate = date("d-m-Y",strtotime($contact_val->Birthdate));

					if($Birthdate == "30-11-0001" || $Birthdate == "01-01-1970" || $Birthdate == NULL){
						$data['contact_list'][$ic]['Birthdate'] = "";
					}else{
						$data['contact_list'][$ic]['Birthdate'] = $Birthdate;
					}

					$data['contact_list'][$ic]['Description'] = $contact_val->Description;
					$data['contact_list'][$ic]['LeadSource'] = $contact_val->LeadSource;
					$data['contact_list'][$ic]['ContactOwner'] = $contact_val->ContactOwner;
					$data['contact_list'][$ic]['ContactOwner_name'] = $contact_val->name;
					$data['contact_list'][$ic]['Category'] = $contact_val->Category;
					$data['contact_list'][$ic]['MallingStreet1'] = $contact_val->MallingStreet1;
					$data['contact_list'][$ic]['Mallingstreet2'] = $contact_val->Mallingstreet2;
					$data['contact_list'][$ic]['MallingCountry'] = $contact_val->MallingCountry;
					$data['contact_list'][$ic]['MallingStateProvince'] = $contact_val->MallingStateProvince;
					$data['contact_list'][$ic]['MallingCity'] = $contact_val->MallingCity;
					$data['contact_list'][$ic]['MallingZipPostal'] = $contact_val->MallingZipPostal;
					$data['contact_list'][$ic]['OtherStreet1'] = $contact_val->OtherStreet1;
					$data['contact_list'][$ic]['Otherstreet2'] = $contact_val->Otherstreet2;
					$data['contact_list'][$ic]['OtherCountry'] = $contact_val->OtherCountry;
					$data['contact_list'][$ic]['OtherStateProvince'] = $contact_val->OtherStateProvince;
					$data['contact_list'][$ic]['OtherCity'] = $contact_val->OtherCity;
					$data['contact_list'][$ic]['OtherZipPostal'] = $contact_val->OtherZipPostal;
					$data['contact_list'][$ic]['created_date_time'] = $contact_val->createdDateTime;
					$ic++;
				} 
			}else{
				$data['contact_list'] = array();
			}

			// Get Customers Info
			if($Customer_ids != "" || $Customer_ids != NULL){
				
				$customer_list = $this->db->query("select *, a.created_date_time as createdDateTime from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where b.customer_id in (".$Customer_ids.") and a.archieve != 1 group by b.customer_id order by a.customer_id DESC ")->result();	
				
				
				if(count($customer_list)>0){			
					$ic=0;
					foreach ($customer_list as $customer_val) {  
						$customer_user_list = $this->db->query("select * from customer_users_maping a inner join users b on (a.user_id = b.user_id) where customer_id =".$customer_val->customer_id)->result();               
						$data['customer_list'][$ic]['customer_id']=$customer_val->customer_id;
						$data['customer_list'][$ic]['CustomerName']=$customer_val->CustomerName;
						$data['customer_list'][$ic]['Customer_location']=$customer_val->Customer_location;
						$data['customer_list'][$ic]['CustomerSAPCode']=$customer_val->CustomerSAPCode;
						$data['customer_list'][$ic]['customer_number']=$customer_val->customer_number;						
						$data['customer_list'][$ic]['Description']=$customer_val->Description;
						$data['customer_list'][$ic]['Phone']=$customer_val->Phone;
						$data['customer_list'][$ic]['Website'] = $customer_val->Website;
						$data['customer_list'][$ic]['AccountSource']=$customer_val->AccountSource;
						$data['customer_list'][$ic]['AnnualRevenue']=$customer_val->AnnualRevenue;
						$data['customer_list'][$ic]['GSTINNumber']=$customer_val->GSTINNumber;
						$data['customer_list'][$ic]['Employees']=$customer_val->Employees;
						$data['customer_list'][$ic]['contact_id']=$customer_val->contact_id;
						$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
						
						if($customer_val->PaymentTerms != 0 || $customer_val->PaymentTerms != "" || $customer_val->PaymentTerms != NULL){
							$PaymentTerms_list = $this->db->query("select * from Payment_terms where Payment_term_id =".$customer_val->PaymentTerms)->row();
							$data['customer_list'][$ic]['PaymentTerms']=$PaymentTerms_list->Payment_name;
						}
						
						$data['customer_list'][$ic]['pancard']=$customer_val->pancard;
						$data['customer_list'][$ic]['approve_status']=$customer_val->approve_status;
						$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;
						$data['customer_list'][$ic]['manager_user_id']=$customer_val->manager_user_id;
						
						if($customer_val->manager_user_id != 0 || $customer_val->manager_user_id != '' || $customer_val->manager_user_id != NULL){
							// Get Manager Info
							$managerInfo = $this->Generic_model->getSingleRecord('users',array('user_id'=>$customer_val->manager_user_id));
							$data['customer_list'][$ic]['manager_name'] = $managerInfo->name;
						}else{
							$data['customer_list'][$ic]['manager_name'] = '';
						}						
						
						// Billing Street Info
						$data['customer_list'][$ic]['BillingStreet1']=$customer_val->BillingStreet1;
						$data['customer_list'][$ic]['Billingstreet2']=$customer_val->Billingstreet2;
						$data['customer_list'][$ic]['BillingCountry']=$customer_val->BillingCountry;
						$data['customer_list'][$ic]['StateProvince']=$customer_val->StateProvince;
						$data['customer_list'][$ic]['BillingCity']=$customer_val->BillingCity;
						$data['customer_list'][$ic]['BillingZipPostal']=$customer_val->BillingZipPostal;
						
						// Shipping Street Info
						$data['customer_list'][$ic]['ShippingStreet1']=$customer_val->ShippingStreet1;
						$data['customer_list'][$ic]['Shippingstreet2']=$customer_val->Shippingstreet2;
						$data['customer_list'][$ic]['ShippingCountry']=$customer_val->ShippingCountry;
						$data['customer_list'][$ic]['ShippingStateProvince']=$customer_val->ShippingStateProvince;
						$data['customer_list'][$ic]['ShippingCity']=$customer_val->ShippingCity;				
						$data['customer_list'][$ic]['ShippingZipPostal']=$customer_val->ShippingZipPostal;
						
						// Sales Organization				
						if($customer_val->SalesOrganisation != "" || $customer_val->SalesOrganisation != NULL){
							$SalesOrganisation_list = $this->db->query("select * from sales_organisation where sap_code= '".$customer_val->SalesOrganisation."'")->row();
							if(count($SalesOrganisation_list)>0){
								$data['customer_list'][$ic]['SalesOrganisation_id']=$SalesOrganisation_list->sap_code;
								$data['customer_list'][$ic]['SalesOrganisation']=$SalesOrganisation_list->organistation_name;
							}else{
								$data['customer_list'][$ic]['SalesOrganisation_id']="";
								$data['customer_list'][$ic]['SalesOrganisation']="";
							}
						}else{
							$data['customer_list'][$ic]['SalesOrganisation_id']="";
							$data['customer_list'][$ic]['SalesOrganisation']="";
						}
						
						// Distribution Channel
						if($customer_val->DistributionChannel != "" || $customer_val->DistributionChannel != NULL){
							$DistributionChannel_list = $this->db->query("select * from DistributionChannel where sap_code= '".$customer_val->DistributionChannel."'")->row();
							if(count($DistributionChannel_list)>0){
								$data['customer_list'][$ic]['DistributionChannel_id']=$DistributionChannel_list->sap_code;
								$data['customer_list'][$ic]['DistributionChannel']=$DistributionChannel_list->ditribution_name;
							}else{
								$data['customer_list'][$ic]['DistributionChannel_id']="";
								$data['customer_list'][$ic]['DistributionChannel']="";
							}
						}else{
							$data['customer_list'][$ic]['DistributionChannel_id']="";
							$data['customer_list'][$ic]['DistributionChannel']="";
						}
						
						// Division Info
						/*
						if($customer_val->Division != "" || $customer_val->Division != NULL){
							$Division_list = $this->db->query("select * from division_master where division_master_id = '".$customer_val->Division."'")->row();
							if(count($Division_list)>0){
								$data['customer_list'][$ic]['division_master_id']=$Division_list->division_master_id;
								$data['customer_list'][$ic]['Division']=$Division_list->division_name;
							}else{
								$data['customer_list'][$ic]['division_master_id']="";
								$data['customer_list'][$ic]['Division']="";
							}
						}else{
							$data['customer_list'][$ic]['division_master_id']="";
							$data['customer_list'][$ic]['Division']="";
						}
						*/

						$data['customer_list'][$ic]['Division']=$customer_val->Division;
						$data['customer_list'][$ic]['CustomerType']=$customer_val->CustomerType;
						$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
						$data['customer_list'][$ic]['Email']=$customer_val->Email;
						$data['customer_list'][$ic]['CustomerCategory']=$customer_val->CustomerCategory;
						$data['customer_list'][$ic]['CreditLimit']=$customer_val->CreditLimit;
						$data['customer_list'][$ic]['SecurityInstruments']=$customer_val->SecurityInstruments;
						$data['customer_list'][$ic]['Pdc_Check_number']=$customer_val->Pdc_Check_number;
						$data['customer_list'][$ic]['Bank']=$customer_val->Bank;
						$data['customer_list'][$ic]['Bank_guarntee_amount_Rs']=$customer_val->Bank_guarntee_amount_Rs;
						$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;

						if($customer_val->IncoTerms1 != 0 || $customer_val->IncoTerms1 != "" || $customer_val->IncoTerms1 != NULL){
							$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms1)->row();
							$data['customer_list'][$ic]['IncoTerms1']=$IncoTerms_list->Incoterm_name;
						}
				
						if($customer_val->IncoTerms2 != 0 || $customer_val->IncoTerms2 != "" || $customer_val->IncoTerms2 != NULL){
							$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms2)->row();
							$data['customer_list'][$ic]['IncoTerms2']=$IncoTerms_list->Incoterm_name;
						}

						$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;
						$data['customer_list'][$ic]['Fax']=$customer_val->Fax;
						$data['customer_list'][$ic]['Industry']=$customer_val->Industry;
						
						$customer_price_list = $this->db->query("select * from customer_price_list a inner join product_price_master b on (a.price_list_id = b.Product_price_master_id) where  a.customer_id ='".$customer_val->customer_id."' and a.status ='Active'")->row();
				
						if(count($customer_price_list) > 0){
							$data['customer_list'][$ic]['price_list']=$customer_price_list->Area;
							$data['customer_list'][$ic]['price_list_id']=$customer_price_list->price_list_id;
						}else{
							$data['customer_list'][$ic]['price_list']="";
							$data['customer_list'][$ic]['price_list_id']="";
						}						

						$data['customer_list'][$ic]['ParentAccount']=$customer_val->ParentAccount;
						$data['customer_list'][$ic]['created_by']=$customer_val->created_by;
						
						$data['customer_list'][$ic]['created_date_time']=$customer_val->createdDateTime;
							
						// Get Sales, Bill & Ship to Party details
						$sbs_list = $this->db->query("SELECT * FROM customer_address_sold_bill_ship WHERE customer_id = ".$customer_val->customer_id)->result();
						if(count($sbs_list) > 0){
							$x = 0;
							foreach($sbs_list as $record){
								$data['customer_list'][$ic][$record->type.'_to_party'][] = $record;
							}
						}
				
						if(count($customer_user_list)>0){
							$jc=0;
							foreach($customer_user_list as $customer_user_val){
								$data['customer_list'][$ic]['user_details'][$jc]["customer_user_id"]=$customer_user_val->customer_user_id;
								$data['customer_list'][$ic]['user_details'][$jc]["user_name"]=$customer_user_val->name;
								$jc++;
							}
						}else{
							$data['customer_list'][$ic]['user_details'] = array();
						}				
						$ic++; 
					}
				}else{
					$data['customer_list'] = array();
				}
			}else{
				$data['customer_list'] = array();
			}

			// Get Opportunities Info
			if($Opportunitie_ids != "" || $Opportunitie_ids != NULL){
				
				$opportunities_list_val = $this->db->query("select *, a.remarks as Opp_remarks, a.created_date_time as createdDateTime, c.name as OwnerName from opportunities a inner join customers b on (b.customer_id = a.Company) inner join users c on (c.user_id = a.OpportunityOwner) where a.opportunity_id in (".$Opportunitie_ids.") and a.archieve != 1")->result();
				
				if(count($opportunities_list_val)>0){
					$i=0;
					foreach($opportunities_list_val as $opp_list){
												
						if($opp_list->opportunity_main_contact_id != '' || $opp_list->opportunity_main_contact_id != NULL){
							$mainContactId = $opp_list->opportunity_main_contact_id;
						}else{
							$mainContactId = '';
						}
						$main_contact = $this->db->query("SELECT FirstName, LastName from contacts WHERE contact_id = '".$mainContactId."'")->row();	
						
						$leadInfo = $this->db->query("SELECT leads_id,lead_size_class_of_project FROM leads WHERE lead_number = '".$opp_list->Leadno."'")->row();

						$data['opportunities_list'][$i]['opportunity_id'] = $opp_list->opportunity_id;
						$data['opportunities_list'][$i]['opp_id'] = $opp_list->opp_id;
						$data['opportunities_list'][$i]['OwnerName'] = $opp_list->OwnerName;
						$data['opportunities_list'][$i]['leads_id'] = $opp_list->leads_id;
						$data['opportunities_list'][$i]['Leadno'] = $opp_list->Leadno;
						$data['opportunities_list'][$i]['Company'] = $opp_list->Company;
						$data['opportunities_list'][$i]['Company_Text'] = $opp_list->Company_text;
						$data['opportunities_list'][$i]['sampling'] = $opp_list->sampling;
						$data['opportunities_list'][$i]['mockup'] = $opp_list->mockup;
						$data['opportunities_list'][$i]['Rating'] = $opp_list->Rating;
						$data['opportunities_list'][$i]['project_name'] = $opp_list->project_name;
						$data['opportunities_list'][$i]['project_type'] = $opp_list->project_type;
						$data['opportunities_list'][$i]['size_class_project'] = $opp_list->size_class_project;
						$data['opportunities_list'][$i]['size_calss_unit'] = $opp_list->size_calss_unit;
						$data['opportunities_list'][$i]['lead_class_of_project'] = $opp_list->lead_class_of_project;
						$data['opportunities_list'][$i]['lead_size_class_of_project'] = $leadInfo->lead_size_class_of_project;
						$data['opportunities_list'][$i]['size_calss_unit_no_of_blocks'] = $opp_list->size_calss_unit_no_of_blocks;
						$data['opportunities_list'][$i]['size_calss_unit_no_of_floor_per_block'] = $opp_list->size_calss_unit_no_of_floor_per_block;						
						
						// Billing Details
						$data['opportunities_list'][$i]['status_project'] = $opp_list->status_project;
						$data['opportunities_list'][$i]['BillingStreet1'] = $opp_list->BillingStreet1;
						$data['opportunities_list'][$i]['BillingStreet2'] = $opp_list->Billingstreet2;
						$data['opportunities_list'][$i]['BillingCountry'] = $opp_list->BillingCountry;
						$data['opportunities_list'][$i]['BillingState'] = $opp_list->BillingState;
						$data['opportunities_list'][$i]['BillingCity'] = $opp_list->BillingCity;
						$data['opportunities_list'][$i]['BillingZipPostal'] = $opp_list->BillingZipPostal;
						$data['opportunities_list'][$i]['BillingArea'] = $opp_list->BillingArea;
						$data['opportunities_list'][$i]['BillingPlotno'] = $opp_list->BillingPlotno;
						$data['opportunities_list'][$i]['BillingWebsite'] = $opp_list->BillingWebsite;
						$data['opportunities_list'][$i]['BillingEmail'] = $opp_list->BillingEmail;
						$data['opportunities_list'][$i]['BillingPhone'] = $opp_list->BillingPhone;
						
						// Shipping Details
						$data['opportunities_list'][$i]['ShippingStreet1'] = $opp_list->ShippingStreet1;
						$data['opportunities_list'][$i]['Shippingstreet2'] = $opp_list->Shippingstreet2;
						$data['opportunities_list'][$i]['ShippingLandmark'] = $opp_list->ShippingLandmark;
						$data['opportunities_list'][$i]['Shippingplotno'] = $opp_list->Shippingplotno;
						$data['opportunities_list'][$i]['ShippingCountry'] = $opp_list->ShippingCountry;
						$data['opportunities_list'][$i]['ShippingStateProvince'] = $opp_list->ShippingStateProvince;
						$data['opportunities_list'][$i]['ShippingCity'] = $opp_list->ShippingCity;
						$data['opportunities_list'][$i]['ShippingZipPostal'] = $opp_list->ShippingZipPostal;
						
						$data['opportunities_list'][$i]['opportunity_main_contact_id'] = $opp_list->opportunity_main_contact_id;
						$data['opportunities_list'][$i]['opportunity_main_contact_name'] = $main_contact->FirstName." ".$main_contact->LastName;
						$data['opportunities_list'][$i]['opportunity_main_contact_designation'] = $opp_list->opportunity_main_contact_designation;
						$data['opportunities_list'][$i]['opportunity_main_contact_email'] = $opp_list->opportunity_main_contact_email;
						$data['opportunities_list'][$i]['opportunity_main_contact_mobile'] = $opp_list->opportunity_main_contact_mobile;
						$data['opportunities_list'][$i]['opportunity_main_contact_category'] = $opp_list->opportunity_main_contact_category;
						$data['opportunities_list'][$i]['opportunity_main_contact_phone'] = $opp_list->opportunity_main_contact_phone;
						$data['opportunities_list'][$i]['opportunity_main_contact_company'] = $opp_list->opportunity_main_contact_company;
						
						$data['opportunities_list'][$i]['no_of_flats'] = $opp_list->no_of_flats;
						$data['opportunities_list'][$i]['cubic_meters'] = $opp_list->cubic_meters;
						$data['opportunities_list'][$i]['sft'] = $opp_list->sft;
						$data['opportunities_list'][$i]['remarks'] = $opp_list->Opp_remarks;
						$data['opportunities_list'][$i]['Finalizationdate'] = $opp_list->Finalizationdate;
						$data['opportunities_list'][$i]['requirement_details_collected'] = $opp_list->requirement_details_collected;
						$data['opportunities_list'][$i]['business_status'] = $opp_list->business_status;
						$data['opportunities_list'][$i]['business_status_delayed_value'] = $opp_list->business_status_delayed_value;
						$data['opportunities_list'][$i]['business_status_pending_value'] = $opp_list->business_status_pending_value;
						$data['opportunities_list'][$i]['business_status_lost_value'] = $opp_list->business_status_lost_value;
						$data['opportunities_list'][$i]['business_status_lost_other_value'] = $opp_list->business_status_lost_other_value; 

						$data['opportunities_list'][$i]['created_date_time'] = $opp_list->createdDateTime;

						$Associate_contact_id = $opp_list->opportunity_main_contact_id;
						if($Associate_contact_id != "" || $Associate_contact_id != NULL){
							$contact_list_a = $this->db->query("select OAC.contact_id, OAC.designation, C.FirstName, C.LastName from opportunity_associate_contacts OAC inner join contacts C on (OAC.contact_id = C.contact_id) where opportunity = ".$opp_list->opportunity_id)->result();
							$c=0;
							foreach($contact_list_a as $assoc_val){
								$data['opportunities_list'][$i]['associate_contact'][$c]["contact_id"] = $assoc_val->contact_id;
								$data['opportunities_list'][$i]['associate_contact'][$c]["contact_name"] = $assoc_val->FirstName." ".$assoc_val->LastName;
								$data['opportunities_list'][$i]['associate_contact'][$c]["designation"] = $assoc_val->designation;
								$c++;
							}
						}else{
							$data['opportunities_list'][$i]['associate_contact'] = array();
						}

						$checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$opp_list->customer_id."'")->row();
					   
						$product_opportunitie_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity ='".$opp_list->opportunity_id."' group by b.product_code")->result();
					  
						if(count($product_opportunitie_list) >0){
							$j=0;
							foreach($product_opportunitie_list as $popp_list){
								$data['opportunities_list'][$i]['final_product'][$j]['Product_opportunities_id'] = $popp_list->Product_opportunities_id;
								$data['opportunities_list'][$i]['final_product'][$j]['product_id'] = $popp_list->Product;
								$data['opportunities_list'][$i]['final_product'][$j]['product_name'] = $popp_list->product_name;
								$data['opportunities_list'][$i]['final_product'][$j]['probability'] = $popp_list->Probability;
								$data['opportunities_list'][$i]['final_product'][$j]['quantity'] = $popp_list->Quantity;
								$data['opportunities_list'][$i]['final_product'][$j]['rate_per_sft'] = $popp_list->final_product_price;
								$data['opportunities_list'][$i]['final_product'][$j]['value'] = $popp_list->final_product_value;
								$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_from'] = date("d-m-Y",strtotime($popp_list->schedule_date_from));
								$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_upto'] = date("d-m-Y",strtotime($popp_list->schedule_date_upto));
								$j++;
							}
						}else{
							$data['opportunities_list'][$i]['final_product'] = array();
						}

						$brand_producta_list = $this->db->query("select * from Products_Brands_targeted_opp where Opportunity =".$opp_list->opportunity_id)->result();
					  
						if(count($brand_producta_list)>0){
							$k=0;
							foreach($brand_producta_list as $brand_product_val){
								$data['opportunities_list'][$i]['brands_product'][$k]['brands_opp_id'] = $brand_product_val->brands_opp_id;
								$data['opportunities_list'][$i]['brands_product'][$k]['product'] = $brand_product_val->Brands_Product;
								$data['opportunities_list'][$i]['brands_product'][$k]['units'] = $brand_product_val->Brands_Units;
								$data['opportunities_list'][$i]['brands_product'][$k]['quantity'] = $brand_product_val->Brands_Quantity;
								$data['opportunities_list'][$i]['brands_product'][$k]['price'] = $brand_product_val->Brands_Price;
								$k++;
							}
						}else{
							$data['opportunities_list'][$i]['brands_product'] = array();
						}

						$Competition_targeted_list = $this->db->query("select * from Competition_targeted_opp where Opportunity = ".$opp_list->opportunity_id)->result();
						if(count($Competition_targeted_list) >0){
							$l=0;
							foreach($Competition_targeted_list as $competition_val){
								$data['opportunities_list'][$i]['competition_product'][$l]['competitions_opp_id'] = $competition_val->competitions_opp_id;                 
								$data['opportunities_list'][$i]['competition_product'][$l]['product'] = $competition_val->Competition_Product;
								$data['opportunities_list'][$i]['competition_product'][$l]['units'] = $competition_val->Competition_Units;                 
								$data['opportunities_list'][$i]['competition_product'][$l]['price'] = $competition_val->Competition_Price;
								$l++;
							}
						}else{
							$data['opportunities_list'][$i]['competition_product'] = array();
						}
						
						$remarks_list = $this->db->query("select * from opportunities_remarks where opportunity_id = ".$opp_list->opportunity_id." ORDER BY remark_date ASC")->result();
						if(count($remarks_list) > 0){
							$l = 0;
							foreach($remarks_list as $remark){
								$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_id'] = $remark->remark_id;     						
								$data['opportunities_list'][$i]['opp_remarks'][$l]['remark'] = $remark->remark;
								$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_date'] = $remark->remark_date;             
								$l++;
							}
						}else{
							$data['opportunities_list'][$i]['opp_remarks'] = [];
						}
						
						$i++;
					}
				}else{
					$data['opportunities_list'] = array();
				}
			}else{
				$data['opportunities_list'] = array();
			}

			
			// Get Contracts Info
			if($Contract_ids != "" || $Contract_ids != NULL){
				
				$contract_list = $this->db->query("select *,a.Description, a.created_date_time as createdDateTime from contract a inner join customers b on (a.Customer = b.customer_id) where a.contract_id  in (".$Contract_ids.") and a.archieve != 1 order by a.contract_id DESC")->result();

				if(count($contract_list) >0){
					$i_c=0;
					foreach($contract_list as $contract_val){
						if($contract_val->CompanySignedBy == "" || $contract_val->CompanySignedBy == NULL){
							$CompanySignedBy_list = array();
						}else{
							$CompanySignedBy_list = $this->db->query("select * from  users where user_id =".$contract_val->CompanySignedBy)->row();
						}
              
						$ContractOwner_list = $this->db->query("select * from users where user_id =".$contract_val->ContractOwner." AND status = 'Active'")->row();
						
						if(count($CompanySignedBy_list)>0){
							$CompanySignedBy = $CompanySignedBy_list->name;
							$CompanySignedBy_id = $CompanySignedBy_list->user_id;
						}else{
							$CompanySignedBy = "";
							$CompanySignedBy_id  = "";
						}

						if(count($ContractOwner_list)>0){
							$ContractOwner = $ContractOwner_list->name;
						}else{
							$ContractOwner ="";
						}
              
						$data['contract_list'][$i_c]['contract_id'] =$contract_val->contract_id;
						$data['contract_list'][$i_c]['Customer'] =$contract_val->CustomerName;
						$data['contract_list'][$i_c]['customer_id'] =$contract_val->customer_id;
						$data['contract_list'][$i_c]['ActivatedBy'] =$contract_val->ActivatedBy;
						$data['contract_list'][$i_c]['ActivatedDate'] =date("d-m-Y",strtotime($contract_val->ActivatedDate));
						$data['contract_list'][$i_c]['BillingAddress'] =$contract_val->BillingAddress;
						$data['contract_list'][$i_c]['ShippingAddress'] =$contract_val->ShippingAddress;

						if($contract_val->CustomerSignedBy == ""||$contract_val->CustomerSignedBy == null ){
							$data['contract_list'][$i_c]['CustomerSignedBy'] ="";
							$data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
						}else{
							$contact_list =  $this->db->query("select * from contacts where contact_id =".$contract_val->CustomerSignedBy)->row();
                
							if(count($contact_list) >0){
								$data['contract_list'][$i_c]['CustomerSignedBy'] =$contact_list->FirstName." ".$contact_list->LastName;
								$data['contract_list'][$i_c]['CustomerSignedBy_id'] =$contact_list->contact_id;
							}else{
								$data['contract_list'][$i_c]['CustomerSignedBy'] ="";
								$data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
							}
						}
						
						$data['contract_list'][$i_c]['CompanySignedDate'] =date("d-m-Y",strtotime($contract_val->CompanySignedDate));
						$data['contract_list'][$i_c]['ContractName'] =$contract_val->ContractName;
						$data['contract_list'][$i_c]['ContractNumber'] =$contract_val->ContractNumber;
						$data['contract_list'][$i_c]['ContractStartDate'] =date("d-m-Y",strtotime($contract_val->ContractStartDate));
						$data['contract_list'][$i_c]['ContractEndDate'] =date("d-m-Y",strtotime($contract_val->ContractEndDate));
						$data['contract_list'][$i_c]['ContractOwner'] =$ContractOwner;
						$data['contract_list'][$i_c]['ContractTerm'] =$contract_val->ContractTerm;
						$data['contract_list'][$i_c]['CustomerSignedBy'] =$contract_val->FirstName." ".$contract_val->LastName;
						$data['contract_list'][$i_c]['CompanySignedBy_id'] = $CompanySignedBy_id;
						$data['contract_list'][$i_c]['CompanySignedBy'] = $CompanySignedBy;
						$data['contract_list'][$i_c]['CustomerSignedDate'] =date("d-m-Y",strtotime($contract_val->CustomerSignedDate));
						$data['contract_list'][$i_c]['Description'] =$contract_val->Description;
						$data['contract_list'][$i_c]['total_amount'] =$contract_val->total_amount;
						$data['contract_list'][$i_c]['OwnerExpirationNotice'] =$contract_val->OwnerExpirationNotice;
						$data['contract_list'][$i_c]['SpecialTerms'] =$contract_val->SpecialTerms;
						$data['contract_list'][$i_c]['Status'] =$contract_val->Status;
						$data['contract_list'][$i_c]['created_date_time'] =$contract_val->createdDateTime;

						$contract_product_list = $this->db->query("select * from contract_products a inner join product_master b on (a.Product = b.product_id) where a.Contract =".$contract_val->contract_id)->result();
						
						
						if(count($contract_product_list)>0){
							$j_c=0;
							foreach($contract_product_list as $cp_list){
								$data['contract_list'][$i_c]['contract_product'][$j_c]['product_contract_id'] = $cp_list->product_contract_id;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['Product'] = $cp_list->product_name;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['Product_id'] = $cp_list->Product;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['ListPrice'] = $cp_list->ListPrice;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['Quantity'] = $cp_list->Quantity;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['Discount'] = $cp_list->Discount;
								$data['contract_list'][$i_c]['contract_product'][$j_c]['Subtotal'] = $cp_list->Subtotal;
								$j++;
							}
						}else{
							$data['contract_list'][$i_c]['contract_product'] = array();
						}
						$i_c++;
					}
				}else{
					$data['contract_list'] =array();
				}
			}else{
				$data['contract_list'] =array();
			}

			// Get Sales Order
			if($SalesOrder_ids != "" || $SalesOrder_ids != NULL){
				//$sales_order_list = $this->db->query("select *, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks from sales_order a inner join customers b on (a.Customer = b.customer_id)   where a.sales_order_id in (".$SalesOrder_ids.") and a.archieve !=1")->result();
				$sales_order_list = $this->db->query("select a.*,b.*,c.CustomerName as delivered_by_customer_name, d.division_name, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks, u.name as OwnerName from sales_order a inner join customers b on (a.Customer = b.customer_id) left join customers c on (a.DeliveredBy_customer_id = c.customer_id) left join division_master d on (a.Division = d.division_master_id) left join users u on (a.created_by = u.user_id) where a.sales_order_id in (".$SalesOrder_ids.") and a.archieve !=1 order by a.sales_order_id DESC")->result();
				
				if(count($sales_order_list) >0){
					$i_s=0;
					foreach($sales_order_list as $so_list){
						$Soldtopartycode = $so_list->Soldtopartycode;
						if($Soldtopartycode == "" || $Soldtopartycode == NULL){
							$Soldtopartycode_val = "";
						}else{
							$Soldtopartycode_list = $this->db->query("select * from contacts where contact_id =".$Soldtopartycode)->row();
							$Soldtopartycode_val= $Soldtopartycode_list->FirstName." ".$Soldtopartycode_list->LastName;
						}
              
						$Shiptopartycode = $so_list->Shiptopartycode;
						
						if($Shiptopartycode == "" || $Shiptopartycode == NULL){
							$Shiptopartycode_val = "";
						}else{
							$Shiptopartycode_list = $this->db->query("select * from contacts where contact_id =".$Shiptopartycode)->row();
							$Shiptopartycode_val= $Shiptopartycode_list->FirstName." ".$Shiptopartycode_list->LastName;
						}
              
						$BilltopartyCode = $so_list->BilltopartyCode;
              
						if($BilltopartyCode == "" || $BilltopartyCode == NULL){
							$BilltopartyCode_val = "";
						}else{
							$BilltopartyCode_list = $this->db->query("select * from contacts where contact_id =".$BilltopartyCode)->row();
							$BilltopartyCode_val= $BilltopartyCode_list->FirstName." ".$BilltopartyCode_list->LastName;
						}

						$data["sales_order_list"][$i_s]['sales_order_id'] = $so_list->sales_order_id;
						$data["sales_order_list"][$i_s]['sales_order_number'] = $so_list->sales_order_number;
						$data["sales_order_list"][$i_s]['OwnerName'] = $so_list->OwnerName;
						$data["sales_order_list"][$i_s]['Customer'] = $so_list->CustomerName;
						$data["sales_order_list"][$i_s]['customer_id'] = $so_list->Customer;
						$data["sales_order_list"][$i_s]['OrderType'] = $so_list->OrderType;
						$data["sales_order_list"][$i_s]['sales_order_dealer_contact_id'] = $so_list->sales_order_dealer_contact_id;
						$data["sales_order_list"][$i_s]['orc_details'] = $so_list->orc_details;
						$data["sales_order_list"][$i_s]['OrderType_form'] = $so_list->OrderType_form;
						$data["sales_order_list"][$i_s]['order_status'] = $so_list->order_status;
						$data["sales_order_list"][$i_s]['order_status_comments'] = $so_list->order_status_comments;
						$data["sales_order_list"][$i_s]['SalesOrganisation'] = $so_list->SalesOrganisation;
						$data["sales_order_list"][$i_s]['DistributionChannel'] = $so_list->DistributionChannel;
						$data["sales_order_list"][$i_s]['date_of_delivery'] = $so_list->date_of_delivery;
						$data["sales_order_list"][$i_s]['delivered_by'] = $so_list->DeliveredBy;
						$data["sales_order_list"][$i_s]['delivered_by_customer_id'] = $so_list->DeliveredBy_customer_id;
						$data["sales_order_list"][$i_s]['delivered_by_customer_name'] = $so_list->delivered_by_customer_name;
						$data["sales_order_list"][$i_s]['Division'] = $so_list->Division;
						$data["sales_order_list"][$i_s]['division_name'] = $so_list->division_name;						
						$data["sales_order_list"][$i_s]['Remarks'] = $so_list->SalesOrderRemarks;
						$data["sales_order_list"][$i_s]['Soldtopartycode'] = $Soldtopartycode_val;
						$data["sales_order_list"][$i_s]['Soldtopartycode_id'] = $so_list->Soldtopartycode;
						$data["sales_order_list"][$i_s]['Shiptopartycode'] = $Shiptopartycode_val;
						$data["sales_order_list"][$i_s]['Shiptopartycode_id'] = $so_list->Shiptopartycode;
						$data["sales_order_list"][$i_s]['BilltopartyCode'] = $BilltopartyCode_val;
						$data["sales_order_list"][$i_s]['BilltopartyCode_id'] = $so_list->BilltopartyCode;
						$data["sales_order_list"][$i_s]['expected_order_dispatch_date'] = $so_list->expected_order_dispatch_date;
						$data["sales_order_list"][$i_s]['Ponumber'] = $so_list->Ponumber;
						$data["sales_order_list"][$i_s]['CashDiscount'] = $so_list->CashDiscount;
						$data["sales_order_list"][$i_s]['SchemeDiscount'] = $so_list->SchemeDiscount;
						$data["sales_order_list"][$i_s]['QuntityDiscount'] = $so_list->QuntityDiscount;
						$data["sales_order_list"][$i_s]['withoutdiscountamount'] = $so_list->withoutdiscountamount;						
						$data["sales_order_list"][$i_s]['Freight'] = $so_list->Freight;
						$data['sales_order_list'][$i_s]['freight_amount'] = $so_list->freight_amount;
						$data["sales_order_list"][$i_s]['discountAmount'] = $so_list->discountAmount;
						$data["sales_order_list"][$i_s]['Total'] = $so_list->Total;
						$data["sales_order_list"][$i_s]['created_date_time'] = $so_list->CreatedDateTime;
						$data["sales_order_list"][$i_s]['purchase_image'] = $so_list->purchase_image;
						$data["sales_order_list"][$i_s]['complaints_image'] = $so_list->complaints_image;
						$data["sales_order_list"][$i_s]['payment_image'] = $so_list->payment_image;
						$data["sales_order_list"][$i_s]['transfer_image'] = $so_list->transfer_image;
              
						$sales_order_product = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
              
						if(count($sales_order_product) >0){
							$j_s=0;
							foreach($sales_order_product as $sop_list){
								$plant_list = $this->db->query("select * from plant_master where plantid = '".$sop_list->plant_id."'")->row();
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['sales_order_products_id'] = $sop_list->sales_order_products_id;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product'] = $sop_list->product_name;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product_id'] = $sop_list->Product;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['ListPrice'] = $sop_list->ListPrice;
								$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_id'] = $sop_list->plant_id;
								$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_name'] = $plant_list->plantName;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Quantity'] = $sop_list->Quantity;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Discount'] = $sop_list->Discount;
								$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Subtotal'] = $sop_list->Subtotal;
								$j_s++;
							}
						}else{
							$data["sales_order_list"][$i_s]['sales_order_product_list'] = array();
						}
						
						// Get Sales Persons Product List
						$tpProducts = $this->db->query("select * from tp_sales_order_sales_person_distributors a inner join product_master b on (a.product_id = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
						
						if(count($tpProducts) >0){
							$cntr=0;
							foreach($tpProducts as $tpp_list){								
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['tp_sales_order_sales_person_distributors_id'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['saleslineItemId'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product'] = $tpp_list->product_name;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_id'] = $tpp_list->product_id;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_code'] = $tpp_list->product_code;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['plan_quantity'] = $tpp_list->plan_quantity;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['ordered_quantity'] = $tpp_list->ordered_quantity;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_quantity'] = $tpp_list->supplied_quantity;
								$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_date'] = $tpp_list->supplied_date;
								$cntr++;
							}
						}else{
							$data["sales_order_list"][$i_s]['salesPersonsProducts'] = array();
						}

						$i_s++;
					}
				}else{
					$data["sales_order_list"] = array();
				}
			}else{
				$data["sales_order_list"] = array();
			}

			// Get Sales Calls Info
			if($SalesCalls_ids != "" || $SalesCalls_ids != NULL){
				$sales_call_list = $this->db->query("select *,a.Phone from sales_call a inner join users b on (a.Owner = b.user_id) where  a.sales_call_id in (".$SalesCalls_ids.") and a.archieve != 1 order by a.sales_call_id desc")->result();
        
				$i =0;
				foreach($sales_call_list as $sc_list){
					$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;
					$data['sales_call_list'][$i]['releted_to'] = $sc_list->releted_to;
					$data['sales_call_list'][$i]['sales_call_customer_contact_type'] = $sc_list->sales_call_customer_contact_type;
					$data['sales_call_list'][$i]['id'] = $sc_list->id;
					$data['sales_call_list'][$i]['Company'] = $sc_list->Company;
					$data['sales_call_list'][$i]['releted_to_new_contact_customer'] = $sc_list->releted_to_new_contact_customer;
					$data['sales_call_list'][$i]['new_contact_customer_person_name'] = $sc_list->new_contact_customer_person_name;
					$data['sales_call_list'][$i]['new_contact_customer_company_name'] = $sc_list->new_contact_customer_company_name;
					$data['sales_call_list'][$i]['new_contact_customer_other_person_name'] = $sc_list->new_contact_customer_other_person_name;

					if($call_date == "00-00-0000" || $call_date == "01-01-1970" || $call_date == NULL){
						$data['sales_call_list'][$i]['Call_Date'] = "";
					}else{
						$data['sales_call_list'][$i]['Call_Date'] = date("Y-m-d",strtotime($sc_list->Call_Date));
					}
					
					$data['sales_call_list'][$i]['Phone'] = $sc_list->Phone;
					$data['sales_call_list'][$i]['Call_Type'] = $sc_list->Call_Type;
					$data['sales_call_list'][$i]['Call_Date'] = $sc_list->Call_Date;
					$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
					$data['sales_call_list'][$i]['call_report'] = $sc_list->call_report;
					$data['sales_call_list'][$i]['MinutesOfMeeting'] = $sc_list->MinutesOfMeeting;
					$data['sales_call_list'][$i]['CommentsByManager'] = $sc_list->CommentsByManager;
					
					$data['sales_call_list'][$i]['Status'] = $sc_list->Status;
					$data['sales_call_list'][$i]['sales_calls_temp_id'] = $sc_list->sales_calls_temp_id;
					$data['sales_call_list'][$i]['Owner'] = $sc_list->Owner;
					$data['sales_call_list'][$i]['Owner_name'] = $sc_list->name;
					$data['sales_call_list'][$i]['tracking_id'] = $sc_list->tracking_id;
					$data['sales_call_list'][$i]['lat_lon_val'] = $sc_list->lat_lon_val;
					$data['sales_call_list'][$i]['geo_status'] = $sc_list->geo_status;
					$data['sales_call_list'][$i]['Assigned_To'] = user_details($sc_list->Assigned_To);
					$data['sales_call_list'][$i]['Assigned_To_id'] = $sc_list->Assigned_To;

					if($sc_list->contacts_id != ""||$sc_list->contacts_id != NULL || $sc_list->contacts_id != 0){
						$contacts_list = $this->db->query("select * from contacts where contact_id =".$sc_list->contacts_id)->row();
						if(count($contacts_list)>0){
							$data['sales_call_list'][$i]['contact_id'] = $sc_list->contacts_id;
							$data['sales_call_list'][$i]['contact_name'] = $contacts_list->FirstName." ".$contacts_list->LastName;
						}else{
							$data['sales_call_list'][$i]['contact_id'] = 0;
							$data['sales_call_list'][$i]['contact_name'] = "";
						}
					}else{
						$data['sales_call_list'][$i]['contact_id'] = 0;
						$data['sales_call_list'][$i]['contact_name'] = "";
					}
				  
					$data['sales_call_list'][$i]['Description'] = $sc_list->Description;
					$call_date = date("d-m-Y",strtotime($sc_list->Call_Date));
				  
					$data['sales_call_list'][$i]['Email'] = $sc_list->Email;
					$data['sales_call_list'][$i]['Comments'] = $sc_list->Comments;
					$NextVisitDate = date("d-m-Y",strtotime($sc_list->NextVisitDate));
				  
					if($NextVisitDate == "00-00-0000" || $NextVisitDate == "01-01-1970" || $NextVisitDate == NULL){
						$data['sales_call_list'][$i]['NextVisitDate'] = "";
					}else{
						$data['sales_call_list'][$i]['NextVisitDate'] = date("Y-m-d",strtotime($sc_list->NextVisitDate));
					}
					
					$data['sales_call_list'][$i]['Priority'] = $sc_list->Priority;
					$data['sales_call_list'][$i]['MinutesOfMeeting'] =  $sc_list->MinutesOfMeeting;
					$data['sales_call_list'][$i]['CommentsByManager '] = $sc_list->CommentsByManager;

					$data['sales_call_list'][$i]['sales_call_id'] = $sc_list->sales_call_id;
					$data['sales_call_list'][$i]['created_date_time'] = $sc_list->created_date_time;

					$i++;
				}
			}else{
				$data['sales_call_list'] = array();
			}
      
			// Get Compaints Info
			if($Complaints_ids != "" || $Complaints_ids != NULL){
				$complaint_list=$this->db->query("select * from Complaints a inner join users b on (a.ComplaintOwner = b.user_id) where  a.ComplaintOwner in (".$Complaints_ids.") and  a.archieve !=1 order by a.Complaint_id DESC")->result();

				if(count($complaint_list)>0){
					$i=0;
					foreach ($complaint_list as $value) {
						$complanints_ass_recc_list = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where Complaint_id = '".$value->Complaint_id."'")->result();

						$data['complaint_list'][$i]['sales_assessment'] =array();
						$data['complaint_list'][$i]['sales_recommendedsolution'] = array();
						$data['complaint_list'][$i]['area_assessment'] = array();
						$data['complaint_list'][$i]['area_recommendedsolution'] = array();
						$data['complaint_list'][$i]['regional_assessment'] =array();
						$data['complaint_list'][$i]['regional_recommendedsolution'] = array();
						$data['complaint_list'][$i]['national_assessment'] = array();
						$data['complaint_list'][$i]['national_recommendedsolution'] = array();
						$data['complaint_list'][$i]['quality_assessment'] = array();
						$data['complaint_list'][$i]['quality_recommendation'] = array();
						$data['complaint_list'][$i]['manufacturing_assessment'] = array();
						$data['complaint_list'][$i]['management_assessment'] = array();
						$data['complaint_list'][$i]['management_recommendation'] = array();
						$ik=$j=$k=$l=$m=$n=$o=$p=$q=$r=$s=$oi=$pi=0;

						foreach($complanints_ass_recc_list as $comp_ass_recc_val){
							$profile_id = $comp_ass_recc_val->profile_id;
							$type = $comp_ass_recc_val->type;
							$complent_recomendation = $comp_ass_recc_val->complent_recomendation;
							$com_ass_rec_id = $comp_ass_recc_val->complaints_aeeigment_recommendation_id;

							if($profile_id == SALESOFFICER && $type == "assessment" || $profile_id == SalesExecutive && $type == "assessment"){
								$data['complaint_list'][$i]['sales_assessment'][$ik]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['sales_assessment'][$ik]['complement_ass_name'] = $complent_recomendation;
								$ik++;
							}else if($profile_id == SALESOFFICER && $type == "recommendation" || $profile_id == SalesExecutive && $type == "recommendation" ){
								$data['complaint_list'][$i]['sales_recommendedsolution'][$j]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['sales_recommendedsolution'][$j]['complement_ass_name'] = $complent_recomendation;
								$j++;
							}else if($profile_id == AreaManager && $type == "assessment"){
								$data['complaint_list'][$i]['area_assessment'][$k]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['area_assessment'][$k]['complement_ass_name'] = $complent_recomendation;
								$k++;
							}else if($profile_id == AreaManager && $type == "recommendation" ){
								$data['complaint_list'][$i]['area_recommendedsolution'][$l]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['area_recommendedsolution'][$l]['complement_ass_name'] = $complent_recomendation;
								$l++;
							}else if($profile_id == Regionalmanager && $type == "assessment"){
								$data['complaint_list'][$i]['regional_assessment'][$m]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['regional_assessment'][$m]['complement_ass_name'] = $complent_recomendation;
								$m++;
							}else if($profile_id == Regionalmanager && $type == "recommendation" ){
								$data['complaint_list'][$i]['regional_recommendedsolution'][$n]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['regional_recommendedsolution'][$n]['complement_ass_name'] = $complent_recomendation;
								$n++;
							}else if($profile_id == NationalHead && $type == "assessment"){
								$data['complaint_list'][$i]['national_assessment'][$o]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['national_assessment'][$o]['complement_ass_name'] = $complent_recomendation;
								$o++;
							}else if($profile_id == NationalHead && $type == "recommendation" ){
								$data['complaint_list'][$i]['national_recommendedsolution'][$p]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['national_recommendedsolution'][$p]['complement_ass_name'] = $complent_recomendation;
								$p++;
							}else if($profile_id == QualityDepartment && $type == "assessment"){
								$data['complaint_list'][$i]['quality_assessment'][$oi]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['quality_assessment'][$oi]['complement_ass_name'] = $complent_recomendation;
								$oi++;
							}else if($profile_id == QualityDepartment && $type == "recommendation" ){
								$data['complaint_list'][$i]['quality_recommendation'][$pi]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['quality_recommendation'][$pi]['complement_ass_name'] = $complent_recomendation;
								$pi++;
							}else if($profile_id == Manufacturing && $type == "assessment"){
								$data['complaint_list'][$i]['manufacturing_assessment'][$q]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['manufacturing_assessment'][$q]['complement_ass_name'] = $complent_recomendation;
								$q++;
							}else if($profile_id == SUPERADMIN && $type == "assessment"){
								$data['complaint_list'][$i]['management_assessment'][$r]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['management_assessment'][$r]['complement_ass_name'] = $complent_recomendation;
								$r++;
							}else if($profile_id == SUPERADMIN && $type == "recommendation" ){
								$data['complaint_list'][$i]['management_recommendation'][$s]['com_ass_rec_id'] = $com_ass_rec_id;
								$data['complaint_list'][$i]['management_recommendation'][$s]['complement_ass_name'] = $complent_recomendation;
								$s++;
							}
						}
	       
						$data['complaint_list'][$i]['complaint_id']=$value->Complaint_id;
						$customer_list = $this->db->query("select * from customers where customer_id ='".$value->CustomerName."'")->row();
						
						if($customer_list > 0){
							$data['complaint_list'][$i]['customer_id']=$customer_list->customer_id;
							$data['complaint_list'][$i]['CustomerName']=$customer_list->CustomerName;
						}else{
							$data['complaint_list'][$i]['customer_id']='';
							$data['complaint_list'][$i]['CustomerName']='';
						}
						
						$data['complaint_list'][$i]['ComplaintNumber'] = $value->ComplaintNumber;
						$data['complaint_list'][$i]['salesorderdate']=$value->salesorderdate;

						$data['complaint_list'][$i]['salesordernumber'] = $value->salesordernumber;
						$data['complaint_list'][$i]['feedback']=$value->feedback;
						$data['complaint_list'][$i]['applicationdate']=$value->applicationdate;

						$data['complaint_list'][$i]['feedbackother']=$value->feedbackother;
						$data['complaint_list'][$i]['invoicedate']=$value->invoicedate;

						$data['complaint_list'][$i]['invoicenumber']=$value->invoicenumber;
						$data['complaint_list'][$i]['batchnumber']=$value->batchnumber;
						$data['complaint_list'][$i]['defectivesample']=$value->defectivesample;
						$data['complaint_list'][$i]['sampleplantlab']=$value->sampleplantlab;

						$data['complaint_list'][$i]['sales_sitevisit']=$value->sales_sitevisit;
						$data['complaint_list'][$i]['sales_status']=$value->sales_status;

						$data['complaint_list'][$i]['area_sitevisit']=$value->area_sitevisit;
						$data['complaint_list'][$i]['area_status']=$value->area_status;

						$data['complaint_list'][$i]['regional_sitevisit']=$value->regional_sitevisit;
						$data['complaint_list'][$i]['regional_status']=$value->regional_status;

						$data['complaint_list'][$i]['national_sitevisit']=$value->national_sitevisit;
						$data['complaint_list'][$i]['national_status']=$value->national_status;

						$data['complaint_list'][$i]['credit_note_given']=$value->credit_note_given;
						$data['complaint_list'][$i]['material_replaced']=$value->material_replaced;
						$data['complaint_list'][$i]['comercial_remarks']=$value->comercial_remarks;
						$data['complaint_list'][$i]['qualitytestsdone']=$value->qualitytestsdone;
						$i++;
					}
				}else{
					$data['complaint_list'] = array();
				}
			}else{
				$data['complaint_list'] = array();
			}

			if($Quotation_ids != "" || $Quotation_ids != NULL){

				$qutation_list = $this->db->query("select * from Quotation where Quotation_id in (".$Quotation_ids.") and  archieve != 1")->result();
				if(count($qutation_list) >0){
					$iQ=0;
					foreach($qutation_list as $qutation_val){
						$customer_details = $this->db->query("select * from customers where customer_id ='".$qutation_val->Customer."'")->row();
						$contact_list = $this->db->query("select * from contacts where contact_id ='".$qutation_val->Contact."'")->row();
						$data["qutation_list"][$iQ]['Quotation_id'] = $qutation_val->Quotation_id;
						$data["qutation_list"][$iQ]['QuotationversionID'] = $qutation_val->QuotationversionID;
						$data["qutation_list"][$iQ]['Opportunity'] = $qutation_val->Opportunity;
						$data["qutation_list"][$iQ]['QuotationDate'] = date("Y-m-d",strtotime($qutation_val->QuotationDate));
						$data["qutation_list"][$iQ]['ExpiryDate'] = date("Y-m-d",strtotime($qutation_val->ExpiryDate));
						$data["qutation_list"][$iQ]['Customer'] = $customer_details->CustomerName;
						$data["qutation_list"][$iQ]['Customer_id'] = $qutation_val->Customer;
						
						if(count($contact_list)>0){
							$data["qutation_list"][$iQ]['Contact_id'] = $contact_list->contact_id;
							$data["qutation_list"][$iQ]['Contact'] = $contact_list->FirstName." ".$contact_list->LastName;
						}else{
							$data["qutation_list"][$iQ]['Contact_id'] = "";
							$data["qutation_list"][$iQ]['Contact'] = "";
						}
						
						$data["qutation_list"][$iQ]['BillingStreet1'] = $qutation_val->BillingStreet1;
						$data["qutation_list"][$iQ]['Billingstreet2'] = $qutation_val->Billingstreet2;
						$data["qutation_list"][$iQ]['BillingCountry'] = $qutation_val->BillingCountry;
						$data["qutation_list"][$iQ]['StateProvince'] = $qutation_val->StateProvince;
						$data["qutation_list"][$iQ]['BillingCity'] = $qutation_val->BillingCity;
						$data["qutation_list"][$iQ]['BillingZipPostal'] = $qutation_val->BillingZipPostal;
						$data["qutation_list"][$iQ]['ShippingStreet1'] = $qutation_val->ShippingStreet1;
						$data["qutation_list"][$iQ]['Shippingstreet2'] = $qutation_val->Shippingstreet2;
						$data["qutation_list"][$iQ]['ShippingCountry'] = $qutation_val->ShippingCountry;
						$data["qutation_list"][$iQ]['ShippingStateProvince'] = $qutation_val->ShippingStateProvince;
						$data["qutation_list"][$iQ]['ShippingCity'] = $qutation_val->ShippingCity;
						$data["qutation_list"][$iQ]['ShippingZipPostal'] = $qutation_val->ShippingZipPostal;
						$data["qutation_list"][$iQ]['TotalPrice'] = $qutation_val->TotalPrice;
						$data["qutation_list"][$iQ]['Remarks'] = $qutation_val->Remarks;
				
						$checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$qutation_val->Customer."'")->row();
						$qutation_product = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$data["qutation_list"][$iQ]['Quotation_id']."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result();

						$jQ=0;
				  
						foreach($qutation_product as $qpp_list){
							$product_master_list = $this->db->query("select * from product_master where product_id =".$qpp_list->Product)->row();
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quotation_Product_id'] = $qpp_list->Quotation_Product_id;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['ListPrice'] = $qpp_list->ListPrice;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product'] = $qpp_list->product_name;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product_id'] = $qpp_list->Product;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quantity'] = $qpp_list->Quantity;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Subtotal'] = $qpp_list->Subtotal;
							$data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Discount'] = $qpp_list->Discount;
							$jQ++;
						}
						$iQ++;
					}
				}else{
					$data["qutation_list"] = array();
				}
			}else{
				$data["qutation_list"] = array();
			}

			// Get Payment Colleciton Id Info
			if($payment_collection_ids != "" || $payment_collection_ids != NULL){
				$payment_collection_list = $this->db->query("select * from payment_collection where payment_collection_id  in (".$payment_collection_ids.") and archieve != '1'")->result();

				if(count($payment_collection_list)>0){
					
					$pay_i= "0";
					
					foreach($payment_collection_list as $payment_val){
						
						$divisions = explode(",",$payment_val->Division);
							
						if(count($divisions) > 0){
							$x = 0;
							foreach($divisions as $division){
								$division_list = $this->db->query("select division_master_id, division_name from division_master where division_master_id = '".$division."'")->row();
								
								if($x > 0){
									$data['payment_collection_list'][$pay_i]['Division'] .= ", ";
								}
								$data['payment_collection_list'][$pay_i]['Division'] .= $division_list->division_name;
								$x++;
							}
						}else{
							$data['payment_collection_list'][$pay_i]['Division'] = $payment_val->Division;
						}		

						$customer_list = $this->db->query("select * from customers where customer_id ='".$payment_val->customer_id."'")->row();
						$contact_list = $this->db->query("select * from contacts where contact_id = '".$payment_val->contact_id."'")->row();
						$userInfo = $this->db->query("select user_id, name from users where user_id = '".$payment_val->created_by."'")->row();

						$data['payment_collection_list'][$pay_i]['payment_collection_id'] = $payment_val->payment_collection_id;
						$data['payment_collection_list'][$pay_i]['customer_name'] = $customer_list->CustomerName;
						$data['payment_collection_list'][$pay_i]['customer_id'] = $payment_val->customer_id;
						$data['payment_collection_list'][$pay_i]['contact_id'] = $payment_val->contact_id;
						$data['payment_collection_list'][$pay_i]['contact_name'] = $contact_list->FirstName." ".$contact_list->LastName;
						
						$data['payment_collection_list'][$pay_i]['invoice_number'] = $payment_val->invoice_number;
						$data['payment_collection_list'][$pay_i]['payment_mode'] = $payment_val->payment_mode;
						
						$data['payment_collection_list'][$pay_i]['customer_location'] = $payment_val->customer_location;
						$data['payment_collection_list'][$pay_i]['CustomerSAPCode'] = $customer_list->CustomerSAPCode;
						$data['payment_collection_list'][$pay_i]['comments_by_commercial_team'] = $payment_val->comments_by_commercial_team;
						$data['payment_collection_list'][$pay_i]['sales_owner_id'] = $userInfo->user_id;
						$data['payment_collection_list'][$pay_i]['sales_owner_name'] = $userInfo->name;
						
						if($payment_val->payment_image != '' || $payment_val->payment_image != NULL){
							$data['payment_collection_list'][$pay_i]['payment_image'] = "/images/Payment/".$payment_val->payment_image;
						}else{
							$data['payment_collection_list'][$pay_i]['payment_image'] = NULL;
						}

						if($payment_val->payment_mode == "Cash"){
							$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
							$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
						}else if($payment_val->payment_mode == "Cheque"){
							$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
							$data['payment_collection_list'][$pay_i]['cheque_no'] = $payment_val->cheque_no;
							$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
							$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
							$data['payment_collection_list'][$pay_i]['cheque_date'] = date("Y-m-d",strtotime($payment_val->cheque_date));
							$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
							$data['payment_collection_list'][$pay_i]['status'] = $payment_val->status;      
						}else if($payment_val->payment_mode == "Online"){
							$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
							$data['payment_collection_list'][$pay_i]['transfer_type'] = $payment_val->transfer_type;
							$data['payment_collection_list'][$pay_i]['transaction_ref_no'] = $payment_val->transaction_ref_no;
							$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
							$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));  
						}        
						$pay_i++;
					} 
				}else{
					$data['payment_collection_list'] = array();       
				}
			}else{
				$data['payment_collection_list'] = array();       
			}

			$this->response(array('code'=>'200','message'=>'Update table List Value','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>200,'message'=>'User Id Cannot be  Empty','result'=>$data,'requestname'=>$method));
		}
	}
  
  public function users_update_date($parameters,$method,$user_id){
    $user_id = $user_id;
    $type = $parameters['type'];
    if($user_id != "" || $user_id != null){
      if($type == "Android"){
        $param_1['android_last_data_updated'] = date("Y-m-d H:I:s");
      }else if($type == "iOS"){
        $param_1['iOS_last_data_updated'] = date("Y-m-d H:i:s");
      }
      $param_1['modified_date_time'] = date("Y-m-d H:i:s");
      $param_1['modified_by'] = $user_id;
	  $data['last_date_time_updated']=date("Y-m-d H:i:s");
      $ok = $this->Generic_model->updateData('users',$param_1,array('user_id'=>$user_id));
      if($ok == 1){
         $this->response(array('code'=>'200','message' => 'Successfull updated users date','result'=>$data,'requestname'=>$method));
      }else{
         $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
      }
    }else{
      $this->response(array('code'=>200,'message'=>'User Id Cannot be  Empty','result'=>$data,'requestname'=>$method));
    }

  }

	public function dashboard($parameters,$method,$user_id){
		$final_users_id = $parameters['team_id'];

		if($final_users_id != "" || $final_users_id != NULL){ 
			$Calles_call = $this->db->query("select * from sales_call a inner join users b on (a.Owner = b.user_id) where a.Owner in (".$final_users_id.") and a.archieve != 1")->result();
		
			// $opp_stages = $this->db->query("SELECT * FROM `opportunities` where OpportunityOwner in (".$final_users_id.") GROUP by Stage")->result();
		
			$status_open = 0;
			$status_completed = 0;
			$status_missed = 0;
		  
			foreach($Calles_call as $values_cal){
				$Status = $values_cal->Status;
				if($Status == "Open"){
					$status_open = $status_open+1;
				}else if($Status == "Missed"){
					$status_missed = $status_missed+1;
				}else if($Status == "Completed"){
					$status_completed = $status_completed+1;
				}
			}

			$Prospecting_val  = 0 ;
			$makers  = 0;
			$fitment  = 0;
			$sample = 0 ;
			$Quote  = 0; 
			$Review  = 0;
			$price  = 0;
			$Payment  = 0;
			$Won  = 0;
			$Lost  = 0;

			/*
			foreach($opp_stages as $opp_val){
				$stage = $opp_val->Stage;
				if($stage == "Prospecting"){
					$Prospecting_val = $Prospecting_val+1;
				}else if($stage == "Id. Decision Makers"){
					$makers = $makers+1;
				}else if($stage == "Product fitment"){
					$fitment = $fitment+1;
				}else if($stage == "Approval of sample"){
					$sample = $sample+1;
				}else if($stage == "Proposal/Price Quote"){
					$Quote = $Quote+1;
				}else if($stage == "Negotiation/Review"){
					$Review = $Review+1;
				}else if($stage == "Product and price approval"){
					$price = $price+1;
				}else if($stage == "Payment terms ok"){
					$Payment = $Payment+1;
				}else if($stage == "Closed Won"){
					$Won = $Won+1;
				}else if($stage == "Closed Lost"){
					$Lost = $Lost+1;
				}
			}
			*/

			$data['dashboard']['sales_call_list']['status_open'] = $status_open;
			$data['dashboard']['sales_call_list']['status_missed'] = $status_missed;
			$data['dashboard']['sales_call_list']['status_completed'] = $status_completed;

			$data['dashboard']['sales_funnel']['Prospecting_val'] = $Prospecting_val;
			$data['dashboard']['sales_funnel']['makers'] = $makers;
			$data['dashboard']['sales_funnel']['fitment'] = $fitment;
			$data['dashboard']['sales_funnel']['sample'] = $sample;
			$data['dashboard']['sales_funnel']['Quote'] = $Quote;
			$data['dashboard']['sales_funnel']['Review'] = $Review;
			$data['dashboard']['sales_funnel']['price'] = $price;
			$data['dashboard']['sales_funnel']['Payment'] = $Payment;
			$data['dashboard']['sales_funnel']['Won'] = $Won;
			$data['dashboard']['sales_funnel']['Lost'] = $Lost;

			$this->response(array('code'=>'200','message' => 'Dashboard list','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}

  public function all_tables_records_view($type,$id){	  

    if($type == "lead"){	 
		/* changes to the old code */
		// start		
		$lead_list = $this->db->query("select *,a.created_date_time as createdDateTime from leads a left join users b on (a.LeadOwner = b.user_id) where a.leads_id = '".$id."'")->result();

		if(count($lead_list)>0){
			$li=0;
			foreach($lead_list as $leads_val){			
			
				// Gathering lead data        							
				$data['lead_list'][$li]['leads_id'] =  $leads_val->leads_id;  				
				$data['lead_list'][$li]['lead_number'] =  $leads_val->lead_number;  	
				$data['lead_list'][$li]['Company'] = $leads_val->Company;
				$data['lead_list'][$li]['Company_Text'] = $leads_val->Company_text;
				$data['lead_list'][$li]['isAccountTagged'] = $leads_val->isAccountTagged;
				$data['lead_list'][$li]['is_lead_converted'] = $leads_val->is_lead_converted;
						
				$data['lead_list'][$li]['lead_email'] = $leads_val->lead_email;
				$data['lead_list'][$li]['LeadOwner'] = ucwords($leads_val->name);
				$data['lead_list'][$li]['LeadSource'] = $leads_val->LeadSource;				
				$data['lead_list'][$li]['lead_phone'] = $leads_val->lead_phone;
				$data['lead_list'][$li]['lead_website'] = $leads_val->lead_website;
				$data['lead_list'][$li]['lead_status'] = $leads_val->lead_status;
				
				// lead Project Data
				$data['lead_list'][$li]['lead_project_name'] = $leads_val->lead_project_name;
				$data['lead_list'][$li]['lead_project_type'] = $leads_val->lead_project_type;
				$data['lead_list'][$li]['lead_class_of_project'] = $leads_val->lead_class_of_project;
				$data['lead_list'][$li]['lead_size_class_of_project'] = $leads_val->lead_size_class_of_project;
				$data['lead_list'][$li]['size_calss_unit_no_of_floor_per_block'] = $leads_val->size_calss_unit_no_of_floor_per_block;
				$data['lead_list'][$li]['size_calss_unit_no_of_blocks'] = $leads_val->size_calss_unit_no_of_blocks;
				$data['lead_list'][$li]['size_calss_unit'] = $leads_val->size_calss_unit;
				$data['lead_list'][$li]['lead_project_status'] = $leads_val->lead_project_status;
				$data['lead_list'][$li]['no_of_flats'] = $leads_val->no_of_flats;
				$data['lead_list'][$li]['sft'] = $leads_val->sft;
				$data['lead_list'][$li]['cubic_meters'] = $leads_val->cubic_meters;
				
				$data['lead_list'][$li]['lead_street1'] = $leads_val->lead_street1;
				$data['lead_list'][$li]['lead_street2'] = $leads_val->lead_street2;								
				$data['lead_list'][$li]['lead_plotno'] = $leads_val->lead_plotno;
				$data['lead_list'][$li]['lead_area'] = $leads_val->lead_area;
				$data['lead_list'][$li]['lead_state'] = $leads_val->lead_state;
				$data['lead_list'][$li]['lead_country'] = $leads_val->lead_country;				
				$data['lead_list'][$li]['lead_City'] = $leads_val->lead_City;
				$data['lead_list'][$li]['lead_pin_zip_code'] = $leads_val->lead_pin_zip_code;

				$data['lead_list'][$li]['lead_project_street1'] = $leads_val->lead_project_street1;
				$data['lead_list'][$li]['lead_project_street2'] = $leads_val->lead_project_street2;				
				$data['lead_list'][$li]['lead_project_plot_no'] = $leads_val->lead_project_plot_no;
				$data['lead_list'][$li]['lead_project_land_mark'] = $leads_val->lead_project_land_mark;				
				$data['lead_list'][$li]['lead_project_city'] = $leads_val->lead_project_city;
				$data['lead_list'][$li]['lead_project_state'] = $leads_val->lead_project_state;
				$data['lead_list'][$li]['lead_project_pin_zip_code'] = $leads_val->lead_project_pin_zip_code;				
				
				$data['lead_list'][$li]['lead_main_contact_id'] = $leads_val->lead_main_contact_id;
				$data['lead_list'][$li]['lead_main_contact_name'] = $leads_val->lead_main_contact_name;				
				$data['lead_list'][$li]['lead_main_contact_designation'] = $leads_val->lead_main_contact_designation;
				$data['lead_list'][$li]['lead_main_contact_email'] = $leads_val->lead_main_contact_email;				
				$data['lead_list'][$li]['lead_main_contact_mobile'] = $leads_val->lead_main_contact_mobile;
				$data['lead_list'][$li]['lead_main_contact_category'] = $leads_val->lead_main_contact_category;
				$data['lead_list'][$li]['lead_main_contact_phone'] = $leads_val->lead_main_contact_phone;				
				$data['lead_list'][$li]['lead_main_contact_company'] = $leads_val->lead_main_contact_company;				
				$data['lead_list'][$li]['created_date_time'] = $leads_val->createdDateTime;				
				
				// Gather Associsative Contact List for this lead				
				$Associate_contact_list = $this->db->query("select lead_associate_contact_id, a.contact_id, assoc_contact_company, associate_contact_designation, associate_contact_mobile, associate_contact_category, b.FirstName, b.LastName from lead_associate_contacts a inner join contacts b on (a.contact_id = b.contact_id) where a.leads_id = '".$leads_val->leads_id."'")->result();

				if(count($Associate_contact_list) > 0){
					$acli = 0;
					foreach($Associate_contact_list as $ass_val){						
						$data['lead_list'][$li]['associate_contact'][$acli] = $ass_val;
						$acli++;
					} 
				}else{
					$data['lead_list'][$li]['associate_contact'] = array();
				}
				
				// Gather Action Work Data of this Lead
				$Action_work_list = $this->db->query("select action_work_done_id, action_work_done_date, action_work_done_remarks from lead_action_work_done where leads_id = '".$leads_val->leads_id."'")->result();

				if(count($Action_work_list) > 0){
					$awli = 0;
					foreach($Action_work_list as $awl_val){						
						$data['lead_list'][$li]['action_work_done'][$awli] = $awl_val;
						$awli++;
					} 					
				}else{
					$data['lead_list'][$li]['action_work_done'] = array();					
				}				
				$li++;
			}
		}else{
			$data['lead_list'] = array();
		}
		return $data; 
		// end

    }else if($type == "Contact"){
     
		$final_users_id = $parameters['team_id'];
		
		$contacts_list = $this->db->query("select a.contact_id, a.Salutation, a.FirstName, a.LastName, a.Email, a.Fax, a.Mobile, a.Phone, a.Department, a.Title_Designation, a.OtherPhone, a.HomePhone, isAccountTagged, a.created_date_time as createdDateTime, a.Company_text, a.Birthdate, a.Description, a.LeadSource, a.ContactOwner, a.Category, a.MallingStreet1, a.MallingStreet2, a.MallingCountry, a.MallingStateProvince, a.MallingCity, a.MallingZipPostal, a.OtherStreet1, a.Otherstreet2, a.OtherCountry,a.OtherStateProvince, a.OtherCity, a.OtherZipPostal, a.other_category, a.ReportsTo, b.name, c.customer_id, c.CustomerName from contacts a left join users b on (a.ContactOwner = b.user_id) left join customers c on (a.Company = c.customer_id) where a.contact_id = '".$id."' and  a.archieve != 1 order by a.contact_id DESC")->result();

		$ic=0;
        
		foreach($contacts_list as $contact_val){
            if($contact_val->ReportsTo == "" || $contact_val->ReportsTo == NULL ||$contact_val->ReportsTo == 0){
				$data['contact_list'][$ic]['ReportsTo_name'] = "";
				$data['contact_list'][$ic]['ReportsTo'] = "";
            }else{
				$report_detatis = $this->db->query("select * from contacts where contact_id =".$contact_val->ReportsTo)->row();
              
				if(count($report_detatis)>0){
					$data['contact_list'][$ic]['ReportsTo_name'] = $report_detatis->FirstName ." ". $report_detatis->LastName;
					$data['contact_list'][$ic]['ReportsTo'] = $contact_val->ReportsTo;
				}else{
					$data['contact_list'][$ic]['ReportsTo_name'] = "";
					$data['contact_list'][$ic]['ReportsTo'] = "";
				}
            }
            $data['contact_list'][$ic]['contact_id'] = $contact_val->contact_id;
            $data['contact_list'][$ic]['Salutation'] = $contact_val->Salutation;
            $data['contact_list'][$ic]['FirstName'] = $contact_val->FirstName;
            $data['contact_list'][$ic]['LastName'] = $contact_val->LastName;
            $data['contact_list'][$ic]['Email'] = $contact_val->Email;
            $data['contact_list'][$ic]['Fax'] = $contact_val->Fax;
            $data['contact_list'][$ic]['Mobile'] = $contact_val->Mobile;
            $data['contact_list'][$ic]['Phone'] = $contact_val->Phone;                        
            $data['contact_list'][$ic]['Department'] = $contact_val->Department;
            $data['contact_list'][$ic]['Title_Designation'] = $contact_val->Title_Designation;
            $data['contact_list'][$ic]['OtherPhone'] = $contact_val->OtherPhone;
            $data['contact_list'][$ic]['HomePhone'] = $contact_val->HomePhone;
			$data['contact_list'][$ic]['isAccountTagged'] = $contact_val->isAccountTagged;
			
			if($contact_val->isAccountTagged == 1){
				$data['contact_list'][$ic]['Company'] = $contact_val->customer_id;
				$data['contact_list'][$ic]['Company_text'] = $contact_val->CustomerName;
				$data['contact_list'][$ic]['customer_id'] = $contact_val->customer_id;
			}else{
				$data['contact_list'][$ic]['Company'] = 0;
				$data['contact_list'][$ic]['Company_text'] = $contact_val->Company_text;	
				$data['contact_list'][$ic]['customer_id'] = 0;
			}
			
            $Birthdate = date("d-m-Y",strtotime($contact_val->Birthdate));
            if($Birthdate == "30-11-0001" || $Birthdate == "01-01-1970" || $Birthdate == NULL){
              $data['contact_list'][$ic]['Birthdate'] = "";
            }else{
              $data['contact_list'][$ic]['Birthdate'] = $Birthdate;
            }
            
            $data['contact_list'][$ic]['Description'] = $contact_val->Description;
            $data['contact_list'][$ic]['LeadSource'] = $contact_val->LeadSource;
            $data['contact_list'][$ic]['ContactOwner'] = $contact_val->ContactOwner;
			$data['contact_list'][$ic]['ContactOwner_name'] = $contact_val->name;
            $data['contact_list'][$ic]['Category'] = $contact_val->Category;
			$data['contact_list'][$ic]['other_category'] = $contact_val->other_category;
            $data['contact_list'][$ic]['MallingStreet1'] = $contact_val->MallingStreet1;
            $data['contact_list'][$ic]['Mallingstreet2'] = $contact_val->Mallingstreet2;
            $data['contact_list'][$ic]['MallingCountry'] = $contact_val->MallingCountry;
            $data['contact_list'][$ic]['MallingStateProvince'] = $contact_val->MallingStateProvince;
            $data['contact_list'][$ic]['MallingCity'] = $contact_val->MallingCity;
            $data['contact_list'][$ic]['MallingZipPostal'] = $contact_val->MallingZipPostal;
            $data['contact_list'][$ic]['OtherStreet1'] = $contact_val->OtherStreet1;
            $data['contact_list'][$ic]['Otherstreet2'] = $contact_val->Otherstreet2;
            $data['contact_list'][$ic]['OtherCountry'] = $contact_val->OtherCountry;
            $data['contact_list'][$ic]['OtherStateProvince'] = $contact_val->OtherStateProvince;
            $data['contact_list'][$ic]['OtherCity'] = $contact_val->OtherCity;
            $data['contact_list'][$ic]['OtherZipPostal'] = $contact_val->OtherZipPostal;
			$data['contact_list'][$ic]['created_date_time'] = $contact_val->createdDateTime;
            $ic++;
            
          } 

      return $data;
	  
    }else if($type == "Customer"){		
		
		$customer_list = $this->db->query("select *, a.created_date_time as createdDateTime from customers a inner join customer_users_maping b on (b.customer_id = a.customer_id) inner join users c on (b.user_id = c.user_id) where a.customer_id ='".$id."' group by b.customer_id ")->result();
		
        if(count($customer_list)>0){			
			$ic=0;
			foreach ($customer_list as $customer_val) {  
				$customer_user_list = $this->db->query("select * from customer_users_maping a inner join users b on (a.user_id = b.user_id) where customer_id =".$customer_val->customer_id)->result();               
				$data['customer_list'][$ic]['customer_id']=$customer_val->customer_id;
				$data['customer_list'][$ic]['CustomerName']=$customer_val->CustomerName;
				$data['customer_list'][$ic]['Customer_location']=$customer_val->Customer_location;
				$data['customer_list'][$ic]['CustomerSAPCode']=$customer_val->CustomerSAPCode;
				$data['customer_list'][$ic]['customer_number']=$customer_val->customer_number;
				$data['customer_list'][$ic]['contact_id']=$customer_val->contact_id;
				$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
				$data['customer_list'][$ic]['Description']=$customer_val->Description;
				$data['customer_list'][$ic]['Phone']=$customer_val->Phone;
				$data['customer_list'][$ic]['Website'] = $customer_val->Website;
				$data['customer_list'][$ic]['AccountSource']=$customer_val->AccountSource;
				$data['customer_list'][$ic]['AnnualRevenue']=$customer_val->AnnualRevenue;
				$data['customer_list'][$ic]['GSTINNumber']=$customer_val->GSTINNumber;
				$data['customer_list'][$ic]['Employees']=$customer_val->Employees;
				$data['customer_list'][$ic]['pancard']=$customer_val->pancard;
				$data['customer_list'][$ic]['approve_status']=$customer_val->approve_status;
				$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;
				$data['customer_list'][$ic]['manager_user_id']=$customer_val->manager_user_id;
				
				if($customer_val->manager_user_id != 0 || $customer_val->manager_user_id != '' || $customer_val->manager_user_id != NULL){
					// Get Manager Info
					$managerInfo = $this->Generic_model->getSingleRecord('users',array('user_id'=>$customer_val->manager_user_id));
					$data['customer_list'][$ic]['manager_name'] = $managerInfo->name;
				}else{
					$data['customer_list'][$ic]['manager_name'] = '';
				}
				$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;
				
				if($customer_val->PaymentTerms != 0 || $customer_val->PaymentTerms != "" || $customer_val->PaymentTerms != NULL){
					$PaymentTerms_list = $this->db->query("select * from Payment_terms where Payment_term_id =".$customer_val->PaymentTerms)->row();
					$data['customer_list'][$ic]['PaymentTerms']=$PaymentTerms_list->Payment_name;
				}
				
				$data['customer_list'][$ic]['pancard']=$customer_val->pancard;
				
				// Billing Street Info
				$data['customer_list'][$ic]['BillingStreet1']=$customer_val->BillingStreet1;
				$data['customer_list'][$ic]['Billingstreet2']=$customer_val->Billingstreet2;
				$data['customer_list'][$ic]['BillingCountry']=$customer_val->BillingCountry;
				$data['customer_list'][$ic]['StateProvince']=$customer_val->StateProvince;
				$data['customer_list'][$ic]['BillingCity']=$customer_val->BillingCity;
				$data['customer_list'][$ic]['BillingZipPostal']=$customer_val->BillingZipPostal;
				
				// Shipping Street Info
				$data['customer_list'][$ic]['ShippingStreet1']=$customer_val->ShippingStreet1;
				$data['customer_list'][$ic]['Shippingstreet2']=$customer_val->Shippingstreet2;
				$data['customer_list'][$ic]['ShippingCountry']=$customer_val->ShippingCountry;
				$data['customer_list'][$ic]['ShippingStateProvince']=$customer_val->ShippingStateProvince;
				$data['customer_list'][$ic]['ShippingCity']=$customer_val->ShippingCity;				
				$data['customer_list'][$ic]['ShippingZipPostal']=$customer_val->ShippingZipPostal;
				
				// Sales Organization				
				if($customer_val->SalesOrganisation != "" || $customer_val->SalesOrganisation != NULL){
					$SalesOrganisation_list = $this->db->query("select * from sales_organisation where sap_code= '".$customer_val->SalesOrganisation."'")->row();
					if(count($SalesOrganisation_list)>0){
						$data['customer_list'][$ic]['SalesOrganisation_id']=$SalesOrganisation_list->sap_code;
						$data['customer_list'][$ic]['SalesOrganisation']=$SalesOrganisation_list->organistation_name;
					}else{
						$data['customer_list'][$ic]['SalesOrganisation_id']="";
						$data['customer_list'][$ic]['SalesOrganisation']="";
					}
				}else{
					$data['customer_list'][$ic]['SalesOrganisation_id']="";
					$data['customer_list'][$ic]['SalesOrganisation']="";
				}
				
				// Distribution Channel
				if($customer_val->DistributionChannel != "" || $customer_val->DistributionChannel != NULL){
					$DistributionChannel_list = $this->db->query("select * from DistributionChannel where sap_code= '".$customer_val->DistributionChannel."'")->row();
					if(count($DistributionChannel_list)>0){
						$data['customer_list'][$ic]['DistributionChannel_id']=$DistributionChannel_list->sap_code;
						$data['customer_list'][$ic]['DistributionChannel']=$DistributionChannel_list->ditribution_name;
					}else{
						$data['customer_list'][$ic]['DistributionChannel_id']="";
						$data['customer_list'][$ic]['DistributionChannel']="";
					}
				}else{
					$data['customer_list'][$ic]['DistributionChannel_id']="";
					$data['customer_list'][$ic]['DistributionChannel']="";
				}
				
				// Division Info
				/*
				if($customer_val->Division != "" || $customer_val->Division != NULL){
					$Division_list = $this->db->query("select * from division_master where division_master_id = '".$customer_val->Division."'")->row();
					if(count($Division_list)>0){
						$data['customer_list'][$ic]['division_master_id']=$Division_list->division_master_id;
						$data['customer_list'][$ic]['Division']=$Division_list->division_name;
					}else{
						$data['customer_list'][$ic]['division_master_id']="";
						$data['customer_list'][$ic]['Division']="";
					}
				}else{
					$data['customer_list'][$ic]['division_master_id']="";
					$data['customer_list'][$ic]['Division']="";
				}
				*/

				$data['customer_list'][$ic]['Division']=$customer_val->Division;
				$data['customer_list'][$ic]['CustomerType']=$customer_val->CustomerType;
				$data['customer_list'][$ic]['CustomerContactName']=$customer_val->CustomerContactName;
				$data['customer_list'][$ic]['Email']=$customer_val->Email;
				$data['customer_list'][$ic]['CustomerCategory']=$customer_val->CustomerCategory;
				$data['customer_list'][$ic]['CreditLimit']=$customer_val->CreditLimit;
				$data['customer_list'][$ic]['SecurityInstruments']=$customer_val->SecurityInstruments;
				$data['customer_list'][$ic]['Pdc_Check_number']=$customer_val->Pdc_Check_number;
				$data['customer_list'][$ic]['Bank']=$customer_val->Bank;
				$data['customer_list'][$ic]['Bank_guarntee_amount_Rs']=$customer_val->Bank_guarntee_amount_Rs;
				$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;

				if($customer_val->IncoTerms1 != 0 || $customer_val->IncoTerms1 != "" || $customer_val->IncoTerms1 != NULL){
					$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms1)->row();
					$data['customer_list'][$ic]['IncoTerms1']=$IncoTerms_list->Incoterm_name;
				}
        
				if($customer_val->IncoTerms2 != 0 || $customer_val->IncoTerms2 != "" || $customer_val->IncoTerms2 != NULL){
					$IncoTerms_list = $this->db->query("select * from Incoterm where Incoterm_id =".$customer_val->IncoTerms2)->row();
					$data['customer_list'][$ic]['IncoTerms2']=$IncoTerms_list->Incoterm_name;
				}

				$data['customer_list'][$ic]['Fax']=$customer_val->Fax;
				$data['customer_list'][$ic]['Industry']=$customer_val->Industry;
				$data['customer_list'][$ic]['LC_amount_Rs']=$customer_val->LC_amount_Rs;
								
				$customer_price_list = $this->db->query("select * from customer_price_list a inner join product_price_master b on (a.price_list_id = b.Product_price_master_id) where  a.customer_id ='".$customer_val->customer_id."' and a.status ='Active'")->row();
				
				if(count($customer_price_list) > 0){
					$data['customer_list'][$ic]['price_list']=$customer_price_list->Area;
					$data['customer_list'][$ic]['price_list_id']=$customer_price_list->price_list_id;
				}else{
					$data['customer_list'][$ic]['price_list']="";
					$data['customer_list'][$ic]['price_list_id']="";
				}

				$data['customer_list'][$ic]['ParentAccount']=$customer_val->ParentAccount;
				$data['customer_list'][$ic]['created_by']=$customer_val->created_by;
				$data['customer_list'][$ic]['created_date_time']=$customer_val->createdDateTime;
				$data['customer_list'][$ic]['approval_comments']=$customer_val->approval_comments;
				
					
				// Get Sales, Bill & Ship to Party details
				$sbs_list = $this->db->query("SELECT * FROM customer_address_sold_bill_ship WHERE customer_id = ".$customer_val->customer_id)->result();
				if(count($sbs_list) > 0){
					$x = 0;
					foreach($sbs_list as $record){
						$data['customer_list'][$ic][$record->type.'_to_party'][] = $record;
					}
				}
        
				if(count($customer_user_list)>0){
					$jc=0;
					foreach($customer_user_list as $customer_user_val){
						$data['customer_list'][$ic]['user_details'][$jc]["customer_user_id"]=$customer_user_val->customer_user_id;
						$data['customer_list'][$ic]['user_details'][$jc]["user_name"]=$customer_user_val->name;
						$jc++;
					}
				}else{
					$data['customer_list'][$ic]['user_details'] = array();
				}	
				
				$data['remarks'] = $customer_val->remarks;
					
				$ic++; 
			}
		}else{
			$data['customer_list'] = array();
		}
		
		return $data;
			
	}else if($type == "sales_call"){
    
			$sales_call_list = $this->db->query("select a.*,a.created_date_time as createdDateTime,b.name,U.name as 'Assigned_To_Name', SCTT.sales_calls_temp_id from sales_call a left join users b on (a.Owner = b.user_id) left join users U on (a.Assigned_to = U.user_id) left join sales_call_temp_table SCTT on (a.sales_call_id = SCTT.sales_call_id) where a.sales_call_id = '".$id."'")->result();
			
			$is = 0;
			foreach($sales_call_list as $sc_list){
				$data['sales_call_list'][$is]['sales_call_id'] = $sc_list->sales_call_id;
				$data['sales_call_list'][$is]['releted_to'] = $sc_list->releted_to;
				$data['sales_call_list'][$is]['sales_call_customer_contact_type'] = $sc_list->sales_call_customer_contact_type;
				$data['sales_call_list'][$is]['id'] = $sc_list->id;
				$data['sales_call_list'][$is]['Company'] = $sc_list->Company;
				$data['sales_call_list'][$is]['releted_to_new_contact_customer'] = $sc_list->releted_to_new_contact_customer;
				$data['sales_call_list'][$is]['new_contact_customer_person_name'] = $sc_list->new_contact_customer_person_name;
				$data['sales_call_list'][$is]['new_contact_customer_company_name'] = $sc_list->new_contact_customer_company_name;
				$data['sales_call_list'][$is]['new_contact_customer_other_person_name'] = $sc_list->new_contact_customer_other_person_name;
				$data['sales_call_list'][$is]['Call_Date'] = $sc_list->Call_Date;
				$data['sales_call_list'][$is]['Phone'] = $sc_list->Phone;
				$data['sales_call_list'][$is]['Call_Type'] = $sc_list->Call_Type;
				$data['sales_call_list'][$is]['Priority'] = $sc_list->Priority;
				$data['sales_call_list'][$is]['call_report'] = $sc_list->call_report;
				$data['sales_call_list'][$is]['MinutesOfMeeting'] = $sc_list->MinutesOfMeeting;
				$data['sales_call_list'][$is]['CommentsByManager'] = $sc_list->CommentsByManager;
				$data['sales_call_list'][$is]['NextVisitDate'] = $sc_list->NextVisitDate;
				$data['sales_call_list'][$is]['Status'] = $sc_list->Status;
				$data['sales_call_list'][$is]['sales_calls_temp_id'] = $sc_list->sales_calls_temp_id;
				$data['sales_call_list'][$is]['Owner'] = $sc_list->Owner;
				$data['sales_call_list'][$is]['Owner_name'] = $sc_list->name;
				$data['sales_call_list'][$is]['tracking_id'] = $sc_list->tracking_id;
				$data['sales_call_list'][$is]['lat_lon_val'] = $sc_list->lat_lon_val;
				$data['sales_call_list'][$is]['geo_status'] = $sc_list->geo_status;
				$data['sales_call_list'][$is]['Assigned_To_id'] = $sc_list->Assigned_To;
				$data['sales_call_list'][$is]['Assigned_To'] = $sc_list->Assigned_To_Name;
				$data['sales_call_list'][$is]['Call_Date'] = $sc_list->Call_Date;
				$data['sales_call_list'][$is]['created_date_time'] = $sc_list->createdDateTime;
				
				$is++;
			}
		return $data;
    }else if($type == "opportunitie"){	

		$opportunities_list_val = $this->db->query("select a.*, a.remarks as Opp_remarks, a.created_date_time as createdDateTime, c.name as OwnerName, L.leads_id, L.lead_size_class_of_project from opportunities a left join customers b on (b.customer_id = a.Company) inner join leads L on (L.lead_number = a.Leadno) inner join users c on (c.user_id = a.OpportunityOwner) where a.opportunity_id = '".$id."'")->result();
		
		if(count($opportunities_list_val)>0){
			$i=0;
            foreach($opportunities_list_val as $opp_list){
				
				$main_contact = $this->db->query("SELECT FirstName, LastName from contacts WHERE contact_id = ".$opp_list->opportunity_main_contact_id)->row();	

				$data['opportunities_list'][$i]['opportunity_id'] = $opp_list->opportunity_id;
				$data['opportunities_list'][$i]['opp_id'] = $opp_list->opp_id;
				$data['opportunities_list'][$i]['OwnerName'] = $opp_list->OwnerName;
				$data['opportunities_list'][$i]['leads_id'] = $opp_list->leads_id;
				$data['opportunities_list'][$i]['Leadno'] = $opp_list->Leadno;
				$data['opportunities_list'][$i]['Company'] = $opp_list->Company;
				$data['opportunities_list'][$i]['Company_Text'] = $opp_list->Company_text;
				$data['opportunities_list'][$i]['sampling'] = $opp_list->sampling;
				$data['opportunities_list'][$i]['mockup'] = $opp_list->mockup;
				$data['opportunities_list'][$i]['Rating'] = $opp_list->Rating;
				$data['opportunities_list'][$i]['project_name'] = $opp_list->project_name;
				$data['opportunities_list'][$i]['project_type'] = $opp_list->project_type;
				$data['opportunities_list'][$i]['size_calss_unit'] = $opp_list->size_calss_unit;
				$data['opportunities_list'][$i]['size_class_project'] = $opp_list->size_class_project;
				$data['opportunities_list'][$i]['lead_class_of_project'] = $opp_list->lead_class_of_project;
				$data['opportunities_list'][$i]['lead_size_class_of_project'] = $opp_list->lead_size_class_of_project;
				$data['opportunities_list'][$i]['size_calss_unit_no_of_blocks'] = $opp_list->size_calss_unit_no_of_blocks;
				$data['opportunities_list'][$i]['size_calss_unit_no_of_floor_per_block'] = $opp_list->size_calss_unit_no_of_floor_per_block;
				$data['opportunities_list'][$i]['no_of_flats'] = $opp_list->no_of_flats;
				$data['opportunities_list'][$i]['cubic_meters'] = $opp_list->cubic_meters;
				$data['opportunities_list'][$i]['sft'] = $opp_list->sft;
				$data['opportunities_list'][$i]['remarks'] = $opp_list->Opp_remarks;
				$data['opportunities_list'][$i]['Finalizationdate'] = $opp_list->Finalizationdate;
				$data['opportunities_list'][$i]['requirement_details_collected'] = $opp_list->requirement_details_collected;
				
				// Billing Details
				$data['opportunities_list'][$i]['status_project'] = $opp_list->status_project;
				$data['opportunities_list'][$i]['BillingStreet1'] = $opp_list->BillingStreet1;
				$data['opportunities_list'][$i]['BillingStreet2'] = $opp_list->Billingstreet2;
				$data['opportunities_list'][$i]['BillingCountry'] = $opp_list->BillingCountry;
				$data['opportunities_list'][$i]['BillingState'] = $opp_list->BillingState;
				$data['opportunities_list'][$i]['BillingCity'] = $opp_list->BillingCity;
				$data['opportunities_list'][$i]['BillingZipPostal'] = $opp_list->BillingZipPostal;
				$data['opportunities_list'][$i]['BillingArea'] = $opp_list->BillingArea;
				$data['opportunities_list'][$i]['BillingPlotno'] = $opp_list->BillingPlotno;
				$data['opportunities_list'][$i]['BillingWebsite'] = $opp_list->BillingWebsite;
				$data['opportunities_list'][$i]['BillingEmail'] = $opp_list->BillingEmail;
				$data['opportunities_list'][$i]['BillingPhone'] = $opp_list->BillingPhone;
				
				// Shipping Details
				$data['opportunities_list'][$i]['ShippingStreet1'] = $opp_list->ShippingStreet1;
				$data['opportunities_list'][$i]['Shippingstreet2'] = $opp_list->Shippingstreet2;
				$data['opportunities_list'][$i]['ShippingLandmark'] = $opp_list->ShippingLandmark;
				$data['opportunities_list'][$i]['Shippingplotno'] = $opp_list->Shippingplotno;
				$data['opportunities_list'][$i]['ShippingCountry'] = $opp_list->ShippingCountry;
				$data['opportunities_list'][$i]['ShippingStateProvince'] = $opp_list->ShippingStateProvince;
				$data['opportunities_list'][$i]['ShippingCity'] = $opp_list->ShippingCity;
				$data['opportunities_list'][$i]['ShippingZipPostal'] = $opp_list->ShippingZipPostal;
				
				$data['opportunities_list'][$i]['opportunity_main_contact_id'] = $opp_list->opportunity_main_contact_id;
				$data['opportunities_list'][$i]['opportunity_main_contact_name'] = $main_contact->FirstName." ".$main_contact->LastName;
				$data['opportunities_list'][$i]['opportunity_main_contact_designation'] = $opp_list->opportunity_main_contact_designation;
				$data['opportunities_list'][$i]['opportunity_main_contact_email'] = $opp_list->opportunity_main_contact_email;
				$data['opportunities_list'][$i]['opportunity_main_contact_mobile'] = $opp_list->opportunity_main_contact_mobile;
				$data['opportunities_list'][$i]['opportunity_main_contact_category'] = $opp_list->opportunity_main_contact_category;
				$data['opportunities_list'][$i]['opportunity_main_contact_phone'] = $opp_list->opportunity_main_contact_phone;
				$data['opportunities_list'][$i]['opportunity_main_contact_company'] = $opp_list->opportunity_main_contact_company;
				
				$data['opportunities_list'][$i]['business_status'] = $opp_list->business_status;
				$data['opportunities_list'][$i]['business_status_delayed_value'] = $opp_list->business_status_delayed_value;
				$data['opportunities_list'][$i]['business_status_pending_value'] = $opp_list->business_status_pending_value;
				$data['opportunities_list'][$i]['business_status_lost_value'] = $opp_list->business_status_lost_value;
				$data['opportunities_list'][$i]['business_status_lost_other_value'] = $opp_list->business_status_lost_other_value; 
					
				$data['opportunities_list'][$i]['created_date_time'] = $opp_list->createdDateTime;
				
				$Associate_contact_id = $opp_list->opportunity_main_contact_id;
				if($Associate_contact_id != "" || $Associate_contact_id != NULL){
					$contact_list_a = $this->db->query("select OAC.opportunity_associate_contacts_id, OAC.contact_id, OAC.designation, C.FirstName, C.LastName from opportunity_associate_contacts OAC inner join contacts C on (OAC.contact_id = C.contact_id) where opportunity = ".$opp_list->opportunity_id)->result();
					$c=0;
					foreach($contact_list_a as $assoc_val){
						$data['opportunities_list'][$i]['associate_contact'][$c]["opportunity_associate_contacts_id"] = $assoc_val->opportunity_associate_contacts_id;
						$data['opportunities_list'][$i]['associate_contact'][$c]["contact_id"] = $assoc_val->contact_id;
						$data['opportunities_list'][$i]['associate_contact'][$c]["contact_name"] = $assoc_val->FirstName." ".$assoc_val->LastName;
						$data['opportunities_list'][$i]['associate_contact'][$c]["designation"] = $assoc_val->designation;
						$c++;
					}
				}else{
					$data['opportunities_list'][$i]['associate_contact'] = array();
				}

              $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$opp_list->customer_id."'")->row();               

			  $product_opportunitie_list = $this->db->query("select * from product_opportunities a inner join product_master b on (a.Product = b.product_code) where a.Opportunity ='".$opp_list->opportunity_id."' group by b.product_code")->result();
              if(count($product_opportunitie_list) >0){
                $j=0;
                foreach($product_opportunitie_list as $popp_list){
					$data['opportunities_list'][$i]['final_product'][$j]['Product_opportunities_id'] = $popp_list->Product_opportunities_id;
					$data['opportunities_list'][$i]['final_product'][$j]['product_id'] = $popp_list->Product;
					$data['opportunities_list'][$i]['final_product'][$j]['product_name'] = $popp_list->product_name;
					$data['opportunities_list'][$i]['final_product'][$j]['probability'] = $popp_list->Probability;
					$data['opportunities_list'][$i]['final_product'][$j]['quantity'] = $popp_list->Quantity;
					$data['opportunities_list'][$i]['final_product'][$j]['rate_per_sft'] = $popp_list->final_product_price;
					$data['opportunities_list'][$i]['final_product'][$j]['value'] = $popp_list->final_product_value;
					$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_from'] = date("d-m-Y",strtotime($popp_list->schedule_date_from));
					//$data['opportunities_list'][$i]['final_product'][$j]['schedule_date_upto'] = date("d-m-Y",strtotime($popp_list->schedule_date_upto));
					$j++;
                }
              }else{
                $data['opportunities_list'][$i]['final_product'] = array();
              }

              $brand_producta_list = $this->db->query("select * from Products_Brands_targeted_opp where Opportunity =".$opp_list->opportunity_id)->result();
              if(count($brand_producta_list)>0){
                $k=0;
                  foreach($brand_producta_list as $brand_product_val){
                  $data['opportunities_list'][$i]['brands_product'][$k]['brands_opp_id'] = $brand_product_val->brands_opp_id;
                  $data['opportunities_list'][$i]['brands_product'][$k]['product'] = $brand_product_val->Brands_Product;
                  $data['opportunities_list'][$i]['brands_product'][$k]['units'] = $brand_product_val->Brands_Units;
                  $data['opportunities_list'][$i]['brands_product'][$k]['quantity'] = $brand_product_val->Brands_Quantity;
                  $data['opportunities_list'][$i]['brands_product'][$k]['price'] = $brand_product_val->Brands_Price;
                  //$data['opportunities_list'][$i]['brand_product_list'][$k]['Opportunity'] = $brand_product_val->Opportunity;
                  $k++;
                 }
              }else{
                $data['opportunities_list'][$i]['brands_product'] = array();
              }

			// Opportunity Associative Contact List
			/*
			$opportunity_associative_contacts_list = $this->db->query("SELECT * FROM opportunity_associate_contacts O LEFT JOIN contacts C ON O.contact_id = C.contact_id WHERE Opportunity = ".$opp_list->opportunity_id)->result();
			if(count($opportunity_associative_contacts_list) > 0){
				$oac = 0;
				foreach($opportunity_associative_contacts_list as $assContact){
					$data['opportunities_list'][$i]['opportunity_associative_contact'][$oac] = $assContact;
					$oac++;
				}				
			}
			*/

              $Competition_targeted_list = $this->db->query("select * from Competition_targeted_opp where Opportunity = ".$opp_list->opportunity_id)->result();
              if(count($Competition_targeted_list) >0){
                $l=0;
                foreach($Competition_targeted_list as $competition_val){
                 $data['opportunities_list'][$i]['competition_product'][$l]['competitions_opp_id'] = $competition_val->competitions_opp_id;                 
                 $data['opportunities_list'][$i]['competition_product'][$l]['product'] = $competition_val->Competition_Product;
                 $data['opportunities_list'][$i]['competition_product'][$l]['units'] = $competition_val->Competition_Units;                 
                 $data['opportunities_list'][$i]['competition_product'][$l]['price'] = $competition_val->Competition_Price;
                 $l++;
                }
              }else{
                  $data['opportunities_list'][$i]['competition_product'] = array();
              }
			  
			  $remarks_list = $this->db->query("select * from opportunities_remarks where opportunity_id = ".$opp_list->opportunity_id)->result();
			  if(count($remarks_list) >0){
					$l = 0;
					foreach($remarks_list as $remark){
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_id'] = $remark->remark_id;     						
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark'] = $remark->remark;
						$data['opportunities_list'][$i]['opp_remarks'][$l]['remark_date'] = $remark->remark_date;             
						$l++;
					}
              }else{
                  $data['opportunities_list'][$i]['opp_remarks'] = [];
              }

              $i++;

            }
          }else{
            $data['opportunities_list'] = array();
          }

          return $data;

    }else if($type == "Complaints"){
    	$complaint_list=$this->db->query("select *, a.created_date_time as createdDateTime from Complaints a inner join users b on (a.ComplaintOwner = b.user_id) where a.Complaint_id = '".$id."'")->result();
    	if(count($complaint_list)>0){
	      $i=0;
	      foreach ($complaint_list as $value) {
	      $complanints_ass_recc_list = $this->db->query("select * from complaints_aeeigment_recommendation_tbl where Complaint_id = '".$value->Complaint_id."'")->result();
		  
			// Check if this Complaint was raised by a sales call
			$res = $this->Generic_model->getSingleRecord('sales_call_temp_table',array('Complaint_id' => $id));
			if($count($res) > 0){
				$data["complaint_list"][$i]['sales_calls_temp_id'] = $res->sales_calls_temp_id;
			}

	        $data['complaint_list'][$i]['sales_assessment'] =array();
	        $data['complaint_list'][$i]['sales_recommendedsolution'] = array();
	        $data['complaint_list'][$i]['area_assessment'] = array();
	        $data['complaint_list'][$i]['area_recommendedsolution'] = array();
	        $data['complaint_list'][$i]['regional_assessment'] =array();
	        $data['complaint_list'][$i]['regional_recommendedsolution'] = array();
	        $data['complaint_list'][$i]['national_assessment'] = array();
	        $data['complaint_list'][$i]['national_recommendedsolution'] = array();
	        $data['complaint_list'][$i]['quality_assessment'] = array();
	        $data['complaint_list'][$i]['quality_recommendation'] = array();
	        $data['complaint_list'][$i]['manufacturing_assessment'] = array();
	        $data['complaint_list'][$i]['management_assessment'] = array();
	        $data['complaint_list'][$i]['management_recommendation'] = array();
	        $ik=$j=$k=$l=$m=$n=$o=$p=$q=$r=$s=$oi=$pi=0;
	        foreach($complanints_ass_recc_list as $comp_ass_recc_val){
	          $profile_id = $comp_ass_recc_val->profile_id;
	          $type = $comp_ass_recc_val->type;
	          $complent_recomendation = $comp_ass_recc_val->complent_recomendation;
	          $com_ass_rec_id = $comp_ass_recc_val->complaints_aeeigment_recommendation_id;
	         // $profile_id = $this->session->userdata('logged_in')['profile_id'];
	          if($profile_id == SALESOFFICER && $type == "assessment" || $profile_id == SalesExecutive && $type == "assessment"){
	            $data['complaint_list'][$i]['sales_assessment'][$ik]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['sales_assessment'][$ik]['complement_ass_name'] = $complent_recomendation;
	            $ik++;
	          }else if($profile_id == SALESOFFICER && $type == "recommendation" || $profile_id == SalesExecutive && $type == "recommendation" ){
	            $data['complaint_list'][$i]['sales_recommendedsolution'][$j]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['sales_recommendedsolution'][$j]['complement_ass_name'] = $complent_recomendation;
	            $j++;
	          }else if($profile_id == AreaManager && $type == "assessment"){
	            $data['complaint_list'][$i]['area_assessment'][$k]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['area_assessment'][$k]['complement_ass_name'] = $complent_recomendation;
	            $k++;
	          }else if($profile_id == AreaManager && $type == "recommendation" ){
	            $data['complaint_list'][$i]['area_recommendedsolution'][$l]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['area_recommendedsolution'][$l]['complement_ass_name'] = $complent_recomendation;
	            $l++;
	          }else if($profile_id == Regionalmanager && $type == "assessment"){
	            $data['complaint_list'][$i]['regional_assessment'][$m]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['regional_assessment'][$m]['complement_ass_name'] = $complent_recomendation;
	            $m++;
	          }else if($profile_id == Regionalmanager && $type == "recommendation" ){
	            $data['complaint_list'][$i]['regional_recommendedsolution'][$n]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['regional_recommendedsolution'][$n]['complement_ass_name'] = $complent_recomendation;
	            $n++;
	          }else if($profile_id == NationalHead && $type == "assessment"){
	            $data['complaint_list'][$i]['national_assessment'][$o]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['national_assessment'][$o]['complement_ass_name'] = $complent_recomendation;
	            $o++;
	          }else if($profile_id == NationalHead && $type == "recommendation" ){
	            $data['complaint_list'][$i]['national_recommendedsolution'][$p]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['national_recommendedsolution'][$p]['complement_ass_name'] = $complent_recomendation;
	            $p++;
	          }else if($profile_id == QualityDepartment && $type == "assessment"){
	            $data['complaint_list'][$i]['quality_assessment'][$oi]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['quality_assessment'][$oi]['complement_ass_name'] = $complent_recomendation;
	            $oi++;
	          }else if($profile_id == QualityDepartment && $type == "recommendation" ){
	            $data['complaint_list'][$i]['quality_recommendation'][$pi]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['quality_recommendation'][$pi]['complement_ass_name'] = $complent_recomendation;
	            $pi++;
	          }else if($profile_id == Manufacturing && $type == "assessment"){
	            $data['complaint_list'][$i]['manufacturing_assessment'][$q]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['manufacturing_assessment'][$q]['complement_ass_name'] = $complent_recomendation;
	            $q++;
	          }else if($profile_id == SUPERADMIN && $type == "assessment"){
	            $data['complaint_list'][$i]['management_assessment'][$r]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['management_assessment'][$r]['complement_ass_name'] = $complent_recomendation;
	            $r++;
	          }else if($profile_id == SUPERADMIN && $type == "recommendation" ){
	            $data['complaint_list'][$i]['management_recommendation'][$s]['com_ass_rec_id'] = $com_ass_rec_id;
	            $data['complaint_list'][$i]['management_recommendation'][$s]['complement_ass_name'] = $complent_recomendation;
	            $s++;
	          }

	        }
	       

	        $data['complaint_list'][$i]['complaint_id']=$value->Complaint_id;
	        $customer_list = $this->db->query("select * from customers where customer_id ='".$value->CustomerName."'")->row();
	         if(($customer_list) >0){
	          $data['complaint_list'][$i]['customer_id']=$customer_list->customer_id;
	          $data['complaint_list'][$i]['CustomerName']=$customer_list->CustomerName;
	        }else{
	          $data['complaint_list'][$i]['customer_id']='';
	          $data['complaint_list'][$i]['CustomerName']='';
	        }

	        $data['complaint_list'][$i]['ComplaintNumber'] = $value->ComplaintNumber;
	        $data['complaint_list'][$i]['salesorderdate']=$value->salesorderdate;

	        $data['complaint_list'][$i]['salesordernumber'] = $value->salesordernumber;
	        $data['complaint_list'][$i]['feedback']=$value->feedback;
	        $data['complaint_list'][$i]['applicationdate']=$value->applicationdate;

	        $data['complaint_list'][$i]['feedbackother']=$value->feedbackother;
	        $data['complaint_list'][$i]['invoicedate']=$value->invoicedate;

	        $data['complaint_list'][$i]['invoicenumber']=$value->invoicenumber;
	        $data['complaint_list'][$i]['batchnumber']=$value->batchnumber;
	        $data['complaint_list'][$i]['defectivesample']=$value->defectivesample;
	        $data['complaint_list'][$i]['sampleplantlab']=$value->sampleplantlab;

	       /* $data['complaint_list'][$i]['sitevisit']=$value->sitevisit;
	        $data['complaint_list'][$i]['otherassessment']=$value->otherassessment;
	        $data['complaint_list'][$i]['otherrecommendedsolution']=$value->otherrecommendedsolution;*/
	         $data['complaint_list'][$i]['sales_sitevisit']=$value->sales_sitevisit;
	        //$data['complaint_list'][$i]['sales_assessment']=$value->sales_assessment;
	        //$data['complaint_list'][$i]['sales_recommendedsolution']=$value->sales_recommendedsolution;
	        $data['complaint_list'][$i]['sales_status']=$value->sales_status;

	         $data['complaint_list'][$i]['area_sitevisit']=$value->area_sitevisit;
	        //$data['complaint_list'][$i]['area_assessment']=$value->area_assessment;
	        //$data['complaint_list'][$i]['area_recommendedsolution']=$value->area_recommendedsolution;
	        $data['complaint_list'][$i]['area_status']=$value->area_status;

	         $data['complaint_list'][$i]['regional_sitevisit']=$value->regional_sitevisit;
	        //$data['complaint_list'][$i]['regional_assessment']=$value->regional_assessment;
	        //$data['complaint_list'][$i]['regional_recommendedsolution']=$value->regional_recommendedsolution;
	        $data['complaint_list'][$i]['regional_status']=$value->regional_status;

	         $data['complaint_list'][$i]['national_sitevisit']=$value->national_sitevisit;
	        //$data['complaint_list'][$i]['national_assessment']=$value->national_assessment;
	        //$data['complaint_list'][$i]['national_recommendedsolution']=$value->national_recommendedsolution;
	         $data['complaint_list'][$i]['national_status']=$value->national_status;

	         $data['complaint_list'][$i]['credit_note_given']=$value->credit_note_given;
	        $data['complaint_list'][$i]['material_replaced']=$value->material_replaced;
	        $data['complaint_list'][$i]['comercial_remarks']=$value->comercial_remarks;


	        $data['complaint_list'][$i]['qualitytestsdone']=$value->qualitytestsdone;
			$data['complaint_list'][$i]['created_date_time']=$value->createdDateTime;
	        //$data['complaint_list'][$i]['qualityassessment']=$value->qualityassessment;
	        //$data['complaint_list'][$i]['qualityrecommendation']=$value->qualityrecommendation;
	        //$data['complaint_list'][$i]['manufacturingassessment']=$value->manufacturingassessment;
	        //$data['complaint_list'][$i]['managementassessment']=$value->managementassessment;
	        //$data['complaint_list'][$i]['managementRecommendation']=$value->managementRecommendation;
	        /*$data['complaint_list'][$i]['Status']=$value->Status;
	        $data['complaint_list'][$i]['Type']=$value->Type;*/
	       
	        $i++;
	      }
	  	}

       return $data;
    }else if($type == "Quotation"){
      $qutation_list = $this->db->query("select * from Quotation where Quotation_id ='".$id."'")->result();
        if(count($qutation_list) >0){
          $iQ=0;
           foreach($qutation_list as $qutation_val){
            $customer_details = $this->db->query("select * from customers where customer_id ='".$qutation_val->Customer."'")->row();
            $contact_list = $this->db->query("select * from contacts where contact_id ='".$qutation_val->Contact."'")->row();
            $data["qutation_list"][$iQ]['Quotation_id'] = $qutation_val->Quotation_id;
            $data["qutation_list"][$iQ]['QuotationversionID'] = $qutation_val->QuotationversionID;
            $data["qutation_list"][$iQ]['Opportunity'] = $qutation_val->Opportunity;
            $data["qutation_list"][$iQ]['QuotationDate'] = date("Y-m-d",strtotime($qutation_val->QuotationDate));
            $data["qutation_list"][$iQ]['ExpiryDate'] = date("Y-m-d",strtotime($qutation_val->ExpiryDate));
            $data["qutation_list"][$iQ]['Customer'] = $customer_details->CustomerName;
             $data["qutation_list"][$iQ]['Customer_id'] = $qutation_val->Customer;
             if(count($contact_list)>0){
              $data["qutation_list"][$iQ]['Contact_id'] = $contact_list->contact_id;
              $data["qutation_list"][$iQ]['Contact'] = $contact_list->FirstName." ".$contact_list->LastName;
             }else{
              $data["qutation_list"][$iQ]['Contact_id'] = "";
              $data["qutation_list"][$iQ]['Contact'] = "";
             }
             
            $data["qutation_list"][$iQ]['BillingStreet1'] = $qutation_val->BillingStreet1;
            $data["qutation_list"][$iQ]['Billingstreet2'] = $qutation_val->Billingstreet2;
            $data["qutation_list"][$iQ]['BillingCountry'] = $qutation_val->BillingCountry;
            $data["qutation_list"][$iQ]['StateProvince'] = $qutation_val->StateProvince;
            $data["qutation_list"][$iQ]['BillingCity'] = $qutation_val->BillingCity;
            $data["qutation_list"][$iQ]['BillingZipPostal'] = $qutation_val->BillingZipPostal;
            $data["qutation_list"][$iQ]['ShippingStreet1'] = $qutation_val->ShippingStreet1;
            $data["qutation_list"][$iQ]['Shippingstreet2'] = $qutation_val->Shippingstreet2;
            $data["qutation_list"][$iQ]['ShippingCountry'] = $qutation_val->ShippingCountry;
            $data["qutation_list"][$iQ]['ShippingStateProvince'] = $qutation_val->ShippingStateProvince;
            $data["qutation_list"][$iQ]['ShippingCity'] = $qutation_val->ShippingCity;
            $data["qutation_list"][$iQ]['ShippingZipPostal'] = $qutation_val->ShippingZipPostal;
            $data["qutation_list"][$iQ]['TotalPrice'] = $qutation_val->TotalPrice;
            $data["qutation_list"][$iQ]['Remarks'] = $qutation_val->Remarks;
			$data["qutation_list"][$iQ]['created_date_time'] = $qutation_val->createdDateTime;          

            $checking_price_list = $this->db->query("select * from customer_price_list where customer_id ='".$qutation_val->Customer."'")->row();
            $qutation_product = $this->db->query("select * from Quotation_Product a inner join product_master b on (a.Product = b.product_code) inner join Price_list_line_Item c on (c.product = b.product_id) where a.Quotation_id = '".$data["qutation_list"][$iQ]['Quotation_id']."' and c.Price_list_id ='".$checking_price_list->price_list_id."'")->result(); 

            //$qutation_product = $this->db->query("select * from Quotation_Product a inner join  product_master b on (a.Product = b.product_id) where  Quotation_id = '".$qutation_val->Quotation_id."'")->result();
            $jQ=0;
            foreach($qutation_product as $qpp_list){
              $product_master_list = $this->db->query("select * from product_master where product_id =".$qpp_list->Product)->row();
              $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quotation_Product_id'] = $qpp_list->Quotation_Product_id;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['ListPrice'] = $qpp_list->ListPrice;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product'] = $qpp_list->product_name;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Product_id'] = $qpp_list->Product;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Quantity'] = $qpp_list->Quantity;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Subtotal'] = $qpp_list->Subtotal;
             $data["qutation_list"][$iQ]['qutation_product_list'][$jQ]['Discount'] = $qpp_list->Discount;
             $jQ++;
            }
            $iQ++;
          }
        }else{
          $data["qutation_list"] = array();
        }
        return $data;


    }else if($type == "tada"){
       $data['tada_list'] = $this->db->query("select * from  ta_da_allowances a inner join users b on (a.Name = b.user_id) where  a.ta_da_id = '".$id."'")->result();

       return($data);
    }else if($type == "expenses"){
      $expenses_list = $this->db->query("select * from expenses  where expenses_id = '".$id."'")->result();
      $i=0;
      foreach($expenses_list as $values){
        $data['expenses_list'][$i]['expenses_id'] = $values->expenses_id;
        $data['expenses_list'][$i]['expenses_number'] = $values->expenses_number;
        $data['expenses_list'][$i]['expenses_type'] = $values->expenses_type;
        $data['expenses_list'][$i]['expenses_name'] = $values->expenses_name;
        $data['expenses_list'][$i]['price'] = $values->price;
        $data['expenses_list'][$i]['expensesdate'] = $values->expensesdate;
        $i++;
      }

      return $data;
    }else if($type == "contract"){
      $contract_list = $this->db->query("select *,a.Description, a.created_date_time as createdDateTime from contract a inner join customers b on (a.Customer = b.customer_id)  where a.contract_id ='".$id."'")->result();
          if(count($contract_list) >0){
            $i_c=0;
            foreach($contract_list as $contract_val){
              $CompanySignedBy_list = $this->db->query("select * from  users where user_id =".$contract_val->CompanySignedBy)->row();

              
              $ContractOwner_list = $this->db->query("select * from users where user_id =".$contract_val->ContractOwner." AND status = 'Active'")->row();
              if(count($CompanySignedBy_list)>0){
                $CompanySignedBy = $CompanySignedBy_list->name;
                $CompanySignedBy_id = $CompanySignedBy_list->user_id;
               
              }else{
                $CompanySignedBy = "";
                  $CompanySignedBy_id  = "";
              }
              if(count($ContractOwner_list)>0){
                $ContractOwner = $ContractOwner_list->name;
              }else{
                $ContractOwner ="";
              }

              $data['contract_list'][$i_c]['contract_id'] =$contract_val->contract_id;
              $data['contract_list'][$i_c]['Customer'] =$contract_val->CustomerName;
              $data['contract_list'][$i_c]['customer_id'] =$contract_val->customer_id;
              $data['contract_list'][$i_c]['ActivatedBy'] =$contract_val->ActivatedBy;
              $data['contract_list'][$i_c]['ActivatedDate'] =date("d-m-Y",strtotime($contract_val->ActivatedDate));
              $data['contract_list'][$i_c]['BillingAddress'] =$contract_val->BillingAddress;
              $data['contract_list'][$i_c]['ShippingAddress'] =$contract_val->ShippingAddress;
              if($contract_val->CustomerSignedBy == ""||$contract_val->CustomerSignedBy == null ){
              $data['contract_list'][$i_c]['CustomerSignedBy'] ="";
              $data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
              }else{
                $contact_list =  $this->db->query("select * from contacts where contact_id =".$contract_val->CustomerSignedBy)->row();
                if(count($contact_list) >0){
                  $data['contract_list'][$i_c]['CustomerSignedBy'] =$contact_list->FirstName." ".$contact_list->LastName;
                  $data['contract_list'][$i_c]['CustomerSignedBy_id'] =$contact_list->contact_id;
                }else{
                  $data['contract_list'][$i_c]['CustomerSignedBy'] ="";
                  $data['contract_list'][$i_c]['CustomerSignedBy_id'] =" ";
                }

              }
              $data['contract_list'][$i_c]['CompanySignedDate'] =date("d-m-Y",strtotime($contract_val->CompanySignedDate));
              $data['contract_list'][$i_c]['ContractName'] =$contract_val->ContractName;
              $data['contract_list'][$i_c]['ContractNumber'] =$contract_val->ContractNumber;
              $data['contract_list'][$i_c]['ContractStartDate'] =date("d-m-Y",strtotime($contract_val->ContractStartDate));
              $data['contract_list'][$i_c]['ContractEndDate'] =date("d-m-Y",strtotime($contract_val->ContractEndDate));
              $data['contract_list'][$i_c]['ContractOwner'] =$ContractOwner;
              $data['contract_list'][$i_c]['ContractTerm'] =$contract_val->ContractTerm;
              $data['contract_list'][$i_c]['CustomerSignedBy'] =$contract_val->FirstName." ".$contract_val->LastName;
              $data['contract_list'][$i_c]['CompanySignedBy_id'] = $CompanySignedBy_id;
              $data['contract_list'][$i_c]['CompanySignedBy'] = $CompanySignedBy;
              $data['contract_list'][$i_c]['CustomerSignedDate'] =date("d-m-Y",strtotime($contract_val->CustomerSignedDate));
              $data['contract_list'][$i_c]['Description'] =$contract_val->Description;
              $data['contract_list'][$i_c]['total_amount'] =$contract_val->total_amount;
              $data['contract_list'][$i_c]['OwnerExpirationNotice'] =$contract_val->OwnerExpirationNotice;
              $data['contract_list'][$i_c]['SpecialTerms'] =$contract_val->SpecialTerms;
              $data['contract_list'][$i_c]['Status'] =$contract_val->Status;
			  $data['contract_list'][$i_c]['created_date_time'] =$contract_val->createdDateTime;

              $contract_product_list = $this->db->query("select * from contract_products a inner join product_master b on (a.Product = b.product_id) where a.Contract =".$contract_val->contract_id)->result();
              if(count($contract_product_list)>0){
                $j_c=0;
                foreach($contract_product_list as $cp_list){
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['product_contract_id'] = $cp_list->product_contract_id;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['Product'] = $cp_list->product_name;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['Product_id'] = $cp_list->Product;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['ListPrice'] = $cp_list->ListPrice;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['Quantity'] = $cp_list->Quantity;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['Discount'] = $cp_list->Discount;
                  $data['contract_list'][$i_c]['contract_product'][$j_c]['Subtotal'] = $cp_list->Subtotal;
                  $j++;
                }
              }else{
                $data['contract_list'][$i_c]['contract_product'] = array();
              }

              $i_c++;
            }
        }else{
          $data['contract_list'] =array();
        }

        return $data;
    }else if($type == "salesorder"){		
		//$sales_order_list = $this->db->query("select *, a.created_date_time as createdDateTime, a.remarks as SalesOrderRemarks, c.customerName as DeliveredCustomerName from sales_order a inner join customers b on (a.Customer = b.customer_id) inner join customers c on (a.DeliveredBy_customer_id = c.customer_id) where a.sales_order_id ='".$id."'")->result();
		
		$sales_order_list = $this->db->query("select a.*,b.*,c.CustomerName as delivered_by_customer_name, d.division_name, a.created_date_time as CreatedDateTime, a.remarks as SalesOrderRemarks, u.name as OwnerName from sales_order a inner join customers b on (a.Customer = b.customer_id) left join customers c on (a.DeliveredBy_customer_id = c.customer_id) left join division_master d on (a.Division = d.division_master_id) left join users u on (a.created_by = u.user_id) where a.sales_order_id ='".$id."' and a.archieve !=1")->result();
		
		if(count($sales_order_list) >0){
			$i_s=0;
			foreach($sales_order_list as $so_list){
				
				// Check if this sales order was raised by a sales call
				$recRes = $this->Generic_model->getSingleRecord('sales_call_temp_table',array('sales_order_id' => $id));				
				if(count($recRes) > 0){
					$data["sales_order_list"][$i_s]['sales_calls_temp_id'] = $recRes->sales_calls_temp_id;
				}					
				
				$Soldtopartycode = $so_list->Soldtopartycode;
				$Soldtopartycode = $so_list->Soldtopartycode;
				if($Soldtopartycode == "" || $Soldtopartycode == NULL){
					$Soldtopartycode_val = "";
				}else{
					$Soldtopartycode_list = $this->db->query("select * from contacts where contact_id =".$Soldtopartycode)->row();
					$Soldtopartycode_val= $Soldtopartycode_list->FirstName." ".$Soldtopartycode_list->LastName;
				}
				
				$Shiptopartycode = $so_list->Shiptopartycode;
				if($Shiptopartycode == "" || $Shiptopartycode == NULL){
					$Shiptopartycode_val = "";
				}else{
					$Shiptopartycode_list = $this->db->query("select * from contacts where contact_id =".$Shiptopartycode)->row();
					$Shiptopartycode_val= $Shiptopartycode_list->FirstName." ".$Shiptopartycode_list->LastName;
				}
				$BilltopartyCode = $so_list->BilltopartyCode;
				if($BilltopartyCode == "" || $BilltopartyCode == NULL){
					$BilltopartyCode_val = "";
				}else{
					$BilltopartyCode_list = $this->db->query("select * from contacts where contact_id =".$BilltopartyCode)->row();
					$BilltopartyCode_val= $BilltopartyCode_list->FirstName." ".$BilltopartyCode_list->LastName;
				}

				$data["sales_order_list"][$i_s]['sales_order_id'] = $so_list->sales_order_id;
				$data["sales_order_list"][$i_s]['sales_order_number'] = $so_list->sales_order_number;
				$data["sales_order_list"][$i_s]['OwnerName'] = $so_list->OwnerName;
				$data["sales_order_list"][$i_s]['contracts_id'] = $so_list->contract_id;
				$data["sales_order_list"][$i_s]['Customer'] = $so_list->CustomerName;
				$data["sales_order_list"][$i_s]['customer_id'] = $so_list->Customer;				
				$data["sales_order_list"][$i_s]['customer_number'] = $so_list->customer_number;
				$data["sales_order_list"][$i_s]['OrderType'] = $so_list->OrderType;
				$data["sales_order_list"][$i_s]['sales_order_dealer_contact_id'] = $so_list->sales_order_dealer_contact_id;
				$data["sales_order_list"][$i_s]['orc_details'] = $so_list->orc_details;
				$data["sales_order_list"][$i_s]['OrderType_form'] = $so_list->OrderType_form;
				$data["sales_order_list"][$i_s]['SalesOrganisation'] = $so_list->SalesOrganisation;
				$data["sales_order_list"][$i_s]['DistributionChannel'] = $so_list->DistributionChannel;
				$data["sales_order_list"][$i_s]['date_of_delivery'] = $so_list->date_of_delivery;
				$data["sales_order_list"][$i_s]['delivered_by'] = $so_list->DeliveredBy;
				$data["sales_order_list"][$i_s]['delivered_by_customer_id'] = $so_list->DeliveredBy_customer_id;
				$data["sales_order_list"][$i_s]['delivered_by_customer_name'] = $so_list->delivered_by_customer_name;
				$data["sales_order_list"][$i_s]['Division'] = $so_list->Division;
				$data["sales_order_list"][$i_s]['division_name'] = $so_list->division_name;
				$data["sales_order_list"][$i_s]['Remarks'] = $so_list->SalesOrderRemarks;
				$data["sales_order_list"][$i_s]['Soldtopartycode'] = $Soldtopartycode_val;
				$data["sales_order_list"][$i_s]['Soldtopartycode_id'] = $so_list->Soldtopartycode;
				$data["sales_order_list"][$i_s]['Shiptopartycode'] = $Shiptopartycode_val;
				$data["sales_order_list"][$i_s]['Shiptopartycode_id'] = $so_list->Shiptopartycode;
				$data["sales_order_list"][$i_s]['BilltopartyCode'] = $BilltopartyCode_val;
				$data["sales_order_list"][$i_s]['BilltopartyCode_id'] = $so_list->BilltopartyCode;
				$data["sales_order_list"][$i_s]['expected_order_dispatch_date'] = $so_list->expected_order_dispatch_date;
				$data["sales_order_list"][$i_s]['Ponumber'] = $so_list->Ponumber;
				$data["sales_order_list"][$i_s]['CashDiscount'] = $so_list->CashDiscount;
				$data["sales_order_list"][$i_s]['SchemeDiscount'] = $so_list->SchemeDiscount;
				$data["sales_order_list"][$i_s]['QuntityDiscount'] = $so_list->QuntityDiscount;
				$data["sales_order_list"][$i_s]['withoutdiscountamount'] = $so_list->withoutdiscountamount;             
				$data["sales_order_list"][$i_s]['Freight'] = $so_list->Freight;
				$data['sales_order_list'][$i_s]['freight_amount'] = $so_list->freight_amount;
				$data["sales_order_list"][$i_s]['discountAmount'] = $so_list->discountAmount;
				$data["sales_order_list"][$i_s]['Total'] = $so_list->Total;
				$data["sales_order_list"][$i_s]['order_status'] = $so_list->order_status;
				$data["sales_order_list"][$i_s]['order_status_comments'] = $so_list->order_status_comments;
				$data["sales_order_list"][$i_s]['created_date_time'] = $so_list->createdDateTime;
				
				// Uploaded images
				$imgPath = "/images/SalesOrder/salesorder_images/";
				
				if($so_list->purchase_image != '' || $so_list->purchase_image != NULL){
					$imgs = explode(",",$so_list->purchase_image);
					$i = 0;
					foreach($imgs as $img){
						if($i != 0){
							$data["sales_order_list"][$i_s]['purchase_image'] .= ", ".$imgPath.$img;
						}else{
							$data["sales_order_list"][$i_s]['purchase_image'] = $imgPath.$img;
						}
						$i++;
					}
				}else{
					$data["sales_order_list"][$i_s]['purchase_image'] = '';
				}
				
				if($so_list->complaints_image != '' || $so_list->complaints_image != NULL){
					$imgs = explode(",",$so_list->complaints_image);
					$i = 0;
					foreach($imgs as $img){
						if($i != 0){
							$data["sales_order_list"][$i_s]['complaints_image'] .= ", ".$imgPath.$img;
						}else{
							$data["sales_order_list"][$i_s]['complaints_image'] = $imgPath.$img;
						}
						$i++;
					}
				}else{
					$data["sales_order_list"][$i_s]['complaints_image'] = '';
				}
				
				if($so_list->payment_image != '' || $so_list->payment_image != NULL){
					$imgs = explode(",",$so_list->payment_image);
					$i = 0;
					foreach($imgs as $img){
						if($i != 0){
							$data["sales_order_list"][$i_s]['payment_image'] .= ", ".$imgPath.$img;
						}else{
							$data["sales_order_list"][$i_s]['payment_image'] = $imgPath.$img;
						}
						$i++;
					}
				}else{
					$data["sales_order_list"][$i_s]['payment_image'] = '';
				}
				
				if($so_list->transfer_image != '' || $so_list->transfer_image != NULL){
					$imgs = explode(",",$so_list->transfer_image);
					$i = 0;
					foreach($imgs as $img){
						if($i != 0){
							$data["sales_order_list"][$i_s]['transfer_image'] .= ", ".$imgPath.$img;
						}else{
							$data["sales_order_list"][$i_s]['transfer_image'] = $imgPath.$img;
						}
						$i++;
					}
				}else{
					$data["sales_order_list"][$i_s]['transfer_image'] = '';
				}
				
				$sales_order_product = $this->db->query("select * from sales_order_products a inner join product_master b on (a.Product = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
				
				if(count($sales_order_product) >0){
					$j_s=0;
					foreach($sales_order_product as $sop_list){
						$plant_list = $this->db->query("select * from plant_master where plantid = '".$sop_list->plant_id."'")->row();
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['sales_order_products_id'] = $sop_list->sales_order_products_id;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['saleslineItemId'] = $sop_list->sales_order_products_id;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product'] = $sop_list->product_name;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Product_id'] = $sop_list->Product;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['product_code'] = $sop_list->product_code;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['ListPrice'] = $sop_list->ListPrice;
						$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_id'] = $sop_list->plant_id;
						$data['sales_order_list'][$i_s]['sales_order_product_list'][$j_s]['plant_name'] = $plant_list->plantName;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Quantity'] = $sop_list->Quantity;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['kgs'] = $sop_list->kgs;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['mt'] = $sop_list->mt;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Discount'] = $sop_list->Discount;
						$data["sales_order_list"][$i_s]['sales_order_product_list'][$j_s]['Subtotal'] = $sop_list->Subtotal;
						$j_s++;
					}
				}else{
					$data["sales_order_list"][$i_s]['sales_order_product_list'] = array();
				}
				
				// Get Sales Persons Third Party Product List if the Customer type is Third Party
				if($so_list->CustomerType == 'Third party Customer'){
					// Get Sales Persons Product List
					$tpProducts = $this->db->query("select * from tp_sales_order_sales_person_distributors a inner join product_master b on (a.product_id = b.product_id) where a.sales_order_id =".$so_list->sales_order_id)->result();
					
					if(count($tpProducts) >0){
						$cntr=0;
						foreach($tpProducts as $tpp_list){								
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['tp_sales_order_sales_person_distributors_id'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['saleslineItemId'] = $tpp_list->tp_sales_order_sales_person_distributors_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product'] = $tpp_list->product_name;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_id'] = $tpp_list->product_id;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['product_code'] = $tpp_list->product_code;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['plan_quantity'] = $tpp_list->plan_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['ordered_quantity'] = $tpp_list->ordered_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_quantity'] = $tpp_list->supplied_quantity;
							$data["sales_order_list"][$i_s]['salesPersonsProducts'][$cntr]['supplied_date'] = $tpp_list->supplied_date;
							$cntr++;
						}
					}else{
						$data["sales_order_list"][$i_s]['salesPersonsProducts'] = array();
					}
				}
				
				$i_s++;
			}
		}else{
			$data["sales_order_list"] = array();
		}
		return $data;
	}else if($type == "payment_collection"){

		$payment_collection_list = $this->db->query("select * from payment_collection where payment_collection_id = '".$id."' and archieve != '1'")->result();
		if(count($payment_collection_list)>0){
			$pay_i= "0";
			foreach($payment_collection_list as $payment_val){
				
				$divisions = explode(",",$payment_val->Division);
					
				if(count($divisions) > 0){
					$x = 0;
					foreach($divisions as $division){
						$division_list = $this->db->query("select division_master_id, division_name from division_master where division_master_id = '".$division."'")->row();
						
						if($x > 0){
							$data['payment_collection_list'][$pay_i]['Division'] .= ", ";
						}
						$data['payment_collection_list'][$pay_i]['Division'] .= $division_list->division_name;
						$x++;
					}
				}else{
					$data['payment_collection_list'][$pay_i]['Division'] = $payment_val->Division;
				}		
				
				// Check if this payment Collection was raised by a sales call
				$recRes = $this->Generic_model->getSingleRecord('sales_call_temp_table',array('payment_collection_id' => $id));
				if(count($recRes) > 0){
					$data["payment_collection_list"][$pay_i]['sales_calls_temp_id'] = $recRes->sales_calls_temp_id;
				}
				
				$customer_list = $this->db->query("select * from customers where customer_id ='".$payment_val->customer_id."'")->row();
				$contact_list = $this->db->query("select * from contacts where contact_id = '".$payment_val->contact_id."'")->row();
				$userInfo = $this->db->query("select user_id, name from users where user_id = '".$payment_val->created_by."'")->row();
				
				$data['payment_collection_list'][$pay_i]['payment_collection_id'] = $payment_val->payment_collection_id;
				$data['payment_collection_list'][$pay_i]['customer_name'] = $customer_list->CustomerName;
				$data['payment_collection_list'][$pay_i]['customer_id'] = $payment_val->customer_id;
				$data['payment_collection_list'][$pay_i]['contact_id'] = $payment_val->contact_id;
				
				$data['payment_collection_list'][$pay_i]['contact_name'] = $contact_list->FirstName." ".$contact_list->LastName;
				$data['payment_collection_list'][$pay_i]['payment_mode'] = $payment_val->payment_mode;
				
				$data['payment_collection_list'][$pay_i]['customer_location'] = $payment_val->customer_location;
				$data['payment_collection_list'][$pay_i]['CustomerSAPCode'] = $customer_list->CustomerSAPCode;
				$data['payment_collection_list'][$pay_i]['comments_by_commercial_team'] = $payment_val->comments_by_commercial_team;
				$data['payment_collection_list'][$pay_i]['sales_owner_id'] = $userInfo->user_id;
				$data['payment_collection_list'][$pay_i]['sales_owner_name'] = $userInfo->name;
				
				$data['payment_collection_list'][$pay_i]['created_date_time'] = date("Y-m-d",strtotime($payment_val->created_date_time));
				
				if($payment_val->payment_image != '' || $payment_val->payment_image != NULL){
					$data['payment_collection_list'][$pay_i]['payment_image'] = "/images/Payment/".$payment_val->payment_image;
				}else{
					$data['payment_collection_list'][$pay_i]['payment_image'] = NULL;
				}
				
				if($payment_val->payment_mode == "Cash"){
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
				}else if($payment_val->payment_mode == "Cheque"){
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['cheque_no'] = $payment_val->cheque_no;
					$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
					$data['payment_collection_list'][$pay_i]['cheque_date'] = date("Y-m-d",strtotime($payment_val->cheque_date));					
					$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
					$data['payment_collection_list'][$pay_i]['status'] = $payment_val->status;
				}else if($payment_val->payment_mode == "Online"){
					$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
					$data['payment_collection_list'][$pay_i]['transfer_type'] = $payment_val->transfer_type;
					$data['payment_collection_list'][$pay_i]['transaction_ref_no'] = $payment_val->transaction_ref_no;
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['payment_date'] = $payment_val->payment_date;  
				}        
				$data['payment_collection_list'][$pay_i]['comments_by_commercial_team'] = $payment_val->comments_by_commercial_team;
				$pay_i++;
			}
		}else{
			$data["payment_collection_list"] = array();
		}
		return $data;
    }
}

	/**
	* Function lead_associate_contact_delete will delete a record from lead_associate_contacts
	* w.r.to lead_associate_contact_id
	*/
	public function lead_associate_contact_delete($parameters, $method, $user_id){
		$lead_associate_contact_id = $parameters['lead_associate_contact_id'];
		
		$this->db->delete('lead_associate_contacts', array('lead_associate_contact_id' => $lead_associate_contact_id));
		
		if($this->db->affected_rows()){
			$this->response(array('code'=>'200','message'=>'Deleted successfully','requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Record not found'), 200);
		}
	}
	

	/**
	* Function payment_collection_insert will create record of new payment collection
	* w.r.to payment_mode Cash/Cheque/Online
	*/
	public function payment_collection_insert($parameters,$method,$user_id)
	{
		
		$paymentMode = $parameters['payment_mode'];
		
		if($paymentMode == "Cash" || $paymentMode == "Cheque" || $paymentMode == "Online") {
			// common details
			$parameters['owner'] = $user_id;
			$parameters['created_by'] = $user_id;
			$parameters['modified_by'] = $user_id;
			$parameters['created_date_time'] = date("Y-m-d H:i:s");
			$parameters['modified_date_time'] = date("Y-m-d H:i:s");
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
		
		/*
		$param['customer_id'] = $parameters['customer_id'];
		$param['payment_mode'] = $parameters['payment_mode'];
		
		if($param['payment_mode'] == "Cash"){
			$param['amount'] = $parameters['amount'];
			$param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
		}else if($param['payment_mode'] == "Cheque"){
			$param['bank_name'] = $parameters['bank_name'];
			$param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
			$param['cheque_no'] = $parameters['cheque_no'];
			$param['amount'] = $parameters['amount'];
			$param['status'] = $parameters['status'];
		}else if($param['payment_mode'] == "Online"){
			$param['bank_name'] = $parameters['bank_name'];
			$param['transfer_type'] = $parameters['transfer_type'];
			$param['transaction_ref_no'] = $parameters['transaction_ref_no'];
			$param['amount'] = $parameters['amount'];
			$param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
		*/		
		$payment_collection_id = $this->Generic_model->insertDataReturnId("payment_collection",$parameters);
		$return_data = $this->all_tables_records_view("payment_collection",$payment_collection_id);
		
		if($payment_collection_id != "" || $payment_collection_id != null){
			$check_update_list = $this->db->query("select * from update_table where module_id ='".$payment_collection_id."' and module_name ='Payment_Collection'")->row();
			if(count($check_update_list)>0){
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $payment_collection_id,'module_name'=>'Payment_Collection'));
			}else{
				$latest_val['module_id'] = $payment_collection_id;
				$latest_val['module_name'] = "Payment_Collection";
				$latest_val['user_id'] = $user_id;
				$latest_val['created_date_time'] = date("Y-m-d H:i:s");
				$this->Generic_model->insertData("update_table",$latest_val);
			}
			$this->response(array('code'=>'200','message' => 'Payment Successfull inserted','result'=>$return_data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
	}
	
	
	
  public function payment_collection_edit($parameters,$method,$user_id){
    $payment_collection_id = $parameters['payment_collection_id'];
    $param['customer_id'] = $parameters['customer_id'];
   // $param['contact_id'] = $parameters['contact_id'];
   
    $param['payment_mode'] = $parameters['payment_mode'];
	$param['Division'] = $parameters['Division']; 
    $param['invoice_number'] = $parameters['invoice_number']; 
    if($param['payment_mode'] == "Cash"){
        $param['amount'] = $parameters['amount'];
        $param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
    }else if($param['payment_mode'] == "Cheque"){
        $param['bank_name'] = $parameters['bank_name'];
        $param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
        $param['cheque_no'] = $parameters['cheque_no'];
        $param['amount'] = $parameters['amount'];
        $param['status'] = $parameters['status'];
    }else if($param['payment_mode'] == "Online"){
      $param['bank_name'] = $parameters['bank_name'];
      $param['transfer_type'] = $parameters['transfer_type'];
      $param['transaction_ref_no'] = $parameters['transaction_ref_no'];
      $param['amount'] = $parameters['amount'];
      $param['payment_date'] = date("Y-m-d" , strtotime($parameters['payment_date']));
    }else{
      $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }
    $param['created_by'] = $user_id;
    $param['modified_by'] = $user_id;
    $param['created_date_time'] = date("Y-m-d H:i:s");
    $param['modified_date_time'] = date("Y-m-d H:i:s");
    $result=$this->Generic_model->updateData('payment_collection',$param,array('payment_collection_id'=>$payment_collection_id));
    //$payment_collection_id = $this->Generic_model->insertDataReturnId("payment_collection",$param);
    $return_data = $this->all_tables_records_view("payment_collection",$payment_collection_id);
    if($result == "1"){
            $check_update_list = $this->db->query("select * from update_table where module_id ='".$payment_collection_id."' and module_name ='Payment_Collection'")->row();
              if(count($check_update_list)>0){
                $latest_val['user_id'] = $user_id;
                $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                $ok = $this->Generic_model->updateData('update_table', $latest_val, array('module_id' => $payment_collection_id,'module_name'=>'Payment_Collection'));
              }else{
                $latest_val['module_id'] = $payment_collection_id;
                $latest_val['module_name'] = "Payment_Collection";
                $latest_val['user_id'] = $user_id;
                $latest_val['created_date_time'] = date("Y-m-d H:i:s");
                $this->Generic_model->insertData("update_table",$latest_val);
              }


       $this->response(array('code'=>'200','message' => 'Payment Successfull updated','result'=>$return_data,'requestname'=>$method));
    }else{
       $this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
    }

  }

	public function payment_collection_list($parameters,$method,$user_id){
		$final_users_id = $parameters['team_id'];
		$payment_collection_list = $this->db->query("select * from payment_collection where owner in (".$final_users_id.") and archieve != '1'")->result();
        if(count($payment_collection_list)>0){
			$pay_i= "0";
			foreach($payment_collection_list as $payment_val){
				
				$divisions = explode(",",$payment_val->Division);
					
				if(count($divisions) > 0){
					$x = 0;
					foreach($divisions as $division){
						$division_list = $this->db->query("select division_master_id, division_name from division_master where division_master_id = '".$division."'")->row();
						
						if($x > 0){
							$data['payment_collection_list'][$pay_i]['Division'] .= ", ";
						}
						$data['payment_collection_list'][$pay_i]['Division'] .= $division_list->division_name;
						$x++;
					}
				}else{
					$data['payment_collection_list'][$pay_i]['Division'] = $payment_val->Division;
				}			
				
				$customer_list = $this->db->query("select * from customers where customer_id ='".$payment_val->customer_id."'")->row();				
				$contact_list = $this->db->query("select * from contacts where contact_id = '".$payment_val->contact_id."'")->row();
				$userInfo = $this->db->query("select user_id, name from users where user_id = '".$payment_val->created_by."'")->row();
				
				$data['payment_collection_list'][$pay_i]['payment_collection_id'] = $payment_val->payment_collection_id;
				$data['payment_collection_list'][$pay_i]['customer_name'] = $customer_list->CustomerName;
				$data['payment_collection_list'][$pay_i]['customer_location'] = $payment_val->customer_location;
				$data['payment_collection_list'][$pay_i]['CustomerSAPCode'] = $customer_list->CustomerSAPCode;
				$data['payment_collection_list'][$pay_i]['customer_id'] = $payment_val->customer_id;
				$data['payment_collection_list'][$pay_i]['contact_id'] = $payment_val->contact_id;
				
				$data['payment_collection_list'][$pay_i]['contact_name'] = $contact_list->FirstName." ".$contact_list->LastName;
				$data['payment_collection_list'][$pay_i]['invoice_number'] = $payment_val->invoice_number;
				$data['payment_collection_list'][$pay_i]['payment_mode'] = $payment_val->payment_mode;
				$data['payment_collection_list'][$pay_i]['comments_by_commercial_team'] = $payment_val->comments_by_commercial_team;
				$data['payment_collection_list'][$pay_i]['sales_owner_id'] = $userInfo->user_id;
				$data['payment_collection_list'][$pay_i]['sales_owner_name'] = $userInfo->name;
				$data['payment_collection_list'][$pay_i]['created_date_time'] = date("Y-m-d H:i:s",strtotime($payment_val->created_date_time));

				if($payment_val->payment_mode == "Cash"){
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
				}else if($payment_val->payment_mode == "Cheque"){
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['cheque_no'] = $payment_val->cheque_no;
					$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['cheque_date'] = date("Y-m-d",strtotime($payment_val->cheque_date));
					$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
					$data['payment_collection_list'][$pay_i]['status'] = $payment_val->status;      
				}else if($payment_val->payment_mode == "Online"){
					$data['payment_collection_list'][$pay_i]['bank_name'] = $payment_val->bank_name;
					$data['payment_collection_list'][$pay_i]['transfer_type'] = $payment_val->transfer_type;
					$data['payment_collection_list'][$pay_i]['transaction_ref_no'] = $payment_val->transaction_ref_no;
					$data['payment_collection_list'][$pay_i]['amount'] = $payment_val->amount;
					$data['payment_collection_list'][$pay_i]['payment_date'] = date("Y-m-d",strtotime($payment_val->payment_date));
				}        
				$pay_i++;
			}
			
			$this->response(array('code'=>'200','message' => 'Payment Collection List','result'=>$data,'requestname'=>$method));
		}else{
			$data['payment_collection_list'] = array();       
			$this->response(array('code'=>'200','message' => 'Payment Collection List','result'=>$data,'requestname'=>$method));
		}
	}
	
  public function app_current_version($parameter, $method, $user_id)

  {

    

    $app = $this->db->query("Select * from app_version")->row();
      $records['app_id'] = $app->app_id;

      $records['app_version_id'] = $app->app_version_id;

      $records['app_version_name'] = $app->app_version_name;      

      $result = array('code' => '200', 'message' => 'success', 'result' => $records, 'requestname' => $method);

            $this->response($result);

  }

}