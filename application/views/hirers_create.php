 <div class="right_col" role="main">
             <div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/index');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/hirers');?>">Hirers &nbsp;&nbsp;</a></li>
                  <li><a href="<?php echo base_url('Admin/hirers_create');?>">Create Hirers &nbsp;&nbsp;</a></li>
               
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>
            <div class="clearfix"></div>

                   <!-- top tiles -->
				   
				   
				    <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>New Hirer Details</h2>
                  
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <br />
                    <form id="demo-form2" action="#"  class="form-horizontal form-label-left">
                 <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">First Name <span class="required">*</span>
                        </label>
                        
                         <input type="text" class="form-control" name="first_name" id="first_name" value="">
                        
                        </div>
                      </div>
                      <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Last Name <span class="required">*</span>
                        </label>
                        
                        <input type="text" class="form-control" name="last_name" id="last_name" value="">
                        </div>
                      </div>
					   <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Phone number <span class="required">*</span>
                        </label>
                        
                       <input type="text" class="form-control" name="phone_num" id="phone_num" value="">
                        </div>
                      </div>
                       <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Secondary phone number
                        </label>
                        
                          <input type="text" class="form-control" name="sec_phone_num" id="sec_phone_num" value="">
                        </div>
                      </div>
					   <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Email address <span class="required">*</span>
                        </label>
                        
                          <input type="email" class="form-control" name="email" id="email" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Secondary email address </label>
                          <input type="email" class="form-control" name="sec_email" id="sec_email" value="">
                        </div>
                      </div>
					   <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Gender <span class="required">*</span>
                        </label>
                        
                        <select class="form-control" name="gender" id="gender">
						
						<option value="Male">Male </option>
						<option value="Femail">Femail</option>
						
						</select>
                        </div>
                      </div>
					  <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">National insurance number</label>
                          <input type="text" class="form-control" name="national_insurance_num" id="national_insurance_num" value="">
                        </div>
                      </div>
					  <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Profession </label>
                          <input type="text" class="form-control" name="profession" id="profession" value="">
                        </div>
                      </div>
					   <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Driving licence number</label>
                          <input type="email" class="form-control" name="driving_licence" id="driving_licence" value="">
                        </div>
                      </div>
					   <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">PCO/private hire DL NO </label>
                          <input type="email" class="form-control" name="pco_hire_dlno" id="pco_hire_dlno" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Licence issued by</label>
                          <input type="email" class="form-control" name="licence_issued_by" id="licence_issued_by" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Driving licence expirity date</label>
                          <input type="email" class="form-control" name="driving_licence_exp_date" id="driving_licence_exp_date" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Date of birth </label>
                          <input type="email" class="form-control" name="date_of_birth" id="date_of_birth" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Address </label><span class="required">*</span>
                          <input type="email" class="form-control" name="address" id="address" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
                        <label  for="">Account number </label>
                          <input type="email" class="form-control" name="account_num" id="account_num" value="">
                        </div>
                      </div>
					    <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
					   <label  for="">&nbsp; UPLOAD LICENCE IMAGE </label>
                    <input type="file" id="licence_img" name="licence_img"><p>Upload front and back image of licence.<br> Supported file format: .png, .jpeg, .jpg</p>
                        </div>
                      </div>
					  <div class="col-md-4 col-sm-4 col-xs-12">
                      <div class="form-group">
					   <label  for="">&nbsp; UPLOAD CUSTOMER IMAGE </label>
                    <input type="file" id="customer_img" name="customer_img"><p>Upload a image of the customer.<br> Supported file format: .png, .jpeg, .jpg</p>
                        </div>
                      </div>
					 
                   <div class="col-md-4 col-sm-4 col-xs-12">
                       <label  for=""> <span class="required"></span> </label>
                     <div class="form-group">
                            <button type="submit" class="btn btn-success">Save</button>
                          </div>
                      </div>

                    </form>
                  </div>
                </div>
              </div>
            </div>
		
		
      </div>
   
	  
	  	‎ <script type="text/babel">
            function Message() {
                return  <select class="form-control">
						<option> </option>
						<option>During Booking </option>
						<option>During Transit </option>
						<option>During Movement </option>
						</select>;
            }
            ReactDOM.render(
              <Message/>,
              document.getElementById('root')
            );
           
        </script>
	  
	<script>
   $('#date_of_birth').datetimepicker({
		timepicker:false,
        format:'d.m.Y',
    });
</script>  
