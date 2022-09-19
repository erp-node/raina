<div class="right_col" role="main" >
    <div class="">
       <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/role_list');?>">&nbsp;&nbsp;ROLES LIST</a></li>   
                  <li><a href="<?php echo base_url('Admin/role_view/');?><?php echo $role_list->role_id;?>">&nbsp;&nbsp;ROLES VIEW</a></li>                                  
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>

        <div class="clearfix"></div>

    </div>
	<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">
              <div class="row">
               <div class="col-md-5 ">
                <?php //if(accessrole('Roles',P_UPDATE)){ ?>
                  <a href="<?php echo base_url();?>Admin/roles_update/<?php echo $role_list->role_id;?>" class="btn btn-primary btn-sm pull-right">EDIT</a> 
                <?php //} ?>
                </div>
                <div class="col-md-5">
                  <?php //if(accessrole('Roles',P_DELETE)){ ?>
                  <a href="<?php echo base_url();?>Admin/roles_delete/<?php echo $role_list->role_id;?>" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to delete?');">DELETE</a> <?php //}?>
                </div>
              </div>
              
               <div class='row'>
                  <div class="col-md-12">
                    <h5 class="content_am"><b>Role Information</b></h5>
                  </div>
              </div>



                <div class="table-responsive">
                    <table class="table table-bordered td_dotted">
                       
                            
                     
                        <tbody>
						<tr>
                                <td style="width: 50%;"> <h4> Role Name :</h4><h5><?php echo $role_list->role_name; ?></h5></td>
                                
                            </tr>
                            <tr>                                
                                <td style="width: 50%;"><h4>Status : </h4><h5><?php echo $role_list->status; ?></h5></td>
								
                            </tr>
                
                        </tbody>
                    </table>
                </div>
            
        </div>
    </div>
</div></div>

<div class="row">
              

              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                 <!--   <h2>List Of Employees</h2> -->
                    
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
           <?php 
    $role_id = $this->session->userdata('logged_in')['role_id'];
    if($role_id == 1){

    ?>
     <div class="x_panel">
            <div class="x_content">
          <div class="row">
            <form method="post" id="roleEdit_form" action="<?php echo base_url('Admin/role_permision_edit');?>" enctype="multipart/form-data">
               <div class="col-sm-12">
                  <h3>Role Entities:</h3>
                  <input type="hidden" class="form-control" id="role_id" name="role_id"  readonly value="<?php echo $role_list->role_id;?>">
               </div>
               <div class="col-sm-12">
                  <div class="col-md-3"></div>
                  
                  <div class="col-md-2">
                     <div class='checkbox checkbox-success checkbox-inline'>
                       <input class="icheckbox_flat-green" id='p_read_all' type='checkbox' /><label for='p_read_all'>Select all</label>
                     </div>
                  </div>
          <div class="col-md-2">
                     <div class='checkbox checkbox-success checkbox-inline'>
                        <input  class="icheckbox_flat-green"  id='p_create_all' type='checkbox' /><label for='p_create_all'>Select all</label>
                     </div>
                  </div>
                  <div class="col-md-2">
                     <div class='checkbox checkbox-success checkbox-inline'>
                        <input class="icheckbox_flat-green"  id='p_update_all' type='checkbox'   /><label for='p_update_all'>Select all</label>
                     </div>
                  </div>
                  <div class="col-md-2">
                     <div class='checkbox checkbox-success checkbox-inline'>
                        <input class="icheckbox_flat-green"  id='p_delete_all' type='checkbox' /><label for='p_delete_all'>Select all</label>
                     </div>
                  </div>
               </div>
              <?php $i=1;foreach($roll_entity as $values){
                $role_permissions_list = $this->db->query("select * from role_permissions where entity_id ='".$values->entity_id."' and role_id = '".$role_list->role_id."'" )->row();
                ?>
               <div class='col-sm-12 setting_edit_padding'>
                <div class='col-md-3'>
                   <div class='checkbox checkbox-success checkbox-inline class_1'><input type='hidden'  id='entity_id_<?php echo $values->entity_id;?>' 
                  class='entity_module' name='entity_id[]'' value='<?php echo $values->entity_id;?>' checked/><label for='entity_id_<?php echo $values->entity_id;?>'><b><?php echo strtoupper($values->user_entity_name);?></b></label>
                  </div>
                </div>
                  <div class='col-md-2'>
                    <div class='checkbox checkbox-success checkbox-inline'><input class="icheckbox_flat-green read_checkall comclass  p_read" id='p_read_<?php echo $values->entity_id;?>' type='checkbox' name='p_read_<?php echo $values->entity_id;?>' value='1' <?php if($role_permissions_list->p_read == 1){echo "checked";}?>/><label for='p_read_<?php echo $values->entity_id;?>'> Read </label>
                    </div>
                   
                  </div>
                  <div class='col-md-2'>
                    <div class='checkbox checkbox-success checkbox-inline'><input class="icheckbox_flat-green create_checkall comclass create" id='p_create_<?php echo $values->entity_id;?>'  type='checkbox'  name='p_create_<?php echo $values->entity_id;?>' value='1' <?php if($role_permissions_list->p_create == 1){echo "checked";}?>/><label for='p_create_<?php echo $values->entity_id;?>'> Create </label>
                    </div>
                  </div>
                  <div class='col-md-2'>
                     <div class='checkbox checkbox-success checkbox-inline'><input class="icheckbox_flat-green update_checkall comclass update" id='p_update_<?php echo $values->entity_id;?>'  type='checkbox'  name='p_update_<?php echo $values->entity_id;?>' value='1' <?php if($role_permissions_list->p_update == 1){echo "checked";}?>/><label for='p_update_<?php echo $values->entity_id;?>'> Update </label>
                    </div>
                  </div>
                  <div class='col-md-2'>
                    <div class='checkbox checkbox-success checkbox-inline'><input class="icheckbox_flat-green delete_checkall comclass delete" id='p_delete_<?php echo $values->entity_id;?>' type='checkbox'  name='p_delete_<?php echo $values->entity_id;?>' value='1' <?php if($role_permissions_list->p_delete == 1){echo "checked";}?>/><label for='p_delete_<?php echo $values->entity_id;?>'> Delete </label>
                    </div>
                  </div>
                </div>
                  <?php $i++; } ?>
                </div>
              <br/>
               <div class="row setting_topmar">
                  <br><br/>
                   <div class="col-sm-6">
                   <button type="submit" class="btn btn-success" id="save" name="Save" value="Save" >Save</button>
                   <button type="button" class="btn btn-warning" id="cancel" name="cancel"  value="Cancel" onclick="window.history.go(-1);">Cancel</button>
                  <br><br>
               </div>
          </div>
        </form>
      </div>
    </div>
</div>
</div>
<?php } ?>
            
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

<script type="text/javascript" src="http://suprasoftapp.com/nsl_dev/assets/js/jquery.js"></script>
<script type="text/javascript">
   //read
$("#p_read_all").change(function(){   
    $(".p_read").prop('checked', $(this).prop("checked")); 
});
$('.p_read').change(function(){ 
    if(false == $(this).prop("checked")){ 
        $("#p_read_all").prop('checked', false); 
    }
    if ($('.p_read:checked').length == $('.checkbox').length ){
        $("#p_read_all").prop('checked', true);
    }
});


 //create
$("#p_create_all").change(function(){  
    $(".create").prop('checked', $(this).prop("checked")); 
});
$('.create').change(function(){ 
    
    if(false == $(this).prop("checked")){ 
        $("#p_create_all").prop('checked', false); 
    }
    if ($('.create:checked').length == $('.checkbox').length ){
        $("#p_create_all").prop('checked', true);
    }
});

//update
$("#p_update_all").change(function(){  
    $(".update").prop('checked', $(this).prop("checked")); 
});
$('.update').change(function(){ 
    if(false == $(this).prop("checked")){ 
        $("#p_update_all").prop('checked', false); 
    }
    if ($('.update:checked').length == $('.checkbox').length ){
        $("#p_update_all").prop('checked', true);
    }
});

//delete
$("#p_delete_all").change(function(){  
    $(".delete").prop('checked', $(this).prop("checked")); 
});
$('.delete').change(function(){ 
    if(false == $(this).prop("checked")){ 
        $("#p_delete_all").prop('checked', false); 
    }
    if ($('.delete:checked').length == $('.checkbox').length ){
        $("#p_delete_all").prop('checked', true);
    }
});
    </script>


 