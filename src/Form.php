<?php

namespace LongTailVentures;

class Form
{
    protected $_name, $_errors, $_values, $_isValid, $_validators, $_csrfValidator;


    /**
     * Form constructor.
     * @param null $name
     */
    public function __construct($name = null)
    {
        $this->_name = empty($name)
            ? get_class($this)
            : $name;

        $this->_errors = array();
        $this->_values = array();

        $this->_isValid = true;

        $this->_validators = array();
    }


    /**
     * @param string $elementName
     * @param \Zend\Validator\AbstractValidator $validator
     */
    public function addValidator($elementName, \Zend\Validator\AbstractValidator $validator)
    {
        if (!isset($this->_validators[$elementName]))
            $this->_validators[$elementName] = array();

        $this->_validators[$elementName][get_class($validator)] = $validator;
    }


    /**
     * @param string $elementName
     * @param $validatorName
     */
    public function removeValidator($elementName, $validatorName)
    {
        if (isset($this->_validators[$elementName][$validatorName]))
            unset($this->_validators[$elementName][$validatorName]);
    }


    /**
     * @param array $values
     * @return bool $isValid
     */
    public function isValid(array $values)
    {
        $this->_isValid = true;

        $this->setValues($values);
        $this->_errors = array();

        if (empty($values))
            return (bool)$this->_isValid;

        // check the csrf token first
        $csrfTokenName = $this->_name . '_CsrfToken';

        if (!isset($this->_values[$csrfTokenName]))
        {
        	$this->_errors['CsrfToken'] = 'Please submit the form again';
        	$this->_isValid = false;

        	return (bool)$this->_isValid;
        }

        $currentToken = isset($_SESSION['CsrfToken'][$this->_name])
        	? $_SESSION['CsrfToken'][$this->_name]
        	: '';
        
        $areTokensEqual = $this->_values[$csrfTokenName] === $currentToken;
        if (!$areTokensEqual)
        {
        	$this->_errors['CsrfToken'] = 'Please submit the form again';
        	$this->_isValid = false;
        	return (bool)$this->_isValid;
        }

        // then check the honeypot
        $honeypotName = $this->_name . '_Ident';
        if (!isset($this->_values[$honeypotName]) || $this->_values[$honeypotName] !== '')
        {
            $this->_errors['Honeypot'] = 'Please re-submit the form again';
            $this->_isValid = false;

            return (bool)$this->_isValid;
        }


        foreach ($this->_validators as $elementName => $validatorsForElement)
        {
            $value = $this->_values[$elementName];

            foreach ($validatorsForElement as $validatorName => $validator)
            {
                $isValid = $validator->isValid($value);

                if (!$isValid)
                {
                	$errorMessages = $validator->getMessages();
                    $this->_errors[$elementName] = current($errorMessages);
                }

                $this->_isValid &= (bool)$isValid;
            }
        }

        return (bool)$this->_isValid;
    }


    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }


    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->_name;
    }


    /**
     * @param string $elementName
     * @param string $value
     */
    public function setValue($elementName, $value)
    {
        $this->_values[$elementName] = $value;
    }


    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->_values = $values;
    }


    /**
     * @param string $elementName
     * @return mixed|string
     */
    public function getValue($elementName)
    {
        if (array_key_exists($elementName, $this->_values))
        {
            $value = $this->_values[$elementName];
            if (is_array($value))
                return $value;
            else if (is_null($value))
                return $value;
            else
                return trim($value);
        }
        else
            return '';

    }


    /**
     * @param string $elementName
     * @param string $error
     */
    public function setError($elementName, $error)
    {
        $this->_errors[$elementName] = $error;
        $this->_isValid = false;
    }


    /**
     * @param $elementName
     * @return mixed|string
     */
    public function getError($elementName)
    {
        return isset($this->_errors[$elementName])
            ? $this->_errors[$elementName]
            : '';
    }


    /**
     * @param array $errors
     */
    public function setErrors($errors)
    {
        foreach ($errors as $elementName => $error)
            $this->_errors[$elementName] = $error;

        $this->_isValid = false;
    }


    /**
     * @return bool $hasErrors
     */
    public function hasErrors()
    {
        return count($this->_errors) > 0;
    }


    /**
     * @param string $elementName
     * @return bool $isError
     */
    public function isError($elementName)
    {
        return isset($this->_errors[$elementName])
            ? true
            : false;
    }


    /**
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }


    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }


    /**
     * @param null $isCompleted
     * @return bool|null $isCompleted
     */
    public function isCompleted($isCompleted = null)
    {
        if ($isCompleted === null)
        {
            $isCompleted = isset($_SESSION[$this->_name]['submitted']);
            if ($isCompleted)
                unset($_SESSION[$this->_name]['submitted']);

            return $isCompleted;
        }
        else if ($isCompleted)
            $_SESSION[$this->_name]['submitted'] = true;
    }


    /**
     * @param string $message
     */
    public function setCompletedMessage($message)
    {
        $_SESSION[$this->_name]['submitted_message'] = $message;
    }


    /**
     * @return string $message
     */
    public function getCompletedMessage()
    {
        if (isset($_SESSION[$this->_name]['submitted_message']))
        {
            $message = $_SESSION[$this->_name]['submitted_message'];
            unset($_SESSION[$this->_name]['submitted_message']);
            return $message;
        }
        else
            return '';
    }


    /**
     * @return string $csrfToken
     */
    public function generateCsrfToken()
    {
        $csrfToken = md5($this->_name . time());

        if (!isset($_SESSION['CsrfToken']))
            $_SESSION['CsrfToken'] = array();

        $_SESSION['CsrfToken'][$this->_name] = $csrfToken;

        return $csrfToken;
    }
}
