<?
$title = "회원가입 | 빅덕(Vig Deok)";
$description = "빅덕(Vig Deok)에 오신것을 환영합니다! 회원이 아니신경우 회원가입을 통해 서비스를 이용해주세요.";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");
$userid = explode("@", $_SESSION["s_userid"]);

$email_arr = array("naver.com", "daum.net", "gmail.com");
?>
<script type="text/javascript">
function submit_chk() {
	var f = document.join_form;
	
	if (f.email_1.value == "") {
		alert("이메일을 입력해주세요!");
		f.email_1.focus();
		return false;
	} else if (f.email_2.value == "") {
		alert("이메일을 선택해주세요!");
		f.email_2.focus();
		return false;
	} <? if (!$_SESSION["SNS"]) { ?>
	
	else if (f.userid.value == "") {
		alert("아이디를 입력해주세요!");
		f.userid.focus();
		return false;
	} else if (f.password.value == "") {
		alert("패스워드를 입력해주세요!");
		f.password.focus();
		return false;
	} else if (f.dup_chk.value == "0") {
		alert("아이디 중복확인을 해주세요!");
		f.userid.focus();
		return false;
	} else if (f.pw_chk.value == "0") {
		alert("비밀번호가 일치하지 않습니다!");
		f.password.focus();
		return false;
	}
	<? } ?>
	else {
		f.submit()
	}
}

function duplicate_chk() {
	var userid = $("#userid").val();
	var mode = "duplicate_id";
	
	if (userid == "") {
		alert("아이디를 입력해주세요!");
		document.getElementById("userid").focus();
		return false;
	} else {
		$.ajax({
			type:"post",
			url:"/ajax/duplicate_id_chk.php",
			data:"mode="+mode+"&userid=" + userid,
			success:function(msg) {
				if (msg == "1") {
					alert("사용가능한 아이디 입니다.");
					$("#dup_chk").val("1");
				} else {
					alert("이미 사용중인 아이디입니다.");
					$("#dup_chk").val("0");
				}
			}
		});
	}
}

$(function(){
	$("#password_chk").focusout(function() {
		$(".result").empty();
		var pw = $("#password").val();
		var pw_chk = $("#password_chk").val();
		
		if (pw == pw_chk) {
			result_txt = "<span style='color:blue'>비밀번호가 일치합니다.</span>";
			$("#pw_chk").val("1");
		} else {
			result_txt = "<span style='color:red'>비밀번호가 일치하지 않습니다.</span>";
			$("#pw_chk").val("0");
		}
		$(".result").append(result_txt);
	});
});
</script>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px;">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>회원가입</h3>
			</div>
			<form name="join_form" action="/process/process.php" method="POST">
			<input type="hidden" name="mode" value="join" />
			<input type="hidden" id="dup_chk" name="dup_chk" value="0" />
			<input type="hidden" id="pw_chk" name="pw_chk" value="0" />
			<? if ($_SESSION["SNS"]) { ?>
				<input type="hidden" name="SNS" value="<?=$_SESSION["SNS"]?>" />
				<? if ($_SESSION["SNS"] == "KAKAO") { ?>
				<input type="hidden" name="s_userid" value="<?=$_SESSION["s_userid"]?>" />
				<? } ?>
			<? } ?>

			<table cellpadding="0" cellspacing="0" class="join_table">
			<colgroup>
				<col width="35%" />
				<col width="*" />
			</colgroup>
			<? if (!$_SESSION["SNS"]) { ?>
			<tr>
				<th>아이디</th>
				<td>
					<input type="text" name="userid" id="userid"/> <a href="javascript:duplicate_chk()"><input type="button" class="etc_btn" value="중복확인" /></a>
				</td>
			</tr>
			<tr>
				<th>비밀번호</th>
				<td><input type="password" id="password" name="password" /></td>
			</tr>
			<tr>
				<th>비밀번호 확인</th>
				<td><input type="password" id="password_chk" name="password_chk" /><div class="result" style="font-size:12px"></div></td>
				
			</tr>
			<? } ?>
			<tr>
				<th>이메일</th>
				<td>
					<input type="text" name="email_1" value="<?if (strpos($userid[0], "Kakao") === false) { echo $userid[0]; }?>">@
					<select name="email_2">
						<option value="">선택해주세요</option>
						<? foreach ($email_arr as $key=>$value) { ?>
						<option value="<?=$key?>" <? if ($userid[1] == $value) { echo "selected = 'selected'"; } ?>><?=$value?></option>
						<? } ?>
					</select>
					<br />
					<span style="font-size:12px; color:#979797">이메일 수신여부
					<label><input type="radio" name="email_chk" value="0" checked/>수신</label>
					<label><input type="radio" name="email_chk" value="1"/>거부</label></span>
				</td>
			</tr>
			</table>
			<div style="margin:10px auto; width:20%; text-align:center">
				<a href="javascript:submit_chk()"><input type="button" class="etc_btn" value="확인" /></a>
			</div>
			</form>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>