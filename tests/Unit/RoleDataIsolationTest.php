<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\BudgetPlan;
use App\Models\Quote;
use App\Models\User;
use App\Support\ActivityLogScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RoleDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_cannot_see_other_provider_activity_log(): void
    {
        $providerA = User::factory()->create(['role' => UserRole::Provider]);
        $providerB = User::factory()->create(['role' => UserRole::Provider]);

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'acción de B',
            'causer_type' => User::class,
            'causer_id' => $providerB->id,
        ]);

        $this->actingAs($providerA);

        $this->assertSame(0, ActivityLogScope::query()->count());
    }

    public function test_admin_cannot_see_other_admin_quotes_or_budgets(): void
    {
        $adminA = User::factory()->create(['role' => UserRole::Admin]);
        $adminB = User::factory()->create(['role' => UserRole::Admin]);

        Quote::query()->create([
            'quote_number' => 'COT-A-0001',
            'issuer_name' => 'Emisor A',
            'recipient_name' => 'Cliente',
            'created_by' => $adminA->id,
        ]);

        Quote::query()->create([
            'quote_number' => 'COT-B-0001',
            'issuer_name' => 'Emisor B',
            'recipient_name' => 'Cliente',
            'created_by' => $adminB->id,
        ]);

        BudgetPlan::query()->create([
            'title' => 'Presupuesto B',
            'budget_number' => 'PRE-B-0001',
            'created_by' => $adminB->id,
        ]);

        $this->actingAs($adminA);

        $this->assertSame(1, Quote::query()->forUser($adminA)->count());
        $this->assertSame(0, BudgetPlan::query()->forUser($adminA)->count());
        $this->assertFalse($adminA->canViewGlobalData());
    }

    public function test_admin_can_manage_users_and_assign_super_admin_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create(['role' => UserRole::Provider]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('update', $target));
        $this->assertContains(
            UserRole::SuperAdmin->value,
            array_keys(UserRole::assignableOptionsFor($admin)),
        );
    }

    public function test_super_admin_can_see_all_records(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $provider = User::factory()->create(['role' => UserRole::Provider]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Quote::query()->create([
            'quote_number' => 'COT-P-0001',
            'issuer_name' => 'Proveedor',
            'recipient_name' => 'Cliente',
            'created_by' => $provider->id,
        ]);

        Quote::query()->create([
            'quote_number' => 'COT-A-0001',
            'issuer_name' => 'Admin',
            'recipient_name' => 'Cliente',
            'created_by' => $admin->id,
        ]);

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'acción proveedor',
            'causer_type' => User::class,
            'causer_id' => $provider->id,
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->canViewGlobalData());
        $this->assertSame(2, Quote::query()->forUser($superAdmin)->count());
        $this->assertTrue(
            ActivityLogScope::query()
                ->where('causer_id', $provider->id)
                ->where('description', 'acción proveedor')
                ->exists(),
        );
    }
}
