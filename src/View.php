<?php

declare(strict_types=1);

namespace Lvt;

final class View
{
    public static function page(string $template, array $data = [], string $layout = 'layouts/public'): Response
    {
        $status = (int) ($data['status'] ?? 200);
        unset($data['status']);
        $inner = self::render($template, $data);
        $html = self::render($layout, array_merge($data, ['content' => $inner]));
        return Response::html($html, $status);
    }

    public static function render(string $template, array $data = []): string
    {
        $shared = [
            'settings' => self::settingsOrEmpty(),
            'authUser' => null,
            'csrf' => '',
            'path' => '/',
        ];
        try {
            $shared['authUser'] = Auth::user();
            $shared['csrf'] = Csrf::token();
            $shared['path'] = Request::current()->path;
        } catch (\Throwable) {
            $shared['authUser'] = $shared['authUser'] ?? null;
        }
        $data = array_merge($shared, $data);
        $file = LVT_ROOT . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Missing template ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private static function settingsOrEmpty(): array
    {
        try {
            return Settings::get();
        } catch (\Throwable) {
            return [
                'business_name' => 'Loaded Valet Trash',
                'owner_name' => 'Anthony Jaamal Jones',
                'phone' => null,
                'email' => null,
                'address' => null,
                'service_area' => null,
                'insurance_text' => null,
                'opt_in_price_cents' => 3500,
                'default_property_share_percent' => '10.00',
                'max_bags_per_night' => 2,
                'holiday_bags' => 4,
                'max_bag_weight_lb' => 25,
                'setout_start' => '18:00:00',
                'setout_end' => '20:00:00',
                'pickup_start' => '20:00:00',
                'pickup_end' => '22:00:00',
                'missed_report_by' => '10:00 AM',
            ];
        }
    }
}
