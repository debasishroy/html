<?php
require_once('array2XML.php');
require_once 'Google/Client.php';
require_once 'Google/Service/YouTubeAnalytics.php';
require_once 'Google/Service/YouTube.php';
require_once 'Google/Service/Urlshortener.php';
require_once 'videosFromChannel.php';
$client = new Google_Client();
$token_id = $_GET['token_id'];
$type = $_GET['type'];
$link = mysql_connect('localhost', 'root', 'GProsl_2014');
mysql_select_db('ytmetrics');
$query = "SELECT * FROM tokens WHERE `id` = $token_id";
$result = mysql_query($query);
if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $token = $line['token'];
    $channel_id = $line['channel_id'];
    mysql_free_result($result);
    mysql_close($link);
    $videos = getVideosFromChannel($channel_id);
    $metrics = array(
        'views',
        'estimatedMinutesWatched',
        'averageViewDuration',
        'averageViewPercentage',
        'earnings',
        'monetizedPlaybacks',
        'playbackBasedCpm',
        'grossRevenue',
        'impressions',
        'impressionBasedCpm',
        'primaryAdGrossRevenue',
        'annotationClicks',
        'annotationClickableImpressions',
        'annotationClickThroughRate',
        'annotationCloses',
        'annotationClosableImpressions',
        'annotationCloseRate',
        'annotationImpressions',
        'likes',
        'dislikes',
        'shares',
        'comments',
        'favoritesAdded',
        'favoritesRemoved',
        'subscribersGained',
        'subscribersLost'
    );
    $fp = fopen("Report." . $type, "w+");
    $file = ("Report." . $type);
    $xmlArray = array();
    if ($type == "csv") {
        fputcsv($fp, $metrics);
    }
    foreach ($videos as $video) {
        $api_response = array(
            'video' => $video['title'],
            'videoId' => $video['id'],
            'views' => '',
            'estimatedMinutesWatched' => '',
            'averageViewDuration' => '',
            'averageViewPercentage' => '',
            'earnings' => '',
            'monetizedPlaybacks' => '',
            'playbackBasedCpm' => '',
            'grossRevenue' => '',
            'impressions' => '',
            'impressionBasedCpm' => '',
            'primaryAdGrossRevenue' => '',
            'annotationClicks' => '',
            'annotationClickableImpressions' => '',
            'annotationClickThroughRate' => '',
            'annotationCloses' => '',
            'annotationClosableImpressions' => '',
            'annotationCloseRate' => '',
            'annotationImpressions' => '',
            'likes' => '',
            'dislikes' => '',
            'shares' => '',
            'comments' => '',
            'favoritesAdded' => '',
            'favoritesRemoved' => '',
            'subscribersGained' => '',
            'subscribersLost' => ''
        );
        $client->setAccessToken($token);
        $service = new Google_Service_YouTube($client);
        $data = $service->channels->listChannels('snippet', array('mine' => 'true',));
        $item = $data->items[0]->id;
        $id = "channel==$item";
        $analytics = new Google_Service_YouTubeAnalytics($client);
        $service = new Google_Service_YouTube($client);
        $start_date = date("Y-m-d", time() - 3600 * 24 * 30);
        $end_date = date("Y-m-d");
        $optparams = array('filters' => "video==" . $video['id']);
        foreach ($metrics as $metric) {
            try {
                $api = $analytics->reports->query($id, $start_date, $end_date, $metric, $optparams);
                if (isset($api['rows'])) $api_response[$metric] = $api['rows'][0][0];
            } catch (Exception $e) {
                throw new Exception("Google API Exception: ", $e->getMessage());
            }
        }
        if ($type == 'xml') {
            array_push($xmlArray, $api_response);
            $converter = new Array2XML();
            $xmlStr = $converter->convert($api_response);
            fwrite($fp, $xmlStr);
            fclose($fp);
        } elseif ($type == 'csv') {
            fputcsv($fp, $api_response);
        }

    }
    if ($type == 'xml') {
        $converter = new Array2XML();
        $xmlStr = $converter->convert($xmlArray);
        fwrite($fp, $xmlStr);
    }
    fclose($fp);
    header("Content-Type: application/octet-stream");
    header("Accept-Ranges: bytes");
    header("Content-Length: " . filesize($file));
    header("Content-Disposition: attachment; filename=" . $file);
    readfile($file);
    unlink($file);
} else {
    mysql_free_result($result);
    mysql_close($link);
}