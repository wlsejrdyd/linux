<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");

if ($_GET["mode"]) {
	$mode = $_GET["mode"];
} else if ($_POST["mode"]) {
	$mode = $_POST["mode"];
}

$userid = $_POST["userid"];
$subject = $_POST["subject"];
$contents = $_POST["c_contents"];
$category = $_POST["category"];
$idx = $_POST["idx"];

if ($mode == "counseling") {
	$sql = "update counseling_list set userid = '$userid', category='$category', subject = '$subject', contents = '$contents', a_reg_date = now() where idx = '$idx'";
	mysqli_query($conn, $sql);
	echo "<script>alert('수정을 완료했습니다.');location.href='/admin/counseling_list.php';</script>";
} else if ($mode == "counseling_del") {
	$sql = "delete from counseling_list where idx = '$_GET[idx]'";
	mysqli_query($conn, $sql);
	echo "<script>alert('삭제되었습니다.');location.href='/admin/counseling_list.php';</script>";
}