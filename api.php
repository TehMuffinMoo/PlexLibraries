<?php
$app->get('/plugins/plexlibraries/settings', function ($request, $response, $args) {
	$plexLibrariesPlugin = new plexLibrariesPlugin();
	if ($plexLibrariesPlugin->checkRoute($request)) {
		if ($plexLibrariesPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $plexLibrariesPlugin->_pluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/plexlibraries/shares', function ($request, $response, $args) {
        $Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
        if ($Organizr->checkRoute($request)) {
            $plexLibraries = new plexLibrariesPlugin;
            $GLOBALS['api']['response']['data'] = $plexLibraries->getPlexShares();
            $response->getBody()->write(jsonE($GLOBALS['api']));
            return $response
                ->withHeader('Content-Type', 'application/json;charset=UTF-8')
                ->withStatus($GLOBALS['responseCode']);
        }
});

$app->post('/plugins/plexlibraries/shares', function ($request, $response, $args) {
	$plexLibraries = new plexLibrariesPlugin;
	$GLOBALS['api']['response']['data'] = $plexLibraries->updatePlexShares($_POST);
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});