<?php
//VK API FROM 3.0 TO 5 ADAPTER
//By Shinovon
//https://web.archive.org/web/20130324035338/http://vk.com/developers.php?oid=-1&p=%D0%A0%D0%B0%D1%81%D1%88%D0%B8%D1%80%D0%B5%D0%BD%D0%BD%D1%8B%D0%B5_%D0%BC%D0%B5%D1%82%D0%BE%D0%B4%D1%8B_API
$api = 'https://api.vk.com/method/';
$apiver = '5.133';
$wallapiver = '5.84';
$msgapiver = '5.84';
define('ADDRESS', 'http://nnproject.cc/vk30/api.php');
define('IMAGEPROXY', 'http://nnproject.cc/vk30/img.php');

//Utils
function startsWith($s, $ss) {
	$len = strlen($ss); 
	return (substr($s, 0, $len) === $ss); 
}
function endsWith($s, $ss) {
    $len = strlen($ss);
    if(!$len) {
        return true;
    }
    return substr($s, -$len) === $ss;
}
function jsonstring($str) {
	return str_replace("\r","\\r",str_replace("\n","\\n",str_replace('"',"\\\"", $str)));
}
function get($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'VK');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	//$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $data;
}
function write_url($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'VK');
	curl_exec($ch);
	curl_close($ch);
	exit();
}
function error($errcode = 1, $errmsg = "?", $params = null) {
	if($params != null) {
		if(isset($params['access_token'])) {
			unset($params['access_token']);
			$params['oauth'] = true;
		}
	}
	$jparams = array();
	if($params != null) {
		foreach($params as $k=>$v) {
			array_push($jparams, array('key' => $k, 'value' => $v));
		}
	}
	echo json_encode(
		array("error" => 
			array(
				"error_code" => $errcode, 
				"error_msg" => $errmsg, 
				"request_params" => $jparams
			)
		), 
	JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
	exit();
}
function getUrl($url) {
	if(startsWith($url, 'https:')) {
		return IMAGEPROXY.'?'.urlencode($url);
		//return ADDRESS.'?jpg=1&get='.urlencode($url);
	} else {
		return $url;
	}
}
function parseProfile($usr) {
	$r = array();
	if(property_exists($usr,'first_name')) {
		$r['uid'] = $usr->{'id'};
		if(property_exists($usr,'first_name')) {
			$r['first_name'] = $usr->{'first_name'};
		}
		if(property_exists($usr,'last_name')) {
			$r['last_name'] = $usr->{'last_name'};
		}
		if(property_exists($usr,'photo')) {
			$r['photo'] = getUrl($usr->{'photo'});
		} else if(property_exists($usr,'photo_50')) {
			$r['photo'] = getUrl($usr->{'photo_50'});
		}
		if(property_exists($usr,'bdate')) {
			$r['bdate'] = $usr->{'bdate'};
		}
		if(property_exists($usr,'activity')) {
			$r['activity'] = $usr->{'activity'};
		}
		if(property_exists($usr,'sex')) {
			$r['sex'] = $usr->{'sex'};
		}
		/*if(property_exists($usr,'city')) {
			$r['city'] = $usr->{'city'};
		}*/
		if(property_exists($usr,'online')) {
			$r['online'] =  $usr->{'online'};
		}
		if(property_exists($usr,'counters')) {
			$counters = $usr->{'counters'};
			$cr = array();
			if(property_exists($counters,'albums')) {
				$cr['albums'] = $counters->{'albums'};
			}
			if(property_exists($counters,'audios')) {
				$cr['audios'] = $counters->{'audios'};
			}
			if(property_exists($counters,'followers')) {
				$cr['followers'] = $counters->{'followers'};
			}
			if(property_exists($counters,'friends')) {
				$cr['friends'] = $counters->{'friends'};
			}
			if(property_exists($counters,'online_friends')) {
				$cr['online_friends'] = $counters->{'online_friends'};
			}
			if(property_exists($counters,'photos')) {
				$cr['user_photos'] = $counters->{'photos'};
			}
			if(property_exists($counters,'groups')) {
				$cr['groups'] = $counters->{'groups'};
			}
			$r['counters'] = $cr;
		}
	} else if(property_exists($usr,'name')) {
		$r['gid'] = $usr->{'id'};
		if(property_exists($usr,'name')) {
			$r['name'] = $usr->{'name'};
		}
		if(property_exists($usr,'photo')) {
			$r['photo'] = getUrl($usr->{'photo'});
		} else if(property_exists($usr,'photo_50')) {
			$r['photo'] = getUrl($usr->{'photo_50'});
		}
	}
	return $r;
}

// Gives parsed image urls in array
function parsePhotoSizes($sizes) {
	$s = null;
	$m = null;
	$x = null;
	foreach($sizes as $size) {
		$type = $size->{'type'};
		$url = null;
		if(property_exists($size,'link')) {
			$url = $size->{'link'};
		} else if(property_exists($size,'url')) {
			$url = $size->{'url'};
		}
		if($type == 's') $s = $url;
		if($type == 'm') $m = $url;
		if($type == 'x') $x = $url;
	}
	return array('75p' => $s, '130p' => $m, '604p', $x);
}

function parseAttachments($atts) {
	$r = array();
	foreach($atts as $att) {
		$type = $att->{'type'};
		$a = array();
		if($type == 'photo' || $type == 'posted_photo' || $type == 'graffiti' || $type == 'app') {
			$photo = $att->{$type};
			
			if(property_exists($photo,'sizes')) {
				$sizes = parsePhotoSizes($photo->{'sizes'});
				if(isset($sizes['75p']) && $sizes['75p'] !== null) {
					$a['src_small'] = getUrl($sizes['75p']);
				}
				if(isset($sizes['130p']) && $sizes['130p'] !== null) {
					$a['src'] = getUrl($sizes['130p']);
				}
				if(isset($sizes['604p']) && $sizes['604p'] !== null) {
					$a['src_big'] = getUrl($sizes['604p']);
				}
			} else {
				if(property_exists($photo,'photo_75')) {
					$a['src_small'] = getUrl($photo->{'photo_75'});
				}
				if(property_exists($photo,'photo_130')) {
					$a['arc'] = getUrl($photo->{'photo_130'});
				}
			}
		} else if($type == 'link') {
			
		}
		$x = array('type' => $type, $type => $a);
	}
	return $r;
}
function parsePost($post) {
	$r = array();
	if(property_exists($post,'from_id')) {
		$r['from_id'] = $post->{'from_id'};
	} else if(property_exists($post, 'source_id')) {
		$r['source_id'] = $post->{'source_id'};
	}
	if(property_exists($post,'type')) {
		$r['type'] = $post->{'type'};
	}
	if(property_exists($post,'id')) {
		$r['id'] = $post->{'id'};
	}
	if(property_exists($post,'post_id')) {
		$r['post_id'] = $post->{'post_id'};
	}
	if(property_exists($post,'date')) {
		$r['date'] = $post->{'date'};
	}
	if(property_exists($post,'text')) {
		$r['text'] = $post->{'text'};
	}
	if(property_exists($post,'likes')) {
		$lr = array();
		$lr['count'] = $post->{'likes'}->{'count'};
		if(property_exists($post->{'likes'}, 'user_likes')) {
			$lr['user_likes'] = $post->{'likes'}->{'user_likes'};
		}
		$r['likes'] = $lr;
	}
	if(property_exists($post,'comments')) {
		$cr = array();
		$cr['count'] = $post->{'comments'}->{'count'};
		if(property_exists($post->{'comments'},'can_post')) {
			$cr['can_post'] = $post->{'comments'}->{'can_post'};
		}
		$r['comments'] = $cr;
	}
	if(property_exists($post,'post_source')) {
		$pr = array();
		$type = $post->{'post_source'}->{'type'};
		//if($type == 'api')
		//	$type = 'vk';
		$pr['type'] = $type;
		if(property_exists($post->{'post_source'},'data')) {
			$pr['data'] = $post->{'post_source'}->{'data'};
		}
		$r['post_source'] = $pr;
	}
	if(property_exists($post,'attachments'))
		$r["attachments"] = parseAttachments($post->{'attachments'});
	return $r;
}

function exceptions_error_handler($severity, $message, $filename, $lineno) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

set_error_handler('exceptions_error_handler');
/*
if(isset($_GET['get'])) {
	$get = $_GET['get'];
	if(isset($_GET['jpg'])) {
		header('Content-Type: image/jpeg');
	} else if(isset($_GET['png'])) {
		header('Content-Type: image/png');
	}
	write_url($get);
	exit();
} else if(isset($_POST['get'])) {
	$get = $_POST['get'];
	if(isset($_POST['jpg'])) {
		header('Content-Type: image/jpeg');
	} else if(isset($_POST['png'])) {
		header('Content-Type: image/png');
	}
	write_url($get);
	exit();
}
*/
header('Content-Type: application/json', true);
$PARAMS = $_POST;
if(isset($_GET['access_token']))
	$PARAMS = $_GET;
else if(isset($_POST['access_token']))
	$PARAMS = $_POST;
else
	error(5, 'no access_token');
if(!isset($PARAMS['method']))
	error(8, 'no method', $PARAMS);
if(!isset($PARAMS['v']))
	error(100, '\'v\' param is required', $PARAMS);
$method = $PARAMS['method'];
$token = $PARAMS['access_token'];
$v = $PARAMS['v'];
if($v != '3.0')
	error(100, 'invalid version param', $PARAMS);
//$query = explode('&', $_SERVER['QUERY_STRING'], 2)[1];
try {
	if(startsWith($method, 'audio')) {
		error(3, 'audio api is not supported', $PARAMS);
	}
	switch ($method) {
		case 'messages.get': {
			$g = get($api.'messages.getConversations?access_token='.$token.'&v='.$apiver.'&count=1&offset=0');
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$x = 0;
				if(property_exists($json->{'response'},'unread_count')) {
					$x = $json->{'response'}->{'unread_count'};
				} else {
					$x = 0;
				}
				echo json_encode(array('response' => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'messages.get: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'friends.getRequests': {
			$count = '99';
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$extended = isset($PARAMS['need_messages']) || isset($PARAMS['need_mutual']);
			$g = get($api.'friends.getRequests?access_token='.$token.'&v='.$apiver.'&need_viewed=1&count='.$count.($extended ? '&extended=1' : ''));
			$json = json_decode($g);
			$x = array();
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'}->{'items'};
				if($items) {
					foreach($items as $usr) {
						if($extended) {
							array_push($x, array("uid" => $usr->{'user_id'}));
						} else {
							try {
								array_push($x, $usr->{'user_id'});
							} catch (Exception $unused) {
								array_push($x);
							}
						}
					}
				}
			} else error(10, 'friends.getRequests: json_decode() failed\n"'.$g.'"', $PARAMS);
			echo json_encode(array("response" => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
			exit();
			break;
		}
		case 'getProfiles': {
			$ids = null;
			if(isset($PARAMS['uids'])) {
				$ids = $PARAMS['uids'];
			} else {
				error(100, 'required parameter \'uids\' is not set', $PARAMS);
			}
			$fields = 'uid';
			if(isset($PARAMS['fields'])) {
				$fields = $PARAMS['fields'];
			}
			$fields = str_replace('uid', 'user_id', $fields);
			$fields = str_replace('photo', 'photo,photo_50', $fields);
			// check if groups and users requested
			if(isset($ids) && strpos($ids, '-') != -1) {
				$arr = explode(',', $ids);
				$uids = '';
				$gids = '';
				foreach($arr as $id) {
					if(startsWith($id, '-')) {
						$gids .= substr($id, 1) . ',';
					} else {
						$uids .= $id . ',';
					}
				}
				// remove last comma
				$uids = substr($uids, 0, strlen($uids)-1);
				$gids = substr($gids, 0, strlen($gids)-1);
				
				$ur = array();
				$gr = array();
				// get users
				if(strlen($uids) > 0){
					$g = get($api.'getProfiles?access_token='.$token.'&v='.$apiver.'&user_ids='.$uids.'&fields='.$fields);
					$json = json_decode($g);
					if($json) {
						if(property_exists($json,'error')) {
							echo($g);
							exit();
						}
						$items = $json->{'response'};
						if($items) {
							foreach($items as $usr) {
								array_push($ur, parseProfile($usr));
							}
						}
					} else error(10, 'getProfiles: json_decode() failed\n"'.$g.'"', $PARAMS);
				}
				// get groups
				if(strlen($gids) > 0){
					$g = get($api.'groups.getById?access_token='.$token.'&v='.$apiver.'&group_ids='.$gids.'&fields='.$fields);
					$json = json_decode($g);
					if($json) {
						if(property_exists($json,'error')) {
							echo($g);
							exit();
						}
						$items = $json->{'response'};
						if($items) {
							foreach($items as $grp) {
								array_push($gr, parseProfile($grp));
							}
						}
					} else error(10, 'groups.getById: json_decode() failed\n"'.$g.'"', $PARAMS);
				}
				echo json_encode(array("response" => array_merge($ur, $gr)), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
				break;
			}
			// only users requested
			$g = get($api.'getProfiles?access_token='.$token.'&v='.$apiver.($ids != null ? ('&user_ids='.$ids) : '').'&fields='.$fields);
			$json = json_decode($g);
			$x = array();
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'};
				if($items) {
					foreach($items as $usr) {
						array_push($x, parseProfile($usr));
					}
				}
				echo json_encode(array("response" => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'getProfiles: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'friends.get': {
			$count = null;
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$uid = null;
			if(isset($PARAMS['uid']))
				$uid = $PARAMS['uid'];
			$offset = '0';
			if(isset($PARAMS['offset']))
				$offset = $PARAMS['offset'];
			$fields = null;
			if(isset($PARAMS['fields']))
				$fields = $PARAMS['fields'];
			$g = get($api.'friends.get?access_token='.$token.'&v='.$apiver.($count != null ? '&count='.$count : '').'&offset='.$offset.($uid != null ? '&user_id='.$uid : '').($fields != null ? '&fields='.$fields : ''));
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$x = array();
				$items = $json->{'response'}->{'items'};
				if($items) {
					foreach($items as $usr) {
						if($fields != null)
							array_push($x, parseProfile($usr));
						else array_push($x, $usr);
					}
				}
				echo json_encode(array("response" => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'friends.get: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'wall.get': {
			$count = null;
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$owner_id = null;
			if(isset($PARAMS['owner_id']))
				$owner_id = $PARAMS['owner_id'];
			$offset = '0';
			if(isset($PARAMS['offset']))
				$offset = $PARAMS['offset'];
			$g = get($api.'wall.get?access_token='.$token.'&v='.$wallapiver.($count != null ? '&count='.$count : '').'&offset='.$offset.($owner_id != null ? '&owner_id='.$owner_id : ''));
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$x = array();
				if(property_exists($json->{'response'},'count')) {
					array_push($x, $json->{'response'}->{'count'});
				}
				$items = $json->{'response'}->{'items'};
				if($items) {
					$i = 0;
					foreach($items as $post) {
						array_push($x, parsePost($post));
					}
				}
				echo json_encode(array("response" => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'wall.get: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'newsfeed.get': {
			$count = 15;
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$g = get($api.'newsfeed.get?access_token='.$token.'&v='.$wallapiver.'&count='.$count.'&filters=post&fields=photo,photo_50,name,first_name,last_name,online,sex');
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$x = array();
				$pr = array();
				$gr = array();
				if(property_exists($json->{'response'},'profiles')) {
					$profiles = $json->{'response'}->{'profiles'};
					if($profiles) {
						foreach($profiles as $usr) {
							array_push($pr, parseProfile($usr));
						}
					}
				}
				if(property_exists($json->{'response'},'groups')) {
					$groups = $json->{'response'}->{'groups'};
					if($groups) {
						foreach($groups as $usr) {
							array_push($gr, parseProfile($usr));
						}
					}
				}
				$items = $json->{'response'}->{'items'};
				if($items) {
					foreach($items as $post) {
						array_push($x, parsePost($post));
					}
				}
				echo json_encode(
					array("response" =>
						array(
							"items" => $x,
							"profiles" => $pr,
							"groups" => $gr
						)
					)
				, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'newsfeed.get: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'messages.getDialogs': {
			$count = '20';
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$offset = '0';
			if(isset($PARAMS['offset']))
				$offset = $PARAMS['offset'];
			$g = get($api.'messages.getConversations?access_token='.$token.'&v='.$apiver.'&count='.$count.'&offset='.$offset);
			$json = json_decode($g);
			$r = array();
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'}->{'items'};
				array_push($r, $json->{'response'}->{'count'});
				if($items) {
					foreach($items as $conv) {
						$type = $conv->{'conversation'}->{'peer'}->{'type'};
						$uid = $conv->{'conversation'}->{'peer'}->{'id'};
						$x = array();
						if($type == 'chat') {
							$x['user_id'] = 0;
							$x['chat_id'] = $uid-2000000000;
							$x['title'] = $conv->{'conversation'}->{'chat_settings'}->{'title'};
						} else {
							$x['user_id'] = $uid;
						}
						if(property_exists($conv,'last_message')) {
							$last = $conv->{'last_message'};
							$x['mid'] = $last->{'id'};
							$x['message_id'] = $last->{'id'};
							if(property_exists($last,'from_id')) {
								$x['uid'] = $last->{'from_id'};
							}
							if(property_exists($last,'out')) {
								$x['out'] = $last->{'out'};
							}
							if(property_exists($last,'date')) {
								$x['date'] = $last->{'date'};
							}
							if(property_exists($last,'text')) {
								$x['body'] = $last->{'text'};
							}
							if(property_exists($last,'attachments')) {
								$x['attachments'] = $last->{'attachments'};
							}
						}
						if(property_exists($conv->{'conversation'},'unread_count')) {
							$x['read_state'] = 0;
						} else {
							$x['read_state'] = 1;
						}
						array_push($r, $x);
					}
				}
				echo json_encode(array('response' => $r), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'messages.getDialogs: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'messages.getHistory': {
			$peerId = null;
			if(isset($PARAMS['uid']))
				$peerId = $PARAMS['uid'];
			if(isset($PARAMS['user_id']))
				$peerId = $PARAMS['user_id'];
			if(isset($PARAMS['chat_id']))
				$peerId = $PARAMS['chat_id'];
			$count = '20';
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$offset = '0';
			if(isset($PARAMS['offset']))
				$offset = $PARAMS['offset'];
			if($peerId == null) {
				error(100, 'user_id or chat_id is not set', $PARAMS);
			}
			$g = get($api.'messages.getHistory?access_token='.$token.'&v='.$msgapiver.'&count='.$count.'&offset='.$offset.'&peer_id='.$peerId.'&fields=read_state,text,date,from_id,out,attachments');
			$json = json_decode($g);
			$r = array();
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'}->{'items'};
				array_push($r, $json->{'response'}->{'count'});
				if($items) {
					foreach($items as $msg) {
						$x = array();
						$x['message_id'] = $msg->{'id'};
						if(property_exists($msg, 'text')) {
							$x['body'] = $msg->{'text'};
						} else if(property_exists($msg, 'body')) {
							$x['body'] = $msg->{'body'};
						}
						if(property_exists($msg,'from_id')) {
							$x['from_id'] = $msg->{'from_id'};
						}
						if(property_exists($msg,'date')) {
							$x['date'] = $msg->{'date'};
						}
						if(property_exists($msg,'read_state')) {
							$x['read_state'] = $msg->{'read_state'};
						} else {
							$x['read_state'] = 1;
						}
						if(property_exists($msg,'out')) {
							$x['out'] = $msg->{'out'};
						}
						if(property_exists($msg,'attachments')) {
							$x['attachments'] = parseAttachments($msg->{'attachments'});
						}
						array_push($r, $x);
					}
				}
				echo json_encode(array('response' => $r), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				exit();
			} else error(10, 'messages.getHistory: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'friends.add': {
			if(isset($PARAMS['uid'])) {
				$uid = $PARAMS['uid'];
				$x = array();
				$g = get($api.'friends.add?access_token='.$token.'&v='.$apiver.'&user_id='.$uid);
				$json = json_decode($g);
				if($json) {
					if(property_exists($json,'error')) {
						echo($g);
						exit();
					}
					echo json_encode(array('response' => $json->{'response'}), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
					exit();
				} else error(10, 'friends.add: json_decode() failed\n"'.$g.'"', $PARAMS);
			} else error(100, '"uid" param is not set', $PARAMS);
			break;
		}
		case 'friends.delete': {
			if(isset($PARAMS['uid'])) {
				$uid = $PARAMS['uid'];
				$x = 0;
				$g = get($api.'friends.delete?access_token='.$token.'&v='.$apiver.'&user_id='.$uid);
				$json = json_decode($g);
				if($json) {
					if(property_exists($json,'error')) {
						echo($g);
						exit();
					}
					$s = $json->{'response'};
					if($s == 'success' || $s == 'friend_deleted') {
						$x = 1;
					} else if($s == 'out_request_deleted' || $s == 'in_request_deleted') {
						$x = 2;
					} else if($s == 'suggestion_deleted') {
						$x = 3;
					}
					echo json_encode(array('response' => $x), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
					exit();
				} else error(10, 'friends.delete: json_decode() failed\n"'.$g.'"', $PARAMS);
			} else error(100, '"uid" param is not set', $PARAMS);
			break;
		}
		case 'status.set': {
			if(isset($PARAMS['text'])) {
				$text = $PARAMS['text'];
				$g = get($api.'status.set?access_token='.$token.'&v='.$apiver.'&text='.$text);
				$json = json_decode($g);
				if($json) {
					if(property_exists($json,'error')) {
						echo($g);
						exit();
					}
					echo json_encode(array('response' => $json->{'response'}), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
					exit();
				} else error(10, 'status.set: json_decode() failed\n"'.$g.'"', $PARAMS);
			} else error(100, '"text" param is not set', $PARAMS);
			break;
		}
		case 'messages.send': {
			if(isset($PARAMS['message'])) {
				if(isset($PARAMS['user_id']) || isset($PARAMS['chat_id']) || isset($PARAMS['uid']) || isset($PARAMS['cid'])) {
					$message = $PARAMS['message'];
					$peerid = null;
					if(isset($PARAMS['user_id'])) {
						$peerid = $PARAMS['user_id'];
					} else if(isset($PARAMS['chat_id'])) {
						$peerid = $PARAMS['chat_id'];
					} else if(isset($PARAMS['uid'])) {
						$peerid = $PARAMS['uid'];
					} else if(isset($PARAMS['cid'])) {
						$peerid = $PARAMS['cid'];
					}
					$x = '0';
					$g = get($api.'messages.send?access_token='.$token.'&v='.$apiver.'&message='.$message.'&peer_id='.$peerid.'&random_id='.strval(rand(1,100)));
					$json = json_decode($g);
					if($json) {
						if(property_exists($json,'error')) {
							echo($g);
							exit();
						}
						echo json_encode(array('response' => $json->{'response'}), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
						exit();
					} else error(10, 'messages.send: json_decode() failed\n"'.$g.'"', $PARAMS);
				} else error(100, '"chat_id" param is not set', $PARAMS);
			} else error(100, '"message" param is not set', $PARAMS);
			break;
		}
		case 'photos.getAll': {
			$count = '1';
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$owner = null;
			if(isset($PARAMS['owner_id']))
				$owner = $PARAMS['owner_id'];
			$offset = '0';
			if(isset($PARAMS['offset']))
				$offset = $PARAMS['offset'];
			$g = get($api.'photos.getAll?access_token='.$token.'&v='.$apiver.'&count='.$count.'&offset='.$offset.($owner != null ? '&owner_id='.$owner : '').'&extended=1&photo_sizes=1');
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'}->{'items'};
				$x = $json->{'response'}->{'count'};
				if($items) {
					foreach($items as $pht) {
						$x .= ',{';
						$x .= '"pid":'.$pht->{'id'};
						if(property_exists($pht,'owner_id')) {
							$x .= ',"owner_id":'.$pht->{'owner_id'};
						}
						if(property_exists($pht,'album_id')) {
							$x .= ',"aid":'.$pht->{'album_id'};
						}
						if(property_exists($pht,'date')) {
							$x .= ',"created":'.$pht->{'date'};
						}
						if(property_exists($pht,'text')) {
							$x .= ',"text":"'.jsonstring($pht->{'text'}).'"';
						}
						if(property_exists($pht,'sizes')) {
							$sizes = parsePhotoSizes($pht->{'sizes'});
							if(isset($sizes['75p']) && $sizes['75p'] !== null) {
								$x .= ',"src_small":"'.getUrl($sizes['75p']).'"';
							}
							if(isset($sizes['130p']) && $sizes['130p'] !== null) {
								$x .= ',"src":"'.getUrl($sizes['130p']).'"';
							}
							if(isset($sizes['604p']) && $sizes['604p'] !== null) {
								$x .= ',"src_big":"'.getUrl($sizes['604p']).'"';
							}
						}
						$x .= '}';
					}
				}
				echo('{"response":['.$x.']}');
				exit();
			} else error(10, 'photos.getAll: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		case 'photos.getAlbums': {
			$count = null;
			if(isset($PARAMS['count']))
				$count = $PARAMS['count'];
			$owner = null;
			if(isset($PARAMS['uid']))
				$owner = $PARAMS['uid'];
			else if(isset($PARAMS['gid']))
				$owner = '-'.$PARAMS['gid'];
			$aids = null;
			if(isset($PARAMS['aids']))
				$aids = $PARAMS['aids'];
			$need_covers = null;
			if(isset($PARAMS['need_covers']))
				$need_covers = $PARAMS['need_covers'];
			$g = get($api.'photos.getAlbums?access_token='.$token.'&v='.$apiver.($count != null ? '&count=' : '').$count.($owner != null ? '&owner_id='.$owner : '').($aids != null ? '&album_ids='.$aids : '').($need_covers != null ? '&need_covers='.$need_covers : ''));
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'}->{'items'};
				$x = '';
				if($items) {
					$i = 0;
					foreach($items as $alb) {
						$x .= '{';
						$x .= '"aid":'.$alb->{'id'};
						if(property_exists($alb,'owner_id')) {
							$x .= ',"owner_id":'.$alb->{'owner_id'};
						}
						if(property_exists($alb,'size')) {
							$x .= ',"size":'.$alb->{'size'};
						}
						if(property_exists($alb,'created')) {
							$x .= ',"created":'.$alb->{'created'};
						}
						if(property_exists($alb,'updated')) {
							$x .= ',"updated":'.$alb->{'updated'};
						}
						if(property_exists($alb,'thumb_id')) {
							$x .= ',"thumb_id":'.$alb->{'thumb_id'};
						}
						if(property_exists($alb,'title')) {
							$x .= ',"title":"'.jsonstring($alb->{'title'}).'"';
						}
						if(property_exists($alb,'description')) {
							$x .= ',"description":"'.jsonstring($alb->{'description'}).'"';
						}
						$x .= '}';
						$i++;
						if($i < count($items))
							$x .= ',';
					}
				}
				echo('{"response":['.$x.']}');
				exit();
			} else error(10, 'photos.getAlbums: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		
		case 'photos.getById': {
			$photos = null;
			if(isset($PARAMS['photos']))
				$photos = $PARAMS['photos'];
			$extended = '0';
			if(isset($PARAMS['extended']))
				$extended = $PARAMS['extended'];
			$g = get($api.'photos.getById?access_token='.$token.'&v='.$apiver.'&extended='.$extended.'&photo_sizes=1'.($photos != null ? '&photos='.$photos : ''));
			$json = json_decode($g);
			if($json) {
				if(property_exists($json,'error')) {
					echo($g);
					exit();
				}
				$items = $json->{'response'};
				$x = '';
				if($items) {
					$i = 0;
					foreach($items as $pht) {
						$x .= ',{';
						$x .= '"pid":'.$pht->{'id'};
						if(property_exists($pht,'owner_id')) {
							$x .= ',"owner_id":'.$pht->{'owner_id'};
						}
						if(property_exists($pht,'album_id')) {
							$x .= ',"aid":'.$pht->{'album_id'};
						}
						if(property_exists($pht,'date')) {
							$x .= ',"created":'.$pht->{'date'};
						}
						if(property_exists($pht,'text')) {
							$x .= ',"text":"'.jsonstring($pht->{'text'}).'"';
						}
						if(property_exists($pht,'sizes')) {
							$sizes = parsePhotoSizes($pht->{'sizes'});
							if(isset($sizes['75p']) && $sizes['75p'] !== null) {
								$x .= ',"src_small":"'.getUrl($sizes['75p']).'"';
							}
							if(isset($sizes['130p']) && $sizes['130p'] !== null) {
								$x .= ',"src":"'.getUrl($sizes['130p']).'"';
							}
							if(isset($sizes['604p']) && $sizes['604p'] !== null) {
								$x .= ',"src_big":"'.getUrl($sizes['604p']).'"';
							}
						}
						if(property_exists($pht,'likes')) {
							$x .= ',"likes":{"count":'.$pht->{'likes'}->{'count'};
							if(property_exists($pht->{'likes'},'user_likes'))
								$x .= ',"user_likes":'.$pht->{'likes'}->{'user_likes'};
							$x .= '}';
						}
						if(property_exists($pht,'comments')) {
							$x .= ',"comments":{"count":'.$pht->{'comments'}->{'count'};
							if(property_exists($pht->{'comments'},'can_post'))
								$x .= ',"can_post":'.$pht->{'comments'}->{'can_post'};
							$x .= '}';
						}
						$x .= '}';
						$i++;
						if($i < count($items))
							$x .= ',';
					}
				}
				echo('{"response":['.$x.']}');
				exit();
			} else error(10, 'photos.getById: json_decode() failed\n"'.$g.'"', $PARAMS);
			break;
		}
		default: {
			error(3, 'Undefined method: '.$method, $PARAMS);
			break;
		}
	}
} catch (Exception $e) {
	error(10, $e->__toString());
}
?>
