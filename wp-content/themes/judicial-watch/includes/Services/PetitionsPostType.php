<?php

namespace Engage\JudicialWatch\Services;

use GF_Fields;
use GFAPI;
use GuzzleHttp\Client as GuzzleClient;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Money\Currency;
use Money\Money;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\controller\CreateTransactionController;
use net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType\ErrorAType;


class PetitionsPostType
{

    public function __construct()
    {

    }

    public static function createDefaultPetitionForm(\WP_Post $petitionPost)
    {
        $formTitle = 'Petition: ' . ($petitionPost->post_title ?? 'Unnamed') . ' [id: #' . $petitionPost->ID . ']';

        $formMeta = [
            'title' => $formTitle,
            'description' => '',
            'labelPlacement' => 'top_label',
            'descriptionPlacement' => 'below',
            'button' => [
                'type' => 'text',
                'text' => 'Submit',
                'imageUrl' => ''
            ],
            'fields' => PetitionsPostType::getDefaultFormFields(),
            'version' => '2.4.6',
            'useCurrentUserAsAuthor' => true,
            'postContentTemplateEnabled' => false,
            'postTitleTemplateEnabled' => false,
            'postTitleTemplate' => '',
            'postContentTemplate' => '',
            'lastPageButton' => null,
            'pagination' => null,
            'firstPageCssClass' => null,
        ];

        $formId = GFAPI::add_form($formMeta);
        update_field('petition_gravity_form', $formId, $petitionPost->ID);
    }

    public function getDefaultFormFields()
    {
        $nameField = GF_Fields::create([
            'id'    => 1,
            'type'  => 'name',
            'label' => 'Name',
            'isRequired' => true,
            'size' => 'medium',
            'nameFormat' => 'advanced',
            'inputs' => [
                [
                    'id' => '1.2',
                    'label' => 'Prefix',
                    'name' => '',
                    'isHidden' => true,
                    'inputType' => 'radio',
                    'choices' => []
                ],
                [
                    'id' => '1.3',
                    'label' => 'First',
                    'name' => '',
                ],
                [
                    'id' => '1.4',
                    'label' => 'Middle',
                    'name' => '',
                    'isHidden' => true
                ],
                [
                    'id' => '1.6',
                    'label' => 'Last',
                    'name' => '',
                ],
                [
                    'id' => '1.8',
                    'label' => 'Suffix',
                    'name' => '',
                    'isHidden' => true
                ],
            ]
        ]);

        $emailField = GF_Fields::create([
            'id'    => 2,
            'type'  => 'email',
            'label' => 'Email',
            'isRequired' => true,
        ]);

        $addressField = GF_Fields::create([
            'id'    => 3,
            'type'  => 'address',
            'label' => 'Address',
            'isRequired' => true,
            'addressType' => 'us',
            'hideCountry' => true,
            'size' => 'medium',
            'defaultCountry' => 'United States',
            'inputs' => [
                [
                    'id' => '3.1',
                    'label' => 'Street Address',
                    'name' => '',
                    'isHidden' => '',
                ],
                [
                    'id' => '3.2',
                    'label' => 'Address Line 2',
                    'name' => '',
                    'isHidden' => '',
                ],
                [
                    'id' => '3.3',
                    'label' => 'City',
                    'name' => '',
                    'isHidden' => '',
                ],
                [
                    'id' => '3.4',
                    'label' => 'State / Province',
                    'name' => '',
                    'isHidden' => '',
                ],
                [
                    'id' => '3.5',
                    'label' => 'ZIP / Postal Code',
                    'name' => '',
                    'isHidden' => '',
                ],
                [
                    'id' => '3.6',
                    'label' => 'Country',
                    'name' => '',
                    'isHidden' => true
                ],
            ]
        ]);

        return [
            $nameField, $emailField, $addressField
        ];
    }
}