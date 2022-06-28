<?php

namespace App\Http\Controllers;

use App\Models\SynchronizeLogs;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use DateInterval;

class HelperController extends Controller
{
    //** function to save last synchronization */
    public function last_sync ($user_type)
    {
        $rules = [
            'user_type' => 'in:student,mentor,editor,alumni'
        ];

        $validator = Validator::make(['user_type' => $user_type], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $data = SynchronizeLogs::where('user_type', $user_type)->where('status', 'success')->orderBy('created_at', 'desc')->first();
        return response()->json(['success' => true, 'data' => date('F d, Y H:i:s', strtotime($data->created_at))]);
    }

    //** external paginate function */
    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
    }

    //* remove underscore
    public function perfect_sentence($sentence)
    {
        $raw = str_replace('_', ' ', $sentence);
        return ucwords($raw);
    }

    //* Youtube function
    public function curl_get($url){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $return = curl_exec($curl);
        curl_close($curl);
        return $return;
    }

    public function youtube_id_from_url ($url)
    {
        $pattern = '%^# Match any youtube URL
        (?:https?://)? # Optional scheme. Either http or https
        (?:www\.)? # Optional www subdomain
        (?: # Group host alternatives
        youtu\.be/ # Either youtu.be,
        | youtube\.com # or youtube.com
        (?: # Group path alternatives
        /embed/ # Either /embed/
        | /v/ # or /v/
        | /watch\?v= # or /watch\?v=
        ) # End path alternatives.
        ) # End host alternatives.
        ([\w-]{10,12}) # Allow 10-12 for 11 char youtube id.$%x';

        $result = preg_match($pattern, $url, $matches);
        if (false !== $result) {
            return $matches[1];
        }

        return false;
    }

    public function getVideolength($videoid='') {
        // define('YT_API_URL', 'http://gdata.youtube.com/feeds/api/videos?q=mHA4BxZTXlk');
        // define('YT_API_URL', 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=&key={YOUR_API_KEY}')
        $video_id = $videoid;
        define('YT_API_URL', 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails%2Cstatistics&id='.$video_id.'&key='.env('YOUTUBE_API_KEY'));
        //Using cURL php extension to make the request to youtube API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, YT_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //$feed holds a rss feed xml returned by youtube API
        $feed = curl_exec($ch);
        curl_close($ch);

        //Using SimpleXML to parse youtube's feed
        $xml = json_decode($feed);
        $vid_duration = $xml->items[0]->contentDetails->duration;
        $video_length = $this->convertDuration($vid_duration);

        return $video_length;
    }

    public function videoDetails($url){
        $video_url = parse_url($url);
        if ($video_url['host'] == 'www.youtube.com' || 
            $video_url['host'] == 'youtube.com') {
            $videoid = $this->youtube_id_from_url($url);
            $video_length = $this->getVideolength($videoid);
            return $video_length;
        }else if ($video_url['host'] == 'www.youtu.be' || 
                $video_url['host'] == 'youtu.be') {

            $videoid = $this->youtube_id_from_url($url);
            $video_length = $this->getVideolength($videoid);
            return $video_length;
        }else if ($video_url['host'] == 'www.vimeo.com' || 
                $video_url['host'] == 'vimeo.com') {
            $oembed_endpoint = 'http://vimeo.com/api/oembed';
            $json_url = $oembed_endpoint.'.json?url='.
                        rawurlencode($video_url).'&width=640';
            $video_arr = $this->curl_get($json_url);
            $video_arr = json_decode($video_arr, TRUE);
            $vid_duration = $video_arr['duration'];
            $video_length = 
            str_pad(floor($vid_duration / 60), 2, '0', STR_PAD_LEFT) . ':'
            .str_pad($vid_duration % 60, 2, '0', STR_PAD_LEFT);
            return $video_length;
        }
    }

    public function convertDuration($duration)
    {
        $di = new DateInterval($duration);
    
        $totalSec = 0;
        if ($di->h > 0) { // hour
        $totalSec+=$di->h*3600;
        }
        if ($di->i > 0) { // minute
        $totalSec+=$di->i*60;
        }
        $totalSec+=$di->s;
        
        return $totalSec;
    }
}
