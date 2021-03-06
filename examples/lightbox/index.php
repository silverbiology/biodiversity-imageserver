<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Biodiveristy Image Server Lightbox Example</title>
	
	<link href="../syntaxhighliter/shCore.css" rel="stylesheet" />
    <link href="../syntaxhighliter/shThemeDefault.css" rel="stylesheet" />
    <script src="../syntaxhighliter/xrepexp.js"></script>
	<script src="../syntaxhighliter/shCore.js"></script>
	<script type="text/javascript" src="../syntaxhighliter/shBrushJScript.js"></script>
	<?php
	//	include("../../config.dynamic.php");
	?>
	<link href="ext-all.css" rel="stylesheet" />
	<script src="ext-base.js"></script>
	<script src="ext-all.js"></script>
	
	<link href="lightbox.css" rel="stylesheet" />
	<script src="lightbox.js"></script>
	<script src="lightboxView.js"></script>
		
	<script>
			
			Ext.onReady(function(){
	
				Ext.QuickTips.init();
			
				// Disable browser right click
			
				Ext.fly(document.body).on('contextmenu', function(e, target) {
							e.preventDefault();
						});
				
				Config={
					baseUrl: 'http://images.cyberfloralouisiana.com/portal/'//"<?php print $config['configWebPath'];?>"
				}
				
				var lightDemo = new BisLightbox({
						renderTo:Ext.get('imagepanel')
				});
			}); 
	</script>	 
	
	<script>
			Ext.ux.Lightbox.register('a[rel^=lightbox]');
			Ext.ux.Lightbox.register('a.lb-flower', true); // true to show them as a set
	</script> 
	
	<style>
            .thumbnail {
            	padding: 10px;
            	background-color: #e6e6e0;
            	border: 1px solid #d6d6d0;
            	float: left;
        	}
			
			 
			
			div.item {
					padding: 10px;
				}            
			body,td,th {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 12px;
			}
			a:link {
				color: #036;
			}
			a:visited {
				color: #036;
			}
			a:hover {
				color: #036;
			}
			a:active {
				color: #036;
			}
						
    </style>
	
</head>
<body style="padding: 10px">
	
	<div style="float:right; width: 600px">
			<pre type="syntaxhighlighter" class="brush: js">
&lt;link href="ext-all.css" rel="stylesheet" />	
&lt;script src="ext-base.js">&lt;/script>	
&lt;script src="ext-all.js">&lt;/script>		
			
&lt;link href="lightbox.css" rel="stylesheet" />
&lt;script src="lightbox.js">&lt;/script>
&lt;script src="lightboxView.js">&lt;/script>
&lt;script>
Ext.onReady(function() {

	Ext.QuickTips.init();
			
	Ext.fly(document.body).on('contextmenu', function(e, target) {
							e.preventDefault();
						});
				
	Config={
			baseUrl: 'http://images.cyberfloralouisiana.com/portal/'
		}
				
	var lightDemo = new BisLightbox({
			renderTo:Ext.get('imagepanel')
	});
  
});
&lt;/script>
&lt;script>
			Ext.ux.Lightbox.register('a[rel^=lightbox]');
			Ext.ux.Lightbox.register('a.lb-flower', true); // true to show them as a set
&lt;/script> 

...

&lt;div id= "imagepanel" style="padding: 5px; height: 280px; width: 230px; background-color: #E6E6E0">&lt;/div>

...</pre>
      </div>
	
<h3>Biodiveristy Image Server Lightbox Example</h3><br>
	<div id= "imagepanel" style="padding: 5px; height: 270px; width: 230px; background-color: #E6E6E0"></div><br>
		
	<p>This example can be placed on any webpage.<br>
        <a href="../">See more examples...</a><br>
        <br>
        <script type="text/javascript">
				 SyntaxHighlighter.all()
			  </script>
     </p>
	  
	
</body>
</html>