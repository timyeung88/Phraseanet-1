<?php

namespace Alchemy\Phrasea;

use Symfony\Component\Console;

/**
 * Phraseanet Command Line Application
 *
 * Largely inspired by Cilex
 * @see https://github.com/Cilex/Cilex
 */
class CLI extends Application
{

    /**
     * Registers the autoloader and necessary components.
     *
     * @param string $name Name for this application.
     * @param string|null $version Version number for this application.
     */
    function __construct($name, $version = null)
    {
        parent::__construct();

        $this['console'] = $this->share(function () use ($name, $version) {
                return new Console\Application($name, $version);
            });
    }

    /**
     * Executes this application.
     *
     * @param bool $interactive runs in an interactive shell if true.
     */
    public function run($interactive = false)
    {
        $app = $this['console'];
        if ($interactive) {
            $app = new Console\Shell($app);
        }

        $app->run();
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param \Cilex\Command\Command $command A Command object
     */
    public function command(Command\Command $command)
    {
        $command->setContainer($this);
        $this['console']->add($command);
    }
}
