<div class="right_col" role="main">
   <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/role_list');?>">&nbsp;&nbsp;ROLES LIST</a></li>
                  <?php  if(isset($roles_list) && !empty($roles_list)){ ?>
                    <li><a href="<?php echo base_url('Admin/roles_update/');?><?php echo $roles_list->role_id;?>">&nbsp;&nbsp;ROLES EDIT</a></li>
                  <?php }else{ ?>
                    <li><a href="<?php echo base_url('Admin/roles_insert');?>">&nbsp;&nbsp;ROLES ADD</a></li>
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
                <?php       
                if(isset($roles_list) && !empty($roles_list))
                   echo form_open('Admin/roles_update/'.$roles_list->role_id); 
                 else
                  echo form_open('Admin/roles_insert'); 
                ?>
            <div class="box-body">
              <div class='row'>
                  <div class="col-md-12">
                    <h5 class="content_am"><b>Role Information</b></h5>
                  </div>
              </div>
              <div class="row clearfix form-horizontal form-label-left">
          <div class="form-group">
            <label for="role_name" class="control-label col-md-1">Role Name </label>
            <div class="col-md-5">
              <input type="text" name="role_name"  value="<?php echo $roles_list->role_name;?>" class="form-control" id="role_name" pattern="^[a-zA-Z0-9][\sa-zA-Z0-9]*" required="required" />
            </div>
           <!-- <label for="role_description" class="control-label col-md-1">Role&nbsp;Reports&nbsp;to</label>
            <div class="col-md-5">
              <select name="role_reports_to" id="role_reports_to" class="form-control">
                            <option value="0"  selected> Self</option>
              <?php foreach($role as $roles){?>
              <option value="<?=$roles->role_id; ?>" <?php if($roles->role_id == $roles_list->role_reports_to){echo "selected";}?>><?=$roles->role_name?></option>
              <?php } ?>
              </select>
            </div>-->
          </div>
           <div class="form-group">
            <label for="status" class="control-label col-md-1">Status</label>
            <div class="col-md-5">
              <select class="form-control" name="status" id="role_status">
                <option value="">--Select--</option>
                <option value="InActive" <?php if($role_list->status == "InActive"){ echo "Selected";}?> >InActive</option>
                <option value="Active" <?php if($role_list->status == "InActive"){ echo "Selected";}else{ echo "selected";}?>>Active</option>
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
            <div class="col-md-6">
                <a href="javascript:void(0)" onClick="window.history.go(-1)"> <button type="button" class="btn btn-danger">
                  <i class="fa fa-close"></i> cancel
                </button></a>
            </div>
          </div>
            <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>
</div></div></div