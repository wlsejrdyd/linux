<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");

$mode = $_POST["mode"];

if ($mode == "duplicate_id") {
	$userid = $_POST["userid"];

	$sql = "select count(*) from member where userid = '$userid'";
	$res = mysqli_query($conn, $sql);
	$rs = mysqli_fetch_array($res);

	if ($rs[0] > "0") {
		echo "0";
	} else {
		echo "1";
	}
}