<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/TrashWeb
 * Date: 18.04.2023 / 5:52:26
 */

namespace Ofey\Logan22\model\ticket;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\auth\auth;
use Verot\Upload\Upload;

class ticket_model {

    //Список последних тикетов
    public static function all($type = "all"): array {
        if($type == "open") {
            return sql::getRows("SELECT
            tickets.id, 
            tickets.user_id,
            SUBSTRING(tickets.content, 1, 100) as content, 
            tickets.date, 
            tickets.`close`,
            tickets.`private`
            FROM tickets WHERE
            `close` = 0 ORDER BY date DESC");
        }
        if($type == "close") {
            return sql::getRows("SELECT
            tickets.id, 
            tickets.user_id,
            SUBSTRING(tickets.content, 1, 100) as content, 
            tickets.date, 
            tickets.`close`,
            tickets.`private`
            FROM tickets
            where close=1 ORDER BY `date` DESC");
        }
        return sql::getRows("SELECT
            tickets.id, 
            tickets.user_id,
            SUBSTRING(tickets.content, 1, 100) as content, 
            tickets.date, 
            tickets.`close`,
            tickets.`private`
            FROM tickets
            ORDER BY `date` DESC");
    }

    static public function get_info($id, $loadComment = true) {
        $ticket = sql::getRow("SELECT tickets.*, users.`name`, users.avatar, users.avatar_background FROM tickets INNER JOIN users ON tickets.user_id = users.id WHERE tickets.id = ?", [$id]);
        if($ticket === false) {
            return false;
        }
        $ticket_img = sql::getRows("SELECT * FROM tickets_image WHERE ticket_id = ?", [$id]);

        if($loadComment) {
            $comments = sql::getRows("SELECT tickets_comment.*, users.`name`, users.avatar, users.avatar_background FROM tickets_comment INNER JOIN users ON tickets_comment.user_id = users.id WHERE ticket_id = ?", [$id]);
            foreach($comments as &$comment) {
                $comment['images'] = self::getCommentImage($comment['id']);
            }
            $ticket['comments'] = $comments;
        }
        $ticket['images'] = $ticket_img;
        return $ticket;
    }

    public static function get_ticket($ticket_id) {
        return sql::getRow("SELECT * FROM tickets WHERE id = ?", [$ticket_id]);
    }

    //Информация о комментарии
    public static function get_comment($ticket_id, $comment_id) {
        $data = sql::getRow("SELECT * FROM tickets_comment WHERE ticket_id = ? AND id = ?", [
            $ticket_id,
            $comment_id,
        ]);
        $ticket_img = sql::getRows("SELECT * FROM tickets_comment_image WHERE comment_id = ?", [$comment_id]);
        $data['images'] = $ticket_img;
        return $data;
    }

    private static function getCommentImage($comment_id): array {
        return sql::getRows("SELECT image FROM tickets_comment_image WHERE comment_id = ?", [$comment_id]);
    }

    //Кол-во созданных последних тикетов за N часов
    private static function countCreateLastTime($user_id, $hour){
        $sql = "SELECT COUNT(*) AS count FROM tickets WHERE user_id = ? AND date >= NOW() - INTERVAL ? HOUR;";
        return sql::run($sql, [$user_id, $hour])->fetch()['count'];
    }

    //Кол-во ответов в тикетах за N часов
    private static function countCommentLastTime($user_id, $hour){
        $sql = "SELECT COUNT(*) AS count FROM tickets_comment WHERE user_id = ? AND date >= NOW() - INTERVAL ? HOUR;";
        return sql::run($sql, [$user_id, $hour])->fetch()['count'];
    }

    public static function add() {
        if($count = self::countCreateLastTime(auth::get_id(), 1)){
            if($count>=5){
                board::notice(false, lang::get_phrase(457));
            }
        }
        $content = isset($_POST['content']) ? trim($_POST['content']) : null;
        if(10 > mb_strlen($content)){
            board::notice(false, lang::get_phrase(458));
        }

        $files = $_FILES['files'] ?? null;

        if($content === null && $files === null) {
            board::notice(false, lang::get_phrase(341));
            return;
        }
        $private = isset($_POST['private']) && $_POST['private'] === 'true';

        sql::run("INSERT INTO `tickets` (`user_id`, `content`, `private`, `hide`) VALUES (?, ?, ?, 1)", [
            auth::get_id(),
            $content,
            $private,
        ]);
        $ticket_ID = sql::lastInsertId();

        if($files !== null) {
            self::processFiles($files, $ticket_ID, false);
        }

        board::alert([
            "ok"       => true,
            "redirect" => "/ticket/" . $ticket_ID,
        ]);
    }

    public static function addComment() {
        if($count = self::countCommentLastTime(auth::get_id(), 1)){
            if($count>=10){
                board::notice(false, lang::get_phrase(454));
            }
        }
        $content = isset($_POST['content']) ? trim($_POST['content']) : null;
        $files = $_FILES['files'] ?? null;
        $ticketID = $_POST['ticketID'] ?? null;

        $ticketInfo = self::get_info($ticketID);
        if($ticketInfo['close']) {
            board::notice(false, lang::get_phrase(360));
        }
        if($content === null and $files === null) {
            board::notice(false, lang::get_phrase(341));
            return;
        }

        sql::run("INSERT INTO `tickets_comment` ( `ticket_id`, `user_id`, `content`) VALUES (?, ?, ?)", [
            $ticketID,
            auth::get_id(),
            $content,
        ]);
        $comment_ID = sql::lastInsertId();

        if($files !== null) {
            self::processFiles($files, $comment_ID, true);
        }

        board::alert([
            "ok" => true,
        ]);
    }

    static private function processFiles($files, $ticketID, $comment = true) {
        $count = count($files['name']);
        if($count > 5) {
            board::notice(false, lang::get_phrase(342));
            return;
        }
        for($i = 0; $i < $count; $i++) {
            $handle = new Upload($files['tmp_name'][$i]);
            if($handle->uploaded) {
                $handle->allowed = ['image/*'];
                $handle->mime_check = true;
                $handle->file_max_size = 5 * 1024 * 1024; // Разрешенная максимальная загрузка 4mb

                $filename = md5(mt_rand(1, 100000) + time());
                $handle->file_new_name_body = $filename;
                $handle->image_resize = true;
                $handle->image_x = 250;
                $handle->image_ratio_y = true;
                $handle->file_name_body_pre = 'thumb_';
                $handle->image_convert = 'webp';
                $handle->webp_quality = 95;
                $handle->process('./uploads/tickets');
                if(!$handle->processed) {
                    if($comment){
                        self::removeComment($ticketID);
                    }else{
                        self::remove($ticketID);
                    }
                    $fileName = $files['name'][$i];
                    $msg = lang::get_phrase(455) . " '" . $fileName . "'\n" . lang::get_phrase(456) . " : " . $handle->error;
                    board::notice(false,  $msg);
                }
                $handle->file_new_name_body = $filename;
                $handle->image_resize = true;
                $handle->image_x = 1200;
                $handle->image_ratio_y = true;
                $handle->image_convert = 'webp';
                $handle->webp_quality = 85;
                $handle->process('./uploads/tickets');
                if($handle->processed) {
                    $handle->clean();
                    if($comment) {
                        sql::run("INSERT INTO `tickets_comment_image` (`user_id`, `comment_id`, `image`) VALUES (?, ?, ?)", [
                            auth::get_id(),
                            $ticketID,
                            $filename . ".webp",
                        ]);
                    } else {
                        sql::run("INSERT INTO `tickets_image` (`user_id`, `image`, `ticket_id`) VALUES (?, ?, ?)", [
                            auth::get_id(),
                            $filename . ".webp",
                            $ticketID,
                        ]);
                    }
                }

                if ($handle->error) {
                    if($comment){
                        self::removeComment($ticketID);
                    }else{
                        self::remove($ticketID);
                    }
                    $fileName = $files['name'][$i];
                    $msg = lang::get_phrase(455) . " '" . $fileName . "'\n" . lang::get_phrase(456) . " : " . $handle->error;
                    board::notice(false,  $msg);
                }

            } else {
                if($comment){
                    self::removeComment($ticketID);
                }else{
                    self::remove($ticketID);
                }
                board::notice(false,  $handle->error);
            }
        }
    }

    public static function removeImage(): void {
        $comment_id = $_POST['commentID'];
        $image_id = $_POST['imageID'];
        $ok = sql::sql("DELETE FROM `tickets_comment_image` WHERE `id` = ? AND `comment_id` = ? AND `user_id` = ?", [
            $image_id,
            $comment_id,
            auth::get_id(),
        ]);
        if($ok) {
            board::notice(true, lang::get_phrase(146));
        }
    }

    public static function editComment(): void {
        $ticket_id = $_POST['ticketID'];
        $comment_id = $_POST['commentID'];
        $content = $_POST['content'];
        $files = $_FILES['files'] ?? null;

        $commentInfo = self::get_comment($ticket_id, $comment_id);
        if($commentInfo['user_id'] != auth::get_id()) {
            board::notice(false, lang::get_phrase(366));
        }

        if($files !== null) {
            $count = count($files['name']);
            $countPostImage = sql::getValue("SELECT count(1) AS `count` FROM tickets_comment_image WHERE comment_id = ?", [$comment_id]);
            if($countPostImage + $count > 3) {
                board::notice(false, lang::get_phrase(367));
            }
            self::processFiles($files, $comment_id, true);
        }

        $ok = sql::run("UPDATE `tickets_comment` SET `content` = ? WHERE `id` = ? AND `ticket_id` = ?", [
            $content,
            $comment_id,
            $ticket_id,
        ]);

        if($ok) {
            board::alert([
                "ok"   => true,
                "link" => "/ticket/{$ticket_id}/#msg{$comment_id}",
            ]);
        }
    }

    public static function editTicket() {
        $ticket_id = $_POST['ticketID'];
        $content = $_POST['content'];
        $files = $_FILES['files'] ?? null;

        $ticketInfo = self::get_info($ticket_id, false);
        if($ticketInfo['user_id'] != auth::get_id()) {
            board::notice(false, lang::get_phrase(366));
        }

        if($files !== null) {
            $count = count($files['name']);
            $countPostImage = sql::getValue("SELECT count(1) AS `count` FROM tickets_image WHERE ticket_id = ?", [$ticket_id]);
            if($countPostImage + $count > 3) {
                board::notice(false, lang::get_phrase(367));
            }
            self::processFiles($files, $ticket_id, false);
        }

        $ok = sql::run("UPDATE `tickets` SET `content` = ? WHERE `id` = ?", [
            $content,
            $ticket_id,
        ]);

        if($ok) {
            board::alert([
                "ok"   => true,
                "link" => "/ticket/{$ticket_id}",
            ]);
        }
    }

    //Удаление тикета
    //Если всё удалено - true
    public static function remove($ticket_id): bool {
        $ticket = self::get_ticket($ticket_id);
        if($ticket){
            sql::run("DELETE FROM `tickets` WHERE `id` = ?", [$ticket_id]);
            $comments = sql::getRows("SELECT `id` FROM `tickets_comment` WHERE ticket_id = ?", [$ticket_id]);
            if($comments){
                sql::run("DELETE FROM `tickets_comment` WHERE `ticket_id` = ?", [$ticket_id]);
                $images = sql::run("SELECT `image` FROM `tickets_comment_image`;");
                foreach ($images as $src) {
                    unlink("uploads/tickets/{$src['image']}");
                    unlink("uploads/tickets/thumb_{$src['image']}");
                }
                foreach ($comments AS $comment){
                    sql::run("DELETE FROM `tickets_comment_image` WHERE `comment_id` = ?", [$comment['id']]);
                }
                $images = sql::run("SELECT `image` FROM `tickets_image`;");
                foreach ($images as $src) {
                    unlink("uploads/tickets/{$src['image']}");
                    unlink("uploads/tickets/thumb_{$src['image']}");
                }
                sql::run("DELETE FROM `tickets_image` WHERE `ticket_id` = ?", [$ticket_id]);
            }
            return true;
        }
        return false;
    }

    public static function removeComment($commentID) : bool {
        if(sql::run("SELECT `ticket_id` FROM `tickets_comment` WHERE id=?", [$commentID])){
            if($images = sql::run("SELECT `image` FROM `tickets_comment_image` WHERE comment_id=?;", [$commentID])){
                foreach ($images as $src) {
                    unlink("uploads/tickets/{$src['image']}");
                    unlink("uploads/tickets/thumb_{$src['image']}");
                }
            }
            sql::run("DELETE FROM `tickets_comment_image` WHERE `comment_id` = ?", [$commentID]);
            sql::run("DELETE FROM `tickets_comment` WHERE `id` = ?", [$commentID]);
            return true;
        }
        return false;
    }

}