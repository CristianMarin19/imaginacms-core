<?php

namespace Modules\Core\Sidebar;

use Illuminate\Contracts\Container\Container;
use Maatwebsite\Sidebar\Menu;
use Maatwebsite\Sidebar\ShouldCache;
use Maatwebsite\Sidebar\Sidebar;
use Maatwebsite\Sidebar\Traits\CacheableTrait;
use Modules\Core\Events\BuildingSidebar;
use Nwidart\Modules\Contracts\RepositoryInterface;

class AdminSidebar implements Sidebar, ShouldCache
{
    use CacheableTrait;

    /**
     * @var Menu
     */
    protected $menu;

    /**
     * @var RepositoryInterface
     */
    protected $modules;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Menu $menu, RepositoryInterface $modules, Container $container)
    {
        $this->menu = $menu;
        $this->modules = $modules;
        $this->container = $container;
    }

    /**
     * Build your sidebar implementation here
     */
    public function build()
    {
        event($event = new BuildingSidebar($this->menu));

        foreach ($this->modules->allEnabled() as $module) {
            $lowercaseModule = strtolower($module->get('name'));
            if ($this->hasCustomSidebar($lowercaseModule) === true) {
                $class = config("asgard.{$lowercaseModule}.config.custom-sidebar");
                $this->addToSidebar($class);

                continue;
            }

            $name = $module->get('name');
            $class = 'Modules\\'.$name.'\\Sidebar\\SidebarExtender';
            $this->addToSidebar($class);
        }
    }

    /**
     * Add the given class to the sidebar collection
     */
    private function addToSidebar(string $class)
    {
        if (class_exists($class) === false) {
            return;
        }
        $extender = $this->container->make($class);

        $this->menu->add($extender->extendWith($this->menu));
    }

    public function getMenu(): Menu
    {
        $this->build();

        return $this->menu;
    }

    /**
     * Check if the module has a custom sidebar class configured
     */
    private function hasCustomSidebar(string $module): bool
    {
        $config = config("asgard.{$module}.config.custom-sidebar");

        return $config !== null;
    }
}
