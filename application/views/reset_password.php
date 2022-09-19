<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <style type="text/css">
  </style>

</head>

<body>
<div class="container">
	<div class="row" style="margin-top: 80px;">
		<div class="col-sm-4 col-sm-offset-4">
	
		<h3> Password Reset</h3>

	<form action="<?php echo base_url('Authentication/change_password'); ?>" method="post" enctype="multipart/form-data">
  
		
			<div class="form-group pass_show">
				<input type="hidden" class="form-control" name="user_id" value="<?php echo $id ?>">
			</div>
		<label>Password:</label>
			<div class="form-group pass_show"> 
				<input type="password" class="form-control" name="new_password">
			</div>
		<label>Confirm Password:</label> 
			<div class="form-group pass_show"> 
				<input type="password" class="form-control" name="confirm_password">
			</div>
		<input type="submit" name="submit" value="submit" class="btn btn-primary" style="background-color: #4b71fa !important; border-color: #4b71fa !important; color: #fff !important;"><br>

		
	</form>
</div>  
</div>
</div>
	
	</body>

</html>