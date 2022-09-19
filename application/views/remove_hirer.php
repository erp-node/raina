    <div class="right_col" role="main">
             <div class="page-title">
              <div class="h2 title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  
				<li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/remove_hirer');?>">Remove Hirers List&nbsp;&nbsp;</a></li>
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>
            <div class="clearfix"></div>

                   <!-- top tiles -->
				   
				   
				   <div class="row">
              <div class="row title_right">
			  
			<!--	 <div class="col-md-1 col-sm-1  form-group pull-right top_search">
                <a href="create_customers_individual.html">  <button  class="btn btn-warning" >Create</button></a>
                </div> -->
				
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
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Email</th>
                          <th>Phone Number</th>
                          <th>Action</th>
                        </tr>
                      </thead>


                      <tbody>
                        <tr>
                          <td>1</td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td><a href="#">  <button  class="btn btn-danger" >Remove</button></a>&nbsp;</td>
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