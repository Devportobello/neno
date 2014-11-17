<?php

/**
 * @package Lingo
 * @subpackage Database
 * 
 * @copyright (c) 2014, Jensen Technologies S.L. All rights reserved
 */
// If the database type is mysqli, let's created a middle class that inherit from the Mysqli drive
if (JFactory::getConfig()->get('dbtype') === 'mysqli') {

    class CommonDriver extends JDatabaseDriverMysqli
    {
        
    }

} else {

    // @TODO JDatabaseDriverMysql is already deprecated, so we should remove this class when the minimum PHP version don't support this extension
    class CommonDriver extends JDatabaseDriverMysql
    {
        
    }

}

/**
 * Database driver class extends from Joomla Platform Database Driver class
 *
 * @package Lingo
 * @subpackage Database
 * @since 1.0
 */
class LingoDatabaseDriverMysqlx extends CommonDriver
{

    /**
     * @inheritdoc
     */
    public function replacePrefix($sql, $prefix = '#__')
    {
        $queryType = LingoDatabaseParser::getQueryType($sql);
        
        // If the query is a select statement let's
        if ($queryType === LingoDatabaseParser::SELECT_QUERY) {
            $sql = LingoDatabaseParser::getCurrentShadowTableName($sql);
        }

        return parent::replacePrefix($sql, $prefix);
    }

    /**
     * Set Autoincrement index in a shadow table
     * @param string $shadowTable Shadow table name
     * @param integer $autoincrementIndex Auto increment index
     * @return boolean True on sucess, false otherwise
     */
    public function setAutoincrementIndex($shadowTable, $autoincrementIndex)
    {
        $sql = 'ALTER TABLE ' . $shadowTable . ' AUTO_INCREMENT=' . intval($autoincrementIndex);
        try {
            $this->executeQuery($sql);
            return true;
        } catch (RuntimeException $ex) {
            return false;
        }
    }

    /**
     * Get Autoincrement index from a particular table
     * @param string $tableName Table name
     * @return integer Autoincrement index
     */
    public function getAutoincrementIndex($tableName)
    {
        // Create a new query object
        $query = $this->getQuery(true);

        $query
                ->select($this->quoteName('AUTO_INCREMENT'))
                ->from('INFORMATION_SCHEMA.TABLES')
                ->where(
                        array(
                            'TABLE_SCHEMA = ' . $this->quote($this->getDatabase()),
                            'TABLE_NAME = ' . $this->quote($tableName)
                        )
        );

        $this->executeQuery($query);

        return intval($this->loadResult());
    }

    /**
     * Execute a sql preventing to lose the query previously assigned.
     * @param mixed $sql JDatabaseQuery object or SQL query
     * @param boolean $preservePreviousQuery True if the previous query will be saved before, false otherwise
     * @return void 
     */
    public function executeQuery($sql, $preservePreviousQuery = true)
    {

        // If the flag is activated, let's keep it save
        if ($preservePreviousQuery) {
            $currentSql = $this->sql;
        }

        $this->sql = $sql;
        $this->execute();


        // If the flag is activated, let's assign to the sql property again.
        if ($preservePreviousQuery) {
            $this->sql = $currentSql;
        }
    }

}
