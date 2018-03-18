<?php
/**
 * Abstract class on which all other formats for qualifier are based.
 *
 * @package Ark
 */
abstract class Ark_Name_Abstract
{
    /**
     * @var array
     */
    protected $_parameters;

    /**
     * @var Omeka_Record_AbstractRecord
     */
    protected $_record;

    /**
     * This option specifies if the processor return a full ark, with naan,
     * prefix, name, suffix and control key, and without the "ark:/".
     *
     * @var boolean
     */
    protected $_isFullArk = false;

    /**
     * When a salt and a length are set, the result may be cut and the process
     * may need to be relaunched.
     *
     * @var integer
     */
    protected $_maxSaltLoop = 4000;

    /**
     * @var string
     */
    protected $_errorMessage = '';

    public function __construct($parameters = array())
    {
        $this->_parameters = $parameters;

        if (!$this->_checkParameters()) {
            $message = __('Parameters are not correct for the selected format "%s".', get_class($this));
            if (!empty($this->_errorMessage)) {
                $message .= PHP_EOL . $this->_errorMessage;
            }
            throw new Ark_ArkException($message);
        }
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
     * Check parameters.
     *
     * @return boolean
     */
    protected function _checkParameters()
    {
        // These functions may be scripted.
        if (!function_exists('bcadd')
                || !function_exists('bcmul')
                || !function_exists('bcdiv')
                || !function_exists('bcmod')
                || !function_exists('bcpow')
            ) {
            $this->_errorMessage .= __('The extension bc-math is not enabled, but required by the format "%s".', get_class($this));
            return false;
        }
        return true;
    }

    /**
     * Create the ark for a record.
     *
     * @param Omeka_Record_AbstractRecord $record The record for which to create the ark.
     * @return string|null The ark, else null if error (no institution).
     */
    final public function create($record)
    {
        $this->_record = $record;

        if (empty($record)) {
            return;
        }

        $naan = $this->_getParameter('naan');

        $ark = $this->_create();

        // Check the result.
        if (empty($ark)) {
            $message = __('No Ark created: check your format "%s" [%s #%d].',
                get_class($this), get_class($this->_record), $this->_record->id);
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            return;
        }

        // Complete partial ark.
        $mainPart = $ark;
        if (!$this->_isFullArk) {
            $ark = $this->_prepareFullArk($ark);
        }
        // Check ark (useful only for external process).
        elseif (!$this->_checkFullArk($ark)) {
            $message = __('Ark "%s" is not correct: check your format "%s" and your processor [%s #%d].',
                $ark, get_class($this), get_class($this->_record), $this->_record->id);
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            return;
        }

        // Add the protocol.
        $protocol = $this->_getParameter('protocol');
        $ark = $protocol . '/' . $ark;

        // Check if the ark is single.
        if ($this->_arkExists($ark)) {
            if ($this->_isFullArk) {
                $message = __('The proposed ark "%s" is not unique [%s #%d].',
                    $ark, get_class($this->_record), $this->_record->id);
                _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
                return;
            }

            return $this->_processDuplicate($ark, $mainPart);
        }

        return $ark;
    }

    /**
     * The true function used to create the name part of the record.
     */
    abstract protected function _create();

    /**
     * Try to create another ark in case of a duplicate.
     *
     * @param string $ark The created ark.
     * @param string $mainPart The created main part of the ark.
     * @return string|null Another ark if possible.
     */
    protected function _processDuplicate($ark, $mainPart)
    {
        $message = __('Unable to create a unique ark.')
            . ' ' . __('Check parameters of the format "%s" [%s #%d].',
                get_class($this), get_class($this->_record), $this->_record->id);
        _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
        return;
    }

    /**
     * Helper to prepare an ark (without the protocol) from a partial ark.
     *
     * @param string $ark A partial ark.
     * @return string An ark.
     */
    protected function _prepareFullArk($mainPart)
    {
        $naan = $this->_getParameter('naan');
        $ark = ($naan ? $naan . '/' : '')
            . $this->_getParameter('prefix')
            . $mainPart
            . $this->_getParameter('suffix');

        // The control key is computed against the naan + ark.
        if ($this->_getParameter('control_key')) {
            $ark .= $this->_controlKey($ark);
        }

        return $ark;
    }

    /**
     * Check if a full ark is an ark.
     *
     * @param string $ark
     * @return boolean
     */
    protected function _checkFullArk($ark)
    {
        $ark = trim($ark);
        $result = explode('/', $ark);

        $naan = $this->_getParameter('naan');

        if ($naan) {
            if (count($result) != 2) {
                return false;
            }
            if ($result[0] != $naan) {
                return false;
            }

            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $result[1]);
            return $clean == $result[1];
        }

        // Else no naan.
        if (strpos($ark, '/') !== false) {
            return false;
        }

        $clean = preg_replace('/[^a-zA-Z0-9]/', '', $ark);
        return $clean == $ark;
    }

    /**
     * Check if an ark exists in the base.
     *
     * @param string $ark The full well formed ark, with "ark:/".
     * @return boolean
     */
    protected function _arkExists($ark)
    {
        return (boolean) get_view()->getRecordFromArk($ark, 'id');
    }

    /**
     * Return the control key of a string.
     *
     * This works like the checkchar() function of the noid program, but the
     * alphabet is the same than the one used for identifier to avoid empty
     * control key.
     *
     * @param string $string
     * @return string The control key.
     */
    protected function _controlKey($string)
    {
        $alphabet = $this->_getAlphabet();
        $total = 0;
        // Make a sum of each character of the string.
        foreach (str_split($string) as $key => $character) {
            $total += ($key + 1) * strpos($alphabet, $character);
        }
        // Get the alphabet letter for the modulus of the total.
        $result = $alphabet[$total % strlen($alphabet)];
        return $result;
    }

    /**
     * Check and pad a string to the configured length.
     *
     * @param string $string
     * @return string "0" or empty string.
     */
    protected function _pad($string)
    {
        $length = $this->_getParameter('length');
        if ($length) {
            // Check if the string is longer to warn it in the log.
            if (strlen($string) > $length) {
                $message = __('The Ark format "%s" requires a static length of %d characters, but the current ark is %d characters long [%s #%d].',
                    get_class($this), $length, strlen($string), get_class($this->_record), $this->_record->id);
                _log('[Ark&Noid] ' . $message, Zend_Log::WARN);
                return $string;
            }
            $pad = $this->_getParameter('pad') ?: substr($this->_getAlphabet(), 0, 1);
            $string = str_pad($string, $length, $pad, STR_PAD_LEFT);
        }

        return $string;
    }

    /**
     * Hash a string with a salt with main parameters (length and alphabet).
     *
     * @param string $string
     * @return string The original string if there is no salt, else the salted
     * formatted string.
     */
    protected function _salt($string)
    {
        $salt = $this->_getParameter('salt');
        if (strlen($salt) == 0) {
            return $string;
        }
        $salted = hash('sha256', $string . $salt);

        // Convert the salt into the alphabet.
        $salted = $this->_convBase($salted, '0123456789abcdef', $this->_getAlphabet());

        // Pad the result. Don't use the function _pad() to change the warn.
        $length = $this->_getParameter('length');
        if (strlen($salted) < $length) {
            // This can occurs too when the conversion creates a small number.
            $message = __('The Ark format "%s" requires a static length of %d characters, but the hash creates a %d characters long [%s #%d].',
                get_class($this), $length, strlen($salted), get_class($this->_record), $this->_record->id);
            _log('[Ark&Noid] ' . $message, Zend_Log::WARN);

            $pad = $this->_getParameter('pad') ?: substr($this->_getAlphabet(), 0, 1);
            $salted = str_pad($salted, $length, $pad, STR_PAD_LEFT);
        }
        // No pad, so the hash is long enough.
        // With salt, the result may be cut, as recommended.
        elseif ($length) {
            $salted = substr($salted, 0, $length);
        }

        return $salted;
    }

    /**
     * Convert an integer to the alphabet base.
     *
     * @param integer $number Input number to convert as a string.
     * @return string
     */
    protected function _convertIntegerToAlphabet($number)
    {
        return $this->_convBase($number, '0123456789', $this->_getAlphabet());
    }

    /**
     * Convert an arbitrarily large number from any base to any base.
     *
     * @link https://php.net/manual/en/function.base-convert.php#106546
     *
     * @param integer $number Input number to convert as a string.
     * @param string $fromBaseInput Base of the number to convert as a string.
     * @param string $toBaseInput Base the number should be converted to as a
     * string.
     * @return string
     */
    protected function _convBase($numberInput, $fromBaseInput, $toBaseInput)
    {
        if ($fromBaseInput == $toBaseInput) {
            return $numberInput;
        }

        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';

        if ($toBaseInput == '0123456789') {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = bcadd($retval, bcmul(array_search($number[$i - 1], $fromBase), bcpow($fromLen, $numberLen - $i)));
            }
            return $retval;
        }

        if ($fromBaseInput != '0123456789') {
            $base10 = $this->_convBase($numberInput, $fromBaseInput, '0123456789');
        }
        else {
            $base10 = $numberInput;
        }

        if ($base10 < strlen($toBaseInput)) {
            return $toBase[$base10];
        }

        while ($base10 != '0') {
            $retval = $toBase[bcmod($base10, $toLen)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }

        return $retval;
    }

    /**
     * Returns the alphabet used by some formats.
     *
     * @param string $name
     * @return string
     */
    protected function _getAlphabet($name = null)
    {
        if (is_null($name)) {
            $name = $this->_getParameter('alphabet');
        }

        switch ($name) {
            case 'numeric':
                return '0123456789';
            case 'hexadecimal':
                return '0123456789abcdef';
            case 'alphabetic':
                return 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            case 'lower_case_alphabetic':
                return 'abcdefghijklmnopqrstuvwxyz';
            case 'upper_case_alphabetic':
                return 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            case 'lower_case_alphabetic_no_vowel':
                return 'bcdfghjklmnpqrstvwxz';
            case 'upper_case_alphabetic_no_vowel':
                return 'BCDFGHJKLMNPQRSTVWXZ';
            case 'alphanumeric':
                return '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            case 'lowercase_alphanumeric':
                return '0123456789abcdefghijklmnopqrstuvwxyz';
            case 'uppercase_alphanumeric':
                return '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            case 'alphanumeric_no_vowel':
                return '0123456789bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ';
            case 'alphanumeric_no_vowel_no_l':
                return '0123456789bcdfghjkmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ';
            case 'lowercase_alphanumeric_no_vowel':
                return '0123456789bcdfghjklmnpqrstvwxz';
            case 'lowercase_alphanumeric_no_vowel_no_l':
                return '0123456789bcdfghjkmnpqrstvwxz';
            case 'uppercase_alphanumeric_no_vowel':
                return '0123456789BCDFGHJKLMNPQRSTVWXZ';
        }
    }

    /**
     * Normalize the name of the current user.
     */
    protected function _getContact()
    {
        $user = current_user();
        return empty($user)
            ? __('Unknown user')
            : $user->name . ' <' . $user->email . '>';
    }

    /**
     * Execute a external command and return results by reference.
     *
     * @see Omeka_File_Derivative_Strategy_ExternalImageMagick().
     * @param string $cmd A valid and checked command.
     * @return void
     */
    protected function _executeCommand($cmd, &$status, &$output, &$errors)
    {
        // Using proc_open() instead of exec() solves a problem where exec('convert')
        // fails with a "Permission Denied" error because the current working
        // directory cannot be set properly via exec().  Note that exec() works
        // fine when executing in the web environment but fails in CLI.
        $descriptorSpec = array(
            0 => array("pipe", "r"), //STDIN
            1 => array("pipe", "w"), //STDOUT
            2 => array("pipe", "w"), //STDERR
        );
        $proc = proc_open($cmd, $descriptorSpec, $pipes, getcwd());
        if ($proc) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            $status = proc_close($proc);
        } else {
            throw new Ark_ArkException(__('Failed to execute command: %s.', $cmd));
        }
    }
}
