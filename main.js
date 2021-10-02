/* This file is loaded when Organizr is loaded */
// Load once Organizr loads
$('body').arrive('#activeInfo', {onceOnly: true}, function() {
	plexLibrariesPluginLaunch();
	plexLibrariesPluginLoadShares();
});
// FUNCTIONS
function plexLibrariesPluginLaunch(){
	var menuList = `<li><a class="inline-popups plexLibrariesModal" href="#plexLibraries-area" data-effect="mfp-zoom-out"><i class="fa fa-tv fa-fw"></i> <span lang="en">Plex Libraries</span></a></li>`;
	var htmlDOM = `
	<div id="plexLibraries-area" class="white-popup mfp-with-anim mfp-hide">
		<div class="col-md-4 col-md-offset-4">
			<div class="panel bg-org panel-info">
				<div class="panel-heading">
					<span lang="en">Customise Plex Libraries</span>
				</div>
				<div class="panel-body">
					<div id="plexLibrariesTable">
						<div class="white-box m-b-0">
							<div class="user-btm-box" id="plexLibraries">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	`;
	$('.append-menu').after(menuList);
	$('.organizr-area').after(htmlDOM);
}

function plexLibrariesPluginLoadShares(){
	$.getJSON( "api/v2/plugins/plexlibraries/shares", function( data ) {
		$(function() {
			// Plex Admin response contains all users shares, mark all toggles as disabled whilst this is a work in progress.
			$.each(data.response.data.SharedServer, function(_, sharedServer) {
				$.each(sharedServer.Section, function(_, obj) {
					plexLibrariesPluginLoadSharesItem(obj,"disabled");
				});
			});
			$.each(data.response.data.SharedServer.Section, function(_, obj) {
					plexLibrariesPluginLoadSharesItem(obj,"");
			});
			const thtml = $("#plexLibraries ");
			thtml.append('<script>onToggle();</script>');
		});
	});
}

function plexLibrariesPluginLoadSharesItem(obj,disabled){
	const thtml = $("#plexLibraries ");
	var mediaType = obj['@attributes'].type
	var mediaShared = obj['@attributes'].shared
	var mediaIcon = "0"
	var checked = "";
	if (mediaType == "movie") {
		var mediaIcon = "video-clapper"
		var mediaIconColour = "purple"
	}
	if (mediaType == "show") {
		var mediaIcon = "video-camera"
		var mediaIconColour = "warning"
	}
	if (mediaShared == 1) {
		var checked = "checked";
	}
	$.each(obj, function(_, text) {
		thtml.append('\
		<div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center">\
			<p class="text-' + mediaIconColour + '"><i class="ti-' + mediaIcon + ' fa-2x"></i></p>\
			<h4 class="">' + obj['@attributes'].title + '</h4>\
			<input type="checkbox" class="js-switch plexLibraries" data-size="small" data-color="#99d683" data-secondary-color="#f96262" value="' + obj['@attributes'].id + '" ' + checked + ' ' + disabled +'>\
		</div>\
		');
	});
}

function onToggle() {
    $('.plexLibraries').change(function () {
        if (this.checked) {
            updateStatus(this.value,"checked");
        } else {
            updateStatus(this.value,"unchecked");
        }
    });
}

function updateStatus(value,checked) {
    $.ajax({
        type: "POST",
        url: "api/v2/plugins/plexlibraries/shares",
        data: {toggle_update: true, shareId: value, checked: checked},
        success: function (result) {
            console.log(result);
        }
    });
}
// EVENTS and LISTENERS