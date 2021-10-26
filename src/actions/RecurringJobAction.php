<?php

declare(strict_types=1);

namespace JCIT\jobqueue\actions;

use JCIT\jobqueue\jobs\RecurringJob;
use League\Tactician\CommandBus;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\console\Application;

class RecurringJobAction extends Action
{
    public function __construct(
        $id,
        $controller,
        private CommandBus $commandBus,
        $config = []
    ) {
        parent::__construct($id, $controller, $config);
    }

    public function init(): void
    {
        if (!$this->controller->module instanceof Application) {
            throw new InvalidConfigException('This action can only be used in a console application.');
        }

        parent::init();
    }

    public function run(): void
    {
        while (true) {
            try {
                $this->commandBus->handle(new RecurringJob());
                sleep(60);
            } catch (\Throwable $throwable) {
                \Yii::error($throwable);
                sleep(60);
            }
        }
    }
}
