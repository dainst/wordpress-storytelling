=== Eagle Storytelling ===

Plugin Name: Eagle Storytelling Application
Plugin URI:  http://www.eagle-network.eu/stories/
Description: Create your own EAGLE story! 
Author:	     Deutsches Archäologisches Institut
Author URI:	 http://www.dainst.org/
Version:     2.1.0007

Contributors: Deutsches Archäologisches Institut
Tags: Web-APIs, epigraphy, links, data, linked data, semantic, ancient world, history, science, wikipedia, pleiades, perseus, pelagios, finds-org,
Requires at least: 4.2
Tested up to: 4.4.2
Stable tag: 2.1.0007
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create your own EAGLE story!

== Description ==
The EAGLE Storytelling Application (ESA) is a tool designed by the Deutsches Archäologisches Institut. It allows users to create multimedia narratives on epigraphic content. It was created in the context of the [EAGLE project](http://www.eagle-network.eu/), a European project which started in 2013 and aimed to connect and collect data sources and projects related to the topic of digital epigraphy, ancient history or archeology. 

Being a Plug-In for Wordpress the ESA allows you to embed multimedia content from a wide variety of data sources in your posts in a form of nicely drawn boxes ESA-Items. For example, you can paste a Wikipedia-URL to your text and it is rendered as a preview Box to the Wikipedia page. But It does not only extend the built-in embed (and oEmbed) functions that are well knows and beloved for working with services like Youtube, Flickr much more.

The ESA-Items are neither iframes nor are they generated with ajax or any other way that would result in API calls to the corresponding web service every time the containing post is displayed. Instead, the embedded content is stored in cache table and refreshed automatically after two weeks. That makes the items also usable for searching, drawing a map of used ESA-Items in the database and so on.

You can not only embed content as ESA-Items by posting URLs from known data sources but also search the data sources directly from the Wordpress text editor.

In this way you can integrate Maps, Wikipedia Articles, Images from Wikimedia Commons and a lot of specialized data sources for epigraphy. The ESA has has a modular sub-plugin architecture which makes it quite easy for developers to add some other data sources via their Web-APIs. Thus it might be no only of interest for those who work in epigraphy or the ancient world but also for those who want to show the content of any Web-API in their blog.

Currently available Sub-Plugins are:

* [Wikipedia](https://www.wikipedia.org/) Articles
* File form [Wikimedia Commons](https://commons.wikimedia.org/wiki/Main_Page)
* Map (via [iDAI Gazetteer](http://gazetteer.dainst.org))
* Media from [Europeana](http://www.europeana.eu/portal/)
* [Epidoc](http://sourceforge.net/projects/epidoc/) File
* Entry from [Eagle Inscription Database](http://www.eagle-network.eu/)
* Entry from [Pelagios.org](http://pelagios.dme.ait.ac.at/) (and with this [Pleaides](http://pleiades.stoa.org/), and some more sources which are collected there)
* Entry from [Finds.org](https://finds.org.uk/)
* Entry from [ancient.eu](https://ancient.eu/)

The Plugin was developed by [Philipp Franck](mailto:philipp.franck@absender.net) at the [Deutsches Archäologisches Institut](http://www.dainst.org) in 2015 and 2016.


== Installation ==

* PHP >= 5.3.0 is required
* Install from Plugin Repository / extract to wp-content/plugins and activate via wp-admin/plugins
* In admin menu go to the “Eagle Storytelling Application”, activate the data sources you want to use and click save

To use the Epidoc-Reader you need
* a) Libxml >= 2.7.8 (as of PHP >= 5.4.0) to be installed
or
* b) PHP Module [Saxon/c Processor](http://www.saxonica.com/html/saxon-c/index.html) to be installed
or
* c) set up a remote Epidoc Render Server (We are currently building a Webservice like that but it's not ready now. You can set it up by yourself, using [this](https://github.com/paflov/epidocConverter) but than you need libxml 2.7.8 or saxon/c as well.

To set up the epidoc rendering mode, create file called 'esa_datasource.settings.local.php' in the plugin directory
and fill it with:
```php
$settings['epidoc'] = array(
	'mode' => {{mode}},
	'settings' =>  array(
		'apiurl' => '{{url}}/epidocConverter/remoteServer.php'
	)
);
```

{{mode}} can be 'saxon', 'libxml', 'remote:saxon', 'remote:libxml' etc. 'apiurl' is only required when using remote.

== Screenshots ==

1. Embedded content in frontent
2. Selecting embedded content
3. Embedded content in the editor
4. The epidoc reader