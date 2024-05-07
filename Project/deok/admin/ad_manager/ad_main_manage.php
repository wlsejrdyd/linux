<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/admin/admin_header.php");

if (!$_SESSION["a_userid"]) {
	echo "<script>alert('잘못된 접속입니다!');location.href='/admin/index.php';</script>";
	exit;
}
$left_sql = "select * from main_left_ad_list where idx = '1'";
$left_res = mysqli_query($conn, $left_sql);
$left_rs = mysqli_fetch_array($left_res);

$right_sql = "select * from main_right_ad_list where idx = '1'";
$right_res = mysqli_query($conn, $right_sql);
$right_rs = mysqli_fetch_array($right_res);
?>
<script type="text/javascript">
function color_change(num) {
	if (num == "1") {
		$("#left").addClass("on");
		$("#right").removeClass("on");
		$("#left_td").show();
		$("#right_td").hide();
	} else if (num == "2") {
		$("#left").removeClass("on");
		$("#right").addClass("on");
		$("#left_td").hide();
		$("#right_td").show();
	}
}
</script>
<div class="wrap">
	<h3>메인페이지 광고관리</h3>
	<div style="border-bottom:2px solid black; width:100%; margin:10px 0 10px 0"></div>
		<table cellpadding="0" cellspacing="0" style="width:100%" class="ad_table">
		<colgroup>
			<col width="50%">
			<col width="50%">
		</colgroup>
		<tr>
			<th id="left" class="on" onclick="color_change('1')">왼쪽</th>
			<th id="right" onclick="color_change('2')">오른쪽</th>
		</tr>
		
		<tr>
			<td colspan="2" id="left_td">
				<form enctype="multipart/form-data" name="left_file_manage_form" action="/admin/file_process.php" method="POST">
					<input type="hidden" name="mode" value="left_file" />
					<table cellpadding="0" cellspacing="0" style="width:100%" class="ad_table3">
					<colgroup>
						<col width="20%"/>
						<col width="30%"/>
						<col width="20%"/>
						<col width="30%"/>
					</colgroup>
					<tr>
						<th>배너이미지1</th>
						<td>
							<input type="file" style="width:70%" name="left_banner_file_1"><?if ($left_rs["file_name1"]) { ?><label><input type="checkbox" name="delete_chk1" value="1">삭제</label><? } ?>
						</td>
						<th>링크1</th>
						<td>
							<input type="text" style="width:100%; font-size: 1.3em; border:none; outline:none" name="left_link_1" value="<?=$left_rs["link1"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지2</th>
						<td>
							<input type="file" style="width:70%" name="left_banner_file_2"><?if ($left_rs["file_name2"]) { ?><label><input type="checkbox" name="delete_chk2" value="1">삭제</label><? } ?>
						</td>
						<th>링크2</th>
						<td>
							<input type="text" style="width:100%; font-size: 1.3em; border:none; outline:none" name="left_link_2" value="<?=$left_rs["link2"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지3</th>
						<td>
							<input type="file" style="width:70%" name="left_banner_file_3"><?if ($left_rs["file_name3"]) { ?><label><input type="checkbox" name="delete_chk3" value="1">삭제</label><? } ?>
						</td>
						<th>링크3</th>
						<td>
							<input type="text" style="width:100%; font-size: 1.3em; border:none; outline:none" name="left_link_3" value="<?=$left_rs["link3"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지4</th>
						<td>
							<input type="file" style="width:70%" name="left_banner_file_4"><?if ($left_rs["file_name4"]) { ?><label><input type="checkbox" name="delete_chk4" value="1">삭제</label><? } ?>
						</td>
						<th>링크4</th>
						<td>
							<input type="text" style="width:100%; font-size: 1.3em; border:none; outline:none" name="left_link_4" value="<?=$left_rs["link4"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지5</th>
						<td>
							<input type="file" style="width:70%" name="left_banner_file_5"><?if ($left_rs["file_name5"]) { ?><label><input type="checkbox" name="delete_chk5" value="1">삭제</label><? } ?>
						</td>
						<th>링크5</th>
						<td>
							<input type="text" style="width:100%; font-size: 1.3em; border:none; outline:none" name="left_link_5" value="<?=$left_rs["link5"]?>">
						</td>
					</tr>
					</table>
					<center>
						<input type="submit" style="font-size:0.8em;" value="수정" />
					</center>
				</form>
			</td>
			<td colspan="2" id="right_td" style="display:none">
				<form enctype="multipart/form-data" name="right_file_manage_form" action="/admin/file_process.php" method="POST">
					<input type="hidden" name="mode" value="right_file" />
					<table cellpadding="0" cellspacing="0" style="width:100%" class="ad_table3">
					<colgroup>
						<col width="20%"/>
						<col width="30%"/>
						<col width="20%"/>
						<col width="30%"/>
					</colgroup>
					<tr>
						<th>배너이미지1</th>
						<td>
							<input type="file" name="right_banner_file_1" style="width:70%"><?if ($right_rs["file_name1"]) { ?><label><input type="checkbox" name="delete_chk1" value="1">삭제</label><? } ?>
						</td>
						<th>링크1</th>
						<td>
							<input type="text" name="right_link_1" style="width:100%; font-size: 1.3em; border:none; outline:none" value="<?=$right_rs["link1"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지2</th>
						<td>
							<input type="file" name="right_banner_file_2" style="width:70%"><?if ($right_rs["file_name2"]) { ?><label><input type="checkbox" name="delete_chk2" value="1">삭제</label><? } ?>
						</td>
						<th>링크2</th>
						<td>
							<input type="text" name="right_link_2" style="width:100%; font-size: 1.3em; border:none; outline:none" value="<?=$right_rs["link2"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지3</th>
						<td>
							<input type="file" name="right_banner_file_3" style="width:70%"><?if ($right_rs["file_name3"]) { ?><label><input type="checkbox" name="delete_chk3"  value="1">삭제</label><? } ?>
						</td>
						<th>링크3</th>
						<td>
							<input type="text" name="right_link_3" style="width:100%; font-size: 1.3em; border:none; outline:none" value="<?=$right_rs["link3"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지4</th>
						<td>
							<input type="file" name="right_banner_file_4" style="width:70%"><?if ($right_rs["file_name4"]) { ?><label><input type="checkbox" name="delete_chk4" value="1">삭제</label><? } ?>
						</td>
						<th>링크4</th>
						<td>
							<input type="text" name="left_link_4" style="width:100%; font-size: 1.3em; border:none; outline:none" value="<?=$left_rs["link4"]?>">
						</td>
					</tr>
					<tr>
						<th>배너이미지5</th>
						<td>
							<input type="file" name="right_banner_file_5" style="width:70%"><?if ($right_rs["file_name5"]) { ?><label><input type="checkbox" name="delete_chk5"  value="1">삭제</label><? } ?>
						</td>
						<th>링크5</th>
						<td>
							<input type="text" name="left_link_5" style="width:100%; font-size: 1.3em; border:none; outline:none" value="<?=$left_rs["link5"]?>">
						</td>
					</tr>
					</table>
					<center>
						<input type="submit" style="font-size:0.8em;" value="수정" />
					</center>
				</form>
			</td>
		</tr>
		</table>
	</form>
	<span style="font-weight:bold; color:red">※ 한번에 한쪽만 수정가능</span>
</div>