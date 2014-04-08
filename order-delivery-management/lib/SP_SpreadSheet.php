<?php

session_start();

set_include_path( dirname( __FILE__ ) );
require_once 'Google/Client.php';
require_once 'Google/Http/MediaFileUpload.php';
require_once 'Google/Service/Drive.php';

class SP_SpreadSheet {
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct( $client_id, $client_secret, $redirect_uri ) {
	$this->client_id = $client_id;
	$this->client_secret = $client_secret;
	$this->redirect_uri = $redirect_uri;
    }
    
    public function setClientId ( $client_id ) {
	$this->client_id = $client_id;
    }
    
    public function setClientSecret ( $client_secret ) {
	$this->client_secret = $client_secret;
    }
    
    public function setRedirectUri ( $redirect_uri ) {
	$this->redirect_uri = $redirect_uri;
    }
    
    public function uploadFile( $filename, $filepath ) {
	$client = new Google_Client();
	$client->setClientId($this->client_id);
	$client->setClientSecret($this->client_secret);
	$client->setRedirectUri($this->redirect_uri);
	$client->addScope("https://www.googleapis.com/auth/drive");
	$service = new Google_Service_Drive($client);

	if (isset($_REQUEST['logout'])) {
	    unset($_SESSION['upload_token ']);
	}

	if (isset($_GET['code'])) {
	    $code = explode( '/', $_GET['code'] );
	    $client->authenticate($code[1]);
	    $_SESSION['upload_token'] = $client->getAccessToken();
	    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
	}

	if (isset($_SESSION['upload_token']) && $_SESSION['upload_token']) {
	    $client->setAccessToken($_SESSION['upload_token']);
	    if ($client->isAccessTokenExpired()) {
		unset($_SESSION['upload_token']);
	    }
	} else {
	    $authUrl = $client->createAuthUrl();
	    header( 'Location:'.$authUrl );
	}

	/************************************************
	  If we're signed in then lets try to upload our
	  file.
	************************************************/
	if ($client->getAccessToken()) {
	    $file = new Google_Service_Drive_DriveFile();
	    $file->title = $filename;
	    $chunkSizeBytes = filesize($filepath);

	    // Call the API with the media upload, defer so it doesn't immediately return.
	    $client->setDefer(true);
	    
	    // Check out https://developers.google.com/drive/v2/reference/files/insert
	    $opt_array = array(
		'convert' => true,
		'useContentAsIndexableText' => true,
		'visibility' => 'DEFAULT',
		'pinned' => true,
		'ocr' => false
	    );
	    $request = $service->files->insert($file, $opt_array);

	    // Create a media file upload to represent our upload process.
	    $media = new Google_Http_MediaFileUpload(
		$client,
		$request,
		'application/vnd.google-apps.spreadsheet',
		null,
		true,
		$chunkSizeBytes
	    );
	    $media->setFileSize(filesize($filepath));

	    // Upload the various chunks. $status will be false until the process is
	    // complete.
	    $status = false;
	    $handle = fopen($filepath, "rb");
	    while (!$status && !feof($handle)) {
	      $chunk = fread($handle, $chunkSizeBytes);
	      $status = $media->nextChunk($chunk);
	    }

	    // The final value of $status will be the data from the API for the object
	    // that has been uploaded.
	    $result = false;
	    if ($status != false) {
	      $result = $status;
	    }

	    fclose($handle);
	}
    }
}