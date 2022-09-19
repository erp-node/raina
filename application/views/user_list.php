<?php $entity_id='Users';?>
<div class="right_col" role="main">

  <div class="page-title">
  <div class="row">
              <div class="col-md-10 title_left">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/Profile_list');?>">&nbsp;&nbsp;USER LIST</a></li>   
                  <li style=" display: none;"><a href="#"></a></li>
                
				</ul>
				 </div> 
				  <div class="col-md-2  pull-right" style="margin-top: 13px;">
             <?php //if(accessrole($entity_id,P_CREATE)){ ?> 
				      <div class="box-tools pull-right">
                  <a href="<?php echo base_url('Admin/user_insert');?>" class="btn btn-success btn-sm">ADD USER</a> 
              </div>
              <?php //}?>
         
  
                       </div> </div> 
                </div>  
<div class="clearfix"></div>
		  

            <div class="row">
              

              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  
                    <div class="clearfix"></div>
               
                  <div class="x_content">
                   
            <div class="box-body">
                <table class="table table-striped table-bordered" id="profile_dt">
                	<thead>
	                    <tr>
          							  <th>S.No</th>
                     
          							   <th>User Name</th>
                           
        									 <th>Email</th>
        									 <th>Mobile</th>        								
        									 <th>Role</th>
        									
                           <th>Status</th>
									         <th>Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                    <?php $i=1; foreach($user_list as $values){ 
                      ?>
                      <tr>
                        <td><?php echo $i++; ?></td>
                       
						
                         <td><a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" ><?php echo $values->user_name; ?></a></td>
                         
                         <td><a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" ><?php echo $values->email; ?></a></td>
                         <td><a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" ><?php echo $values->mobile; ?></a></td>                         
                         <td><a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" ><?php echo $values->role_name; ?></a></td>
                         
                         <td><a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" ><?php

                         if($values->status=='1'){$status='Active';}else{

                          $status='In Active';

                         }
                          echo $status; ?></a></td>
                         <td>
                          <a href="<?php echo base_url();?>Admin/user_view/<?php echo $values->user_id;?>" > <img src="<?php echo base_url('assets/images/view.png');?>"/></a>
                            &nbsp;
                         <?php //if(accessrole($entity_id,P_UPDATE)){ ?>
                          <a href="<?php echo base_url();?>Admin/user_edit/<?php echo $values->user_id;?>" ><img src="<?php echo base_url('assets/images/edit.png');?>"/></a>
                        <?php //} ?>
                         
                          <?php $chk_super_admin_user = $this->session->userdata('logged_in')['user_id'];

                        // if(accessrole($entity_id,P_DELETE) && $chk_super_admin_user =='1'){ ?>
                        
                          <a onclick="return confirm('Are you sure you want to delete?');" href="<?php echo base_url();?>Admin/user_delete/<?php echo $values->user_id;?>"><img src="<?php echo base_url('assets/images/delete.png');?>"/></a>
                        <?php //} ?>
                        </td>
                      </tr>

                    <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<script>
  $(document).ready(function() {
      $('#profile_dt').DataTable({
       
      });
  });
</script>