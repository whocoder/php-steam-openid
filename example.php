<html>
	<body style="background-color:black;text-align:center;margin:0 auto;">

<?php
	include 'php/steamopenid.php';

	if(isset($_GET['login']))
		$Login = true;
	else
		$Login = false;

	$Steam = new SteamOpenID($Login);

	if($Steam->loggedin()){
		$Info = $Steam->getsummaries();
?>
		<h1>Welcome, <?= $Info['personaname'] ?></h1>
		<img src='<?= $Info['avatarfull'] ?>' />
<?php
	}else{
?>
		<form action='?login' method='post'>
			<input type='image' src='http://cdn.steamcommunity.com/public/images/signinthroughsteam/sits_small.png' />
		</form>
<?php
	}
?>
	<span style="display:block;vertical-align:bottom;"><a href='http://steampowered.com/'>Powered by Steam</a></span>
	</body>
</html>
