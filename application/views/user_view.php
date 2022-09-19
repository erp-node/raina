<div class="right_col" role="main">
    <div class="">
       <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/dashboard');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/user_list');?>">&nbsp;&nbsp;USER LIST</a></li>   
                  <li><a href="<?php echo base_url('Admin/user_view/');?>">&nbsp;&nbsp;USER VIEW</a></li>  
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
                <?php //if(accessrole('Users',P_UPDATE)){ ?>
                  <a href="<?php echo base_url();?>Admin/user_edit/<?php echo $user_list->user_id;?>" class="btn btn-primary btn-sm pull-right">EDIT</a> 
                <?php //} ?>
                </div>
                <div class="col-md-5">
                  <?php //if(accessrole('Users',P_DELETE)){ ?>
                  <a href="<?php echo base_url();?>Admin/user_delete/<?php echo $user_list->user_id;?>" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to delete?');">DELETE</a> <?php //}?>
                </div>
              </div>
              
                <div class='row'>
                        <div class="col-md-12">
                          <h5 class="content_am"><b>User Information</b></h5>
                        </div>
                      </div>



                <div class="table-responsive">
                    <table class="table table-bordered td_dotted">
                        <tbody>

                             <tr>
                              
                                <td> <h4>User Name  : </h4><h5><?php echo $user_list->first_name; ?></h5></td>
								<td style="width: 50%;"> <h4>Email : </h4><h5><?php echo $user_list->email; ?></h5></td>
                                 
                            </tr>
                            <tr>
                                
                                
                                <td> <h4>Mobile : </h4><h5><?php echo $user_list->mobile; ?></h5></td>
								<td> <h4>Role : </h4><h5><?php echo $user_list->role_name; ?></h5></td>
                            </tr>
            								
                           
                           
                                    <tr>
                                    
                                      <td> <h4>status : </h4><h5><?php if($user_list->status==0){echo "In Active";}else{echo "Active";} ?></h5></td>

                                    </tr>
            					                           
            								
								
            								
                        </tbody>
                       
                    </table>
                </div>
            
        </div>
    </div>
</div>
</div></div>

 