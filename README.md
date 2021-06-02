# A recurring job extension for Job Queue for Yii2 based on Beanstalkd

This extension provides a package that implements a way to store recurring jobs in the database for the yii2 job queue.

```bash
$ composer require jc-it/yii2-job-queue-recurring
```

or add

```
"jc-it/yii2-job-queue-recurring": "<latest version>"
```

to the `require` section of your `composer.json` file.

## Configuration

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
