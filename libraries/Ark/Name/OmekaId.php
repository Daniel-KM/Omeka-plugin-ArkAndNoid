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
                $main = $this->_addZero . $this->_convertIntegerToAlphabet($record->id);
                break;
            case 'Item':
                $main = $this->_convertIntegerToAlphabet($record->id);
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
        $length = $this->_getParameter('length');

        // The salt process builds a code of 256 bits.
        $salt = $this->_getParameter('salt');
        if ($salt) {
            if ($length > 32) {
                $this->_errorMessage = __('When a salt is set, the length should be empty or lower than 32.');
                return false;
            }
        }

        //préfix set : soit fixe soit lenght et pas dans l'alphabet

        $prefix = $this->_getParameter('prefix');
        if ($prefix) {
            if (empty($length)) {
                $alphabet = $this->_getAlphabet();
                $testAlphabet = str_replace(str_split($prefix), '', $alphabet);
                if ($testAlphabet != $alphabet) {
                    $this->_errorMessage = __('When a prefix is set, a length should be set or the prefix should not use the characters of the alphabet.');
                    return false;
                }
            }
        }

        $suffix = $this->_getParameter('suffix');
        if ($suffix) {
            if (empty($length)) {
                $alphabet = $this->_getAlphabet();
                $testAlphabet = str_replace(str_split($suffix), '', $alphabet);
                if ($testAlphabet != $alphabet) {
                    $this->_errorMessage = __('When a suffix is set, a length should be set or the suffix should not use the characters of the alphabet.');
                    return false;
                }
            }
        }

        if ($this->_addZero) {
            if ($length) {
                $this->_errorMessage = __('With the format Omeka Id, the length should be used when the prefix and the suffix are the same for collections and items.');
                return false;
            }
        }

        return true;
    }
}
