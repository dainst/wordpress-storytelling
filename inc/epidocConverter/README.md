# epidocConverter

A PHP library to convert EpiDoc-XMLs to HTML, using the XSLT Stylesheets and the Saxon XSLT 2.0 Processor or some fallback Stylesheets and the PHP XSLT 1.0 Processor.
 
Version 1.5, 2016. 

Author: Philipp Franck.

Try it out on [http://epidoc.dainst.org/]([http://epidoc.dainst.org/])

## Requirements

* PHP >= 5.4.0 < 7.0.0
* Saxon/C XSLT Processor 1.0.0

## Tutorial

```php
try {
 $conv = epidocConverter::create($xmlData);
} catch (Exception $e) {
 echo $e->getMessage();
}
```

See `epidocConverterSaxon.class.php` and `epidocConverterFallback.class.php` for more hints.

# Documentation

## Files

### epidocConverter.class.php
Mother PHP Class. Is an Interface that can implement the different types of Epidoc Renderers.

### epidocConverter.libxml.class.php
Uses the built in XSLT 1.0 Processor of PHP and the (extremly simplified)
Needs: Libxml >= 2.7.8 (as of PHP >= 5.4.0) 

### epidocConverter.remote.class.php
Uses A EpidocConverter API Render to render the epidoc:
epidoc.dainst.org per default, but you can set up your own with this package.

### epidocConverter.saxon.class.php
The renderer. Uses Saxon/C XSLT 2.0 Processor and the default EpidDoc-Stylesheets in /xsl.

### epidocConverter.saxon03.class.php
Old Version epidocConverter.saxon.class.php using Saxon/C 0.3 beta. Not sure if still working but included just in case.

### tei-epidoc.dtd
DTD fpr Epidoc. Necessary in some cases.

### [xsl]
Epidoc example stylesheets as found here https://sourceforge.net/p/epidoc/wiki/Stylesheets/, slightly modified as follows:
- htm-teidivapparatus.xsl

### [xslShort]
Some simple xslt 1.0 (using some php-xstl-functions) to render the edition part of an EpidDoc.

###  [test]

#### testSuite.php
A simple Page to test the EpidocConverter.

#### [testData]
A collection of EpidDoc-Files from several Projects.

### [api]
A simple server script to set up an epidoc rendering server. Ir you want to render your files on one machine but have the Saxon/C
intalled on another.
