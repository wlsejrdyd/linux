<?
session_start();
if ($_SESSION["SNS"]) {
	unset($_SESSION["naver_state"]);
}
unset($_SESSION["userid"]);
unset($_SESSION["s_userid"]);
unset($_SESSION["SNS"]);

echo "<script>alert('로그아웃 되었습니다.');location.href='/';</script>";
?>