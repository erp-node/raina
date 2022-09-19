<link href="http://www.jqueryscript.net/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url('assets/css/file-explore.css');?>" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">



<div class="right_col" role="main" >
  <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/role_list');?>">&nbsp;&nbsp;ROLES LIST</a></li>                                  
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>
<?php $entity_id = $this->uri->segment(2); ?>  
<div style="margin-top: 13px;">

 <?php //if(accessrole($entity_id,P_CREATE)){ ?>
  				          <div class="box-tools pull-right margin_align">
                      <a href="<?php echo base_url('Admin/roles_insert');?>" class="btn btn-success btn-sm">ADD ROLE</a> 
                     </div>
                     <!-- <div class="box-tools pull-right">
                      <a href="<?php echo base_url('Admin/role_bulk');?>" class="btn btn-success btn-sm">ROLES BULKUPLOAD</a> 
                     </div> -->
                    <?php //} ?>
</div>
<div class="clearfix"></div>

            <div class="row">
              

              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  
                    <div class="clearfix"></div>
                 
                  <div class="x_content">

                     <div class="" role="tabpanel" data-example-id="togglable-tabs">
                     
                      <div id="myTabContent" class="tab-content">
                        <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">
                          <div class="box-body">
                <table class="table table-striped table-bordered" id="roles_dt">
                  <thead>
                      <tr>
              <th>Role Id</th>
              <th>Role Name</th>            
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i =1;  foreach($role_list as $values){ ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><a href="<?php echo base_url();?>Admin/role_view/<?php echo $values->role_id;?>"><?php echo $values->role_name;?></a></td>              
           
              <td><a href="<?php echo base_url();?>Admin/role_view/<?php echo $values->role_id;?>"><?php echo $values->status;?></a></td>
              <td>
                <a href="<?php echo base_url();?>Admin/role_view/<?php echo $values->role_id;?>" > <img src="<?php echo base_url('assets/images/view.png');?>"/></a>
                &nbsp; <?php //if(accessrole('Roles',P_UPDATE)){ ?>
                <a href="<?php echo base_url();?>Admin/roles_update/<?php echo $values->role_id;?>" ><img src="<?php echo base_url('assets/images/edit.png');?>"/></a>
                <?php //} ?>
                &nbsp;
                <?php //if(accessrole('Roles',P_DELETE)){?>
                <a onclick="return confirm('Are you sure you want to delete?');" href="<?php echo base_url();?>Admin/roles_delete/<?php echo $values->role_id;?>" ><img src="<?php echo base_url('assets/images/delete.png');?>"/></a>
                <?php //} ?>
              </td>  

            <?php } ?>
          </tbody>
        </table>
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
<script>
  $(document).ready(function() {
      $('#roles_dt').DataTable({
              
    } );
  });
</script>
<!--<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script> 
<script src="<?php //echo base_url('assets/js/file-explore.js');?>"></script> 
<script>
$(document).ready(function() {
            $(".file-tree").filetree();
          });
</script>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36251023-1']);
  _gaq.push(['_setDomainName', 'jqueryscript.net']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script> -->