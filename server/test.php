<?php
// Uncomment these to Turn on Errors for this page
	#ini_set('display_errors','on');
	#error_reporting(E_ALL);
  // header("Access-Control-Allow-Origin: *");
/*
 * login_with_mavenlink.php
 *
 * @(#) $Id: login_with_mavenlink.php,v 1.1 2014/05/15 09:41:45 mlemos Exp $
 *
 */

	/*
	 *  Get the http.php file from http://www.phpclasses.org/httpclient
	 */
	require('http.php');
	require('oauth_client.php');

	$client = new oauth_client_class;
	$client->server = 'Mavenlink';

	$client->debug = false;
	$client->debug_http = true;
	$client->redirect_uri = 'https://'.$_SERVER['HTTP_HOST'].
		dirname(strtok($_SERVER['REQUEST_URI'],'?'));

	$client->client_id = 'a62e3a3afcab32f75f9518ed669aeba5d6fbd1c3c00b5d1788f6218b1558e522'; $application_line = __LINE__;
	$client->client_secret = 'b5a75a919a083be9954149de8e4200c9d70998283c3ee1bc76565525611e525e';

	if(strlen($client->client_id) == 0
	|| strlen($client->client_secret) == 0)
		die('Please create a Mavenlink application in '.
			'https://app.mavenlink.com/oauth/applications/new , and in the line '.
			$application_line.' set the client_id to Client ID and '.
			'client_secret with Client Secret. '.
			'The callback URL must be '.$client->redirect_uri.' but make sure '.
			'it is a secure URL (https://).');

	/* API permissions, empty is the default for this application
	 */
	$client->scope = '';
	if(($success = $client->Initialize()))
	{
		if(($success = $client->Process()))
		{
			if(strlen($client->authorization_error))
			{
				$client->error = $client->authorization_error;
				$success = false;
			}
			elseif(strlen($client->access_token))
			{
				$success = $client->CallAPI(
					'https://api.mavenlink.com/api/v1/users/me.json',
					'GET', array(), array(
						'FailOnAccessError'=>true,
						'FollowRedirection'=>true
					), $user);
			}
		}
		$success = $client->Finalize($success);
	}
	if($client->exit)
		exit;
	if($success && isset($_POST['time']))
	{

           //Set up the parameters
           $time = $_POST['time'];
           $workspace = $_POST['workspace'];
           $task = $_POST['task'];
           $notes = $_POST['notes'];
           
           // Save Time Entry
           $client->CallAPI(
            'https://api.mavenlink.com/api/v1/time_entries.json',
					'POST', array('time_entry' => array(
                        'workspace_id'=>$workspace,
                        'date_performed'=>date('Y-m-d'), //YYYY-MM-DD
                        'time_in_minutes'=>$time,
                        'notes'=>$notes,
                        'story_id'=>$task
                        )
                    ), array(
						'FailOnAccessError'=>true,
						'FollowRedirection'=>true
					), $result);
                    
           //Set up the JSON Callback         
           //echo $_GET['jsoncallback'] . '(' . json_encode($result) . ');';
           echo json_encode($result);
           exit;   
    }elseif($success){
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Mavenlink OAuth client results</title>
</head>
<body>
<?php
		$key = Key($user->users);
		echo '<h1>', HtmlSpecialChars($user->users->{$key}->full_name),
			' you have logged in successfully with Mavenlink!</h1>';
		echo '<pre>', HtmlSpecialChars(print_r($user, 1)), '</pre>';
        
        // Time Entries
        $client->CallAPI(
            'https://api.mavenlink.com/api/v1/time_entries.json',
					'GET', array(), array(
						'FailOnAccessError'=>true,
						'FollowRedirection'=>true
					), $time);
        //Stories (Tasks)
        $client->CallAPI(
    'https://api.mavenlink.com/api/v1/stories.json?all_on_account=true',
            'GET', array(), array(
                'FailOnAccessError'=>true,
                'FollowRedirection'=>true
            ), $stories);
        //Workspaces
        $client->CallAPI(
    'https://api.mavenlink.com/api/v1/workspaces.json',
            'GET', array(), array(
                'FailOnAccessError'=>true,
                'FollowRedirection'=>true
            ), $work);
            
    #print_r($time);
    #print_r($stories);
    //Grab All the Workspaces (Projects)
    $workspaces = $work->workspaces;
    $workSelect = array();
    foreach($workspaces as $key => $workspace){
       $workSelect[$key]['title'] = $workspace->title;
       $workSelect[$key]['id'] = $workspace->id;
    }
    //Grab all the Stories (Tasks)
    $tasks = $stories->stories;
    $taskSelect = array();
    foreach($tasks as $key => $task){
       $taskSelect[$key]['title'] = $task->title;
       $taskSelect[$key]['id'] = $task->id;
       $taskSelect[$key]['workspace_id'] = $task->workspace_id;
    }
    
    $checked = false;
          
?>
<label for="project">Project</label>
<select name="project" id="project">
    <option value="all">All</option>
    <?php foreach($workSelect as $option){
      echo "<option value='".$option['id']."'>".$option['title']."</option>"; 
    }
    ?>
</select>
<hr />
<label for="task">Task</label>
<select name="task" id="task">
    <?php foreach($taskSelect as $option){
      echo "<option value='".$option['id']."' data-workspace='".$option['workspace_id']."'>".$option['title']."</option>";
    }
    ?>
</select>
<label for="time">Time:</label>
<input type="time" value="00:00:00" placeholder="00:00:00" id="time" name="time" />
<label for="billable">Billable:</label>
<input type="checkbox" name="billable" <?php echo ($checked ? "checked" : ""); ?> />
<label for="notes">Notes:</label>
<input type="text" name="notes" id="notes" placeholder="What ya thinkin about?"/>
<input type="submit" id="submit" value="Save" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    jQuery(document).ready( function($){
        $('#project').on('change', function(){
           var pId = $(this).val();
           $('#task option').each( function(i){
              var wId = $(this).data('workspace');
              if(pId == wId || pId === 'all'){
                  $(this).show();
              }else{
                  $(this).hide();
              }
           });
        });
        
        $('#submit').on('click', function(){
            //Grab the form values
            var workspace = $('#project').val();
            var task = $('#task').val();
            var time = $('#time').val();
            var notes = $('#notes').val();
            
            $.ajax({
                url: 'https://maventoggl.primitivespark.com/index.php',
                //dataType: 'jsonp',
                //jsonp: 'jsoncallback',
                timeout: 15000,
                type: 'POST',
	            data:{'workspace': workspace, 'task': task, 'time': time, 'notes': notes},
                error: {},
                cache: false,
                success: function(data){
                    console.log(data);
                    }
            });
            
        });
    });
</script>
</body>
</html>
<?php
	}
	else
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>OAuth client error</title>
</head>
<body>
<h1>OAuth client error</h1>
<pre>Error: <?php echo HtmlSpecialChars($client->error); ?></pre>
</body>
</html>
<?php
	}

?>