<?
session_start();
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");
$_SESSION["s_userid"] = "Kakao_".$_GET["id"];
$_SESSION["SNS"] = "KAKAO";

$sql = "select * from member where userid = '$_SESSION[s_userid]'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);
if ($rs["idx"]) {
	echo "<script>location.href='/';</script>";
	$_SESSION["userid"] = $_SESSION["s_userid"];
} else {
	echo "<script>location.href='/member/join.php';</script>";
}
?>