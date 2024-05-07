</div>
<?
$sql = "select * from footer_change where category='f'";
$res = mysqli_query($conn, $sql);
$rs = mysqli_fetch_array($res);

if ($_GET["contents1"]) {
	$contents1 = $_GET["contents1"];
} else {
	$contents1 = $rs["contents_1"];
}
if ($_GET["contents2"]) {
	$contents2 = $_GET["contents2"];
} else {
	$contents2 = $rs["contents_2"];
}
if ($_GET["contents3"]) {
	$contents3 = $_GET["contents3"];
} else {
	$contents3 = $rs["contents_3"];
}
?>
<div id="footer">
	<div class="wrap">
		<span style="font-weight: bold">
		<? 
		if ($contents1) { echo $contents1."<br />"; } 
		if ($contents2) { echo $contents2."<br />"; }
		if ($contents3) { echo $contents3."<br />"; }
		?>
		</span>
	</div>
</div>
</body>

</html>
