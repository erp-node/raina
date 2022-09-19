 <style>
 #myImg {
  cursor: pointer;
    transition: 0.3s;
}

#myImg:hover {opacity: 0.7;}

/* The Modal (background) */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
}

/* Modal Content (image) */
.modal-content {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
}

/* Caption of Modal Image */
#caption {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
    text-align: center;
    color: #ccc;
    padding: 10px 0;
    height: 150px;
}

/* Add Animation */
.modal-content, #caption {    
    -webkit-animation-name: zoom;
    -webkit-animation-duration: 0.6s;
    animation-name: zoom;
    animation-duration: 0.6s;
}

@-webkit-keyframes zoom {
    from {-webkit-transform:scale(0)} 
    to {-webkit-transform:scale(1)}
}

@keyframes zoom {
    from {transform:scale(0)} 
    to {transform:scale(1)}
}

/* The Close Button */
.close {
    position: absolute;
    top: 15px;
    right: 35px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    transition: 0.3s;
	
}

.close:hover,
.close:focus {
    color: #bbb;
    text-decoration: none;
    cursor: pointer;
}

/* 100% Image Width on Smaller Screens */
@media only screen and (max-width: 700px){
    .modal-content {
        width: 100%;
    }
}

 </style>
 <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <!-- <div class="page-title">
              <div class="title_left" id="in_voice">
               
               <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('admin/dashboard');?>">HOME</a></li>
                 <li><a href="#">&nbsp;&nbsp;MY PROFILE</a></li>
                  <li style=" display: none;"><a href="#"></a></li>
               </ul>
             
            </div> -->
            
            <div class="clearfix"></div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>User Profile</h2>
                    
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				  
                    <div class="col-md-3 col-sm-3 col-xs-12 profile_left" >
                      <div class="profile_img">
                        <div id="crop-avatar">
                          <!-- Current avatar -->
                      <div class="x_panel" style="margin-top: 64px;width: 203px;height:  223px;">
						 <div class="image-upload">
							<label for="file-input">
                <?php if($user_details->profile_image == ""){ ?>
                 <img  id="myImg" class="img-responsive avatar-view fancybox" src="<?php echo  base_url('assets/images/user.png');?>" height='223px' width='203px'>
                <?php }else{ ?>
                <img id="myImg"  class="img-responsive avatar-view fancybox" src="<?php echo  base_url('images/profile_image/');?><?php echo $user_details->profile_image ;?>" style="height: 197px;width: 168px;">
               <?php  } ?>
							 
					
<!-- The Modal -->
<div id="myModal" class="modal">
  <span class="close">&times;</span>
  <img class="modal-content" id="img01">
  <div id="caption"></div>
</div>		 
							
							</label>

							
						</div></div>
                      
                      
					   </div>
                      </div>
					  
                    <!--   <h3><?php echo $users_list->name;?></h3>

                     <ul class="list-unstyled user_data">
                       
                        <li>
                          <i class="fa fa-briefcase user-profile-icon"></i> <?php echo $users_list->designaion;?>
                        </li>

                        <li>
                          <i class="fa fa-envelope"></i> <?php echo $users_list->username ;?>
                       
                        </li>
                      </ul>  -->
                      <div style=" margin-left: 48px;">
                      <a class="btn btn-sm btn-success" data-toggle="modal" data-target="#myModalim"><i class="fa fa-edit m-right-xs"></i> EDIT IMAGE</a>
                    </div>
                    <div id="myModalim" class="modal fade" role="dialog">
				  <div class="modal-dialog">
            <?php echo form_open_multipart('authentication/profile_edit');?>
					<!-- Modal content-->
					<div class="modal-content">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">IMAGE UPLOAD</h4>
					  </div>
					  <div class="modal-body">
						<input  type="file" class="form-control" name="file" id="file">
					  </div>
					  <div class="modal-footer">
						<input type="submit" value="ok" class="btn btn-danger">
						<?php echo form_close();?>
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					  </div>
					</div>

				  </div>
				</div>
			  <br />
			  <!-- start skills -->
			  <!-- end of skills -->
			</div>
                   
					
                        <div class="auto bat">
              <div class="col-md-6 col-xs-12" style="margin-top: 64px;">
			  
                <div class="x_panel">
				
                  <div class="x_title">
                    <h2>Details</h2>
                     
                    <div  style="float: right;">
					 <a class="btn btn-sm btn-success wait" value="manual"><i class="fa fa-edit m-right-xs"></i>CHANGE PASWORD</a>
                    </div>
                    <div class="clearfix"></div>
                  </div>

				   
                  <div class="x_content">
                    <br />
					 
                    <div class="form-horizontal form-label-left" >
                        <?php echo form_open('authentication/change_profile');?>
                        <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">First Name</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                          <input type="text" class="form-control" name="first_name" value="<?php echo $user_details->first_name;?>" >
                        </div>
                      </div>
                       <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Last Name</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                          <input type="text" class="form-control" name="last_name" value="<?php echo $user_details->last_name;?>" >
                        </div>
                      </div> 
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Mobile</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                           <input type="number" id="number" name="mobile"  value="<?php echo $user_details->mobile;?>" required="required" data-validate-minmax="10,100" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
                      
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Email</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                         <input type="email" id="email" name="email" disabled="disabled"  value="<?php echo $user_details->email;?>" class="form-control col-md-10">
                        </div>
                      </div>
                     
					         <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
					               <div class="col-md-5 col-sm-9 col-xs-12">
                          <input type="password"  disabled="disabled" value="<?php echo $user_details->password;?>" class="form-control col-md-8"/>  
                        </div>
					         </div>
                  <div class="form-group">
                        <div class="col-md-6 col-md-offset-3">
                         
                          <button id="send" type="submit" class="btn btn-success">Submit</button>
                        </div>
                      </div>
            <?php echo form_close(); ?>
					 
				</div>
				</div>
				
			</div>
		  </div>
		  
		</div>
		   <div class="manual bat" style="display:none;">
			
				 <div class="col-md-6 col-xs-12" style="margin-top: 64px;">
			  
                <div class="x_panel">
				
                  <div class="x_title">
                    <h2>Change Password</h2>
                     <div  style="float: right;">
                      <a class="btn btn-sm btn-success wait" value="auto"><i class="fa fa-edit m-right-xs"></i> EDIT PROFILE</a>
                    </div>
                    
                    <div class="clearfix"></div>
                  </div>
				   
                  <div class="x_content">
                    <br />
					<div class="form-horizontal form-label-left" >
                    <div id="sample_table" >
					<?php echo form_open_multipart('authentication/change_password');?>
					
					  <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">New Password</label>
                        <div class="col-md-6 col-sm-9 col-xs-12">
                          <input type="password"  name="new_password" id="new_password"  class="form-control col-md-10" required="required" />
                        </div>
                      </div>
					  <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Confirm Password</label>
                        <div class="col-md-6 col-sm-9 col-xs-12">
                          <input type="password"  name="confirm_password" id="confirm_password" class="form-control" required="required" /> <span id='message'></span>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-md-6 col-md-offset-3">
                         
                          <button id="send" type="submit" class="btn btn-success">Submit</button>
                        </div>
                      </div>
                    </div>
				</div>
				</div>
				
			</div>
		  </div>
			  </div>
			  
		  
		  
		  
		  
	  </div>
	</div>
	<!-- start of user-activity-graph -->
	<!-- end of user-activity-graph -->
</div>
</div>
</div>
</div>

<script>
// Get the modal
var modal = document.getElementById('myModal');

// Get the image and insert it inside the modal - use its "alt" text as a caption
var img = document.getElementById('myImg');
var modalImg = document.getElementById("img01");
var captionText = document.getElementById("caption");
img.onclick = function(){
    modal.style.display = "block";
    modalImg.src = this.src;
    captionText.innerHTML = this.alt;
}

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() { 
    modal.style.display = "none";
}
</script>
<script>
$('#new_password, #confirm_password').on('keyup', function () {
    if ($('#new_password').val() == $('#confirm_password').val()) {
        $('#message').html('Matching').css('color', 'green');
    } else 
        $('#message').html('Not Matching').css('color', 'red');
});
</script>		
<script>
$("#show").click(function(){
        $("#sample_table").fadeToggle();
    });
</script>

<script type="text/javascript">
$(document).ready(function(){
    $('.wait').click(function(){
        var inputValue = $(this).attr("value");
        var targetBat = $("." + inputValue);
        $(".bat").not(targetBat).hide();
        $(targetBat).show();
    });
});
</script>