<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:10:17
 */

namespace Ofey\Logan22\controller\user\auth;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\user\auth\forget;
use Ofey\Logan22\template\tpl;

class auth {

    public static function index() {
        validation::user_protection("guest");
        tpl::display("user/auth/auth.html");
    }

    public static function auth_request() {
        validation::user_protection("guest");
        \Ofey\Logan22\model\user\auth\auth::user_enter();
    }

    public static function logout() {
        if(\Ofey\Logan22\model\user\auth\auth::get_is_auth()) {
            \Ofey\Logan22\model\user\auth\auth::logout();
        }
        header('Location: /main');
        die();
    }

    /**
     * Восстановление пароля
     */
    public static function forget() {
        validation::user_protection("guest");
        tpl::addVar("title", lang::get_phrase(285));
        tpl::display("user/auth/forget.html");
    }

    public static function send_email_forget() {
        validation::user_protection("guest");
        forget::password();
    }

    //Когда пользователь открывает страницу (с почты) восстановления
    public static function open_forget_page($code): void {
        validation::user_protection("guest");
        $data = forget::dataForgetInfo($code);
        tpl::addVar([
            'title' => lang::get_phrase(67),
            'code' => $code,
            'email' => $data['email'],
        ]);
        tpl::display("user/auth/forget_link.html");
     }

    /**
     * Отправить пароль на почту
     * Используется при сбросе пароля
     * @return void
     */
     public static function send_password(): void {
         validation::user_protection("guest");
         forget::reset_password();
     }

    /**
     * Для страницы на которой пользователь вводит вручную код
     *
     * @return void
     */
    public static function send_email_verification_forget() {
        validation::user_protection("guest");
        forget::verification();
    }
}