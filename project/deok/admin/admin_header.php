<?
header("Content-Type: text/html; charset=UTF-8");
@session_start();
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");
$session_userid = $_SESSION["a_userid"];

$phone_chk = "";
$MobileArray  = array("iphone","lgtelecom","skt","mobile","samsung","nokia","blackberry","Android","android","sony","iphone");
foreach ($MobileArray as $k => $v) {
	if (strpos($_SERVER["HTTP_USER_AGENT"], $v) !== false) {
		$phone_chk = "y";
	}
}
?>
<html>
<head>
	<title><?=$title?></title>
	<link rel="stylesheet" href="/css/admin_style.css">
	<script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
	<script type="text/javascript">
	function menu_show() {
		if ($("#ad_menu").css("display") == "none") {
			$("#ad_menu").show();
		} else {
			$("#ad_menu").hide();
		}
	}
	</script>
</head>
<body>
<div class="head_bar">
	<span style="font-weight: bold">Vig Deok 관리자</span>
	<div class="menu_btn">
		<a href="/index.php">메인</a> | <a href="/admin/logout.php">로그아웃</a>
	</div>
</div>
<div class="admin_menu">
	<span id="show_menu" onclick="$('#show_menu').hide(); $('#menu_list').show()">메뉴확인</span>
	<div class="menu_list" id="menu_list" style="display:none">
		<input type="button" class="sub_menu_btn" value="메인페이지광고" onclick="location.href='/admin/ad_manager/ad_main_manage.php'" />
		<input type="button" class="sub_menu_btn" value="3페이지광고" onclick="location.href='/admin/ad_manager/ad_page_list.php'" />
		<input type="button" class="sub_menu_btn" value="푸터관리" onclick="location.href='/admin/footer_change.php'" />
		<input type="button" class="sub_menu_btn" value="헤더관리" onclick="location.href='/admin/header_change.php'" />
		<input type="button" class="sub_menu_btn" value="상담문의" onclick="location.href='/admin/counseling_list.php'" />
		<input type="button" class="sub_menu_btn" value="회원관리" onclick="location.href='/admin/member_list.php'" />
	</div>
</div>
<div class="side_menu">
	<ul>
		<li onclick="menu_show();" style="cursor:pointer">광고관리</li>
			<ul id="ad_menu" style="padding-left: 20px; display:none;">
				<li onclick="location.href='/admin/ad_manager/ad_main_manage.php'" style="cursor:pointer">메인페이지 광고</li>
				<li onclick="location.href='/admin/ad_manager/ad_page_list.php'" style="cursor:pointer">3페이지 광고</li>
			</ul>
		<li onclick="location.href='/admin/footer_change.php'" style="cursor:pointer">푸터관리</li>
		<li onclick="location.href='/admin/header_change.php'" style="cursor:pointer">헤더관리</li>
		<li onclick="location.href='/admin/counseling_list.php'" style="cursor:pointer">상담문의</li>
		<li onclick="location.href='/admin/member_list.php'" style="cursor:pointer">회원관리</li>
	</ul>
</div>
</body>
</html>
