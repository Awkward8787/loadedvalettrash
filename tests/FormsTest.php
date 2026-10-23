<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;

final class FormsTest extends TestCase
{
    public function testProposalCreatesALeadAndAnInquiry(): void
    {
        $client = $this->client();
        $client->ip = '10.6.1.1';
        $client->get('/contact');
        $done = $client->follow($client->post('/contact', [
            '_csrf' => $client->csrf(),
            'kind' => 'proposal',
            'property_name' => 'Northwind Apartments',
            'units' => '120',
            'contact_name' => 'Jordan Lee',
            'contact_email' => 'jordan@example.test',
            'contact_phone' => '229-555-0140',
            'message' => 'We would like a walkthrough next week.',
            'company_website' => '',
        ]));
        $this->assertStringContainsString('we have your request', strtolower($done->body));
        $property = Database::one('SELECT * FROM properties WHERE name = ?', ['Northwind Apartments']);
        $this->assertSame('lead', $property['stage']);
        $this->assertSame(120, (int) $property['units_count']);
        $inquiry = Database::one('SELECT * FROM inquiries WHERE property_name = ?', ['Northwind Apartments']);
        $this->assertSame('new', $inquiry['status']);
        $this->assertSame((int) $property['id'], (int) $inquiry['property_id']);
    }

    public function testEventAndMoveOutRequestsKeepTheirKind(): void
    {
        $client = $this->client();
        $client->ip = '10.6.1.2';
        $client->get('/contact?intent=event_cleanup');
        $this->assertStringContainsString('Party and event cleanup', $client->get('/contact?intent=event_cleanup')->body);
        $done = $client->follow($client->post('/contact', [
            '_csrf' => $client->csrf(),
            'kind' => 'event_cleanup',
            'property_name' => 'Clubhouse Party',
            'units' => '1',
            'contact_name' => 'Casey Host',
            'contact_email' => '',
            'contact_phone' => '229-555-0199',
            'message' => 'Cleanup after a clubhouse party.',
            'company_website' => '',
        ]));
        $this->assertStringContainsString('we have your request', strtolower($done->body));
        $event = Database::one('SELECT kind FROM inquiries WHERE property_name = ?', ['Clubhouse Party']);
        $this->assertSame('event_cleanup', $event['kind']);

        $client->get('/contact?intent=move_out');
        $done = $client->follow($client->post('/contact', [
            '_csrf' => $client->csrf(),
            'kind' => 'move_out',
            'property_name' => 'Unit 12 Move Out',
            'units' => '1',
            'contact_name' => 'Riley Office',
            'contact_email' => 'riley@example.test',
            'contact_phone' => '',
            'message' => 'Move-out cleanout for one apartment.',
            'company_website' => '',
        ]));
        $this->assertStringContainsString('we have your request', strtolower($done->body));
        $move = Database::one('SELECT kind FROM inquiries WHERE property_name = ?', ['Unit 12 Move Out']);
        $this->assertSame('move_out', $move['kind']);
    }

    public function testIncompleteProposalIsRejected(): void
    {
        $client = $this->client();
        $client->get('/contact');
        $response = $client->post('/contact', [
            '_csrf' => $client->csrf(),
            'kind' => 'proposal',
            'property_name' => '',
            'units' => '',
            'contact_name' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'message' => '',
        ]);
        $this->assertSame(422, $response->status);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM inquiries'));
    }

    public function testMissedPickupCreatesAnIssue(): void
    {
        $propertyId = $this->insertProperty('Sunset Court', ['stage' => 'live']);
        $client = $this->client();
        $client->get('/missed-pickup');
        $done = $client->follow($client->post('/missed-pickup', [
            '_csrf' => $client->csrf(),
            'property_name' => 'Sunset Court',
            'unit_label' => 'B4',
            'reporter_name' => 'Alex Resident',
            'reporter_contact' => '229-555-0177',
            'reporter_role' => 'Resident',
            'happened_on' => '2026-10-21',
            'description' => 'Two tied bags were outside the door at 7:40 and still there this morning.',
            'company_website' => '',
        ]));
        $this->assertStringContainsString('logged the missed pickup', $done->body);
        $issue = Database::one('SELECT * FROM issues WHERE unit_label = ?', ['B4']);
        $this->assertSame('missed_pickup', $issue['type']);
        $this->assertSame('public', $issue['source']);
        $this->assertSame('open', $issue['status']);
        $this->assertSame($propertyId, (int) $issue['property_id']);
    }
}
