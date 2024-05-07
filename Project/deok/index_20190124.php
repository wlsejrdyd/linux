<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

$sql = "select * from qulify_list where qual_cd = 'T' group by main_cate_cd order by idx asc";
$res = mysqli_query($conn, $sql);
?>
<script type="text/javascript">
function submit_chk() {
	var f = document.search_form;

	if (f.school.value == "") {
		alert("학력을 선택해주세요!");
		f.school.focus();
		$("[name=main_cate_cd] option:eq(0)").prop("selected", true);
	} else if (f.main_cate_cd.value == "") {
		alert("관심분야를 선택해주세요!");
		f.main_cate_cd.focus();
	} else {
		f.submit();
	}
}

function obligfld() {
	var f = document.search_form;
	if (f.qualgbcd.value == "T") {
		$("select[name=main_cate_cd]").css("display", "unset");
		$("select[name=school]").css("display", "unset");
		<? if ($phone_chk == "y") { ?>
		$("#school").show();
		$("#cate").show();
		<? } ?>
	} else if (f.qualgbcd.value == "S") {
		$("select[name=main_cate_cd]").css("display", "none");
		$("select[name=school]").css("display", "none");
		$("select[name=main_cate_cd]").val("");
		$("select[name=school]").val("");
		f.submit();
	}
}
</script>

<div class="wrapB">
	<div class="left">
		광고2
	</div>
	<div class="cont" style="border:2px solid #ffce4b">
		<div class="top" style="padding:10px 0 0 10px">
			<form name="search_form" id="search_form" action="/middle_view.php" method="POST" style="text-align:center">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%">
				<h3>자격증 검색</h3>
			</div>
			<? if ($phone_chk == "y") { ?>
				<table cellpadding="0" cellspacing="0" width="100%" class="mobile_table">
				<tr>
					<th>구분</th>
					<td>
						<select name="qualgbcd" onchange="obligfld()" style="width:150px">
							<option value="">선택</option>
							<option value="T" <? if ($_POST["qualgbcd"] == "T") { ?>selected<? } ?>>국가기술자격</option>
							<option value="S" <? if ($_POST["qualgbcd"] == "S") { ?>selected<? } ?>>국가전문자격</option>
						</select>
					</td>
				</tr>
				<tr id="school" style="display:none">
					<th>학력</th>
					<td>
						<select name="school" style="width:150px">
							<option value="">학력</option>
							<option value="H" <? if ($_POST["school"] == "H") { ?>selected<? } ?>>고졸</option>
							<option value="C" <? if ($_POST["school"] == "C") { ?>selected<? } ?>>전문대졸업</option>
							<option value="U" <? if ($_POST["school"] == "U") { ?>selected<? } ?>>4년제졸업</option>
							<option value="G" <? if ($_POST["school"] == "G") { ?>selected<? } ?>>대학원졸업</option>
						</select>
					</td>
				</tr>
				<tr id="cate" style="display:none">
					<th>관심분야</th>
					<td>
						<select name="main_cate_cd" onchange="submit_chk()" style="width:150px">
							<option value="">선택</option>
							<? for ($i=0;$i<=$rs = mysqli_fetch_array($res); $i++) { ?>
							<option value="<?=$rs["main_cate_cd"]?>" <? if ($_POST["main_cate_cd"] == $rs["main_cate_cd"]) { ?>selected<? } ?>><?=$rs["main_cate_nm"]?></option>
							<? } ?>
						</select>
					</td>
				</tr>
				</table>
			<? } else { ?>
				<select name="qualgbcd" onchange="obligfld()" style="font-size:1.2em">
					<option value="">자격구분</option>
					<option value="T" <? if ($_POST["qualgbcd"] == "T") { ?>selected<? } ?>>국가기술자격</option>
					<option value="S" <? if ($_POST["qualgbcd"] == "S") { ?>selected<? } ?>>국가전문자격</option>
				</select>

				<select name="school" <? if (!$_POST["school"]) { ?>style="display:none;font-size:1.2em"<? } ?>>
					<option value="">학력</option>
					<option value="H" <? if ($_POST["school"] == "H") { ?>selected<? } ?>>고졸</option>
					<option value="C" <? if ($_POST["school"] == "C") { ?>selected<? } ?>>전문대졸업</option>
					<option value="U" <? if ($_POST["school"] == "U") { ?>selected<? } ?>>4년제졸업</option>
					<option value="G" <? if ($_POST["school"] == "G") { ?>selected<? } ?>>대학원졸업</option>
				</select>

				<select name="main_cate_cd" <? if (!$_POST["main_cate_cd"]) { ?>style="display:none;font-size:1.2em"<? } ?> onchange="submit_chk()">
					<option value="">관심분야</option>
					<? for ($i=0;$i<=$rs = mysqli_fetch_array($res); $i++) { ?>
					<option value="<?=$rs["main_cate_cd"]?>" <? if ($_POST["main_cate_cd"] == $rs["main_cate_cd"]) { ?>selected<? } ?>><?=$rs["main_cate_nm"]?></option>
					<? } ?>
				</select>
			<? } ?>
			</form>
		</div>
		<div class="bottom">

		</div>
	</div>
	<div class="right">
		광고3
	</div>
</div>

<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php");
?>
