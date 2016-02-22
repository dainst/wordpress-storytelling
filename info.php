<div id='esa_item_list_sidebar'>
	<table id='esa_infotable'>
		<tr>
			<td colspan='2'><a href='http://www.eagle-network.eu/' target='_blank'><img style='width:230px' src='http://www.eagle-network.eu/wp-content/uploads/2013/06/egl_web_logo.png' alt='eagle logo' /></a></td>
		</tr>
		<tr>
			<td><a href='https://www.dainst.org/' target='_blank'><img style='width:120px' src='https://www.dainst.org/image/company_logo?img_id=11201&t=1454494336195' alt='dai logo' /></a></td>
			<td><a id='dai_name' href='https://www.dainst.org/' target='_blank'>Deutsches<br>Archäologisches<br>Institut</a></td>
		</tr>
	</table>
</div>

<div class='media-frame-content'>
	<h1>Eagle Storytelling Application</h1>
	<p>
		The EAGLE Storytelling Application (ESA) is a tool designed to allow users to create multimedia narratives on epigraphic content. 
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
	<h2>Tutorial</h2>
	<p>
		By default, all the embedded contents (excerpts from Wikipedia pages, interactive maps or objects from the EAGLE 
		collection or Europeana) are displayed in a compact view. By clicking on the buttons, an excerpt can be expanded 
		(downward arrow at the bottom of the item) and visualized in its original web page (the eye icon on the top-right corner). 
		Finally, you can search for all the stories that embed the same item in our EAGLE collection.
	</p>
	<p>
		<strong>Try it yourself!</strong> Here is a <strong>map</strong> of Berlin. When you expand it using the downward arrow, the interactive map will become available for browsing!<br>
		[esa source="idai" id="2282601"]
		<br>Here is an <strong>inscription</strong> from the huge EAGLE collection.<br>
		[esa source="eagle" id="EDB::ecacd215c0e820d5407b32369cd33b9b::7e3028a2329c7e1e0432cc11b965e21c::visual"]
		<br>And finally, here is a Wikipedia page: the first paragraph of the embedded voice from the free encyclopedia is reported. Of course, by clicking on the eye you can continue reading the page in its original context.<br>
		[esa source="wiki" id="Epigraphy@en"]
	</p>
	<h2>What is the "Epidoc reader"?</h4>
	<p>
		EAGLE is very proud of putting together the largest collection of Graeco-Roman digitized inscriptions on the web. Moreover, we're promoting the use of <a href="http://sourceforge.net/p/epidoc/wiki/Home/">EpiDoc</a> as a standard for the digital encoding of epigraphic content.<br>
		If you want to make reference to an inscription that is published in the web in EpiDoc format but it's not included in our collection, our Storytelling App is the right tool! Just launch click on "Add Media" from within the editor, select the <strong>EAGLE Storytelling Application</strong>  gallery (just like for any other content) and then click on the <strong>Epidoc</strong> tab.<br>
		Paste the URL of the XML edition of the inscription you want to insert in the search bar and hit the "Search" button. If you want, the App will suggest a series of repositories where you can find EpiDoc xml. The result will look something like this (from <a href="http://iospe.kcl.ac.uk/index.html">IOSPE, Ancient Inscriptions of the Northern Black Sea</a>):<br>	
		[esa source="epidoc" id="http://iospe.kcl.ac.uk/5.140.xml"]
	</p>
	<h2>Links</h2>
	<ul>
		<li><a target='_blank' href='http://www.eagle-network.eu/resources/flagship-storytelling-app/'>www.eagle-network.eu</a></li>
		<li><a target='_blank' hruf='https://github.com/codarchlab/eagle-storytelling'>Github Project (TBA)</a></li>
		<li><a target='_blank' href='#'>Wordpress.org (TBA)</a>
		<li><a target='_blank' href='https://github.com/paflov/epidocConverter'>Github Project: Epidoc Converter</a></li>
		<li><a target='_blank' href='https://www.dainst.org/'>Deutsches Archäologisches Institut</a></li>
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


