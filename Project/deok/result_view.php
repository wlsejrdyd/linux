<?
$page = "three";
$title = $_GET["name"]." 설명 | 빅덕(Vig Deok)";
include_once ($_SERVER["DOCUMENT_ROOT"]."/header.php");

if ($_GET["code"]) {
	$ch = curl_init();
	$url = 'http://openapi.q-net.or.kr/api/service/rest/InquiryInformationTradeNTQSVC/getList';
	$queryParams = '?'.urlencode('jmCd').'='.urlencode("$_GET[code]");
	$queryParams .= '&'.urlencode('ServiceKey').'=VRtAFdGiVIw1KCTmMrUfalruR6ph042QOPCyKaqx7N8N3ewbD6kwnNQOdgl2Z7Yyd%2BbzQLR3fqogst%2Fme7U5zA%3D%3D';

	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	curl_close($ch);
	$xml=simplexml_load_string($response) or die("Error: Cannot create object");

	$subject1 = strip_tags($xml->body->items->item[0]->infogb);
	$subject2 = strip_tags($xml->body->items->item[1]->infogb);
	$subject3 = strip_tags($xml->body->items->item[2]->infogb);

	$contents1 = strip_tags(array_pop(explode("}",$xml->body->items->item[0]->contents)));
	$contents1_2 = strip_tags(array_pop(explode("}",$xml->body->items->item[0]->contents)));
	$contents2 = strip_tags(array_pop(explode("}",$xml->body->items->item[1]->contents)));
	$contents2_2 = strip_tags(array_pop(explode("}",$xml->body->items->item[1]->contents)));
	$contents3 = strip_tags(array_pop(explode("}",$xml->body->items->item[2]->contents)));
	
	if (strlen($contents1) > "300") {
		$contents1 = mb_substr($contents1, "0", "150", "UTF-8")."...<br /><a href='javascript:more_view(1)'><span style='font-weight: bold; color: blue;'>더보기</a>";
	}

} else {
	echo "<script>alert('잘못된 접속입니다.');location.href='/';</script>";
}

$search_code_sql = "select * from qulify_list where sma_cate_cd = '$_GET[code]'";
$search_code_res = mysqli_query($conn, $search_code_sql);
$search_code_rs = mysqli_fetch_array($search_code_res);

$main_code = $search_code_rs["sma_cate_nm"];

$sql = "select * from page_ad_list where code = '$_GET[code]' order by idx desc";
$res = mysqli_query($conn, $sql);

$total_sql = "select count(*) from page_ad_list where code = '$_GET[code]' order by idx desc";
$total_res = mysqli_query($conn, $total_sql);
$total_rs = mysqli_fetch_array($total_res);
?>
<script type="text/javascript">
function more_view(num) {
	if (num == "0") {
		$("#list1").show();
		$("#list1_2").hide();
	} else if (num == "1") {
		$("#list1_2").show();
		$("#list1").hide();
	}
	
}
</script>
<div class="wrapB">
	<div class="cont pages2">
		<div class="top" style="padding:10px">
			<ul style="padding:0; margin: 0; list-style: none;">
				<li id="list1"><div style="border-bottom:2px solid #ffce4b; padding-bottom:10px; margin-bottom:10px"><span style="font-weight:bold"><?=$subject1?></span></div>
					<?=$contents1?><br /><br /></li>
				<li id="list1_2" style="display:none"><div style="border-bottom:2px solid #ffce4b; padding-bottom:10px; margin-bottom:10px"><span style="font-weight:bold;"><?=$subject1?></span></div><?=$contents1_2?><br />
				<a href="javascript:more_view('0')"><span style="font-weight:bold; color:blue;" />접기</span></a><br /><br /></li>
				<li><a href='http://www.q-net.or.kr/crf005.do?id=crf00503&jmCd=<?=$_GET[code]?>&gSite=Q&gId' target='_blank'>
				<strong>출제기준 / 취득방법 확인</strong></a><br /><br /></li>
			</ul>			
		</div>
		<div class="bottom" style="padding:10px">
			<div style="border-bottom:2px solid #ffce4b; padding:10px; margin-bottom:10px">
				<span style="font-weight:bold">학원정보 (<?=$main_code?>)</span>
			</div>
			<? if ($total_rs[0] > 0) { ?>
			<? for ($i=1; $i<=$rs = mysqli_fetch_array($res); $i++) { ?>
				<div class="class_info">
					<a href="<?=$rs["link"]?>" target="_blank">
						<table cellpadding="0" cellspacing="0" class="info_table">
						<? if ($phone_chk != "y") { ?>
						<colgroup>
							<col width="10%" />
							<col width="10%" />
							<col width="10%" />
							<col width="10%" />
							<col width="15%" />
							<col width="8%" />
							<col width="15%" />
							<col width="12%" />
						</colgroup>
						<? } else { ?>
						<colgroup>
							<col width="10%" />
							<col width="10%" />
							<col width="10%" />
							<col width="10%" />
							<col width="20%" />
						</colgroup>
						<? } ?>
						<?if ($phone_chk != "y") { ?>
						<tr>
							<th rowspan="3" align="center" style="border-bottom:2px solid black;">
								<img src="/upload/user_banner/<?=$rs["file_name"]?>" alt="<?=$rs["class_name"]?> 이미지" style="width:100%; height:auto; margin-top:5px"/>
							</th>
						</tr>
						<tr>
							<th>학원이름</th>
							<td><?=$rs["class_name"]?></td>
							<th>연락처</th>
							<td><?=$rs["pcs"]?></td>
							<th>지역</th>
							<td><?=$rs["area"]?></td>
							<th>국비지원여부</th>
							<td><span <? if ($rs["support"] == "y") { ?>style="color:blue"<? } else { ?>style="color:red"<? } ?>><? if($rs["support"] == "y") { echo "지원"; } else { echo "미지원"; } ?></span></td>
						</tr>
						<? } else { ?>
						<tr>
							<th rowspan="4" align="center" style="border-bottom:2px solid black; padding-right: 5px">
								<img src="/upload/user_banner/<?=$rs["file_name"]?>" alt="<?=$rs["class_name"]?> 이미지" style="width:100%; height:auto; margin-top:5px"/>
							</th>
						</tr>
						<tr>
							<th>학원이름</th>
							<td><?=$rs["class_name"]?></td>
							<th>연락처</th>
							<td><?=$rs["pcs"]?></td>
						</tr>
						<tr>
							<th>지역</th>
							<td><?=$rs["area"]?></td>
							<th>국비지원</th>
							<td><span <? if ($rs["support"] == "y") { ?>style="color:blue"<? } else { ?>style="color:red"<? } ?>><? if($rs["support"] == "y") { echo "지원"; } else { echo "미지원"; } ?></span></td>
						</tr>
						<? } ?>
						<tr>
							<td colspan="8" class="td_end"><?=$rs["link"]?></td>
						</tr>
						</table>
					</a>
				</div>
				<? }
			} else {
				echo "등록된 학원 정보가 없습니다.";
			} ?>
		</div>
	</div>
</div>

<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php");
?>
