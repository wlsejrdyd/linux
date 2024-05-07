<?
$page = "two";
$title = $_GET["name"]." 분야 검색 | 빅덕(Vig Deok)";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

if ($_GET["qualgbcd"] == "S") {
	$qualgbcd = "S";
} else if ($_POST["qualgbcd"] == "S") {
	$qualgbcd = "S";
}

if ($_GET["main_cate_cd"]) {
	$main_cate_cd = $_GET["main_cate_cd"];
} else if ($_POST["main_cate_cd"]) {
	$main_cate_cd = $_POST["main_cate_cd"];
}

if ($_GET["school"]) {
	$school = $_GET["school"];
} else if ($_POST["school"]) {
	$school = $_POST["school"];
}

if ($main_cate_cd) {
	if ($school == "H") {
		$where = "and sma_cate_nm like '%기능사'";
	} else if ($school == "C") {
		$where = "and (sma_cate_nm like '%산업기사' or sma_cate_nm like '%기능사')";
	} else {
		$where = "";
	}
	$search_sql = "select * from qulify_list where main_cate_cd = '$main_cate_cd' $where order by idx asc";
	$search_res = mysqli_query($conn, $search_sql);
} else if ($qualgbcd == "S") {
	$search_sql = "select * from qulify_list where qual_cd = 'S' order by idx asc";
	$search_res = mysqli_query($conn, $search_sql);
} else {
	echo "<script>alert('잘못된 접속입니다.');location.href='/';</script>";
}
?>
<div class="wrapB">
	<div class="cont pages2 pages1">
<!-- 		<div class="top"> -->
<!-- 		</div> -->
		<div class="mid">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>자격증 목록</h3>
			</div>
			<div style="text-align:center">
			<? while($search_rs = mysqli_fetch_array($search_res)) { 
				if (strpos($search_rs["sma_cate_nm"], "기술사") === false && strpos($search_rs["sma_cate_nm"], "기능장") === false) { ?>
				<a href="/result_view.php?code=<?=$search_rs["sma_cate_cd"]?>&name=<?=$search_rs["sma_cate_nm"]?>"><input type="button" value="<?=$search_rs["sma_cate_nm"]?>" class="button_list2" /></a>
				<? }
			} ?>
			</div>
			
		</div>
<!-- 		<div class="bottom"> -->
<!-- 		 -->
<!-- 		</div> -->
	</div>
</div>

<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php");
?>
