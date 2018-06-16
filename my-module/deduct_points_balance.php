<?php
	include("includes/db.php");

	if(!empty($_GET)){
		try {
			$user_id = $_REQUEST['id'];
			$balanace = $_REQUEST['pt'];
			
			//wp_edd_customers テーブルから 現在のポイントを取得
			$result=mysqli_query($conn, "select purchase_value from wp_edd_customers where user_id='".$user_id."'");
			$row=mysqli_fetch_array($result);
			if(empty($row)){
				print('{"error":"could not find customer for given id."}');
				exit;
			}else{
				$purchase_value = $row['purchase_value'];
				if($purchase_value >= $balanace){
					//現在のポイントから減
					$result=mysqli_query($conn, "update wp_edd_customers set purchase_value = (purchase_value - $balanace) where user_id='".$user_id."'");
					//更新された情報を取得
					$result=mysqli_query($conn, "select purchase_value, email from wp_edd_customers where user_id='".$user_id."'");
					$row=mysqli_fetch_array($result);
					print json_encode($row);
					exit;
				}else{
					print('{"error":"Service usage point is insufficient. Please purchase usage rights at structuralengine.com"}');
				}
			}
		} catch (Exception $e) {
			print(json_encode(array("error" => $e->getMessage())));
		}
	  
	} else {
		print('{"error":"The call is not appropriate"}');
	} 
	exit;		
?>