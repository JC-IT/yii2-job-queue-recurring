<?php
declare(strict_types=1);

namespace JCIT\jobqueue\models\activeRecord;

use Cron\CronExpression;
use JCIT\jobqueue\interfaces\JobFactoryInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\validators\DefaultValueValidator;
use yii\validators\InlineValidator;
use yii\validators\NumberValidator;
use yii\validators\RequiredValidator;
use yii\validators\StringValidator;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $cron
 * @property int|null $priority
 * @property int|null $delay
 * @property int|null $ttr
 * @property array $jobData
 * @property string|null $queuedAt
 * @property string|null $createdAt
 * @property string|null $updatedAt
 *
 * @property-read bool $isDue
 */
class RecurringJob extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class => [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    public function getIsDue(): bool
    {
        return CronExpression::factory($this->cron)->isDue();
    }

    public function rules(): array
    {
        return [
            [['name', 'cron', 'jobData'], RequiredValidator::class],
            [['description'], StringValidator::class],
            [['cron'], function ($attribute, $params, InlineValidator $validator) {
                try {
                    CronExpression::factory($this->cron);
                } catch (\InvalidArgumentException $e) {
                    $this->addError($attribute, $e->getMessage());
                }
            }],

            [['delay', 'priority', 'ttr'], DefaultValueValidator::class, 'value' => null],
            [['delay', 'priority', 'ttr'], NumberValidator::class, 'integerOnly' => true, 'min' => 0],
            [['jobData'], function ($attribute, $params, InlineValidator $validator) {
                try {
                    \Yii::createObject(JobFactoryInterface::class)->createFromArray($this->jobData);
                } catch (\Throwable $t) {
                    $this->addError($attribute, $t->getMessage());
                }
            }]
        ];
    }
}
