 <div class="right_col" role="main">
             <div class="page-title">
              <div class="h2 title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
				 <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/vehical');?>">Vehicles &nbsp;&nbsp;</a></li>
               
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
				
              </div> 
			  
            </div>
            <div class="clearfix"></div>

                   <!-- top tiles -->
				   
				   
				   <div class="row">
              <div class="row title_right">
			  <div class="col-md-1 col-sm-1">&nbsp;&nbsp;&nbsp;</div>&nbsp;
				 <div class="col-md-2 col-sm-2  form-group pull-right top_search">
                <a href="<?php echo base_url('Admin/vehical_create');?>">  <button  class="btn btn-warning " >Add a vehicle</button></a>
                </div>
				
              </div><br>
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   
                  </div>
                  <div class="x_content">
                  <div class="table-responsive">
                   
                     <table class="table table-striped" id="customers_list">
                      <thead>
                        <tr>
                          <th>Vehicle</th>
                          <th>Body type</th>
                          <th>Status</th>
                          <th>Fuel type</th>
                          <th>Action</th>
                        </tr>
                      </thead>


                      <tbody>
                        <tr>
                          <td> Van <img class="img_inventory" src="../assets/img/small.png"></td>
                          <td>Small</td>
                          <td>Active</td>
                          <td>CNG</td>
                          <td><a href="#" style="color:red;"><u>Delete</u></a></td>
                        </tr>
                     <tr>
                          <td> Van <img class="img_inventory" src="../assets/img/medium.png"></td>
                          <td>Medium</td>
                          <td>In Active</td>
                          <td>Petrol</td>
                          <td><a href="#" style="color:red;"><u>Delete</u></a></td>
                        </tr>
						
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
    $('#customers_list').DataTable({
      dom: 'Bfrtip',
	  fixedHeader: true,
	  pagingType: 'full_numbers',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

</script>