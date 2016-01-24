<?php
/**
 * Abstract class on which all other formats for qualifier are based.
 *
 * @package Ark
 */
abstract class Ark_Name_Abstract
{
    protected $_parameters;

    /**
     * This option specifies if the processor return a full ark, with naan,
     * prefix, name, suffix and control key.
     *
     * @var boolean
     */
    protected $_isFullArk = false;

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
     * Create the ark for a record.
     *
     * @param AbstractRecord $record The record for which to create the ark.
     * @return string|null The ark, else null if error (no institution).
     */
    final public function create($record)
    {
        if (empty($record)) {
            return;
        }

        $naan = $this->_getParameter('naan');
        if (empty($naan)) {
            return;
        }

        // Create a full ark (in particular via noid).
        if ($this->_isFullArk) {
            $ark = $this->_create($record);
            if ($ark) {
                // TODO Check uniqueness?
                return 'ark:/' . $ark;
            }
        }
        // Name part ark.
        else {
            $ark = $naan . '/'
                . $this->_getParameter('prefix')
                . $this->_create($record)
                . $this->_getParameter('suffix');
            if ($ark) {
                // The control key is computed against the naan + ark.
                if ($this->_getParameter('control_key')) {
                    $ark .= $this->_controlKey($ark);
                }

                // TODO Check uniqueness?
                return 'ark:/' . $ark;
            }
        }
    }

    /**
     * The true function used to create the name part of the record.
     */
    abstract protected function _create($record);

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
     * Returns '0' if the prefix and suffix are same for collections and items
     * in order to avoid duplicate arks.
     *
     * @return string "0" or empty string.
     */
    protected function _addZeroForCollection()
    {
        return get_option('ark_prefix_collection') == get_option('ark_prefix_item')
            && get_option('ark_suffix_collection') == get_option('ark_suffix_item')
            ? '0'
            : '';
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
     */
    protected function _getAlphabet()
    {
        switch ($this->_getParameter('alphabet')) {
            case 'numeric':
                return '0123456789';
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
