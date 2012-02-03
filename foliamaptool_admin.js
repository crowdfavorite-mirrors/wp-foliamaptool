/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

	Copyright 2010 Carsten Jonstrup
*/

// *********************************************************************
// Admin functions for options screen
// *********************************************************************

var foliamaptool_maps = new Array;

// Check if API is valid
function mappCheckAPI() {
	var apiKey = document.getElementById('api_key');
	var apiMessage = document.getElementById('api_message');
	var apiBlock = document.getElementById('api_block');
	//var googleLink = '<a target="_blank" href="http://code.google.com/apis/maps/signup.html">' + mappressl10n.here + '</a>';
	if (apiKey.value == "") {
		apiBlock.className = 'api_error';
		apiMessage.innerHTML = "Error key missing";
		return;
	}else{



    jQuery.getJSON("http://api.FoliamapTool.com/v3/keys/?apiKey="+jQuery("#api_key").val()+"&format=json&callback=?",function(obj){

      if(obj.data){
        apiBlock.className = '';
	    apiMessage.innerHTML = "Hello " + obj.data.userName;
      }
      else{
        apiBlock.className = 'api_error';
	    apiMessage.innerHTML = "Wrong key";
      }

    });


    }



}

// *********************************************************************
// Admin functions for post edit screen
// *********************************************************************

// Admin screen initalization
jQuery(document).ready(function($){


	jQuery('#foliamaptool_insert').click(function(e) {
		foliamapInsertShortCode();
	});


});

// Insert foliamaptool shortcode in post
function foliamapInsertShortCode () {
	//shortcode = "[foliamaptool]";
	//send_to_editor(shortcode);

    var id = jQuery("select#foliamaptool_maps").val();

    //alert(foliamaptool_maps[id].mapUrl);

    send_to_editor('<iframe src="'+foliamaptool_maps[id].mapUrl+'" width="'+foliamaptool_maps[id].mapWidth+'" height="'+foliamaptool_maps[id].mapHeight+'"></iframe>');



    return false;
}


function populateMaps(key){

 foliamaptool_maps.length = 0;

 jQuery.getJSON("http://api.FoliamapTool.com/v3/maps/?apiKey="+key+"&format=json&callback=?",function(obj){


 if (obj.data == null){
   return;
 }


 var combo = document.getElementById("foliamaptool_maps");

 jQuery("#combo").find('option').remove();

 jQuery.each(obj.data, function(i,map){

 var option = document.createElement("option");
 option.text = map.mapName;
 option.value = map.mapId;

 foliamaptool_maps[map.mapId] = map;

 try {
 combo.add(option, null); //Standard
 }catch(error) {
 combo.add(option); // IE only
 }

 });

 });


jQuery("select#foliamaptool_maps").change(function () {





        });





}




