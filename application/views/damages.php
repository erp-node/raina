 <div class="right_col" role="main">
             <div class="page-title">
              <div class="h2 title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/damages');?>">Damages List &nbsp;&nbsp;</a></li>
               
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>  

				 <div class="col-md-1 col-sm-1  form-group pull-right top_search">
                <a href="<?php echo base_url('Admin/damages_create');?>">  <button  class="btn btn-warning" >Create</button></a>
                </div>			  
            </div>
            <div class="clearfix"></div>

                   <!-- top tiles -->
				   
				   
				   <div class="row"><br>
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   
                  </div>
                  <div class="x_content">
                   <div class="table-responsive">
                    <table class="table table-striped" id="customers_list">
                      <thead>
                        <tr>
                          <th>Registration Number</th>
                          <th>Incident Date</th>
                          <th>Reported Date</th>
                          <th>Booking Ref.</th>
                          <th>Referance No.</th>
                          <th>Circumstances</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>1</td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
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