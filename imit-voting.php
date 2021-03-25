<?php


/**
 * Plugin Name:       IMIT Advance voting
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       This is a demo plugin.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Ideasy Corp.
 * Author URI:        https://ideasymind.com
 * License:           GPL v2 or later
 * License URI:       https://ideasymind.com
 * Text Domain:       imit-voting
 * Domain Path:       /languages
 */

define('IMIT_VOTE_DB_VERSION', '1.0');

/**
 * secure plugin
 */
if(!defined('ABSPATH')){
    exit;
}

/**
 * register text domain
 */
function imit_voting_textdomain(){
    load_plugin_textdomain('imit-voting', false, dirname(__FILE__).'/languages');
}

add_action('plugin_loaded', 'imit_voting_textdomain');

/**
 * create table when user active plugin
 */
function imit_vote_init(){
    global $wpdb;

    $voting_table_name = $wpdb->prefix.'imit_votes';
    $proposal_table_name = $wpdb->prefix.'imit_event_proposals';
    $comment_table_name = $wpdb->prefix.'imit_proposal_comments';
    $comment_like_table = $wpdb->prefix.'imit_comment_likes';
    $comment_replay_table = $wpdb->prefix.'imit_comment_replays';

    require_once (ABSPATH.'wp-admin/includes/upgrade.php');

    $sql[] = "CREATE TABLE {$proposal_table_name} (
        id BIGINT (20) NOT NULL AUTO_INCREMENT,
        proposal_title VARCHAR (250) NOT NULL,
        proposal_category VARCHAR (250) NOT NULL,
        proposal_description VARCHAR (5000) NOT NULL,
        event_id INT (20) NOT NULL,
        user_id INT (20) NOT NULL,
        tags VARCHAR (5000) NOT NULL,
        status VARCHAR (250) DEFAULT ('1'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    );";

    $sql[] = "CREATE TABLE {$voting_table_name} (
        id BIGINT (20) NOT NULL AUTO_INCREMENT,
        user_id INT (20) NOT NULL,
        proposal_id INT (20) NOT NULL,
        status VARCHAR (250) DEFAULT ('1'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    );";

    $sql[] = "CREATE TABLE {$comment_table_name} (
        id BIGINT (20) NOT NULL AUTO_INCREMENT,
        comment VARCHAR(2500) NOT NULL,
        user_id INT (20) NOT NULL,
        proposal_id INT (20) NOT NULL,
        status VARCHAR (250) DEFAULT ('1'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    );";

    $sql[] = "CREATE TABLE {$comment_like_table} (
        id BIGINT (20) NOT NULL AUTO_INCREMENT,
        comment_id INT (20) NOT NULL,
        user_id INT (20) NOT NULL,
        status VARCHAR (250) DEFAULT ('1'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    )";

    $sql[] = "CREATE TABLE {$comment_replay_table} (
        id BIGINT (20) NOT NULL AUTO_INCREMENT,
        comment_id INT (20) NOT NULL,
        user_id INT (20) NOT NULL,
        replay_text VARCHAR (250) NOT NULL,
        status VARCHAR (250) DEFAULT ('1'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    )";

    dbDelta($sql);

    add_option('imit_vote_db_version', IMIT_VOTE_DB_VERSION);
}

register_activation_hook(__FILE__, 'imit_vote_init');


/**
 * theme support add
 */

function imit_theme_supports(){

    /**
     * hide admin menu bar
     */
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }

    add_theme_support('post-thumbnails');
    /**
     * register post type
     */
    register_post_type( 'imitproposalcat', [
        'public'             => true,
        'labels' => [
                'name' => 'Proposal categories'
        ],
        'menu_icon' => 'dashicons-welcome-write-blog',
        'capabilities' => array(
            'edit_post'          => false,
            'read_post'          => false,
            'delete_post'        => false,
            'edit_others_posts'  => false,
            'publish_posts'      => false,
            'read_private_posts' => false,
            'create_posts'       => false,
        ),
    ]);

    /**
     * add new taxonomies
     */
    register_taxonomy( 'proposalcat', 'imitproposalcat', [
        'hierarchical'          => true,
        'labels' => [
                'name' => 'Categories'
        ]
    ] );

}

add_action('after_setup_theme', 'imit_theme_supports');


/**
 * add script for frontend
 */
function imit_voting_scripts(){
    wp_enqueue_style('imit-bootstrap', PLUGINS_URL('css/bootstrap.min.css', __FILE__));
    wp_enqueue_style('imit-fontawesome', PLUGINS_URL('css/all.min.css', __FILE__));
    wp_enqueue_style('imit-stylesheet', PLUGINS_URL('css/style.css', __FILE__));

    wp_enqueue_script('imit-jQuery', PLUGINS_URL('js/jquery-3.6.0.min.js', __FILE__), [], true, true);
    wp_enqueue_script('imit-bootstrap-popper', PLUGINS_URL('js/popper.min.js', __FILE__), ['imit-jQuery'], true, true);
    wp_enqueue_script('imit-bootstrap-js', PLUGINS_URL('js/bootstrap.min.js', __FILE__), ['imit-jQuery'], true, true);
    wp_enqueue_script('imit-custom-js', PLUGINS_URL('js/custom.js', __FILE__), ['imit-jQuery'], true, true);

    /**
     * if user submit proposal button
     */
    $proposal_nonce= wp_create_nonce('imit_create_proposal');
    wp_localize_script('imit-custom-js', 'imitProposalData', [
       'ajax_url' => admin_url('admin-ajax.php'),
        'imit_porposal_nonce' => $proposal_nonce,
        'is_login' => is_user_logged_in()
    ]);

    /**
     * for create vote
     */
    $vote_nonce = wp_create_nonce('imit_create_vote');
    wp_localize_script('imit-custom-js', 'imitVoteData', [
       'ajax_url' => admin_url('admin-ajax.php'),
       'imit_vote_nonce' => $vote_nonce,
        'is_login' => is_user_logged_in()
    ]);

    /**
     * fetch single proposal
     */
    $single_proposal_nonce = wp_create_nonce('imit_single_proposal_show');
    wp_localize_script('imit-custom-js', 'imitSingleProposal', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_single_proposal_nonce' => $single_proposal_nonce,
        'is_login' => is_user_logged_in()
    ]);

    /**
     * proposal ajax search
     */
    $proposal_ajax_search = wp_create_nonce('imit_ajax_search');
    wp_localize_script('imit-custom-js', 'imitAjaxSearch', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_ajax_search_nonce' => $proposal_ajax_search,
    ]);

    /**
     * comment add
     */
    $proposal_comment_nonce = wp_create_nonce( 'proposal_comment_create' );
    wp_localize_script( 'imit-custom-js', 'imitCommentAdd', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_comment_create_nonce' => $proposal_comment_nonce
    ] );

    /**
     * add new user
     */
    $wordpress_user = wp_create_nonce( 'add_wordpress_user' );
    wp_localize_script( 'imit-custom-js', 'imitCreateUser', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_create_wordpress_user_nonce' => $wordpress_user
    ] );

    /**
     * login user
     */
    $wordpress_user_login = wp_create_nonce('user_login_nonce');
    wp_localize_script( 'imit-custom-js', 'imitLogin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_login_nonce' => $wordpress_user_login,
        'redirect_to' => site_url().'/dashboard'
    ] );
    
    /**
     * proposal comment like
     */
    $create_proposal_comment_like = wp_create_nonce( 'create_proposal_like_n' );
    wp_localize_script( 'imit-custom-js', 'imitCreateLike', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_proposal_like_create_nonce' => $create_proposal_comment_like
    ] );

    /**
     * proposal comment replay create
     */
    $proposal_comment_replay = wp_create_nonce( 'proposal_comment_replay_n' );
    wp_localize_script( 'imit-custom-js', 'imitCreateReplay', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'imit_proposal_comment_replay_nonce' => $proposal_comment_replay
    ] );
}

add_action('wp_enqueue_scripts', 'imit_voting_scripts');

/**
 * shortcode for show proposals
 */
add_shortcode('imit-voting', function(){
    ob_start();
    global $wpdb;
    $event_id = get_the_ID();
    $all_proposals = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE status = '1' AND event_id = '{$event_id}' ORDER BY id DESC");
    $get_event_proposals = $wpdb -> get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}'");
    $get_event_votes = $wpdb -> get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id IN (SELECT id FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}')");
    $participates = $wpdb -> get_results("SELECT DISTINCT user_id FROM wp_imit_votes WHERE proposal_id IN (SELECT id FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}') UNION SELECT DISTINCT user_id FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}'");
                
    $event_data = get_field('event_step');
    ?>
    <section class="event-banner p-0" style="background-image: url(<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>);">
        <div class="overlay text-center">
            <h3 class="event-title mb-5 text-white mt-0"><?php the_title(); ?></h3>
            <p class="event-subtitle mb-5 text-white"><?php echo get_field('page_subtitle'); ?></p>
            <ul class="d-flex flex-sm-row flex-column justify-content-center align-items-center mb-4 text-white ff-poppins">
                <li class="d-flex flex-sm-row mb-sm-0 mb-3 flex-column justify-content-start align-items-center">
                    <i class="fas fa-file-signature me-2"></i>
                    <span class="me-2 fz-16"><?php echo count($get_event_proposals); ?></span>
                    <span class="me-3 fz-16">Contributions</span>
                </li>
                <li class="d-flex flex-sm-row mb-sm-0 mb-3 flex-column justify-content-start align-items-center">
                    <i class="fas fa-thumbs-up me-2"></i>
                    <span class="me-2 fz-16"><?php echo count($get_event_votes); ?></span>
                    <span class="me-3 fz-16">Votes</span>
                </li>
                <li class="d-flex flex-sm-row mb-sm-0 mb-3 flex-column justify-content-start align-items-center">
                    <i class="fas fa-users me-2"></i>
                    <span class="me-2 fz-16"><?php echo count($participates); ?></span>
                    <span class="fz-16">Participants</span>
                </li>
            </ul>
            <div class="d-flex flex-row justify-content-center align-items-center">
                <?php echo do_shortcode('[DISPLAY_ULTIMATE_SOCIAL_ICONS]'); ?>
            </div>
            
        </div>
    </section>

    <section class="event-progress-menu">
        <ul class="menu-wrapper mb-0 ps-0 overflow-hidden" id="myTab" role="tablist">
            <div class="container d-flex flex-row justify-content-start align-items-center" style="overflow-x: auto;overflow-y: hidden">
                
            <?php 
            $i = 1;
            foreach($event_data as $event){
                ?>
                <li class="menu-list">
                    <a href="#" data-target="<?php echo $event['step_id']; ?>" class="menu-link imit-custom-tab-link d-flex flex-row justify-content-center align-items-center <?php if($event['is_active'] == 'yes'){echo 'active';} ?>" id="imit-custom-tab">
                        <span class="counter me-3 ff-poppins"><?php echo $i;$i++; ?></span>
                        <span>
                            <span class="link-title fz-16 d-block"><?php echo $event['step_title']; ?></span>
                            <span class="badge fz-14 ff-poppins d-table"><?php if($event['is_active'] == 'yes'){echo 'En cours';}else{echo 'À venir';} ?></span>
                        </span>
                    </a>
                </li>
                <?php
            } ?>
            </div>
        </ul>
    </section>

    
            <?php foreach($event_data as $event){
                if($event['template_type'] == 'PROPOSER UNE IDÉE'){
                    ?>
                   <section class="event-info ff-poppins bg-light pt-5" style="display: <?php if($event['is_active'] == 'yes'){echo 'block';}else{echo 'none';} ?>" id="<?php echo $event['step_id']; ?>">
                        <div class="container">
                            <h3 class="title primary-color mb-4">Évènements </h3>
                            <div class="border rounded-3 bg-white p-3 event-details">
                                <a href="#" class="event-title mb-3  fz-16 primary-color text-decoration-none d-block"><i class="fas fa-users me-2"></i><?php echo $event['event_title']; ?></a>
                                <p class="event-date text-secondary mb-2 fz-16 "><i class="fas fa-calendar-alt me-2"></i><?php echo $event['event_date']; ?> - past</p>
                                <p class="event-location text-secondary  fz-16 mb-0"><i class="fas fa-microphone-alt me-2"></i><?php echo $event['event_location']; ?></p>
                            </div>

                            <h3 class="title primary-color my-4"><?php echo $event['step_heading']; ?></h3>
                            <div class="d-flex flex-row justify-content-start align-items-center">
                                <p class="me-3 fz-16"><i class="fas fa-calendar-alt me-2"></i>From <?php echo $event['project_start']; ?> to <?php echo $event['project_end']; ?></p>
                                <p class="fz-16"><i class="fas fa-hourglass-end me-2"></i><?php 
                                    $future = strtotime($event['project_end']); //Future date.
                                    $timefromdb = strtotime('now');
                                    $timeleft = $future-$timefromdb;
                                    $daysleft = round((($timeleft/24)/60)/60); 
                                    echo $daysleft;
                                ?> days left</p>
                            </div>
                            <div class="border rounded-3 ff-poppins fz-16 bg-white p-3 event-details">
                                <?php echo $event['step_description']; ?>
                            </div>

                            <div class="vote-header my-3">
                                <div class="row">
                                    <div class="col-sm-6 text-sm-start text-center">
                                        <h3 class="contributor-counter primary-color ff-poppins m-0" id="proposal-counter"><?php echo count($all_proposals); ?> propositions</h3>
                                    </div>
                                    <div class="col-sm-6 text-sm-end text-center">
                                        <a href="#" class="btn primary-bg text-white btn-sm rounded-0 ff-poppins fz-16" data-bs-toggle="modal" data-bs-target="<?php if(is_user_logged_in()){echo '#submit-proposal';}else{echo '#login-modal';} ?>"><i class="fas fa-plus me-2"></i> Déposer une proposition</a>
                                    </div>
                                </div>
                            </div>
                            <div class="vote-filter">
                                <form method="POST" id="proposal-ajax-searching" data-event_id="<?php echo get_the_ID();?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="position-relative">
                                                <i class="fas fa-search custom-search-icon"></i>
                                                <input type="text" name="tag" class="form-control form-control-sm ps-5 mb-md-0 mb-3 ff-poppins fz-16" placeholder="Mots-clés ou référence" style="padding: 0.36rem 0.66rem 0.36rem 2rem !important;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="sort" id="" class="form-select form-select-sm mb-md-0 mb-3 ff-poppins fz-16">
                                                <option value="rand" selected>Random sort</option>
                                                <option value="recent">The most recent</option>
                                                <option value="oldest">The oldest</option>
                                                <!-- <option value="most_commented">Most commented</option>
                                                <option value="voted">Most voted</option> -->
                                                <option value="latest_voted">The latest voted</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="category" id="" class="form-select form-select-sm ff-poppins fz-16">
                                                <option value="all" selected>All categories</option>
                                                <?php
                                                $taxonomies = get_terms( array(
                                                    'taxonomy' => 'proposalcat',
                                                    'hide_empty' => false
                                                ) );
                                                foreach($taxonomies as $pcat):
                                                    ?>
                                                    <option value="<?php echo $pcat->slug; ?>"><?php echo $pcat->name; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="vote-body pb-5">
                                <div class="row" id="fetch-proposals">

                                    <?php
                                    foreach($all_proposals as $proposal):
                                        $user_data = get_userdata($proposal->user_id);
                                        $proposal_category_info = get_term_by('slug', $proposal->proposal_category, 'proposalcat');
                                        $get_category_image = get_field('proposal_category_image', $proposal_category_info);
                                    ?>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="card mt-3 rounded-0">
                                            <div class="card-body p-0">
                                                <div class="ratio ratio-21x9 overflow-hidden">
                                                    <img src="<?php echo $get_category_image; ?>" alt="">
                                                </div>
                                                <div class="p-3">
                                                    <div class="user-info d-flex flex-row justify-content-start align-items-center border-bottom pb-3">
                                                        <?php echo get_avatar($proposal->user_id, '96', '', '', ['class' => 'user-image']); ?>
                                                        <div class="profile-info ms-2">
                                                            <p class="username mb-0"><?php echo $user_data->user_login; ?></p>
                                                            <span class="text-secondary d-block"><?php echo date('F d, Y', strtotime($proposal->created_at));//human_time_diff( strtotime( $proposal->created_at ), current_time( 'timestamp') );; ?></span>
                                                        </div>
                                                    </div>
                                                    <a href="<?php echo site_url().'/single-page/?id='.$proposal->id; ?>" class="primary-color custom-title mt-3 d-block"><?php echo $proposal->proposal_title; ?></a>
                                                    <p class="text-secondary custom-text mt-2"><?php echo wp_trim_words($proposal->proposal_description, 20, false); ?></p>
                                                    <div class="tags fz-16 ff-poppins"><i class="fas fa-tags me-2"></i><?php
                                                        // $all_tags = array_slice(json_decode($proposal->tags), 0, 5);
                                                        // echo implode(', ', $all_tags);
                                                        echo str_replace('-', ', ', $proposal->proposal_category);
                                                        ?></div>
                                                    <?php
                                                    if(is_user_logged_in()){
                                                        $user_id = get_current_user_id();
                                                        $all_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal->id}' AND user_id = '{$user_id}'");
                                                        if(count($all_votes) > 0){
                                                            ?>
                                                            <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 disabled"><i class="fas fa-thumbs-up me-2"></i> Voated</a>
                                                            <?php
                                                        }else{
                                                            ?>
                                                            <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 vote-button<?php echo $proposal->id; ?>" data-bs-toggle="modal" data-bs-target="#vote-modal" data-proposal_id="<?php echo $proposal->id; ?>" id="create-vote"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                                                            <?php
                                                        }
                                                    }else{
                                                        ?>
                                                        <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16" data-bs-toggle="modal" data-bs-target="#login-modal"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                                                        <?php
                                                    }
                                                    ?>

                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="row">
                                                    <div class="col-6 text-center border-end fz-14 ff-poppins">
                                                        <span class="d-block"><?php 
                                                        $proposal_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_proposal_comments WHERE proposal_id = '{$proposal->id}'");
                                                            echo count($proposal_votes);
                                                            ?></span>
                                                        <span>commentaire</span>
                                                    </div>
                                                    <div class="col-6 text-center fz-14 ff-poppins">
                                                        <span class="d-block" id="votecounter<?php echo $proposal->id; ?>"><?php
                                                            $proposal_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal->id}'");
                                                            echo count($proposal_votes);
                                                            ?></span>
                                                        <span>votes</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>



                                </div>
                            </div>
                        </div>
                    </section>
                    <?php
                }else if($event['template_type'] == 'DONNER VOTRE AVIS'){
                    ?>
                <section class="event-info bg-light pt-5 ff-poppins" style="display: <?php if($event['is_active'] == 'yes'){echo 'block';}else{echo 'none';} ?>" id="<?php echo $event['step_id']; ?>">
                        <div class="container">
                            <div class="alert alert-info mb-4 fz-16 custom-alert"><?php echo $event['step_alert']; ?></div>
                            <h3 class="title text-center primary-color mb-4"><?php echo $event['step_heading']; ?></h3>
                            <p class="text-center"><i class="fas fa-calendar-alt me-2"></i>Du <?php echo $event['project_start']; ?> au <?php echo $event['project_end']; ?> avr.</p>
                            <div class="d-flex flex-row fz-16 mb-3 justify-content-center align-items-center">
                                <div><i class="fas fa-comment me-2"></i><?php echo count($get_event_proposals); ?> contribution</div>
                                <div><i class="fas fa-thumbs-up ms-2 me-2"></i><?php echo count($get_event_votes); ?> vote</div>
                                <div><i class="fas fa-user ms-2 me-2"></i><?php echo count($participates); ?> participant</div>
                            </div>
                            <div class="p-2 border bg-white mx-auto rounded-3 fz-16 ff-poppins custom-alert" style="max-width: 913px;">
                                <?php echo $event['step_description']; ?>
                            </div>
                            <h3 class="text-center m-0 py-4"><?php echo $event['event_title']; ?></h3>
                        </div>
                    </section>
                    <?php
                }else if($event['template_type'] == 'DÉCIDER DES PRIORITÉS'){
                    ?>
                <section class="event-info bg-light pt-5 ff-poppins pb-4" style="display: <?php if($event['is_active'] == 'yes'){echo 'block';}else{echo 'none';} ?>" id="<?php echo $event['step_id']; ?>">
                        <div class="container">
                            <div class="alert alert-info mb-4 custom-alert fz-16"><?php echo $event['step_alert']; ?></div>
                            <h3 class="title primary-color mb-4"><?php echo $event['step_heading']; ?></h3>
                            <p class="fz-16"><i class="fas fa-calendar-alt me-2"></i>Du <?php echo $event['project_start']; ?> au <?php echo $event['project_end']; ?></p>
                            <div class="p-2 border bg-white mx-auto rounded-3 fz-16 custom-alert">
                                <?php echo $event['step_description']; ?>
                            </div>

                            <div class="p-4 border bg-white mx-auto rounded-3 mt-4">
                                <h4 class="primary-bg text-white rounded-3 p-2"><?php echo $event['event_title']; ?></h4>
                                <hr>
                                <div class="d-flex flex-row justify-content-start align-items-center mt-2">
                                    <a href="#" class="btn fz-16 primary-bg disabled text-white me-2 rounded-0">Enregistrer le brouillon</a>
                                    <a href="#" class="btn fz-16 primary-bg disabled text-white rounded-0">Envoyer</a>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php
                }
            } ?>

    

    <?php if(is_user_logged_in()): ?>
    <div class="modal fade" id="submit-proposal">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="ff-poppins fz-16 m-0">Submit a proposal</h3>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <form id="proposal-submit" data-event_id="<?php echo get_the_ID(); ?>" method="POST">
                    <div class="modal-body">
                        <div id="proposal-add-message"></div>
                        <label for="" class="form-label ff-poppins fz-16">Proposal title</label>
                        <input type="text" class="form-control ff-poppins fz-16" name="proposal_title" placeholder="Enter porposal title">

                        <label for="" class="forn-label ff-poppins fz-16">Proposal category</label>
                        <select name="proposal-category" id="" class="form-select ff-poppins fz-16">
                            <?php
                            foreach($taxonomies as $pcat):
                            ?>
                            <option value="<?php echo $pcat->slug; ?>"><?php echo $pcat->name; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="" class="ff-poppins fz-16 form-label">Desctiption</label>
                        <textarea name="proposal-description" class="form-control ff-poppins fz-16" id="" cols="30" rows="10"></textarea>
                    </div>
                    <div class="modal-footer">
                        <div class="btn-group">
                            <button class="btn btn-light fz-16 ff-poppins text-dark" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn primary-bg text-white fz-16 ff-poppins" type="submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="vote-modal">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="ff-poppins fz-16 primary-color m-0">Vote for</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="submit-vote">
                    <div class="modal-body">
                        <div id="imit-vote-message"></div>
                        <p class="ff-poppins fz-16">1 Vote</p>
                        <div class="bg-light rounded-3 p-3">
                            <a href="#" class="fz-16 text-dark ff-poppins text-decoration-none" id="proposal-title"></a>
                            <p class="ff-poppins fz-14 mb-0">New vote</p>
                            <div class="d-flex flex-row justify-content-start align-items-center mt-2">
                                <div class="form-check form-switch me-2">
                                    <input class="form-check-input" name="vote-privacy" value="private" type="checkbox" id="flexSwitchCheckChecked" checked>
                                </div>
                                <span class="badge bg-danger fz-14 ff-poppins" id="proposal-privacy-status">Private</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="btn-group">
                            <button type="button" class="btn btn-light fz-16 ff-poppins" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    else:
        ?>
        <div class="modal fade" id="login-modal">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <p class="primary-color mb-0 fz-16 ff-poppins">Connectez-vous pour contribuer</p>
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <a href="<?php echo home_url( '/'); ?>/register" class="btn btn-light text-dark d-block mb-2 ff-poppins fz-16">Register</a>
                        <a href="<?php echo home_url( '/'); ?>/login" class="btn btn-success text-white d-block ff-poppins fz-16">Login</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endif;
    return ob_get_clean();
});

/**
 * add proposal
 */
function submit_proposal_for_event(){
    $action = 'imit_create_proposal';
    $nonce = $_POST['nonce'];
    if(wp_verify_nonce($nonce, $action)){
        $proposal_title = sanitize_text_field($_POST['proposal_title']);
        $proposal_description = sanitize_text_field($_POST['proposal_description']);
        $proposal_category = sanitize_text_field($_POST['proposal_category']);
        if(empty($proposal_title) || empty($proposal_description) || empty($proposal_category)){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
                      <strong>Warning!</strong> All fields are required.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }else{
            global $wpdb;
            $table_name = $wpdb->prefix.'imit_event_proposals';
            $title_tag = explode(' ', sanitize_text_field($_POST['proposal_title']));
            $description_tag = explode(' ', sanitize_text_field($_POST['proposal_description']));
            $tags = array_merge($title_tag, $description_tag);
            $db_tags = json_encode($tags);
            $wpdb->insert($table_name, [
                'proposal_title' => $proposal_title,
                'proposal_category' => $proposal_category,
                'proposal_description' => $proposal_description,
                'event_id' => sanitize_text_field($_POST['event_id']),
                'user_id' => get_current_user_id(),
                'tags' => $db_tags
            ]);
            echo '<div class="ff-poppins fz-16 alert alert-success alert-dismissible fade show" role="alert">
                      <strong>Success!</strong> Proposal accepted.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
    }
    die();
}

add_action('wp_ajax_imit_proposal_submit', 'submit_proposal_for_event');

/**
 * get proposal by id
 */
function get_proposal_by_id(){
    global $wpdb;

    $table_name = $wpdb->prefix.'imit_event_proposals';
    if(wp_verify_nonce($_POST['nonce'], 'imit_single_proposal_show')){
        $proposal_id = sanitize_text_field($_POST['proposal_id']);

        $proposal = $wpdb->get_results("SELECT * FROM {$table_name} WHERE id = '{$proposal_id}'");

        echo json_encode($proposal);
    }
    die();
}

add_action('wp_ajax_single_proposal_show', 'get_proposal_by_id');

/**
 * create vote
 */
function imit_create_vote(){
    global $wpdb;

    if(wp_verify_nonce($_POST['nonce'], 'imit_create_vote')){
        $table_name = $wpdb->prefix.'imit_votes';
        $proposal_id = sanitize_text_field($_POST['proposal_id']);
        $status = sanitize_text_field($_POST['status']);
        $user_id = get_current_user_id();
        $check_vote = $wpdb->get_results("SELECT * FROM {$table_name} WHERE proposal_id = '{$proposal_id}' AND user_id = '{$user_id}'");

        if(empty($proposal_id) || empty($status)){
            echo '<div class="ff-poppins fz-16 alert alert-danger alert-dismissible fade show" role="alert">
                      <strong>Stop!</strong> Something went wrong, please try again later.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }else if(count($check_vote) > 0){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
                      <strong>Warning!</strong> You aren\'t allowed to do this.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }else{
            $wpdb->insert($table_name, [
               'user_id' => get_current_user_id(),
               'proposal_id' => $proposal_id,
               'status' => $status,
            ]);
            echo '<div class="ff-poppins fz-16 alert alert-success alert-dismissible fade show" role="alert">
                      <strong>Success!</strong> Vote added successful.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
    }
    die();
}

add_shortcode('imit-proposal-single-page', function(){
    ob_start();
    if(isset($_GET['id']) && !empty($_GET['id'])){
        global $wpdb;
        $proposal_id = $_GET['id'];
        $proposal_result = $wpdb -> get_row("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE id = '{$proposal_id}'");
        $user_data = get_userdata($proposal_result->user_id);
        global $all_votes;
        $all_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal_result->id}'");
        ?>
        <section class="single-page border-bottom">
            <div class="border-bottom">
                <div class="container">
                    <div class="banner mt-4 position-relative">
                        <div class="ratio ratio-21x9 overflow-hidden">
                        <?php 
                        $proposal_category_info = get_term_by('slug', $proposal_result->proposal_category, 'proposalcat');
                        $get_category_image = get_field('proposal_category_image', $proposal_category_info);
                        ?>
                            <img src="<?php echo $get_category_image; ?>" alt="" class="img-fluid rounded-3">
                        </div>
                        <a href="<?php echo esc_url( get_permalink($proposal_result->event_id) ); ?>" class="custom-back fz-16 ff-poppins"><i class="fas fa-angle-left me-2"></i>Liste des propositions</a>
                    </div>
                    <h3 class="custom-title mt-3"><?php echo $proposal_result->proposal_title; ?></h3>
                    <div class="user-info d-flex flex-row justify-content-start align-items-center my-3">
                        <?php echo get_avatar($user_data->id, 96, '', '', ['class'=>'rounded-circle']); ?>
                        <div class="profile-data ms-3">
                            <p class="username mb-0 fz-16 ff-poppins"><?php echo $user_data->user_login; ?></p>
                            <span class="text-secondary d-block ff-poppins"><?php echo date('F d, Y', strtotime($proposal_result->created_at)); ?></span>
                        </div>
                    </div>
                    <?php
                            if(is_user_logged_in()){
                            $user_id = get_current_user_id();
                            $vote = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal_result->id}' AND user_id = '{$user_id}'");
                            if(count($vote) > 0){
                            ?>
                            <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 ff-poppins disabled"><i class="fas fa-thumbs-up me-2"></i> Voated</a>
                                    <?php
                                }else{
                                    ?>
                                    <a href="#" class="btn btn-success text-white btn-sm mt-3  ff-poppins fz-16 vote-button<?php echo $proposal_result->id; ?>" data-bs-toggle="modal" data-bs-target="#vote-modal" data-proposal_id="<?php echo $proposal_result->id; ?>" id="create-vote"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                                    <?php
                                }
                            }else{
                                ?>
                                <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 ff-poppins" data-bs-toggle="modal" data-bs-target="#login-modal"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                                <?php
                            }
                    ?>

                    <ul class="nav nav-tabs mt-3 border-bottom-0" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button style="border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important;" class="nav-link text-dark active" id="home-tab" data-bs-toggle="tab" data-bs-target="#presentation" type="button" role="tab" aria-controls="presentation" aria-selected="true">Présentation</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button style="border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important;" class="nav-link text-dark" id="profile-tab" data-bs-toggle="tab" data-bs-target="#vote" type="button" role="tab" aria-controls="vote" aria-selected="false">Vote <?php if(count($all_votes) > 0){echo '<span class="badge bg-info">'.count($all_votes).'</span>';} ?></button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content bg-light" id="myTabContent">
                <div class="tab-pane fade show active" id="presentation" role="tabpanel" aria-labelledby="presentation-tab">
                    <div class="container py-3">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="description shadow rounded-3 bg-white">
                                    <div class="title mb-3"><i class="fas fa-file-alt me-2 shadow rounded-circle bg-white"></i>Description</div>
                                    <p class="text"><?php echo $proposal_result->proposal_description; ?></p>
                                </div>
                                <div class="comment-box shadow rounded-3 bg-white mt-3">
                                    <div class="title mb-3"><i class="fas fa-comments me-2 shadow rounded-circle bg-white"></i>Discussions</div>
                                    <span class="text-secondary fz-16 ff-poppins"><?php 
                                    $all_comments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_proposal_comments WHERE proposal_id = '{$proposal_id}' ORDER BY id DESC");
                                    echo count($all_comments);
                                    ?> comment</span>
                                    <?php if(is_user_logged_in(  )): ?>
                                    <div id="comment-message"></div>
                                    <div class="row mt-3">                                        
                                        <div class="col-sm-2 mb-sm-0 mb-2 pe-0">
                                            <div class="wrapper">
                                                <?php 
                                                $logged_in_user = wp_get_current_user();

                                                echo get_avatar( $logged_in_user->id, 96, '', '', ['class' => 'rounded-circle']);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-sm-10 ps-sm-0 ps-2">
                                            <form method="POST" class="w-100 col-sm-10" id="add_comment_on_proposal" data-proposal_id="<?php echo $proposal_result->id; ?>">
                                                <textarea name="proposal-comment" id="" placeholder="Leave a comment" class="form-control fz-16 ff-poppins"></textarea>
                                                <button type="submit" class="btn primary-bg text-white ms-auto mt-3 d-block fz-16 ff-poppins">Post Comment</button>
                                            </form>
                                        </div>                            
                                    </div>
                                    <?php endif; ?>
                                    <ul class="list-group mt-3">

                                        <?php 
                                        

                                        foreach($all_comments as $proposal_comment):
                                        $comment_user = get_userdata($proposal_comment->user_id);
                                        ?>
                                        <li class="list-group-item border-0">
                                            <div class="d-flex flex-row justify-content-start align-items-start">
                                                <div class="wrapper">
                                                    <?php echo get_avatar($comment_user->id, 96, '', '', ['class'=>'rounded-circle']); ?>
                                                </div>
                                                <div class="user-info ms-1 p-3 bg-light w-100 rounded-2">
                                                    <p class="username mb-0 fw-bold fz-16 ff-poppins"><?php echo $comment_user->user_login; ?> <span class="fw-light fz-14 ms-2"><?php echo date('F d, Y', strtotime($proposal_comment->created_at)); ?></span></p>
                                                    <div class="comment ps-0">
                                                        <p class="text-secondary my-2 fz-16 ff-poppins"><?php echo $proposal_comment->comment; ?></p>
                                                        <div class="d-flex flex-row justify-content-start align-items-center">
                                                            <?php 
                                                            $user_id = get_current_user_id();
                                                            $comment_likes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_comment_likes WHERE comment_id = '{$proposal_comment->id}' AND user_id = '{$user_id}'");
                                                            if(count($comment_likes) > 0){
                                                                ?>
                                                                <a href="#" class="text-decoration-none d-block fz-16 ff-poppins primary-color me-3" data-comment_id="<?php echo $proposal_comment->id; ?>" id="dislike-proposal-comment"><i class="fas fa-thumbs-up"></i> <span class="ms-1 me-1 ff-poppins fz-16" id="like-counter<?php echo $proposal_comment->id; ?>"><?php echo count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_comment_likes WHERE comment_id = '{$proposal_comment->id}'")); ?></span> <span id="like-text">Liked</span></a>
                                                                <?php
                                                            }else{
                                                                ?>
                                                                <a href="#" class="text-secondary text-decoration-none d-block fz-16 ff-poppins me-3" data-comment_id="<?php echo $proposal_comment->id; ?>" id="like-proposal-comment"><i class="fas fa-thumbs-up"></i> <span class="ms-1 me-1 ff-poppins fz-16" id="like-counter<?php echo $proposal_comment->id; ?>"><?php echo count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_comment_likes WHERE comment_id = '{$proposal_comment->id}'")); ?></span> <span id="like-text">Like</span></a>
                                                                <?php
                                                            }
                                                            ?>
                                                            
                                                            <a href="#" class="text-secondary text-decoration-none d-block fz-16 ff-poppins" id="replay-comment" data-comment_id="<?php echo $proposal_comment->id; ?>"><i class="fas fa-reply"></i> Replay</a>
                                                        </div>
                                                        <ul class="list-group mt-3">

                                                        <?php 
                                                        $all_replay= $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_comment_replays WHERE comment_id = '{$proposal_comment->id}' ORDER BY id DESC");

                                                        foreach($all_replay as $replay_data):
                                                            $replay_user = get_userdata($replay_data->user_id);
                                                            ?>
                                                            <li class="list-group-item border-0 bg-light">
                                                                <div class="d-flex flex-row justify-content-start align-items-start">
                                                                    <div class="wrapper">
                                                                        <?php echo get_avatar($replay_user->id, 96, '', ''); ?>
                                                                    </div>
                                                                    <div class="user-info ms-3 bg-light w-100 rounded-2">
                                                                        <p class="username mb-0 fw-bold fz-16 ff-poppins"><?php echo $replay_user->user_login; ?> <span class="fw-light fz-14 ms-2"><?php echo date('F d, Y', strtotime($replay_data->created_at)); ?></span></p>
                                                                        <div class="comment ps-0">
                                                                            <p class="text-secondary my-2 fz-16 ff-poppins"><?php echo $replay_data->replay_text; ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                        <?php if(is_user_logged_in(  )): ?>
                                                        <form class="comment-replay replay<?php echo $proposal_comment->id; ?>" id="replay-comment-form" style="display:none;" data-comment_id="<?php echo $proposal_comment->id; ?>">
                                                            <div id="replay-message<?php echo $proposal_comment->id; ?>"></div>
                                                            <label for="replay-text" class="form-label fz-16 ff-poppins fw-bold">Replay</label>
                                                            <textarea class="form-control fz-16 ff-poppins" name="replay-text<?php echo $proposal_comment->id; ?>" id="replay-text" cols="1" rows="3" placeholder="Leave a replay"></textarea>
                                                            <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins mt-2">Replay</button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-lg-4 right-sidebar mt-lg-0 mt-3">
                                <div class="tags rounded-3 bg-white shadow p-3">
                                    <ul class="list-group">
                                        <li class="list-group-item border-0 fz-16 ff-poppins">
                                            <i class="fas fa-tag primary-color shadow-sm me-2 rounded-circle bg-white hash-icon"></i>
                                            <span><?php
                                                $all_tags = array_slice(json_decode($proposal_result->tags), 0, 3);
                                                echo implode(', ', $all_tags);
                                                ?></span>
                                        </li>
                                        <li class="list-group-item border-0 fz-16 ff-poppins">
                                            <i class="fas fa-hashtag primary-color shadow-sm me-2 rounded-circle bg-white hash-icon"></i>
                                            <span>0-<?php echo count(json_decode($proposal_result->tags)); ?></span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="custom-progress bg-white shadow p-4 mt-3 rounded-3">
                                    <h3 class="title mb-4">État d'avancement</h3>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex flex-row justify-content-start align-items-start p-0 fz-16 ff-poppins">
                                            <div class="progress-bullet me-3">
                                                <span class="bullet rounded-circle active"></span>
                                            </div>
                                            <div class="progress-content">
                                                <p class="mb-0">Partagez vos idées pour l’Occitanie !</p>
                                                <span>11 feb. 2021 - 30 Apr 2021</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item d-flex flex-row justify-content-start align-items-start p-0 fz-16 ff-poppins">
                                            <div class="progress-bullet me-3">
                                                <span class="bullet rounded-circle"></span>
                                            </div>
                                            <div class="progress-content">
                                                <p class="mb-0">Prononcez-vous sur les propositions !</p>
                                                <span>Apr 1, 2021 - Apr 15, 2021</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item d-flex flex-row justify-content-start align-items-start p-0 fz-16 ff-poppins">
                                            <div class="progress-bullet me-3">
                                                <span class="bullet rounded-circle"></span>
                                            </div>
                                            <div class="progress-content">
                                                <p class="mb-0">Exprimez vos choix sur le programme d’actions !</p>
                                                <span>Apr 15, 2021 - Apr 30, 2021</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="vote-counter mt-3 shadow rounded-3 bg-white p-3">
                                    <div class="title fz-16 ff-poppins">Soutenez cette proposition</div>
                                    <div class="d-flex flex-row justify-content-start align-items-center mt-3 fz-16 ff-poppins">
                                        <i class="fas fa-thumbs-up me-2 shadow"></i>
                                        <span><?php
                                            echo count($all_votes);
                                            ?> votes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade py-4" id="vote" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="voter-list container">
                        <h3 class="title"><i class="fas fa-thumbs-up shadow-sm"></i> <?php echo count($all_votes); ?> Votes</h3>
                        <div class="row">
                            <?php
                            $all_active_vote = $wpdb->get_results("SELECT DISTINCT user_id FROM {$wpdb->prefix}imit_votes INNER JOIN {$wpdb->prefix}users ON {$wpdb->prefix}imit_votes.user_id = {$wpdb->prefix}users.id WHERE {$wpdb->prefix}imit_votes.proposal_id = {$proposal_result->id} AND {$wpdb->prefix}imit_votes.status = '1'");
                            foreach($all_active_vote as $user_vote){
                                ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                                    <div class="voter bg-white d-flex flex-row justify-content-start align-items-start border p-3">
                                        <div class="profile-image">
                                            <?php echo get_avatar($user_vote->user_id, '96', '', ''); ?>
                                        </div>
                                        <div class="profile-info ms-3">
                                            <p class="mb-0 fz-16 ff-poppins"><?php
                                                $user_data = get_userdata($user_vote->user_id);
                                                echo $user_data->user_login;
                                                ?></p>
                                            <span class="fz-14 ff-poppins d-block"><?php
                                                $contribution = $wpdb -> get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE user_id = '{$user_vote->user_id}'");
                                                echo count($contribution);
                                                ?> contributions • <?php
                                                $user_votes = $wpdb -> get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE user_id = '{$user_vote->user_id}' AND status = '1' OR status = '2'");
                                                echo count($user_votes);
                                                ?> votes</span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
 if(is_user_logged_in()): ?>
        <div class="modal fade" id="vote-modal">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="ff-poppins fz-16 primary-color m-0">Vote for</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="submit-vote">
                        <div class="modal-body">
                            <div id="imit-vote-message"></div>
                            <p class="ff-poppins fz-16">1 Vote</p>
                            <div class="bg-light rounded-3 p-3">
                                <a href="#" class="fz-16 text-dark ff-poppins text-decoration-none" id="proposal-title"></a>
                                <p class="ff-poppins fz-14 mb-0">New vote</p>
                                <div class="d-flex flex-row justify-content-start align-items-center mt-2">
                                    <div class="form-check form-switch me-2">
                                        <input class="form-check-input" name="vote-privacy" value="private" type="checkbox" id="flexSwitchCheckChecked" checked>
                                    </div>
                                    <span class="badge bg-danger fz-14 ff-poppins" id="proposal-privacy-status">Private</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="btn-group">
                                <button type="button" class="btn btn-light fz-16 ff-poppins" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        else:
            ?>
            <div class="modal fade" id="login-modal">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <p class="primary-color mb-0 fz-16 ff-poppins">Connectez-vous pour contribuer</p>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <a href="#" class="btn btn-light text-dark d-block mb-2 ff-poppins fz-16">Register</a>
                            <a href="#" class="btn btn-success text-white d-block ff-poppins fz-16">Login</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        endif;
    return ob_get_clean();
});


add_action('wp_ajax_imit_create_vote', 'imit_create_vote');


/**
 * for ajax searching
 */
function imit_ajax_search(){
    global $wpdb;
    $nonce = $_POST['nonce'];
   if(wp_verify_nonce($nonce, 'imit_ajax_search')){
       $tag = sanitize_text_field($_POST['tag']);
       $sort = sanitize_text_field($_POST['sort']);
       $category = sanitize_text_field($_POST['category']);
       $event_id = sanitize_text_field($_POST['event_id']);

        if(!empty($tag)){
            $keyword = "WHERE {$wpdb->prefix}imit_event_proposals.tags LIKE '%{$tag}%'";
        }else{
            $keyword = '';
        }

        if(!empty($category)){
            if(!empty($tag)){
                if($category == 'all'){
                    $cat = '';
                }else{
                    $cat = "AND {$wpdb->prefix}imit_event_proposals.proposal_category = '{$category}'";
                }
            }else{
                if($category == 'all'){
                    $cat = '';
                }else{
                    $cat = "WHERE {$wpdb->prefix}imit_event_proposals.proposal_category = '{$category}'";
                }
            }
        }else{
            $cat = '';
        }

        if(!empty($sort)){
            if($sort == 'rand'){
                $order = "ORDER BY RAND()";
            }else if($sort == 'recent'){
                $order = "ORDER BY {$wpdb->prefix}imit_event_proposals.id DESC";
            }else if($sort == 'oldest'){
                $order = "ORDER BY {$wpdb->prefix}imit_event_proposals.id ASC";
            }
        }else{
            $order = '';
        }

        if($sort == 'latest_voted'){
            if(empty($tag) && empty($category) || $category == 'all'){
                $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE id IN (SELECT DISTINCT {$wpdb->prefix}imit_event_proposals.id FROM {$wpdb->prefix}imit_event_proposals INNER JOIN {$wpdb->prefix}imit_votes ON {$wpdb->prefix}imit_event_proposals.id = {$wpdb->prefix}imit_votes.proposal_id WHERE event_id = '{$event_id}' ORDER BY {$wpdb->prefix}imit_votes.id DESC)");
            }else{
                $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE id IN (SELECT DISTINCT {$wpdb->prefix}imit_event_proposals.id FROM {$wpdb->prefix}imit_event_proposals INNER JOIN {$wpdb->prefix}imit_votes ON {$wpdb->prefix}imit_event_proposals.id = {$wpdb->prefix}imit_votes.proposal_id {$keyword} {$cat} AND event_id = '{$event_id}' ORDER BY {$wpdb->prefix}imit_votes.id DESC)");
            }
        }else{
            if(!empty($tag) || !empty($category)){
                if(!empty($category) && $category == 'all'){
                    $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}' {$order}");
                }else{
                    $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals {$keyword} {$cat} AND event_id = '{$event_id}' {$order}");
                }
            
            }else{
                $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_event_proposals WHERE event_id = '{$event_id}' {$order}");
            }
            
        }

        foreach($result as $proposal):
            $user_data = get_userdata($proposal->user_id);
            $proposal_category_info = get_term_by('slug', $proposal->proposal_category, 'proposalcat');
            $get_category_image = get_field('proposal_category_image', $proposal_category_info);
        ?>
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card mt-3 rounded-0">
                <div class="card-body p-0">
                    <div class="ratio ratio-21x9 overflow-hidden">
                        <img src="<?php echo $get_category_image; ?>" alt="">
                    </div>
                    <div class="p-3">
                        <div class="user-info d-flex flex-row justify-content-start align-items-center border-bottom pb-3">
                            <?php echo get_avatar($proposal->user_id, '96', '', '', ['class' => 'user-image']); ?>
                            <div class="profile-info ms-2">
                                <p class="username mb-0"><?php echo $user_data->user_login; ?></p>
                                <span class="text-secondary d-block"><?php echo date('F d, Y', strtotime($proposal->created_at));//human_time_diff( strtotime( $proposal->created_at ), current_time( 'timestamp') );; ?></span>
                            </div>
                        </div>
                        <a href="<?php echo site_url().'/single-page/?id='.$proposal->id; ?>" class="primary-color custom-title mt-3 d-block"><?php echo $proposal->proposal_title; ?></a>
                        <p class="text-secondary custom-text mt-2"><?php echo wp_trim_words($proposal->proposal_description, 20, false); ?></p>
                        <div class="tags fz-16 ff-poppins"><i class="fas fa-tags me-2"></i><?php
                            $all_tags = array_slice(json_decode($proposal->tags), 0, 5);
                            echo implode(', ', $all_tags);
                            ?></div>
                        <?php
                        if(is_user_logged_in()){
                            $user_id = get_current_user_id();
                            $all_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal->id}' AND user_id = '{$user_id}'");
                            if(count($all_votes) > 0){
                                ?>
                                <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 disabled"><i class="fas fa-thumbs-up me-2"></i> Voated</a>
                                <?php
                            }else{
                                ?>
                                <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16 vote-button<?php echo $proposal->id; ?>" data-bs-toggle="modal" data-bs-target="#vote-modal" data-proposal_id="<?php echo $proposal->id; ?>" id="create-vote"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                                <?php
                            }
                        }else{
                            ?>
                            <a href="#" class="btn btn-success text-white btn-sm mt-3 fz-16" data-bs-toggle="modal" data-bs-target="#login-modal"><i class="fas fa-thumbs-up me-2"></i> Voter pour</a>
                            <?php
                        }
                        ?>

                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-6 text-center border-end fz-14 ff-poppins">
                            <span class="d-block"><?php 
                                        $proposal_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_proposal_comments WHERE proposal_id = '{$proposal->id}'");
                                            echo count($proposal_votes);
                                            ?></span>
                            <span>commentaire</span>
                        </div>
                        <div class="col-6 text-center fz-14 ff-poppins">
                            <span class="d-block" id="votecounter<?php echo $proposal->id; ?>"><?php
                                $proposal_votes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}imit_votes WHERE proposal_id = '{$proposal->id}'");
                                echo count($proposal_votes);
                                ?></span>
                            <span>votes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; 

        

   }
    die();
}

add_action('wp_ajax_imit_ajax_search', 'imit_ajax_search');
add_action('wp_ajax_nopriv_imit_ajax_search', 'imit_ajax_search');

function imit_create_comment(){
    global $wpdb;
    $nonce = $_POST['nonce'];
    if(wp_verify_nonce( $nonce, 'proposal_comment_create' )){
        $comment_table_name = $wpdb->prefix.'imit_proposal_comments';
        $proposal_comment = sanitize_text_field( $_POST['proposal_comment'] );
        $proposal_id = sanitize_text_field( $_POST['proposal_id'] );
        if(empty($proposal_comment) || empty($proposal_id)){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning!</strong> All fields are required.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else{
            $wpdb->insert($comment_table_name, [
                'comment' => $proposal_comment,
                'proposal_id' => $proposal_id,
                'user_id' => get_current_user_id(), 
            ]);
                echo '<div class="ff-poppins fz-16 alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> Comment added successful.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        }
    }
    die();
}

add_action('wp_ajax_imit_create_proposal_comment', 'imit_create_comment');


add_shortcode( 'imit_user_registration', function(){
    ob_start();
    if(is_user_logged_in()){
        ?>
        <script type="text/javascript">
    window.location.href= "<?php echo site_url().'/dashboard'; ?>";
    </script>
        <?php
    }else{
        ?>
        <section class="login py-5">
           <form id="wordpress_create_user" class="mx-2" method="POST">
               <div id="wp_create_user_message"></div>
               <label for="name" class="form-label fz-16 ff-poppins">Full name</label>
               <input name="name" type="text" id="name" class="form-control fz-16 ff-poppins mb-3" placeholder="Enter name">
   
               <label for="email" class="form-label fz-16 ff-poppins">Email</label>
               <input name="email" type="text" id="email" class="form-control fz-16 ff-poppins mb-3" placeholder="Enter email">
   
               <label for="password" class="form-label fz-16 ff-poppins">Password</label>
               <input name="password" type="password" id="password" class="form-control fz-16 ff-poppins mb-3" placeholder="Enter password">
   
               <label for="re-password" class="form-label fz-16 ff-poppins">Re-Enter Password</label>
               <input name="re-password" type="password" id="re-password" class="form-control fz-16 ff-poppins" placeholder="Password confirm">

               <p class="fz-16 ff-poppins mb-2">Already have an account? <a href="<?php echo site_url().'/login'; ?>">Login</a></p>
   
               <button type="submit" class="btn primary-bg text-white mt-2 fz-16 ff-poppins" name="submit" value="register">Register</button>
           </form>
       </section>
       <?php
    }
    
    return ob_get_clean();
} );

/**
 * create new wordpress user
 */
function create_wordpress_new_user(){
    $nonce = $_POST['nonce'];
    if(wp_verify_nonce( $nonce, 'add_wordpress_user' )){
        $name = sanitize_text_field( $_POST['name'] );
        $email = sanitize_text_field( $_POST['email'] );
        $password = sanitize_text_field( $_POST['password'] );
        $re_pass = sanitize_text_field( $_POST['re_pass'] );

        if(empty($name) || empty($email) || empty($password) || empty($re_pass)){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning!</strong> All fields are required.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo '<div class="ff-poppins fz-16 alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Stop!</strong> Invalid email!.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else if($password !== $re_pass){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning!</strong> Password not matched.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else if(email_exists( $email ) == true){
            echo '<div class="ff-poppins fz-16 alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Stop!</strong> Email already exists.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else{
            wp_create_user( $name, $password, $email );
            echo '<div class="ff-poppins fz-16 alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> User created. <a href="#">Click here</a> to login.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }
    }
    die();
}

add_action('wp_ajax_nopriv_imit_create_user', 'create_wordpress_new_user');


add_shortcode( 'imit_login', function(){
    ob_start();
    if(is_user_logged_in()){
        ?>
        <script type="text/javascript">
    window.location.href= "<?php echo site_url().'/dashboard'; ?>";
    </script>
        <?php
    }else{
        ?>
        <section class="login py-5">
            <form id="imit_login" class="mx-2" method="POST">
                <div id="imit-login-error"></div>
                <?php wp_nonce_field('imit_login_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="imit_custom_login">
                <label for="name" class="form-label fz-16 ff-poppins">Username/Email</label>
                <input type="text" name="name" id="name" class="form-control mb-3 fz-16 ff-poppins" placeholder="Enter name or email">
    
                <label for="password" class="form-label fz-16 ff-poppins">Password</label>
                <input type="password" name="password" id="password" class="form-control fz-16 ff-poppins" placeholder="Enter password">
                <p class="fz-16 ff-poppins mb-2">Have no account? <a href="<?php echo site_url().'/register'; ?>">Register</a></p>
    
                <button type="submit" class="btn primary-bg text-white mt-2 fz-16 ff-poppins">Login</button>
            </form>
        </section>
    
        <?php
    }
    return ob_get_clean();
} );

/**
 * login user
 */
add_action('wp_ajax_nopriv_imit_custom_login', function(){
    if(wp_verify_nonce( $_POST['nonce'], 'user_login_nonce')){
        $name = sanitize_text_field( $_POST['name'] );
        $password = sanitize_text_field( $_POST['password'] );
        $user = wp_signon([
            'user_login' => $name,
            'user_password' => $password,
            'remember' => true
        ]);
        if(is_wp_error($user)){
            $error['response'] = '<div class="ff-poppins fz-16 alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Stop!</strong> Invalid info!.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
          $error['redirect'] = 'no';
        }else{
            $error['redirect'] = 'yes';
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
        }
        echo json_encode($error);
    }
    die();
});

/**
 * wordpress dashboard
 */
add_shortcode( 'imit-dashboard', function(){
    ob_start();
    if(is_user_logged_in()):
        $user_data = wp_get_current_user();
    ?>
<section class="user-dashboard py-5">
        <div class="container">
            <div class="row">
                <div class="list-group col-md-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <div class="list-group-item list-group-item-action d-flex flex-row justify-content-start align-items-center">
                        <div class="profile-image me-3">
                            <?php echo get_avatar($user_data->ID, 96, '', '', ['class' => 'rounded-circle']); ?>
                        </div>
                        <p class="username fz-16 text-dark mb-0 fz-16 ff-poppins"><?php echo $user_data -> user_login; ?></p>
                    </div>
                    <a href="#" class="list-group-item list-group-item-action active fz-16 ff-poppins" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="false"><i class="fas fa-user me-2"></i>Profile</a>
                    <a href="#" class="list-group-item list-group-item-action fz-16 ff-poppins" id="v-pills-account-tab" data-bs-toggle="pill" data-bs-target="#v-pills-account" type="button" role="tab" aria-controls="v-pills-account" aria-selected="false"><i class="fas fa-cog me-2"></i>Account</a>
                    <a href="#" class="list-group-item list-group-item-action fz-16 ff-poppins" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false"><i class="fas fa-key me-2"></i>Password</a>
                    <a href="<?php echo wp_logout_url( get_home_url() ); ?>" class="list-group-item list-group-item-action fz-16 ff-poppins bg-danger text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
                <div class="tab-content col-md-9" id="v-pills-tabContent">
                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="title ff-poppins">Profile</h3>
                            </div>
                            <div class="card-body">
                                <label for="name" class="form-label fz-16 ff-poppins">Full name</label>
                                <input type="text" class="form-control fz-16 ff-poppins" name="name" id="name" placeholder="Enter full name" value="<?php echo $user_data->user_login; ?>">
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins">Save</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-account" role="tabpanel" aria-labelledby="v-pills-account-tab">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="title ff-poppins">Account</h3>
                            </div>
                            <div class="card-body">
                                <label for="email" class="form-label fz-16 ff-poppins">Your email</label>
                                <input type="text" class="form-control fz-16 ff-poppins" name="name" id="email" placeholder="Enter valid email" value="<?php echo $user_data->user_email; ?>">
                            </div>
                            <div class="card-footer d-flex flex-row justify-content-between align-items-center">
                                <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins">Save</button>
                                <button type="submit" class="btn btn-danger text-white fz-16 ff-poppins">Delete my account</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="title ff-poppins">Change password</h3>
                            </div>
                            <div class="card-body">
                                <label for="old-password" class="form-label fz-16 ff-poppins">Old password</label>
                                <input type="password" class="form-control fz-16 ff-poppins mb-2" name="old_password" id="old-password" placeholder="Enter old password">

                                <label for="new-password" class="form-label fz-16 ff-poppins">New password</label>
                                <input type="password" class="form-control fz-16 ff-poppins mb-2" name="new_password" id="new-password" placeholder="Enter new password">

                                <label for="confirm-password" class="form-label fz-16 ff-poppins">Confirm password</label>
                                <input type="password" class="form-control fz-16 ff-poppins mb-2" name="confirm_password" id="confirm-password" placeholder="Enter confirm password">
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn primary-bg text-white fz-16 ff-poppins">Change password</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    else:
        ?>
        <script type="text/javascript">
        window.location.href="<?php echo site_url().'/login'; ?>"
    </script>
        <?php
    endif;
    return ob_get_clean();
} );


/**
 * like and dislike
 */
add_action('wp_ajax_imit_like_create', function(){
    global $wpdb;
    $nonce = $_POST['nonce'];
    $action = 'create_proposal_like_n';
    if(wp_verify_nonce( $nonce, $action )){
        $table_name = $wpdb->prefix.'imit_comment_likes';
        $comment_id = sanitize_text_field( $_POST['comment_id'] );
        $user_id = get_current_user_id();
        if(!empty($comment_id) && !empty($user_id)){
            if($_POST['like_action'] == 'like'){
                $wpdb->insert($table_name, [
                    'comment_id' => $comment_id,
                    'user_id' => $user_id
                ]);
            }else{
                $wpdb->delete($table_name, [
                    'comment_id' => $comment_id,
                    'user_id' => $user_id
                ]);
            }
        }
    }
    die();
});


/**
 * post a replay
 */
add_action('wp_ajax_imit_create_replay', function(){
    global $wpdb;
    $nonce = $_POST['nonce'];
    if(wp_verify_nonce( $nonce, 'proposal_comment_replay_n' )){
        $table_name = $wpdb->prefix.'imit_comment_replays';
        $comment_id = sanitize_text_field($_POST['comment_id']);
        $replay_text = sanitize_text_field( $_POST['replay_text'] );
        if(empty($comment_id) || empty($replay_text)){
            echo '<div class="ff-poppins fz-16 alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning!</strong> Please leave a replay.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }else{
            $wpdb->insert($table_name, [
                'comment_id' => $comment_id,
                'user_id' => get_current_user_id(),
                'replay_text' => $replay_text
            ]);
            echo '<div class="ff-poppins fz-16 alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Replay added successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }
    }
    die();
});


/**
 * for optinion page
 */
add_shortcode( 'imit-opinion', function(){
    ob_start();
    ?>
<section class="event-banner ff-poppins" style="background-image: url(<?php echo plugins_url('images/banner.jpg', __FILE__); ?>);">
        <div class="overlay text-center">
            <h3 class="event-title mb-5 text-white">The citizen project</h3>
            <p class="event-subtitle mb-5 text-white">Occitania in common</p>
            <ul class=" fz-16 d-flex flex-row justify-content-center align-items-center mb-4 text-white">
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-file-signature me-2"></i>
                    <span class="me-2">224</span>
                    <span class="me-3">Contributions</span>
                </li>
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-thumbs-up me-2"></i>
                    <span class="me-2">1652</span>
                    <span class="me-3">Votes</span>
                </li>
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-users me-2"></i>
                    <span class="me-2">486</span>
                    <span>Participants</span>
                </li>
            </ul>
            <div class="dropdown">
                <button class="fz-16 btn border border-light text-white dropdown-toggle mb-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-share-square"></i>
                    Share
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Email</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-facebook-f me-2"></i>Facebook</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-twitter me-2"></i>Twitter</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-linkedin me-2"></i>Linkedin</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-link me-2"></i>Link</a></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="event-progress-menu ff-poppins">
            <ul class="menu-wrapper mb-0 ps-0 overflow-hidden">
                <div class="container d-flex flex-row justify-content-start align-items-center" style="overflow-x: auto;overflow-y: hidden">
                    <li class="menu-list">
                        <a href="<?php echo site_url(); ?>" class="menu-link d-flex flex-row justify-content-center align-items-center">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">PROPOSER UNE IDÉE</span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                    <li class="menu-list">
                        <a href="<?php echo site_url().'/opinion'; ?>" class="menu-link d-flex flex-row justify-content-center align-items-center active">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">DONNER VOTRE AVIS</span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                    <li class="menu-list">
                        <a href="<?php echo site_url().'/decide'; ?>" class="menu-link d-flex flex-row justify-content-center align-items-center">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">DÉCIDER DES PRIORITÉS/span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                </div>
            </ul>
    </section>

    <section class="event-info bg-light pt-5 ff-poppins">
        <div class="container">
            <div class="alert alert-info mb-4 fz-16"><strong>Consultation à venir.</strong> La consultation débutera le <strong>1 avril 2021.</strong></div>
            <h3 class="title text-center primary-color mb-4">Prononcez-vous sur les propositions !</h3>
            <p class="text-center fz-16"><i class="fas fa-calendar-alt me-2"></i>Du 1 avr. au 15 avr.</p>
            <div class="d-flex flex-row justify-content-center align-items-center fz-16 mb-3">
                <div><i class="fas fa-comment me-2"></i>0 contribution</div>
                <div><i class="fas fa-thumbs-up ms-2 me-2"></i>0 vote</div>
                <div><i class="fas fa-user ms-2 me-2"></i>0 participant</div>
            </div>
            <div class="p-2 border bg-white mx-auto rounded-3 fz-16" style="max-width: 913px;">
                <p class="mb-0">Partagez votre opinion sur les propositions et aidez-nous à faire émerger les actions qui ont le plus de sens pour vous. Votre avis compte pour nous !</p>
            </div>
            <h3 class="text-center m-0 py-4">Bientôt disponible !</h3>
        </div>
    </section>
    <?php
    return ob_get_clean();
} );

/**
 * for decide page
 */
add_shortcode( 'imit-decide', function(){
    ob_start();
    ?>
<section class="event-banner ff-poppins" style="background-image: url(<?php echo plugins_url('images/banner.jpg', __FILE__); ?>);">
        <div class="overlay text-center">
            <h3 class="event-title mb-5 text-white">The citizen project</h3>
            <p class="event-subtitle mb-5 text-white">Occitania in common</p>
            <ul class=" fz-16 d-flex flex-row justify-content-center align-items-center mb-4 text-white">
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-file-signature me-2"></i>
                    <span class="me-2">224</span>
                    <span class="me-3">Contributions</span>
                </li>
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-thumbs-up me-2"></i>
                    <span class="me-2">1652</span>
                    <span class="me-3">Votes</span>
                </li>
                <li class="d-flex flex-sm-row flex-column justify-content-start align-items-center">
                    <i class="fas fa-users me-2"></i>
                    <span class="me-2">486</span>
                    <span>Participants</span>
                </li>
            </ul>
            <div class="dropdown">
                <button class="fz-16 btn border border-light text-white dropdown-toggle mb-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-share-square"></i>
                    Share
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Email</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-facebook-f me-2"></i>Facebook</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-twitter me-2"></i>Twitter</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fab fa-linkedin me-2"></i>Linkedin</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-link me-2"></i>Link</a></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="event-progress-menu ff-poppins">
            <ul class="menu-wrapper mb-0 ps-0 overflow-hidden">
                <div class="container d-flex flex-row justify-content-start align-items-center" style="overflow-x: auto;overflow-y: hidden">
                    <li class="menu-list">
                        <a href="<?php echo site_url(); ?>" class="menu-link d-flex flex-row justify-content-center align-items-center">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">Propose an idea</span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                    <li class="menu-list">
                        <a href="<?php echo site_url().'/opinion'; ?>" class="menu-link d-flex flex-row justify-content-center align-items-center">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">DONNER VOTRE AVIS</span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                    <li class="menu-list">
                        <a href="<?php echo site_url().'/decide'; ?>" class="menu-link d-flex flex-row justify-content-center align-items-center active">
                            <span class="counter me-3">1</span>
                            <span>
                            <span class="link-title d-block fz-16">DÉCIDER DES PRIORITÉS</span>
                            <span class="badge fz-14 d-table">À venir</span>
                        </span>
                        </a>
                    </li>
                </div>
            </ul>
    </section>

    <section class="event-info bg-light pt-5 ff-poppins pb-4 ff-poppins">
        <div class="container">
            <div class="alert alert-info mb-4 fz-16"><strong>Questionnaire à venir.</strong> L'étape de questionnaire débutera le <strong>15 avril 2021.</strong></div>
            <h3 class="title primary-color mb-4">Exprimez vos choix sur le programme d’actions !</h3>
            <p class=""><i class="fas fa-calendar-alt me-2 fz-16"></i>Du 15 avril 2021 au 30 avril 2021</p>
            <div class="p-2 border bg-white mx-auto rounded-3">
                <p class="mb-0 fz-16">Contribuez au programme en partageant vos choix et vos priorités pour l’Occitanie. Votez pour construire le projet citoyen de notre région.</p>
            </div>

            <div class="p-4 border bg-white mx-auto rounded-3 mt-4">
                <h4 class="primary-bg text-white rounded-3 p-2">Bientôt disponible !</h4>
                <hr>
                <div class="d-flex flex-row justify-content-start align-items-center mt-2">
                    <a href="#" class="fz-16 btn primary-bg disabled text-white me-2 rounded-0">Enregistrer le brouillon</a>
                    <a href="#" class="fz-16 btn primary-bg disabled text-white rounded-0">Envoyer</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
} );
