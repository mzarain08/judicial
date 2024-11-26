<?php

namespace Engage\JudicialWatch\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Collection;

class DeployerServiceProvider
{
    public $apiKey;

    public $apiUser;

    public $apiBaseUrl = 'https://jw.deployer.email/wta/xml.php';

    public $httpClient;

    public $customDeployerFields = [
        1  => 'Title',
        2  => 'First Name',
        3  => 'Last Name',
        4  => 'Phone',
        5  => 'Mobile',
        6  => 'Fax',
        7  => 'Birth Date',
        8  => 'City',
        9  => 'State',
        10 => null,
        11 => 'Country',
        12 => 'Zip Code',
        13 => 'Campaigner Lists',
        14 => 'Publications',
        15 => 'SourceID',
        16 => 'Street Address',
        17 => 'microsite',
        18 => 'int',
        19 => 'Address_2',
        20 => 'County',
        21 => 'MRC Date',
        22 => 'MRC Amount',
        23 => 'DM HPC',
        24 => 'Highest Donation Date',
        25 => 'Average Donation',
        26 => null,
        27 => 'No Snail Mail',
        28 => 'Do not Email',
        29 => 'Deceased',
        30 => 'Salutation',
        31 => 'Highest Gift Amount',
        32 => 'Full Name',
        33 => 'Do Not Send Fundraising Emails',
        34 => 'Is Donor',
        35 => 'ab_split',
        36 => 'Organization',
        37 => 'Phone Flag',
        38 => 'Control Group',
        39 => 'Amicus Status',
        40 => 'Amicus Amount',
        41 => 'President Circle',
        42 => 'Frequency Code',
        43 => 'click_id',
        44 => 'ff',
        45 => 'File Name',
        46 => 'Age',
        50 => 'Submission URL',
        51 => 'Page Title',
        2065 => 'SMS phone',
        2066 => 'Checkbox_',
        2070 => 'int_tag',
        2073 => 'int_datetime'

    ];

    public function __construct()
    {
        if (!defined('DEPLOYER_USER')
            || !defined('DEPLOYER_API_KEY')) {
            throw new \Exception('Deployer user and api key constants are required.');
        }

        $this->apiUser = DEPLOYER_USER;
        $this->apiKey = DEPLOYER_API_KEY;
        $this->httpClient = new GuzzleClient();
        $this->customDeployerFields = collect($this->customDeployerFields);
    }

    /**
     * Map Gfield to Deployer Field
     *
     * This checks for "advanced" Gravity Forms field to parse in a POST request.
     * If none is found (Name, Address, etc) a secondary check is done for simple fields (text inputs, etc).
     *
     * @param $gformField
     * @param $postRequest array
     * @return array
     */
    public function mapGfieldToDeployerField($gformField, $postRequest)
    {
        /**
         * This method first checks for advanced Gravity Forms fields to parse.
         * If none is found (Name, Address, etc) a secondary check is done for simple fields (text inputs, etc).
         */

        /**
         * Gravity Form Advanced Fields
         *
         * Fields like "Name" or "Address" that come from an advanced field are parsed differently,
         * so we test for those first.
         */
        // Name
        if (\GF_Field_Name::class === get_class($gformField)) {
            // First name
            $fnameId = $this->getDeployerIdByLabel('First Name');
            $fnameKey = str_replace('.', '_', data_get(
                collect($gformField->inputs)->whereIn('label', ['First', 'First Name'])->first(),
                'id'
            ));

            // Last name
            $lnameId = $this->getDeployerIdByLabel('Last Name');
            $lnameKey = str_replace('.', '_', data_get(
                collect($gformField->inputs)->whereIn('label', ['Last', 'Last Name'])->first(),
                'id'
            ));

            return [
                $fnameId => data_get($postRequest, 'input_' . $fnameKey),
                $lnameId => data_get($postRequest, 'input_' . $lnameKey),
            ];
        }

        // Phone
        if (\GF_Field_Phone::class === get_class($gformField)) {
            $deployerId = $this->getDeployerIdByLabel('Mobile');
            return [
                $deployerId => data_get($postRequest, 'input_' . $gformField->id)
            ];
        }

        // checkbox
        if (\GF_Field_Checkbox::class === get_class($gformField)) {
            $deployerId = $this->getDeployerIdByLabel('SMS phone');
            $fnameKey = str_replace(
                '.',
                '_',
                data_get(
                    collect($gformField->inputs)->whereIn('id',['11.1','10.1','7.1','9.1','12.1','8.1'])->first(),
                    'id'
                )
            );
            return [
                $deployerId => data_get($postRequest, 'input_' . $fnameKey)?true:false
            ];
        }

        // Address
        $gfieldInputs = collect($gformField->inputs)
            ->transform(function($gfieldInput) {
                if(isset($gfieldInput['label']))
                    $gfieldInput['label'] = trim(strtolower(@$gfieldInput['label']));

                return $gfieldInput;
            });

        if (\GF_Field_Address::class === get_class($gformField)) {
            // Street 1
            $streetId = $this->getDeployerIdByLabel('Street Address');
            $streetKey = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', ['street', 'street address', 'street 1', 'address 1'])->first(),
                'id'
            ));

            // Street 2
            $street2Id = $this->getDeployerIdByLabel('Address_2');
            $street2Key = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', [
                    'street 2', 'street Address 2', 'street 2', 'address 2', 'address line 2'
                ])->first(),
                'id'
            ));

            // City
            $cityId = $this->getDeployerIdByLabel('City');
            $cityKey = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', ['city', 'your city'])->first(),
                'id'
            ));

            // State
            $stateId = $this->getDeployerIdByLabel('State');
            $stateKey = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', ['state', 'your state', 'state / province'])->first(),
                'id'
            ));

            // Zip
            $zipId = $this->getDeployerIdByLabel('Zip Code');
            $zipKey = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', ['zip / postal code', 'zip', 'zip code', 'postal code', 'your zip code'])->first(),
                'id'
            ));

            // Country
            $countryId = $this->getDeployerIdByLabel('Country');
            $countryKey = str_replace('.', '_', data_get(
                $gfieldInputs->whereIn('label', ['country', 'your country'])->first(),
                'id'
            ));

            return [
                $streetId  => data_get($postRequest, 'input_' . $streetKey),
                $street2Id => data_get($postRequest, 'input_' . $street2Key),
                $cityId    => data_get($postRequest, 'input_' . $cityKey),
                $stateId   => data_get($postRequest, 'input_' . $stateKey),
                $zipId     => data_get($postRequest, 'input_' . $zipKey),
                $countryId => data_get($postRequest, 'input_' . $countryKey),
            ];
        }

        /**
         * Common Gravity Field Labels to Map
         *
         * These are second order of preference. First we check class of the field to account
         * for Address, Name, Email, and Phone advanced fields.
         */
        $fieldAliases = collect([
            'First Name'     => collect(['first name', 'your first name']),
            'Last Name'      => collect(['last name', 'your last name']),
            'Zip Code'       => collect(['zip', 'zip code', 'postal code', 'zip / postal code']),
            'Full Name'      => collect(['name', 'full name', 'your name']),
            'Street Address' => collect(['street address', 'address', 'address 1', 'street address 1']),
            'Address 2'      => collect(['address line 2', 'address 2']),
            'City'           => collect(['city', 'your city']),
            'State'          => collect(['state', 'your state']),
            'Country'        => collect(['country', 'your country']),
        ]);

        $singleField = $fieldAliases->filter(function(Collection $aliases, $deployerLabel) use($gformField) {
                return $aliases->contains(
                    strtolower($gformField->label)
                );
            })
            ->mapWithKeys(function(Collection $aliases, $deployerLabel) use($gformField, $postRequest) {
                $deployerId = $this->getDeployerIdByLabel($deployerLabel);
                $formKey = str_replace('.', '_', data_get($gformField, 'id'));

                return [
                    $deployerId => data_get($postRequest, 'input_' . $formKey)
                ];
            })
            ->toArray();

        if ($singleField) {
            return $singleField;
        }

        return [];
    }

    /**
     * Get Deployer Fields From Gform Submission
     *
     * Given a POST request, this method gets Deployer fields from the request and returns them.
     * This method only works when a valid Gravity Forms submission has taken place.
     *
     * @param $postRequest array
     * @return Collection
     * @throws \Exception
     */
    public function getDeployerFieldsFromGformSubmission($postRequest)
    {
        $formId = data_get($postRequest, 'gform_submit');
        $form   = \GFAPI::get_form($formId);

        if (!$formId || !$form) {
            throw new \Exception('Attempted to save Deployer data, but no form was found.');
        }

        /**
         * Map Gravity Form Fields to Deployer Fields
         */
        $customFields = collect($form['fields'])
            ->mapWithKeys(function ($field) use ($postRequest) {
               return $this->mapGfieldToDeployerField($field, $postRequest);

            })
        ->filter();

        // Source ID
        $sourceId = data_get($postRequest, 'SourceID', 34);
        $customFields->put(
            $this->getDeployerIdByLabel('SourceID'),
            $sourceId
        );

        // INT Code
        $intCode = data_get($postRequest, 'int_code');
        $customFields->put(
            $this->getDeployerIdByLabel('int'),
            $intCode
        );

        // INT Tag
        $intCode = data_get($postRequest, 'int_code');
        $customFields->put(
            $this->getDeployerIdByLabel('int_tag'),
            $intCode
        );

        // INT Datetime
        $intDatetime = $intCode."_".date("Ymd");
        $customFields->put(
            '2073',
            $intDatetime
        );
        

        // Click ID
        $clickId = data_get($postRequest, 'click_id');
        if ($clickId) {
            $customFields->put(
                $this->getDeployerIdByLabel('click_id'),
                $clickId
            );
        }

        // Page Title
        $pageTitle = data_get($postRequest, 'Page Title');
        if ($pageTitle) {
            $customFields->put(
                $this->getDeployerIdByLabel('Page Title'),
                urlencode(trim($pageTitle))
            );
        }

        // Page Submission
        $pageSubmission = data_get($postRequest, 'Submission URL');
        if ($pageSubmission) {
            $customFields->put(
                $this->getDeployerIdByLabel('Submission URL'),
                urlencode(trim($pageSubmission))
            );
        }

        /**
         * Virtual Fields
         *
         * Virtual fields aren't on the Gravity Form itself, but instead are appended to the form after
         * page load. An example of this is with newsletter forms that add additional data.
         */
        $virtualFields = collect(data_get($postRequest, 'virtual_fields'));
        $virtualFields->each(function($virtualFieldValue, $virtualFieldKey) use (&$customFields) {
            if (!$virtualFieldValue || !$virtualFieldKey) {
                return;
            }

            $fieldAliases = collect([
                'First Name'     => collect(['first name', 'your first name']),
                'Last Name'      => collect(['last name', 'your last name']),
                'Zip Code'       => collect(['zip', 'zip code', 'postal code', 'zip / postal code']),
                'Full Name'      => collect(['name', 'full name', 'your name']),
                'Street Address' => collect(['street address', 'address', 'address 1', 'street address 1']),
                'Address 2'      => collect(['address line 2', 'address 2']),
                'City'           => collect(['city', 'your city']),
                'State'          => collect(['state', 'your state']),
                'Country'        => collect(['country', 'your country']),
            ]);

             $fieldAliases->filter(function(Collection $aliases, $deployerLabel) use($virtualFieldKey) {
                    return $aliases->contains(
                        strtolower($virtualFieldKey)
                    );
                })
                ->each(function(Collection $aliases, $deployerLabel) use($virtualFieldValue, &$customFields) {
                    $deployerId = $this->getDeployerIdByLabel($deployerLabel);

                    $customFields = $customFields->put(
                        $deployerId,
                        $virtualFieldValue
                    );
                });

        });


        return $customFields;
    }

    /**
     * Get Deployer ID By Label
     *
     * Given a field label, attempt to find the corresponding Deployer ID.
     *
     * @param $deployerLabel string
     * @return mixed
     */
    public function getDeployerIdByLabel($deployerLabel)
    {
        return $this->customDeployerFields
            ->filter()
            ->search($deployerLabel);
    }

    /**
     * Add Or Update Subscriber
     *
     * This is the main method which will subscribe, or update, a user in Deployer.
     *
     * @param $email string
     * @param $customDeployerFields null|array
     */
    public function addOrUpdateSubscriber($email, $customDeployerFields = null)
    {


        //echo "<br>";echo "</pre>";
        //print_r($customDeployerFields['50']);
        $StringUrl = array_slice(explode("/",urldecode($customDeployerFields['50'])),1);
        //echo "</pre>";
        //print_r($StringUrl);
        //echo "<br>";
      
        $finalString = ltrim(rtrim(implode("/",$StringUrl),"/"),"/");
        //echo "<br>";
        //echo $finalString;
        //echo"<br>";
        //print_r(LIST_ID_2637_DEPLOYER_SERVICE);
        //echo "<br>";

       
        if (in_array($finalString, LIST_ID_2637_DEPLOYER_SERVICE)){
            //echo "I am here";
            $listId="2637"; // Locker Dome Audience > list
            $listGroupId="";
       }else{
         //echo "I am outside";
            $listId="";
            $listGroupId="17";
       }
        //die();
        
        $customDeployerFields = $customDeployerFields ?? collect();
        $costumArray = $customDeployerFields->all();
        $sms_mobile = '';
        $opt_in = 0;
        if( isset($costumArray['2065']) ){
            $opt_in = 1;
            $sms_mobile = str_replace(["(",")","-"," "], ["","","",""], $costumArray['5']);
            unset($costumArray['2065']);
        }
        
        $xmlString = \Timber::render(
            'deployer/requests/update-subscriber.twig',
            [
                // API Auth
                'api' => [
                    'user'  => $this->apiUser,
                    'token' => $this->apiKey
                ],
                "sms_mobile" => $sms_mobile,
                "opt_in" => $opt_in,
                "listId" => $listId,
                // Custom Fields
                'customFields' => collect($costumArray),
                // List group ID is always 17; it's the default members list
                'listGroupId' => $listGroupId,
                // Subscriber
                'subscriber' => [
                    'email' => $email
                ],
            ]
        );
        //echo "<pre>";
        //print_r($xmlString);
        //die();
        
        $httpResponse = $this->httpClient->post(
            $this->apiBaseUrl,
            [
                'body' => $xmlString
            ]
        );

        return $httpResponse;
    }

    public function checkUniqueLead($email){

        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
        <xmlrequest>
               <username>judicialwatch</username>
                <usertoken>453e5318ec0a29f3ec52c27207cae995c11ae694</usertoken>
                <requesttype>subscribers</requesttype>
                <requestmethod>IsSubscriberOnList</requestmethod>
                <details>
                <Email>'.$email.'</Email>
                <List>38</List>
                </details>
                </xmlrequest>';


        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://jw.deployer.email/wta/xml.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS =>$xmlString,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/xml'
            ),
        ));
        try{

            $result = @curl_exec($curl);
            if($result === false) {
                error_log("Error performing request Action: IsSubscriberOnList");
            }
            else {
                    $xml_doc = simplexml_load_string($result);
                    if ($xml_doc->status == 'SUCCESS') {
                        if($xml_doc->data->subscriberid > 0){
                            return 0; 
                        }else{
                            return 1;
                        }
                    } else {
                       error_log( 'Error is:- '. $xml_doc->errormessage);;
                    }
            }
        }catch (Exception $e){
            print_r($e->getMessage());
        }

        
    }
}