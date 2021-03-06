<?php
class Tech_Labs_Pages_Controller {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace     = '/tech-labs/v1';
        $this->resource_name = 'pages';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
    }
    /**
     * Check permissions for the pages.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_permissions_check( $request ) {
        if ( get_option('close_json_pages') ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the page resource.' ), array( 'status' => $this->authorization_status_code() ) );
        }
        return true;
    }
 
    /**
     * Grabs the five most recent pages and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item( $request ) {
        $id = (int) $request['id'];
        $page = get_post( $id );
 
        if ( empty( $page ) ) {
            return rest_ensure_response( array() );
        }
 
        $response = $this->prepare_item_for_response( $page, $request );
 
        // Return all of our page response data.
        return $response;
    }
 
    /**
     * Matches the page data to the schema we want.
     *
     * @param WP_Page $page The comment object whose response is being prepared.
     */
    public function prepare_item_for_response( $page, $request ) {
        $schema = $this->get_item_schema( $request );
        if ( isset( $schema['properties']['id'] ) ) {
            $page_data['id'] = (int) $page->ID;
        }
 
        if ( isset( $schema['properties']['title'] ) ) {
            $page_data['title'] = preg_replace("/&#?[a-z0-9]{2,8};/i","",strip_tags(apply_filters( 'the_title', $page->post_title, $page )));
        }
 
        if ( isset( $schema['properties']['content'] ) ) {
            $page_data['content'] = preg_replace("/&#?[a-z0-9]{2,8};/i","",strip_tags(apply_filters( 'the_content', $page->post_content, $page )));
        }
        if ( isset( $schema['properties']['future_image'] )) {
    
            $page_data['future_image'] = get_the_post_thumbnail_url( $page->ID, 'full' );
        }
        return rest_ensure_response( $page_data );
    }
 
    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection( $response ) {
        if ( ! ( $response instanceof WP_REST_Response ) ) {
            return $response;
        }
 
        $data = (array) $response->get_data();
        $server = rest_get_server();
 
        if ( method_exists( $server, 'get_compact_response_links' ) ) {
            $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
        } else {
            $links = call_user_func( array( $server, 'get_response_links' ), $response );
        }
 
        if ( ! empty( $links ) ) {
            $data['_links'] = $links;
        }
 
        return $data;
    }
 
    /**
     * Get our sample schema for a page.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema( $request ) {
        $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'page',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'tl-json' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true,
                ),
                'title' => array(
                    'description'  => esc_html__( 'The content title.', 'tl-json' ),
                    'type'         => 'string',
                ),
                'future_image' => array(
                    'description'  => esc_html__( 'The content future image.', 'tl-json' ),
                    'type'         => 'array',
                ),
                'content' => array(
                    'description'  => esc_html__( 'The content for the object.', 'tl-json' ),
                    'type'         => 'string',
                )
            ),
        );
 
        return $schema;
    }
 
    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {
 
        $status = 401;
 
        if ( is_user_logged_in() ) {
            $status = 403;
        }
 
        return $status;
    }
}