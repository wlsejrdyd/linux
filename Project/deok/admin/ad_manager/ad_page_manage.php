<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");

if (!$_SESSION["a_userid"]) {
	echo "<script>alert('잘못된 접속입니다!');location.href='/admin/index.php';</script>";
	exit;
}

$idx = $_GET["idx"];

$page_sql = "select * from page_ad_list where idx = '$idx'";
$page_res = mysqli_query($conn, $page_sql);
$page_rs = mysqli_fetch_array($page_res);

$sql = "select * from qulify_list where main_cate_cd = '$_GET[main_code]' order by idx asc";
$res = mysqli_query($conn, $sql);

?>
<div class="wrap">
	<h3>3페이지 광고관리</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
		<table cellpadding="0" cellspacing="0" class="ad_table3">
		<tr>	
			<td colspan="5">
			<!-- 광고1:s -->
				<form enctype="multipart/form-data" name="page_manage_form" action="/admin/file_process.php" method="POST">
					<input type="hidden" name="mode" value="page_file" />
					<input type="hidden" name="main_code" value="<?=$_GET["main_code"]?>" />
					
					<table cellpadding="0" id="ad_table" cellspacing="0" class="ad_table">
					<colgroup>
						<col width="20%">
						<col width="20%">
						<col width="20%">
						<col width="20%">
					</colgroup>
					<tr>
						<th>배너이미지</th>
						<td colspan="3">
							<input type="file" name="page_banner_file" style="width:100%"><?if ($page_rs["file_name"]) { ?><label><input type="checkbox" name="delete_chk" value="1">삭제</label><? } ?>
						</td>
					</tr>
					<tr>
						<th>자격증</th>
						<td>
							<select name="code">
								<? for ($i="1"; $i<=$rs=mysqli_fetch_array($res); $i++) { ?>
								<option value="<?=$rs["sma_cate_cd"]?>" <? if ($page_rs["code"] == $rs["sma_cate_cd"]) { echo "selected='selected'"; } ?>><?=$rs["sma_cate_nm"]?></option>
								<? } ?>
							</select>
						</td>
						<th>링크</th>
						<td>
							<input type="text" name="page_link" class="input_text" <? if ($page_rs["link"]) { ?>value="<?=$page_rs["link"]?>"<? } else { ?> value="http://"<? } ?>>
						</td>
					</tr>
					<tr>
						<th>학원이름</th>
						<td>
							<input type="text" name="class_name" class="input_text" value="<?=$page_rs["class_name"]?>">
						</td>
						<th>연락처</th>
						<td>
							<input type="text" name="pcs" class="input_text" value="<?=$page_rs["pcs"]?>">
						</td>
					</tr>
					<tr>
						<th>지역</th>
						<td>
							<input type="text" name="area" class="input_text" value="<?=$page_rs["area"]?>">
						</td>
						<th>국비지원여부</th>
						<td>
							<input type="radio" name="support" value="y" <? if ($page_rs["support"] == "y") { ?>checked<? } ?>>지원
							<input type="radio" name="support" value="n" <? if ($page_rs["support"] == "n") { ?>checked<? } ?>>미지원
						</td>
					</tr>
					<tr>
						<td colspan="4" align="center">
							<input type="submit" style="font-size:0.8em;" value="수정"/>
							<input type="button" style="font-size:0.8em;" value="목록" onclick="location.href='/admin/ad_manager/ad_page_list.php';"/>
							<input type="button" style="font-size:0.8em;" value="삭제" onclick="location.href='/admin/ad_manager/ad_page_del.php?idx=<?=$page_rs["idx"]?>'"/>
						</td>
					</tr>
					</table>	
				</form>
			<!-- 광고1:e -->
			</td>
		</tr>
		</table>
	</form>

</div>