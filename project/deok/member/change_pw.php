<?
$title = "비밀번호 변경 | 빅덕(Vig Deok)";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

if ($_GET["userid"]) { 
	echo "<script>$(document).ready(function() { $('.result').show(); }); </script>";
}
?>
<script type="text/javascript">
function direct_input() {
	if ($("#email_2").val() == "direct") {
		$("#email_2").hide();
		$("#email_2_1").show();
	}
}

function submit_chk() {
	var mode = "change_pw";

	if ($("#email_2").css("display") != "none") {
		var mail_chk = $("#email_2").val();
	} else if ($("#email_2_1").css("display") != "none") {
		var mail_chk = $("#email_2_1").val();
	}
	
	if ($("#userid").val() == "") {
		alert("아이디를 입력해주세요!");
		document.getElementById("userid").focus();
		return false;
	} else if ($("#email_1").val() == "") {
		alert("이메일을 입력해주세요!");
		document.getElementById("email_1").focus();
		return false;
	} else if (mail_chk == "") {
		alert("이메일을 입력해주세요!");
		document.getElementById("email_2").focus();
		return false;
	} else {
		$.ajax({
			type:"post",
			url:"/ajax/find_userinfo.php",
			data:"mode="+mode+"&userid=" + $("#userid").val() + "&email=" + $("#email_1").val() + "@" + mail_chk,
			success:function(msg) {
				if (msg == "0") {
					alert("아이디/이메일이 일치하지 않습니다. 다시확인해주세요!");
				} else {
					$(".result").show();
					var userid = $("#userid").val();
					document.getElementById("pw_userid").value = userid;
					$("#search_form").hide();
				}
			}
		});
	}
}

function pw_change() {
	var pw_change = $("#password_chk").val();
	var pw = $("#pw").val();
	var pw_chk = $("#pw_chk").val();

	var f = document.chane_pw;

	if (f.pw.value == "") {
		alert("비밀번호를 입력해주세요");
		f.pw.focus();
		return false;
	} else if (f.pw_chk.value == "") {
		alert("비밀번호 확인을 입력해주세요");
		f.pw_chk.focus();
		return false;
	} else if (f.password_chk.value == "0") {
		alert("비밀번호가 일치하지 않습니다.");
		f.pw.focus();
		return false;
	} else {
		f.submit();
	}
}

$(function(){
	$("#pw_chk").focusout(function() {
		$(".pw_result").empty();
		var pw = $("#pw").val();
		var pw_chk = $("#pw_chk").val();
		
		if (pw == pw_chk) {
			result_txt = "<span style='color:blue'>비밀번호가 일치합니다.</span>";
			$("#password_chk").val('1');
		} else {
			result_txt = "<span style='color:red'>비밀번호가 일치하지 않습니다.</span>";
			$("#password_chk").val('0');
		}
		$(".pw_result").show();
		$(".pw_result").append(result_txt);
	});
});
</script>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px;">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>비밀번호 변경</h3>
			</div>
			<? if (!$_GET["userid"]) { ?>
			<div style="margin:auto; width:100%; text-align:center;" id="search_form">
				<input type="text" name="userid" id="userid" placeholder="아이디" />
				<input type="text" name="email_1" id="email_1" placeholder="이메일" />@
				<select name="email_2" id="email_2" onchange="direct_input()">
					<option value="">선택</option>
					<option value="naver.com">naver.com</option>
					<option value="daum.net">daum.net</option>
					<option value="gmail.com">gmail.com</option>
					<option value="direct">직접입력</option>
				</select>
				<input type="text" name="email_2_1" id="email_2_1" style="display:none" />
				<a href="javascript:submit_chk()"><input type="button" class="etc_btn" value="찾기"/></a>
			</div>
			<? } ?>
			<div class="result" style="margin:auto; width:50%; text-align:center; display:none;">
				<form name="chane_pw" action="/process/process.php" method="POST">
					<input type="hidden" name="mode" value="pw_change" />
					<input type="hidden" name="pw_userid" id="pw_userid" value="" />
					<input type="hidden" name="password_chk" id="password_chk" value="0" />
					<h4>변경하실 비밀번호을 입력해주세요!</h4>
					<input type="password" id="pw" name="pw" placeholder="비밀번호"/><br />
					<input type="password" id="pw_chk" name="pw_chk" placeholder="비밀번호 확인"/><br />
					<div class="pw_result" style="display:none"></div>
					<input type="button" class="etc_btn" onclick="pw_change()" value="변경하기" />
				</form>
			</div>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>