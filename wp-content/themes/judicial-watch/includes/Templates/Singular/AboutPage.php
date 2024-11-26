<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class AboutPage extends AbstractTemplate {

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
	    $page = $this->transformPost(get_post($this->postId), true);
        $defaultImagePath = get_theme_file_uri('assets/images/section-title-bg-issues.jpg');

        $backgroundImage = get_field('page_header_background_image', $page->postId) ?: $defaultImagePath;
        if (is_array($backgroundImage)) {
            $backgroundImage = $backgroundImage['url'];
        }

		$this->context = [
			'page'                 => $page,
            'backgroundImage'      => $backgroundImage,
            'investigationsTab' => [
                'description' => get_field('investigations_tab_description', $this->postId),
                'staff' => $this->getInvestigationsStaff(get_field('investigations_staff_order', $this->postId)),
            ],
            'legalTab' => [
                'description' => get_field('legal_tab_description', $this->postId),
                'staff' => $this->getLegalStaff(get_field('legal_staff_order', $this->postId)),
            ],
            'boardTab' => [
                'description' => get_field('board_tab_description', $this->postId),
                'staff' => $this->getBoardStaff(get_field('board_staff_order', $this->postId)),
            ],
            'staffTab' => [
                'description' => get_field('staff_tab_description', $this->postId),
                'staff' => $this->getOtherStaff(get_field('staff_order', $this->postId)),
            ],
            'careersTab' => [
                'description' => get_field('career_tab_description', $this->postId),
                'careers' => $this->getCareerPosts(get_field('careers_order', $this->postId))
            ]
		];
	}

    public function getInvestigationsStaff($staffField)
    {
        $staff = collect(get_field('investigations_staff_order', $this->postId))
            ->transform(function($staffItem) {
                $staffPost = data_get($staffItem, 'investigations_staff_member');

                if (!$staffPost) {
                    return;
                }

                $post = new JWPost($staffPost, true);
                $post->team_title = get_field('team_title', $staffPost->ID);

                return $post;
            });

        return $staff;
    }

    public function getLegalStaff($staffField)
    {
        $staff = collect(get_field('legal_staff_order', $this->postId))
            ->transform(function($staffItem) {
                $staffPost = data_get($staffItem, 'legal_staff_member');

                if (!$staffPost) {
                    return;
                }

                $post = new JWPost($staffPost, true);
                $post->team_title = get_field('team_title', $staffPost->ID);

                return $post;
            });

        return $staff;
    }

    public function getBoardStaff($staffField)
    {
        $staff = collect(get_field('board_staff_order', $this->postId))
            ->transform(function($staffItem) {
                $staffPost = data_get($staffItem, 'board_staff_member');

                if (!$staffPost) {
                    return;
                }

                $post = new JWPost($staffPost, true);
                $post->team_title = get_field('team_title', $staffPost->ID);

                return $post;
            });

        return $staff;
    }

    public function getOtherStaff($staffField)
    {
        $staff = collect(get_field('staff_order', $this->postId))
            ->transform(function($staffItem) {
                $staffPost = data_get($staffItem, 'staff_member');

                if (!$staffPost) {
                    return;
                }

                $post = new JWPost($staffPost, true);
                $post->team_title = get_field('team_title', $staffPost->ID);

                return $post;
            });

        return $staff;
    }

    public function getCareerPosts($careerField)
    {
        $careers = collect(get_field('careers_order', $this->postId))
            ->transform(function($post) {
                $careerPost = data_get($post, 'career');

                if (!$careerPost) {
                    return;
                }

                $careerPost->department = get_field('department', $careerPost->ID);
                $careerPost->reports_to = get_field('reports_to', $careerPost->ID);
                $careerPost->status = get_field('status', $careerPost->ID);
                $careerPost->summary = get_field('summary', $careerPost->ID);
                $careerPost->content = apply_filters('the_content', $careerPost->post_content);

                return $careerPost;
            });

        return $careers;
    }

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	protected function bodyClasses(): array {
		return ['page', 'page-template-2col', 'page-about'];
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