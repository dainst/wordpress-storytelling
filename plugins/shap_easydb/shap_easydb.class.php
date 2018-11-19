<?php


namespace esa_datasource {

    class shap_easydb extends abstract_datasource {

        public $title = 'SHAP - EasyDB'; // Label / Title of the Datasource
        public $index = 1; // where to appear in the menu
        public $info = "...Kurzbeschreibung..."; // get created automatically, or enter text
        public $homeurl; // link to the dataset's homepage
        public $debug = true;
        //public $examplesearch; // placeholder for search field
        //public $searchbuttonlabel = 'Search'; // label for searchbutton

        public $pagination = false; // are results paginated?
        public $optional_classes = array(); // some classes, the user may add to the esa_item

        public $require = array();  // require additional classes -> array of fileanmes

        public $url_parser = '#https?\:\/\/(www\.)some_page.de?ID=(.*)#'; // // url regex (or array)

        public $force_curl = true;

        private $session_token;

        private $easydb_url = "https://syrian-heritage.5.easydb.de/api/v1";
        private $easydb_user = "root";
        private $easydb_pass = "";

        function dependency_check() {
            if (!$this->check_for_curl()) {
                throw new \Exception('PHP Curl extension not installed');
            }
            $this->get_easy_db_login();
            return 'O. K.';
        }

        /**
         * constructor
         * @see \esa_datasource\abstract_datasource::construct()
         */
        function construct() {

        }

        function message_abstract($e) {
            $json_msg = json_decode($e->getMessage());
            if (($e->getCode() == 666) and (is_object($json_msg))) {
                echo esa_debug($json_msg);
                return isset($json_msg->description) ? $json_msg->description : $json_msg->code;
            }
            return $e->getMessage();
        }

        function get_easy_db_login() {
            try {
                $resp = json_decode($this->_fetch_external_data("{$this->easydb_url}/session"));
                if (!isset($resp->token)) {
                    throw new \Exception('no token');
                }
                $this->session_token = $resp->token;
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: create session failed: ' . $this->message_abstract($e));
            }

            try {
                $resp = $this->_fetch_external_data("{$this->easydb_url}/session/authenticate?token={$this->session_token}&login={$this->easydb_user}&password={$this->easydb_pass}", array());
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: authentication failed: ' . $this->message_abstract($e));
            }

            echo esa_debug($resp);

            return true;


        }

        function api_search_url($query, $params = array()) {
            return "";
        }

        function api_single_url($id, $params = array()) {
            return "";
        }


        function api_record_url($id, $params = array()) {
            return "";
        }


        /*	pagination functions
        function api_search_url_next($query, $params = array()) {
            $this->page += 1;
            return $this->api_search_url($query) . '&page=' . $this->page;
        }

        function api_search_url_prev($query, $params = array()) {
            $this->page -= 1;
            return $this->api_search_url($query) . '&page=' . $this->page;
        }

        function api_search_url_first($query, $params = array()) {
            $this->page = 1;
            return $this->api_search_url($query) . '&page=' . $this->page;
        }

        function api_search_url_last($query, $params = array()) {
            $this->page = $this->pages;
            return $this->api_search_url($query) . '&page=' . $this->page;
        }
        */
        function parse_result_set($response) {
            $response = json_decode($response);
            $this->results = array();
            foreach ($response->items as $item) {


                /* old way of doint it

                $title = $this->results[2];
                $url = $this->results[3];
                $html  = "<div class='esa_item_left_column'>";
                $html .= "<div class='esa_item_main_image' style='background-image:url(\"{ image url }\")'>&nbsp;</div>";
                $html .= "</div>";

                $html .= "<div class='esa_item_right_column'>";
                $html .= "<h4>{ title }</h4>";

                $html .= "<ul class='datatable'>";
                $html .= "<li><strong>{ field }: </strong>{ data }</li>";
                $html .= "</ul>";

                $html .= "</div>";
                */

                $data = new \esa_item\data();

                $data->title = __title__;
                $data->addText($key, $value);
                $data->addTable($key, $value);
                $data->addImages(array(
                    'url' 		=> '',
                    'fullres' 	=> '',
                    'type' 		=> 'BITMAP',
                    'mime' 		=> '',
                    'title' 	=> '',
                    'text' 		=> ''
                ));



                $this->results[] = new \esa_item(__source__, __id__, $data->render(), __url__, __title__);
            }

            // pagination
            $this->pages = 1 + (int) ($response->meta->totalResults / $response->meta->resultsPerPage);
            $this->page  = $response->meta->currentPage;

            return $this->results;
        }

        function parse_result($response) {
            // if always return a whole set
            $res = $this->parse_result_set($response);
            return $res[0];
        }

        function stylesheet() {
            return array(
                'name' => get_class($this),
                'css' => ''
            );
        }

    }
}
?>