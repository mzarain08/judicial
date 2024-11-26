<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class Donate extends AbstractTemplate {

    /**
     * addToThisPageContext
     *
     * Adds data to the $context property that is, later, used to render
     * a Twig template.
     *
     * @return void
     * @throws ContainerException
     */
    protected function addToThisPageContext() {
        global $wp;

        $page = $this->transformPost(get_post($this->postId), true);
        $defaultImagePath = get_theme_file_uri('assets/images/section-title-bg-issues.jpg');

        $backgroundImage = get_field('page_header_background_image', $page->postId) ?: $defaultImagePath;

        $donateAmounts = [35, 100, 250, 500, 1000, 2500];
        $acfDonateAmounts = get_field('contribution_amounts', $this->postId);

        if (is_array($acfDonateAmounts) && count($acfDonateAmounts)) {
            $donateAmounts = collect($acfDonateAmounts)->transform(function ($amount) {
                    return (int)collect($amount)->first();
                })
                ->toArray();
        }

        // Allow override of recurring checkbox with a get param
        $preselectRecurring = get_field('preselect_recurring_donation', $this->postId);
        if (!$preselectRecurring && isset($_GET['isc_is_recur'])) {
            $preselectRecurring = true;
        }

        // Get click id from session if applicable
        $clickId = null;
        if (!session_id()) {
            session_start();
        }
        if (data_get($_SESSION, 'click_id')) {
            $clickId = $_SESSION['click_id'];
            unset($_SESSION['click_id']);
        }

        // Check if user came from a successful submission
        $petitionSubmittedSuccess = false;
        if (data_get($_SESSION, 'petition_successful_submission')) {
            $petitionSubmittedSuccess = true;
        }

        $pageContentAvailable = false;
        $pageContentNotAvailable = true;
        if(strlen($page->content)>0){
            $pageContentAvailable = true;
            $pageContentNotAvailable = false;
        }

        $this->context = [
            'page'              => $page,
            'clickId'           => $clickId,
            'backgroundImage'   => $backgroundImage,
            'donateAmounts'     => $donateAmounts,
            'paypalReturnUrl'   => home_url('/thanks-for-your-donation'),
            'paypalCancelReturnUrl' => home_url($wp->request),
            'allowPaypal'       => get_field('allow_paypal', $this->postId),
            'lockerdomeClickId' => get_field('lockerdome_click_id', $this->postId),
            'thankyouPage'      => get_field('thank_you_page', $this->postId),
            'mbTrackingCode'    => get_field('mb_tracking_code', $this->postId),
            'preselectRecurringCheckbox' => $preselectRecurring,
            'petitionSubmittedSuccess'   => $petitionSubmittedSuccess,
            'pageContentAvailable' => $pageContentAvailable,
            'pageContentNotAvailable' => $pageContentNotAvailable
        ];
    }

    public function redirectTo404()
    {
        status_header(404);
        nocache_headers();
        include(get_query_template('404'));
        exit;
    }

    /**
     * bodyClasses
     *
     * Returns an array of custom body classes for this template.
     *
     * @return array
     */
    protected function bodyClasses(): array {
        return ['page', 'page-donate'];
    }

    /**
     * withCorruptionBanner
     *
     * Returns true when the Fight Corruption! banner should be visible
     * on this template.
     *
     * @return bool
     */
    protected function withCorruptionBanner(): bool {
        return false;
    }

    /**
     * withDaywatch
     *
     * Returns true when the Daywatch subscription banner should be
     * visible on this template.
     *
     * @return bool
     */
    protected function withDaywatchBanner(): bool {

        // TODO: this banner is in invision, not in the static-cut

        return false;
    }
}