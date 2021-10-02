<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['plexlibraries'] = array( // Plugin Name
	'name' => 'Plex Libraries', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'Library Management', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'PLEXLIBRARIES', // html element id prefix (All Uppercase)
	'configPrefix' => 'PLEXLIBRARIES', // config file prefix for array items without the hypen (All Uppercase)
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/plexLibraries/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/plexlibraries/settings', // api route for settings page (All Lowercase)
	'homepage' => false // Is plugin for use on homepage? true or false
);

class plexLibrariesPlugin extends Organizr
{
	public function _pluginGetSettings()
	{

		$libraryList = [['name' => 'Refresh page to update List', 'value' => '', 'disabled' => true]];
		if ($this->config['plexID'] !== '' && $this->config['plexToken'] !== '') {
			$libraryList = [];
			$loop = $this->plexLibraryList('key')['libraries'];
			foreach ($loop as $key => $value) {
				$libraryList[] = ['name' => $key, 'value' => $value];
			}
		}

		return array(
			'Plex Libraries Settings' => array(
				array(
					'type' => 'password-alt',
					'name' => 'plexToken',
					'label' => 'Plex Token',
					'value' => $this->config['plexToken'],
				),
				array(
					'type' => 'password-alt',
					'name' => 'plexID',
					'label' => 'Plex Machine',
					'value' => $this->config['plexID'],
				),
				array(
					'type' => 'input',
					'name' => 'plexAdmin',
					'label' => 'Plex Admin Username',
					'value' => $this->config['plexAdmin'],
				),
				array(
					'type' => 'select2',
					'name' => 'PLEXLIBRARIES-librariesToInclude',
					'label' => 'Plex Libraries to Include',
					'value' => $this->config['PLEXLIBRARIES-librariesToInclude'],
					'options' => $libraryList,
				),
			),
			'NOTE' => array(
				array(
					'type' => 'html',
					'label' => 'Note',
					'html' => '<p>Authentication settings can be setup using the "Get Plex Token" button when selecting Plex as the Authentication backend. It also shares the same settings with the Plex homepage item.</p>
					           <p>The Plex Admin Username is not shared with the other settings.</p>'
				)
			)
		);
	}


	public function getPlexShares($includeAll = false) {
		if (!empty($this->config['plexToken'])) {
			$headers = array(
				'Content-type: application/xml',
				'X-Plex-Token' => $this->config['plexToken'],
			);
			$url = 'https://plex.tv/api/users';
			try {
				$response = Requests::get($url, $headers, array());
				if ($response->success) {
					libxml_use_internal_errors(true);
					$userXML = simplexml_load_string($response->body);
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
				return false;
			}

		    $Username = $this->user['username'];
			if ($Username == $this->config['plexAdmin']) {
				$url = 'https://plex.tv/api/servers/'.$this->config['plexID'].'/shared_servers/';
				try {
					$response = Requests::get($url, $headers, array());
					if ($response->success) {
						libxml_use_internal_errors(true);
						$shareXML = simplexml_load_string($response->body);
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
					return false;
				}
				return $shareXML;
			} else {
				$UserData = $userXML->xpath('//User[@username="'.$Username.'"]');
				$url = 'https://plex.tv/api/servers/'.$this->config['plexID'].'/shared_servers/'.$UserData[0]->Server->attributes()->id;
				try {
					$response = Requests::get($url, $headers, array());
					if ($response->success) {
						libxml_use_internal_errors(true);
						$shareXML = simplexml_load_string($response->body);
						if (!$includeAll) {
							$librariesToInclude = explode(",",$this->config['PLEXLIBRARIES-librariesToInclude']);
							foreach ($shareXML->xpath('//Section') as $share) {
								if (!in_array($share->attributes()->key, $librariesToInclude)) {
									$key = $share->attributes()->key;
									unset($share[0]);
								}
							}
						}
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
					return false;
				}
				return $shareXML;
			}
		}
	}

    public function call_endpoint($url, $method = 'GET', $args = false)
    {
        $postdata = ($args) ? json_encode($args) : '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($postdata)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
		return $response;
        curl_close($ch);
    }

	public function updatePlexShares($data) {
		if ($data["checked"] == "checked") {
			$Shares = $this->getPlexShares(true);
			$xpath = $Shares->xpath('//SharedServer//Section[@shared="1"]');
			$NewShares = array();
			foreach ($xpath as $Share) {
				$ShareString = (string)$Share->attributes()->id;
				$NewShares[] = $ShareString;
			}
			if (!in_array($data["shareId"], $NewShares)) {
				$NewShares[] = $data["shareId"];
			}
			$Msg = "Enabling ".$data["shareId"];
		} else {
			$Shares = $this->getPlexShares(true);
			$xpath = $Shares->xpath('//SharedServer//Section[@shared="1"]');
			$NewShares = array();
			foreach ($xpath as $Share) {
				$ShareString = (string)$Share->attributes()->id;
				if ($ShareString != $data["shareId"]) {
					$NewShares[] = $ShareString;
				}
			}
			$Msg = "Disabling ".$data["shareId"];
		}
		if (empty($NewShares)) {
			$this->setAPIResponse('error', 'You must have at least one share.', 400);
		} else {
			$http_body = array(
				"server_id" => $this->config['plexID'],
				"shared_server" => array(
					"library_section_ids" => $NewShares
			));
			$url = 'https://plex.tv/api/servers/'.$this->config['plexID'].'/shared_servers/'.$Shares->SharedServer->attributes()->id.'?X-Plex-Token='.$this->config['plexToken'];
			$response = $this->call_endpoint($url, "PUT", $http_body);
			$this->setAPIResponse('success', $Msg, 200);
		}
	}
}