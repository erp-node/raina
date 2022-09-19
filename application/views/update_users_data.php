<div class="right_col" role="main">


<div class="page-title">
              <div class="title_left" id="in_voice">
                <ul class="breadcrumb" id="breadcrumb">
                  <li><a href="<?php echo base_url('Admin/dashboard');?>">HOME</a></li>
                  <li><a href="<?php echo base_url('Admin/transfer_user_data');?>">&nbsp;&nbsp;TRANSFER USERS DATA LIST</a></li>
                          
                
                  
                  <li style=" display: none;"><a href="#"></a></li>
                </ul>
              </div>             
            </div>
			
			
			 <div class="clearfix"></div>
			 <form action="<?php echo base_url('Admin/update_user_data');?>" METHOD="POST" >
            <div class="row">
			
			<div class="form-group">
                    <label for="Description" class=" control-label col-md-1">From user</label>
                    <div class="col-md-5">
                      <input type="number" name="old_user_id" value="" class="form-control"  id="old_user_id" required />
                    </div>
                    
                  </div>
			</div>
			<div class="clearfix"></div>
			   <div class="row">
			   
			   <div class="form-group">
                    <label for="Description" class=" control-label col-md-1">To User</label>
                    <div class="col-md-5">
                      <input type="text" name="new_user_id" value="" class="form-control"  id="new_user_id"  required />
                    </div>
                    
                  </div>
			   </div>
			<button type="submit" name="submit" class="btn btn-success">Save</button> 
			</form>
			
			 <form action="<?php echo base_url('Admin/get_users_data_count');?>" METHOD="POST" >
			<div class="row">
			   
			   <div class="form-group">
                    <label for="Description" class=" control-label col-md-1">Users data</label>
                    <div class="col-md-5">
                      <input type="text" name="user_id" value="" class="form-control"  id="user_id"  required />
                    </div>
                    
                  </div>
			   </div>
			   <button type="submit" name="submit">Get users data count</button> 
			   </form>
			   <table class="bordered"><thead><th>Table name</th> <th>Count</th>
			   </thead>
			   <tbody>
			   <tr><td>Leads</td> <td><?php echo $lead_count?></td></tr>
			   <tr>
			    <td>opportunities</td> <td><?php echo $opportunities?></td></tr>
				<tr><td>customers</td> <td><?php echo $customers?></td></tr>
				<tr><td>sales Orders</td> <td><?php echo $sales_order?></td></tr>
				<tr><td>sales Calls</td> <td><?php echo $sales_call?></td></tr>
				<tr><td>contacts</td> <td><?php echo $contacts?></td></tr>
				<tr><td>payment collection</td> <td><?php echo $payment_collection?></td></tr>
				<tr><td>quotation</td> <td><?php echo $quotation?></td></tr>
			    <tr><td>contract</td> <td><?php echo $contract?></td></tr>
			    <tr><td>complaints</td> <td><?php echo $complaints?></td></tr>
				<tr><td>Expenses</td> <td><?php echo $expenses?></td></tr>
				<tr><td>Geo tracking</td> <td><?php echo $geo_tracking?></td></tr>
				<tr><td>Notiffication</td> <td><?php echo $notiffication?></td></tr>
			   </tr>
			   </tbody>
			   </table>
			</div>