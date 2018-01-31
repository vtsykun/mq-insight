<?php

namespace Okvpn\Bundle\MQInsightBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OkvpnMQInsightBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('okvpn_mq_state_stat');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('created', Type::DATETIME);
        $table->addColumn('queue', Type::INTEGER);
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('okvpn_mq_processor_stat');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('created', Type::DATETIME);
        $table->addColumn('name', Type::STRING, ['length' => 255]);
        $table->addColumn('avg_time', Type::DECIMAL, ['scale' => 3, 'precision' => 7, 'notnull' => false]);
        $table->addColumn('max_time', Type::DECIMAL, ['scale' => 3, 'precision' => 7, 'notnull' => false]);
        $table->addColumn('min_time', Type::DECIMAL, ['scale' => 3, 'precision' => 7, 'notnull' => false]);
        $table->addColumn('ack', Type::INTEGER);
        $table->addColumn('reject', Type::INTEGER);
        $table->addColumn('requeue', Type::INTEGER);
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('okvpn_mq_change_stat');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('created', Type::DATETIME);
        $table->addColumn('added', Type::INTEGER);
        $table->addColumn('removed', Type::INTEGER);
        $table->addColumn('channel', Type::STRING, ['notnull' => false, 'length' => 32]);
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('okvpn_mq_error_stat');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('created', Type::DATETIME);
        $table->addColumn('processor_name', Type::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('message_id', Type::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('log', Type::TEXT, ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }
}
