<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>
      Dashboard
    </title>
    <meta http-equiv="content-type" content=
    "text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href=
    "assets/style.css" />
    <script type="text/javascript" charset="utf-8" src=
    "_resource/js/jquery.min.js">
</script>
    <script type="text/javascript">
//<![CDATA[


    // remap jQuery to $
    (function($){})(window.jQuery);

    function selectApp(targetRadio) {



        // fake the toggling of checkboxes when clicking on status bar buttons or shed map hotspots
        if($('#'+targetRadio).is(':checked')) {


                $('#'+targetRadio).prop('checked', false);
        } else {




                $('#'+targetRadio).prop('checked', true);
        }
        $(this).click(function(event) {
                // event.preventDefault(); 
                // prevent the default behaviour of clicking on the link (eg jumping to the top of the page because the href is "#" )
        });
    }

    /* trigger when page is ready */
    $(document).ready(function (){

        // your functions go here
        $("#status .statusbutton").toggle( // find all buttons on the status bar and toggle the classes when clicked 
                function() {
                        $(this).addClass('checked');
                }, 
                function() {
                        $(this).removeClass('checked');
                }
        );
        
        $("#building .toggle").toggle( // find all hotspots on the shed map and toggle the classes when clicked 
                function() {
                        $(this).addClass('checked');
                }, 
                function() {
                        $(this).removeClass('checked');
                }
        );
        

    });
    //]]>
    </script>
  </head>
  <body>
    <div id="center">
      <noscript>
      <h3>
        Your browser does not support JavaScript! This page will
        not work correctly.
      </h3></noscript> <img draggable="false" src=
      "assets/walls.svg" id="walls" /> 
      <!-- <div id="node29"></div> --><!-- #node29 end -->
       <!-- <div id="node28"></div> --><!-- #node28 end -->
       <!-- <div id="node27"></div> --><!-- #node27 end -->
      <div id="node1" style="border:1px solid black;">
        <div id="node35"></div><!-- #node35 end -->
      </div><!-- #node1 end -->
      <!-- <div id="node31"></div> --><!-- #node31 end -->
      <!-- <div id="node30"></div> --><!-- #node30 end -->
      <div id="node2" style="border:1px solid black;"></div>
      <!-- #node2 end -->
      <div id="node3" style="border:1px solid black;"></div>
      <!-- #node3 end -->
      <div id="node5" style="border:1px solid black;"></div>
      <!-- #node5 end -->
      <div id="node4" style="border:1px solid black;"></div>
      <!-- #node4 end -->
      <div id="node6" style="border:1px solid black;">
        <!--    <div id="node22"></div> --><!-- #node22 end -->
      </div><!-- #node6 end -->
      <div id="node12" style="border:1px solid black;"></div>
      <!-- #node12 end -->
      <div id="node11" style="border:1px solid black;"></div>
      <!-- #node11 end -->
      <div id="node7" style="border:1px solid black;"></div>
      <!-- #node7 end -->
      <!-- <div id="node32"></div> --><!-- #node32 end -->
      <div id="node24" style="border:1px solid black;"></div>
      <!-- #node24 end -->
      <!-- <div id="node26"></div> --><!-- #node26 end -->
      <div id="node23" style="border:1px solid black;"></div>
      <!-- #node23 end -->
      <div id="node13" style="border:1px solid black;"></div>
      <!-- #node13 end -->
      <div id="node8" style="border:1px solid black;"></div>
      <!-- #node8 end -->
      <div id="node10" style="border:1px solid black;">
        <div id="node20" style="border:1px solid black;"></div>
        <!-- #node20 end -->
        <div id="node21" style="border:1px solid black;"></div>
        <!-- #node21 end -->
      </div><!-- #node10 end -->
      <div id="node9" style="border:1px solid black;"></div>
      <!-- #node9 end -->
      <div id="node15" style="border:1px solid black;">
        <div id="node16" style="border:1px solid black;"></div>
        <!-- #node16 end -->
        <div id="node17" style="border:1px solid black;"></div>
        <!-- #node17 end -->
      </div><!-- #node15 end -->
      <!-- <div id="node25"></div> --><!-- #node25 end -->
      <div id="node14" style="border:1px solid black;">
        <div id="node18" style="border:1px solid black;"></div>
        <!-- #node18 end -->
        <div id="node19" style="border:1px solid black;"></div>
        <!-- #node19 end -->
      </div><!-- #node14 end -->
      <!-- <span id="node146"></span> --><!-- #node146 end -->
      <!-- <span id="node145"></span> --><!-- #node145 end -->
    </div>
  </body>
</html>