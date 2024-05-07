<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");

if (!$_SESSION["a_userid"]) {
	echo "<script>alert('잘못된 접속입니다!');location.href='/admin/index.php';</script>";
	exit;
}

// 한페이지에 보여질 게시물의 수
$page_size = "10";

// 페이지 나누기에 표시될 페이지의 수
$page_list_size = "10";

$no = $_GET["no"];
if (!$no || $no < 0) $no = "0";

$sql = "select * from qulify_list where qual_cd = 'T' group by main_cate_cd order by idx asc";
$res = mysqli_query($conn, $sql);

$list_sql = "select * from page_ad_list order by idx desc limit $no, $page_size";

if ($_POST["main_cate_cd"]) {
	$sql2 = "select * from qulify_list where main_cate_cd = '$_POST[main_cate_cd]' order by idx asc";
	$res2 = mysqli_query($conn, $sql2);
	
	$list_sql = "select * from page_ad_list where main_code = '$_POST[main_cate_cd]' order by idx desc limit $no, $page_size";
	$where = "where main_code = '$_POST[main_cate_cd]' ";
	if ($_POST["sma_cate_cd"]) {
		$list_sql = "select * from page_ad_list where code = '$_POST[sma_cate_cd]' order by idx desc limit $no, $page_size";
		$where = "where main_code = '$_POST[sma_cate_cd]' ";
	}
}
$list_res = mysqli_query($conn, $list_sql);

$result_count = mysqli_query($conn, "select count(*) from page_ad_list $where");
$result_row = mysqli_fetch_array($result_count);
$total_row = $result_row[0];

if ($total_row <= 0) $total_row = 0;

$total_page = floor(($total_row - 1) / $page_size);

$current_page = floor($no / $page_size);

$num = $total_row;
?>
<script type="text/javascript">
function locate_chk(code) {
	if (code == "") {
		alert("대분류를 선택해주세요!");
		return false
	} else {
		location.href="/admin/ad_manager/ad_page_manage.php?main_code="+code;
	}
}
</script>
<style>
a { text-decoration:none }
</style>
<div class="wrap">
	<h3>페이지 광고관리</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
	<div style="float:right">
		<input type="button" onclick="locate_chk('<?=$_POST["main_cate_cd"]?>')" value="쓰기"/>
	</div>
	<form name="search_ad" action="<?=$PHP_SELF?>" method="POST" style="display:inline-block" />
		<select name="main_cate_cd" onchange="submit()" style="font-size:1em">
			<option value="">선택</option>
			<? for ($i=0;$i<=$rs = mysqli_fetch_array($res); $i++) { ?>
			<option value="<?=$rs["main_cate_cd"]?>" <? if ($_POST["main_cate_cd"] == $rs["main_cate_cd"]) { ?>selected<? } ?>><?=$rs["main_cate_nm"]?></option>
			<? } ?>
		</select>
		<select name="sma_cate_cd" onchange="submit()" style="font-size:1em">
			<option value="">선택</option>
			<? for ($i=0;$i<=$rs2 = mysqli_fetch_array($res2); $i++) { ?>
			<option value="<?=$rs2["sma_cate_cd"]?>" <? if ($_POST["sma_cate_cd"] == $rs2["sma_cate_cd"]) { ?>selected<? } ?>><?=$rs2["sma_cate_nm"]?></option>
			<? } ?>
		</select>
	</form>
	<table cellpadding="0" cellspacing="0" style="width:100%" class="ad_table">
	<colgroup>
		<col width="5%" />
		<? if ($phone_chk == "y") { ?>
		<col width="20%" />
		<? } else { ?>
		<col width="10%" />
		<? } ?>
		<col width="*"/>
		<col width="10%" />
		<col width="10%" />
		<col width="20%" />
	</colgroup>
	<tr>
		<th>번호</th>
		<th>자격증</th>
		<th>이름</th>
		<th>지역</th>
		<th>국비</th>
		<th>등록일</th>
	</tr>
	<? if ($total_row > "0") {
		for ($i="1"; $i<=$list_rs=mysqli_fetch_array($list_res); $i++) { 
			$search_code_sql = "select * from qulify_list where sma_cate_cd = '$list_rs[code]'";
			$search_code_res = mysqli_query($conn, $search_code_sql);
			$search_code_rs = mysqli_fetch_array($search_code_res);
			$search_code = $search_code_rs["sma_cate_nm"];
			
			if ($_POST["main_cate_cd"]) {
				$main_code = $_POST["main_cate_cd"];
			} else {
				$main_code = $list_rs["main_code"];
			}
		?>
	<tr style="text-align:center; cursor:pointer" onclick="location.href='/admin/ad_manager/ad_page_manage.php?idx=<?=$list_rs["idx"]?>&main_code=<?=$main_code?>'">
		<td><?=$num?></td>
		<td align="left"><?=mb_substr($search_code, "0", "15")."..."?></td>
		<td align="left"><?=$list_rs["class_name"]?></td>
		<td><?=mb_substr($list_rs["area"], "0", "16")."..."?></td>
		<td><? if ($list_rs["support"] == "y") { echo "<span style='color:blue'>지원</span>"; } else { echo "<span style='color:red'>미지원</span>"; } ?></td>
		<td><?=$list_rs["edit_date"]?></td>
	</tr>

		<? $num--;
		}
	} else { ?>
	<tr>
		<td colspan="6" align="center">등록된 내역이 없습니다.</td>
	</tr>
	<? } ?>
	</table>
	<div style="margin:auto; text-align:center; margin-top:10px">
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