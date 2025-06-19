<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCompanyRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_company(): void
    {
        $user = User::factory()->create();

        // Criar role CA com forceFill
        $role = new Role();
        $role->forceFill([
            'name' => 'Company Admin',
            'code' => 'CA',
        ])->save();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/companies', [
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.com',
            'company_type' => 'Startup',
            'sector' => 'Tecnologia',
        ]);

        $response->assertStatus(201);

        $empresa = Company::where('email', 'empresa@teste.com')->first();

        $this->assertDatabaseHas('companies', [
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.com',
            'status' => 'Inativo',
        ]);

        $this->assertDatabaseHas('user_company_role', [
            'user_id' => $user->id,
            'company_id' => $empresa->id,
            'role_id' => $role->id,
        ]);
    }

    /** @test */
    public function super_admin_can_validate_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.com',
            'company_type' => 'Startup',
            'sector' => 'Tecnologia',
            'status' => 'Inativo',
        ]);

        // Email tem de ser admin@admin.com!
        $admin = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/companies/{$company->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'Ativo',
        ]);
    }
}
