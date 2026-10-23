<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Database;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\Settings;
use Lvt\View;

final class PublicController extends Controller
{
    public function home(): Response
    {
        return $this->page('public/home', 'Valet trash in Valdosta', $this->defaultDescription(), [
            'documentTitle' => 'Valet Trash in Valdosta, GA | Loaded Valet Trash',
            'reviews' => $this->approvedReviews(),
            'holidays' => $this->upcomingHolidays(),
            'faqs' => array_slice($this->faqs(), 0, 5),
            'jsonLd' => $this->jsonLd(),
        ]);
    }

    public function services(): Response
    {
        return $this->page(
            'public/services',
            'Services',
            'Doorstep valet trash for apartment complexes, party and event cleanup quoted per event, and move-out cleanout quoted per job in Valdosta and Lowndes County, Georgia.'
        );
    }

    public function how(): Response
    {
        return $this->page('public/how', 'How valet trash works', 'Monday through Friday doorstep pickup for Valdosta apartment communities. Set-out window, bag rules, and what the leasing office does.', [
            'holidays' => $this->upcomingHolidays(),
        ]);
    }

    public function pricing(): Response
    {
        return $this->page('public/pricing', 'Pricing', 'Opt-in valet trash is $' . $this->priceWhole() . ' a month, billed by the leasing office. Community-wide service is quoted per property.', [
            'faqs' => $this->faqs(),
        ]);
    }

    public function who(): Response
    {
        return $this->page('public/who', 'Who we serve', 'Valet trash for apartments, property management companies, HOAs, student housing, senior living, and short-term rentals in Valdosta and Lowndes County.');
    }

    public function reviews(): Response
    {
        return $this->page('public/reviews', 'Reviews', 'Reviews from property managers and community staff. Loaded Valet Trash publishes a review only after it is approved.', [
            'reviews' => $this->approvedReviews(),
        ]);
    }

    public function reviewForm(): Response
    {
        return $this->page('public/review-form', 'Share a review', 'Submit a firsthand review of Loaded Valet Trash. It is published only after approval.');
    }

    public function reviewSubmit(): Response
    {
        $this->csrf();
        if ($trap = $this->honeypot('/reviews')) {
            return $trap;
        }
        $req = Request::current();
        $errors = [];
        $name = $req->string('reviewer_name');
        $property = $req->string('property_name');
        $role = $req->string('role_title');
        $rating = $req->int('rating', 0);
        $body = $req->string('body');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['reviewer_name'] = 'Enter your name.';
        }
        if (mb_strlen($property) < 2 || mb_strlen($property) > 180) {
            $errors['property_name'] = 'Enter the property name.';
        }
        if (mb_strlen($role) < 2 || mb_strlen($role) > 80) {
            $errors['role_title'] = 'Enter your role, such as community manager.';
        }
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Choose a rating from 1 to 5.';
        }
        if (mb_strlen($body) < 20 || mb_strlen($body) > 2000) {
            $errors['body'] = 'Write at least a sentence (20 to 2,000 characters).';
        }
        if ($errors) {
            return $this->invalid('public/review-form', $errors, [
                'title' => 'Share a review',
                'description' => 'Submit a firsthand review.',
            ]);
        }
        $id = Database::insertRow('reviews', [
            'reviewer_name' => $name,
            'property_name' => $property,
            'role_title' => $role,
            'rating' => $rating,
            'body' => $body,
            'status' => 'pending',
            'created_at' => now_dt(),
            'moderated_by' => null,
            'moderated_at' => null,
        ]);
        Audit::write(null, 'review.submit', 'review', $id, 'Review submitted for moderation');
        Session::flash('success', 'Thank you. Your review is in the approval queue and will not appear on the site until it is approved.');
        return Response::redirect('/reviews');
    }

    public function faq(): Response
    {
        return $this->page('public/faq', 'FAQ', 'Answers for property managers about valet trash billing, bag rules, missed pickups, and the free 30-day trial in Valdosta.', [
            'faqs' => $this->faqs(),
        ]);
    }

    public function about(): Response
    {
        return $this->page('public/about', 'About', 'Loaded Valet Trash is owned by Anthony Jaamal Jones (Dondon) and serves apartment communities in Valdosta and Lowndes County, Georgia.');
    }

    public function contact(): Response
    {
        return $this->page('public/contact', 'Request a quote', 'Ask Loaded Valet Trash for a proposal, a walkthrough, party and event cleanup, or a move-out cleanout.');
    }

    public function contactSubmit(): Response
    {
        $this->csrf();
        if ($trap = $this->honeypot('/contact')) {
            return $trap;
        }
        $req = Request::current();
        $errors = [];
        $property = $req->string('property_name');
        $units = $req->int('units', 0);
        $contact = $req->string('contact_name');
        $email = $req->string('contact_email');
        $phone = $req->string('contact_phone');
        $message = $req->string('message');
        $kind = $req->string('kind', 'proposal');
        if (!array_key_exists($kind, inquiry_kinds())) {
            $kind = 'proposal';
        }
        if (mb_strlen($property) < 2 || mb_strlen($property) > 180) {
            $errors['property_name'] = 'Enter the property name.';
        }
        if ($units < 1 || $units > 5000) {
            $errors['units'] = 'Enter the number of units.';
        }
        if (mb_strlen($contact) < 2 || mb_strlen($contact) > 120) {
            $errors['contact_name'] = 'Enter a contact name.';
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['contact_email'] = 'Enter a valid email, or leave it blank and add a phone number.';
        }
        if ($email === '' && $phone === '') {
            $errors['contact_phone'] = 'Add an email or a phone number so we can reply.';
        }
        if (mb_strlen($phone) > 40) {
            $errors['contact_phone'] = 'That phone number is too long.';
        }
        if (mb_strlen($message) > 4000) {
            $errors['message'] = 'Keep the message under 4,000 characters.';
        }
        if ($errors) {
            return $this->invalid('public/contact', $errors, [
                'title' => 'Request a proposal',
                'description' => 'Ask for a walkthrough or a proposal.',
            ]);
        }
        $existing = Database::one('SELECT id FROM properties WHERE LOWER(name) = LOWER(?)', [$property]);
        if ($existing) {
            $propertyId = (int) $existing['id'];
            Database::insertRow('property_notes', [
                'property_id' => $propertyId,
                'user_id' => null,
                'body' => 'Website ' . inquiry_kind_label($kind) . ' request from ' . $contact . ($message !== '' ? ': ' . $message : '.'),
                'created_at' => now_dt(),
            ]);
        } else {
            $propertyId = Database::insertRow('properties', $this->newLead($property, $units));
            Database::insertRow('property_contacts', [
                'property_id' => $propertyId,
                'name' => $contact,
                'role_title' => 'Website inquiry',
                'phone' => blank_to_null($phone),
                'email' => blank_to_null($email),
                'is_primary' => 1,
                'created_at' => now_dt(),
            ]);
            if ($message !== '') {
                Database::insertRow('property_notes', [
                    'property_id' => $propertyId,
                    'user_id' => null,
                    'body' => $message,
                    'created_at' => now_dt(),
                ]);
            }
        }
        $inquiryId = Database::insertRow('inquiries', [
            'kind' => $kind,
            'property_name' => $property,
            'units_count' => $units,
            'contact_name' => $contact,
            'contact_email' => blank_to_null($email),
            'contact_phone' => blank_to_null($phone),
            'message' => blank_to_null($message),
            'status' => 'new',
            'property_id' => $propertyId,
            'created_at' => now_dt(),
        ]);
        Audit::write(null, 'inquiry.create', 'inquiry', $inquiryId, 'Public ' . $kind . ' form');
        Session::flash('success', 'Thank you. We have your request and will follow up using the contact details you sent.');
        return Response::redirect('/contact');
    }

    public function missed(): Response
    {
        return $this->page('public/missed', 'Report a missed pickup', 'Residents and leasing offices can report a missed valet trash pickup. If the bag was out by the end of the set-out window, we schedule a return the same day.');
    }

    public function missedSubmit(): Response
    {
        $this->csrf();
        if ($trap = $this->honeypot('/missed-pickup')) {
            return $trap;
        }
        $req = Request::current();
        $errors = [];
        $property = $req->string('property_name');
        $unit = $req->string('unit_label');
        $name = $req->string('reporter_name');
        $contact = $req->string('reporter_contact');
        $description = $req->string('description');
        $when = $req->string('happened_on');
        if (mb_strlen($property) < 2) {
            $errors['property_name'] = 'Enter the property name.';
        }
        if ($unit === '' || mb_strlen($unit) > 40) {
            $errors['unit_label'] = 'Enter the unit number.';
        }
        if (mb_strlen($name) < 2) {
            $errors['reporter_name'] = 'Enter your name.';
        }
        if ($contact === '' || mb_strlen($contact) > 190) {
            $errors['reporter_contact'] = 'Enter a phone number or email.';
        }
        if (mb_strlen($description) < 10) {
            $errors['description'] = 'Tell us what was set out and when.';
        }
        if ($when !== '' && !valid_date($when)) {
            $errors['happened_on'] = 'Use a real date.';
        }
        if ($errors) {
            return $this->invalid('public/missed', $errors, [
                'title' => 'Report a missed pickup',
                'description' => 'Report a missed valet trash pickup.',
            ]);
        }
        $match = Database::one('SELECT id FROM properties WHERE LOWER(name) = LOWER(?)', [$property]);
        $note = $description;
        if ($when !== '') {
            $note = 'Service night ' . $when . '. ' . $note;
        }
        $id = Database::insertRow('issues', [
            'property_id' => $match ? (int) $match['id'] : null,
            'resident_id' => null,
            'route_stop_id' => null,
            'unit_label' => $unit,
            'external_property_name' => $property,
            'type' => 'missed_pickup',
            'violation_kind' => null,
            'source' => 'public',
            'status' => 'open',
            'reporter_name' => $name,
            'reporter_contact' => $contact,
            'reporter_role' => $req->string('reporter_role') !== '' ? $req->string('reporter_role') : 'Resident or office',
            'description' => $note,
            'photo_path' => null,
            'created_by' => null,
            'created_at' => now_dt(),
            'resolved_at' => null,
        ]);
        Audit::write(null, 'issue.public_miss', 'issue', $id, 'Missed pickup reported from the website');
        Session::flash('success', 'We logged the missed pickup. If the bag was out on time, the return is the same day.');
        return Response::redirect('/missed-pickup');
    }

    public function privacy(): Response
    {
        return $this->page('public/privacy', 'Privacy policy', 'How Loaded Valet Trash handles information collected on this website and while running doorstep pickup.');
    }

    public function terms(): Response
    {
        return $this->page('public/terms', 'Terms', 'Service terms for Loaded Valet Trash doorstep pickup in Valdosta and Lowndes County.');
    }

    public function sitemap(): Response
    {
        $paths = ['/', '/services', '/how-it-works', '/pricing', '/who-we-serve', '/reviews', '/reviews/new', '/faq', '/about', '/contact', '/missed-pickup', '/privacy', '/terms'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($paths as $path) {
            $xml .= '<url><loc>' . e(base_url($path === '/' ? '/' : ltrim($path, '/'))) . '</loc></url>';
        }
        $xml .= '</urlset>';
        return Response::text($xml, 200, 'application/xml; charset=utf-8');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /login\nDisallow: /account\nSitemap: " . base_url('sitemap.xml') . "\n";
        return Response::text($body);
    }

    public function manifest(): Response
    {
        $json = json_encode([
            'name' => 'Loaded Valet Trash',
            'short_name' => 'Loaded',
            'start_url' => '/admin/tonight',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'icons' => [
                ['src' => '/assets/brand/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/assets/brand/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], JSON_UNESCAPED_SLASHES);
        return Response::text((string) $json, 200, 'application/manifest+json');
    }

    /** @return list<array<string,mixed>> */
    private function approvedReviews(): array
    {
        return Database::all('SELECT reviewer_name, property_name, role_title, rating, body, created_at FROM reviews WHERE status = "approved" ORDER BY created_at DESC LIMIT 24');
    }

    /** @return list<array<string,mixed>> */
    private function upcomingHolidays(): array
    {
        return Database::all('SELECT name, observed_on, service_off, instructions FROM holidays WHERE observed_on >= ? ORDER BY observed_on ASC', [today_date()]);
    }

    /** @return list<array{q:string,a:string}> */
    private function faqs(): array
    {
        $s = Settings::get();
        $price = money_plain((int) $s['opt_in_price_cents']);
        $bags = (int) $s['max_bags_per_night'];
        $holidayBags = (int) $s['holiday_bags'];
        $weight = (int) $s['max_bag_weight_lb'];
        $setout = clock_range((string) $s['setout_start'], (string) $s['setout_end']);
        $pickup = clock_range((string) $s['pickup_start'], (string) $s['pickup_end']);
        $report = (string) $s['missed_report_by'];
        $share = rtrim(rtrim(number_format((float) $s['default_property_share_percent'], 2), '0'), '.');
        $items = [
            [
                'q' => 'What does the property pay?',
                'a' => 'Under the opt-in model, there is no separate vendor bill. Residents who want the service pay ' . $price . ' a month through rent. If we agree on a property share, the office keeps that percentage and remits the rest. The share we use as a starting point is ' . $share . '%. Community-wide service is a separate quote.',
            ],
            [
                'q' => 'How does billing work?',
                'a' => 'The resident signs a one-page opt-in addendum. The office adds ' . $price . ' to the ledger, the same way it bills pet rent or a parking fee. On the 1st we send the active roster. The property remits by the 10th. A resident can cancel with 30 days’ written notice to the office, ending at the next month-end. New residents can opt in any time. Billing is not prorated for the holiday nights built into the price.',
            ],
            [
                'q' => 'What do residents do?',
                'a' => 'Monday through Friday, tie the trash in a bag and put it inside your can. Put your trash can outside your door with the bagged trash inside it during ' . $setout . '. Never put a loose bag on the ground. A second bag goes on top of the can, never on the ground or beside it. On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it. Not on stairs or walkways. Porters collect from ' . $pickup . ' and take the trash to the property compactor or dumpster. Cans left out before the window, or still out after pickup, go back inside. Loose bags scatter garbage.',
            ],
            [
                'q' => 'What are the bag rules?',
                'a' => 'Tie the trash in a bag and put it inside your can. Tied, leak-proof bags, kitchen size (13 gallon) up to 30 gallon, ' . $weight . ' pounds max, up to ' . $bags . ' bags a night (' . $holidayBags . ' the night after a holiday). On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it. Put your trash can outside your door with the bagged trash inside it. Never put a loose bag on the ground. Set the can right outside your door during the set-out window, not on stairs or walkways. A second bag goes on top of the can, never on the ground or beside it. Loose bags scatter garbage. No loose trash, no open boxes, no leaking bags. Flatten and tie cardboard into a small bundle of up to 2 boxes, or take it to the compactor. We do not take furniture, mattresses, electronics, paint, chemicals, batteries, needles or medical waste, construction debris, hot ashes, or unbagged pet waste.',
            ],
            [
                'q' => 'Do you clean up after events or move-outs?',
                'a' => 'Yes. Party and event cleanup is for residents, properties and clubhouses, and private events. Pricing is quoted per event. Move-out cleanout and trash haul-off is for leasing offices, apartment complexes, and homes. Pricing is quoted per job. Use the contact form and choose the job.',
            ],
            [
                'q' => 'What if a pickup is missed?',
                'a' => 'If the can was out by the end of the set-out window, with the bagged trash inside it, and it was not collected, tell the leasing office by ' . $report . ' the next day (or use the missed-pickup form on this site). If it was our miss, we come back the same day. If the can went out late, it waits for the next service night.',
            ],
            [
                'q' => 'Will trash at the door cause pests or odor?',
                'a' => 'The can is outside only during the set-out window and is collected the same night, so trash is not left overnight on a normal route. Tie the trash in a bag and put it inside the can. A second bag goes on top of the can, never on the ground or beside it. On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it. Never put a loose bag on the ground. Loose bags scatter garbage. A rule break gets a door notice and a photo for the office. Porters carry absorbent and a broom for drips they cause.',
            ],
            [
                'q' => 'How much work is this for my staff?',
                'a' => 'The office adds the rent line and sends one monthly remittance from the roster we provide. We handle questions about the pickup itself. Lease enforcement, including any fee your community already uses for rule breaks, stays with the property.',
            ],
            [
                'q' => 'Can we try it first?',
                'a' => 'Yes. The offer includes a free 30-day trial on the buildings you choose. We provide flyers and a simple opt-in addendum. At day 30 you continue, expand, or stop.',
            ],
            [
                'q' => 'Which nights do you run?',
                'a' => 'Monday through Friday, including Friday night, so the can can go out before the weekend. We do not run Saturday or Sunday. Listed holidays are off, with notice ahead of time, and the next service night allows up to ' . $holidayBags . ' bags. Severe storms, lightning, or a tropical-storm warning can delay a route up to two hours or cancel it. Residents are told by 7 PM, and the next night is a double-bag night. On those extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.',
            ],
        ];
        $items[] = [
            'q' => 'Do porters go inside apartments?',
            'a' => 'No. Porters pick up the can outside the door, with the bagged trash inside it, and a second bag only if it is on top of the can. They take that trash to the property compactor or dumpster. A certificate is provided before service starts. This website does not state a policy limit and does not name a property as an additional insured.',
        ];
        if (filled($s['insurance_text'] ?? null)) {
            $items[] = [
                'q' => 'Where is the certificate described?',
                'a' => 'A certificate is provided before service starts. ' . trim((string) $s['insurance_text']),
            ];
        }
        $area = trim((string) ($s['service_area'] ?? ''));
        if ($area !== '') {
            $items[] = [
                'q' => 'Where do you serve?',
                'a' => 'We are set up for ' . $area . '. If your community is nearby and not on that list, ask anyway and we will tell you honestly whether we can reach it on a weeknight route.',
            ];
        }
        return $items;
    }

    private function page(string $template, string $title, string $description, array $extra = []): Response
    {
        return View::page($template, array_merge([
            'title' => $title,
            'description' => $description,
        ], $extra));
    }

    private function defaultDescription(): string
    {
        $price = $this->priceWhole();
        return 'Doorstep valet trash for apartment communities in Valdosta and Lowndes County. Residents opt in for ' . $price . ' a month. Monday to Friday evening pickup. Request a proposal.';
    }

    private function priceWhole(): string
    {
        return money_plain((int) Settings::get()['opt_in_price_cents']);
    }

    /** @return array<string,mixed> */
    private function newLead(string $name, int $units): array
    {
        $now = now_dt();
        return [
            'management_company_id' => null,
            'name' => $name,
            'address' => null,
            'city' => null,
            'state' => 'GA',
            'postal_code' => null,
            'units_count' => $units,
            'planned_doors' => null,
            'website' => null,
            'has_valet_trash' => 'unknown',
            'stage' => 'lead',
            'lost_reason' => null,
            'pricing_model' => 'opt_in',
            'opt_in_price_cents' => null,
            'property_share_percent' => null,
            'per_door_rate_cents' => null,
            'flat_rate_cents' => null,
            'service_days' => 'mon,tue,wed,thu,fri',
            'setout_start' => null,
            'setout_end' => null,
            'pickup_start' => null,
            'pickup_end' => null,
            'minutes_per_door' => null,
            'minutes_fixed' => null,
            'compactor_location' => null,
            'gate_access_notes' => null,
            'contract_start' => null,
            'contract_end' => null,
            'trial_start' => null,
            'trial_end' => null,
            'follow_up_on' => null,
            'supplies_cents' => null,
            'gas_cents' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @return array<string,mixed> */
    private function jsonLd(): array
    {
        $s = Settings::get();
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => (string) $s['business_name'],
            'url' => base_url('/'),
            'image' => base_url('assets/brand/og-share.jpg'),
            'description' => $this->defaultDescription(),
            'founder' => [
                '@type' => 'Person',
                'name' => 'Anthony Jaamal Jones',
            ],
            'areaServed' => ['Valdosta', 'Lowndes County', 'Hahira', 'Remerton'],
        ];
        if (filled($s['phone'] ?? null)) {
            $data['telephone'] = (string) $s['phone'];
        }
        if (filled($s['email'] ?? null)) {
            $data['email'] = (string) $s['email'];
        }
        if (filled($s['address'] ?? null)) {
            $data['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => (string) $s['address'],
                'addressRegion' => 'GA',
                'addressCountry' => 'US',
            ];
        }
        return $data;
    }
}
