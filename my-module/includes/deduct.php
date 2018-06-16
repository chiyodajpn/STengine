<?PHP

function deduct() {
	global $conn, $user_id, $balanace;
	$result=mysqli_query($conn, "SELECT `point_id` FROM `wp_edd_points` WHERE `user_id` = '{$user_id}' ORDER BY `point_id` DESC");
	$point_id = mysqli_fetch_row($result);
	if(!empty($point_id) && !empty($point_id[0])) $point_id = $point_id[0] + 1;
	else $point_id = 1;
	
	mysqli_query($conn, "INSERT INTO `wp_edd_points`(`date`,`user_id`,`point_id`,`point_value`) VALUES('".date('Y-m-d H-i-s')."','{$user_id}','{$point_id}','{$balanace}')");
}