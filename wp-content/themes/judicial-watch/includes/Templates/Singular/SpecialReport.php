<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class SpecialReport extends AbstractTemplate
{
    /**
     * bodyClasses
     *
     * Returns an array of custom body classes for this template.
     *
     * @return array
     */
    public function bodyClasses(): array
    {
        return ['p-t-0 page page-special-report'];
    }

    /**
     * addToThisPageContext
     *
     * Adds data to the $context property that is, later, used to render
     * a Twig template.
     *
     * @return void
     */
    protected function addToThisPageContext()
    {

        $file = get_field('report', $this->postId);
        $image = get_field('report_image', $this->postId);
        $formType = get_field('form_type', $this->postId);
        $layout = get_field('layout', $this->postId);
        $disclaimer = get_field('disclaimer', $this->postId);
        $successMessage = get_field('success_message', $this->postId);

        $pageData = [
            'file' => $file,
            'image' => $image,
            'formType' => $formType,
            'form' => gravity_form(get_field('special_report_gravity_form', $this->postId), false, false, false, null, true, 1, false),
            'layout' => $layout,
            'disclaimer' => $disclaimer,
            'success_message' => $successMessage,
            'post' => new JWPost(get_post($this->postId), true)
        ];

        $this->context = $pageData;
    }

    /**
     * withCorruptionBanner
     *
     * Returns true when the Fight Corruption! banner should be visible
     * on this template.
     *
     * @return bool
     */
    protected function withCorruptionBanner(): bool
    {
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
    protected function withDaywatchBanner(): bool
    {
        return false;
    }
}