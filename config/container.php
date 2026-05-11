<?php

declare(strict_types=1);

/**
 * container.php — Bindings do Container de DI do FlexCore.
 *
 * Cada linha faz o Container saber qual classe concreta
 * instanciar quando um serviço ou controller pedir uma
 * interface (ou classe base).
 *
 * Como usar em qualquer lugar do código:
 *   $container = \FlexCore\Core\Container\Container::getInstance();
 *   $service   = $container->make(\FlexCore\App\Services\RecordService::class);
 */

use FlexCore\Core\Container\Container;
use FlexCore\App\Repositories\RecordRepository;
use FlexCore\App\Repositories\FieldRepository;
use FlexCore\App\Repositories\EntityRepository;
use FlexCore\App\Repositories\AutomationRepository;
use FlexCore\App\Services\RecordService;
use FlexCore\App\Services\AuditService;
use FlexCore\Modules\Automations\AutomationEngine;
use FlexCore\Modules\Automations\Actions\WebhookAction;
use FlexCore\Modules\Plugins\PluginLoader;

$container = Container::getInstance();

// ── Repositories ──────────────────────────────────────────────────────
$container->singleton(RecordRepository::class,     RecordRepository::class);
$container->singleton(FieldRepository::class,      FieldRepository::class);
$container->singleton(EntityRepository::class,     EntityRepository::class);
$container->singleton(AutomationRepository::class, AutomationRepository::class);

// ── Services ──────────────────────────────────────────────────────────
$container->singleton(AuditService::class, AuditService::class);

$container->singleton(RecordService::class, function (Container $c) {
    return new RecordService(
        $c->make(RecordRepository::class),
        $c->make(FieldRepository::class),
        $c->make(EntityRepository::class),
        $c->make(AuditService::class)
    );
});

// ── Automations ───────────────────────────────────────────────────────
$container->singleton(AutomationEngine::class, function (Container $c) {
    $engine = new AutomationEngine($c->make(AutomationRepository::class));
    $engine->registerAction('webhook', new WebhookAction());
    return $engine;
});

// ── Plugins ───────────────────────────────────────────────────────────
// PluginLoader é instanciado diretamente no bootstrap com APP_VERSION seguro
// (APP_VERSION só existe após index.php — não registrar como singleton aqui)

return $container;
