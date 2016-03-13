<?php
/**
 * External command format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_Command extends Ark_Name_Abstract
{
    protected $_isFullArk = true;

    /**
     * Check parameters.
     *
     * @return boolean
     */
    protected function _checkParameters()
    {
        return true;
    }

    protected function _create()
    {
        // Record and parameters are not used. Extend the class if needed.
        return $this->_command();
    }

    protected function _command()
    {
        $command = $this->_getParameter('command');
        $status = null;
        $output = null;
        $errors = null;

        $this->_executeCommand($command, $status, $output, $errors);

        if (!empty($errors)) {
            _log('[Ark&Noid] ' . __('Error output from ark command: %s', $errors), Zend_Log::WARN);
        }

        if ($status) {
            _log('[Ark&Noid] ' . __('Ark command failed with status code %s.', $status), Zend_Log::ERR);
            return;
        }

        return $output;
    }
}
