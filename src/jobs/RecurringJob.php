<?php
declare(strict_types=1);

namespace JCIT\jobqueue\jobs;

use JCIT\jobqueue\interfaces\JobInterface;

class RecurringJob implements JobInterface
{
    public static function fromArray(array $config): JobInterface
    {
        return new static;
    }

    public function jsonSerialize(): array
    {
        return [];
    }
}
