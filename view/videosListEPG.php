<?php

if (!function_exists('isPIDRunning')) {

    function isPIDRunning($pid) {
        return file_exists("/proc/$pid");
    }

}
error_reporting(E_ALL);
if (!empty($_REQUEST['date_default_timezone'])) {
    $dt = new DateTime();
    $dt->setTimezone(new DateTimeZone($_REQUEST['date_default_timezone']));
    date_default_timezone_set($_REQUEST['date_default_timezone']);
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$global['systemRootPath'] = dirname(__FILE__) . "/../";
$videosListToLivePath = $global['systemRootPath'] . 'videos/videosListToLive/';
$epg = new stdClass();
$epg->generated = time();
$epg->generated_date = date("Y-m-d H:i:s");
$epg->sites = array();
if (is_dir($videosListToLivePath)) {
    if ($dh = opendir($videosListToLivePath)) {
        while (($site = readdir($dh)) !== false) {
            if ($site == "." || $site == "..") {
                continue;
            }
            $dir = "{$videosListToLivePath}{$site}/epg/";
            if (is_dir($dir)) {
                if ($dh2 = opendir($dir)) {
                    while (($file2 = readdir($dh2)) !== false) {
                        if ($file2 == "." || $file2 == "..") {
                            continue;
                        }
                        $channel_id = 0;
                        $playlist_id = 0;

                        preg_match("/channel_([0-9]+)_playlist_([0-9]+).json/", $file2, $matches);

                        $channel_id = intval(@$matches[1]);
                        $playlist_id = intval(@$matches[2]);
                        if (empty($channel_id) || empty($playlist_id)) {
                            continue;
                        }

                        $epgJsonFile = "{$dir}{$file2}";
                        $json = json_decode(file_get_contents($epgJsonFile));
                        $json->liveDir = "/HLS/live/{$json->key}_{$json->playlists_id}/";
                        $zeroFile = $json->liveDir . "0.ts";
                        if (empty($json->created)) {
                            $json->created = filectime($zeroFile);
                            $json->created_date = date("Y-m-d H:i:s", $json->created);
                            file_put_contents($epgJsonFile, json_encode($json));
                        } else {
                            $created = filectime($zeroFile);
                            if (!empty($created) && $created != $json->created) {
                                $json->created = $created;
                                $json->created_date = date("Y-m-d H:i:s", $json->created);
                                file_put_contents($epgJsonFile, json_encode($json));
                            }
                        }
                        $currentProgramStart = $json->created;
                        $json_total = count($json->programme);
                        $json->finished = false;
                        $json->isPIDRunning = isPIDRunning($json->pid);
                        $json_not_finished = 0;
                        foreach ($json->programme as $key => $value) {
                            $json->programme[$key]->current = false;
                            $json->programme[$key]->finished = false;

                            $json->programme[$key]->start = $currentProgramStart;
                            $json->programme[$key]->start_date = date("Y-m-d H:i:s", $json->programme[$key]->start);
                            $json->programme[$key]->stop = $currentProgramStart + $json->programme[$key]->duration_seconds;
                            $json->programme[$key]->stop_date = date("Y-m-d H:i:s", $json->programme[$key]->stop);
                            $json->programme[$key]->seconds_left_to_finish = $json->programme[$key]->stop - $epg->generated;
                            $json->programme[$key]->seconds_left_to_start = 0;
                            $currentProgramStart = $json->programme[$key]->stop;

                            if ($json->programme[$key]->stop > $epg->generated) {
                                $json_not_finished++;
                            } else {
                                $json->programme[$key]->finished = true;
                            }
                            if ($json->programme[$key]->stop < $epg->generated) {
                                $json->programme[$key]->seconds_left_to_finish = 0;
                                $json->programme[$key]->finished = $epg->generated - $json->programme[$key]->stop;
                            } else if ($json->programme[$key]->start < $epg->generated) {
                                $json->programme[$key]->current = $epg->generated - $json->programme[$key]->start;
                            } else if ($json->programme[$key]->start > $epg->generated) {
                                $json->programme[$key]->seconds_left_to_start = $json->programme[$key]->start - $epg->generate;
                            }
                        }
                        $json_total = count($json->programme);
                        $json_finished = $json_total - $json_not_finished;
                        if (empty($json_not_finished)) {
                            $json->finished = true;
                            //continue;
                        }

                        if (empty($epg->sites[$site])) {
                            $epg->sites[$site] = array();
                            $epg->sites[$site] = array('site' => $site, 'channels' => array());
                        }

                        if (empty($epg->sites[$site]['channels'][$channel_id])) {
                            $epg->sites[$site]['channels'][$channel_id] = new stdClass();
                            $epg->sites[$site]['channels'][$channel_id]->users_id = $json->users_id;
                            $epg->sites[$site]['channels'][$channel_id]->name = $json->name;
                            $epg->sites[$site]['channels'][$channel_id]->icon = $json->icon;
                            $epg->sites[$site]['channels'][$channel_id]->bg = $json->bg;
                            $epg->sites[$site]['channels'][$channel_id]->playlists = array();
                        }
                        $json->playlist_id = $playlist_id;
                        unset($json->users_id);
                        unset($json->name);
                        unset($json->icon);
                        unset($json->bg);

                        if (empty($epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id])) {
                            $epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id] = $json;
                        } else {
                            $epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id]->programme = array_merge($epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id]->programme, $json->programme);
                        }
                        if (empty($epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id]->programme)) {
                            unset($epg->sites[$site]['channels'][$channel_id]->playlists[$playlist_id]);
                            continue;
                        }
                        if (empty($epg->sites[$site]['channels'][$channel_id]->playlists)) {
                            unset($epg->sites[$site]['channels'][$channel_id]);
                            continue;
                        }
                        /*
                          $epg->sites[$site]['channels'][$channel_id]->playlists = array_values($epg->sites[$site]['channels'][$channel_id]->playlists);
                          usort($epg->sites[$site]['channels'][$channel_id]->playlists, function($a, $b) {
                          return strcmp($a->start, $b->start);
                          });
                         */
                    }
                    closedir($dh2);
                }
            } else {
                continue;
            }
            /*
              $epg->sites[$site]['channels'] = array_values($epg->sites[$site]['channels']);
              usort($epg->sites[$site]['channels'], function($a, $b) {
              return strcmp($a[0]->playlists[0]->start, $b[0]->playlists[0]->start);
              });

             */
        }
        closedir($dh);
    }
}
die(json_encode($epg));
