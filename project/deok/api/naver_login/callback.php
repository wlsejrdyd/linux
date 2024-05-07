<?php
define( '_NAVER_GET_USERINFO_URL', 'https://apis.naver.com/nidlogin/nid/getUserProfile.xml');
session_start();
include_once ($_SERVER["DOCUMENT_ROOT"]."/lib/config.php");

  // 네이버 로그인 콜백
  $client_id = "28SJWvbW1OaiGvLL0Yo8";
  $client_secret = "EpWOF1AvGz";
  $code = $_GET["code"];
  $state = $_GET["state"];
  $redirectURI = urlencode("/index.php");
  $url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$redirectURI."&code=".$code."&state=".$state;
  $is_post = false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, $is_post);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $headers = array();
  $response = curl_exec ($ch);
  $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $data = json_decode($response, true); 
  $tokenArr = array( 
	'Authorization: '.$data['token_type'].' '.$data['access_token'] 
  );
//  echo "status_code:".$status_code."";

  curl_close ($ch);
  if($status_code == 200) {
//    var_dump($response);
		$ci = curl_init(); 
		curl_setopt($ci, CURLOPT_URL, _NAVER_GET_USERINFO_URL ); 
		curl_setopt($ci, CURLOPT_HTTPHEADER, $tokenArr ); 
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, true ); 
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false );
		$g = curl_exec($ci); 
		curl_close($ci); 
		$xml = simplexml_load_string($g);

		$userInfo = array( 
			'userID' => (string)$xml -> response -> email, 
			'name' => (string)$xml -> response -> name, //이름 
			'nickname' => (string)$xml -> response -> nickname, //닉네임 
			'age' => (string)$xml -> response -> age, //나이 
			'birth' => (string)$xml -> response -> birthday, //생일 
			'gender' => (string)$xml -> response -> gender, //성별 
			'profImg' => (string)$xml -> response -> profile_image //프로필이미지 
		);
		$_SESSION["s_userid"] = $userInfo["userID"];
		$_SESSION["SNS"] = "NAVER";
//	echo "<script>location.href='/index.php';</script>";
//	echo "<script>window.close();</script>";
$sql = "select * from member where email = '$_SESSION[s_userid]'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);
if ($rs["idx"]) {
	$_SESSION["userid"] = $_SESSION["s_userid"];
	echo "<script>window.close();opener.parent.window.location.href='/';</script>";
} else {
	echo "<script>window.close();opener.parent.window.location.href='/member/join.php';</script>";
}
	
  } else {
    echo "Error 내용:".$response;
  }
?>