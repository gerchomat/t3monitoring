<?php

namespace T3Monitor\T3monitoring\Domain\Model\Dto;

/*
 * This file is part of the t3monitoring extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ClientFilterDemand extends AbstractEntity
{

    /** @var string */
    protected $version;

    /** @var int */
    protected $sla;

    /** @var string */
    protected $searchWord;

    /** @var bool */
    protected $withErrorMessage;

    /** @var bool */
    protected $withInsecureExtensions;

    /** @var bool */
    protected $withInsecureCore;

    /** @var bool */
    protected $withOutdatedCore;

    /** @var bool */
    protected $withOutdatedExtensions;

    /** @var bool */
    protected $withExtraInfo;

    /** @var bool */
    protected $withExtraWarning;

    /** @var bool */
    protected $withExtraDanger;

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return int
     */
    public function getSla()
    {
        return $this->sla;
    }

    /**
     * @param int $sla
     * @return $this
     */
    public function setSla($sla)
    {
        $this->sla = $sla;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearchWord()
    {
        return $this->searchWord;
    }

    /**
     * @param string $searchWord
     * @return $this
     */
    public function setSearchWord($searchWord)
    {
        $this->searchWord = $searchWord;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithErrorMessage()
    {
        return $this->withErrorMessage;
    }

    /**
     * @param boolean $withErrorMessage
     * @return $this
     */
    public function setWithErrorMessage($withErrorMessage)
    {
        $this->withErrorMessage = $withErrorMessage;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithInsecureExtensions()
    {
        return $this->withInsecureExtensions;
    }

    /**
     * @param boolean $withInsecureExtensions
     * @return $this
     */
    public function setWithInsecureExtensions($withInsecureExtensions)
    {
        $this->withInsecureExtensions = $withInsecureExtensions;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithInsecureCore()
    {
        return $this->withInsecureCore;
    }

    /**
     * @param boolean $withInsecureCore
     * @return $this
     */
    public function setWithInsecureCore($withInsecureCore)
    {
        $this->withInsecureCore = $withInsecureCore;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithOutdatedCore()
    {
        return $this->withOutdatedCore;
    }

    /**
     * @param boolean $withOutdatedCore
     * @return $this
     */
    public function setWithOutdatedCore($withOutdatedCore)
    {
        $this->withOutdatedCore = $withOutdatedCore;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithOutdatedExtensions()
    {
        return $this->withOutdatedExtensions;
    }

    /**
     * @param boolean $withOutdatedExtensions
     * @return $this
     */
    public function setWithOutdatedExtensions($withOutdatedExtensions)
    {
        $this->withOutdatedExtensions = $withOutdatedExtensions;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithExtraInfo()
    {
        return $this->withExtraInfo;
    }

    /**
     * @param boolean $withExtraInfo
     * @return $this
     */
    public function setWithExtraInfo($withExtraInfo)
    {
        $this->withExtraInfo = $withExtraInfo;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithExtraWarning()
    {
        return $this->withExtraWarning;
    }

    /**
     * @param boolean $withExtraWarning
     * @return $this
     */
    public function setWithExtraWarning($withExtraWarning)
    {
        $this->withExtraWarning = $withExtraWarning;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isWithExtraDanger()
    {
        return $this->withExtraDanger;
    }

    /**
     * @param boolean $withExtraDanger
     * @return $this
     */
    public function setWithExtraDanger($withExtraDanger)
    {
        $this->withExtraDanger = $withExtraDanger;
        return $this;
    }

}