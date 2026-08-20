<?php

namespace Tests\Feature;

use App\Enums\BudgetCategoryType;
use App\Enums\SavingsTransactionType;
use App\Enums\UserRole;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\Savings\SavingsLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SavingsLedgerTest extends TestCase
{
    use RefreshDatabase;

    private SavingsLedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(SavingsLedgerService::class);
    }

    public function test_opening_deposit_withdrawal_and_replenishment_update_balances(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $account = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Fondo general',
        ]);

        $this->ledger->recordOpening($account, 300, 'Saldo inicial');
        $account->refresh();
        $this->assertSame(300.0, (float) $account->current_balance);
        $this->assertSame(0.0, (float) $account->pending_replenishment);

        $this->ledger->recordDeposit($account, 70, 'Ahorro quincenal');
        $account->refresh();
        $this->assertSame(370.0, (float) $account->current_balance);

        $withdrawal = $this->ledger->recordWithdrawal($account, 50, 'Emergencia');
        $account->refresh();
        $this->assertSame(320.0, (float) $account->current_balance);
        $this->assertSame(50.0, (float) $account->pending_replenishment);

        $this->ledger->recordReplenishment($account, $withdrawal, 50);
        $account->refresh();
        $this->assertSame(370.0, (float) $account->current_balance);
        $this->assertSame(0.0, (float) $account->pending_replenishment);
    }

    public function test_withdrawal_is_blocked_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $account = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Fondo',
        ]);

        $this->ledger->recordOpening($account, 100);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $this->ledger->recordWithdrawal($account, 150);
    }

    public function test_replenishment_cannot_exceed_pending_withdrawal_amount(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $account = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Fondo',
        ]);

        $this->ledger->recordOpening($account, 200);
        $withdrawal = $this->ledger->recordWithdrawal($account, 50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('supera el monto pendiente');

        $this->ledger->recordReplenishment($account, $withdrawal, 60);
    }

    public function test_budget_item_creates_single_deposit_when_marked_paid(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $account = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Ahorro quincenal',
        ]);

        $plan = BudgetPlan::query()->create([
            'title' => 'Quincena 1',
            'budget_number' => 'PRE-001',
            'created_by' => $user->id,
        ]);

        $item = BudgetPlanItem::query()->create([
            'budget_plan_id' => $plan->id,
            'category_type' => BudgetCategoryType::Savings,
            'concept' => 'Ahorro quincenal',
            'amount' => 70,
            'is_paid' => true,
            'paid_at' => now()->toDateString(),
            'savings_account_id' => $account->id,
        ]);

        $transaction = $this->ledger->syncDepositFromBudgetItem($item);
        $this->assertNotNull($transaction);
        $this->assertSame(SavingsTransactionType::Deposit, $transaction->type);
        $this->assertSame($item->id, $transaction->budget_plan_item_id);

        $account->refresh();
        $this->assertSame(70.0, (float) $account->current_balance);

        $duplicate = $this->ledger->syncDepositFromBudgetItem($item->fresh());
        $this->assertNull($duplicate);
        $this->assertSame(1, SavingsTransaction::query()->where('budget_plan_item_id', $item->id)->count());
    }

    public function test_summary_for_user_aggregates_active_accounts(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $active = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Activa',
            'is_active' => true,
        ]);

        $inactive = SavingsAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Archivada',
            'is_active' => false,
        ]);

        $this->ledger->recordOpening($active, 300);
        $this->ledger->recordWithdrawal($active, 50);
        $this->ledger->recordOpening($inactive, 1000);

        $summary = $this->ledger->summaryForUser($user);

        $this->assertSame(250.0, $summary['total_balance']);
        $this->assertSame(50.0, $summary['pending_replenishment']);
        $this->assertSame(1, $summary['active_accounts']);
    }
}
