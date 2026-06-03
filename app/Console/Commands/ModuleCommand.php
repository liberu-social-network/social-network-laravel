<?php

namespace App\Console\Commands;

use App\Modules\ModuleManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleCommand extends Command
{
    #[\Override]
    protected $signature = 'module {action} {name?} {--force} {--format=text : Output format (text or json)}';

    #[\Override]
    protected $description = 'Manage application modules';

    public function __construct(protected ModuleManager $moduleManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        return match ($action) {
            'list'      => $this->listModules(),
            'enable'    => $this->enableModule($name),
            'disable'   => $this->disableModule($name),
            'install'   => $this->installModule($name),
            'uninstall' => $this->uninstallModule($name),
            'create'    => $this->createModule($name),
            'info'      => $this->showModuleInfo($name),
            default     => $this->showHelp(),
        };
    }

    protected function listModules(): int
    {
        if ($this->option('format') === 'json') {
            $this->line(json_encode($this->moduleManager->getAllModulesInfo(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $modules = $this->moduleManager->all();

        if ($modules->isEmpty()) {
            $this->info('No modules found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Version', 'Status', 'Description'],
            $modules->map(fn ($m) => [
                $m->getName(),
                $m->getVersion(),
                $m->isEnabled() ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                $m->getDescription(),
            ])->values()->toArray()
        );

        return self::SUCCESS;
    }

    protected function enableModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        try {
            if ($this->moduleManager->enable($name)) {
                $this->info("Module '{$name}' has been enabled.");

                return self::SUCCESS;
            }

            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error("Failed to enable module '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function disableModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        try {
            if ($this->moduleManager->disable($name)) {
                $this->info("Module '{$name}' has been disabled.");

                return self::SUCCESS;
            }

            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error("Failed to disable module '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function installModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        try {
            if ($this->moduleManager->install($name)) {
                $this->info("Module '{$name}' has been installed and enabled.");

                return self::SUCCESS;
            }

            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error("Failed to install module '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function uninstallModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to uninstall module '{$name}'? This cannot be undone.")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            if ($this->moduleManager->uninstall($name)) {
                $this->info("Module '{$name}' has been uninstalled.");

                return self::SUCCESS;
            }

            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error("Failed to uninstall module '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function createModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        $modulePath = app_path("Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module '{$name}' already exists.");

            return self::FAILURE;
        }

        $this->createModuleStructure($name, $modulePath);
        $this->info("Module '{$name}' has been created at app/Modules/{$name}.");

        return self::SUCCESS;
    }

    protected function showModuleInfo(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        $info = $this->moduleManager->getModuleInfo($name);

        if (empty($info)) {
            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode($info, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Module Information:');
        $this->line("  Name:         {$info['name']}");
        $this->line("  Version:      {$info['version']}");
        $this->line("  Description:  {$info['description']}");
        $this->line('  Status:       '.($info['enabled'] ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));

        if (! empty($info['dependencies'])) {
            $this->line('  Dependencies: '.implode(', ', $info['dependencies']));
        }

        return self::SUCCESS;
    }

    protected function createModuleStructure(string $name, string $modulePath): void
    {
        $directories = [
            'Providers',
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Services',
            'resources/views',
            'resources/lang',
            'resources/assets',
            'routes',
            'database/migrations',
            'database/seeders',
            'config',
            'tests',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$modulePath}/{$directory}", 0755, true);
        }

        File::put("{$modulePath}/module.json", json_encode([
            'name'         => $name,
            'version'      => '1.0.0',
            'description'  => "Custom {$name} module",
            'dependencies' => [],
            'config'       => [],
        ], JSON_PRETTY_PRINT));

        File::put("{$modulePath}/{$name}Module.php", $this->getModuleClassStub($name));
        File::put("{$modulePath}/Providers/{$name}ServiceProvider.php", $this->getServiceProviderStub($name));
        File::put("{$modulePath}/routes/web.php", "<?php\n\n// Web routes for {$name} module\n");
        File::put("{$modulePath}/routes/api.php", "<?php\n\n// API routes for {$name} module\n");
    }

    protected function getModuleClassStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$name};

use App\\Modules\\BaseModule;

class {$name}Module extends BaseModule
{
    protected function onEnable(): void {}

    protected function onDisable(): void {}

    protected function onInstall(): void {}

    protected function onUninstall(): void {}
}
PHP;
    }

    protected function getServiceProviderStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}
PHP;
    }

    protected function showHelp(): int
    {
        $this->info('Available actions:');
        $this->line('  list                 List all modules');
        $this->line('  enable <name>        Enable a module');
        $this->line('  disable <name>       Disable a module');
        $this->line('  install <name>       Install a module');
        $this->line('  uninstall <name>     Uninstall a module (use --force to skip confirmation)');
        $this->line('  create <name>        Create a new module scaffold');
        $this->line('  info <name>          Show module information');
        $this->line('');
        $this->line('Options:');
        $this->line('  --format=json        Output list/info as JSON');

        return self::SUCCESS;
    }
}
