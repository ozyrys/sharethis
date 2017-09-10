<?php

/**
 * This handles the input and response for the Publishing Endpoints from
 * the PublishThis platform
 * Current Actions
 * 1 - Verify
 * 2 - Publish
 */
class Publishthis_Endpoint {
  private $obj_api;
  private $obj_publish;

  function __construct() {
	  $this->obj_api     = new Publishthis_API();
	  $this->obj_publish = new Publishthis_Publish();
  }

  /**
   * Escape sprecial characters
   */
  function escapeJsonString($value) { // list from www.json.org: (\b backspace, \f formfeed)
	  $escapers     = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
	  $replacements = array(
	    "\\\\",
	    "\\/",
	    "\\\"",
	    "\\n",
	    "\\r",
	    "\\t",
	    "\\f",
	    "\\b"
	  );
	  $result       = str_replace($escapers, $replacements, $value);
	  $escapers     = array('\":\"', '\",\"', '{\"', '\"}');
	  $replacements = array('":"', '","', '{"', '"}');
	  $result       = str_replace($escapers, $replacements, $result);
	  return $result;
  }

  /**
   * Returns json response with failed status
   */
  function sendFailure($message) {
	  $obj = NULL;

	  $obj->success      = FALSE;
	  $obj->errorMessage = $this->escapeJsonString($message);

	  $this->sendJSON($obj);
  }

  /**
   * Returns json response with succeess status
   */
  function sendSuccess($message) {
	  $obj = NULL;

	  $obj->success      = TRUE;
	  $obj->errorMessage = NULL;

	  $this->sendJSON($obj);
	}

  /*
  * Send object in JSON format
  */
	private function sendJSON($obj) {
	  header('Content-Type: application/json');
	  echo json_encode($obj);
  }

  /**
   * Verify endpoint action
   */
  private function actionVerify() {
	  //first check to make sure we have our api token
	  $apiToken = $this->obj_api->_get_token('api_token');

	  if (empty($apiToken)) {
	    $message = array  (
		    'message' => 'Verify Plugin Endpoint',
		    'status'  => 'error',
		    'details' => 'Asked to verify our install at: ' . date("Y-m-d H:i:s") . ' failed because api token is not filled out'
			);
	    $this->obj_api->_log_message($message, "1");

	    $this->sendFailure("No API Key Entered");
	    return;
	  }

	  //then, make a easy call to our api that should return our basic info.
	  $apiResponse = $this->obj_api->get_client_info();

	  if (empty($apiResponse)) {
	    $message = array(
	  	  'message' => 'Verify Plugin Endpoint',
	  	  'status'  => 'error',
	  	  'detai  ls' => 'Asked to verify our install at: ' . date("Y-m-d H:i:s") . ' failed because api token is not valid'
	    );
	    $this->obj_api->_log_message($message, "1");

	    $this->sendFailure("API Key Entered is not Valid");
	    return;
	  }

	  //if we got here, then it is a valid api token, and the plugin is installed.

	  $message = array(
	    'message' => 'Verify Plugin Endpoint',
	    'status'  => 'info',
	    'details' => 'Asked to verify our install at: ' . date("Y-m-d H:i:s")
	  );
	  $this->obj_api->_log_message($message, "2");

	  $this->sendSuccess("");
  }

  /**
   * Publish endpoint action
   * we get the information and then publish the feed
   * here is the info being passed right now
   * action: "publish",
   * feedId: 123,
   * templateId: 456,
   * clientId: 789,
   * userId: 21,
   * publishDate: Date
   *
   * @param integer $feedId
   */
  private function actionPublish($postId, $nid) {
	  if (empty($postId)) {
	    $this->sendFailure("Empty post id");
	    return;
	  }

	  //ok, now go try and publish the post passed in
	  try {
	    $this->obj_publish->publish_specific_post($postId, $nid);
	  } catch (Exception $ex) {
	    //looks like there was an internal error in publish, we will need to send a failure.
	    //no need to log here, as our internal methods have all ready logged it
	    $this->sendFailure($ex->getMessage());
	    return;
	  }

	  $this->sendSuccess("published");
	  return;
  }

  private function array_values_recursive($arr) {
	  $arr = array_values($arr);
	  foreach ($arr as $key => $val) {
	    if (array_values($val['subcategories']) !== $val['subcategories']) {
		    $arr[$key]['subcategories'] = $this->array_values_recursive($val['subcategories']);
	    }
	  }

	  return $arr;
  }

  private function actionGetAuthors() {
	  $authors = array();
	  $obj     = new stdClass();

	  $users  = entity_load('user');
	  $emails = '';
	  foreach ($users as $user) {
	    if (array_key_exists(3, $user->roles)) {
	  	  if (strlen($emails) > 0) {
	  	    $emails .= ' ' . $user->mail;
	  	  }
	  	  else {
	  	    $emails = $user->mail;
	  	  }
	  	  $authors[] = array('id' => $user->uid, 'name' => $user->name);
	    }
	  }
	  $obj->success      = TRUE;
	  $obj->errorMessage = NULL;
	  $obj->authors      = $authors;

	  $this->sendJSON($obj);
  }

  private function actionGetCategories() {
	  global $pt_settings_value;
		$categories = array();
		if ($pt_settings_value['taxonomy']['get_term'] !== 0) {
			$taxonomies = taxonomy_vocabulary_get_names();
			foreach ($taxonomies as $taxonomie) {
				if ($taxonomie->machine_name == $pt_settings_value['taxonomy_group']) {
					$tax_id = $taxonomie->vid;
				}
				if (!$pt_settings_value['taxonomy_group'] !== 'default') {
					$terms    = taxonomy_get_tree($tax_id);
					$tax_name = taxonomy_vocabulary_load($tax_id);
					foreach ($terms as $term) {
						$category               = array(
							'id'            => intval($term->tid),
							'name'          => $term->name,
							'taxonomyId'    => intval($term->tid),
							'taxonomyName'  => $tax_name->machine_name,
							'subcategories' => array()
						);
						$categories[$term->tid] = $category;
					}
				}
			}
		}
	  $obj               = new stdClass();
	  $obj->success      = TRUE;
	  $obj->errorMessage = NULL;
	  $obj->categories   = $this->array_values_recursive($categories);
	  $this->sendJSON($obj);
  }

  /**
   * Process request main function
   */
  function process_request($token) {
	  global $pt_settings_value;

		// Check if token from request matches Drupal token
		if ($token != $pt_settings_value['endpoint']) {
			$message = array(
				'message' => 'Verify Plugin Endpoint',
				'status' => 'error',
				'details' => 'Asked to verify our install at: ' . date("Y-m-d H:i:s") . ' failed because request token mismatch Drupal token'
			);
			$this->obj_api->_log_message($message, "1");

			$this->sendFailure("Request token mismatch Drupal token");
			return;
		}

	  try {
	    $bodyContent = file_get_contents('php://input');
	    $this->obj_api->_log_message(array(
	  	  'message' => 'Endpoint Request',
	  	  'status'  => 'info',
	  	  'details' => $bodyContent
	    ), "2");

	    $arrEndPoint = json_decode($bodyContent, TRUE);
	    $action      = $arrEndPoint["action"];

			// If it's not "verify" action Validate API token
			if ($action != 'verify') {
				$current_token = $this->obj_api->_get_token();

				$is_token_valid = empty($current_token) ? FALSE : TRUE;
				if (!empty($current_token)) {
					$token_status = $this->obj_api->validate_token($current_token);
					if (!isset($token_status) || $token_status['valid'] != 1) {
						$is_token_valid = FALSE;
					}
				}

				if (!$is_token_valid) {
					$message = array(
						'message' => 'API tokem mismatch',
						'status' => 'error',
						'details' => 'We could not authenticate your API token, please correct the error and try again.'
					);
					$this->obj_api->_log_message($message, "1");

					$this->sendFailure("We could not authenticate your API token, please correct the error and try again.");
					return;
				}
			}

	    switch ($action) {
	  	  case "verify":
	  	    $this->actionVerify();
	  	  break;
	  	  case "publish":
	  	    $postId = intval($arrEndPoint["postId"], 10);
			  	$nid = intval( $arrEndPoint["publishedId"], 10 );
	  	    $this->actionPublish($postId, $nid);
	  	  break;
	  	  case "getAuthors":
	  	    $this->actionGetAuthors();
	  	  break;
	  	  case "getCategories":
	  	    $this->actionGetCategories();
	  	  break;
	  	  default:
	  	    $this->sendFailure("Empty or bad request made to endpoint");
	  	  break;
	    }
	  } catch (Exception $ex) {
	    //we will log this to the pt logger, but we always need to send back a failure if this occurs
	    $this->sendFailure($ex->getMessage());
	  }
	  return;
  }
}

?>
