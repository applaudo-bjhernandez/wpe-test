<?php

namespace TnL\ImportVideos;

use GuzzleHttp\Client;

class ZypeImportCommand
{

    protected $client;
    public $imported_videos;

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false
        ]);

        $this->$imported_videos = 0;
    }

    /**
     * Imports videos from Zype.
     *
     * ## OPTIONS
     *
     * [--page=<page>]
     * : Number of page to import.
     * ---
     * default: 1
     * ---
     * [--pages=<pages>]
     * : Number of pages to import.
     * * ---
     * default: 1
     * ---
    * [--post_type=<post_type>]
    * : Custom post type to use.
    * ---
    * default: episode
    * ---
    * [--list_id=<list_id>]
    * : List ID to import videos from.
    * ---
    * default: ''
    * ---
     * ## EXAMPLES
     *

     * wp zype import --option=value
     * docker-compose exec wordpress wp zype import --page=1 --pages=2 --allow-root
     * winpty docker exec -it truth_liberty_backend-wordpress-1 bash
     */
    public function import( $args, $assoc_args )
    {
        $nextPageNumber = !empty($assoc_args['page']) ? $assoc_args['page'] : 1;
        $maxPages = !empty($assoc_args['pages']) ? (int) $assoc_args['pages'] : 1;
        $post_type = !empty($assoc_args['post_type']) ? $assoc_args['post_type'] : 'episode';
        $listId = !empty($assoc_args['list_id']) ? $assoc_args['list_id'] : '670804106a7edc0001082295';
        $importedPages = 0;

        while (
            $nextPageNumber !== null
            && (!($maxPages !== 0) || $importedPages < $maxPages)
        ) {
            $currentPage = $this->importPage($nextPageNumber, $post_type, $listId);
            $nextPageNumber = $currentPage['pagination']['next'] ?? null;
            $importedPages++;
        }
 
        $this->log_import($post_type, $this->$imported_videos);
        \WP_CLI::success( "Import command executed successfully." );
    }

    protected function importPage($page, $post_type, $listId)
    {
        \WP_CLI::success( "Importing page " .  $page );

        $params = http_build_query([
            'page' => $page,
            'api_key' => 'L1IgNYuTRl5DPPpJrUiYjrcoaAL4D8EUb42PqfQ9WA9MPmIChTtN3qNNEg9Vregi',
            'active' => 'true',
            'order' => 'desc',
            'sort' => 'published_at',
            'playlist_id.inclusive' => $listId,
            'per_page' => 25,
        ]);

        $urlRequest = 'https://api.zype.com/videos?' . $params;

        $response = $this->client->request(
            'GET',
            $urlRequest
            ,

            [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]
        );


        $response = json_decode(
            $response->getBody()->getContents(),
            true
        );

        $this->importVideos($response['response'], $post_type);

        return $response;
    }

    // Save log in database
    protected function log_import($post_type, $imported_videos) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zype_import_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'imported_at' => current_time('mysql'),
                'post_type' => $post_type,
                'imported_videos' => $imported_videos,
                'status' => "Success"
            )
        );
        
        \WP_CLI::success( "Log saved successfully." );
    }


    protected function importVideo(array $video, $post_type)
    {
        \WP_CLI::success( "Importing video " . $video['title'] );

        if ($this->videoHasBeenImported($video['_id'], $post_type)) {
            \WP_CLI::success( "Skipping video " . $video['title'] );
            return;
        }

        $post_id = wp_insert_post([
            'post_date' => $video['published_at'],
            'post_title' => $video['title'],
            'post_type'  => $post_type,
            'post_status'  => 'publish',
            'post_content' => $video['description'],
            'post_excerpt' => $video['short_description']
        ]);

        $years_value = '';


        foreach ($video['categories'] as $category) {
            if ($category['title'] === 'Years') {
                $years_value = $category['value'][0];
                break;
            }
        }

        if ($post_id != 0) {
            add_post_meta($post_id, 'episode_number', $video['episode'], true);
            add_post_meta($post_id, 'zype_remote_id', $video['_id'], true);
            add_post_meta($post_id, 'thumbnails', $video['thumbnails'][2]["url"], true);
            add_post_meta($post_id, 'created_at', $video['published_at'], true);
            add_post_meta($post_id, 'rating', $video['rating'], true);
            add_post_meta($post_id, 'duration', $video['duration'], true);
            add_post_meta($post_id, 'year', intval($years_value), true);
        }

        $this->$imported_videos++;
        
    }

    private function importVideos(array $response, $post_type)
    {
        foreach ($response as $video) {
            $this->importVideo($video, $post_type);
        }
    }

    private function videoHasBeenImported(string $_id, $post_type)
    {
        $args = array(
            'post_type'  => $post_type,
            'post_status'  => 'any',
            'meta_query' => array(
                array(
                    'key'   => 'zype_remote_id',
                    'value' => $_id,
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1,
        );

        $query = new \WP_Query($args);

        return $query->have_posts();
    }
}