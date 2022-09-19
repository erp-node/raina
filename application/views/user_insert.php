<div class="right_col" role="main">
  
          <div class="">
            <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/user_list');?>">&nbsp;&nbsp;USER LIST</a></li>
                  <?php if(isset($users_list) && !empty($users_list)){?>
                      <li><a href="#">&nbsp;&nbsp;USER EDIT</a></li>  
                  <?php }else{?>
                      <li><a href="<?php echo base_url('Admin/user_insert');?>">&nbsp;&nbsp;USER ADD</a></li>  
                  <?php } ?>
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">                
                    <div class="clearfix"></div>                
                  <div class="x_content">
                     <?php if(isset($users_list) && !empty($users_list)){
                      echo form_open("admin/user_edit/".$users_list->user_id."");
                     }else{
                      echo form_open("admin/user_insert");
                     }
                  ?>
                    <div class="box-body">
                      <div class='row'>
                        <div class="col-md-12">
                          <h5 class="content_am"><b>User Information</b></h5>
                        </div>
                      </div>
                      <div class="row clearfix form-horizontal form-label-left">
                  <div class="form-group">
                    <label for="profile_name" class="control-label col-md-1">User Name<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                      <input type="text" name="first_name" value="<?php echo $users_list->first_name;?>" class="form-control"  id="first_name" required="required" autocomplete='off'/>
                    </div>
                   
                  </div>
				  <div class="form-group">

             <label for="profile_name" class="control-label col-md-1">Email<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                      <input type="email" name="email" value="<?php echo $users_list->email;?>" class="form-control"  id="Email" required="required" <?php if(!empty($users_list->email)){echo "readonly";}?> autocomplete='off'/>
                    </div> 
                    <label for="mobile" class="control-label col-md-1">Mobile<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                      <input type="text" name="mobile" value="<?php echo $users_list->mobile;?>" class="form-control"  id="mobile" required="required" autocomplete='off'/>
                    </div>
                    
                  
                  </div>
				 




  <div class="form-group">
                   
                    <label for="profile_name" class="control-label col-md-1">Password<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                      <input type="password" name="password" value="<?php echo base64_decode($users_list->password);?>" class="form-control"  id="password" required="required"/>
                    </div>
                 

                
         <label for="profile_name" class="control-label col-md-1">Role<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                       <select class="form-control" name="role_id" id="role_id" required="required">
                        <option value="">--Select--</option>
                        <?php foreach($role_list as $role_values) {?>
                          <option value="<?php echo $role_values->role_id;?>" <?php if($users_list->role_id ==  $role_values->role_id){echo"selected";} ?>><?php echo $role_values->role_name ;?> </option>

                       <?php  }?>
                       </select>



                    </div>        
				 
				  


                 
                  </div>
				
				        
				        <div class="form-group">
                   
                  </div>
				          
				      <div class="form-group">
                    
                    <label for="status" class="control-label col-md-1">Status<span style="color:red;">*</span></label>
                    <div class=" col-md-5">
                      <select class="form-control" name="status" id="status" required="required">
                        <option value="">--Select--</option>
                        <option value="0" <?php if($users_list->status == "0"){echo "selected" ;} ?> >InActive</option>
                        <option value="1" <?php if($users_list->status == "1"){echo "selected" ;} ?>>Active</option>
                      </select>
                    </div>
                   
                  </div>
                
					
					
					
					
				  
				  
				
				
				
				
              </div>
              <div class="col-md-12">
                <div class="col-md-6">
                  <div class="pull-right">
                      <button type="submit" class="btn btn-success">
                        <i class="fa fa-check"></i> Save
                      </button>
                  </div>
                </div>
                <div class="col-md-16">
                      <a href="javascript:void(0)" onClick="window.history.go(-1)"> <button type="button" class="btn btn-danger">
                      <i class="fa fa-close"></i> cancel
                      </button></a>
                </div>
              </div>
                   


                    <?php echo form_close();?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

<script>
  $(document).ready(function(){
    $("#role_id").change(function() {
    var base_url = '<?php echo base_url(); ?>';
    var role_id = $("#role_id").val();
     $.ajax({
      url : base_url+"/Admin/emp_role_report",
      method : "POST",
      data : {"role_id":role_id},
        success : function(data) { 
         employees = data;
         $("#emp_manager_id").html(employees);
        }
      })
     });

  });


</script>
<script type="text/javascript">
$(function() {
    $('.multiselect-ui').multiselect({
        includeSelectAllOption: true
    });
});
</script>
      
