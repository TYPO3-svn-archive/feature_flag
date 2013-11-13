<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 AOE GmbH <dev@aoemedia.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @package FeatureFlag
 * @subpackage Domain_Repository
 * @author Kevin Schu <kevin.schu@aoemedia.de>
 * @author Matthias Gutjahr <matthias.gutjahr@aoemedia.de>
 */
class Tx_FeatureFlag_Domain_Repository_FeatureFlag extends Tx_Extbase_Persistence_Repository
{
    /**
     * @var Tx_FeatureFlag_System_Db_SqlFactory
     */
    private $sqlFactory;

    /**
     * @param Tx_FeatureFlag_System_Db_SqlFactory $sqlFactory
     */
    public function injectSqlFactory(Tx_FeatureFlag_System_Db_SqlFactory $sqlFactory)
    {
        $this->sqlFactory = $sqlFactory;
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        /** @var $defaultQuerySettings Tx_Extbase_Persistence_Typo3QuerySettings */
        $defaultQuerySettings = $this->objectManager->get('Tx_Extbase_Persistence_Typo3QuerySettings');
        $defaultQuerySettings->setRespectStoragePage(FALSE);
        $defaultQuerySettings->setRespectSysLanguage(FALSE);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * @param string $flag
     * @return object
     */
    public function findByFlag($flag)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(FALSE);
        $query->getQuerySettings()->setRespectStoragePage(FALSE);
        $query->matching($query->equals('flag', $flag));
        return $query->execute()->getFirst();
    }

    /**
     * @param string $table
     */
    public function updateFeatureFlagStatusForTable($table)
    {
        $this->hideEntries($table, $this->getUpdateEntriesUids($table, 'tx_featureflag_hide', 1));
        $this->unHideEntries($table, $this->getUpdateEntriesUids($table, 'tx_featureflag_hide'));
        $this->hideEntries($table, $this->getUpdateEntriesUids($table, 'tx_featureflag_show'));
        $this->unHideEntries($table, $this->getUpdateEntriesUids($table, 'tx_featureflag_show', 1));
    }

    /**
     * @param string $table
     * @param array $uids
     * @return array|Tx_Extbase_Persistence_QueryResultInterface
     */
    private function hideEntries($table, array $uids)
    {
        return $this->updateEntries($table, $uids, 1);
    }

    /**
     * @param string $table
     * @param array $uids
     * @return array|Tx_Extbase_Persistence_QueryResultInterface
     */
    private function unHideEntries($table, array $uids)
    {
        return $this->updateEntries($table, $uids, 0);
    }

    /**
     * @param $table
     * @param array $uids
     * @param $hidden
     * @return array|Tx_Extbase_Persistence_QueryResultInterface
     */
    private function updateEntries($table, array $uids, $hidden)
    {
        if (empty($uids)) {
            return array();
        }
        /** @var Tx_Extbase_Persistence_Query $query */
        $query = $this->configureQuery();
        $statement = $this->sqlFactory->getUpdateStatementForContentElements($table);
        $query->statement($statement, array($hidden, $uids));
        return $query->execute();
    }

    /**
     * @param string $table
     * @param string $column
     * @param int $enabled
     * @return array
     */
    private function getUpdateEntriesUids($table, $column, $enabled = 0)
    {
        $query = $this->configureQuery();
        $statement = $this->sqlFactory->getSelectStatementForContentElements($table, $column, $enabled);
        $query->statement($statement);
        $uids = array();
        $rows = $query->execute();
        foreach ($rows as $row) {
            $uids[] = $row['uid'];
        }
        return $uids;
    }

    /**
     * @return Tx_Extbase_Persistence_Query
     */
    private function configureQuery()
    {
        /** @var Tx_Extbase_Persistence_Query $query */
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectEnableFields(FALSE);
        $query->getQuerySettings()->setReturnRawQueryResult(TRUE);
        return $query;
    }
}