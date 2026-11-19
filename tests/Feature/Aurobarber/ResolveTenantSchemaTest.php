<?php

use App\Http\Middleware\ResolveTenantSchema;
use App\Models\Barbershop;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

test('resolve tenant schema falls back to session tenant when referer is missing on livewire requests', function () {
    $tenant = Barbershop::create([
        'name' => 'Barbearia Sessão',
        'slug' => 'barbearia-sessao',
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono Sessão',
        'email' => 'dono-sessao@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($tenant->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    $request = Request::create('/livewire/update', 'POST');
    $session = app('session.store');
    $session->put('aurobarber.active_tenant_id', $tenant->id);
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $owner);

    $middleware = new ResolveTenantSchema(app(TenantService::class));

    $middleware->handle($request, fn (): Response => new Response('ok'));

    expect(app()->bound('current_tenant_barbershop'))->toBeTrue()
        ->and(app('current_tenant_barbershop')->id)->toBe($tenant->id);
});

test('resolve tenant schema ignores session tenant if authenticated user cannot access it', function () {
    $tenantA = Barbershop::create([
        'name' => 'Barbearia A',
        'slug' => 'barbearia-a',
        'is_active' => true,
    ]);
    $tenantB = Barbershop::create([
        'name' => 'Barbearia B',
        'slug' => 'barbearia-b',
        'is_active' => true,
    ]);

    $ownerA = User::create([
        'name' => 'Dono A',
        'email' => 'dono-a@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenantA->id,
        'is_active' => true,
    ]);
    $ownerA->barbershops()->attach($tenantA->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    app()->forgetInstance('current_tenant_barbershop');

    $request = Request::create('/livewire/update', 'POST');
    $session = app('session.store');
    $session->put('aurobarber.active_tenant_id', $tenantB->id);
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $ownerA);

    $middleware = new ResolveTenantSchema(app(TenantService::class));

    $middleware->handle($request, fn (): Response => new Response('ok'));

    expect(app()->bound('current_tenant_barbershop'))->toBeFalse();
});

test('resolve tenant schema resolves public booking route slug and switches to tenant schema without altering admin session', function () {
    $tenant = Barbershop::create([
        'name' => 'Barbearia Pública',
        'slug' => 'barbearia-publica',
        'schema_name' => 'tenant_barbearia_publica',
        'is_active' => true,
    ]);

    app()->forgetInstance('current_tenant_barbershop');

    $tenantService = Mockery::mock(TenantService::class);
    $tenantService->shouldReceive('switchToPublic')->once();
    $tenantService->shouldReceive('resolveTenantFromHost')->once()->andReturnNull();
    $tenantService->shouldReceive('switchToTenant')->once()->with('tenant_barbearia_publica');

    $request = Request::create('/barbearia-publica/book', 'POST');
    $route = new Route('POST', '/{slug}/book', fn () => 'ok');
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $session = app('session.store');
    $session->put('aurobarber.active_tenant_id', 999);
    $request->setLaravelSession($session);

    $middleware = new ResolveTenantSchema($tenantService);
    $middleware->handle($request, fn (): Response => new Response('ok'));

    expect(app()->bound('current_tenant_barbershop'))->toBeTrue()
        ->and(app('current_tenant_barbershop')->id)->toBe($tenant->id)
        ->and($session->get('aurobarber.active_tenant_id'))->toBe(999);
});

test('resolve tenant schema resolves tenant from path segments when route is not yet bound', function () {
    $tenant = Barbershop::create([
        'name' => 'Barbearia Marinho',
        'slug' => 'marinho',
        'schema_name' => 'tenant_marinho',
        'is_active' => true,
    ]);

    app()->forgetInstance('current_tenant_barbershop');

    $tenantService = Mockery::mock(TenantService::class);
    $tenantService->shouldReceive('switchToPublic')->once();
    $tenantService->shouldReceive('resolveTenantFromHost')->once()->andReturnNull();
    $tenantService->shouldReceive('switchToTenant')->once()->with('tenant_marinho');

    // Request direto sem route binding (simulando pipeline global inicial)
    $request = Request::create('/admin/marinho/appointments', 'GET');
    $session = app('session.store');
    $request->setLaravelSession($session);

    $middleware = new ResolveTenantSchema($tenantService);
    $middleware->handle($request, fn (): Response => new Response('ok'));

    expect(app()->bound('current_tenant_barbershop'))->toBeTrue()
        ->and(app('current_tenant_barbershop')->id)->toBe($tenant->id)
        ->and($session->get('aurobarber.active_tenant_id'))->toBe($tenant->id);
});
