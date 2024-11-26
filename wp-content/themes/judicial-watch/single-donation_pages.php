<?php

use Engage\JudicialWatch\JudicialWatch;

try {
    $template = new \Engage\JudicialWatch\Templates\Singular\Donate;

    // For single donation pages, check access rules if they exist
    $allowUsers = collect(get_field('restrict_access_to_users', $template->getContext()->page))
        ->filter(function($item) {
            return $item ? true : null;
        });

    // If no rules, or is admin, return early
    if (!$allowUsers->count() || current_user_can('read_private_posts')) {
        $template->show('pages/singular/single-donation-page.twig', JudicialWatch::isDebug());
        return;
    }

    // User must be logged in
    $currentUser = wp_get_current_user();
    if (!$currentUser) {
        $template->redirectTo404();
    }

    // If user ID matches
    $allowUsers->each(function($user) use ($currentUser, $template) {
        if ($currentUser->ID === $user->ID) {
            $template->show('pages/singular/single-donation-page.twig', JudicialWatch::isDebug());
            exit;
        }
    });

    // Default to 404
    $template->redirectTo404();
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}