<?
$title = "헤더 문구 변경 | 빅덕 관리자";
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");

if (!$_SESSION["a_userid"]) {
	echo "<script>alert('잘못된 접속입니다!');location.href='/admin/index.php';</script>";
	exit;
}

$sql = "select * from footer_change where category='h'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);
?>
<div class="wrap">
	<h3>Header 내용 수정하기</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
	<form action="file_process.php" method="POST">
		<input type="hidden" name="mode" value="header_change" />
		<table cellpadding="0" cellspacing="0" style="margin-bottom:20px" align="center" class="ad_table2">
		<colgroup>
			<col width="30%"/>
			<col width="70%"/>
		</colgroup>
		<tr>
			<th>메인페이지</th>
			<td><input type="text" name="contents1" id="contents1" class="header_text" value="<?=$rs["contents_1"]?>" /></td>
		</tr>
		<tr>
			<th>2페이지</th>
			<td><input type="text" name="contents2" id="contents2" class="header_text" value="<?=$rs["contents_2"]?>" /></td>
		</tr>
		<tr>
			<th>3페이지</th>
			<td><input type="text" name="contents3" id="contents3" class="header_text" value="<?=$rs["contents_3"]?>" /></td>
		</tr>
		</table>
		<center>
			<input type="submit" value="수정하기" class="btn_submit" />
		</center>
	</form>
</div>