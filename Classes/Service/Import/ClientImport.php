<?php

namespace T3Monitor\T3monitoring\Service\Import;

/*
 * This file is part of the t3monitoring extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use T3Monitor\T3monitoring\Domain\Model\Extension;
use T3Monitor\T3monitoring\Service\DataIntegrity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class ClientImport extends BaseImport
{
    const TABLE = 'tx_t3monitoring_domain_model_client';

    /** @var array */
    protected $coreVersions = array();

    /** @var array */
    protected $responseCount = array('error' => 0, 'success' => 0);

    public function __construct()
    {
        $this->coreVersions = $this->getAllCoreVersions();
        parent::__construct();
    }

    /**
     * @param null|int $clientId
     */
    public function run($clientId = null)
    {
        $where = 'deleted=0 AND hidden=0';
        if (is_null($clientId)) {
            $clientId = (int)$clientId;
            if ($clientId > 0) {
                $where .= ' AND uid=' . $clientId;
            }
        }
        $clientRows = $this->getDatabaseConnection()->exec_SELECTgetRows('*', self::TABLE, $where);

        foreach ($clientRows as $client) {
            $this->importSingleClient($client);
        }

        /** @var DataIntegrity $dataIntegrity */
        $dataIntegrity = GeneralUtility::makeInstance(DataIntegrity::class);
        $dataIntegrity->invokeAfterClientImport();
        $this->setImportTime('client');
    }

    /**
     * @return array
     */
    public function getResponseCount()
    {
        return $this->responseCount;
    }

    protected function importSingleClient(array $row)
    {
        try {
            $response = $this->requestClientData($row);
            if (empty($response)) {
                throw new \RuntimeException('Empty response from client ' . $row['title']);
            }
            $json = json_decode($response, true);

            $update = array(
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'last_successful_import' => $GLOBALS['EXEC_TIME'],
                'error_message' => '',
                'php_version' => $json['core']['phpVersion'],
                'mysql_version' => $json['core']['mysqlClientVersion'],
                'core' => $this->getUsedCore($json['core']['typo3Version']),
                'extensions' => $this->handleExtensionRelations($row['uid'], $json['extensions']),
                'backend_users' => $this->handleBackendUserRelations($row['uid'], $json['users']['backend']),
            );

            $this->addExtraData($json, $update, 'info');
            $this->addExtraData($json, $update, 'warning');
            $this->addExtraData($json, $update, 'danger');

            $this->getDatabaseConnection()->exec_UPDATEquery('tx_t3monitoring_domain_model_client',
                'uid=' . (int)$row['uid'], $update);
            $this->responseCount['success']++;
        } catch (\Exception $e) {
            $this->handleError($row['uid'], $e);
        }
    }

    /**
     * Add extra information for info, warning, danger
     *
     * @param array $json
     * @param array $update
     * @param string $field
     */
    protected function addExtraData(array $json, array &$update, $field)
    {
        $dbField = 'extra_' . $field;
        if (isset($json['extra']) && isset($json['extra']) && is_array($json['extra'][$field])) {
            $update[$dbField] = json_encode($json['extra'][$field]);
        } else {
            $update[$dbField] = '';
        }
    }

    protected function handleError($client, \Exception $error)
    {
        $this->responseCount['error']++;
        $this->getDatabaseConnection()->exec_UPDATEquery(self::TABLE, 'uid=' . (int)$client, array(
            'error_message' => $error->getMessage()
        ));
    }

    protected function requestClientData(array $row)
    {
        $domain = $this->unifyDomain($row['domain']);
        $url = $domain . '/index.php?eID=t3monitoring&secret=' . rawurlencode($row['secret']);
        $report = [];
        $response = GeneralUtility::getUrl($url, 0, null, $report);
        if (!empty($report['message']) && $report['message'] !== 'OK') {
            throw new \RuntimeException($report['message']);
        }
        return $response;
    }

    /**
     * @param string $domain
     * @return string
     */
    protected function unifyDomain($domain)
    {
        $domain = rtrim($domain, '/');
        if (!StringUtility::beginsWith($domain, 'http://') && !StringUtility::beginsWith($domain, 'https://')) {
            $domain = 'http://' . $domain;
        }

        return $domain;
    }

    /**
     * @param int $client client uid
     * @param array $extensions list of extensions
     * @return int count of used extensions
     */
    protected function handleExtensionRelations($client, array $extensions = array())
    {
        $table = 'tx_t3monitoring_domain_model_extension';

        $whereClause = array();
        foreach ($extensions as $key => $data) {
            $whereClause[] = sprintf(
                '(version=%s AND name=%s)',
                $this->getDatabaseConnection()->fullQuoteStr($data['version'], $table),
                $this->getDatabaseConnection()->fullQuoteStr($key, $table)
            );
        }

        $existingExtensions = $this->getDatabaseConnection()->exec_SELECTgetRows('uid,version,name', $table,
            implode(' OR ', $whereClause));

        $relationsToBeAdded = array();
        foreach ($extensions as $key => $data) {
            // search if exists
            $found = null;
            foreach ($existingExtensions as $existingExtension) {
                if ($existingExtension['version'] === $data['version'] && $existingExtension['name'] === $key) {
                    $found = $existingExtension;
                    continue;
                }
            }

            if ($found) {
                $relationId = $found['uid'];
            } else {
                $insert = array(
                    'pid' => $this->emConfiguration->getPid(),
                    'name' => $key,
                    'version' => $data['version'],
                    'version_integer' => VersionNumberUtility::convertVersionNumberToInteger($data['version']),
                    'title' => (string)$data['title'],
                    'description' => (string)$data['description'],
                    'state' => array_search($data['state'], Extension::$defaultStates),
                    'is_official' => 0,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                );
                $this->getDatabaseConnection()->exec_INSERTquery('tx_t3monitoring_domain_model_extension', $insert);
                $relationId = $this->getDatabaseConnection()->sql_insert_id();
            }
            $fields = array('uid_local', 'uid_foreign', 'title', 'state', 'is_loaded');
            $relationsToBeAdded[] = array(
                $client,
                $relationId,
                $data['title'],
                array_search($data['state'], Extension::$defaultStates),
                $data['isLoaded'],
            );

            $mmTable = 'tx_t3monitoring_client_extension_mm';
            $this->getDatabaseConnection()->exec_DELETEquery($mmTable, 'uid_local=' . (int)$client);
            $this->getDatabaseConnection()->exec_INSERTmultipleRows($mmTable, $fields, $relationsToBeAdded);
        }

        return count($extensions);
    }

    /**
     * @param int $client client uid
     * @param array $users list of extensions
     * @return int count of used extensions
     */
    protected function handleBackendUserRelations($client, array $users = array())
    {
        $table = 'tx_t3monitoring_domain_model_backend_user';
        $mmTable = 'tx_t3monitoring_client_backend_user_mm';

        $existingUsers = $this->getDatabaseConnection()->exec_SELECTgetRows('A.*', $table.' A LEFT OUTER JOIN '.$mmTable.' B ON A.uid=B.uid_foreign', 'B.uid_local='.(int)$client);

        foreach($users as $user)
        {
            $found = null;

            foreach($existingUsers as $existingUser)
            {
                if($existingUser['user_name'] == $user['userName'])
                {
                    $found = $existingUser;
                    break;
                }
            }

            if($found)
            {
                $relationId = $found['uid'];
                $update = array(
                    'real_name' => (string)$user['realName'],
                    'email_address' => (string)$user['emailAddress'],
                    'description' => (string)$user['description'],
                    'last_login' => (string)$user['lastLogin'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                );
                $this->getDatabaseConnection()->exec_UPDATEquery($table, 'uid='.(int)$relationId, $update);
            } else {
                $insert = array(
                    'pid' => $this->emConfiguration->getPid(),
                    'user_name' => (string)$user['userName'],
                    'real_name' => (string)$user['realName'],
                    'email_address' => (string)$user['emailAddress'],
                    'description' => (string)$user['description'],
                    'last_login' => (string)$user['lastLogin'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                );
                $this->getDatabaseConnection()->exec_INSERTquery($table, $insert);
                $relationId = $this->getDatabaseConnection()->sql_insert_id();
            }
            $fields = array('uid_local', 'uid_foreign');
            $relationsToBeAdded[] = array(
                $client,
                $relationId
            );

            $this->getDatabaseConnection()->exec_DELETEquery($mmTable, 'uid_local=' . (int)$client);
            $this->getDatabaseConnection()->exec_INSERTmultipleRows($mmTable, $fields, $relationsToBeAdded);
        }

        return count($users);
    }

    protected function getUsedCore($version)
    {
        if (isset($this->coreVersions[$version])) {
            return $this->coreVersions[$version]['uid'];
        } else {
            // insert new core
            $insert = array(
                'pid' => $this->emConfiguration->getPid(),
                'is_official' => 0,
                'version' => $version,
                'version_integer' => VersionNumberUtility::convertVersionNumberToInteger($version),
                'insecure' => 1 // @todo to be discussed
            );
            $this->getDatabaseConnection()->exec_INSERTquery('tx_t3monitoring_domain_model_core', $insert);
            $newId = $this->getDatabaseConnection()->sql_insert_id();
            $this->coreVersions[$version] = array('uid' => $newId, 'version' => $version);

            return $newId;
        }
    }

    protected function getAllCoreVersions()
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,version',
            'tx_t3monitoring_domain_model_core',
            '1=1',
            '',
            '',
            '',
            'version');
    }

}
