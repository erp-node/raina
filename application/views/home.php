<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RAINA </title>

    <!-- Bootstrap -->
    <link href="<?php echo base_url('assets/css/bootstrap.min.css');?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="<?php echo base_url('assets/css/font-awesome.min.css');?>" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?php echo base_url('assets/css/nprogress.css');?>" rel="stylesheet">
    <!-- Animate.css -->
    <link href="<?php echo base_url('assets/css/animate.min.css');?>" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="<?php echo base_url('assets/css/custom.css');?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/style.css');?>" rel="stylesheet">
      <script src="<?php echo base_url('assets/js/jquery.js'); ?>"></script>       
  <script src="<?php echo base_url('assets/js/bootstrap.min.js');?>"></script>
  </head>
<style>
.b_box {
    padding: 40px;
    background-color: #fff;
}
</style>
  <style>
    .notify {
    position: fixed;
    width: 100%;
    top: 0px;
    color: #fff;
    text-align: center;
    padding: 10px;
    z-index: 99;
    font-size: 18px;
    display: none;
    }
    .notify-error {
        background-color: rgb(228,69,69);
    }
    .notify-suscess {
        background-color: #4cae4c;
    }
	
  </style>

  <body class="login"style="background-color:  #f5f4f3;">
   <div class="row">
            <div class="notify notify-error"  style="display: none;"></div>
            <div class="notify notify-suscess" style="display: none;"></div>
        <script>
        <?php if($this->session->flashdata('suscess')){ 
                  ?>
          $("div.notify.notify-suscess").html('<p class="error_mages_col"><i class="thumbs up icon"></i><?php echo $this->session->flashdata('suscess')?></p>').fadeIn(500).delay(2000).fadeOut(500);
          <?php } ?>
          <?php if($this->session->flashdata('error')){  ?>
            $("div.notify.notify-error").html('<p class="error_mages_col"><i class="thumbs down icon"></i><?php echo $this->session->flashdata('error')?></p>').fadeIn(500).delay(2000).fadeOut(500);
            <?php } ?>
        </script>
     </div> 

    <div>
      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
             <?php
				$attrributes = "autocomplete='off'";
			 echo form_open('Authentication/signin',$attrributes);?>
      
			  <div class="b_box">
			   <!--<img src="<?php echo base_url();?>/assets/images/ncl_logo.png" style=" width: 177px;margin:10px;"/>  -->
            
              <br/>  <h1>RAINA</h1>
              <div class="">
                <input type="text" class="form-control" placeholder="Username" name="email" id="email" required style="height: 42px;"/>
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password"  name="password" id="password" required style="height: 42px;"/>
              </div>
              <div>
                <input type="submit" name="login" value="Login" class="btn btn-default submit" />
                <a class="reset_pass" href="" data-toggle="modal" data-target="#myModal" >Lost your password?</a>
                </div>

              <div class="clearfix"></div>
				<hr>
           <!--  <a class="btn btn-md btn-default" href="<?php echo base_url('Authentication/customer_register');?>" style="text-decoration: none;">Customer Registration</a>
			 <br/> -->
			 </div>
            <?php echo form_close();?>
          </section>
        </div>

        <div id="register" class="animate form registration_form" >
          <section class="login_content">
            <form  action="<?php echo base_url('authentication/create_account');?>" method="post">
              <h1>Create Account</h1>
              <?php echo form_open('authentication/create_account');?> 
              <div>
                <input type="text" class="form-control" placeholder="First Name" name="first_name" required="" />
              </div>
              <div>
                <input type="text" class="form-control" placeholder="Last Name" name="last_name" required="" />
              </div>

              <div>
                <input type="text" class="form-control" placeholder="Company Name"  name="company_name" required="" />
              </div>
               <div>
                <input type="text" class="form-control" placeholder="Department"  name="department_name" required="" />
              </div>
              <div>
                <input type="text" class="form-control" placeholder="Mobile" name="mobile"  required="" />
              </div> 
               <div>
                <textarea class="form-control" placeholder="Address" name="address" cols="3"  required="" ></textarea> 
              </div>
              <input type="hidden" name="user_type" value="customer" >
              <br/>   
              <div>
                <input type="email" class="form-control" placeholder="Email"  name="email"  required="" />
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password" name="password"  required="" />
              </div>
			    <div>
               
              </div>
              <div>
                <input type="submit" class="btn btn-default submit" value="Submit">
              </div>
              <?php echo form_close();?> 

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">Already a member ?
                  <a href="#signin" class="to_register"> Log in </a>
                </p>

                <div class="clearfix"></div>
                <br />

                
              </div>
            </form>
          </section>
        </div>
		
      </div>
    </div>
    <!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Forgot Password</h4>
      </div>
      <div class="modal-body">
       <?php echo form_open('Authentication/forgot_password');?>
        <div class="col-md-10">
            <label for="email" class="control-label">Email</label>
            <div class="form-group">
              <input type="email" name="email" value="" class="form-control" required="required" id="email" />
            </div>
          </div>
      
      </div>
      <div class="modal-footer">
        <input type="submit"class="btn btn-success" value="Submit" name="submit">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
     <?php echo form_close();?>

  </div>
</div>
  </body>
</html>
