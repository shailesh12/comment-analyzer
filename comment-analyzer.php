<?php

/**
 * Plugin Name: Comment Analyzer
 * Plugin URI: 
 * Description: Comment Analyzer is a plugin that helps you moderate the comments on your WordPress site.
 * Author: Shailesh Tyagi
 * Version: 0.2
 */
class comment_analysis {

    public function __construct() {
        add_filter('manage_edit-comments_columns', array($this, 'add_sentiment_coloumn'), 10, 1);
        add_action('manage_comments_custom_column', array($this, 'show_sentiment_column'), 10, 2);
        add_action('comment_post', array($this, 'save_sentiment_response'), 10, 1);
    }

    /**
     * Add sentiment type column on manage comment page(admin)
     * @param array $cols
     * @return array
     */
    function add_sentiment_coloumn($cols) {
        $cols['api_result'] = __('Sentiment Type', 'wpse64973');
        return $cols;
    }

    /**
     * Display sentiment type field value on manage comment page(admin)
     * @param string $col
     * @param int $comment_id
     */
    function show_sentiment_column($col, $comment_id) {
        if ($col == 'api_result') {
            if ($t = get_comment_meta($comment_id, 'api_sentiment_response', true)) {
                echo esc_html($t);
            } else {
                esc_html_e('No Sentiment Response', 'wpse64973');
            }
        }
    }

    /**
     * Save sentiment response into database
     * @param int $comment_ID
     */
    function save_sentiment_response($comment_ID) {
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

/**
 * Create instance of the class to kick off the whole things
 */
new comment_analysis();


