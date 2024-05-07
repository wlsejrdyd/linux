<?
$title = "상담문의하기 | 빅덕(Vig Deok)";
$description = "광고견적상담, 건의사항, 문의사항 등을 알려주시면 바로 답변 드리는 서비스입니다.";

include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

if (!$_SESSION["userid"]) {
	echo "<script>alert('회원만 사용가능합니다.');location.href='/';</script>";
}
?>
<style>
.c_table { border: 1px solid #e0e0e0; width: 80%; margin:auto; border-collapse: collapse; }
.c_table th { border: 1px solid #e0e0e0; background-color:#d8d8d8; font-weight:bold; padding:0; height: 30px }
.c_table td { padding: 0;border: 1px solid #e0e0e0; }
.c_btn { border: none; border-radius: 5px; background-color: #ffce4b; outline: none; padding: 5px 10px 5px 10px; font-weight: bold; }
</style>
<script type="text/javascript">
function submit_chk() {
	var f = document.counseling_form;
	if (f.subject.value == "") {
		alert("제목을 입력해주세요!");
		f.subject.focus();
		return false;
	} else if (f.category.value == "") {
		alert("분야를 선택해주세요!");
		f.category.focus();
		return false;
	} else if (document.getElementById("c_contents").value.length == 0) {
		alert("내용을 입력해주세요!");
		f.c_contents.focus();
		return false;
	} else {
		f.submit();
	}
}
</script>
<div class="wrapB">
	<div class="cont pages2 pages1">
		<div class="mid" style="padding-bottom:10px">
			<div style="margin:10px auto 20px auto; border-bottom:2px solid #ffce4b; padding-bottom:10px; width:50%; text-align:center">
				<h3>상담문의하기</h3>
			</div>
			<form name="counseling_form" action="/process/process.php" method="POST">
				<input type="hidden" name="mode" value="counseling_w" />
				<input type="hidden" name="userid" value="<?=$_SESSION["userid"]?>"/>
				<table cellpadding="0" cellspacing="0" class="c_table">
				<colgroup>
					<col width="10%"/>
					<col width="60%"/>
					<col width="10%"/>
					<col width="20%"/>
				</colgroup>
				<tr>
					<th>제목</th>
					<td>
						<input type="text" name="subject" style="width:99%; border:none; outline:none; padding-left:5px"/>
					</td>
					<th>분류</th>
					<td>
						<select name="category">
							<option value="">선택</option>
							<option value="1">견적상담</option>
							<option value="2">건의사항</option>
							<option value="3">문의사항</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>내용</th>
					<td colspan="3">
						<textarea rows="5" name="c_contents" id="c_contents" style="width:99%;border:none; outline:none; padding-left:5px"></textarea>
					</td>
				</tr>
				</table>
				<div style="margin:10px auto 20px auto; width:20%; text-align:center;">
					<input type="button" value="확인" class="c_btn" onclick="submit_chk()">
				</div>
			</form>
		</div>
	</div>
</div>
<? include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php"); ?>