<?php 
//Include Mavenlink App Classes
require 'lib/mavenlink_api.php';

$client = new MavenlinkApi('a1b36de3985d5d2db9d5ad804aaad5d12e730d3b1a5e7d4a2621a946999ca17b');

$userJson = $client->getCurrentUser();
$userDecodedJson = json_decode($userJson, true);

echo "My Data: <br />";
print_r($userDecodedJson);

$usersJson = $client->getUsers();
print_r("users!");
print_r($usersJson);
print_r("\n\n\n\n\n\n\n\n");

?>

<html>
	<head>

	</head>
	<body>
		<?php #echo 'MavenToggl in da house!!' ?>
        <?php
        
        ?>
	</body>
</html>