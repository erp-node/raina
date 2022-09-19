<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<div class="" style="border: 1px solid black; width:700px;height:400px;margin-left:150px;margin-top: 20px;">
		<div style ="border:1px solid black; width:700px;height:84px;background-color:white;">
			<h2 style="padding-left: 145px;font-size:28px;">NCL Alltek and Seccolor ltd </h2>
		
		</div>
		<div style="border:1px solid black;width:700px;height:318px;background-color:#F2F3F4;">
			<p style="padding-left: 46px;padding-right: 20px;font-size: 21px;font-weight: 400;font-family: serif;">Hi <span><?php echo $name ;?></span>  </p>
			<p style="padding-left: 46px;padding-right: 20px;font-size: 21px;font-weight: 400;font-family: serif;margin-bottom: 45px;"> You told Us You forgot Your Password. if You reallY did click hear to choose a new one :</p>

			<a href="<?php echo base_url('authentication/reset_password/');?><?php echo $user_id ;?> " style="border: 0px solid red; padding: 15px;margin-left: 207px;background-color: #27AE60;color: white;text-decoration: none;font-size: 20px;  border-radius: 18px;">Choose a new password</a>

			<p style="padding-left: 46px;padding-right: 20px;font-size: 21px;font-weight: 400;font-family: serif;margin-top: 60px;">If You didn`t mean to reset Your Password , then You can just Ignore This email; Your password will not change.</p>  

		</div>

	</div>


</body>
</html>