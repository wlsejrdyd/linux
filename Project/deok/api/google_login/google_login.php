<?
session_start();
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");

$_SESSION["s_userid"] = $_GET["userid"];
$_SESSION["SNS"] = "GOOGLE";

$sql = "select * from member where email = '$_SESSION[s_userid]'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);
if ($rs["idx"]) {
	echo "<script>location.href='/';</script>";
	$_SESSION["userid"] = $_SESSION["s_userid"];
} else {
	echo "<script>location.href='/member/join.php';</script>";
}
?>