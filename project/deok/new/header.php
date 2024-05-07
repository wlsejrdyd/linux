<?
session_start();
$session_userid = $_SESSION["userid"];
// DB정보
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");

//모바일 체크
$phone_chk = "";
$MobileArray  = array("iphone","lgtelecom","skt","mobile","samsung","nokia","blackberry","Android","android","sony","iphone");
foreach ($MobileArray as $k => $v) {
	if (strpos($_SERVER["HTTP_USER_AGENT"], $v) !== false) {
		$phone_chk = "y";
	}
}

// 푸터 정보 가져오기
$sql = "select * from footer_change where category = 'h'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);

//타이틀 정보
if ($page == "main") {
	$title = "자격증 검색 | 빅덕(Vig Deok)";
	$contents = $rs["contents_1"];
} else if ($page == "two") {
	$contents = $rs["contents_2"];
} else if ($page == "three") {
	$contents = $rs["contents_3"];
}

// 사이트 설명
$description = "취득가능한 자격증 정보제공, 자격증별 학원 정보도 함께 확인가능합니다. 맞춤 학원정보 서비스를 통해 국비지원을 받아보세요.";
?>
<!DOCTYPE html>
<html lang="ko">

<head>
	<title><?=$title?></title>
	<meta charset="utf-8" />
	<meta name="viewport" content="user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,width=device-width">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="title" content="<?=$title?>">
	<meta name="writer" content="빅덕(Vig Deok)">
	<meta name="description" content="<?=$description?>">
	<meta name="keywords" content="자격증,자격증검색,학원검색,국비지원,국비지원학원,취준생,스터디,빅덕,큐넷,직업정보,Vig Deok">

	<meta property="og:title" content="<?=$title?>">
	<meta property="og:description" content="<?=$description?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="http://www.deok.kr">
	<meta property="og:site_name" content="<?=$title?>">
	<meta property="og:locale" content="ko_KR">
	<meta property="og:image" content="http://www.deok.kr/images/logo.png">
	<meta property="og:image:width" content="200">
	<meta property="og:image:height" content="200">
	<meta name="NaverBot" content="All"/>
	<meta name="NaverBot" content="index,follow"/>
	<meta name="Yeti" content="All"/>
	<meta name="Yeti" content="index,follow"/>

	<meta name="naver-site-verification" content="2d6d898018a90aac1d3464920b2b45dc31fbcb0c"/>

	<script src="https://apis.google.com/js/api:client.js"></script>
	<script src="//developers.kakao.com/sdk/js/kakao.min.js"></script>

	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-132835937-2"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-132835937-2');
	</script>

	
	<link rel="shortcut icon" type="image/x-icon" href="http://www.deok.kr/favicon.ico" />
	<link rel="icon"type="image/x-icon" href="http://www.deok.kr/favicon.ico" />
	<link rel="canonical" href="http://www.deok.kr"/>

	<link rel="stylesheet" href="/css/reset.css">
	<link rel="stylesheet" href="/css/base.css">
	<link rel="stylesheet" href="/css/style.css">

</head>

<body>
	<script>
	// 구글 로그인
	  var googleUser = {};
	  var startApp = function() {
		gapi.load('auth2', function(){
		  // Retrieve the singleton for the GoogleAuth library and set up the client.
		  auth2 = gapi.auth2.init({
			client_id: '1098761984262-6bgvuiodseiai6os0g6no2pag2ijpgpu.apps.googleusercontent.com',
			cookiepolicy: 'single_host_origin',
			// Request scopes in addition to 'profile' and 'email'
			//scope: 'additional_scope'
		  });
		  attachSignin(document.getElementById('customBtn'));
		});
	  };

	  function attachSignin(element) {
		auth2.attachClickHandler(element, {},
			function(googleUser) {
			  location.href='/api/google_login/google_login.php?userid=' + googleUser.getBasicProfile().getEmail();
			}, function(error) {
			  console.log(JSON.stringify(error, undefined, 2));
			});
	  }
	</script>
	<div class="menu-section">
		<div class="menu-toggle">
			<div class="one"></div>
			<div class="two"></div>
			<div class="three"></div>
		</div>

		<nav>
			<ul role="navigation" class="hidden">
				<? if (!$_SESSION["userid"]) { ?>
				<li><a href="/member/login.php">로그인</a></li>
				<li><a href="/member/join.php">회원가입</a></li>
				<? } else { ?>
				<li><a href="/lnb/counseling_write.php">상담문의</a></li>
				<li><a href="javascript:alert('개발중입니다.')">마이페이지</a></li>
				<li><a href="javascript:alert('개발중입니다.')">개인레슨</a></li>
				<? } ?>
		    </ul>
		</nav>
	</div>
<!-- 	<div id="add01"> -->
<!-- 		광고1 -->
<!-- 	</div> -->
	<div id="top_menu">
		<p><?=$contents?></p>
		<div class="member_menu">
		<? if ($_SESSION["userid"]) { ?>
		<!--<a href="/member/mypage.php">마이페이지</a> | --><a href="/member/logout.php">로그아웃</a>
		<? } else { ?>
		<a href="/member/login.php">로그인</a> | <a href="/member/join.php">회원가입</a>
		<? } ?>
		</div>
	</div>

	<div class="wrap">
		<a href="/"><img src="/images/logo.jpg" alt="빅덕(Vig Deok) 로고" class="logo"></a>
	</div>

	<div id="content">
		<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="/js/index.js"></script>