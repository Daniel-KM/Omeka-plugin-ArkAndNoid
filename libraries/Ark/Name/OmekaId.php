<?php
/**
 * Omeka Id format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_OmekaId extends Ark_Name_Abstract
{
    public function __construct($parameters = array())
    {
        $this->_addZeroForCollection();

        parent::__construct($parameters);
    }

    protected function _create()
    {
        $record = &$this->_record;

        switch (get_class($record)) {
            case 'Collection':
                $main = $this->_addZero . $record->id;
                break;
            case 'Item':
                $main = (string) $record->id;
                break;
        }

        $salted = $this->_salt($main);
        // Check if string is salted: the salt process uses length and alphabet.
        $result = $salted == $main
            ? $this->_pad($main)
            : $salted;

        return $result;
    }

    /**
     * Check parameters.
     *
     * @return boolean
     */
    protected function _checkParameters()
    {
        if ($this->_addZero) {
            $length = $this->_getParameter('length');
            if ($length) {
                $this->_errorMessage = __('With the format "%s", the option "Length" cannot be used when the prefix and the suffix are the same for collections and items.', __('Omeka Id'));
                return false;
            }
        }

        return parent::_checkParameters();
    }
}
