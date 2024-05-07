<?
@session_start();

$id = "admin";
$pw = md5("qlrejr1!");
if ($_SESSION["a_userid"]) {
	echo "<script>location.href='/admin/ad_manager/ad_main_manage.php';</script>";
	exit;
}

if ($_POST["mode"] == "login") {
	$userid = $_POST["userid"];
	$passwd = md5($_POST["passwd"]);

	if ($userid == $id) {
		if ($passwd == $pw) {
			echo "<script>alert('로그인 되었습니다');location.href='/admin/ad_manager/ad_main_manage.php';</script>";
			$_SESSION["a_userid"] = $_POST["userid"];
		} else {
			echo "<script>alert('비밀번호가 일치하지 않습니다.');location.href='/admin/index.php';</script>";
		}
	} else {
		echo "<script>alert('지정된 계정으로만 로그인 할 수 있습니다.');location.href='/admin/index.php';</script>";
	}
}
?>
<html>
<head>

<title>Vig Deok 관리자 페이지</title>
<link rel="stylesheet" href="/css/admin_style.css">
<script type="text/javascript">
function submit_chk() {
	var f = document.login_form;
	if (f.userid.value == "") {
		alert("아이디를 입력해주세요");
		f.userid.focus();
		return false;
	} else if (f.passwd.value == "") {
		alert("비밀번호를 입력해주세요");
		f.passwd.focus();
		return false;
	} else {
		f.submit();
	}
}
</script>
</head>
<body>
<div class="admin_wrap">
	<span style="font-weight:bold">빅덕 관리자 로그인</span><br /><br />
	<form name="login_form" action="<?=$PHP_SELF?>" method="POST">
		<input type="hidden" name="mode" value="login"/>
		
		<div class="login_info">
			아이디 <input type="text" name="userid" class="userid"/><br />
			비밀번호 <input type="password" name="passwd" class="passwd"/>
		</div>
		<div class="login_btn_div">
			<input type="button" onclick="submit_chk()" value="로그인" class="login_btn"/>
		</div>
		<br /><br />
		<span style="font-weight:bold; color:red">관리자 외 접근시 법적 처벌을 받을 수 있습니다.</span>
	</form>
</div>
</body>
</html>