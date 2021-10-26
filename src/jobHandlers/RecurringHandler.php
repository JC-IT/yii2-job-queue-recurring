<?php

declare(strict_types=1);

namespace JCIT\jobqueue\jobHandlers;

use Closure;
use JCIT\jobqueue\interfaces\JobFactoryInterface;
use JCIT\jobqueue\interfaces\JobHandlerInterface;
use JCIT\jobqueue\interfaces\JobInterface;
use JCIT\jobqueue\interfaces\JobQueueInterface;
use JCIT\jobqueue\jobs\RecurringJob;
use JCIT\jobqueue\models\activeRecord\RecurringJob as ActiveRecordRecurringJob;
use yii\base\InvalidArgumentException;

class RecurringHandler implements JobHandlerInterface
{
    public int $delay = 1;
    public Closure $jobCreatedCallback;
    public int $priority = 2048;
    public Closure $queryModifier;
    public string $queuedAtAttribute = 'queuedAt';
    /** @var class-string */
    public string $recurringJobClass = RecurringJob::class;
    public string $jobDataAttribute = 'jobData';

    public function __construct(
        private JobFactoryInterface $jobFactory,
        private JobQueueInterface $jobQueue
    ) {
    }

    protected function createJob($recurringJob): JobInterface
    {
        if (!$recurringJob instanceof $this->recurringJobClass) {
            throw new InvalidArgumentException('Recurring job must be instance of ' . $this->recurringJobClass);
        }

        $job = $this->jobFactory->createFromArray($recurringJob->{$this->jobDataAttribute});

        if ($this->jobCreatedCallback) {
            ($this->jobCreatedCallback)($job, $recurringJob);
        }

        return $job;
    }

    public function handle(JobInterface $job): void
    {
        $query = $this->recurringJobClass::find();
        if ($this->queryModifier) {
            ($this->queryModifier)($query);
        }

        /** @var ActiveRecordRecurringJob $recurringJob */
        foreach ($query->each() as $recurringJob) {
            try {
                if ($recurringJob->isDue) {
                    $this->jobQueue->putJob(
                        $this->createJob($recurringJob),
                        $this->priority,
                        $this->delay
                    );
                    if ($this->queuedAtAttribute) {
                        $recurringJob->touch($this->queuedAtAttribute);
                    }
                }
            } catch (\Throwable $t) {
                \Yii::error($t);
                throw $t;
            }
        }
    }
}
