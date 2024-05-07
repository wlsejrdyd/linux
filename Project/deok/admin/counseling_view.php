<?
$title = "상담문의보기 | 빅덕 관리자";
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");
$idx = $_GET["idx"];

$sql = "select * from counseling_list where idx = '$idx'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);
?>
<div class="wrap">
	<h3>상담문의 내용</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
	<form name="contents_form" action="contents_process.php" method="POST">
		<input type="hidden" name="mode" value="counseling" />
		<input type="hidden" name="userid" value="<?=$rs["userid"]?>" />
		<input type="hidden" name="idx" value="<?=$idx?>" />
		<table cellpadding="0" cellspacing="0" style="width:100%" class="ad_table">
		<tr>
			<th>회원ID</th>
			<td colspan="3"><?=$rs["userid"]?></td>
		</tr>
		<tr>
			<th>제목</th>
			<td><input type="text" name="subject" value="<?=$rs["subject"]?>" style="width:100%"/></td>
			<th>분류</th>
			<td>
				<select name="category">
					<option value="">선택</option>
					<option value="1" <? if ($rs["category"] == "1") { echo "selected='selected'"; } ?>>견적상담</option>
					<option value="2" <? if ($rs["category"] == "2") { echo "selected='selected'"; } ?>>건의사항</option>
					<option value="3" <? if ($rs["category"] == "3") { echo "selected='selected'"; } ?>>문의사항</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>내용</th>
			<td colspan="3"><textarea name="c_contents" rows="5" style="width:100%"><?=$rs["contents"]?></textarea></td>
		</tr>
		</table>
		<div style="margin:10px auto; width:10%;">
			<input type="submit" value="확인"/>
			<input type="button" onclick="location.href='contents_process.php?mode=counseling_del&idx=<?=$rs["idx"]?>'" value="삭제"/>
		</div>
	</form>
</div>