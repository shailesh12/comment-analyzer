<?php

/**
 * Plugin Name: Comment Analyzer
 * Plugin URI: 
 * Description: Comment Analyzer is a plugin that helps you moderate the comments on your WordPress site.
 * Author: Shailesh Tyagi
 * Version: 0.1
 */
register_activation_hook(__FILE__, "include_wp_comment");

function include_wp_comment() {
    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
}

add_action('load-edit-comments.php', 'custom_column_load');

//Add custom column on manage comment page(admin)
function custom_column_load() {
    $screen = get_current_screen();
    add_filter("manage_{$screen->id}_columns", 'add_sentiment_column');
}

function add_sentiment_column($cols) {
    require_once(ABSPATH . "wp-includes/comment-template.php");
    $cols['api_result'] = __('Sentiment Type', 'wpse64973');
    return $cols;
}

add_action('manage_comments_custom_column', 'display_custom_column_val', 10, 2);

//Display custom field value on manage comment page(admin)
function display_custom_column_val($col, $comment_id) {
    // you could expand the switch to take care of other custom columns
    switch ($col) {
        case 'api_result':
            if ($t = get_comment_meta($comment_id, 'api_sentiment_response', true)) {
                echo esc_html($t);
            } else {
                esc_html_e('No Sentiment Response', 'wpse64973');
            }
            break;
    }
}

add_action('comment_post', 'save_sentiment_response', 10, 1);

// Save sentiment response into database
function save_sentiment_response($comment_ID) {

    //function logic goes here
    $comment_text = get_comment_text($comment_ID);
    // Analyze comment sentiment using open source third party libaray
    require_once(ABSPATH . "wp-content/plugins/comment-analyzer/php-sentiment-analyser/lib/sentiment_analyser.class.php");
    $sa = new SentimentAnalysis();
    $sa->initialize();
    $sa->analyse($comment_text);
    $score = $sa->return_sentiment_rating();
    /*
     * Sentiment scale:
      < 2.5 : Negative
      = 2.5 : Neutral
      > 2.5 : Positive
     */
    if ($score > 2.5) {
        $sentiment_response = 'Postive';
    } else if ($score < 2.5) {
        $sentiment_response = 'Negative';
    } else {
        $sentiment_response = 'Neutral';
    }
    update_comment_meta($comment_ID, 'api_sentiment_response', esc_attr($sentiment_response));
}



