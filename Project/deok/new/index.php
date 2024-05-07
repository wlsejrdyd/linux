<?
session_start();
$page = "main";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

$sql = "select * from qulify_list where qual_cd = 'T' group by main_cate_cd order by idx asc";
$res = mysqli_query($conn, $sql);

$sns = $_SESSION["SNS"];
?>
<script type="text/javascript">
function submit_chk() {
	var f = document.search_form;

	if (f.school.value == "") {
		alert("학력을 선택해주세요!");
		f.school.focus();
		$("[name=main_cate_cd] option:eq(0)").prop("selected", true);
	} else if (f.main_cate_cd.value == "") {
		alert("관심분야를 선택해주세요!");
		f.main_cate_cd.focus();
	} else {
		f.submit();
	}
}

function obligfld() {
	var f = document.search_form;
	if (f.qualgbcd.value == "T") {
		$("#main_cate_cd").css("display", "unset");
		$("select[name=school]").css("display", "unset");
		<? if ($phone_chk == "y") { ?>
		$("#school").show();
		$("#cate").show();
		<? } ?>
	} else if (f.qualgbcd.value == "S") {
		$("select[name=main_cate_cd]").css("display", "none");
		$("select[name=school]").css("display", "none");
		$("select[name=main_cate_cd]").val("");
		$("select[name=school]").val("");
		f.submit();
	}
}

function obligfld_2() {
	if ($("select[name=qualgbcd]").val() == "T") {
		$("select[name=school]").show();
	} else {
		location.href="/middle_view.php?qualgbcd=S";
	}
}

function main_cate_ld() {
	if ($("select[name=qualgbcd]").val() != "") {
		$(".main_descript").hide();
		$("#main_cate_cd").show();
	}
}

function locate_button(num) {
	var data = $("#button_list_"+num).data('val');
	var school = $("select[name=school]").val();
	var qualgbcd = $("select[name=qualgbcd]").val();
	var name = $("#button_list_"+num).val();
	location.href="/middle_view.php?main_cate_cd="+data+"&school="+school+"&qualgbcd="+qualgbcd+"&name="+name;
}

//카카오 로그인
// 사용할 앱의 JavaScript 키를 설정해 주세요.
Kakao.init('9055da34548f4b3f43e5ff30db5822ff');
function loginWithKakao() {
	// 로그인 창을 띄웁니다.
	Kakao.Auth.login({
		success: function(authObj) {
			Kakao.API.request({
				url:'/v1/user/me',
				success:function(res) {
					location.href='/api/kakao_login/kakao_login.php?id='+res.id+'&nickname='+res.properties['nickname'];
				}
			});
		},
		fail: function(err) {
//			alert(JSON.stringify(err));
			alert("로그인에 실패했습니다.\n\n관리자에게 문의해주세요!");
		}
	});
};

</script>
<div class="wrapB">
	<div class="left" style="background-color:#291a2d">
		 
	</div>
	<!-- cont:s -->
	<div class="cont" <? if ($phone_chk != "y") { ?>style="height:auto"<? } ?>>
		<!-- top:s -->
		<div class="top">
			<div class="title">
				<h3>자격증 검색</h3>
			</div>
			<? // 모바일에서 접속했을 때
			if ($phone_chk == "y") { ?>
			<!-- search_form:s -->
			<form name="search_form" id="search_form" action="/middle_view.php" method="POST" style="text-align:center">
				<!-- mobile_table:s -->
				<table cellpadding="0" cellspacing="0" width="100%" class="mobile_table">
				<tr>
					<th>구분</th>
					<td>
						<select name="qualgbcd" onchange="obligfld()" class="obligfld">
							<option value="">선택</option>
							<option value="T" <? if ($_POST["qualgbcd"] == "T") { ?>selected<? } ?>>국가기술자격</option>
							<option value="S" <? if ($_POST["qualgbcd"] == "S") { ?>selected<? } ?>>국가전문자격</option>
						</select>
					</td>
				</tr>
				<tr id="school" style="display:none">
					<th>학력</th>
					<td>
						<select name="school" class="obligfld">
							<option value="">학력</option>
							<option value="H" <? if ($_POST["school"] == "H") { ?>selected<? } ?>>고졸</option>
							<option value="C" <? if ($_POST["school"] == "C") { ?>selected<? } ?>>전문대졸업</option>
							<option value="U" <? if ($_POST["school"] == "U") { ?>selected<? } ?>>4년제졸업</option>
							<option value="G" <? if ($_POST["school"] == "G") { ?>selected<? } ?>>대학원졸업</option>
						</select>
					</td>
				</tr>
				<tr id="cate" style="display:none">
					<th>분 야</th>
					<td>
						<select name="main_cate_cd" onchange="submit_chk()" class="obligfld">
							<option value="">선택</option>
							<? for ($i=0; $i<=$rs = mysqli_fetch_array($res); $i++) { ?>
							<option value="<?=$rs["main_cate_cd"]?>" <? if ($_POST["main_cate_cd"] == $rs["main_cate_cd"]) { ?>selected<? } ?>><?=$rs["main_cate_nm"]?></option>
							<? } ?>
						</select>
					</td>
				</tr>
				</table>
				<!-- mobile_table:e -->
			</form>
			<!-- search_form:e -->
		</div>
		<!-- top:e -->
			
		<div class="top_line"></div>
		
		<!-- sns_login:s -->
		<div id="sns_login" class="sns_login">
			<? if ($_SESSION["userid"]) { 
				if ($sns) { ?>
			<h3>로그인한 SNS 계정</h3>
			<div class="bottom_line"></div>
			<div style="margin:auto">
					<? if ($sns == "NAVER") { ?>
					<!-- <a href="/member/logout.php"><img src="/images/naver_btn.png" alt="네이버아이콘" style="width:10%;" /></a> -->
					<? } else if ($sns == "KAKAO") { ?>
					<a href="/member/logout.php"><img src="/images/kakao_btn.png" alt="카카오아이콘" style="width:10%;" /></a>
					<? } else if ($sns == "GOOGLE") { ?>
					<a href="/member/logout.php"><img src="/images/google_btn.png" alt="구글아이콘" style="width:10%;" /></a>
					<? } ?>
			</div>
				<? } else { ?>
			<h3>로그인한 계정</h3>
			<div class="bottom_line"></div>
			<div style="margin:auto">
				<a href="/member/logout.php"><img src="/images/vigdeok_btn.png" alt="빅덕아이콘" style="width:10%;" /></a>
			</div>
				<? } ?>
			<? } else { ?>
			<h3>SNS로그인</h3>
			<div class="bottom_line"></div>
			<div style="margin:auto">
				<? //include_once ($_SERVER["DOCUMENT_ROOT"]."/api/naver_login/naver_login.php"); ?>
				<a href="javascript:loginWithKakao()">
					<img src="/images/kakao_btn.png" id="#kakao-login-btn" alt="카카오로그인" style="width:10%;" />
				</a>
				<a href="javascript:startApp()">
					<img src="/images/google_btn.png" id="customBtn" alt="구글로그인" style="width:10%; cursor:pointer"/>
				</a>
			</div>
			<? } ?>
		</div>
		<!-- sns_login:e -->

		<div class="top_line"></div>
		<!-- main_descript:s -->
		<div class="main_descript">
			<span style="font-weight:bold">국가기술자격?</span><br />
			국가기술자격이란 자격기본법에 따른 국가자격 중 산업과 관련이 있는 기술, 기능 및 서비스 분야의 자격을 말한다.<br />
			ex) 기능사, 산업기사, 기사, 기능장, 기술사<br /><br />
			<span style="font-weight:bold">국가전문자격?</span><br />
			국가전문자격은 정부부처, 즉 보건복지부, 여성가족부 등에서 주관하는 자격증이다.<br />
			ex) 변호사, 공인회계사, 세무사, 법무사 등
		</div>
		<!-- main_descript:e -->
		<? // 모바일 접속이 아닐 때
		} else { ?>
			<select name="qualgbcd" onchange="obligfld_2()" class="obligfld">
				<option value="">자격구분</option>
				<option value="T" <? if ($_POST["qualgbcd"] == "T") { ?>selected<? } ?>>국가기술자격</option>
				<option value="S" <? if ($_POST["qualgbcd"] == "S") { ?>selected<? } ?>>국가전문자격</option>
			</select>

			<select name="school" onchange="main_cate_ld()" <? if (!$_POST["school"]) { ?> class="obligfld" style="display:none"<? } ?>>
				<option value="">학력</option>
				<option value="H" <? if ($_POST["school"] == "H") { ?>selected<? } ?>>고졸</option>
				<option value="C" <? if ($_POST["school"] == "C") { ?>selected<? } ?>>전문대졸업</option>
				<option value="U" <? if ($_POST["school"] == "U") { ?>selected<? } ?>>4년제졸업</option>
				<option value="G" <? if ($_POST["school"] == "G") { ?>selected<? } ?>>대학원졸업</option>
			</select>

		</div>
		<!-- top:e -->
		<div class="top_line"></div>
		
		<!-- sns_login:s -->
		<div id="sns_login" class="sns_login">
			<? if ($_SESSION["userid"]) {
				if ($sns) { ?>
			<h3>로그인한 SNS 계정</h3>
			<div class="bottom_line"></div>
			<div style="margin:auto">
				<? if ($sns == "NAVER") { ?>
					<!-- <a href="/member/logout.php"><img src="/images/naver_btn.png" alt="네이버아이콘" style="width:10%;" /></a> -->
				<? } else if ($sns == "KAKAO") { ?>
					<a href="/member/logout.php"><img src="/images/kakao_btn.png" alt="카카오아이콘" style="width:10%;" /></a>
				<? } else if ($sns == "GOOGLE") { ?>
					<a href="/member/logout.php"><img src="/images/google_btn.png" alt="구글아이콘" style="width:10%;" /></a>
				<? } ?>
			</div>
			<? } else { ?>
				<h3>로그인한 계정</h3>
				<div class="bottom_line"></div>
				<div style="margin:auto">
					<a href="/member/logout.php"><img src="/images/vigdeok_btn.png" alt="빅덕아이콘" style="width:10%;" /></a>
				</div>
				<? } ?>
			<? } else { ?>
			<h3>SNS로그인</h3>
			<div class="bottom_line"></div>
			<div style="margin:auto">
				<? //include_once ($_SERVER["DOCUMENT_ROOT"]."/api/naver_login/naver_login.php"); ?>
				<a href="javascript:loginWithKakao()">
					<img src="/images/kakao_btn.png" id="#kakao-login-btn" alt="카카오로그인" style="width:10%;" />
				</a>
				<a href="javascript:startApp()">
					<img src="/images/google_btn.png" alt="구글로그인" id="customBtn" style="width:10%;"/>
				</a>
			</div>
			<? } ?>
		</div>
		<!-- sns_login:e -->
		<div class="top_line"></div>
			<div class="main_descript">
				<span style="font-weight:bold">국가기술자격?</span><br />
				국가기술자격이란 자격기본법에 따른 국가자격 중 산업과 관련이 있는 기술, 기능 및 서비스 분야의 자격을 말한다.<br />
				ex) 기능사, 산업기사, 기사, 기능장, 기술사<br /><br />
				<span style="font-weight:bold">국가전문자격?</span><br />
				국가전문자격은 정부부처, 즉 보건복지부, 여성가족부 등에서 주관하는 자격증이다.<br />
				ex) 변호사, 공인회계사, 세무사, 법무사 등
			</div>
			<div id="main_cate_cd" class="main_cate_cd">
			<div class="list">
					<h3>분 야</h3>
				</div>
				<? for ($i=0;$i<=$rs = mysqli_fetch_array($res); $i++) { ?>
				<a href="javascript:locate_button('<?=$i+1?>')"><input class="button_list" id="button_list_<?=$i+1?>" type="button" value="<?=$rs["main_cate_nm"]?>" data-val="<?=$rs["main_cate_cd"]?>" /></a>
				<? } ?>
			</div>
		<? } ?>
		<div class="bottom">

		</div>
	</div>
	<!-- top:e -->
	<div class="right" style="background-color:#291a2d">
		 
	</div>
</div>
<!-- cont:e -->

<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php");
?>
