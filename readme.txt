# Storytelling Application

Plugin Name: Enhanced Storytelling Application
Plugin URI:  http://www.eagle-network.eu/stories/
Description: Embed data from various sources in Your Posts.
Author:	     Deutsches Archäologisches Institut
Author URI:	 http://www.dainst.org/
Version:     2.2

Contributors: Deutsches Archäologisches Institut
Tags: Web-APIs, epigraphy, links, data, linked data, semantic, ancient world, history, science, wikipedia, pleiades, perseus, pelagios, finds-org,
Requires at least: 4.2
Tested up to: 4.9.9
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


## Description
This is a tool designed by the Deutsches Archäologisches Institut. It allows users to create multimedia narratives on different content. It was created in the context of the [EAGLE project](http://www.eagle-network.eu/), a European project which started in 2013 and aimed to connect and collect data sources and projects related to the topic of digital epigraphy, ancient history or archeology and continued  for the [Syrian Heritage Archive Project] (https://syrian-heritage.org).

Being a Plug-In for Wordpress the ESA allows you to embed multimedia content from a wide variety of data sources in your posts in a form of nicely drawn boxes ESA-Items. For example, you can paste a Wikipedia-URL to your text and it is rendered as a preview Box to the Wikipedia page. But It does not only extend the built-in embed-functions that are well known and beloved for working with services like Youtube, Flickr much more.

The ESA-Items are neither iframes nor are they generated with ajax or any other way that would result in API calls to the corresponding web service every time the containing post is displayed. Instead, the embedded content is stored in cache table and refreshed automatically after two weeks. That makes the items also usable for searching, drawing a map of used ESA-Items in the database and so on.

You can not only embed content as ESA-Items by posting URLs from known data sources but also search the data sources directly from the Wordpress text editor.

In this way you can integrate Maps, Wikipedia Articles, Images from Wikimedia Commons and a lot of specialized data sources for epigraphy. The ESA has has a modular sub-plugin architecture which makes it quite easy for developers to add some other data sources via their Web-APIs. Thus it might be no only of interest for those who work in epigraphy or the ancient world but also for those who want to show the content of any Web-API in their blog.

Currently available Sub-Plugins are:

Common

* [Wikipedia](https://www.wikipedia.org/) Articles
* File form [Wikimedia Commons](https://commons.wikimedia.org/wiki/Main_Page)
* Media from [Europeana](http://www.europeana.eu/portal/)

Places

* Map (via [iDAI Gazetteer](http://gazetteer.dainst.org))
* Entry from [Pelagios.org](http://pelagios.dme.ait.ac.at/) (and with this [Pleaides](http://pleiades.stoa.org/), and some more sources which are collected there)

Archaeology and Epigraphy

* Entry from [Finds.org](https://finds.org.uk/)
* Entry from [ancient.eu](https://ancient.eu/)
* Entry from [arachne](https://arachne.dainst.org/)
* Entry from [Eagle Inscription Database](http://www.eagle-network.eu/)
* [Epidoc](http://sourceforge.net/projects/epidoc/)-File

The Plugin was developed by [Philipp Franck](mailto:philipp.franck@absender.net) at the [Deutsches Archäologisches Institut](http://www.dainst.org) in 2015-2016 and 2018-2019.


## Installation

* PHP >= 5.3.0 is required
* Install from Plugin Repository / extract to wp-content/plugins and activate via wp-admin/plugins
* In admin menu go to the “Storytelling Application”, activate the data sources you want to use and click save

## Usage

See info page after installing Plugin or info.php. 
