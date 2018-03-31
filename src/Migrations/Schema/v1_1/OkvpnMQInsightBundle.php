<?php

namespace Okvpn\Bundle\MQInsightBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OkvpnMQInsightBundle implements Migration, ConnectionAwareInterface
{
    /** @var Connection */
    private $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $this->setPrimaryKey($schema, $queries, 'okvpn_mq_change_stat');
            $this->setPrimaryKey($schema, $queries, 'okvpn_mq_error_stat');
        }

        $table = $schema->getTable('okvpn_mq_processor_stat');
        $table->addColumn('priority', Type::INTEGER, ['notnull' => false]);

        $table = $schema->getTable('okvpn_mq_error_stat');
        $table->addColumn('redeliver_count', Type::INTEGER, ['notnull' => false]);
        $table->addIndex(['message_id']);
    }

    private function setPrimaryKey(Schema $schema, QueryBag $queries, $table)
    {
        $queries->addPreQuery("--
            delete from $table where id in (
              select id from $table GROUP BY id HAVING count(id) > 1
            )"
        );

        $table = $schema->getTable($table);
        if (!$table->hasPrimaryKey()) {
            $table->setPrimaryKey(['id']);
        }
    }
}
