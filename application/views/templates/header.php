<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RAINA</title>
	<!-- Bootstrap -->
	
    <link href="<?php echo base_url('assets/css/bootstrap.min.css');?>" rel="stylesheet">
    <!-- Font Awesome -->
   <link href="<?php echo base_url('assets/css/font-awesome.min.css');?>" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
     <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">-->
	
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
    <link href="<?php echo base_url('assets/css/daterangepicker.css');?>" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="<?php echo base_url('assets/css/custom.css');?>" rel="stylesheet">
    <!-- /top navigation -->
	<link href="https://fonts.googleapis.com/css?family=Nunito+Sans" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
		  <!-- jQuery -->
	<script src="<?php echo base_url('assets/js/jquery.min.js');?>"></script>
	<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>"></script>
		   
<script src="<?php echo base_url('assets/js/jquery.datetimepicker.full.js');?>" type="text/javascript" ></script>
	
  </head>
		<style>
	.menu_section>ul {
    margin-top: 0px;
}
.right_col{
	min-height: 800px;
}
.nav>li>a>img {
    max-width: none;
    margin-top: -10px;
}
.table-responsive {
    min-height: 400px;
    overflow-x: auto;
}
	</style>

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
      
         <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a href="<?php echo base_url('Admin/index');?>" class="site_title" ><i class="fa fa-paw"></i> <span>RAINA</span></a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
               
              </div>
            </div>

            <!-- sidebar menu -->
              <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <ul class="nav side-menu">
				        <h3>MAIN</h3>
			
					 <ul class="nav side-menu">
					<li><a href="<?php echo base_url('Admin/index');?>"><i class="fa fa-dashboard"></i> Dashboard</a>
					
                </ul>
					 <ul class="nav side-menu">
					<li  class ='<?php if($this->uri->segment(2) =="vehical_create" || $this->uri->segment(2) =="damages_create"){echo"current-page active"; } ?>'><a><i class="fa fa-car"></i>Fleet<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu" <?php if($this->uri->segment(2) =="vehical_create"|| $this->uri->segment(2) =="damages_create"){echo"style='display:block'"; } ?>>
					<li  class ='<?php if($this->uri->segment(2) =="vehical_create"){echo"current-page"; } ?>'><a href="<?php echo base_url('Admin/vehical');?>">Vehicles</a></li>
					<li  class ='<?php if($this->uri->segment(2) =="damages_create"){echo"current-page"; } ?>'><a href="<?php echo base_url('Admin/damages');?>">Damages</a></li>
                    </ul>
                </ul>
					 <ul class="nav side-menu">
					<li class ='<?php if($this->uri->segment(2) =="hirers_create"){echo"current-page active"; } ?>'><a><i class="fa fa-car"></i> &nbsp;&nbsp;Hirers<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu " <?php if($this->uri->segment(2) =="hirers_create"){echo"style='display:block'"; } ?>>
					
					<li class ='<?php if($this->uri->segment(2) =="hirers_create"){echo"current-page"; } ?>'><a href="<?php echo base_url('Admin/hirers');?>">Hirer</a></li>
					<li><a href="<?php echo base_url('Admin/approve_hirer');?>">Approve a Hirer</a></li>
					<li><a href="<?php echo base_url('Admin/active_hirer');?>">Active Hirer List</a></li>
					<li><a href="<?php echo base_url('Admin/remove_hirer');?>">Remove a Hirer</a></li>
                    </ul>
                </ul>
					
					<ul class="nav side-menu">
					<li ><a><i class="fa fa-book"></i>Booking<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu">
					<li><a href="#">Make a Booking</a></li>
					<li><a href="#">Cancle a Booking</a></li>
                    </ul>
                </ul>
                 <h3></h3>
               <ul class="nav side-menu">
					<li   class ='<?php if($this->uri->segment(2) =="user_insert" || $this->uri->segment(2) =="roles_insert"){echo"current-page active"; } ?>'><a><i class="fa fa-cog"></i>Settings<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu" <?php if($this->uri->segment(2) =="user_insert"|| $this->uri->segment(2) =="roles_insert"){echo"style='display:block'"; } ?>>
					<li  class ='<?php if($this->uri->segment(2) =="user_insert"){echo"current-page"; } ?>'><a href="<?php echo base_url('Admin/user_list'); ?>">User Profile</a></li>
					<li  class ='<?php if($this->uri->segment(2) =="roles_insert"){echo"current-page"; } ?>'><a href="<?php echo base_url('Admin/role_list');?>">Roles</a></li>
                    </ul>
                </ul>
             
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
                    <?php $user_name = $this->session->userdata('logged_in')['first_name'];
                      $user_id = $this->session->userdata('logged_in')['user_id'];

                      ?>
                   
              
                   Logout <span class="fa fa-caret-down"></span></p>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right">
                    <li><a href="<?php echo base_url('Authentication/user_profile');?>"> Profile</a></li>
                    <li><a href="<?php echo base_url('Authentication/signout');?>"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                  </ul>
                </li>
			          <li role="presentation" class="dropdown">
                  <!--<a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false" style="border-bottom:none;height: 65px;padding-top: 18px;">
                    <i class="fa fa-bell pulse_signal"></i>
                    <span class="badge"></span>
                  </a> -->
                  <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">

                  
                          <li>
                            <a href="#">
                              <span class="image">
                							  <i class="fa fa-user-circle-o" style="font-size:20px"></i>
                							  <!--<img src="<?php echo base_url('assets/images/img.jpg');?>"  />--></span>
                              <span>
                                <span><?php echo $user_name;?></span>
                                
                              </span>
                              
                            </a>
                          </li>
                       
                               
                            </ul>    
                
             
            </nav>
          </div>
        </div>
		 
       
		 
