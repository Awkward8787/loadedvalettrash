<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Csrf;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\View;

abstract class Controller
{
    protected function csrf(): void
    {
        if (!Csrf::verify(Request::current()->string('_csrf'))) {
            throw new HttpException(419, 'That form expired. Refresh the page and submit it again.');
        }
    }

    /** @param array<string,string> $errors */
    protected function invalid(string $template, array $errors, array $data = [], string $layout = 'layouts/public'): Response
    {
        $GLOBALS['_errors'] = $errors;
        $post = Request::current()->post;
        unset($post['_csrf'], $post['password'], $post['password_confirm']);
        $GLOBALS['_old'] = $post;
        $data['status'] = 422;
        return View::page($template, $data, $layout);
    }

    protected function honeypot(string $redirect): ?Response
    {
        if (Request::current()->string('company_website') !== '') {
            Session::flash('success', 'Thanks. We have it.');
            return Response::redirect($redirect);
        }
        return null;
    }
}
