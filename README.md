# Job Queue for Yii2 based on Beanstalkd

This extension provides a package that implements a queue, workers and jobs.

```bash
$ composer require jc-it/yii2-job-queue
```

or add

```
"jc-it/yii2-job-queue": "<latest version>"
```

to the `require` section of your `composer.json` file.

## Important

This package has been created to share code between projects (for now). This means that it can change regularly with breaking changes.

Make sure a specific version is used.

## Configuration

- You need to have Beanstalk installed (`sudo apt install beanstalkd`)
- Apply configuration:

```php
'container' => [
    'definitions' => [
        \League\Tactician\CommandBus::class => function(\yii\di\Container $container) {
            return new \League\Tactician\CommandBus([
                new \League\Tactician\Handler\CommandHandlerMiddleware (
                    $container->get(\League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor::class),
                    $container->get(\League\Tactician\Handler\Locator\HandlerLocator::class),
                    $container->get(\League\Tactician\Handler\MethodNameInflector\MethodNameInflector::class)
                )
            ]);
        },
        \JCIT\jobqueue\components\ContainerMapLocator::class => function(\yii\di\Container $container) {
            $result = new \JCIT\jobqueue\components\ContainerMapLocator($container);
            // Register handlers here
            // i.e. $result->setHandlerForCommand(\JCIT\jobqueue\jobs\HelloJob::class, \JCIT\jobqueue\jobHandlers\HelloHandler::class);
            return $result;
        },
        \League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor::class => \League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor::class,
        \League\Tactician\Handler\Locator\HandlerLocator::class => \JCIT\jobqueue\components\ContainerMapLocator::class,
        \JCIT\jobqueue\interfaces\JobFactoryInterface::class => \JCIT\jobqueue\factories\JobFactory::class,
        \JCIT\jobqueue\interfaces\JobQueueInterface::class => \JCIT\jobqueue\components\Beanstalk::class,
        \League\Tactician\Handler\MethodNameInflector\MethodNameInflector::class => \League\Tactician\Handler\MethodNameInflector\HandleInflector::class,
        \Pheanstalk\Contract\PheanstalkInterface::class => \JCIT\jobqueue\components\Beanstalk::class,
        \Pheanstalk\Contract\SocketFactoryInterface::class => function() {
            return new \Pheanstalk\SocketFactory('localhost', 11300, 10);
        },
    ]
],
```

- Register Daemon action in controller:

```php
public function actions(): array
{
    return [
        'daemon' => \JCIT\jobqueue\actions\DaemonAction::class,
    ];
}
```
- Run action, i.e. `./yii job-queue/daemon`

### Optional: install daemon as service

Look [here](https://www.yiiframework.com/extension/yiisoft/yii2-queue/doc/guide/2.0/en/worker) to see an example how a console action can be installed as a linux service.

## Quick test

- Execute steps in Configuration
- Register `\JCIT\jobqueue\jobs\HelloJob::class` and `\JCIT\jobqueue\jobHandlers\HelloHandler::class` in the `ContainerMapLocator` (as shown in the configuration)
- Create `JobQueueController` console controller
```php
class JobQueueController extends \yii\console\Controller
{
    public $defaultAction = 'daemon';

    public function actionTest(
        \JCIT\jobqueue\interfaces\JobQueueInterface $jobQueue
    ) {
        $task = \Yii::createObject(\JCIT\jobqueue\jobs\HelloJob::class, ['world']);
        $jobQueue->putJob($task);
    }

    /**
     * @return array
     */
    public function actions(): array
    {
        return [
            'daemon' => [
                'class' => \JCIT\jobqueue\actions\DaemonAction::class,
            ]
        ];
    }
}
```
- Run in one console `src/yii job-queue`
- Run in a second console `src/yii job-queue/test`
- The console that runs the daemon will show `Hello world`
- **NOTE** the daemon must be restarted when handlers have changed

## Own implementations
- Create Job (that implements `\JCIT\jobqueue\interfaces\JobInterface::class`) which should not do more than carry data
- Create JobHandler (that implements `\JCIT\jobqueue\interfaces\JobHandlerInterface::class`) which handles the handling of the job
  - Injection should be done on construction of the handler

## Logging
- Implement `\JCIT\jobqueue\interfaces\JobHandlerLoggerInterface::class` and register it in your DI
- Either add it to you JobHandler or extend `\JCIT\jobqueue\jobHandlers\LoggingHandler::class`
- `\JCIT\jobqueue\events\JobQueueEvent` is triggered on `->put` and `->handle` to enable even more precise logging  

## Recurring jobs
A possible implementation for recurring jobs has been added. This implementation stores the recurrence using the [Cron](https://crontab.guru/) notation and an Active Record model. 
It can easily be extended by creating own implementations for the AR model and Handler.

To use the recurring jobs with the implementation as provided by the package:
- Add `\JCIT\jobqueue\migrations` to your migration namespaces, or extend a new migration from the migration in the package and run them
- Register `\JCIT\jobqueue\jobs\RecurringJob::class` and `\JCIT\jobqueue\jobHandlers\RecurringHandler::class` in the `ContainerMapLocator` (as shown in the configuration) 
- Add the recurring action to the controller:
```php
public function actions(): array
{
    return [
        'daemon' => \JCIT\jobqueue\actions\DaemonAction::class,
        'recurring' => \JCIT\jobqueue\actions\RecurringJobAction::class,
    ];
}
```
- Run action, i.e. `./yii job-queue/recurring`
- Add a recurring job to the database i.e.
```php
$jobFactory = \Yii::createObject(\JCIT\jobqueue\interfaces\JobFactoryInterface::class);
$job = new \JCIT\jobqueue\models\activeRecord\RecurringJob([
    'name' => 'Hello world job',
    'description' => 'Say hello to the world every minute',
    'job_data' => $jobFactory->saveToArray(new \JCIT\jobqueue\jobs\HelloJob('world')),
    'cron' => '* * * * *'
]);
$job->save();
```

## Credits
- [Sam Mousa](https://github.com/SamMousa)
- [Joey Claessen](https://github.com/joester89)

## License

This code is proprietary but [Wolfpack IT (Works B.V.)](https://github.com/wolfpack-it) has a forever-use right in her or her customers' projects. 
Modifications can be made in a fork of this repository but it is not allowed to copy code outside. 

## Wolfpack IT Hints
- Look at the [Urban Journalist project](https://gitlab.com/wolfpackit/projects/urban-journalist/uj---rebuild/api-backend-frontend) where this package has been used.