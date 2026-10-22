<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Auth;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\Totp;
use Lvt\View;

final class AuthController extends Controller
{
    public function loginForm(): Response
    {
        if (Auth::user()) {
            return Response::redirect($this->homeFor(Auth::user()));
        }
        if ((int) Session::get('pending_uid', 0) > 0) {
            return Response::redirect('/login/2fa');
        }
        return View::page('auth/login', [
            'title' => 'Sign in',
            'description' => 'Staff sign-in for Loaded Valet Trash.',
            'next' => safe_next(Request::current()->string('next'), '/admin'),
        ], 'layouts/auth');
    }

    public function login(): Response
    {
        $this->csrf();
        $req = Request::current();
        $email = strtolower($req->string('email'));
        $password = (string) ($req->post['password'] ?? '');
        $next = safe_next($req->string('next'), '/admin');
        if ($email === '' || $password === '') {
            return $this->invalid('auth/login', [
                'email' => 'Enter the email and password for your staff account.',
            ], [
                'title' => 'Sign in',
                'description' => 'Staff sign-in.',
                'next' => $next,
            ], 'layouts/auth');
        }
        $result = Auth::attempt($email, $password, $req->ip());
        if ($result === 'locked') {
            return View::page('auth/login', [
                'title' => 'Sign in',
                'description' => 'Staff sign-in.',
                'next' => $next,
                'formError' => 'Too many attempts. Wait about 15 minutes and try again.',
                'status' => 429,
            ], 'layouts/auth');
        }
        if ($result === 'invalid' || $result === 'inactive') {
            return View::page('auth/login', [
                'title' => 'Sign in',
                'description' => 'Staff sign-in.',
                'next' => $next,
                'formError' => 'Those details do not match an active staff account.',
                'status' => 422,
            ], 'layouts/auth');
        }
        if ($result === '2fa') {
            Session::set('after_login', $next);
            return Response::redirect('/login/2fa');
        }
        Session::set('after_login', $next);
        return Response::redirect('/account/security');
    }

    public function twoFactorForm(): Response
    {
        if (Auth::user()) {
            return Response::redirect($this->homeFor(Auth::user()));
        }
        if ((int) Session::get('pending_uid', 0) <= 0) {
            return Response::redirect('/login');
        }
        return View::page('auth/2fa', [
            'title' => 'Authenticator code',
            'description' => 'Enter the 6-digit code from your authenticator app.',
        ], 'layouts/auth');
    }

    public function twoFactor(): Response
    {
        $this->csrf();
        $code = Request::current()->string('code');
        $result = Auth::confirmTotp($code, Request::current()->ip());
        if ($result === 'expired') {
            Session::flash('error', 'That sign-in expired. Start again.');
            return Response::redirect('/login');
        }
        if ($result === 'locked') {
            return View::page('auth/2fa', [
                'title' => 'Authenticator code',
                'description' => 'Enter the code from your authenticator app.',
                'formError' => 'Too many attempts. Wait about 15 minutes and try again.',
                'status' => 429,
            ], 'layouts/auth');
        }
        if ($result !== 'ok') {
            return View::page('auth/2fa', [
                'title' => 'Authenticator code',
                'description' => 'Enter the code from your authenticator app.',
                'formError' => 'That code does not match. You can also use an unused recovery code.',
                'status' => 422,
            ], 'layouts/auth');
        }
        $next = safe_next((string) Session::get('after_login', '/admin'), $this->homeFor(Auth::user() ?? ['role' => 'porter']));
        Session::forget('after_login');
        return Response::redirect($next);
    }

    public function logout(): Response
    {
        $this->csrf();
        Auth::logout();
        Session::flash('success', 'You are signed out.');
        return Response::redirect('/login');
    }

    public function securityForm(): Response
    {
        $user = Auth::user();
        $enrolling = Auth::enrolling();
        $subject = $user ?? $enrolling;
        if (!$subject) {
            return Response::redirect('/login');
        }
        $secret = null;
        $uri = null;
        if ((int) ($subject['totp_enabled'] ?? 0) !== 1) {
            $secret = Auth::beginEnrollment((int) $subject['id']);
            $uri = Totp::provisioningUri($secret, (string) $subject['email']);
        }
        $codes = Session::get('fresh_recovery_codes');
        Session::forget('fresh_recovery_codes');
        return View::page('auth/security', [
            'title' => 'Authenticator',
            'description' => 'Set up the authenticator app for your staff account.',
            'subject' => $subject,
            'secret' => $secret,
            'otpauth' => $uri,
            'recoveryCodes' => is_array($codes) ? $codes : [],
        ], 'layouts/auth');
    }

    public function securityEnable(): Response
    {
        $this->csrf();
        $user = Auth::user();
        $enrolling = Auth::enrolling();
        $subject = $user ?? $enrolling;
        if (!$subject) {
            return Response::redirect('/login');
        }
        if ((int) ($subject['totp_enabled'] ?? 0) === 1) {
            Session::flash('success', 'Two-factor authentication is already on for this account.');
            return Response::redirect($this->homeFor($subject));
        }
        try {
            $codes = Auth::enableTotp((int) $subject['id'], Request::current()->string('code'));
        } catch (HttpException $e) {
            if ($e->status !== 422) {
                throw $e;
            }
            $secret = Auth::beginEnrollment((int) $subject['id']);
            return View::page('auth/security', [
                'title' => 'Authenticator',
                'description' => 'Set up the authenticator app.',
                'subject' => $subject,
                'secret' => $secret,
                'otpauth' => Totp::provisioningUri($secret, (string) $subject['email']),
                'recoveryCodes' => [],
                'formError' => $e->getMessage(),
                'status' => 422,
            ], 'layouts/auth');
        }
        Session::set('fresh_recovery_codes', $codes);
        Session::flash('success', 'Authenticator is on. Save these recovery codes now. They are shown once.');
        return Response::redirect('/account/security');
    }

    private function homeFor(array $user): string
    {
        return ($user['role'] ?? '') === 'porter' ? '/admin/tonight' : '/admin';
    }
}
