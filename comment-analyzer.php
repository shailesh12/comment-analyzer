<?php

/**
 * Plugin Name: Comment Analyzer
 * Plugin URI: 
 * Description: Comment Analyzer is a plugin that helps you moderate the comments on your WordPress site.
 * Author: Shailesh Tyagi
 * Version: 0.1
 */
register_activation_hook(__FILE__, "includeCommentLibrary");

function includeCommentLibrary() {
    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
}

add_action('load-edit-comments.php', 'wpse64973_load');

//Add custom field on manage comment page(admin)
function wpse64973_load() {
    $screen = get_current_screen();
    add_filter("manage_{$screen->id}_columns", 'addSentimentColumn');
}

function addSentimentColumn($cols) {
    require_once(ABSPATH . "wp-includes/comment-template.php");
    $cols['api_result'] = __('Sentiment Type', 'wpse64973');
    return $cols;
}

add_action('manage_comments_custom_column', 'wpse64973_column_cb', 10, 2);

//Display custom field value on manage comment page(admin)
function wpse64973_column_cb($col, $comment_id) {
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

add_action('comment_post', 'show_message_function', 10, 2);

// Save sentiment api response into database
function show_message_function($comment_ID, $comment_approved) {

    if (1 === $comment_approved) {
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
}

function api_request($comment) {

    $myObj->text = $comment;
    $myObj->features = $myObj->sentiment = "";
    $data = json_encode($myObj);
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'apikey' => 'v-movQD5hvI7ZG3PZWfjQhp5K4ufafp13sshwDMKfZx-'
        ),
        'data' => $data, // fails if larger than 1024
    );

    $response = wp_remote_post('https://gateway-syd.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2018-09-21', $args);
    print_r($response);
    die;
}

function curl_request($comment) {
    // Get cURL resource
    $curl = curl_init();
// Set some options
    curl_setopt_array($curl, array(
        CURLOPT_HTTPHEADER => array(
            apikey => 'v-movQD5hvI7ZG3PZWfjQhp5K4ufafp13sshwDMKfZx-',
            Content - Type => 'application/json'
        ),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://gateway-syd.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2018-09-21',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => array(
            text => $comment,
            features => array(
                sentiment => ''
            )
        )
    ));
// Send the request & save response to $resp
    $resp = curl_exec($curl);
    print_r($resp);
    die;
// Close request to clear up some resources
    curl_close($curl);
}
