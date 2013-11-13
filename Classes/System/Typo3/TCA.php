<?php

/**
 * Class Tx_FeatureFlag_System_Typo3_TCA
 */
class Tx_FeatureFlag_System_Typo3_TCA
{
    /**
     * @var string
     */
    const FIELD_HIDE = 'tx_featureflag_hide';

    /**
     * @var string
     */
    const FIELD_SHOW = 'tx_featureflag_show';

    /**
     * @var Tx_FeatureFlag_Domain_Repository_FeatureFlag
     */
    protected $featureFlagRepository;

    /**
     * @var Tx_Extbase_Object_ObjectManager
     */
    protected $objectManager;

    /**
     * @var Tx_Extbase_Persistence_Manager
     */
    protected $persistenceManager;

    /**
     * @param array $PA
     * @param t3lib_TCEforms $fob
     * @return string
     */
    public function renderSelect(array $PA, t3lib_TCEforms $fob)
    {
        $activeMapping = $this->getMappingRepository()->findByForeignTableNameUidAndColumnName($PA['row']['uid'], $PA['table'], $PA['field']);
        $html = '';
        $html .= "<select id=\"{$PA['itemFormElID']}\" name=\"{$PA['itemFormElName']}\">";
        $html .= "<option value=\"0\"></option>";
        foreach ($this->getFeatureFlagRepository()->findAll() as $featureFlag) {
            /** @var Tx_FeatureFlag_Domain_Model_FeatureFlag $featureFlag */
            $selected = '';
            if ($activeMapping instanceof Tx_FeatureFlag_Domain_Model_Mapping && $activeMapping->getFeatureFlag()->getUid() === $featureFlag->getUid()) {
                $selected = ' selected';
            }
            $value = $featureFlag->getUid();
            $label = $featureFlag->getDescription();
            $html .= "<option value=\"$value\"$selected>$label</option>";
        }
        $html .= "</select>";
        return $html;
    }

    /**
     * Hook for updates in Typo3 backend
     * @param array $incomingFieldArray
     * @param string $table
     * @param integer $id
     * @param t3lib_tcemain $tcemain
     */
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, t3lib_TCEmain &$tcemain)
    {
        $this->updateMapping($table, $id, self::FIELD_HIDE, $incomingFieldArray[self::FIELD_HIDE]);
        $this->updateMapping($table, $id, self::FIELD_SHOW, $incomingFieldArray[self::FIELD_SHOW]);
        unset ($incomingFieldArray [self::FIELD_HIDE]);
        unset ($incomingFieldArray [self::FIELD_SHOW]);
    }

    /**
     * Hook for deletes in Typo3 Backend. It also delete all overwrite protection
     * @param string $command
     * @param string $table
     * @param integer $id
     */
    public function processCmdmap_postProcess($command, $table, $id)
    {
        if ($command !== 'delete') {
            return;
        }
        $mappings = $this->getMappingRepository()->findByForeignTableNameAndUid($id, $table);
        foreach ($mappings as $mapping) {
            if ($mapping instanceof Tx_FeatureFlag_Domain_Model_Mapping) {
                $this->getMappingRepository()->remove($mapping);
            }
        }
        $this->getPersistenceManager()->persistAll();
    }

    /**
     * @param string $table
     * @param int $id
     * @param string $field
     * @param int $featureFlag
     */
    protected function updateMapping($table, $id, $field, $featureFlag)
    {
        $mapping = $this->getMappingRepository()->findByForeignTableNameUidAndColumnName(
            $id,
            $table,
            $field
        );
        if ($mapping instanceof Tx_FeatureFlag_Domain_Model_Mapping) {
            if (0 == $featureFlag) {
                $this->getMappingRepository()->remove($mapping);
            } else {
                $mapping->setFeatureFlag($this->getFeatureFlagByUid($featureFlag));
            }
            $mapping->setTstamp(time());
            $this->getMappingRepository()->update($mapping);
        } elseif (0 != $featureFlag) {
            /** @var Tx_FeatureFlag_Domain_Model_Mapping $mapping */
            $mapping = $this->getObjectManager()->get('Tx_FeatureFlag_Domain_Model_Mapping');
            $mapping->setFeatureFlag($this->getFeatureFlagByUid($featureFlag));
            $mapping->setForeignTableName($table);
            $mapping->setForeignTableUid($id);
            $mapping->setForeignTableColumn($field);
            $mapping->setCrdate(time());
            $mapping->setTstamp(time());
            $this->getMappingRepository()->add($mapping);
        }
        $this->getPersistenceManager()->persistAll();
    }

    /**
     * @param int $uid
     * @return Tx_FeatureFlag_Domain_Model_FeatureFlag
     * @throws Tx_FeatureFlag_Service_Exception_FeatureNotFound
     */
    protected function getFeatureFlagByUid($uid)
    {
        /** @var Tx_FeatureFlag_Domain_Model_FeatureFlag $featureFlag */
        $featureFlag = $this->getFeatureFlagRepository()->findByUid($uid);
        if (false === ($featureFlag instanceof Tx_FeatureFlag_Domain_Model_FeatureFlag)) {
            throw new Tx_FeatureFlag_Service_Exception_FeatureNotFound('Feature Flag not found by uid: "' . $uid . '"', 1384340431);
        }
        return $featureFlag;
    }

    /**
     * @return Tx_FeatureFlag_Domain_Repository_Mapping
     */
    protected function getMappingRepository()
    {
        return $this->getObjectManager()->get('Tx_FeatureFlag_Domain_Repository_Mapping');
    }

    /**
     * @return Tx_FeatureFlag_Domain_Repository_FeatureFlag
     */
    protected function getFeatureFlagRepository()
    {
        return $this->getObjectManager()->get('Tx_FeatureFlag_Domain_Repository_FeatureFlag');
    }

    /**
     * @return Tx_Extbase_Object_ObjectManager
     */
    protected function getObjectManager()
    {
        if (FALSE === ($this->objectManager instanceof Tx_Extbase_Object_ObjectManager)) {
            $this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
        }
        return $this->objectManager;
    }

    /**
     * @return Tx_Extbase_Persistence_Manager
     */
    protected function getPersistenceManager()
    {
        if (FALSE === ($this->persistenceManager instanceof Tx_Extbase_Persistence_Manager)) {
            $this->persistenceManager = $this->getObjectManager()->get('Tx_Extbase_Persistence_Manager');
        }
        return $this->persistenceManager;
    }
}
