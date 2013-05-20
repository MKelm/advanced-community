<?xml version="1.0"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml"
  exclude-result-prefixes="#default"
>
<!--
  @papaya:modules ACommunityCommentsBox, ACommunityCommentsRankingBox, ACommunityCommentersRankingBox, ACommunitySurferGalleryUploadBox, ACommunitySurferGalleryFoldersBox, ACommunitySurferGalleryTeaserBox, ACommunitySurferStatusBox, ACommunitySurfersListBox
-->

<!-- import main layout rules, this will import the default rules, too -->
<xsl:import href="./base/boxes.xsl" />

<!-- import module specific rules, this overrides the content-area and other default rules -->
<xsl:import href="./modules/external/ACommunity/Boxes.xsl"/>

<!-- to change the output, redefine the imported rules here -->

</xsl:stylesheet>
