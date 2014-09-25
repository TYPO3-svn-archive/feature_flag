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

if (!defined('TYPO3_cliMode')) {
    die ('Access denied: CLI only.');
}

/**
 * @package FeatureFlag
 * @subpackage System_Typo3
 * @author Kevin Schu <kevin.schu@aoemedia.de>
 * @author Matthias Gutjahr <matthias.gutjahr@aoemedia.de>
 */
class Tx_FeatureFlag_System_Typo3_Cli extends t3lib_cli
{
    /**
     * @var string
     */
    protected $extKey = 'feature_flag';
    /**
     * @var string
     */
    protected $prefixId = 'tx_featureflag_system_typo3_cli';
    /**
     * @var string
     */
    protected $scriptRelPath = 'Classes/System/Typo3/Cli.php';

    /**
     * constructor
     */
    public function __construct()
    {
        parent::t3lib_cli();
        $this->cli_options = array_merge($this->cli_options, array());
        $this->cli_help = array_merge($this->cli_help, array(
            'name' => $this->prefixId,
            'synopsis' => $this->extKey . ' command',
            'description' => 'This script can flag all configured tables by feature flags.',
            'examples' => 'typo3/cli_dispatch.phpsh ' . $this->extKey . ' [flagEntries]',
            'author' => '(c) 2013 AOE media GmbH <dev@aoemedia.de>',
        ));
        $this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
    }

    /**
     * @param $argv
     * @return int
     */
    public function cli_main($argv)
    {
        $this->init();
        try {
            switch ($this->getAction()) {
                case 'flagEntries':
                    $this->flagEntries();
                    break;
                default:
                    $this->cli_help();
                    break;
            }
        } catch (Exception $e) {
            return 1;
        }
        return 0;
    }

    /**
     * @return string
     */
    private function getAction()
    {
        return (string)$this->cli_args['_DEFAULT'][1];
    }

    /**
     * @return void
     */
    private function init()
    {
        $this->cli_validateArgs();
    }

    /**
     * include scheduler-cli-script (so the scheduler-cli will be processed)
     * @return void
     */
    private function processScheduler()
    {
        $MCONF['name'] = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['scheduler'][1];
        include(t3lib_div::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['scheduler'][0]));
    }

    /**
     * check if scheduler is installed
     *
     * @return boolean
     */
    private function schedulerIsInstalled()
    {
        return is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['scheduler']);
    }

    /**
     */
    private function flagEntries()
    {
        if ($this->schedulerIsInstalled()) {
            // set tasks (which updates the caches) to 1 (than the tasks will be executed the next time)
            $sqlFrom = 'tx_scheduler_task';
            $sqlWhere = 'classname = "Tx_FeatureFlag_System_Typo3_Task_FlagEntries"';
            $sqlValues = array('nextexecution' => 1, 'disable' => 0);
            echo $GLOBALS['TYPO3_DB']->exec_UPDATEquery($sqlFrom, $sqlWhere, $sqlValues);
            $this->processScheduler();
        }
    }
}

/** @var Tx_FeatureFlag_System_Typo3_Cli $cli */
$cli = t3lib_div::makeInstance('Tx_FeatureFlag_System_Typo3_Cli');
exit($cli->cli_main($_SERVER['argv']));