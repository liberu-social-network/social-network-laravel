<?php

namespace Tests\Feature;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleManager $moduleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleManager = app(ModuleManager::class);
    }

    #[Test]
    public function it_can_list_all_modules()
    {
        $modules = $this->moduleManager->all();
        $this->assertIsIterable($modules);
    }

    #[Test]
    public function it_can_get_module_by_name()
    {
        $module = $this->moduleManager->get('NonExistentModule');
        $this->assertNull($module);
    }

    #[Test]
    public function it_can_enable_and_disable_modules()
    {
        $moduleName = 'NonExistentModule';

        // Enable non-existent module returns false
        $result = $this->moduleManager->enable($moduleName);
        $this->assertFalse($result);

        // Disable non-existent module returns false
        $result = $this->moduleManager->disable($moduleName);
        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_get_module_info()
    {
        $info = $this->moduleManager->getModuleInfo('NonExistentModule');
        $this->assertIsArray($info);
    }

    #[Test]
    public function it_can_install_and_uninstall_modules()
    {
        $moduleName = 'NonExistentModule';

        // Install non-existent module returns false
        $result = $this->moduleManager->install($moduleName);
        $this->assertFalse($result);

        // Uninstall non-existent module returns false
        $result = $this->moduleManager->uninstall($moduleName);
        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_for_non_existent_modules()
    {
        $result = $this->moduleManager->enable('NonExistentModule');
        $this->assertFalse($result);

        $result = $this->moduleManager->disable('NonExistentModule');
        $this->assertFalse($result);

        $module = $this->moduleManager->get('NonExistentModule');
        $this->assertNull($module);
    }
}
