<?php
/**
 * Abstract class on which all other formats for qualifier are based.
 *
 * @package Ark
 */
abstract class Ark_Qualifier_Abstract
{
    protected $_parameters;

    public function __construct($parameters = array())
    {
        $this->_parameters = $parameters;
    }

    /**
     * Get parameter by name.
     *
     * @return mixed Value, if any, else null.
     */
    protected function _getParameter($name)
    {
        return isset($this->_parameters[$name]) ? $this->_parameters[$name] : null;
    }

    /**
     * Create the ark qualifier for a record.
     *
     * @param AbstractRecord $record The record for which to create qualifier.
     * @return string|null The qualifier, else null if error (no file).
     */
    final public function create($record)
    {
        if (empty($record)) {
            return;
        }

        $qualifier = $this->_create($record);
        if ($qualifier) {
            return $qualifier;
        }
    }

    /**
     * The true function used to create the name part of the record.
     */
    protected function _create($record)
    {
    }

    /**
     * Get a sub-record from an qualifier.
     *
     * @param AbstractRecord $record The main record (item).
     * @param string $qualifier
     * @return AbstractRecord|null The record (file), if any.
     */
    final public function getRecordFromQualifier($record, $qualifier)
    {
        if (empty($record) || empty($qualifier)) {
            return;
        }

        return $this->_getRecordFromQualifier($record, $qualifier);
    }

    /**
     * The true function used to get the record from a qualifier.
     */
    protected function _getRecordFromQualifier($record, $qualifier)
    {
    }
}
