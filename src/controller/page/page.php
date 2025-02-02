<?php

namespace Ofey\Logan22\controller\page;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\page\page as page_model;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class page {

    /**
     * Добавление комментария
     * Комментарий ограничен БД до 3 тыс. символов
     * Комментарий добавляется только в разрешенные страницы
     */
    public static function addComment() {
        if(!auth::get_is_auth()) {
            board::notice(false, "Only auth user");
        }
        if(auth::get_ban_page()){
            board::notice(false, "You are not allowed to do this");
        }
        $page_id = $_POST['id'];
        $comment = htmlentities($_POST['comment']);
        $page = page_model::get_news($page_id);
        if(!$page)
            board::notice(false, lang::get_phrase(174));
        if (auth::get_access_level() !== "admin" && !$page['comment']) {
                board::notice(false, lang::get_phrase(240));
        }
        if(1 > mb_strlen($comment))
            board::notice(false, lang::get_phrase(241));
        if(3000 < mb_strlen($comment))
            board::notice(false, lang::get_phrase(242, mb_strlen($comment)));
        page_model::add_comment($page_id, $comment);
        board::notice(true, lang::get_phrase(243));
    }

    public static function show($id) {
        if($page = page_model::get_news($id)) {
            tpl::addVar([
                'page'        => $page,
                'comments'    => page_model::get_comments($id),
                'server_list' => server::get_server_info(),
            ]);
            tpl::display("page.html");
        }
        redirect::location("/main");
    }

    public static function get_news_ajax() {
        $id = $_POST['news_id'];
        $content = page_model::get_news($id);
        if($content == false) {
            board::notice(false, "Not news");
        }
        board::alert(array_merge($content, ['ok' => true]));
    }
}