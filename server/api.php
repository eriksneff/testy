<?php
// Uncomment these to Turn on Errors for this page
	//ini_set('display_errors','on');
	//error_reporting(E_ALL);
/**
  * This file Handles all calls to the mavenlink API.  It authenticates with OAuth 2.  
  * All Calls made to this file should be with POST
  *
  * @author Brett Exnowski <bexnowski@primitivespark.com>
  *
  * @date 6/24/2016
  *
  *
*/

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

//Mavenlink API Credentials
$clientId = 'a62e3a3afcab32f75f9518ed669aeba5d6fbd1c3c00b5d1788f6218b1558e522';
$clientSecret = 'b5a75a919a083be9954149de8e4200c9d70998283c3ee1bc76565525611e525e';

//Defaults
$action = 'createTimeEntry'; //This is the most common task we use
$date = date('Y-m-d'); 
$data = array();

//Set up the POST Data
if(isset($_POST['action'])) $action = $_POST['action']; //string
if(isset($_POST['date'])) $date = $_POST['date']; //YYYY-MM-DD
if(isset($_POST['workspace_id'])) $workspaceId = $_POST['workspace_id']; //integer
if(isset($_POST['task_id'])) $taskId = $_POST['task_id']; //integer
if(isset($_POST['notes'])) $notes = $_POST['notes']; //Text String, max length 
if(isset($_POST['time'])) $time = $_POST['time']; //In minutes
if(isset($_POST['entry_id'])) $entryId = $_POST['entry_id'];

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
    die("Could not connect to the API at this time :-/ ");
    
//If we successly connected to the API, lets do stuff!    
if($success)
{
    //Fetch Time Entries
    if($action == "getTimeEntries"){
        // Date format 2013-07-04T000000
        $start = date("Y-m-d\This", strtotime('last monday', strtotime('next week', time())));
        $end = date("Y-m-d\This", strtotime($date));
        $type = "GET";
        $url = "https://api.mavenlink.com/api/v1/time_entries.json?created_at_between=$start:$end";
        echo mavenToggl($url, $data, $type);
    }

    //Time Entry Creation
    if($action == "createTimeEntry"){

        //Define the Parameters
        $url = 'https://api.mavenlink.com/api/v1/time_entries.json';
        $type = 'POST'; 
        $data = array('time_entry' => array(
            'workspace_id'=>$workspaceId,
            'date_performed'=>$date,
            'time_in_minutes'=>$time,
            'notes'=>$notes,
            'story_id'=>$taskId
            )
        );
        
       //YOU make the call!
       echo mavenToggl($url, $data, $type);
    }
    
    if($action == "updateTimeEntry"){
        
        //Define the Parameters
        $url = "https://api.mavenlink.com/api/v1/time_entries/$entryId.json";
        $type = 'PUT'; 
        $data = array('time_entry' => array(
            'workspace_id'=>$workspaceId,
            'date_performed'=>$date,
            'time_in_minutes'=>$time,
            'notes'=>$notes,
            'story_id'=>$taskId
            )
        );
        
       //YOU make the call!
       echo mavenToggl($url, $data, $type);
    }
    
    //Delete Time Entry
    if($action == "deleteTimeEntry"){

       $url = "https://api.mavenlink.com/api/v1/time_entries/$entryId.json";
       $type = "DELETE";
       
       //YOU make the call!
       echo mavenToggl($url, $data, $type);
    }
    
    // Get Workspaces
    if($action == "getWorkspaces"){

       $url = "https://api.mavenlink.com/api/v1/workspaces.json";
       $type = "GET";
       
       //YOU make the call!
       echo mavenToggl($url, $data, $type);
    }
    
    // Get Stories (Tasks)
    if($action == "getTasks"){

       $url = "https://api.mavenlink.com/api/v1/stories.json?all_on_account=true";
       $type = "GET";
       
       //YOU make the call!
       echo mavenToggl($url, $data, $type);
    }
}

/** Function to trigger the API call to Mavenlink
    
    @parameter $url, the specific API URL to hit
    @type, the type of request. POST, GET, PUT, DELETE
    @data, array(), the POST Data Array
    
    @return the MAven link JSON response (EXCEPT DELETE, which has no repsonse, if successful)
    
*/

function mavenToggl($url, $data, $type = "POST"){
    global $client;
    
    $client->CallAPI(
        $url,
        $type, 
        $data, 
        array(
            'FailOnAccessError'=>true,
            'FollowRedirection'=>true
        ), $result);
        
     return json_encode($result);
}

?>