<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentHistoryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole();
    }

    private function payment(array $attributes = [], ?Member $member = null): Payment
    {
        return Payment::create(array_merge([
            'member_id' => ($member ?? Member::factory()->create())->id,
            'amount' => 2000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_at' => '2026-09-13 12:00:00',
            'reference' => 'RECEIPT-ABC',
        ], $attributes));
    }

    public function test_search_matches_member_fields_and_reference(): void
    {
        $member = Member::factory()->create([
            'first_name' => 'Samir', 'last_name' => 'Lama', 'membership_number' => 'GYM-12345',
        ]);
        $payment = $this->payment([], $member);
        $this->payment(['reference' => 'OTHER'], Member::factory()->create([
            'first_name' => 'Jane', 'last_name' => 'Doe', 'membership_number' => 'GYM-98765',
        ]));

        foreach (['Samir', 'Lama', 'Samir Lama', 'GYM-12345', 'RECEIPT-ABC'] as $search) {
            $this->get(route('billing.index', ['search' => " $search "]))
                ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Index')
                ->where('filters.search', $search)
                ->has('payments.data', 1)
                ->where('payments.data.0.id', $payment->id)
                ->where('payments.data.0.member.first_name', 'Samir'));
        }
    }

    public function test_combined_filters_and_inclusive_date_boundaries(): void
    {
        $first = $this->payment(['paid_at' => '2026-09-12 00:00:00']);
        $last = $this->payment(['paid_at' => '2026-09-13 23:59:59']);
        foreach ([
            ['paid_at' => '2026-09-11 23:59:59'],
            ['paid_at' => '2026-09-14 00:00:00'],
            ['paid_at' => null],
            ['payment_status' => 'pending'],
            ['payment_method' => 'card'],
            ['reference' => 'OTHER'],
        ] as $attributes) {
            $this->payment($attributes);
        }

        $filters = ['search' => 'RECEIPT-ABC', 'status' => 'paid', 'method' => 'cash',
            'from_date' => '2026-09-12', 'to_date' => '2026-09-13'];
        $this->get(route('billing.index', $filters))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters', $filters)->has('payments.data', 2)
                ->where('payments.data.0.id', $last->id)
                ->where('payments.data.1.id', $first->id));
    }

    public function test_filters_work_individually_and_zero_is_searchable(): void
    {
        $member = Member::factory()->create(['membership_number' => 'GYM-ABC', 'first_name' => 'Jane', 'last_name' => 'Doe']);
        $this->payment(['reference' => '0'], $member);
        $this->payment(['payment_status' => 'pending', 'payment_method' => 'card',
            'paid_at' => '2026-09-10 12:00:00', 'reference' => 'OTHER'], $member);
        foreach ([['status' => 'paid'], ['method' => 'cash'], ['from_date' => '2026-09-13'],
            ['to_date' => '2026-09-10'], ['search' => '0']] as $filter) {
            $this->get(route('billing.index', $filter))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('payments.data', 1));
        }
    }

    public function test_pagination_preserves_filters(): void
    {
        $member = Member::factory()->create();
        for ($i = 0; $i < 16; $i++) {
            $this->payment([], $member);
        }
        $this->get(route('billing.index', ['status' => 'paid', 'method' => 'cash']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 15)->where('payments.total', 16)
            ->where('payments.next_page_url', function ($url) {
                parse_str(parse_url($url, PHP_URL_QUERY), $query);

                return $query['status'] === 'paid' && $query['method'] === 'cash' && $query['page'] === '2';
            }));
        $this->get(route('billing.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.search', '')->where('payments.total', 16));
    }

    public function test_invalid_filters_are_rejected(): void
    {
        foreach ([['status' => 'unknown'], ['method' => 'unknown'], ['search' => ['bad']],
            ['from_date' => 'invalid'], ['to_date' => '2026-02-30'],
            ['to_date' => '2026-09-12', 'from_date' => '2026-09-13']] as $filter) {
            $this->from(route('billing.index'))->get(route('billing.index', $filter))->assertRedirect(route('billing.index'))
                ->assertSessionHasErrors(array_key_first($filter));
        }
    }
}
