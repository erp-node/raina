<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>NCL</title>
	<!-- Bootstrap -->
	
    <link href="<?php echo base_url('assets/css/bootstrap.min.css');?>" rel="stylesheet">
    <!-- Font Awesome -->
   <link href="<?php echo base_url('assets/css/font-awesome.min.css');?>" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
     <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">-->
	<link href="<?php echo base_url('assets/css/jquery-ui.min.css');?>" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?php echo base_url('assets/css/nprogress.css');?>" rel="stylesheet">
   <!-- iCheck -->
    <link href="<?php echo base_url('assets/css/green.css');?>" rel="stylesheet">

    <link href="<?php echo base_url('assets/css/dataTables.bootstrap.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/buttons.bootstrap.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/fixedHeader.bootstrap.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/bootstrap-select.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/responsive.bootstrap.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/scroller.bootstrap.min.css');?>" rel="stylesheet">

    <link href="<?php echo base_url('assets/css/style.css');?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/iCheck/skins/flat/green.css');?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/css/jquery.datetimepicker.min.css');?>" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/css/bootstrap-select.min.css" />

    <!-- bootstrap-progressbar -->
    <link href="<?php echo base_url('assets/css/bootstrap-progressbar-3.3.4.min.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/multiple-select.css');?>" rel="stylesheet">
    <!-- JQVMap -->
    <link href="<?php echo base_url('assets/jqvmap/dist/jqvmap.min.css');?>" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <!-- <link href="<?php// echo base_url('assets/css/daterangepicker.css');?>" rel="stylesheet"> -->
    <!-- Custom Theme Style -->
    <link href="<?php echo base_url('assets/css/custom.css');?>" rel="stylesheet">
    <!-- /top navigation -->
	<link href="https://fonts.googleapis.com/css?family=Nunito+Sans" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
		  <!-- jQuery -->
  <script src="<?php echo base_url('assets/js/jquery.js');?>" type="text/javascript" ></script>
	<script src="<?php echo base_url('assets/js/jquery.min.js');?>"></script>
	<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>"></script>
		   <script src="<?php echo base_url('assets/js/jquery-ui.min.js');?>"></script>
 <script src="<?php echo base_url('assets/js/jquery.datetimepicker.full.js');?>" type="text/javascript" ></script>
 <!-- Madhu -->
<script type="text/javascript" src="https://cdn.datatables.net/tabletools/2.2.2/swf/copy_csv_xls_pdf.swf"></script>

		<script type="text/javascript" src="https://cdn.datatables.net/tabletools/2.2.4/js/dataTables.tableTools.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.1.2/js/dataTables.buttons.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.1.2/js/buttons.flash.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
		<script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
		<script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.1.2/js/buttons.html5.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.1.2/js/buttons.print.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.5.2/css/buttons.dataTables.min.css"></script>

     <!-- <script src="<?php echo base_url('assets/js/highcharts.js');?>"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/funnel.js"></script>
 -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/funnel.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>

  
	
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
	  <!-- Header Navbar: style can be found in header.less -->
          <div class="row">
		   <div class="notify notify-error"  style="display: none;"></div>

                <div class="notify notify-suscess" style="display: none;"></div>

                <script>
                <?php if ($this->session->flashdata('suscess')) { ?>
                    
                    $("div.notify.notify-suscess").html('<p><i class="thumbs up icon"></i><?php echo $this->session->flashdata('suscess') ?></p>').fadeIn(500).delay(2000).fadeOut(500);
                    <?php } ?>
                        <?php if ($this->session->flashdata('error')) { ?>$("div.notify.notify-error").html('<p><i class="thumbs down icon"></i><?php echo $this->session->flashdata('error') ?></p>').fadeIn(500).delay(2000).fadeOut(500);
                <?php } ?>
                </script>
           
     </div> 
<style>
.nav>li>a>img {
    max-width: none;
    margin-top: -10px;
}
</style>
      
        <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
           <div class="navbar nav_title" style="border: 0;">
             <a href="<?php echo base_url('Admin/dashboard');?>" class="site_title"><img src="<?php echo base_url('assets/images/Ncl_Logo.png');?>" class="img_wid img_ncl_v" style="display:none"/><span> &nbsp;&nbsp;<img src="<?php echo base_url('assets/images/For_App_Icon.png');?>"  class="header-logo1" id="mm"/></span><span class="logo_text"></span></a>
            
			</div>

            <div class="clearfix"></div>

            

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <ul class="nav side-menu" >
				        <h3>MAIN</h3>
						<!--
                <li>
                  <a href="<?php echo base_url('admin/dashboard') ;?>"><img src="<?php echo base_url('assets/images/icons_ncl/Dashboard.png');?>" /></i>&nbsp;&nbsp;&nbsp;&nbsp;Dashboard </a>
                </li>-->
				
				<?php  if(accessrole(Contacts,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/contact_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Contactsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Contacts</a>
                  <li>
                <?php } ?>
				
				
                <?php  if(accessrole(Leads,P_READ)){ ?>
                    <li>
                      <a href="<?php echo base_url('Leads/lead_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Leadsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Leads</a>
                    </li> 
                <?php } ?>
				
				<?php  if(accessrole(Opportunity,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Opportunity/opportunity_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Opportunitiesx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Opportunities</a>
                  </li>
                <?php } ?>
				
                <?php  if(accessrole(Customers,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/customer_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Customersx.png');?>" />Direct Customers</a>
                  </li>
				   <li>
                    <a href="<?php echo base_url('Admin/tp_customer_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Customersx.png');?>" />Third Party Customers</a>
                  </li>
                <?php } ?>
				<?php  if(accessrole(SalesOrder,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/sales_oders_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Sales Orders</a>
                  </li>
                <?php } ?>
				
				<?php  if(accessrole(SalesOrder,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/tp_sales_oders_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Third Party Orders</a>
                  </li>
                <?php } ?>
				<?php  if(accessrole(SalesCalls,P_READ)){ ?>
                <li>
                  <a href="<?php echo base_url('Admin/calls_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Callsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Sales Calls</a>
                </li>
              <?php } ?>
               
                
                <?php  if(accessrole(quotations,P_READ)){ ?>
                <li>
                    <a href="<?php echo base_url('Admin/quotation_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/quotation-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Quotation</a>
                  </li>
                <?php } ?>
                 <?php  if(accessrole(Contacts,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/contract_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Contractsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Contracts</a>
                  <li>
                <?php } ?>
                <?php  if(accessrole(SalesOrder,P_READ)){ ?>
                  <!--<li>
                    <a href="<?php echo base_url('Admin/sales_oders_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Sales Orders</a>
                  </li>
                <?php } ?>
                
                <?php  if(accessrole(SalesCalls,P_READ)){ ?>
                <li>
                  <a href="<?php echo base_url('Admin/calls_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Callsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Sales Calls</a>
                </li>-->
              <?php } ?>
                <?php  if(accessrole(Complaint,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/complaint_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Complaintsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Complaints </a>
                  </li>
                <?php }?>
                <?php  if(accessrole(Ta_and_da,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/ta_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Ta & Dax.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Expenses </a>
                  </li>
                <?php } ?>
                <li>
                  <a href="<?php echo base_url('Admin/route_map_list_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Route Map</a>
                </li>
                 <li>
                  <a href="<?php echo base_url('Admin/payment_collection_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Payment Collection</a>
                </li>
               
                <li>
                  <a href="<?php echo base_url('Admin/notification_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Notifications</a>
                </li>

                 <li>
                  <a href="<?php echo base_url('Admin/conveyance_list'); ?>"><i class="fa fa-institution"></i>Conveyance</a>
                </li> 
                 <li>
                  <a href="<?php echo base_url('Admin/profile_permissions_list'); ?>"><i class="fa fa-user-circle-o"></i>USER CONTROLS </a> 
                 </li>

                 <h3>REPORTS</h3>
                  <li>
                  <a href="<?php echo base_url('Admin/tracking_reports');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;TRACKING</a>
                </li>
                  <li>
                  <a href="<?php echo base_url('Admin/sales_calls_report_ajax'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;DSR</a> 
                 </li>
				 
				 <li>
                  <a href="<?php echo base_url('Reports/dsr_compliance'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;DSR Compliance</a> 
                 </li>
				 
				 <li>
                  <a href="<?php echo base_url('Reports/weekly_dsr_compliance'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Notificationsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp; Weekly DSR Compliance</a> 
                 </li>
				 
				
                  <li>
                    <a href="<?php echo base_url('Reports/sales_oders_report'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Sales Order Report</a>
                  </li>
				  
				  <li>
                    <a href="<?php echo base_url('Reports/tp_sales_oders_report'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Third party order wise report</a>
                  </li>
				  
				   <li>
                    <a href="<?php echo base_url('Reports/tp_sales_oders_productwise_report'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Sales Ordersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Third party product wise report</a>
                  </li>
				  
				  
                



                 <?php 
                 $Products = accessrole(Products,P_READ);
                 $price_list = accessrole(price_list,P_READ);
                 $plant = accessrole(plant,P_READ);
                 $Departments = accessrole(Departments,P_READ);
                 $Division = accessrole(Division,P_READ);
                 $PayementTerms = accessrole(PayementTerms,P_READ);
                 $incoTerms = accessrole(incoTerms,P_READ);
                 $materialGroup = accessrole(materialGroup,P_READ);
                  $materialsubgroup = accessrole(materialsubgroup,P_READ);
                 $region = accessrole(region,P_READ);
                 ?>
                 <?php if($Products == 1|| $price_list == 1|| $plant == 1|| $Departments == 1|| $Division == 1||$PayementTerms == 1||$incoTerms == 1|| $materialGroup == 1|| $Products == 1|| $region ==1){ ?>
                  <h3>MASTERS</h3>
                <?php } ?>
                  <?php  if(accessrole(Products,P_READ)){ ?>
                    <li>
                      <a href="<?php echo base_url('Admin/product_list_ajax_data'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Productsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Products</a>
                    </li>
					
					<!--<li>
                      <a href="<?php echo base_url('Migration/upload_products_bulk_form'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Productsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Products bulkupload</a>
                    </li>-->
					
					<li>
                      <a href="<?php echo base_url('Migration/upload_products_weight_form'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Productsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Products bulkupload</a>
                    </li>
                  <?php }?>
                  
                 <?php  if(accessrole(price_list,P_READ)){ ?>
                    <li>
                      <a href="<?php echo base_url('Admin/price_product_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Product Pricex.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Price List</a>
                    </li>
                  <?php } ?>
                     <?php  if(accessrole(plant,P_READ)){ ?>
                    <li>
                    <a href="<?php echo base_url('Admin/plant_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Contractsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Plants</a>
                  <li>
                  <?php } ?>
                  <?php  if(accessrole(Departments,P_READ)){ ?>
                    <!--<li>
                      <a href="<?php echo base_url('Admin/departments_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Customersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Departments</a>
                    </li>-->
                  <?php } ?>

                  <li>
                    <a href="#"><img src="<?php echo base_url('assets/images/icons_ncl/Departmentx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Schemes Information</a>
                  </li>
                   <?php  if(accessrole(Division,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('admin/division_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Divison-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Division</a>
                  </li>
                <?php } ?>
                 <?php  if(accessrole(PayementTerms,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/payment_terms_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/payment terms-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Payment Terms</a>
                  </li>
                <?php } ?>
                 <?php  if(accessrole(incoTerms,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/incoterm_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Inco terms-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Inco Terms</a>
                  </li>
                <?php } ?>
                 <?php  if(accessrole(materialGroup,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/materialGroup_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/meterial group-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Material Group</a>
                  </li>
                <?php } ?>
                 <?php  if(accessrole(materialsubgroup,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin/materialsubGroup_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/meterial sub group-01-01.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Material Sub Group</a>
                  </li>
                <?php } ?>
                 <!-- <?php  if(accessrole(region,P_READ)){ ?>
                  <li>
                    <a href="<?php echo base_url('Admin_01/region_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Departmentx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Region</a>
                  </li>
                  <?php } ?> -->

                  <?php 
                 $Users = accessrole(Users,P_READ);
                 $Roles = accessrole(Roles,P_READ);
                 $Profiles = accessrole(Profiles,P_READ);

                 if($Users == 1|| $Roles == 1 || $Profiles ==1){
                 ?>

                 <h3>ADMINISTRATION</h3>
               <?php } ?>
                  <?php  if(accessrole(Users,P_READ)){ ?>
                    <li>
                      <a href="<?php echo base_url('Admin/user_list'); ?>" ><img src="<?php echo base_url('assets/images/icons_ncl/Usersx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Users</a>
                    </li> 
                  <?php } ?>
                  <?php  if(accessrole(Roles,P_READ)){ ?>
                    <li >
                      <a href="<?php echo base_url('Admin/role_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Rolesx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Roles</a>
                    </li> 
                  <?php } ?>
                  <?php  if(accessrole(Profiles,P_READ)){ ?>
                    <li>
                      <a href="<?php echo base_url('Admin/Profile_list');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Profilesx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Profiles </a>
                    </li> 
                  <?php } ?>

                   <!-- <li>
                    <a href="<?php echo base_url('Admin_01/Approval_Process');?>"><img src="<?php echo base_url('assets/images/icons_ncl/Workflowsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Approval Process</a>
                  </li>
                  <li>
                    <a href="<?php echo base_url('Admin/email_template_list'); ?>"><img src="<?php echo base_url('assets/images/icons_ncl/Contractsx.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;Mail Templates</a>
                  <li>-->
                      
                       <!-- <li ><a href="<?php echo base_url('Admin/profile_permissions_list');?>"><img src="<?php echo base_url('images/User Controls.png');?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;User Controls</a></li> -->
                   
                  
                   
              </ul>
            </div>
          </div>
			
            <!-- /sidebar menu -->

            <!-- /menu footer buttons -->
              <div class="sidebar-footer hidden-small" style=" display:  none;">
              <a data-toggle="tooltip" data-placement="top" title="Settings">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Lock">
                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Logout" >
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
              </a>
            </div>
            <!-- /menu footer buttons -->
          </div>
        </div>

        <!-- top navigation -->
        <div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars" style=" color: #565791;"></i></a>
              </div>

              <ul class="nav navbar-nav navbar-right">
               <li class="" style="margin-right:15px;">
                  <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false" >
                    <?php $user_name = $this->session->userdata('logged_in')['name'];
                      $user_id = $this->session->userdata('logged_in')['id'];
                      $prifile_checking = $this->db->query("select * from users where user_id =".$user_id)->row();
                      if($prifile_checking->profile_image == "" || $prifile_checking->profile_image == Null){
                     ?>
                  <p style=" color: #565791;"><img src=" <?php echo base_url('assets/images/img.jpg');?>" alt=""><b><?php echo $user_name ;?></b>
                    <?php }else{?>
                      <p style=" color: #565791;"><img src=" <?php echo base_url('images/profile_image/');?><?php echo $prifile_checking->profile_image ;?>" alt=""><b><?php echo $user_name ;?></b>
                    <?php }?>
              
                    <span class=" fa fa-caret-down"></span></p>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right">
                    <li><a href="<?php echo base_url('Authentication/user_profile');?>"> Profile</a></li>
                    <li><a href="<?php echo base_url('Authentication/signout');?>"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                  </ul>
                </li>
			          <li role="presentation" class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false" style="border-bottom:none;height: 65px;padding-top: 18px;">
                    <i class="fa fa-bell pulse_signal"></i>
                    <span class="badge"></span>
                  </a>
                  <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">

                  
                          <li>
                            <a href="#">
                              <span class="image">
                							  <i class="fa fa-user-circle-o" style="font-size:20px"></i>
                							  <!--<img src="<?php echo base_url('assets/images/img.jpg');?>"  />--></span>
                              <span>
                                <span>admin</span>
                                <span class="time">10:30</span>
                              </span>
                              <span class="message">
                                title message
                              </span>
                            </a>
                          </li>
                       
                                <li>
                                <div class="text-center">
                                  <a href="#">
                                    <strong>See All Alerts</strong>
                                    <i class="fa fa-angle-right"></i>
                                  </a>
                                </div>
                              </li>
                            </ul>    
                
             
            </nav>
          </div>
        </div>
		 
       
		 
