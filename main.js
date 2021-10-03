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
							<div class="col-12" style="width: 1514px" style="display:block">
								<select name="plexUsers" id="plexUsers" style="width: 26%">
										<option value="">- Plex User -</option>
								</select><br>
							</div>
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
			// Single Response
			if (data.response.data.SharedServer.Section) {
				$.each(data.response.data.SharedServer.Section, function(_, obj) {
						plexLibrariesPluginLoadSharesItem(obj,"","");
				});
			} else {
				// Plex Admin response contains all users shares, mark all toggles as disabled whilst this is a work in progress.
				$.each(data.response.data.SharedServer, function(_, sharedServer) {
					const thtml = $("#plexUsers ");
					var username = sharedServer['@attributes'].username;
					thtml.append('<option value="'+username+'">'+sharedServer['@attributes'].username+'</option>');
					$.each(sharedServer.Section, function(_, obj) {
						plexLibrariesPluginLoadSharesItem(obj,"disabled",username);
					});
				});
			}
			const thtml = $("#plexLibraries ");
			thtml.append('<script>onToggle();</script>');
			thtml.append('<script>onSelect();</script>');
		});
	});
}

function plexLibrariesPluginLoadSharesItem(obj,disabled,username){
	const thtml = $("#plexLibraries ");
	var mediaType = obj['@attributes'].type
	var mediaShared = obj['@attributes'].shared
	var mediaIcon = "0"
	var checked = "";
	switch(mediaType) {
		case 'movie':
			var mediaIcon = "video-clapper"
			var mediaIconColour = "purple"
			break;
		case 'show':
			var mediaIcon = "video-camera"
			var mediaIconColour = "warning"
			break;
		case 'artist':
			var mediaIcon = "music-alt"
			var mediaIconColour = "info"
			break;
		case 'photo':
			var mediaIcon = "camera"
			var mediaIconColour = "danger"
			break;
	}
	if (mediaShared == 1) {
		var checked = "checked";
	}
	if (username === "") {
		var display = "true"
	} else {
		var display = "none"
	}
	$.each(obj, function(_, text) {
		thtml.append('\
		<div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center plexUser ' + username + '" style="display:' + display + '">\
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

function onSelect() {
    $('#plexUsers').change(function () {
        alert(this.value);
		var plexUsers = document.getElementsByClassName('plexUser');
		var plexUser = document.getElementsByClassName(this.value);
		for(i = 0; i < plexUsers.length; i++) {
            i.style.display = "none";
            }
		for(i = 0; i < plexUser.length; i++) {
            i.style.display = "block";
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