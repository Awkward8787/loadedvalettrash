<?php

declare(strict_types=1);

use Lvt\App;
use Lvt\Http\Controllers\AuthController;
use Lvt\Http\Controllers\BillingController;
use Lvt\Http\Controllers\DashboardController;
use Lvt\Http\Controllers\IssueController;
use Lvt\Http\Controllers\ModerationController;
use Lvt\Http\Controllers\PeopleController;
use Lvt\Http\Controllers\PipelineController;
use Lvt\Http\Controllers\PorterController;
use Lvt\Http\Controllers\PropertyController;
use Lvt\Http\Controllers\PublicController;
use Lvt\Http\Controllers\ScheduleController;
use Lvt\Http\Controllers\SettingsController;
use Lvt\Router;

$router = new Router();

$router->get('/', [PublicController::class, 'home']);
$router->get('/how-it-works', [PublicController::class, 'how']);
$router->get('/pricing', [PublicController::class, 'pricing']);
$router->get('/who-we-serve', [PublicController::class, 'who']);
$router->get('/reviews', [PublicController::class, 'reviews']);
$router->get('/reviews/new', [PublicController::class, 'reviewForm']);
$router->post('/reviews', [PublicController::class, 'reviewSubmit']);
$router->get('/faq', [PublicController::class, 'faq']);
$router->get('/about', [PublicController::class, 'about']);
$router->get('/contact', [PublicController::class, 'contact']);
$router->post('/contact', [PublicController::class, 'contactSubmit']);
$router->get('/missed-pickup', [PublicController::class, 'missed']);
$router->post('/missed-pickup', [PublicController::class, 'missedSubmit']);
$router->get('/privacy', [PublicController::class, 'privacy']);
$router->get('/terms', [PublicController::class, 'terms']);
$router->get('/sitemap.xml', [PublicController::class, 'sitemap']);
$router->get('/robots.txt', [PublicController::class, 'robots']);
$router->get('/manifest.webmanifest', [PublicController::class, 'manifest']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/login/2fa', [AuthController::class, 'twoFactorForm']);
$router->post('/login/2fa', [AuthController::class, 'twoFactor']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/account/security', [AuthController::class, 'securityForm'], ['auth']);
$router->post('/account/security', [AuthController::class, 'securityEnable'], ['auth']);

$ops = ['auth', 'role:owner,manager'];
$owner = ['auth', 'role:owner'];

$router->get('/admin', [DashboardController::class, 'index'], ['auth']);
$router->get('/admin/pipeline', [PipelineController::class, 'index'], $ops);
$router->get('/admin/pipeline/new', [PipelineController::class, 'create'], $ops);
$router->post('/admin/pipeline', [PipelineController::class, 'store'], $ops);
$router->post('/admin/pipeline/import', [PipelineController::class, 'import'], $ops);
$router->get('/admin/pipeline/template.csv', [PipelineController::class, 'template'], $ops);
$router->post('/admin/companies', [PipelineController::class, 'company'], $ops);

$router->get('/admin/properties', [PropertyController::class, 'index'], $ops);
$router->get('/admin/properties/{id}', [PropertyController::class, 'show'], $ops);
$router->post('/admin/properties/{id}', [PropertyController::class, 'update'], $ops);
$router->post('/admin/properties/{id}/notes', [PropertyController::class, 'note'], $ops);
$router->post('/admin/properties/{id}/contacts', [PropertyController::class, 'contact'], $ops);
$router->post('/admin/properties/{id}/buildings', [PropertyController::class, 'building'], $ops);
$router->get('/admin/properties/{id}/residents', [PropertyController::class, 'residents'], $ops);
$router->post('/admin/properties/{id}/residents/import', [PropertyController::class, 'residentImport'], $ops);
$router->post('/admin/properties/{id}/residents', [PropertyController::class, 'residentStore'], $ops);
$router->post('/admin/properties/{id}/residents/{rid}', [PropertyController::class, 'residentUpdate'], $ops);
$router->get('/admin/properties/{id}/residents.csv', [PropertyController::class, 'residentExport'], $ops);
$router->get('/admin/residents-template.csv', [PropertyController::class, 'residentTemplate'], $ops);

$router->get('/admin/employees', [PeopleController::class, 'index'], $ops);
$router->post('/admin/employees', [PeopleController::class, 'store'], $ops);
$router->get('/admin/employees/{id}', [PeopleController::class, 'show'], $ops);
$router->post('/admin/employees/{id}', [PeopleController::class, 'update'], $ops);

$router->get('/admin/schedule', [ScheduleController::class, 'index'], $ops);
$router->post('/admin/schedule/runs', [ScheduleController::class, 'storeRun'], $ops);
$router->post('/admin/schedule/templates', [ScheduleController::class, 'storeTemplate'], $ops);
$router->post('/admin/schedule/generate', [ScheduleController::class, 'generate'], $ops);
$router->get('/admin/schedule/calendar', [ScheduleController::class, 'calendar'], $ops);
$router->get('/admin/schedule/{id}', [ScheduleController::class, 'show'], $ops);
$router->post('/admin/schedule/{id}', [ScheduleController::class, 'update'], $ops);

$router->get('/admin/tonight', [PorterController::class, 'tonight'], ['auth']);
$router->post('/admin/tonight/clock', [PorterController::class, 'clock'], ['auth']);
$router->post('/admin/tonight/stops/{id}', [PorterController::class, 'stop'], ['auth']);
$router->post('/admin/tonight/doors/{id}', [PorterController::class, 'door'], ['auth']);

$router->get('/admin/issues', [IssueController::class, 'index'], $ops);
$router->get('/admin/issues/report', [IssueController::class, 'report'], $ops);
$router->get('/admin/issues/report.pdf', [IssueController::class, 'pdf'], $ops);
$router->get('/admin/issues/{id}', [IssueController::class, 'show'], $ops);
$router->post('/admin/issues/{id}', [IssueController::class, 'update'], $ops);
$router->get('/admin/issues/{id}/photo', [IssueController::class, 'photo'], $ops);

$router->get('/admin/billing', [BillingController::class, 'index'], $ops);
$router->post('/admin/billing/generate', [BillingController::class, 'generate'], $ops);
$router->get('/admin/billing/{id}', [BillingController::class, 'show'], $ops);
$router->post('/admin/billing/{id}', [BillingController::class, 'update'], $ops);

$router->get('/admin/reviews', [ModerationController::class, 'reviews'], $ops);
$router->post('/admin/reviews/{id}', [ModerationController::class, 'review'], $ops);
$router->get('/admin/inquiries', [ModerationController::class, 'inquiries'], $ops);
$router->post('/admin/inquiries/{id}', [ModerationController::class, 'inquiry'], $ops);

$router->get('/admin/settings', [SettingsController::class, 'edit'], $owner);
$router->post('/admin/settings', [SettingsController::class, 'update'], $owner);
$router->post('/admin/holidays', [SettingsController::class, 'holiday'], $owner);
$router->post('/admin/holidays/{id}/delete', [SettingsController::class, 'holidayDelete'], $owner);
$router->get('/admin/audit', [SettingsController::class, 'audit'], $ops);

return new App($router);
