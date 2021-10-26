<?php

declare(strict_types=1);

namespace JCIT\jobqueue\migrations;

use yii\db\Migration;

class M201103160000CreateRecurringJob extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%recurring_job}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'description' => $this->text(),
                'cron' => $this->string()->notNull(),
                'jobData' => $this->json(),

                'queuedAt' => $this->timestamp()->null(),
                'createdAt' => $this->timestamp()->null(),
                'updatedAt' => $this->timestamp()->null(),
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%recurring_job}}');
    }
}
