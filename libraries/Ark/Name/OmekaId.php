<?php
/**
 * Omeka Id format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_OmekaId extends Ark_Name_Abstract
{
    protected function _create($record)
    {
        switch (get_class($record)) {
            case 'Collection':
                return $this->_addZeroForCollection() . $record->id;
            case 'Item':
                return (string) $record->id;
        }
    }
}
