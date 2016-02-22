<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl"  xmlns:t="http://www.tei-c.org/ns/1.0" exclude-result-prefixes="php">

  <!-- glyphs -->
  <xsl:include href="teig.xsl" />


  <xsl:template match="//t:div[@type = 'edition']">
    <div id="edition">
      <xsl:attribute name="class">
        <xsl:text>edition lang_</xsl:text>
        <xsl:value-of select="@xml:lang"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  
  <xsl:template match="//t:div[@type = 'textpart']">
    <div id="textpart">
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  
  <xsl:template match="//t:pb">
    <div class='pb'><xsl:value-of select="@n"/></div>
  </xsl:template>
  
  <xsl:template match="//t:ab/text()">
    <xsl:value-of select="." />
  </xsl:template>
  
  
  <xsl:template match="//t:p">
    <span>

      <xsl:attribute name="class">
        <xsl:text>lang_</xsl:text>
        <xsl:value-of select="@xml:lang"/>
      </xsl:attribute>

      <xsl:apply-templates/>
    
    </span>
  </xsl:template>


  <xsl:template match="//t:lb">
    <xsl:if test="(local-name(preceding-sibling::*[1]) != 'pb') and (local-name(preceding-sibling::*[1]) != 'div') and (local-name(preceding-sibling::*[1]) != 'p')">
      <br />
    </xsl:if>
    <span class="linenumber">
      <xsl:if test="@n &gt; 0 and @n mod 5 = 0"><xsl:value-of select="@n" /></xsl:if>
    </span>
    <!--{<xsl:value-of select="local-name(preceding-sibling::*[1])" />}-->
  </xsl:template>
  
  
  <xsl:template match="//t:ex">
  	<span class="ex">
    	<xsl:text>(</xsl:text><xsl:value-of select="." /><xsl:text>)</xsl:text>
    </span>
  </xsl:template>
  
  <xsl:template match="//t:abbr">
  	<span class="abbr">
    	<xsl:text></xsl:text><xsl:value-of select="." /><xsl:text></xsl:text>
    </span>
  </xsl:template>  
  
  <xsl:template match="//t:gap">
    <span class="gap">
      <xsl:choose>
        <xsl:when test="@quantity and @unit='character'">
          <xsl:value-of select="php:function('str_repeat', '.', string(@quantity))" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>---</xsl:text>
        </xsl:otherwise>
      </xsl:choose>

    </span>
  </xsl:template>
  
  <xsl:template match="//t:head">
  </xsl:template>
  
  <xsl:template match="//t:supplied">
    <span>
      <xsl:attribute name="class">supplied supplied_<xsl:value-of select='@cert' /></xsl:attribute>
      <xsl:text>[</xsl:text>
      <xsl:apply-templates/><xsl:if test="@cert = 'low'"><xsl:text>?</xsl:text></xsl:if>
      <xsl:text>]</xsl:text>
    </span>
  </xsl:template>  
  
  <xsl:template match="//t:note">
    <span class="note"><xsl:text>(</xsl:text><xsl:value-of select="." /><xsl:text>)</xsl:text></span>
  </xsl:template>
  
  <xsl:template match="//t:choice">
    <span class="choice">
      <xsl:attribute name="title">
        <xsl:value-of select="reg" />
      </xsl:attribute>
      <xsl:value-of select="orig" /><xsl:text> </xsl:text>
    </span>
  </xsl:template>

  <xsl:template match="//t:unclear">
    <span class="unclear"><xsl:value-of select="." /></span>
  </xsl:template>

  
</xsl:stylesheet>
