<?
$title = "로그인 | 빅덕(Vig Deok)";
$description = "빅덕(Vig Deok)에 오신것을 환영합니다! 회원이 아니신경우 회원가입을 통해 서비스를 이용해주세요.";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");
?>
<script type="text/javascript">
function submit_chk() {
	var f = document.login_form;

	if (f.userid.value == "") {
		alert("아이디를 입력해주세요!");
		f.userid.focus();
		return false;
	} else if (f.password.value == "") {
		alert("비밀번호를 입력해주세요!");
		f.password.focus();
		return false;
	} else {
		f.submit();
	}
}
</script>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px;">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>로그인</h3>
			</div>
			<div style="margin:auto; width:100%; text-align:center;">
				<h3>빅덕(VigDeok)에 오신것을 환영합니다!</h3>
				<!-- login_form:s -->
				<form name="login_form" action="/process/process.php" method="POST">
					<input type="hidden" name="mode" value="login" />
					<div class="login">
						<div style="margin-bottom:10px; display:inline-block">
							<input type="text" name="userid" id="userid" style="width:100%" placeholder="아이디"/>
						</div><br />
						<div style="display:inline-block">
							<input type="password" name="password" id="password" style="width:100%" placeholder="비밀번호" />
						</div>
					</div>
					<div style="display:inline-block">
						<a href="javascript:submit_chk()"><input type="button" class="login_btn" value="로그인" /></a>
					</div>
				</form>
				<!-- login_form:e -->
			</div>
			<div style="border-top:2px solid black; margin:20px auto; width:50%; padding:10px 0 10px 0">
				<?if ($phone_chk != "y") { ?><span>아직 회원이 아니신가요?</span> <? } ?><a href="/member/join.php"><input type="button" class="etc_btn" value="회원가입" /></a><br />	
				<?if ($phone_chk != "y") { ?><span>아이디/비밀번호가 기억이 나지 않나요?</span> <? } ?><a href="/member/search_userinfo.php"><input type="button" class="etc_btn" value="아이디/비밀번호 찾기" /></a>
			</div>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>