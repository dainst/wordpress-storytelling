<?php
/**
 *  ******************************************* Settings page
 */

add_action('admin_menu', function () {


    //create new top-level menu
    add_menu_page('Storytelling Application', 'Storytelling Application', 'administrator', ESA_FILE, function() {

        global $esa_settings;
        $url = admin_url('admin.php');

        $datasources = json_decode(get_option('esa_datasources'));
        if (!is_array($datasources)) {
            $datasources  = array();
        }

        //update_option('esa_datasources') = json_encode($list);

        $dsfiles = glob(ESA_PATH . "datasources/*.class.php");

        echo "<div class='wrap'>";

        esa_info();

        echo "<h2>Settings</h2>";

        echo "<form method='POST' action='$url'>";
        echo "<h4>Available Data Sources</h4>";
        echo "<p>Here you can see all currently installed sub-plugins, which are connectors to several epigraphic / other datasources.";
        $labels = array();
        $optionlist = array();
        $index = array();
        foreach ($dsfiles as $filename) {
            $name = basename($filename, '.class.php');
            $ds = get_esa_datasource($name);
            $label = $ds->title;
            $labels[$name] = $label;
            try  {
                $is_ok = true;
                $status = $ds->dependency_check();
            } catch(\exception $e) {
                $is_ok = false;
                $status = 'Error:' . $e->getMessage();
            }
            $status = ($is_ok === true) ? "<span style='color:green'>($status)</span>" : "<span style='color:red'>(Error: $status)</span>";
            $checked = ((in_array($name, $datasources)) and ($is_ok === true)) ?  'checked="checked"' : '';
            $disabled = ($is_ok === true) ? '' : 'disabled="disabled"';
            $optionlist[$ds->index] = "<div><input type='checkbox' name='esa_datasources[]' value='$name' id='esa_activate_datasource_$name' $checked $disabled /><label for='esa_activate_datasource_$name'>$label $status</label></div>";
        }
        ksort($optionlist);
        echo implode("\n", $optionlist);

        update_option('esa_datasource_labels', json_encode($labels));
        wp_nonce_field('esa_save_settings', 'esa_save_settings_nonce');
        echo "<input type='hidden' name='action' value='esa_save_settings'>";
        echo "<input type='submit' value='Save' class='button button-primary'>";
        echo "</form>";

        echo "<h3>Cache debug functions</h3>";
        echo "<p><b>These are debug functions you most likely not need!</b><br> Explanation: Normally embedded content from epigraphic datasources ('Esa-Items') is stored in cache and gets refreshed (causing a new API call) in the moment it get displayed when it was not refreshed by more than two weeks.<br>";
        echo "But you can force to empty the cache and also force to refresh all items at once (You may want to do that after an update for example).</p>";
        echo "<form method='POST' action='$url'>";
        echo "<input type='hidden' name='action' value='esa_flush_cache'>";
        echo "<input type='submit' value='Delete all cached content!' class='button'>";
        echo "</form>";
        echo "<form method='POST' action='$url'>";
        echo "<input type='hidden' name='action' value='esa_refresh_cache'>";
        echo "<input type='submit' value='Refresh all cached content! (May take extremly long time).' class='button'>";
        echo "</form>";

        echo "</div>";
    });

});
//toplevel_page_wordpress-storytelling/eagle-storytelling
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'toplevel_page_' . ESA_NAME . '/' . basename(ESA_FILE, '.php')) {
        wp_enqueue_style('esa_item', plugins_url() . ESA_DIR . '/css/esa_item.css');
        esa_register_special_styles();
        wp_enqueue_style('esa_admin', plugins_url() . ESA_DIR . '/css/esa_admin.css');
        wp_enqueue_script('esa_item', plugins_url() . ESA_DIR . '/js/esa_item.js', array('jquery'));
    }
});

function esa_info() {
    ob_start();
    ?>
    <div id='esa_item_list_sidebar'>
        <table id='esa_infotable'>
            <tr>
                <td colspan='2'><a href='http://www.eagle-network.eu/' target='_blank'><img style='width:230px' src='<?php echo plugins_url() . ESA_DIR . '/images/eagle_logo.png' ?>' alt='eagle logo' /></a></td>
            </tr>
            <tr>
                <td><a href='https://www.dainst.org/' target='_blank'><img style='width:120px' src='<?php echo plugins_url() . ESA_DIR . '/images/dai_logo.png' ?>' alt='dai logo' /></a></td>
                <td><a id='dai_name' href='https://www.dainst.org/' target='_blank'>Deutsches<br>Archäologisches<br>Institut</a></td>
            </tr>
        </table>
    </div>

    <div class='media-frame-content'>
        <h1>Storytelling Application</h1>
        <p>
            The Enhanced Storytelling Application (ESA) is a tool designed to allow users to create multimedia narratives on epigraphic content.
            It was created in the context of the EAGLE project, a European project which started in 2013 and aimed to connect and collect data
            sources and projects related to the topic of digital epigraphy, ancient history or archeology.
        </p>
        <p>
            Being a Plug-In for Wordpress the ESA allows you to embed multimedia content from a wide variety of data sources in your posts in a
            form of nicely drawn boxes ESA-Items. For example, you can paste a Wikipedia-URL to your text and it is rendered as a preview Box to
            the Wikipedia page. But It does not only extend the built-in embed (and oEmbed) functions that are well knows and beloved for working
            with services like Youtube, Flickr much more.
        </p>
        <p>
            The ESA-Items are neither iframes nor are they generated with ajax or any other way that would result in API calls to the corresponding
            web service every time the containing post is displayed. Instead, the embedded content is stored in cache table and refreshed automatically
            after two weeks. That makes the items also usable for searching, drawing a map of used ESA-Items in the database and so on.
        </p>
        <p>
            You can not only embed content as ESA-Items by posting URLs from known data sources but also search the data sources directly from the
            Wordpress text editor.
        </p>
        <p>
            In this way you can integrate Maps, Wikipedia Articles, Images from Wikimedia Commons and a lot of specialized data sources for epigraphy.
            The ESA has has a modular sub-plugin architecture which makes it quite easy for developers to add some other data sources via their
            Web-APIs. Thus it might be no only of interest for those who work in epigraphy or the ancient world but also for those who want to show
            the content of any Web-API in their blog.
        </p>
        <h2>Usage</h2>
        <p>
            To add such embedded content paste an URL to your post or click "Add Media" an your content editor and goto "Eagle Storytelling Application". There you can post URLs or serach directly in the various datasources via their APIs.
        </p>
        <p>
            Internally theese embedded contents are represented by Wordpresses shortcodes and look like that:<br>
            <code>[[esa source="wiki" id="Epigraphy@en"]]</code>
        </p>
        <p>
            There is also a widget wich displays a map of all posts with embedded content wich has geographic coordinates. You can find it in the widget area.
        </p>
        <p>
            By default, all the embedded contents (excerpts from Wikipedia pages, interactive maps or objects from the Europeana collection and so on) are displayed in a compact view.
            By clicking on the buttons, an excerpt can be expanded
            (downward arrow at the bottom of the item) and visualized in its original web page (the eye icon on the top-right corner).
            Finally, you can search for all the posts that embed the same item .
        </p>
        <p>
            Here is a <strong>map</strong> of Berlin. When you expand it using the downward arrow, the interactive map will become available for browsing!<br>
            [esa source="idai" id="2282601"]
            <br>Here is an <strong>inscription</strong> from the huge EAGLE collection.<br>
            [esa source="eagle" id="EDB::ecacd215c0e820d5407b32369cd33b9b::7e3028a2329c7e1e0432cc11b965e21c::visual"]
            <br>And finally, here is a Wikipedia page: the first paragraph of the embedded voice from the free encyclopedia is reported. Of course, by clicking on the eye you can continue reading the page in its original context.<br>
            [esa source="wiki" id="Epigraphy@en"]
        </p>
        <h2>What is the "Epidoc reader"?</h2>
        <p>
            EAGLE is very proud of putting together the largest collection of Graeco-Roman digitized inscriptions on the web. Moreover, we're promoting the use of <a href="http://sourceforge.net/p/epidoc/wiki/Home/">EpiDoc</a> as a standard for the digital encoding of epigraphic content.<br>
            If you want to make reference to an inscription that is published in the web in EpiDoc format but it's not included in our collection, our Storytelling App is the right tool! Just launch click on "Add Media" from within the editor, select the <strong>EAGLE Storytelling Application</strong>  gallery (just like for any other content) and then click on the <strong>Epidoc</strong> tab.<br>
            Paste the URL of the XML edition of the inscription you want to insert in the search bar and hit the "Search" button. If you want, the App will suggest a series of repositories where you can find EpiDoc xml. The result will look something like this (from <a href="http://iospe.kcl.ac.uk/index.html">IOSPE, Ancient Inscriptions of the Northern Black Sea</a>):<br>
            <div data-id="http://iospe.kcl.ac.uk/5.140.xml" data-source="epidoc" class="esa_item esa_item_epidoc esa_item_cached esa_item_collapsed"><div class="esa_item_tools"><a title="expand" class="esa_item_tools_expand">&nbsp;</a><a href="http://195.37.232.186/eagle?s&amp;post_type=story&amp;esa_item_id=http://iospe.kcl.ac.uk/5.140.xml&amp;esa_item_source=epidoc" class="esa_item_tools_find" title="Find Stories with this Item">&nbsp;</a></div><div class="esa_item_inner"><div class="esa_item_left_column_max_left"><div class="esa_item_text edition"><div id="edition" lang="grc">  <span class="textpartnumber" id="ab1">1</span>  <div class="textpart">  <a id="a1-l1"><!--0--></a>Ἐκημίθυ <br id="a1-l2">ἡ δούλ(η) τοῦ <br id="a1-l3">θεοῦ Ἀγ̣ά̣τη, <br id="a1-l4">υἱὸς τῆς Παλ- <br id="a1-l5"><span class="linenumber">5</span>κου ἔτους ͵ς- <br id="a1-l6">Ϡκθ´ </div>  <span class="textpartnumber" id="ab2">2</span>  <div class="textpart">  <a id="a2-l1"><!--0--></a>((stauros)) Ἐγώ, Γιάσων ((stauros)) </div>  </div></div><div class="esa_item_text translation"><div id="translation">  <div>
                <h2>textpart</h2>  <p>Fell asleep: a servant of God, Agathe, son of Palkos(?), in the year 6925.</p>  </div>  <div>  <p>I, Jason (?)</p>  </div>  </div></div></div><div class="esa_item_right_column_max_left"><h4>Надгробие Агаты, Epitaph of Agathe</h4><br><ul class="datatable"><li><strong>Content Provider: </strong> King's College London</li><li><strong>Type: </strong> <a target="_blank" href="monument-search.xml#mon8">Квадр.</a>, Wall block.</li><li><strong>Material: </strong> <a target="_blank" href="material-search.xml#m2">Известняк.</a>, Limestone.</li><li><strong>Ancient find spot: </strong> <a target="_blank" href="origPlace.xml#p012">
                </a></li><li><strong>urls: </strong> 5.140, PE5000140, byz135</li><li><strong>xslt: </strong> Remote (saxon)</li></ul></div></div><div style="display: none;" class="esa_item_resizebar">&nbsp;</div>
            </div>
        </p>
        <p>
            This feature needs
            <ul>
                <li>either PHP Module Libxml >= 2.7.8 (as of PHP >= 5.4.0)</li>
                <li>or PHP Module <a href='http://www.saxonica.com/html/saxon-c/index.html'>Saxon/c Processor</a> to be installed</li>
                <li>or a remote Epidoc Render Server set up. <i>(We are currently building a webservice for that but it's not ready now.)</i></li>
            </ul>
        </p>
        <h2>Links</h2>
        <ul>
            <li><a target='_blank' href='http://www.eagle-network.eu/resources/flagship-storytelling-app/'>www.eagle-network.eu</a></li>
            <li><a target='_blank' href='https://www.dainst.org/'>Deutsches Archäologisches Institut</a></li>
            <li><a target='_blank' hruf='https://github.com/codarchlab/eagle-storytelling'>Github Project</a></li>
            <li><a target='_blank' href='https://wordpress.org/plugins/eagle-storytelling-application/'>Wordpress.org</a>
            <li><a target='_blank' href='https://github.com/paflov/epidocConverter'>Github Project: Epidoc Converter</a></li>
        </ul>
        <h2>Legal Notice</h2>
        <p>
            Copyright (C) 2015, 2016 by Deutsches Archäologisches Institut<br>
            <br>
            This program is free software; you can redistribute it and/or
            modify it under the terms of the GNU General Public License
            as published by the Free Software Foundation; either version 2
            as published by the Free Software Foundation; either version 2
            of the License, or (at your option) any later version.<br>
            This program is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
            GNU General Public License for more details.<br>
            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software
            Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.<br>
            <a target='_blank' href='http://www.gnu.org/licenses/gpl-2.0.html'>(GPL)</a><br>
            <br>
            Written by Philipp Franck (philipp.franck@dainst.org)
        </p>


    </div>

    <?php
    $info = ob_get_clean();
    echo do_shortcode($info);
}