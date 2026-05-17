<?php

declare(strict_types=1);

/**
 * container.php — FlexCore DI Container bindings.
 *
 * Each line tells the Container which concrete class
 * should be instantiated when a service or controller
 * requests an interface (or base class).
 *
 * How to use anywhere in the codebase:
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
use FlexCore\App\Controllers\AuditController;
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
// PluginLoader is instantiated directly in the bootstrap with a safe APP_VERSION
// (APP_VERSION only exists after index.php — do not register it as a singleton here)

return $container;
