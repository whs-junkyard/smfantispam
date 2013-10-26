<?php

/**
 * This file is part of SMF 2 antispam
 * 
 * Copyright (c) 2013, Manatsawin Hanmongkolchai
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of the contributors may be used to endorse or
 *      promote products derived from this software without specific
 *      prior written permission.
 **/

require_once '../SSI.php';

if(!$context['user']['is_logged'] || !in_array($user_info['groups'][0], array(1, 2))){
	header('HTTP/1.0 401 Unauthorized');
	fatal_error('Protected access');
}

class AntiSpamServer{
	public static $allowed = array('get', 'save', 'delete', 'edit');
	public static $allowOnlyMethod = array(
		'save' => array('POST', 'PUT'),
		'delete' => array('POST', 'DELETE'),
		'edit' => array('POST'),
	);
	public static function get(){
		global $smcFunc, $user_profile;
		$q = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}antispam
		');
		$results = array();
		while($res = $smcFunc['db_fetch_assoc']($q)){
			if($res['edited_by'] === '0'){
				unset($res['edited_by']);
				unset($res['edited']);
			}else{
				if(!isset($user_profile[$res['edited_by']])){
					loadMemberData($res['edited_by'], false, 'minimal');
				}
				$res['edited_by'] = static::member_only_fields($user_profile[$res['edited_by']]);
			}
			if(!isset($user_profile[$res['created_by']])){
				loadMemberData($res['created_by'], false, 'minimal');
			}
			$res['created_by'] = static::member_only_fields($user_profile[$res['created_by']]);

			$results[] = $res;
		}
		$smcFunc['db_free_result']($q);
		return $results;
	}

	public static function save(){
		global $smcFunc, $context;
		$input = json_decode(file_get_contents('php://input'), true);
		if(empty($input['text'])){
			return self::get();
		}
		$smcFunc['db_query']('', '
			INSERT INTO {db_prefix}antispam
			(text, created_by)
			VALUES
			({string:text}, {int:created_by})
		', array(
			'text' => $input['text'],
			'created_by' => $context['user']['id']
		));
		return self::get();
	}

	public static function delete(){
		global $smcFunc, $context;
		$input = json_decode(file_get_contents('php://input'), true);
		if(empty($input['id'])){
			return self::get();
		}
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}antispam
			WHERE id = {int:id}
		', array(
			'id' => $input['id']
		));
		return self::get();
	}

	public static function edit(){
		global $smcFunc, $context;
		$input = json_decode(file_get_contents('php://input'), true);
		if(empty($input['text']) || empty($input['id'])){
			return self::get();
		}
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}antispam
			SET text = {string:text},
			edited_by = {int:user_id},
			edited = NOW()
			WHERE id = {int:id}
		', array(
			'id' => $input['id'],
			'text' => $input['text'],
			'user_id' => $context['user']['id']
		));
		return self::get();
	}

	public static function member_only_fields($member, $fields=array('id_member', 'member_name')){
		$out = array();
		foreach($fields as $field){
			$out[$field] = $member[$field];
		}
		return $out;
	}
}

if(empty($_GET['act'])){
	header('HTTP/1.0 400 Bad Request');
}else if(in_array($_GET['act'], AntiSpamServer::$allowed)){
	$act = $_GET['act'];
	if(array_key_exists($act, AntiSpamServer::$allowOnlyMethod)){
		if(!in_array($_SERVER['REQUEST_METHOD'], AntiSpamServer::$allowOnlyMethod[$act])){
			header('HTTP/1.0 405 Method Not Allowed');
			fatal_error('Method not allowed');
		}
	}
	$out = call_user_func(array('AntiSpamServer', $act));
	if(is_array($out)){
		header('Content-Type: application/json');
		// prevent JSON Hijacking
		echo "for(;;);\n";
		echo json_encode($out);
	}
}else{
	header('HTTP/1.0 400 Bad Request');
}