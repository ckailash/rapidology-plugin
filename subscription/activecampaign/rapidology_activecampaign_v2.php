<?php

class rapidology_active_campaign_v2 extends rapidology_active_campagin
{

    public function __construct($url, $api_key)
    {
        parent::__construct($url, $api_key);
    }

    public function v1_validate_html($xpath)
    {

        $form_fields = array();
        $i           = 0;
        $error       = 0;
        $success     = 1;

        /**
         * Check to see if captcha exist if so automaticlly disqualify form
         */
        $captcha = $xpath->query('//div[contains(@class,"g-recaptcha")]');
        if ($captcha->length > 0) {
            return 'Our forms do not support captchas';
        }
        $inputs      = $xpath->query('//input');
        $form_fields = [];
        $i           = 0;
        foreach ($inputs as $input) {
            foreach ($input->attributes as $att) {
                //echo '<pre>'; print_r($att);
                if (($att->name == 'type')) {
                    $form_fields[$i]['type'] = trim($att->nodeValue);
                    $i++;
                }
                if ($att->name == 'required') {
                    $form_fields[$i]['required'] = true;
                }
                if ($att->name == 'name') {
                    $form_fields[$i]['value'] = $att->value;
                }
            }
        }


        foreach ($form_fields as $field) {


            if (isset($field['required']) && isset($field['type']) && !in_array($field['type'], $this->supported_types)) {
                return 'You have an unsupported required field';
            }

        }
        return $form_fields;
    }
}