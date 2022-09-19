<?php
error_reporting(1);
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'libraries/REST_Controller.php');

class RestApi extends REST_Controller{
		
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
			$customer_id = $fdata->requesterid;
				
			// Multipart API Call End*/	
		}else{
		
			$entityBody = file_get_contents('php://input');                        
			$data = json_decode($entityBody,TRUE);
			$parameters = $data['requestparameters'];
			$method = $data['requestname'];	
			$customer_id = $data['requesterid'];
			$this->$method($parameters,$method,$customer_id);
		}
	
	}
		
		
	/*
	** Below Function login_details() will authenticate the user trying to login
	** Author: Uday Kanth Rapalli
	** Dated: 19 May 2022 : 1800 Hrs
	*/
	public function customer_login($parameters,$method,$customer_id){
		
		$username = $parameters['username'];
		$password = base64_encode($parameters['password']);
		
		$customer = $this->db->query("SELECT * FROM bf_customer WHERE (email='".$username."' AND password = '".$password."') OR (mobile='".$username."' AND password = '".$password."') AND archive = 0")->row();
		
		if(count($customer) > 0){// if user exists
		
			// Get Customer Addresses
			$customerAddress = $this->db->query("SELECT block_phase, flat_no FROM bf_customer_address WHERE customer_id = ".$customer->customer_id." LIMIT 1")->row();

			if($customer->status > 0){

				$device_type = $parameters['device_type'];
				
				if($device_type == "iOS"){
					$param_list['fcmId_iOS'] = $parameters['fcmId'];
					$param_list['deviceid_iOS'] = $parameters['deviceid'];
					$param_list['modified_at'] = date("Y-m-d H:i:s");
					$this->Generic_model->updateData('bf_customer',$param_list,array('customer_id'=>$customer->customer_id));
				}else if($device_type == "ANDROID"){
					$param_list['fcmId_android'] = $parameters['fcmId'];
					$param_list['deviceid_android'] = $parameters['deviceid'];
					$param_list['modified_at'] = date("Y-m-d H:i:s");
					$this->Generic_model->updateData('bf_customer',$param_list,array('customer_id'=>$customer->customer_id));
				}			

				$data['customer_id'] = $customer->customer_id;
				$data['first_name'] = $customer->first_name;
				$data['last_name'] = $customer->last_name;
				$data['email'] = $customer->email;
				$data['mobile'] = $customer->mobile;
				$data['block_phase'] = $customerAddress->block_phase;
				$data['flat_no'] = $customerAddress->flat_no;
				
				// Insert customer log information
				$logData['customer_id'] = $customer->customer_id;
				$logData['login_date_time'] = date("Y-m-d H:i:s");

				$customer_log_id = $this->Generic_model->insertDataReturnId('bf_customer_login_history',$logData);
				
				$data['customer_log_id'] = $customer_log_id;
				$this->response(array('code'=>'200','message' => 'Customer exist, approved & successfully loged in ','result'=>$data,'requestname'=>$method));
			}else{
				$this->response(array('code'=>'404','message' => 'Customer exist, but not yet approved'), 200);
			}	
		}else{
			$this->response(array('code'=>'404','message' => 'Username or Password mismatch. Try again!'), 200);
		}
	}
	
	/**
	* Function master_list will retrieve data depending on the table_name specified
	*/
	public function master_list($parameters,$method,$customer_id){
		
		$table_name = $parameters['table_name'];
		
		// Get Community Master Records
		if($table_name == 'community'){
			// Get Community list
			$community_list = $this->db->query("select * from bf_community WHERE status = 1 AND archive = 0 ORDER BY community_name ASC")->result();
			
			$i=0;
			foreach($community_list as $community){
				$data['community_list'][$i]['community_id']=$community->community_id;
				$data['community_list'][$i]['community_name']=ucwords($community->community_name);
				$i++;
			}
		}
		
		// Get Categories Master Records
		if($table_name == 'categories'){
			// Get Category list
			$categories = $this->db->query("SELECT category_id, category_name, image_path, description, parent_category_id FROM bf_categories WHERE status = 1")->result();
			
			if(count($categories) > 0){
				$i = 0;
				foreach($categories as $category){
					$data['Category_list'][$i]['category_id']=$category->category_id;
					$data['Category_list'][$i]['category_name']=ucwords($category->category_name);
					$data['Category_list'][$i]['image_path']=$category->image_path;
					$data['Category_list'][$i]['description']=$category->description;
					$data['Category_list'][$i]['parent_category_id']=$category->parent_category_id;
					$i++;
				}
			}			
		}
		
		// Get Product Master Records
		// Params
		// table_name, category_id
		if($table_name == 'products'){
			
			// Get fcmId
			$fcmId = $this->db->query("select fcmId_android from bf_customer where customer_id = ".$customer_id)->row();
			
			$push_noti['content_type'] = "Testing phase content";
			$push_noti['user_id'] = $customer_id;
			$push_noti['fcmId_android'] = $fcmId->fcmId_android;
			$push_noti['subject'] = "A Notification for a test phase is successfully pushed...";
			
			$this->PushNotifications->test_notifications($push_noti);
			
			if(!isset($parameters['category_id'])){
				$category_id = '';
			}else{
				$category_id = $parameters['category_id'];
			}
			
			if($category_id != 'all' || $category_id != null || $category_id != ''){
				// Get product list of all category
				$products = $this->db->query("SELECT P.product_id, P.product_name, P.product_code, P.category_id, C.category_name, P.short_description, P.long_description, P.sku_code, P.base_quantity,  U.uom_id, U.uom, PS.qty_in_store FROM bf_products P LEFT JOIN bf_uom U ON (P.uom_id = U.uom_id) INNER JOIN bf_categories C ON (P.category_id = C.category_id) INNER JOIN bf_product_stock PS ON (P.product_id = PS.product_id) WHERE P.category_id = ".$category_id." AND P.status = 1")->result();
			}else{
				// Get product list of particular category
				$products = $this->db->query("SELECT P.product_id, P.product_name, P.product_code, P.category_id, C.category_name, P.short_description, P.long_description, P.sku_code, P.base_quantity,  U.uom_id, U.uom, PS.qty_in_store FROM bf_products P LEFT JOIN bf_uom U ON (P.uom_id = U.uom_id) INNER JOIN bf_categories C ON (P.category_id = C.category_id) INNER JOIN bf_product_stock PS ON (P.product_id = PS.product_id) WHERE P.status = 1")->result();				
			}
			
			if(count($products) > 0){
				$i = 0;
				foreach($products as $product){
					
					// Get requested customer blocked qty if the item is blocked
					$blocked_qty = $this->db->query("SELECT block_qty FROM bf_block_product WHERE customer_id = ".$customer_id." AND product_id = ".$product->product_id)->row();
					
					if(count($blocked_qty) > 0){
						$product->qty_in_store = strval($product->qty_in_store + $blocked_qty->block_qty); 
					}else{
						$product->qty_in_store = strval($product->qty_in_store);
					}
					
					$data['product_list'][$i] = $product;
					
					// Get Product image from product_images db
					$image = $this->db->query("SELECT image_path FROM bf_product_images WHERE product_id = ".$product->product_id." LIMIT 1")->row();
					$data['product_list'][$i]->image = "http://54.179.95.174/bfarmz/uploads/".$image->image_path;
								
					// Get Product price for the product
					$price = $this->db->query("SELECT product_price_id, price, discount, discount_start_date, discount_end_date, discount_type FROM bf_product_price WHERE product_id = ".$product->product_id." AND (end_date IS NULL OR end_date = '0000-00-00')")->row();
					
					if(count($price) > 0){
						$data['product_list'][$i] -> product_price_id = $price -> product_price_id;
						$data['product_list'][$i] -> price = $price -> price;
						$data['product_list'][$i] -> discount = $price -> discount;
						$data['product_list'][$i] -> discount_type = $price -> discount_type;					
						$data['product_list'][$i] -> discount_start_date = $price -> discount_start_date;
						$data['product_list'][$i] -> discount_end_date = $price -> discount_end_date;
						
						if($price->discount != NULL){						
							if($price->discount_type == "%"){
								$discounted_price = ($price->price) - (($price->price * $price->discount)/100);
							}else{
								$discounted_price = ($price->price - $price->discount);
							}
							$discounted_price = round($discounted_price);
						}
						
						$data['product_list'][$i] -> price_after_discount = $discounted_price;
						$data['product_list'][$i] -> total_discount = round($price -> price - $discounted_price);
					}else{
						$data['product_list'][$i] -> product_price_id = null;
						$data['product_list'][$i] -> price = 0;
						$data['product_list'][$i] -> discount = 0;
						$data['product_list'][$i] -> discount_type = null;					
						$data['product_list'][$i] -> discount_start_date = null;
						$data['product_list'][$i] -> discount_end_date = null;
						$data['product_list'][$i] -> price_after_discount = 0;	
						$data['product_list'][$i] -> total_discount = 0;
					}
					$i++;
				}
			}else{
				$data['product_list'] = [];
			}
		}
		
		// Get Categories Master Records
		if($table_name == 'category_product'){
			// Get Category list
			$categories = $this->db->query("SELECT category_id, category_name, image_path, description, parent_category_id FROM bf_categories WHERE status = 1")->result();
			
			if(count($categories) > 0){
				$i = 0;
				foreach($categories as $category){
					$data['Category_list'][$i]['category_id'] = $category->category_id;
					$data['Category_list'][$i]['category_name'] = $category->category_name;
					$data['Category_list'][$i]['image_path'] = "http://54.179.95.174/bfarmz/uploads/".$category->image_path;
					$data['Category_list'][$i]['description'] = $category->description;
					$data['Category_list'][$i]['parent_category_id'] = $category->parent_category_id;
					
					// Get Product list
					$products = $this->db->query("SELECT P.product_id, P.product_name, P.product_code, P.category_id, C.category_name, P.short_description, P.long_description, P.sku_code, U.uom_id, U.uom FROM bf_products P LEFT JOIN bf_uom U ON (P.uom_id = U.uom_id) INNER JOIN bf_categories C ON (P.category_id = C.category_id) WHERE P.category_id = ".$category->category_id." AND P.status = 1")->result();
					
					if(count($products) > 0){
						$j = 0;
						foreach($products as $product){
							$data['Category_list'][$i]['Product_list'][$j]['product_id'] = $product->product_id;
							$data['Category_list'][$i]['Product_list'][$j]['product_name'] = ucwords($product->product_name);
							$data['Category_list'][$i]['Product_list'][$j]['product_code'] = $product->product_code;
							$data['Category_list'][$i]['Product_list'][$j]['short_description'] = $product->short_description;
							$data['Category_list'][$i]['Product_list'][$j]['long_description'] = $product->long_description;
							$data['Category_list'][$i]['Product_list'][$j]['sku_code'] = $product->sku_code;
							$data['Category_list'][$i]['Product_list'][$j]['uom'] = $product->uom;
							$data['Category_list'][$i]['Product_list'][$j]['category_id'] = $product->category_id;
							$data['Category_list'][$i]['Product_list'][$j]['category_name'] = $product->category_name;					
							
							// Get product image from product_images db
							$image = $this->db->query("SELECT image_path FROM bf_product_images WHERE product_id = ".$product->product_id." LIMIT 1")->row();
							$data['Category_list'][$i]['Product_list'][$j]['image'] = "http://54.179.95.174/bfarmz/uploads/".$image->image_path;

							$j++;
						}
					}else{
						$data['Category_list'][$i]['Product_list'] = [];
					}
					
					$i++;
				}
			}			
		}
		
		$this->response(array('code'=>'200','message'=>'Master List','result'=>$data,'requestname'=>$method));
	}
	
	
	// public function product_list($parameters,$method,$customer_id){
		
		// $category_id = $parameters['category_id'];
		
		// Get product list
		// $products = $this->db->query("SELECT P.product_id, P.product_name, P.product_code, P.category_id, C.category_name, P.short_description, P.long_description, P.sku_code, P.base_quantity,  U.uom_id, U.uom FROM bf_products P LEFT JOIN bf_uom U ON (P.uom_id = U.uom_id) INNER JOIN bf_categories C ON (P.category_id = C.category_id) WHERE P.category_id = ".$category_id." AND P.status = 1")->result();
		
		// if(count($products) > 0){
			// $i = 0;
			// foreach($products as $product){
				
				// $data['product_list'][$i] = $product;
							
				// Get Price for the product
				// $price = $this->db->query("SELECT price, discount, discount_start_date, discount_end_date, discount_type FROM bf_product_price WHERE product_id = ".$product->product_id." AND end_date IS NULL")->row();
				
				// if(count($price) > 0){
					// $data['product_list'][$i] -> price = $price -> price;
					// $data['product_list'][$i] -> discount = $price -> discount;
					// $data['product_list'][$i] -> discount_type = $price -> discount_type;					
					// $data['product_list'][$i] -> discount_start_date = $price -> discount_start_date;
					// $data['product_list'][$i] -> discount_end_date = $price -> discount_end_date;
					
					// if($price->discount != NULL){						
						// if($price->discount_type == "%"){
							// $discounted_price = ($price->price) - (($price->price * $price->discount)/100);
						// }else{
							// $discounted_price = ($price->price - $price->discount);
						// }
					// }
					
					// $data['product_list'][$i] -> price_after_discount = $discounted_price;					
				// }else{
					// $data['product_list'][$i] -> price = null;
					// $data['product_list'][$i] -> discount = null;
					// $data['product_list'][$i] -> discount_type = null;					
					// $data['product_list'][$i] -> discount_start_date = null;
					// $data['product_list'][$i] -> discount_end_date = null;
					// $data['product_list'][$i] -> price_after_discount = null;	
				// }
				// $i++;
			// }
		// }
		
		// $this->response(array('code'=>'200','message'=>'Product List','result'=>$data,'requestname'=>$method));
		
	// }
	
	
	/**
	* Function dropdownlist will retireve data w.r.to the requested params
	*/
	public function dropdown_list($parameters,$method,$customer_id){
		
		// Get Community list
		$community_list = $this->db->query("select * from bf_community WHERE status = 1 AND archive = 0 ORDER BY community_name ASC")->result();
		
		$i=0;
		foreach($community_list as $community){
			$data['community_list'][$i]['community_id']=$community->community_id;
			$data['community_list'][$i]['community_name']=ucwords($community->community_name);
			$i++;
		}
		
		if(count($data)>0){
			$this->response(array('code'=>'200','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'404','message' => 'Authentication Failed'), 200);
		}
				
	}
	
	
	/** Function entity_info will retrieve information Enitty 
	* Entity
	*/
	public function entity_info($entity, $id = NULL){	  
	
		if($entity == "bf_customer"){
			
			if($id == NULL){
				$condition = "";
			}else{
				// Get all customers info
				$condition = " WHERE customer_id = ".$id." ORDER BY first_name ASC";
			}
	
			// Get Customer(s) Info
			$customerInfo = $this->db->query("SELECT * FROM ".$entity.$condition)->result();
			
			if(count($customerInfo) > 0){
				$i = 0;
				foreach($customerInfo as $customer){
					$data['customer'][$i]['customer_id'] = $customer->customer_id;
					$data['customer'][$i]['first_name'] = ucwords($customer->first_name);
					$data['customer'][$i]['last_name'] = ucwords($customer->last_name);
					$data['customer'][$i]['email'] = strtolower($customer->email);
					$data['customer'][$i]['mobile'] = $customer->mobile;
					$data['customer'][$i]['created_at'] = $customer->created_at;
					
					// Get Customer Addresses
					$customerAddressInfo = $this->db->query("SELECT CA.community_id, CA.block_phase, CA.flat_no, C.community_name, C.geo_lat, C.geo_long, C.address, D.district_name, S.state_name FROM bf_customer_address CA INNER JOIN bf_community C ON (CA.community_id = C.community_id) INNER JOIN districts D ON (C.district_id = D.district_id) INNER JOIN states S ON (C.state_id = S.state_id) WHERE customer_id = ".$customer->customer_id)->result();
					
					if(count($customerAddressInfo)>0){
						$j = 0;
						foreach($customerAddressInfo as $address){
							$data['customer'][$i]['address_list'][$j] = $address;
							$j++;
						}
					}
					$i++;			
				}
			}
		
		}
		
		if($entity == "bf_orders"){
			
			if($id == NULL){
				$condition = "";
			}else{
				// Get all orders info
				$condition = " WHERE O.order_id = ".$id;
			}
			
			// Get Order(s) Info
			$orderInfo = $this->db->query("SELECT O.order_id, O.order_number, O.qr_code, O.order_amount, O.customer_id, O.created_at, C.first_name, C.last_name, C.email, C.mobile FROM bf_orders O INNER JOIN bf_customer C ON (O.customer_id = C.customer_id) ".$condition)->result();
			
			// echo "SELECT O.order_id, O.order_number, O.qr_code, O.customer_id, C.first_name, C.last_name, C.email, C.mobile FROM bf_orders O INNER JOIN bf_customer C ON (O.customer_id = C.customer_id) ".$condition;
			// exit;
			
			if(count($orderInfo) > 0){
				$i = 0;
				foreach($orderInfo as $order){
					$data['order'][$i]['order_id'] = $order->order_id;
					$data['order'][$i]['order_number'] = $order->order_number;
					$data['order'][$i]['order_amount'] = $order->order_amount;
					$data['order'][$i]['created_at'] = $order->created_at;
					
					// Get Order line items
					// $orderLineItem = $this->db->query("SELECT O.order_line_item_id , O.product_id, O.quantity, O.product_price_id, O.product_price, O.discount, O.discount_type, O.product_price_after_discount, P.product_name, P.product_code, P.sku_code, PI.image_path FROM bf_order_line_items O INNER JOIN bf_products P ON (O.product_id = P.product_id) INNER JOIN bf_product_images PI ON (P.product_id = PI.product_id) ".$condition)->result();
					
					// if(count($orderLineItem)>0){
						// $j=0;
						// foreach($orderLineItem as $lineItem){
							// print_r($lineItem);
							// $data['order'][$i]['line_items'][$j] = $lineItem;
							// $data['order'][$i]['line_items'][$j]->image_path = "http://54.179.95.174/bfarmz/uploads/".$lineItem->image_path;	
							// $j++;
						// }
					// }
					// $i++;			
				}
			}
			
		}	

		if($entity == "order_confirmation"){
			
			// Get Order(s) Info
			$orderInfo = $this->db->query("SELECT O.order_id, O.order_number, O.qr_code, O.order_amount, O.order_confirmation_amount, O.customer_id, O.created_at, C.first_name, C.last_name, C.email, C.mobile FROM bf_orders O INNER JOIN bf_customer C ON (O.customer_id = C.customer_id) WHERE O.order_id = ".$id)->row();
			
			if(count($orderInfo) > 0){
				
				$data['order']['order_id'] = $id;
				$data['order']['order_number'] = $orderInfo->order_number;
				$data['order']['order_amount'] = $orderInfo->order_amount;
				$data['order']['order_confirmation_amount'] = $orderInfo->order_confirmation_amount;
				$data['order']['created_at'] = $orderInfo->created_at;
				$data['order']['Available'] = [];
				$data['order']['Shortage'] = [];
				$data['order']['Unavailable'] = [];
				
				// Get Order line items
				$orderLineItems = $this->db->query("SELECT O.order_line_item_id , O.product_id, O.quantity, O.quantity_confirmed, O.status, O.product_price_id, O.product_price, O.discount, O.discount_type, O.product_price_after_discount, P.product_name, P.product_code, P.sku_code, PI.image_path, U.uom FROM bf_order_line_items O INNER JOIN bf_products P ON (O.product_id = P.product_id) INNER JOIN bf_product_images PI ON (P.product_id = PI.product_id) INNER JOIN bf_uom U ON (P.uom_id = U.uom_id) where O.order_id = ".$id)->result();
				
				if(count($orderLineItems) > 0){
					
					$j=0; $unavailable=$shortage=0;
					
					foreach($orderLineItems as $lineItem){
						$data['order'][$lineItem->status][$j] = $lineItem; 
						$data['order'][$lineItem->status][$j]->image_path = "http://54.179.95.174/bfarmz/uploads/".$lineItem->image_path;
						
						if($lineItem->status == "Unavailable"){
							$data['order'][$lineItem->status][$j]->message = "Sold out";
							$unavailable++;
						}
						
						if($lineItem->status == "Shortage"){
							$data['order'][$lineItem->status][$j]->message = "Ordered for ".$lineItem->quantity." ".$lineItem->uom.", only ".$lineItem->quantity_confirmed." ".$lineItem->uom." available";
							$shortage++;
						}
						
						if($lineItem->status == "Available"){
							$data['order'][$lineItem->status][$j]->message = "Available";
						}
						
						$j++;
					}
					
					if($unavailable > 0)
					$data['order']['unavailable_block'] = $unavailable." item(s) sold out";
					$data['order']['shortage_block'] = $shortage." item(s) Shortage";
					
					$data['order']['Available'] = array_values($data['order']['Available']);
					$data['order']['Shortage'] = array_values($data['order']['Shortage']);
					$data['order']['Unavailable'] = array_values($data['order']['Unavailable']);
				}
				
			}
			
		}
		
		if($entity == "order_payment_transaction"){
			
			// Get Order Details	
			$orderInfo = $this->db->query("SELECT order_id, order_number, customer_id, order_confirmation_amount, payment_mode, payment_method, payment_status, transaction_number, created_at as `payment_date` FROM bf_orders WHERE order_id = ".$id)->row();
			
			if(count($orderInfo) > 0){
				$data['order_payment_transaction'] = $orderInfo;
				$data['order_payment_transaction']->payment_date = date("d-m-Y", strtotime($orderInfo->payment_date));
				$data['order_payment_transaction']->invoice = "http://54.179.95.174/bfarmz/uploads/invoice/demo_invoice.pdf";
			}
		
		}
	
		return $data;
		
	}
	
	
	/**
	Function customer_add registers a customer by creating a record
	Author : Uday Kanth Rapalli
	Dated: 20 May 2022 : 0930 Hrs
	*/
	Public function customer_add($parameters,$method,$customer_id){
		
		extract($parameters);
		
		// Check for Customer duplicaion
		$check = $this->db->query("SELECT * FROM bf_customer WHERE email = '".strtolower($email)."' OR mobile = '".$mobile."'")->row();
		
		$password = base64_encode($parameters['password']);
		
		if(count($check) == 0){
			// New Customer
			// Create/Register Customer
			if($device_type == "iOS"){
				$param_list['fcmId_iOS'] = $parameters['fcmId'];
				$param_list['deviceid_iOS'] = $parameters['deviceid'];
				$this->Generic_model->updateData('bf_customer',$param_list,array('customer_id'=>$existed_or_not->customer_id));
			}else if($device_type == "ANDROID"){
				$param_list['fcmId_android'] = $parameters['fcmId'];
				$param_list['deviceid_android'] = $parameters['deviceid'];
				$this->Generic_model->updateData('bf_customer',$param_list,array('customer_id'=>$existed_or_not->customer_id));
			}	
			
			$param_list['first_name'] = ucwords($parameters['first_name']);
			$param_list['last_name'] = ucwords($parameters['last_name']);
			$param_list['email'] = strtolower($parameters['email']);
			$param_list['mobile'] = $parameters['mobile'];
			$param_list['password'] = $password;
			$param_list['created_at'] = date("Y-m-d H:i:s");
			
			// Get customer id by inserting the customer
			$customer_id = $this->Generic_model->insertDataReturnId('bf_customer',$param_list);
			
			if($customer_id > 0){
				// Get Community info : Commmunity id, Block, Flat
				$param['customer_id'] = $customer_id;
				$param['community_id'] = $parameters['community_id'];
				$param['block_phase'] = strtoupper($parameters['block_phase']);
				$param['flat_no'] = $parameters['flat_no'];
				$param['created_at'] = date("Y-m-d H:i:s");
				
				// Customer Community Mapping record insert
				$okay = $this->Generic_model->insertData('bf_customer_address',$param);	

				if($okay){
					$return_data = $this->entity_info("bf_customer",$customer_id);
					$this->response(array('code'=>'200','message'=>'Customer created successfully', 'result'=>$return_data, 'requestname'=>$method));
				}else{
					$this->response(array('code'=>'404','message' => 'Customer sign-up failed. Please try again'), 200);
				}					
			}			
		}else{
			$this->response(array('code'=>'404','message' => 'Customer already exist with the given Email/Mobile'), 200);
		}
	}
	
	
	/**
	Function order_add creates a new order with products, quantity, pricing information
	Author: Uday Kanth Rapalli
	Dated: 18 Jun 2022 : 0500 Hrs
	*/
	Public function order_add($parameters,$method,$customer_id){
		
		extract($parameters);
		
		// Gather Complete Order Information
		$orderParam['customer_id'] = $customer_id;
		$orderParam['order_amount'] = $order_amount;
		$orderParam['status'] = "Blocked";
		
		// Create Order and get its order id
		$order_id = $this->Generic_model->insertDataReturnId('bf_orders',$orderParam);
		
		// Update Order number
		$updateOdr['order_number'] = 'ODR'.sprintf('%03d', $order_id);
		$this->Generic_model->updateData('bf_orders',$updateOdr,array('order_id'=>$order_id)); 
		
		// Check for product availability
		$productList = $parameters['order_list'];
		
		if(count($productList) > 0){
			$j = 0;
			
			$orderPrice = $confirmedOrderPrice = 0;
			
			foreach($productList as $product){

				$orderLineItem = $product;
				$orderLineItem['order_id'] = $order_id;
			
				// Get available quantity
				$stockQty = $this->db->query("SELECT qty_in_store FROM bf_product_stock WHERE product_id = ".$product['product_id'])->row();
				
				$qty_in_store = $stockQty->qty_in_store;
				$qty_ordered = $product['quantity'];
				$leftQty = 0;
				
				if($qty_in_store != 0){
					 
					if($qty_ordered <= $qty_in_store){
						$orderLineItem['status'] = "Available";
						$orderLineItem['quantity_confirmed'] = $product['quantity'];
						$leftQty = $qty_in_store - $product['quantity'];
					}else{ // If Qty ordered is greater than qty in store
						$orderLineItem['status'] = "Shortage";
						$orderLineItem['quantity_confirmed'] = $qty_in_store;
						$leftQty = 0;
					}
					
					$confirmedOrderPrice = $confirmedOrderPrice + ($product['product_price_after_discount'] * $orderLineItem['quantity_confirmed']);
					
				}else{
					$orderLineItem['status'] = "Unavailable";
					$orderLineItem['quantity_confirmed'] = $qty_in_store;
					$leftQty = 0;
				}
				
				// Create Order Line Items
				$order_line_item_id = $this->Generic_model->insertDataReturnId('bf_order_line_items',$orderLineItem);				
				
				// Update the product stock
				$stockUpdateData = array(
					"qty_in_store" => $leftQty 
				);
				$this->db->where('product_id', $product['product_id']);
				$this->db->update('bf_product_stock', $stockUpdateData);
				
				// Insert products blocked data into bf_block_product if the quantity_confirmed is not 0
				// Gather data for blocked products
				if($orderLineItem['quantity_confirmed'] > 0){
					$blockProductData['customer_id'] = $customer_id;
					$blockProductData['order_id'] = $order_id;
					$blockProductData['order_line_item_id'] = $order_line_item_id;				
					$blockProductData['product_id'] = $product['product_id'];
					$blockProductData['block_qty'] = $orderLineItem['quantity_confirmed'];
					
					// Create Product Block Record
					$this->Generic_model->insertDataReturnId('bf_block_product',$blockProductData);	
				}
				
				// Calculate Actual Order Price
				$orderPrice = $orderPrice + ($product['product_price_after_discount'] * $orderLineItem['quantity']);
				
				$j++;
			
			}
		}	
		
		// Update Order Amount & confirmed Order Amount
		unset($updateOdr);
		$updateOdr['order_amount'] = $orderPrice;
		$updateOdr['order_confirmation_amount'] = $confirmedOrderPrice;
		$this->Generic_model->updateData('bf_orders',$updateOdr,array('order_id'=>$order_id)); 
		
		// Pull Order complete info
		$return_data = $this->entity_info("order_confirmation",$order_id);
		$this->response(array('code'=>'200','message'=>'Order created successfully', 'result'=>$return_data, 'requestname'=>$method));
		
	}
	
	
	/**
	Function order_edit edits an existing order with products, quantity, pricing information
	Author: Uday Kanth Rapalli
	Dated: 18 Jun 2022 : 0500 Hrs
	*/
	Public function order_edit($parameters,$method,$customer_id){	
		
		extract($parameters);
		
		// Gather Complete Order Information
		$orderParam['order_amount'] = $order_amount;
		
		// Update Order with Ordered Amount
		$updateRes = $this->Generic_model->updateData('bf_orders',$orderParam,array('order_id' => $order_id)); 
		
		// Check for product availability and update accordingly
		$productList = $parameters['order_list'];
		
		if(count($productList) > 0){
			
			// Get all the product id w.r.to the order id			
			$product_ids = $this->db->query("SELECT product_id FROM bf_block_product WHERE order_id = ".$order_id)->result();
			
			foreach($product_ids as $proRec){ // Currently bocked products with respect to order are in productIdData var
				$productIdData[$proRec->product_id] = $proRec->product_id;
			}
			
			$j = 0;
			
			$orderPrice = $confirmedOrderPrice = 0;
			
			foreach($productList as $product){
				
				// If the product id is in the array of productIDData, then remove the product_id from the array
				if(in_array($product['product_id'], $productIdData)){
					unset($productIdData[$product['product_id']]);
				}

				$orderLineItem = $product;
				$orderLineItem['order_id'] = $order_id;
				$block_product_id = 0;
			
				// Get product's available quantity in store
				$stockQty = $this->db->query("SELECT qty_in_store FROM bf_product_stock WHERE product_id = ".$product['product_id'])->row();
				
				// Get blocked product quantity if the product is blocked for the particular order
				$blockedQty = $this->db->query("SELECT block_product_id, block_qty FROM bf_block_product WHERE product_id = ".$product['product_id']." AND order_id = ".$order_id)->row();
				
				// If blocked product qty is existing, quantity in store = original Qty in store + Blocked Quantity
				if(count($blockedQty) > 0){
					$qty_in_store = $stockQty->qty_in_store + $blockedQty->block_qty;
					$block_product_id = $blockedQty->block_product_id;
				}else{
					$qty_in_store = $stockQty->qty_in_store;
				}
				
				$qty_ordered = $product['quantity'];
				$leftQty = 0;
				
				if($qty_in_store != 0){
					 
					if($qty_ordered <= $qty_in_store){ // If the Qty ordered is LESS or EQUAL to the Quantity in store
						$orderLineItem['status'] = "Available";
						$orderLineItem['quantity_confirmed'] = $product['quantity'];
						$leftQty = $qty_in_store - $product['quantity'];
					}else{ // If Quantity ordered is greater than qty in store
						$orderLineItem['status'] = "Shortage";
						$orderLineItem['quantity_confirmed'] = $qty_in_store;
						$leftQty = 0;
					}
					
					$confirmedOrderPrice = $confirmedOrderPrice + ($product['product_price_after_discount'] * $orderLineItem['quantity_confirmed']);
					
				}else{
					$orderLineItem['status'] = "Unavailable";
					$orderLineItem['quantity_confirmed'] = $qty_in_store;
					$leftQty = 0;
				}
				
				// Create/Update Order Line Items
				// Check if the record with the product_id and order_id already exists : If yes then update the record
				$lineItemRec = $this->db->query("SELECT order_line_item_id FROM bf_order_line_items WHERE order_id = ".$order_id." and product_id = ".$product['product_id'])->row();
				
				if(count($lineItemRec) > 0){ // Record exist -> update Record					
					$this->Generic_model->updateData("bf_order_line_items",$orderLineItem, array("order_id"=>$order_id, "product_id"=>$product['product_id']));
					$order_line_item_id = $lineItemRec->order_line_item_id;
				}else{ // New record
					$order_line_item_id = $this->Generic_model->insertDataReturnId('bf_order_line_items',$orderLineItem);				
				}
				
				// Update the product stock
				$stockUpdateData = array(
					"qty_in_store" => $leftQty 
				);
				$this->db->where('product_id', $product['product_id']);
				$this->db->update('bf_product_stock', $stockUpdateData);
				
				// Insert products blocked data into bf_block_product or update the record if order_id & product_id exists
				// Gather data for blocked products
				if($block_product_id > 0){ // Update existing block record
					$blockProductData['block_qty'] = $orderLineItem['quantity_confirmed'];
					// Update Product Block Record
					$this->Generic_model->updateData('bf_block_product',$blockProductData, array('order_id'=>$order_id,'product_id'=>$product['product_id']));	
				}else{ // Create a new Block record
					$blockProductData['customer_id'] = $customer_id;
					$blockProductData['order_id'] = $order_id;
					$blockProductData['order_line_item_id'] = $order_line_item_id;				
					$blockProductData['product_id'] = $product['product_id'];
					$blockProductData['block_qty'] = $orderLineItem['quantity_confirmed'];
					
					// Create Product Block Record
					$this->Generic_model->insertDataReturnId('bf_block_product',$blockProductData);	
				}
				
				// Calculate Actual Order Price
				$orderPrice = $orderPrice + ($product['product_price_after_discount'] * $orderLineItem['quantity']);
				
				$j++;
			}
		
			if(count($productIdData) > 0){
				foreach($productIdData as $removeProductId){
					
					// Get the product block quantity and add back to the product Stock
					$productBlockQty = $this->db->query("SELECT block_qty FROM bf_block_product WHERE product_id = ".$removeProductId." AND order_id = ".$order_id)->row();
					
					$updateStockValue = $productBlockQty->block_qty;
	
					// Update product stock qty back into product_stocks table
					$this->db->set('qty_in_store', 'qty_in_store+'.$updateStockValue, FALSE);
					$this->db->where('product_id', $removeProductId);
					$res = $this->db->update('bf_product_stock');
	
					// Remove records in block product table, order line item table
					$tables = array("bf_block_product","bf_order_line_items");
					$this->db->where(array("order_id"=>$order_id, "product_id"=>$removeProductId));
					$this->db->delete($tables);
				}
			}
			
		}	
		
		// Update Order Amount & confirmed Order Amount
		unset($updateOdr);
		$updateOdr['order_amount'] = $orderPrice;
		$updateOdr['order_confirmation_amount'] = $confirmedOrderPrice;
		$res = $this->Generic_model->updateData('bf_orders',$updateOdr,array('order_id'=>$order_id)); 
		
		// echo "Order id:". $order_id;
		// die;
		
		// Pull Order complete info
		$return_data = $this->entity_info("order_confirmation",$order_id);
		$this->response(array('code'=>'200','message'=>'Order created successfully', 'result'=>$return_data, 'requestname'=>$method));
		
	}
	
	
	
	/**
	Function order_confirmation confirms the order status and removes the products blocked record
	Author: Uday kanth Rapalli
	Dated: 7 July 2022 | 0056 Hrs
	*/
	Public function	order_payment_confirmation($parameters,$method,$customer_id){
		
		extract($parameters);
		
		// Status: Success
		// Update Order status
		$updateOdr['status'] = $order_status;
		$updateOdr['payment_mode'] = $payment_mode;
		$updateOdr['payment_method'] = $payment_method;
		$updateOdr['payment_status'] = $payment_status;
		$updateOdr['transaction_number'] = $transaction_number;
		
		$this->Generic_model->updateData('bf_orders',$updateOdr,array('order_id'=>$order_id));

		// Pull Order complete info
		$return_data = $this->entity_info("order_payment_transaction",$order_id);
		$this->response(array('code'=>'200','message'=>'Order Payment successful','result'=>$return_data,'requestname'=>$method));
		
	}
	
	
	/**
	Function order_list retrieves all the orders concern to particular customer
	Author: Uday kanth Rapalli
	Dated: 18 Jun 2022 : 1312 Hrs
	*/
	Public function order_list($parameters,$method,$customer_id){
		
		extract($parameters);
		
		if(!isset($parameters['customer_id'])){
			$msg = "Please provide customer id";
			$data = [];

			// Show up error message
			$this->response(array('code'=>'200','message'=>$msg,'result'=>$data,'requestname'=>$method));
		}else{
		
			// Gather all orders belongs to the customer
			$orders = $this->Generic_model->getAllRecords('bf_orders',array("customer_id" => $customer_id),array('field' => 'created_at','type' => 'DESC'));
			
			if(count($orders) > 0){
				$i = 0;
				foreach($orders as $order){
					$data['order_list'][$i] = $order;
					$data['order_list'][$i]->created_at = date("d-m-Y", strtotime($order->created_at));
					
					// Get line items for the order					
					$lineItems = $this->db->query("SELECT OLI.order_line_item_id, OLI.order_id, OLI.product_id, OLI.quantity, OLI.quantity_confirmed, P.product_name, P.product_code, P.sku_code, P.uom_id, PP.price, PP.discount, PP.discount_type, UOM.uom FROM bf_order_line_items OLI INNER JOIN bf_products P ON (OLI.product_id = P.product_id) inner join bf_product_price PP ON (OLI.product_price_id = PP.product_price_id) inner join bf_uom UOM on (P.uom_id = UOM.uom_id) WHERE OLI.order_id = $order->order_id ORDER BY OLI.order_line_item_id ASC")->result();
		
					if(count($lineItems) > 0){
						$j =0;
						$complete_order_discount = 0;
						foreach($lineItems as $lineItem){
							$confirmedQty = $lineItem->quantity_confirmed;
							$price = $price_after_discount = $lineItem->price;
							$discount = $lineItem->discount;
							$discount_type = $lineItem->discount_type;
							$total_discount = 0;
							
							$data['order_list'][$i]->line_items[$j] = $lineItem;
							
							if($discount != 0){
								// If type is percentage/%
								if($discount_type == "%" || $discount_type == "Percentage"){
									$price_after_discount = round(($price) - (($price * $discount)/100));
								}else if($discount_type == "INR"){
									$price_after_discount = round(($price) - ($discount));
								}
								$total_discount = round($price - $price_after_discount);
								$complete_order_discount = ($complete_order_discount + ($total_discount * $confirmedQty));
							}
							
							$data['order_list'][$i]->line_items[$j]->price_after_discount = $price_after_discount;					
							$data['order_list'][$i]->line_items[$j]->total_discount = $total_discount;
							
							// Get Product image
							$proImage = $this->db->query("SELECT image_path FROM bf_product_images WHERE product_id = ".$lineItem->product_id." LIMIT 1")->row();
							
							if(count($proImage) > 0){
								$data['order_list'][$i]->line_items[$j]->product_image = "http://54.179.95.174/bfarmz/uploads/".$proImage->image_path;
							}
							$j++;
						}
					}else{
						$data['order']['line_items'] = [];
					}
					$data['order_list'][$i]->total_order_discount = $complete_order_discount;
					$data['order_list'][$i]->invoice = "http://54.179.95.174/bfarmz/uploads/invoice/demo_invoice.pdf";
					$i++;
				}
				
				$this->response(array('code'=>'200','message'=>'Orders List','result'=>$data,'requestname'=>$method));
			}else{
				$data['order_list'] = [];
				$this->response(array('code'=>'200','message'=>'No Orders Placed Yet','result'=>$data,'requestname'=>$method));
			}
		}
	}
	
	
	/**
	Function order_list retrieves all the orders concern to particular customer
	Author: Uday kanth Rapalli
	Dated: 18 Jun 2022 : 1312 Hrs
	*/
	Public function order_details($parameters,$method,$customer_id){
		
		extract($parameters);
				
		// Get line items for the order
		// $lineItems = $this->Generic_model->getAllRecords('bf_order_line_items', array("order_id" => $order->order_id),  array('field' => 'order_line_item_id', 'type' => 'ASC'));			
		$lineItems = $this->db->query("SELECT OLI.order_line_item_id, OLI.order_id, OLI.product_id, P.product_name, P.product_code, P.sku_code, P.uom_id, PP.price, PP.discount, PP.discount_type FROM bf_order_line_items OLI INNER JOIN bf_products P ON (OLI.product_id = P.product_id) inner join bf_product_price PP ON (OLI.product_price_id = PP.product_price_id) WHERE OLI.order_id = ".$order_id." ORDER BY OLI.order_line_item_id ASC")->result();
		
		print_r($lineItems);
		exit;
		// foreach($lineItems as $item){
			
		// }
		// $data['order'][$i]->line_items = $order
		
	}
	
	
	/** Function delete_order_ite softm deletes the line item of the order
	* Author: Uday Kanth Rapalli
	* Dated: 6th July 2022 : 1320 Hrs
	*/
	Public function delete_order_item($parameters,$method,$customer_id){
		
		extract($parameters);
		
		// Delete the order line item
		// $result = $this->Generic_model->deleteRecord('bf_order_line_items', array("order_line_item_id" => $order_line_item_id ));
		$result = 1;
		if($result > 0){
			
			// Revert back by updating the orginal quantity of the product in the product stocks table
			// Get the stauts of the order
			$order_status = $this->db->query("SELECT status FROM bf_orders WHERE order_id = ".$order_id)->row();

			// if($order_status->status == "Blocked"){
				// Get blocked qty of the product
				// $blockedQuantity = $this->db->query("SELECT block_qty FROM bf_block_product WHERE order_line_item_id = ".$order_line_item_id)->row();
				// echo "Blocked Quantity:";
				// print_r($blockedQuantity);
				// exit;
			// }
			
			$this->response(array('code'=>'200','message'=>'Item deleted successfully','result'=>$data,'requestname'=>$method));
		}else{
			$this->response(array('code'=>'','message'=>'Item could not be deleted, please check if you are sending order_line_item_id','result'=>$data,'requestname'=>$method));
		}
	}
	
	
	/** Function profile_edit will edit the promfile name using user id
	* Params: user_id, complete name
	*/
	Public Function generate_invoice_old($parameters,$method,$customer_id){
		
		extract($parameters);
		
		$data['order_id'] = $order_id;
		$data['invoice'] = "http://54.179.95.174/bfarmz/uploads/invoice/demo_invoice.pdf";
		
		$this->response(array('code'=>'200','message'=>'Invoice generated successfully','result'=>$data,'requestname'=>$method));
	}
	
	
	/** Function profile_edit will edit the promfile name using user id
	* Params: user_id, complete name
	*/
	Public function profile_edit($parameters,$method,$customer_id){
		
		extract($parameters);
		
		// Edit the first & last Names of the Customer
		$data['first_name'] = $first_name;
		$data['last_name'] = $last_name;
		
		$addressData['block_phase'] = $block_phase;
		$addressData['flat_no'] = $flat_no;
	
		$res = $this->Generic_model->updateData('bf_customer', $data, array('customer_id' => $customer_id));
		$res = $this->Generic_model->updateData('bf_customer_address', $addressData, array('customer_id' => $customer_id));
		
		if($res){
			$return_data = $this->entity_info("bf_customer",$customer_id);
			$this->response(array('code'=>'200','message'=>'Customer profile edited successfully', 'result'=>$return_data, 'requestname'=>$method));
		}else{
			$this->response(array('code'=>'500','message'=>'Somethign wrong, Please try again', 'result'=>'', 'requestname'=>$method));
		}
		
	}
	
	
	
	public function download_invoice($order_id){

		
	$data['orders_list']=$this->db->query("select a.payment_mode,a.payment_method,a.transaction_number,a.customer_id,b.email,b.mobile,a.order_id,a.payment_mode,IF(payment_status=1, 'PAID', 'NOT PAID') as paymentstatus,a.order_amount,DATE_FORMAT(a.created_at,'%d %b, %Y') as orderdate,a.order_number,CONCAT(b.first_name,'',b.last_name) as customer from bf_orders a inner join bf_customer b on a.customer_id=b.customer_id where order_id='".$order_id."'")->row();

	$data['customer_address']=$this->db->query("SELECT * FROM bf_customer_address a right join bf_community b on a.community_id=b.community_id  left join states c on b.state_id=c.state_id left join districts d on d.state_id=c.state_id where a.customer_id='".$data['orders_list']->customer_id."'")->row();
	
	$data['order_products']=$this->db->query("SELECT * FROM bf_order_line_items a inner join bf_orders b on a.order_id=b.order_id inner join bf_products c on a.product_id=c.product_id inner join bf_categories d on c.category_id=d.category_id where a.order_id='".$order_id."'")->result();	
	
		$this->load->library('M_pdf');
		
		
		
		$html=$this->load->view('order_invoice',$data, true);	
		$pdfFilePath ="INVOICE-".$data['orders_list']->order_number."-".time()."-download.pdf";		
		$pdf = $this->m_pdf->load();
		$pdf->WriteHTML($html,2);
		$is_file_downloaded=$pdf->Output("D:/xampp/htdocs/bfarmz/invoices/".$pdfFilePath, "F");
		
		if($is_file_downloaded){
			
		echo "Hello";	
			
		}
		
	
	
		
	}
	
	
	
	Public Function generate_invoice($parameters,$method,$customer_id){
		
		extract($parameters);
		
				
		$data['orders_list']=$this->db->query("select a.payment_mode,a.payment_method,a.transaction_number,a.customer_id,b.email,b.mobile,a.order_id,a.payment_mode,IF(payment_status=1, 'PAID', 'NOT PAID') as paymentstatus,a.order_amount,DATE_FORMAT(a.created_at,'%d %b, %Y') as orderdate,a.order_number,CONCAT(b.first_name,'',b.last_name) as customer from bf_orders a inner join bf_customer b on a.customer_id=b.customer_id where order_id='".$order_id."'")->row();

	$data['customer_address']=$this->db->query("SELECT * FROM bf_customer_address a right join bf_community b on a.community_id=b.community_id  left join states c on b.state_id=c.state_id left join districts d on d.state_id=c.state_id where a.customer_id='".$data['orders_list']->customer_id."'")->row();
	
	$data['order_products']=$this->db->query("SELECT * FROM bf_order_line_items a inner join bf_orders b on a.order_id=b.order_id inner join bf_products c on a.product_id=c.product_id inner join bf_categories d on c.category_id=d.category_id where a.order_id='".$order_id."'")->result();	
	
		$this->load->library('M_pdf');
		
		
		
		$html=$this->load->view('order_invoice',$data, true);	
		$pdfFilePath ="INVOICE-".$data['orders_list']->order_number."-".time()."-download.pdf";		
		$pdf = $this->m_pdf->load();
		$pdf->WriteHTML($html,2);
		$is_file_downloaded=$pdf->Output("D:/xampp/htdocs/bfarmz/invoices/".$pdfFilePath, "F");		
		
		$datas['invoice'] = "http://54.179.95.174/bfarmz/invoices/".$pdfFilePath;
		
		$this->response(array('code'=>'200','message'=>'Invoice generated successfully','result'=>$datas,'requestname'=>$method));
	}
	
	
	
	
	
}
?>