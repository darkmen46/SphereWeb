<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 02.12.2022 / 0:56:36
 */

/**
 * Проверка работоспособности ONLINE/OFFLINE сервер
 */

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\component\base\base;
use Ofey\Logan22\component\cache\cache;
use Ofey\Logan22\component\cache\dir;
use Ofey\Logan22\component\cache\timeout;
use Ofey\Logan22\model\db\sdb;
use Ofey\Logan22\model\user\player\player_account;

class online {

    private static array $server_status = [];

    public static function server_online_status() {
        $actualCache = cache::read(dir::server_online_status->show(), second: timeout::server_online_status->time());
        if($actualCache)
            return $actualCache;
        $data_connect_info = server::get_server_info();
        foreach($data_connect_info as $info) {

            $connect_login = false;
            $connect_game = false;
            $player_count_online = 0;

            if($info['check_server_online']){
                if(@fsockopen($info['check_loginserver_online_host'], $info['check_loginserver_online_port'], $errno, $errstr, 1)) {
                    $connect_login = true;
                }

                if(@fsockopen($info['check_gameserver_online_host'], $info['check_gameserver_online_port'], $errno, $errstr, 1)) {
                    $connect_game = true;
                    $player_count_online = player_account::extracted("count_online_player", $info);
                    if($player_count_online===false){
                        $player_count_online = 0;
                    }else if(!sdb::is_error()) {
                        $player_count_online = $player_count_online->fetch()["count_online_player"];
                    }else{
                        $player_count_online = -1;
                    }
                }
            }
            self::$server_status[] = [
                'id'                          => $info['id'],
                'name'                        => $info['name'],
                'rate_exp'                    => $info['rate_exp'],
                'chronicle'                   => $info['chronicle'],
                'launcher_enabled'            => $info['launcher_enabled'],
                'date_start_server'            => $info['date_start_server'],
                'connect_login'               => $connect_login,
                'connect_game'                => $connect_game,
                'player_count_online'         => $player_count_online,
                'get_default_page_id'         => server::get_default_desc_page_id($info['id']),
            ];
        }
        cache::save(dir::server_online_status->show(), self::$server_status);
        return self::$server_status;
    }
}