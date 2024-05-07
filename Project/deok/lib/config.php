<?
$user = "wlsejrdyd";
$passwd = "qlrejr1!";
$db = "wlsejrdyd";
$host = "localhost";

$conn = mysqli_connect($host, $user, $passwd, $db) or die ("DB Connect Failed");
mysqli_select_db($conn, $db);

//mysql_query("set names euckr");

?>