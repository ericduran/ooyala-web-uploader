<?php
require 'OoyalaApi.php';

/**
 * Place your API keys from the Ooyala backlot here. It is highly recommended
 * that this example not be used on a publicly accesible web server, as any
 * user who can access this example will be able to upload assets to your
 * backlot account.
 */
define("OOYALA_API_KEY", "");
define("OOYALA_API_SECRET", "");

/**
 * Parse out the path of the object being accessed. Ensures that we are posting
 * a request and on the assets path. Your application should have something
 * much more robust and sane than this.
 *
 * @param $path_info
 *   A string containg the path being requested, with the leading slash.
 *
 * @return
 *   An array of the path components, or FALSE if the path could not be parsed.
 */
function parsePath($path_info) {
  $path = explode('/', substr($_SERVER['PATH_INFO'], 1));
  if ($_SERVER['REQUEST_METHOD'] != 'POST' || $path[0] != 'assets') {
    return FALSE;
  }
  return $path;
}

/**
 * Kill this request with a 403.
 */
function http403() {
  header("HTTP/1.1 403 Access denied");
  exit;
}

/**
 * Kill this request with a 500.
 *
 * @param $e
 *   The exception that caused this request to fail.
 */
function http500($e) {
  header("Status: 500 Internal Server Error");
  error_log($e->getMessage());
  exit;
}

// End of functions, begin our script here.
if (!isset($_SERVER['PATH_INFO']) || !$path = parsePath($_SERVER['PATH_INFO'])) {
  http403();
}

$api = new OoyalaApi(OOYALA_API_KEY, OOYALA_API_SECRET);

// We can't use $_POST since that only works if we are posting urlencoded data
// and not pure JSON.
$clientAsset = json_decode(file_get_contents("php://input"));

if (count($path) == 1) {
  // Build our request to create the new asset.
  $asset = new stdClass();
  $properties = array(
    'name',
    'description',
    'file_name',
    'file_size',
    'chunk_size',
    'asset_type',
  );
  foreach ($properties as $property) {
    if (!isset($clientAsset->$property)) {
      header("HTTP/1.1 403 Access denied");
      exit;
    }
    $asset->{$property} = $clientAsset->{$property};
  }

  try {
    $asset = $api->post("assets", $asset);
    $uploading_urls = $api->get("assets/" . $asset->embed_code . "/uploading_urls");

    $response = new stdClass();
    $response->embed_code = $asset->embed_code;
    $response->uploading_urls = $uploading_urls;
    exit(json_encode($response));
  }
  catch(Exception $e){
    http500($e);
  }
}
else {
  $embed_code = $path[1];
}

