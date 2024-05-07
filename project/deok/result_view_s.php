<?
$page = "three";
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
		$contents1 = mb_substr($contents1, "0", "150", "UTF-8")."...<br /><span style='font-weight: bold; color: blue; cursor:pointer' onclick='more_view(1)'>더보기</a>";
	}

} else {
	echo "<script>alert('잘못된 접속입니다.');location.href='/';</script>";
}

$sql = "select * from page_ad_list";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);

$search_code_sql = "select * from qulify_list where main_cate_cd = '$_GET[code]'";
$search_code_res = mysqli_query($conn, $sql);
$search_code_rs = mysql_fetch_array($search_code_res);

$main_code = $search_code_rs["main_cate_cd"];
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
				<? if ($subject1) { ?>
				<li id="list1"><div style="border-bottom:2px solid #ffce4b; padding-bottom:10px; margin-bottom:10px"><span style="font-weight:bold"><?=$subject1?></span></div><br /><?=$contents1?><br /><br /></li>
				<li id="list1_2" style="display:none"><div style="border-bottom:2px solid #ffce4b; padding-bottom:10px; margin-bottom:10px"><span style="font-weight:bold;"><?=$subject1?></span></div><?=$contents1_2?><br />
				<span onclick="more_view('0')" style="font-weight:bold; color:blue; cursor:pointer"/>접기</span><br /><br /></li>
				<li><a href='http://www.q-net.or.kr/crf005.do?id=crf00503&jmCd=<?=$_GET[code]?>&gSite=Q&gId' target='_blank'>
				<? } ?>
				<strong>시험과목 / 취득방법확인</strong></a><br /><br /></li>
			</ul>			
		</div>
		
		
		<? if ($main_code == $rs["code"]) { ?>
		<div class="bottom" style="padding:10px">	
			<div style="border-bottom:2px solid #ffce4b; padding:10px; margin-bottom:10px"><span style="font-weight:bold">학원정보</span></div>
				<? if ($rs["file_name1"]) { ?>
				<a href="<?=$rs["link1"]?>"><img src="/upload/user_banner/<?=$rs["file_name1"]?>" style="width:100%; height:100px"/></a>
				<? } 
				if ($rs["file_name2"]) { ?>
					<a href="<?=$rs["link2"]?>"><img src="/upload/user_banner/<?=$rs["file_name2"]?>" style="width:100%; height:100px"/></a>
				<? } 
				if ($rs["file_name3"]) { ?>
					<a href="<?=$rs["link3"]?>"><img src="/upload/user_banner/<?=$rs["file_name3"]?>" style="width:100%; height:100px"/></a>
				<? }
				if ($rs["file_name4"]) { ?>
					<a href="<?=$rs["link4"]?>"><img src="/upload/user_banner/<?=$rs["file_name4"]?>" style="width:100%; height:100px"/></a>
				<? }
				if ($rs["file_name5"]) { ?>
					<a href="<?=$rs["link5"]?>"><img src="/upload/user_banner/<?=$rs["file_name5"]?>" style="width:100%; height:100px"/></a>
				<? } ?>
		</div>
		<? } ?>
	</div>
</div>

<?
include_once ($_SERVER["DOCUMENT_ROOT"]."/footer.php");
?>
