<?php


use Phinx\Migration\AbstractMigration;

class EmailTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $emailAddresses = $this->table('email_address');
        $emailAddresses->addColumn('address', 'string', ['limit' => 100])
            ->addColumn('uses', 'integer', ['default' => 0])
            ->addIndex('address', ['unique' => true])
            ->save();

        $emails = $this->table('sent_email');
        $emails->addColumn('timestamp', 'datetime')
            ->addColumn('subject', 'string', ['limit' => 100])
            ->addColumn('body', 'string', ['limit' => 1000])
            ->addColumn('alt_body', 'string', ['limit' => 1000])
            ->addColumn('job_id', 'integer', ['null' => false])
            ->addColumn('address_id', 'integer', ['null' => false])
            ->addForeignKey('job_id', 'job', 'id')
            ->addForeignKey('address_id', 'email_address', 'id')
            ->save();

    }
}
