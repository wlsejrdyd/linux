<?
$title = "회원정보 찾기 | 빅덕(Vig Deok)";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");
?>
<script type="text/javascript">
function direct_input() {
	if ($("#email_2").val() == "direct") {
		$("#email_2").hide();
		$("#email_2_1").show();
	}
}

function submit_chk() {
	var mode = "search_id";
	
	if ($("#email_2").css("display") != "none") {
		var mail_chk = $("#email_2").val();
	} else if ($("#email_2_1").css("display") != "none") {
		var mail_chk = $("#email_2_1").val();
	}

	if ($("#email_1").val() == "") {
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
			data:"mode="+mode+"&email=" + $("#email_1").val() + "@" + mail_chk,
			dataType:"json",
			success:function(msg) {
				var result_list = "";
				result_list += "<h4>"+$("#email_1").val() + "@" + mail_chk + "메일의 검색 결과입니다.</h4>";
				if (msg != "") {
					$.each(msg, function(k, v) {
							result_list += "<input type='button' id='result_list_"+k+"' class='etc_btn' style='margin-top:10px; outline:none' value='"+v+"' /><br />";
						}
					);
				} else {
					result_list += "<h5>검색된 결과가 없습니다. 다시확인해주세요.<br />(SNS 회원은 아이디 찾기가 불가능합니다.)</h5>";
				}
				result_list += "<div style='border-top:2px solid black; margin-top:20px'>";
				if (msg != "") {
					result_list += "<a href='/member/login.php'><input type='button' class='etc_btn' style='margin:10px 20px 0 0' value='로그인' /></a>";
					result_list += "<a href='/member/change_pw.php'><input type='button' class='etc_btn' style='margin:10px 0 0 0' value='비밀번호 찾기' /></a>";
				} else {
					result_list += "<a href='/'><input type='button' class='etc_btn' style='margin:10px 20px 0 0' value='메인' /></a>";
				}
				result_list += "</div>";

				$("#search_form").hide();
				$(".result").show();
				$(".result").append(result_list);
			}
		});
	}
}

</script>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px;">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>회원정보 찾기</h3>
			</div>
			<div style="margin:auto; width:100%; text-align:center;" id="search_form">
				<h4>가입했을때 사용했던 이메일 주소를 입력해주세요!</h4>
				<input type="text" name="email_1" id="email_1" />@
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
			<div class="result" style="margin:auto; width:50%; text-align:center; display:none;"></div>
			<div class="change_passwd" style="margin:auto; width:50%; text-align:center; display:none;"></div>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>