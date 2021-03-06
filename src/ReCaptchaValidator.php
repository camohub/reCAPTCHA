<?php

namespace Minetro\Forms\reCAPTCHA;

use Nette\Forms\Controls\BaseControl;
use Nette\Http\Url;
use Nette\Object;

/**
 * reCAPTCHA Validator
 *
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 *
 * @method onValidateControl(ReCaptchaValidator $validator, BaseControl $control)
 * @method onValidate(ReCaptchaValidator $validator, mixed $response)
 */
class ReCaptchaValidator extends Object
{

    /** GOOGLE URL */
    const VERIFICATION_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var array */
    public $onValidate = [];

    /** @var array */
    public $onValidateControl = [];

    /** @var string */
    private $secretKey;

    /**
     * @param string $secretKey
     */
    function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param mixed $response
     * @return ReCaptchaResponse|bool
     */
    public function validate($response)
    {
        // Fire events!
        $this->onValidate($this, $response);

        // Load response
        $response = $this->makeRequest($response);

        // Response is empty or failed..
        if (empty($response)) return FALSE;

        // Decode server answer (with key assoc reserved)
        $answer = json_decode($response, TRUE);

        // Return response
        if (trim($answer['success']) == TRUE) {
            return new ReCaptchaResponse(TRUE);
        } else {
            return new ReCaptchaResponse(FALSE, isset($answer['error-codes']) ? $answer['error-codes'] : NULL);
        }
    }

    /**
     * @param BaseControl $control
     * @return bool
     */
    public function validateControl(BaseControl $control)
    {
        // Fire events!
        $this->onValidateControl($this, $control);

        // Get response
        $response = $this->validate($control->getValue());

        if ($response) {
            return $response->isSuccess();
        } 
        return FALSE;
    }


    /**
     * HELPERS *****************************************************************
     * *************************************************************************
     */

    /**
     * @param mixed $response
     * @param string $remoteIp
     * @return mixed
     */
    protected function makeRequest($response, $remoteIp = NULL)
    {
        if (empty($response)) return NULL;

        $params = [
            'secret' => $this->secretKey,
            'response' => $response,
        ];

        if ($remoteIp) {
            $params['remoteip'] = $remoteIp;
        }

        return @file_get_contents($this->buildUrl($params));
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function buildUrl(array $parameters = [])
    {
        $url = new Url(self::VERIFICATION_URL);

        foreach ($parameters as $name => $value) {
            $url->setQueryParameter($name, $value);
        }

        return (string)$url;
    }

}
