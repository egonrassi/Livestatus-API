<?php

require "livestatus_client.php";

// FIXME: Do we really want unlimited memory?
ini_set('memory_limit', -1);

header('Content-Type: application/json');

$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$path_parts = explode('/', $path);
$request_method = $_SERVER['REQUEST_METHOD'];
$omdPath = $_SERVER['HOME'] . '/tmp/run/live';  // or use OMD_ROOT
$socket = '/var/nagios/var/rw/live';  // default
if (file_exists($omdPath)) {
    $info = @stat($omdPath);
    if ($info !== false && ($info['mode'] & 0170000) === 0140000) {
        $socket = $omdPath;
    }
}
$client = new LiveStatusClient($socket);

$client->pretty_print = true;

$action = $path_parts[1];

$response = [ 'success' => true ];

$args = json_decode(file_get_contents("php://input"),true);

try {
    switch ($action) {

    case 'acknowledge_problem':
        $client->acknowledgeProblem($args);
        break;
       
    case 'cancel_downtime':
        $client->cancelDowntime($args);
        break;

    case 'schedule_downtime':
        $client->scheduleDowntime($args);
        break;

    case 'enable_notifications':
        $client->enableNotifications($args);
        break;

    case 'disable_notifications':
        $client->disableNotifications($args);
        break;

    default:
        $response['content'] =  $client->getQuery($action, $_GET);

    }

} catch (LiveStatusException $e) {
    $response['success'] = false;
    $response['content'] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
    http_response_code($e->getCode());
}
echo json_encode($response);

?>
