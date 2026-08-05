<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AdminViewMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminViewModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_toggle_provider_preview(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->viewsAsAdmin());
        $this->assertFalse($superAdmin->viewsAsProvider());

        AdminViewMode::enableProviderPreview();

        $this->assertTrue(AdminViewMode::isProviderPreview());
        $this->assertTrue($superAdmin->fresh()->viewsAsProvider());
        $this->assertFalse($superAdmin->fresh()->viewsAsAdmin());

        AdminViewMode::disableProviderPreview();

        $this->assertFalse(AdminViewMode::isProviderPreview());
        $this->assertTrue($superAdmin->fresh()->viewsAsAdmin());
    }

    public function test_regular_admin_cannot_toggle_provider_preview(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        AdminViewMode::enableProviderPreview();
    }

    public function test_provider_cannot_enable_preview_mode(): void
    {
        $provider = User::factory()->create(['role' => UserRole::Provider]);

        $this->actingAs($provider);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        AdminViewMode::enableProviderPreview();
    }
}
