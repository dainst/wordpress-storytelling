<?php


namespace esa_datasource {

    class shap_easydb extends abstract_datasource {

        public $title = 'SHAP - EasyDB'; // Label / Title of the Datasource
        public $index = 1; // where to appear in the menu
        public $info = "<p>...Kurzbeschreibung...</p>"; // get created automatically, or enter text
        public $homeurl; // link to the dataset's homepage
        public $debug = false;
        //public $examplesearch; // placeholder for search field
        //public $searchbuttonlabel = 'Search'; // label for searchbutton

        public $force_curl = true;

        private $_session_token;

        private $_easydb_url = "";
        private $_easydb_user = "";
        private $_easydb_pass = "";

        private $_items_per_page = 12;

        function construct() {
            $this->_easydb_url = esa_get_settings('modules', 'shap_easydb', 'easyurl');
            $this->_easydb_user = esa_get_settings('modules', 'shap_easydb', 'easyuser');
            $this->_easydb_pass = esa_get_settings('modules', 'shap_easydb', 'easypass');
        }

        function dependency_check() {
            if (!$this->check_for_curl()) {
                throw new \Exception('PHP Curl extension not installed');
            }
            $this->get_easy_db_session_token();
            return 'O. K.';
        }

        function parse_error_response($msg) {
            $json_msg = json_decode($msg);
            if (is_object($json_msg)) {
                return isset($json_msg->description) ? $json_msg->description : $json_msg->code;
            }
            return $msg;
        }

        function get_easy_db_session_token() {
            if ($this->_session_token) {
                return $this->_session_token;
            }
            try {
                $resp = json_decode($this->_fetch_external_data("{$this->_easydb_url}/api/v1/session"));
                if (!isset($resp->token)) {
                    throw new \Exception('no token');
                }
                $this->_session_token = $resp->token;
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: create session failed: ' . $this->parse_error_response($e));
            }
            try {

                if (!$this->_easydb_url or !$this->_easydb_pass or !$this->_easydb_user) {
                    $credentials = "method=anonymous";
                } else {
                    $credentials = "login={$this->_easydb_user}&password={$this->_easydb_pass}";
                }

                $this->_fetch_external_data((object) array(
                    "url" => "{$this->_easydb_url}/api/v1/session/authenticate?token={$this->_session_token}&$credentials",
                    "method" => "post"
                ));
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: authentication failed: ' . $this->parse_error_response($e));
            }
            return $this->_session_token;
        }



        // id is _objecttype + "|" + id
        function api_single_url($id, $params = array()) : string {
            $this->get_easy_db_session_token();
            list($object_type, $object_id) = explode("|", $id);
            return "{$this->_easydb_url}/api/v1/db/$object_type/_all_fields/global_object_id/$object_id@local?token={$this->_session_token}";
        }

        function api_record_url($id, $params = array()) : string {
            list($object_type, $object_id) = explode("|", $id);
            return "{$this->_easydb_url}/lists/$object_type/$object_id";
        }

        function api_search_url($query, $params = array()) {

            $this->get_easy_db_session_token();

            $search = array(
                "search" => array(
                    array(
                        "type" => "match",
                        "mode" => "token",
                        "string"=> $query,
                        "phrase"=> true
                    )
                ),
                "limit" => $this->_items_per_page
            );

            if (isset($params['offset'])) {
                $search['offset'] = $params['offset'];
            }

            return (object) array(
                'method' => 'post',
                'url' => "{$this->_easydb_url}/api/v1/search?token={$this->_session_token}",
                'post_json' => $search
            );
        }

        function api_search_url_next($query, $params = array()) {
            $this->page += 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_prev($query, $params = array()) {
            $this->page -= 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_first($query, $params = array()) {
            $this->page = 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_last($query, $params = array()) {
            $this->page = $this->pages;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function parse_result_set($response) : array {
            $response = json_decode($response);
            $this->results = array();
            foreach ($response->objects as $item) {
                $type = $item->_objecttype;
                $this->results[] = $this->parse_result($this->_fetch_external_data($this->api_single_url("$type|{$item->_system_object_id}")));
            }

            $this->pages = (int) ($response->count / $this->_items_per_page) + 1;
            $this->page = isset($response->offset) ? ((int) ($response->offset / $this->_items_per_page) + 1) : 1;

            return $this->results;
        }

        function parse_field($field) {
            $lang = "de-DE";
            if (!isset($field->_standard)) {
                return "";
            }
            $one = "1";
            if (!isset($field->_standard->{$one})) {
                return "";
            }
            if (!isset($field->_standard->{$one}->text)) {
                return "";
            }
            if (!isset($field->_standard->{$one}->text->{$lang})) {
                $lang = get_object_vars($field->_standard->{$one}->text)[0];
            }
            return $field->_standard->{$one}->text->{$lang};
        }

        function parse_field_name($field_name) {
            return ucwords(str_replace(array("_id", "_"), array("", " "), $field_name));
        }


        function parse_result($response) : \esa_item{

            $json_response = $this->_json_decode($response);
            $system_object_id = $json_response[0]->_system_object_id;
            $object_type = $json_response[0]->_objecttype;
            $object = $json_response[0]->{$object_type};
            $id = "$object_type|$system_object_id";

            $data = new \esa_item\data();

            $data->title = $id;

            if ($object_type !== "bilder") {
                return new \esa_item("shap_easydb", $id, "not in bilder: $object_type", $this->api_record_url($id), "error");
            }

            $this->_parse_title($object, $data);
            $this->_parse_blocks($object, $data);
            $this->_parse_nested($object, $data);
            $this->_parse_date($object, $data);

            list($lat, $lon) = $this->_parse_place($object, $data);


            // image
            if (isset($object->bild) and isset($object->bild[0]->versions)) {
                $data->addImages(array('url' => $object->bild[0]->versions->preview->url, 'fullres' => $object->bild[0]->versions->full->url));
            }

            return new \esa_item("shap_easydb", $id, esa_debug($data->_data), $this->api_record_url($id), $data->title, array(), array(), $lat, $lon, $data->_data);
        }

        function _parse_title($o, \esa_item\data $data) {
            if (isset($o->ueberschrift)) {
                $data->title = $o->ueberschrift;
            } else if (isset($o->titel)) {
                $data->title = $o->titel;
            } else if (isset($o->beschreibung)) {
                $data->title = $o->beschreibung;
            }
        }

        function _parse_nested($o, \esa_item\data $data) {

            $to_parse = array(
                "keyword"   =>  "schlagwort",
                "element"   =>  "element",
                "style"     =>  "stilmerkmal",
                "tech"      =>  "technik",
                "material"  =>  "material",
            ); // skipped: teilelement, literatur

            foreach ($to_parse as $tag_type => $name) {
                $n = "_nested:bilder__$name";
                $a = "lk_{$name}_id";
                foreach ($o->$n as $keyword) {
                    $this->_get_detail($data, $tag_type, $keyword->$a);
                }
            }

        }

        function _parse_date($o, \esa_item\data $data) {
            if (isset($o->original_datum)) {
                $data->put("decade", $this->_get_decade($o->original_datum->_from));
            } else if (isset($o->bild[0]->date_created)) {
                if (isset($o->bild) and count($o->bild)) {
                    $data->put("decade", $this->_get_decade($o->bild[0]->date_created));
                }
            }
        }

        function _parse_blocks($o, \esa_item\data $data) {
            $blocks = array(
                "Vorlage" => "art_der_vorlage_id",
                "Status" => "bearbeitungsstatus_id",
                "Motiv" => "art_des_motivs_id_old",
                "Ort" => "ort_des_motivs_id",
                "Anbieter" => "anbieter_id",
                "Ersteller" => "ersteller_der_vorlage_id_old",
                "Material" => "material_der_vorlage_id"
            );

            foreach ($blocks as $bname => $block) {
                $this->_get_detail($data, $bname, $o->$block);
            }
        }

        function _get_decade(string $datestring) {
            $year = date("Y", strtotime($datestring));
            return substr($year, 0, 3) . "0s";
        }

        function _get_detail($data, $name, $block, $field = "_standard") {
            $one = 1;
            if (isset($block->$field) and isset($block->$field->$one)) {
                $data->putMultilang($name, (array) $block->$field->$one->text);
            }
        }

        function _parse_place($o, \esa_item\data $data) : array {

            if (!isset($o->ort_des_motivs_id)) {
                return array(null, null);
            }

            $soid = $o->ort_des_motivs_id->_system_object_id;

            $place = json_decode($this->_fetch_external_data($this->api_single_url("ortsthesaurus|$soid")));

            $place = $place[0];

            if (!isset($place) or !isset($place->ortsthesaurus) or !isset($place->ortsthesaurus->gazetteer_id)) {
                die(esa_debug($place));
                return array(null, null);
            }

            $gazId = $place->ortsthesaurus->gazetteer_id;

            foreach ($gazId->otherNames as $name) {
                $data->put("Ort", $name->title, "#");
            }

            if (!isset($gazId->position)) {
                return array(null, null);
            }

            return array($gazId->position->lat, $gazId->position->lng);

        }

        function _parse_generic(string $object_type, $object, \esa_item\data $data) {
            if (isset($object->name)) {
                $data->title = $object->name;
            }


            $data->addTable("Typ", $object_type);
            $data->addTable("ID", $object->_id);

            $fields_to_parse = array(
                'anbieter_id',
                'art_der_vorlage_id',
                'art_des_motivs_id',
                'bearbeitungsstatus_id',
                'ersteller_der_vorlage_id',
                'material_der_vorlage_id',
                'ort_des_motivs_id'
            );
            foreach ($fields_to_parse as $field_to_parse) {
                if (isset($object->{$field_to_parse})) {
                    $data->addTable($this->parse_field_name($field_to_parse), $this->parse_field($object->{$field_to_parse}));
                }
            }
            $fields_to_add = array(
                'anweisungen',
                'nutzungsbedingungen',
                'quelle',
                'verfasser_der_beschreibung'
            );
            foreach ($fields_to_add as $field_to_add) {
                if (isset($object->{$field_to_add})) {
                    $data->addTable(ucwords($field_to_add), $object->{$field_to_add});
                    //$data->put();
                }
            }


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