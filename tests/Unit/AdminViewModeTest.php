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

    public function test_admin_can_toggle_provider_preview(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin);

        $this->assertTrue($admin->viewsAsAdmin());
        $this->assertFalse($admin->viewsAsProvider());

        AdminViewMode::enableProviderPreview();

        $this->assertTrue(AdminViewMode::isProviderPreview());
        $this->assertTrue($admin->fresh()->viewsAsProvider());
        $this->assertFalse($admin->fresh()->viewsAsAdmin());

        AdminViewMode::disableProviderPreview();

        $this->assertFalse(AdminViewMode::isProviderPreview());
        $this->assertTrue($admin->fresh()->viewsAsAdmin());
    }

    public function test_provider_cannot_enable_preview_mode(): void
    {
        $provider = User::factory()->create(['role' => UserRole::Provider]);

        $this->actingAs($provider);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        AdminViewMode::enableProviderPreview();
    }
}
