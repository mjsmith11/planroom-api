<?php


use Phinx\Migration\AbstractMigration;

class JobTable extends AbstractMigration
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
        $jobs = $this->table('job');
        $jobs->addColumn('name', 'string', ['limit' => 75])
             ->addColumn('bidDate', 'date')
             ->addColumn('subcontractorBidsDue', 'datetime')
             ->addColumn('prebidDateTime','datetime')
             ->addColumn('prebidAddress', 'string', ['limit' => 150])
             ->addColumn('bidEmail', 'string', ['limit' => 100])
             ->addColumn('bonding','boolean')
             ->addColumn('taxible', 'boolean')
             ->addIndex('bidDate',['unique' => false])
             ->save();
    }
}
