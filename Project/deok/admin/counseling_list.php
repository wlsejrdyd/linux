<?
$title = "상담문의내역 | 빅덕 관리자";
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");

// 한페이지에 보여질 게시물의 수
$page_size = "10";

// 페이지 나누기에 표시될 페이지의 수
$page_list_size = "10";

$no = $_GET["no"];
if (!$no || $no < 0) $no = "0";

$sql = "select * from counseling_list order by idx desc limit $no, $page_size;";
$res = mysqli_query($conn, $sql);

$result_count = mysqli_query($conn, "select count(*) from counseling_list");
$result_row = mysqli_fetch_array($result_count);
$total_row = $result_row[0];

if ($total_row <= 0) $total_row = 0;

$total_page = floor(($total_row - 1) / $page_size);

$current_page = floor($no / $page_size);

$num = $total_row;
?>
<style>
.a_table { width: 100%; border: 1px solid #444444; border-collapse: collapse; font-size: 1em }
.a_table td { border: 1px solid #444444; padding: 10px; font-size: 0.8em }
.a_table th { border: 1px solid #444444; background-color: #d9d9d9 }
</style>
<div class="wrap">
	<h3>상담문의</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
	<table cellpadding="0" cellspacing="0" class="a_table" style="width:100%">
	<colgroup>
		<col width="10%" />
		<col width="10%" />
		<col width="10%" />
		<col width="40%" />
		<col width="30%" />
	</colgroup>
	<tr>
		<th>번호</th>
		<th>회원ID</th>
		<th>분류</th>
		<th>제목</th>
		<th>등록일자</th>
	</tr>
	<? if ($total_row > "0") { ?>
	<? for ($i=1; $i<=$rs=mysqli_fetch_array($res); $i++) { ?>
	<tr onclick="location.href='counseling_view.php?idx=<?=$rs['idx']?>'" style="cursor:pointer">
		<td align="center"><?=$num?></td>
		<td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis" align="center"><?=$rs["userid"]?></td>
		<td align="center"><? if ($rs["category"] == "1") { echo "견적상담"; } else if ($rs["category"] == "2") { echo "건의사항"; } else { echo "문의사항"; } ?></td>
		<td><?=$rs["subject"]?></td>
		<td align="center"><?=$rs["reg_date"]?></td>
	</tr>
	<? $num--; 
		}
	} else { ?>
	<tr>
		<td colspan="5" align="center">등록된 상담문의가 없습니다.</td>
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