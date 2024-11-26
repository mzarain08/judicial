<?php
namespace Engage\JudicialWatch;

use Engage\JudicialWatch\Services\AuthorizeNetService;
use Engage\JudicialWatch\Services\DeployerServiceProvider;
use Engage\JudicialWatch\Services\Media\PodcastImporter;
use Engage\JudicialWatch\Services\PetitionsPostType;
use Engage\JudicialWatch\Services\Redirector;
use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Traits\SharingAndAnalyticsTrait;
use Engage\WordPress\Traits\AutoExportCustomFieldGroupTrait;
use Engage\WordPress\Hooks\HookException;
use Engage\JudicialWatch\Services\TaxonomyMeta\CategoryMeta;
use Engage\JudicialWatch\Services\Documents\DocumentArchivist;
use Engage\JudicialWatch\Services\Media\MediaImporter;
use Engage\JudicialWatch\Services\ACF\ConfigureACF;
use Engage\JudicialWatch\Services\ACF\SitewideSettings;
use Exception;
use Illuminate\Support\Str;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use Throwable;
use WP_Post;
use GuzzleHttp\Client as GuzzleClient;
use YoutubeCron;


class JudicialWatch extends AbstractHandler {
    use AutoExportCustomFieldGroupTrait;
    use SharingAndAnalyticsTrait;

    // we can use the splat operator (...) and the following constants to
    // more easily work with the gravity_form() function.  we can call it
    // as follows to get the form with or without the associated metadata
    // like title and description:
    //
    // gravity_form($gfId, ...JudicialWatch::GF_RETURN_FORM);

    const GF_RETURN_FORM = [1, 1, 1, null, 0, 1, false];
    const GF_RETURN_ONLY_FORM = [0, 0, 0, null, 0, 0, false];

    // so that the various templates within this theme can quickly identify
    // their theme handler and communicate with it, we store this static
    // property which we use as a variable variable throughout.

    /**
     * @var string
     */
    public static $theme = "judicialWatchThemeHandler";

    /**
     * @var Redirector
     */
    protected $redirector;

    /**
     * initialize
     *
     * Connects this theme to WordPress action and filter hooks.
     *
     * @throws HookException
     *
     * @return void
     */
    public function initialize(): void {
        $this->addAction("init", "addImportedUserRole");
        $this->addAction("init", "manageHappeningNowCookie");
        $this->addAction("after_setup_theme", "setupThemeOptions");
        $this->addAction("wp_enqueue_scripts", "enqueuePublicAssets");
        $this->addAction("admin_enqueue_scripts", "enqueueAdminAssets");
        $this->addAction("acf/save_post", "createGravityformOnPetitionSave");
        $this->addFilter("cron_schedules", "addCronSchedulingOptions");
        $this->addFilter("gform_post_submission", "redirectPetitionSubmissionToDonationPage");
        add_action("gform_after_submission_6", [$this, 'handleNewsletterSignup'], 10, 2);
        add_action("gform_pre_submission", [$this, 'gformAddSourceIdBeforeSubmit'], 10, 2);
        $this->addAction('admin_post_donate_authorizenet', 'donateWithAuthorizenet');
        $this->addAction('admin_post_nopriv_donate_authorizenet', 'donateWithAuthorizenet');
        $this->addFilter('search_template', 'rewriteDocumentsSearchTemplate');
        $this->addFilter('acf/render_field/name=petitions_submissions_tab_content', 'showPetitionsAcfSubmissionsLink');
        add_action('pre_get_posts', [$this, 'setSearchPostsLimit']);

        add_filter('gform_display_add_form_button', [$this, 'addGravityformsToTinymce']);
        add_action('init', [$this, 'rewriteOldPostUrls']);

        // Cron
        add_action('API_IMPORT_CRON', [$this, 'runApiImportCrons']);

        $this->initializeServices();

        error_log('Vipul initializing services');


        // our traits need some initialization, too, so we'll call their
        // init methods, too.  the first one takes the location in which
        // we're saving the JSON files produced by ACF when we change
        // field groups.  the true-flag sent to the second one tells us
        // that we're adding a sub-menu page to the Settings menu item.

        $this->initializeCustomFieldGroupExport($this->dir . "/assets/acf-groups");
        $this->initializeSharingAndAnalytics(true);

        $this->initializeCustomEmbedWidths();
        $this->saveYoutubeThumbnailOnVideoSave();
    }

    public function runApiImportCrons()
    {
        // Only run in production, since it consumes API credits
        if ('production' !== WP_SERVER_ENVIRONMENT) {
            return;
        }

        \set_time_limit(0);
        (new PodcastImporter)->import();
        (new YoutubeCron)->import();
    }

    public function setSearchPostsLimit()
    {
        global $wp_query;

        if (is_search()) {
            $wp_query->query_vars['posts_per_page'] = 9;
        }
    }

    public function rewriteOldPostUrls()
    {
        $requestUri = data_get($_SERVER, 'REQUEST_URI');
        $postRegex = '/^\/blog\/\d{4}\/\d{2}\/(\S+)/';
        $oldPostMatch = null;

        if (!Str::contains($requestUri, '/blog/')) {
            return;
        }


        preg_match($postRegex, $requestUri, $oldPostMatch);

        $matches = collect($oldPostMatch);
        $postPermalink = null;
        if (2 === $matches->count()) {
            $postPermalink = str_replace('/', '', $matches->get(1));

            if (!$postPermalink || !strlen($postPermalink)) {
                return;
            }
        }

        // Get post permalink and redirect
        $post    = get_page_by_path($postPermalink, OBJECT, 'post');
        $postUrl = get_permalink($post);

        wp_redirect($postUrl, 301);
        exit;
    }

    /**
     * gForm Add Source ID Before Submit
     *
     * Adds `source` POST Parameter to all Gravity Forms.
     * If not sent, the source ID becomes the default of 34.
     */
    public function gformAddSourceIdBeforeSubmit()
    {
        global $post;

        $_POST['SourceID'] = (int)data_get($_GET, 'source', 34);
        $_POST['click_id'] = (int)data_get($_GET, 'clk');
        $_POST['Page Title'] = Str::limit(data_get($post, 'post_title'));
        $_POST['Submission URL'] = sprintf(
            '%s%s',
            data_get($_SERVER, 'HTTP_HOST'),
            data_get($_SERVER, 'REQUEST_URI')
        );
    }

    public function handleNewsletterSignup($entry, $form)
    {
        $formId = data_get($_POST, 'gform_submit');
        $form   = \GFAPI::get_form($formId);

        if ($form) {
            $emailField = collect($form['fields'])
                ->filter(function ($field) {
                    return 'GF_Field_Email' === get_class($field);
                })
                ->first();
            $userEmail = data_get($_POST, 'input_' . $emailField->id);
            if ($userEmail) {
                $deployerService = new DeployerServiceProvider;
                $deployerService->addOrUpdateSubscriber(
                    $userEmail,
                    $deployerService->getDeployerFieldsFromGformSubmission($_POST)
                );
            }
        }
    }

    public function redirectPetitionSubmissionToDonationPage()
    {
        global $post;

        if ('petitions' !== $post->post_type) {
            return;
        }

        if (!session_id()) {
            session_start();
        }

        // Indicate in session that a successful submission took place
        $_SESSION['petition_successful_submission'] = 1;

        // Check for click ID
        $clickId = data_get($_GET, 'clk') ?? data_get($_POST, 'click_id');
        if ($clickId) {
            $_POST['click_id']    = $clickId;
            $_SESSION['click_id'] = $clickId;
        }

        // Check for INT Code
        $intCode = data_get($_GET, 'int_code') ?? get_field('int_code', $post->ID);
        if ($intCode) {
            data_set($_POST, 'int_code', $intCode);
            $_SESSION['int_code'] = $intCode;
        }

        // Save user in Deployer list
        $formId = data_get($_POST, 'gform_submit');
        $form   = \GFAPI::get_form($formId);

        if ($form) {
            // Email
            $emailField = collect($form['fields'])
                ->filter(function ($field) {
                    return 'GF_Field_Email' === get_class($field);
                })
                ->first();
            $userEmail = data_get($_POST, 'input_' . $emailField->id);
            if ($userEmail) {
                $deployerService = new DeployerServiceProvider;
                
                $isNewLead =$deployerService->checkUniqueLead($userEmail);
                $event="existing_lead";
                if($isNewLead){
                    $event="new_lead";
                }

                $deployerService->addOrUpdateSubscriber(
                    $userEmail,
                    $deployerService->getDeployerFieldsFromGformSubmission($_POST)
                );
            }
        }

        // Redirect to donation page
        $donationPage = get_field('donation_page', $post->ID);
        if (!$donationPage) {
            return;
        }

        // The field format comes through as an array; get the underlying post
        $donationPage = collect($donationPage)->first();
        $permalink    = get_permalink($donationPage->ID);
        $redirect_uri = add_query_arg ('event', $event, $permalink) ;

        wp_redirect($redirect_uri);
        exit;
    }

    /**
     * Create Gravity Form on Petition Save
     *
     * All petitions must have a gravity form. Create a default form if one wasn't set.
     * @param $postId
     */
    public function createGravityformOnPetitionSave($postId)
    {
        global $post;

        if ('petitions' !== $post->post_type) {
            return;
        }

        $gravityFormAcfValue = data_get($_POST, 'acf.field_5c61f961ac458');

        if ($gravityFormAcfValue && $post) {
            update_field('petition_gravity_form', $gravityFormAcfValue, $post->ID);
            return;
        }

        if (!$gravityFormAcfValue && $post) {
            PetitionsPostType::createDefaultPetitionForm($post);
        }

    }

    public function showPetitionsAcfSubmissionsLink($field)
    {
        global $post;
        echo '<div class="wrap" style="padding:25px;">';
        echo '<h1>View Submissions</h1>';

        // Get gravity form ID
        $gravityFormId = get_field('petition_gravity_form', $post->ID);
        if (!$gravityFormId) {
            echo '<p>No Gravity Form is associated with this Petition yet.</p>';
        }

        // Get Gravity Form
        $gravityForm = \GFAPI::get_form($gravityFormId);
        if ($gravityFormId && !$gravityForm) {
            echo '<p>A Form is set but it doesnt appear to exist anymore. Please create a new form.</p>';
        }

        // Get link to gravity form
        if ($gravityFormId && $gravityForm) {
            echo '<p>You may view all submissions for this petition below.</p>';
            printf(
                '<a href="%s" class="button button-primary button-large" target="_blank">View Submissions</a>',
                '/wp-admin/admin.php?page=gf_entries&id=' . $gravityFormId
            );
        }

        echo '</div>';

        return $field;
    }

    public function rewriteDocumentsSearchTemplate($template)
    {
        if (is_post_type_archive('documents') && is_search()) {
            $find_template = locate_template(['archive-documents.php']);

            if ('' !== $find_template) {
                $template = $find_template;
            }

            return $template;
        } else if (is_post_type_archive('videos') && is_search()) {
            $find_template = locate_template(['archive-videos.php']);

            if ('' !== $find_template) {
                $template = $find_template;
            }

            return $template;
        } else if (is_search()) {
            $find_template = locate_template(['search.php']);

            if ('' !== $find_template) {
                $template = $find_template;
            }

            return $template;
        }
    }

    public function donateWithAuthorizenet()
    { 
        // print_r([
        //     data_get(@$_POST, 'is_mobile_attached'),
        //     (data_get(@$_POST, 'is_mobile_attached') == 'true')
        // ]); die;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://jw.deployer.email/wta/xml.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="UTF-8"?>
            <xmlrequest>
                <username>judicialwatch</username>
                <usertoken>453e5318ec0a29f3ec52c27207cae995c11ae694</usertoken>
                <requesttype>subscribers</requesttype>
                <requestmethod>AddOrUpdateSubscriber</requestmethod>
                <details>
                    <emailaddress>'.data_get(@$_POST['person'], 'email').'</emailaddress>
                    <listgroupid>17</listgroupid>
                    <format>html</format>
                    <confirmed>yes</confirmed>
                    <customfields>
                        <item>
                            <fieldid>2</fieldid>
                            <value>'. data_get(@$_POST['person']['name'], 'first') .'</value>
                        </item>
                        <item>
                            <fieldid>3</fieldid>
                            <value>'. data_get(@$_POST['person']['name'], 'last') .'</value>
                        </item>
                        <item>
                            <fieldid>16</fieldid>
                            <value>'. data_get(@$_POST['person']['address'], 'street') .'</value>
                        </item>
                        <item>
                            <fieldid>19</fieldid>
                            <value>'. data_get(@$_POST['person']['address'], 'street_2') .'</value>
                        </item>
                        <item>
                            <fieldid>8</fieldid>
                            <value>'. data_get(@$_POST['person']['address'], 'city') .'</value>
                        </item>
                        <item>
                            <fieldid>9</fieldid>
                            <value>'. data_get(@$_POST['person']['address'], 'state') .'</value>
                        </item>
                        <item>
                            <fieldid>12</fieldid>
                            <value>'. data_get(@$_POST['person']['address'], 'zipcode') .'</value>
                        </item>
                        <item>
                            <fieldid>15</fieldid>
                            <value>34</value>
                        </item>
                        <item>
                            <fieldid>18</fieldid>
                            <value>A20II1ARP</value>
                        </item>
                        <item>
                            <fieldid>2070</fieldid>
                            <value>A20II1ARP</value>
                        </item>
                        <item>
                            <fieldid>51</fieldid>
                            <value>NATIONAL+IMPACT+SURVEY+OF+ILLEGAL+IMMIGRATION+ON+TAXPAYERS+AND+VOTERS+-+AR</value>
                        </item>
                        <item>
                            <fieldid>50</fieldid>
                            <value>judicialwatchx.wpengine.com/donate/make-a-contribution-2/</value>
                        </item>
                        <item>
                            <fieldid>5</fieldid>
                            <value>'. data_get(@$_POST['person'], 'phone') .'</value>
                        </item>
                    </customfields>
                    <opt_in>'.( (data_get(@$_POST, 'is_mobile_attached') == 'true')?1:0 ).'</opt_in>
                    <sms_mobile>'.( (data_get(@$_POST, 'is_mobile_attached') == 'true')?data_get(@$_POST['person'], 'phone'):"" ) .'</sms_mobile>
                </details>
            </xmlrequest>',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/xml'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        // Curl End:

        try{
            $authorizenetService = new AuthorizeNetService;

            if (!session_id()) {
                session_start();
            }

            $intCode = data_get($_SESSION, 'int_code');
            if ($intCode) {
                data_set($_POST, 'int_code', $intCode);
            }

            // Send request based on subscription or transaction
            $isSubscription = data_get($_POST, 'isMonthlyDonation');
            if ($isSubscription == 'true') {
                $transactionResponse = $authorizenetService->createMonthlySubscriptionFromPostRequest($_POST);
            } else {
                $transactionResponse = $authorizenetService->createTransactionFromPostRequest($_POST);
            }

            // Check for top-level errors
            $messages = collect($transactionResponse->getMessages());
            if ($messages->count()
                && ('Error' === $messages->first() || 'Error' === data_get($messages->first(), 'resultCode') )) {
                wp_send_json([
                    'success' => false,
                    'message' => 'Sorry, we were unable to process this transaction.'
                ]);
                exit;
            }


            // Check for transaction-level errors on once-only transactions
            if (CreateTransactionResponse::class === get_class($transactionResponse)) {
                $errors = collect(data_get($transactionResponse->getTransactionResponse(), 'errors'));
                if ($errors->count()) {
                    wp_send_json([
                        'success' => false,
                        'message' => 'Sorry, we were unable to process this transaction.'
                    ]);
                    exit;
                }
            } else {
                // Check for subscription transaction success
                if (!$messages->count()
                    || data_get($messages, 'resultCode') !== 'Ok') {
                    wp_send_json([
                        'success' => false,
                        'message' => 'Sorry, we were unable to process this transaction.'
                    ]);
                    exit;
                }
            }
            // unset session for int_code
            if(isset($_SESSION['int_code']))
            {
                unset($_SESSION['int_code']);
            }

            $tresponse = $transactionResponse->getTransactionResponse();

            if (($tresponse != null) && ($tresponse->getResponseCode()=="1"))
            {
                // Call ga ecommerce tracking function after transaction response
                $transId = $tresponse->getTransId();
                $amount = $_POST['transaction_amount'];

                return wp_send_json([
                    'success' => true,
                    'trans_id' => $transId,
                    'amount' => $amount,
                    'message' => 'Your donation has been processed - thanks!'
                ]);
            }else{
                return wp_send_json([
                    'success' => true,
                    'message' => 'Your donation has been processed - thanks!'
                ]);
            }
        }
        catch(HookException $e){
            return wp_send_json([
                'success' => true,
                'message' => 'Your donation has been processed - thanks!'
            ]);
        } 

        

    }

    public function saveYoutubeThumbnailOnVideoSave()
    {
        // Save thumbnail from youtube if youtube id was set and no images exist
        add_action('save_post', function($postId, $post, $update) {
            $bgImage = get_field('background_image', $postId);
            $youtubeId = get_field('video_id', $post);

            if (!get_the_post_thumbnail($postId)
                && !$bgImage
                && $youtubeId) {
                $imageUrl = sprintf('https://img.youtube.com/vi/%s/maxresdefault.jpg', $youtubeId);
                $httpClient = new GuzzleClient([
                    'exceptions' => false
                ]);
                $response = $httpClient->get($imageUrl);

                if (200 !== $response->getStatusCode()) {
                    return;
                }

                $tmpFilePath = download_url($imageUrl);
                $mimeType = mime_content_type($tmpFilePath);
                $mimeExtensions = collect([
                    'image/jpeg'   => '.jpg',
                    'image/gif'    => '.gif',
                    'image/png'    => '.png',
                    'image/x-icon' => '.ico',
                ]);
                $fileName = sprintf('%s-%s%s', $youtubeId, str_random(6), $mimeExtensions->get($mimeType));
                $fileSize = filesize($tmpFilePath);
                $fileAttributes = [
                    'name'     => $fileName,
                    'type'     => $mimeType,
                    'tmp_name' => $tmpFilePath,
                    'error'    => 0,
                    'size'     => $fileSize,
                ];
                $uploadedFileAttributes = wp_handle_sideload($fileAttributes,
                    [
                        'test_form'   => false,
                        'test_size'   => true,
                        'test_upload' => true,
                    ]
                );

                $attachId = wp_insert_attachment([
                    'guid'           => wp_upload_dir()['url'] . '/' . basename($uploadedFileAttributes['file']),
                    'post_mime_type' => $uploadedFileAttributes['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename($uploadedFileAttributes['file'])),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ]);

                $attachAbsPath = sprintf('%s/%s', wp_upload_dir()['basedir'], 'test.png');
                $attachData = wp_generate_attachment_metadata($attachId, $uploadedFileAttributes['file']);
                $attachData['sizes']['full'] = $attachData['sizes']['large'];
                wp_update_attachment_metadata($attachId, $attachData);
                set_post_thumbnail($post, $attachId );
            }


        }, 10, 3);
    }

    public function addGravityformsToTinymce()
    {
        $request = collect($_GET);

        if ($request->get('taxonomy') === 'issues'
            && $request->get('tag_ID') === '7'
            && $request->get('post_type') === 'post') {
                return true;
            }
    }

    public function initializeCustomEmbedWidths()
    {
        add_filter('template_redirect', function($embedSize) {
            if (is_page()) {
                global $content_width;
                $content_width = 650;
            }
        });
    }

    /**
     * initializeServices
     *
     * Simply initializes the Service objects used by this theme to help
     * separate responsibilities across different objects.
     *
     * @throws HookException
     *
     * @return void
     */
    protected function initializeServices(): void {

        // the following service objects each handle a portion of our
        // plugin's needs.  each can be found within the Services
        // namespace, and each attempts to be as focused as possible
        // without overly inflating the number of objects we need
        // here.

        (new ConfigureACF())->initialize();         // configurations/hooks for ACF fields
        (new SitewideSettings())->initialize();         // configurations/hooks for ACF fields
        (new MediaImporter())->initialize();        // handles cron jobs for podcasts and videos
        (new DocumentArchivist())->initialize();    // handles issues relating to the doc archive
        (new CategoryMeta())->initialize();         // add category columns

        // we use our Redirector object when we encounter 404 errors, so unlike
        // the objects above that we can instantiate and then discard, this one
        // we'll keep.

        //$this->redirector = new Redirector();
        //$this->redirector->initialize();
    }

    /**
     * catcher
     *
     * Catches throwable objects and handles them based on whether or not
     * we're in a debugging environment.
     *
     * @param Throwable $thrown
     *
     * @return void
     */
    public static function catcher(Throwable $thrown): void {
        self::isDebug() ? self::debug($thrown, true) : self::writeLog($thrown);
    }

    /**
     * getRecentPostTimestamp
     *
     * This static function returns the date of the most recently published
     * post in the database.
     *
     * @return int
     */
    public static function getRecentPostTimestamp() {
        $posts = wp_get_recent_posts([
            "post_status"    => "publish",
            "posts_per_page" => 1,
        ], OBJECT);

        /** @var WP_Post $post */

        $post = $posts[0];

        if (!$post) {
            return;
        }

        return strtotime($post->post_date_gmt);
    }

    /**
     * addImportedUserRole
     *
     * Adds the "imported" role to our WordPress installation.
     *
     * @return void
     */
    protected function addImportedUserRole(): void {
        add_role("imported", __("Imported", "judicialwatch"), []);
    }

    /**
     * manageHappeningNowCookie
     *
     * This static method manages the happening now cookie which determines
     * whether or not the notification badge for new posts is visible or
     * invisible on screen.
     *
     * @return void
     */
    public static function manageHappeningNowCookie(): void {
        if (isset($_COOKIE["hide-happening-now-badge"])) {

            // the timestamp in our cookie is the last time that this
            // visitor clicked the happening now icon.  we want to see
            // if there are any posts in the database that happened
            // after that date and, if so, we kill the cookie.

            if (self::getRecentPostTimestamp() > $_COOKIE["hide-happening-now-badge"]) {
                setcookie("hide-happening-now-badge", "", time()-1);
            }
        }
    }

    /**
     * setupThemeOptions
     *
     * After the theme is setup, add our options.
     *
     * @return void
     */
    protected function setupThemeOptions(): void {
        register_nav_menu("main-menu", "Main Menu");
        add_theme_support("post-thumbnails");
        register_sidebar([
            'name' => 'Subpage Sidebar',
            'id'   => 'subpage-sidebar',
            'description' => 'This sidebar displays on certain pages, such as the Lawsuits post page.'
        ]);
    }

    /**
     * enqueueStyles
     *
     * Adds the JW stylesheets to the DOM.
     *
     * @return void
     */
    protected function enqueuePublicAssets(): void {
        $this->enqueue("//apis.google.com/js/platform.js");
        $this->enqueue("/assets/scripts/vendor.js", ["platform"]);
        $this->enqueue("/assets/scripts/judicial-watch.js", ["platform", "vendor"]);
        $this->enqueue("/assets/styles/judicial-watch.css");
    }

    /**
     * enqueue
     *
     * Adds the script or style to the DOM.
     *
     * @param string           $file
     * @param array            $dependencies
     * @param string|bool|null $finalArg
     *
     * @return void
     */
    protected function enqueue(string $file, array $dependencies = [], $finalArg = null): void {
        $fileInfo = pathinfo($file);
        $isScript = $fileInfo["extension"] === "js";

        // the $function variable will be used as a variable function.  we
        // want to set it to either the WP function that enqueues scripts or
        // the one for styles.  then, we can call that function below using
        // $function().

        $function = $isScript ? "wp_enqueue_script" : "wp_enqueue_style";

        if (is_null($finalArg)) {

            // the final argument for our $function is either a Boolean or
            // a string for scripts and styles respectively.  if it's null
            // at the moment, we'll default it to the following.  otherwise,
            // we assume the calling scope knows what it's doing.

            $finalArg = $isScript ? true : "all";
        }

        // if the asset we're enqueuing begins with "//" then it's a remote
        // asset.  we don't want to prefix it with our local URL and DIR
        // values.  first, we replace the protocol designation just to be
        // sure it's not present for our test.

        $file = preg_replace("/^https?:/", "", $file);
        $isRemote = substr($file, 0, 2) === "//";

        // the include is either the $file itself or that prefixed by our
        // URL property.  but, for the version of that file, we use the last
        // modified timestamp for local files and this year and month for
        // remote ones.  that should force browsers to update their cache
        // at least once per month for remote assets.

        $include = !$isRemote ? ($this->url . $file) : $file;
        $version = !$isRemote ? filemtime($this->dir . $file) : date("Ym");
        $function($fileInfo["filename"], $include, $dependencies, $version, $finalArg);
    }

    /**
     * enqueueAdminStyles
     *
     * Adds the JW administrative styles to the DOM.
     *
     * @return void
     */
    protected function enqueueAdminAssets(): void {
        $this->enqueue("/assets/styles/judicial-watch-admin.css");
    }

    /**
     * addCronSchedulingOptions
     *
     * The original JW site added these options to the WP cron system.
     * We'll add them, too, so that we can use them if we need to.  If we
     * never do, we can always nix this method.
     *
     * @return array
     */
    protected function addCronSchedulingOptions(): array {
        return [
            "fivemin"    => ["interval" => 300, "display" => "Once Every 5 Minutes"],
            "hourly"     => ["interval" => 3600, "display" => "Once Hourly"],
            "twicedaily" => ["interval" => 43200, "display" => "Twice Daily"],
            "daily"      => ["interval" => 86400, "display" => "Once Daily"],
            "weekly"     => ["interval" => 604800, "display" => "Once Weekly"],
            "monthly"    => ["interval" => 2419200, "display" => "Once Monthly"],
            "quarterly"  => ["interval" => 9676800, "display" => "Once Quarterly"],
            "yearly"     => ["interval" => 29030400, "display" => "Once Yearly"],
        ];
    }
}