<?
$title = "마이페이지 | 빅덕(Vig Deok)";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");
?>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>마이페이지</h3>
			</div>
			<div style="width:50%; margin:auto">
				<a href="/member/change_pw.php?userid=<?=$_SESSION["userid"]?>"><span style="font-weight:bold">비밀번호 변경하기</span></a>
			</div>
			<div style="border-bottom:2px solid black; text-align:center; width:50%; margin:auto; padding-bottom:10px">
				<h4>내가 쓴 상담문의</h4>
			</div>
			<br /><br />
			<div style="border-bottom:2px solid black; text-align:center; width:50%; margin:auto; padding-bottom:10px">
				<h4>최근 검색한 자격증</h4>
			</div>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>