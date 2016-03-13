<?php
/**
 * Omeka Id format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_OmekaId extends Ark_Name_Abstract
{
    protected function _create()
    {
        $record = &$this->_record;

        $main = $this->_convertIntegerToAlphabet($record->id);

        // To set a different ark for collections and items, a "0" may be
        // prepended before process when there is a salt, else after.
        $recordType = get_class($record);

        // Add the prepend before salting.
        $salt = $this->_getParameter('salt');
        if (strlen($salt) > 0) {
            // The record type is always added to simplify process.
            $main = $recordType . ' ' . $main;

            // The salt process uses length and alphabet, so no more padding.
            $result = $this->_salt($main);
        }

        // No salt.
        else {
            $result = $this->_pad($main);
            if ($recordType == 'Collection' && $this->_getParameter('identifix')) {
                $result = substr($this->_getAlphabet(), 0, 1) . $result;
            }
        }

        return $result;
    }

    /**
     * Try to create another ark in case of a duplicate.
     *
     * @param string $ark The created ark.
     * @param string $mainPart The created main part of the ark.
     * @return string|null Another ark if possible.
     */
    protected function _processDuplicate($ark, $mainPart)
    {
        $salt = $this->_getParameter('salt');
        // When there is no salt, il's not possible to create another ark.
        if (empty($salt)) {
            $message = __('No Ark created with the format "%s": the proposed ark "%s" is not unique [%s #%d].',
                get_class($this), $ark, get_class($this->_record), $this->_record->id);
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            return;
        }

        // Resalt the ark until it will become single.
        $protocol = $this->_getParameter('protocol');
        $i = 0;
        do {
            $mainPart = $this->_salt($mainPart);
            $ark = $protocol . '/' . $this->_prepareFullArk($mainPart);
        }
        while ($i++ < $this->_maxSaltLoop && $this->_arkExists($ark));
        if ($i >= $this->_maxSaltLoop) {
            $message = __('Unable to create a unique ark despite the salt.')
                . ' ' . __('Check parameters of the format "%s" [%s #%d].',
                    get_class($this), get_class($this->_record), $this->_record->id);
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            return;
        }

        return $ark;
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
            // TODO In fact, depends on the alphabet.
            if ($length > 32) {
                $this->_errorMessage = __('When a salt is set, the length should be empty or lower than 32.');
                return false;
            }
        }

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

        $identifix = $this->_getParameter('identifix');
        if (!$salt) {
            if ($identifix) {
                // This is just a warn: the parameters are fine because a prepend
                // is automatically added.
                $prepend = substr($this->_getAlphabet(), 0, 1);
                $this->_errorMessage = __('When the prefixes and the suffixes are the same for collections and items, without salt, a "%s" is prepended for collections.',
                    $prepend);
            }
            $this->_errorMessage .= PHP_EOL . __('Without salt, some checks need to be done manually: the prepended prefix of the collection should be different than the prefix of the item.');
        }

        /* This can't be done, because the options and the alphabet are not set.
        $salt = $this->_getParameter('salt');
        if (empty($salt)) {
            $prepend = substr($this->_getAlphabet(), 0, 1);
            if ((get_option('ark_id_prefix_collection') . $prepend) == get_option('ark_id_prefix_item')) {
                $this->_errorMessage = __('The prefix of the collection cannot be near the prefix of the item.');
            }
        }
        */

        return parent::_checkParameters();
    }
}
