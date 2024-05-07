<?
session_start();
unset($_SESSION["a_userid"]);

echo "<script>alert('로그아웃 되었습니다.');location.href='/admin'</script>";
?>