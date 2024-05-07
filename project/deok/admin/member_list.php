<?
$title = "회원목록 | 빅덕 관리자";
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");


// 한페이지에 보여질 게시물의 수
$page_size = "10";

// 페이지 나누기에 표시될 페이지의 수
$page_list_size = "10";

$no = $_GET["no"];
if (!$no || $no < 0) $no = "0";

if ($_POST["sns_search"]) {
	if ($_POST["sns_search"] == "direct") {
		$where .= "and sns='' ";
	} else {
		$where .= "and sns='$_POST[sns_search]' ";
	}
}
if ($_POST["userid"]) {
	$where .= "and userid = '$_POST[userid]' ";
}

$sql = "select * from member where 1 $where limit $no, $page_size";
$res = mysqli_query($conn, $sql);

$result_count = mysqli_query($conn, "select count(*) from member where 1 $where");
$result_row = mysqli_fetch_array($result_count);
$total_row = $result_row[0];

if ($total_row <= 0) $total_row = 0;

// 전체페이지
$total_page = floor(($total_row - 1) / $page_size);

// 현재페이지
$current_page = floor($no / $page_size);

$num = $total_row;
?>
<style>
.ad_table td { text-align: center }
</style>
<script type="text/javascript">
function confirm_chk(idx) { 
	if (confirm("다시 복구할 수 없습니다. 삭제하시겠습니까?") == true) {
		location.href="/process/process.php?mode=del_user&idx="+idx
	}
}
</script>
<div class="wrap">
	<h3>회원관리</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
	<div style="float:right">
		<form name="search_form" action="<?=$PHP_SELF?>" method="POST">
			<select name="sns_search" style="vertical-align:middle">
				<option value="NAVER" <? if ($_POST["sns_search"] == "NAVER") { echo "selected='selected'"; } ?>>네이버</option>
				<option value="KAKAO" <? if ($_POST["sns_search"] == "KAKAO") { echo "selected='selected'"; } ?>>카카오</option>
				<option value="GOOGLE" <? if ($_POST["sns_search"] == "GOOGLE") { echo "selected='selected'"; } ?>>구글</option>
				<option value="direct" <? if ($_POST["sns_search"] == "direct") { echo "selected='selected'"; } ?>>직접가입</option>
			</select>
			<input type="text" name="userid" />
			<input type="submit" style="vertical-align:middle" value="검색" />
		</form>
	</div>
	<table cellpadding="0" cellspacing="0" class="ad_table">
	<tr>
		<th>번호</th>
		<th>아이디</th>
		<th>SNS</th>
		<th>이메일</th>
		<th>수신여부</th>
		<th>등록일</th>
		<th>기타</th>
	</tr>
	<? while ($rs = mysqli_fetch_array($res)) { 
		if (!$rs["sns"]) { $sns = "직접가입"; } else { $sns = $rs["sns"]; } ?>
	<tr>
		<td><?=$num?></td>
		<td><?=$rs["userid"]?></td>
		<td><?=$sns?></td>
		<td><?=$rs["email"]?></td>
		<td><? if($rs["email_chk"] == "0") { ?><span style="color:blue">수신</span><? } else { ?><span style="color:red">거부</span><? } ?></td>
		<td><?=$rs["reg_date"]?></td>
		<td><input type="button" value="강퇴" style="background-color:red; border:none; font-weight:bold; color:white; border-radius:5px; padding: 0 20px 0 20px" onclick="confirm_chk('<?=$rs["idx"]?>')"/></td>
	</tr>
	<?  $num--;
	} ?>
	</table>
	<div style="text-align:center; margin-top:10px">
	<?
	$start_page = (int) ($current_page / $page_list_size) * $page_list_size;
	
	$end_page = $start_page + $page_list_size - 1;
	
	if ($total_page < $end_page) $end_page = $total_page;

	if ($start_page >= $page_list_size) {
		$prev_list = ($start_page - 1) * $page_size;
		echo "<a href=\"$PHP_SELF?no=$prev_list\">◀</a>\n";
	}

	for ($i=$start_page; $i<=$end_page; $i++) {
		$page = $page_size * $i;
		$page_num = $i + 1;
		
		if ($no != $page) {
			echo "<a href=\"$PHP_SELF?no=$page\" style='color:blue'>";
		}
		echo "<b> $page_num </b>";

		if ($no != $page) {
			echo "</a>";
		}
	}

	if ($total_page > $end_page) {
		$next_list = ($end_page + 1) * $page_size;
		echo "<a href=$PHP_SELF?no=$next_list>▶</a>";
	}
	?>
	</div>
</div>